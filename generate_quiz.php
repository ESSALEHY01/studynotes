
<?php
session_start();
require_once('db_connection.php');
require_once('includes/functions.php');
require_once('includes/ai_functions.php');

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Check if this is a request to generate quiz from all notes
$source = isset($_GET['source']) ? $_GET['source'] : '';

if ($source === 'all_notes') {
    // Handle "Generate Quiz from All Notes" functionality
    
    // Get all notes for this user
    $stmt = $conn->prepare("
        SELECT n.*, m.module_name
        FROM notes n
        JOIN modules m ON n.module_id = m.module_id
        WHERE n.user_id = ?
        ORDER BY n.updated_at DESC
    ");
    $stmt->execute([$user_id]);
    $notes = $stmt->fetchAll();
    
    // Check if user has any notes
    if (count($notes) === 0) {
        header('Location: notes.php?error=You need to create some notes before generating a quiz from all notes');
        exit;
    }
    
    // Handle form submission for quiz generation
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $quiz_title = trim($_POST['quiz_title']);
        $num_questions = intval($_POST['num_questions']);
        $difficulty = $_POST['difficulty'];
        
        // Validate input
        if (empty($quiz_title)) {
            $error = "Quiz title is required";
        } elseif ($num_questions < 1 || $num_questions > 20) {
            $error = "Number of questions must be between 1 and 20";
        } elseif (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
            $error = "Invalid difficulty level";
        } else {
            try {
                // Combine content from all notes
                $combined_content = "";
                $note_titles = [];
                
                foreach ($notes as $note) {
                    $combined_content .= "\n\n=== " . $note['title'] . " (Module: " . $note['module_name'] . ") ===\n";
                    $combined_content .= strip_tags($note['content']);
                    $note_titles[] = $note['title'];
                }
                
                // Create a comprehensive title for the combined content
                $combined_title = "Comprehensive Study Material from All Notes";
                
                // Log the request
                ai_debug_log("Generating quiz from all notes", [
                    'user_id' => $user_id,
                    'note_count' => count($notes),
                    'num_questions' => $num_questions,
                    'difficulty' => $difficulty,
                    'content_length' => strlen($combined_content)
                ]);
                
                // Generate quiz questions using AI with combined content
                $questions = generate_quiz($combined_content, $combined_title, $num_questions, $difficulty);
                
                if (!$questions) {
                    ai_debug_log("Failed to generate quiz questions from all notes", [
                        'user_id' => $user_id,
                        'note_count' => count($notes),
                        'difficulty' => $difficulty
                    ]);

                    // Check if this might be due to rate limiting by looking at recent logs
                    $log_file = 'logs/ai_debug.log';
                    $rate_limit_detected = false;
                    if (file_exists($log_file)) {
                        $recent_logs = file_get_contents($log_file);
                        if (strpos($recent_logs, 'Rate limit') !== false || strpos($recent_logs, '429') !== false) {
                            $rate_limit_detected = true;
                        }
                    }

                    if ($rate_limit_detected) {
                        $error = "AI service is temporarily unavailable due to rate limits. The system has generated placeholder questions instead. Please try again later for AI-generated content.";
                    } else {
                        $error = "Failed to generate quiz questions. Please try again.";
                    }
                } else {
                    // Check if we got fallback questions (indicates rate limiting or API issues)
                    $is_fallback = false;
                    if (isset($questions[0]['question'])) {
                        $first_question = $questions[0]['question'];
                        // Check for fallback question patterns
                        if (strpos($first_question, 'main topic of') !== false ||
                            strpos($first_question, 'Comprehensive Study Material') !== false) {
                            $is_fallback = true;
                        }
                    }

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
                    
                    // For "all notes" quiz, we'll use the first available module_id
                    // and note_id = NULL to indicate it's not based on a single note
                    // We'll identify "all notes" quizzes by having note_id = NULL
                    $first_module_id = $notes[0]['module_id']; // Use first note's module as representative

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
                            $stmt = $conn->prepare("
                                INSERT INTO quizzes (user_id, module_id, quiz_title, note_id, difficulty, created_at)
                                VALUES (?, ?, ?, NULL, ?, NOW())
                            ");
                            $stmt->execute([$user_id, $first_module_id, $quiz_title, $difficulty]);
                        } else {
                            $stmt = $conn->prepare("
                                INSERT INTO quizzes (user_id, module_id, quiz_title, note_id, created_at)
                                VALUES (?, ?, ?, NULL, NOW())
                            ");
                            $stmt->execute([$user_id, $first_module_id, $quiz_title]);
                        }
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
                    
                    // Log success
                    ai_debug_log("Successfully generated quiz from all notes", [
                        'user_id' => $user_id,
                        'quiz_id' => $quiz_id,
                        'question_count' => count($questions)
                    ]);
                    
                    // Redirect to take quiz page with appropriate message
                    if ($is_fallback) {
                        header("Location: take_quiz.php?id={$quiz_id}&warning=Quiz generated with general study questions. The AI service has reached its daily usage limit and cannot create content-specific questions at this time.");
                    } else {
                        header("Location: take_quiz.php?id={$quiz_id}&success=Quiz generated successfully from all notes with AI-powered content-specific questions");
                    }
                    exit;
                }
                
            } catch (PDOException $e) {
                // Rollback the transaction on error
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                
                $error = "Database error: " . $e->getMessage();
                ai_debug_log("Database error during quiz generation", ['error' => $e->getMessage()]);
            } catch (Exception $e) {
                $error = "Server error: " . $e->getMessage();
                ai_debug_log("Server error during quiz generation", ['error' => $e->getMessage()]);
            }
        }
    }
} else {
    // Invalid source parameter
    header('Location: notes.php?error=Invalid quiz generation source');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyNotes - Generate Quiz from All Notes</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="css/form-elements.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .generation-progress {
            text-align: center;
            padding: 40px 20px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--highlight-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .notes-summary {
            background-color: rgba(101, 53, 15, 0.05);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }

        .notes-summary h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notes-count {
            background-color: var(--highlight-color);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }

        .module-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .module-item {
            background-color: white;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid rgba(101, 53, 15, 0.1);
        }

        .module-item h4 {
            color: var(--primary-color);
            margin: 0 0 5px 0;
            font-size: 14px;
        }

        .module-item .note-count {
            color: var(--secondary-color);
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>StudyNotes</h1>
                <p class="tagline">Your Study Notes</p>
            </div>
            <div class="user-profile">
                <span class="user-name"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <main>
            <div class="dashboard-container">
                <?php
                $active_page = 'notes';
                include('includes/sidebar.php');
                ?>

                <div class="content">
                    <div class="page-header">
                        <h2>Generate Quiz from All Notes</h2>
                        <div class="page-actions">
                            <a href="notes.php" class="btn btn-slim btn-nav"><i class="fas fa-arrow-left"></i> Back to Notes</a>
                        </div>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="error-message">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Notes Summary -->
                    <div class="notes-summary">
                        <h3>
                            <i class="fas fa-sticky-note"></i>
                            Your Notes Collection
                            <span class="notes-count"><?php echo count($notes); ?> Notes</span>
                        </h3>
                        <p>This quiz will be generated from content across all your notes, covering multiple topics and modules for comprehensive testing.</p>

                        <?php
                        // Group notes by module for display
                        $modules_breakdown = [];
                        foreach ($notes as $note) {
                            $module_name = $note['module_name'];
                            if (!isset($modules_breakdown[$module_name])) {
                                $modules_breakdown[$module_name] = 0;
                            }
                            $modules_breakdown[$module_name]++;
                        }
                        ?>

                        <div class="module-breakdown">
                            <?php foreach ($modules_breakdown as $module_name => $note_count): ?>
                                <div class="module-item">
                                    <h4><?php echo htmlspecialchars($module_name); ?></h4>
                                    <div class="note-count"><?php echo $note_count; ?> note<?php echo $note_count !== 1 ? 's' : ''; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Quiz Generation Form -->
                    <div class="card">
                        <div class="card-body">
                            <form method="post" id="generateQuizForm">
                                <div class="form-group">
                                    <label for="quiz_title"><i class="fas fa-heading"></i> Quiz Title</label>
                                    <input type="text" id="quiz_title" name="quiz_title"
                                           value="Comprehensive Quiz - All Topics"
                                           placeholder="Enter a title for your quiz" required>
                                </div>

                                <div class="form-group">
                                    <label for="num_questions"><i class="fas fa-list-ol"></i> Number of Questions</label>
                                    <input type="number" id="num_questions" name="num_questions"
                                           min="1" max="20" value="10" required>
                                    <small>Choose between 1 and 20 questions</small>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-chart-line"></i> Difficulty Level</label>
                                    <div class="difficulty-radio-group">
                                        <div class="difficulty-radio-item easy">
                                            <input type="radio" id="easy" name="difficulty" value="easy">
                                            <label for="easy" class="difficulty-radio-label">
                                                <i class="fas fa-seedling"></i>
                                                <span>Easy</span>
                                            </label>
                                        </div>
                                        <div class="difficulty-radio-item medium">
                                            <input type="radio" id="medium" name="difficulty" value="medium" checked>
                                            <label for="medium" class="difficulty-radio-label">
                                                <i class="fas fa-balance-scale"></i>
                                                <span>Medium</span>
                                            </label>
                                        </div>
                                        <div class="difficulty-radio-item hard">
                                            <input type="radio" id="hard" name="difficulty" value="hard">
                                            <label for="hard" class="difficulty-radio-label">
                                                <i class="fas fa-fire"></i>
                                                <span>Hard</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-buttons">
                                    <a href="notes.php" class="btn btn-secondary btn-slim btn-nav"><i class="fas fa-times"></i> Cancel</a>
                                    <button type="submit" class="btn btn-primary btn-slim btn-action">
                                        <i class="fas fa-magic"></i> Generate Comprehensive Quiz
                                    </button>
                                </div>
                            </form>

                            <!-- Progress Indicator -->
                            <div id="generationProgress" class="generation-progress" style="display: none;">
                                <div class="spinner"></div>
                                <p>Analyzing all your notes and generating comprehensive quiz questions...</p>
                                <p class="small">This may take a few moments as we process content from <?php echo count($notes); ?> notes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> StudyNotes. All rights reserved.</p>
        </footer>
    </div>

    <script src="js/common.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('generateQuizForm');
            const progressDiv = document.getElementById('generationProgress');

            if (form) {
                form.addEventListener('submit', function(e) {
                    // Show progress indicator
                    form.style.display = 'none';
                    progressDiv.style.display = 'block';

                    // Let the form submit normally (no preventDefault)
                    // The PHP will handle the processing and redirect
                });
            }
        });
    </script>
</body>
</html>

