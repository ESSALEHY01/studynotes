<?php
/**
 * API endpoint for generating quizzes using AI
 */
session_start();
require_once('../db_connection.php');
require_once('../includes/functions.php');
require_once('../includes/ai_functions.php');

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['note_id']) || !isset($data['num_questions']) || !isset($data['difficulty']) || !isset($data['quiz_title'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$note_id = intval($data['note_id']);
$num_questions = intval($data['num_questions']);
$difficulty = $data['difficulty'];
$quiz_title = trim($data['quiz_title']);
$module_id = isset($data['module_id']) ? intval($data['module_id']) : 0;

// Validate number of questions
if ($num_questions < 1 || $num_questions > 20) {
    http_response_code(400);
    echo json_encode(['error' => 'Number of questions must be between 1 and 20']);
    exit;
}

// Validate difficulty
$valid_difficulties = ['easy', 'medium', 'hard'];
if (!in_array($difficulty, $valid_difficulties)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid difficulty']);
    exit;
}

// Get note content
try {
    $stmt = $conn->prepare("
        SELECT n.*, m.module_id
        FROM notes n
        JOIN modules m ON n.module_id = m.module_id
        WHERE n.note_id = ? AND n.user_id = ?
    ");
    $stmt->execute([$note_id, $user_id]);
    $note = $stmt->fetch();

    if (!$note) {
        http_response_code(404);
        echo json_encode(['error' => 'Note not found']);
        exit;
    }

    // If module_id is not provided, use the note's module_id
    if ($module_id === 0) {
        $module_id = $note['module_id'];
    }

    // Log the request
    ai_debug_log("Generating quiz for note: " . $note['title'], [
        'note_id' => $note_id,
        'num_questions' => $num_questions,
        'difficulty' => $difficulty,
        'content_length' => strlen($note['content'])
    ]);

    // Generate quiz questions using AI
    $questions = generate_quiz($note['content'], $note['title'], $num_questions, $difficulty);

    if (!$questions) {
        ai_debug_log("Failed to generate quiz questions", [
            'note_id' => $note_id,
            'note_title' => $note['title'],
            'difficulty' => $difficulty
        ]);

        http_response_code(500);
        echo json_encode(['error' => 'Failed to generate quiz questions']);
        exit;
    }

    // Log success
    ai_debug_log("Successfully generated quiz questions", [
        'note_id' => $note_id,
        'question_count' => count($questions)
    ]);

    // Start a transaction
    $conn->beginTransaction();

    // Check if note_id column exists in quizzes table
    $column_exists = false;
    try {
        $check_column = $conn->prepare("SHOW COLUMNS FROM quizzes LIKE 'note_id'");
        $check_column->execute();
        $column_exists = $check_column->fetch() !== false;
    } catch (PDOException $e) {
        // Column doesn't exist, continue with basic query
    }

    // Create a new quiz
    if ($column_exists) {
        // Check if difficulty column exists
        $difficulty_column_exists = false;
        try {
            $check_difficulty = $conn->prepare("SHOW COLUMNS FROM quizzes LIKE 'difficulty'");
            $check_difficulty->execute();
            $difficulty_column_exists = $check_difficulty->fetch() !== false;
        } catch (PDOException $e) {
            // Column doesn't exist, continue without it
        }

        if ($difficulty_column_exists) {
            // If both note_id and difficulty columns exist
            $stmt = $conn->prepare("
                INSERT INTO quizzes (user_id, module_id, quiz_title, note_id, difficulty, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $module_id, $quiz_title, $note_id, $difficulty]);
        } else {
            // If only note_id column exists
            $stmt = $conn->prepare("
                INSERT INTO quizzes (user_id, module_id, quiz_title, note_id, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $module_id, $quiz_title, $note_id]);
        }
    } else {
        // If note_id column doesn't exist, use the original query
        $stmt = $conn->prepare("
            INSERT INTO quizzes (user_id, module_id, quiz_title, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $module_id, $quiz_title]);
    }
    $quiz_id = $conn->lastInsertId();

    // All questions are now multiple-choice format

    // Insert quiz questions for multiple-choice format
    $stmt = $conn->prepare("
        INSERT INTO quiz_questions (quiz_id, question_text, correct_answer, created_at)
        VALUES (?, ?, ?, NOW())
    ");

    // Prepare statement for inserting options
    $options_stmt = $conn->prepare("
        INSERT INTO quiz_options (question_id, option_text, is_correct)
        VALUES (?, ?, ?)
    ");

    foreach ($questions as $question) {
        // Insert the question
        $stmt->execute([$quiz_id, $question['question'], $question['correct_answer']]);
        $question_id = $conn->lastInsertId();

        // Insert the correct answer as an option
        $options_stmt->execute([$question_id, $question['correct_answer'], 1]);

        // Insert the incorrect answers as options
        foreach ($question['incorrect_answers'] as $incorrect_answer) {
            $options_stmt->execute([$question_id, $incorrect_answer, 0]);
        }
    }

    // Commit the transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'quiz_id' => $quiz_id,
        'message' => 'Quiz generated successfully',
        'questions' => $questions
    ]);

} catch (PDOException $e) {
    // Rollback the transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
}
?>
