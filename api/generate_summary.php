<?php
/**
 * API endpoint for generating summaries using AI
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
if (!isset($data['note_id']) || !isset($data['max_length'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$note_id = intval($data['note_id']);
$max_length = intval($data['max_length']);

// Validate max length
if ($max_length < 100 || $max_length > 2000) {
    http_response_code(400);
    echo json_encode(['error' => 'Max length must be between 100 and 2000 characters']);
    exit;
}

// Get note content
try {
    $stmt = $conn->prepare("
        SELECT n.*
        FROM notes n
        WHERE n.note_id = ? AND n.user_id = ?
    ");
    $stmt->execute([$note_id, $user_id]);
    $note = $stmt->fetch();

    if (!$note) {
        http_response_code(404);
        echo json_encode(['error' => 'Note not found']);
        exit;
    }

    // Check if a summary already exists for this note
    $stmt = $conn->prepare("
        SELECT summary_id, summary_content
        FROM ai_summaries
        WHERE note_id = ?
    ");
    $stmt->execute([$note_id]);
    $existing_summary = $stmt->fetch();

    // Log the request
    ai_debug_log("Generating summary for note: " . $note['title'], [
        'note_id' => $note_id,
        'max_length' => $max_length,
        'content_length' => strlen($note['content'])
    ]);

    // Generate summary using AI
    $summary_content = generate_summary($note['content'], $note['title'], $max_length);

    if (!$summary_content) {
        ai_debug_log("Failed to generate summary", [
            'note_id' => $note_id,
            'note_title' => $note['title']
        ]);

        http_response_code(500);
        echo json_encode(['error' => 'Failed to generate summary']);
        exit;
    }

    // Log success
    ai_debug_log("Successfully generated summary", [
        'note_id' => $note_id,
        'summary_length' => strlen($summary_content)
    ]);

    // Start a transaction
    $conn->beginTransaction();

    if ($existing_summary) {
        // Update existing summary
        $stmt = $conn->prepare("
            UPDATE ai_summaries
            SET summary_content = ?, created_at = NOW()
            WHERE summary_id = ?
        ");
        $stmt->execute([$summary_content, $existing_summary['summary_id']]);
        $summary_id = $existing_summary['summary_id'];
    } else {
        // Create a new summary
        $stmt = $conn->prepare("
            INSERT INTO ai_summaries (note_id, summary_content, created_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$note_id, $summary_content]);
        $summary_id = $conn->lastInsertId();
    }

    // Commit the transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'summary_id' => $summary_id,
        'summary_content' => $summary_content,
        'message' => 'Summary generated successfully'
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
