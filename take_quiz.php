
<?php
session_start();
require_once('db_connection.php');
require_once('includes/functions.php');

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Check if quiz ID is provided
if (!isset($_GET['id'])) {
    header('Location: quizzes.php?error=Quiz ID is required');
    exit;
}

$quiz_id = intval($_GET['id']);

// Check if note_id column exists in quizzes table
$column_exists = false;
try {
    $check_column = $conn->prepare("SHOW COLUMNS FROM quizzes LIKE 'note_id'");
    $check_column->execute();
    $column_exists = $check_column->fetch() !== false;
} catch (PDOException $e) {
    // Column doesn't exist, continue with basic query
}

// Check if difficulty column exists
$difficulty_column_exists = false;
try {
    $check_difficulty = $conn->prepare("SHOW COLUMNS FROM quizzes LIKE 'difficulty'");
    $check_difficulty->execute();
    $difficulty_column_exists = $check_difficulty->fetch() !== false;
} catch (PDOException $e) {
    // Column doesn't exist, continue without it
}

// Get quiz details
if ($column_exists) {
    // If note_id column exists, include it in the query
    // Check if note_id is NULL to identify "all notes" quizzes
    if ($difficulty_column_exists) {
        $stmt = $conn->prepare("
            SELECT q.*,
                   CASE
                       WHEN q.note_id IS NULL THEN 'All Modules'
                       ELSE m.module_name
                   END as module_name,
                   q.note_id,
                   q.difficulty
            FROM quizzes q
            LEFT JOIN modules m ON q.module_id = m.module_id
            WHERE q.quiz_id = ? AND q.user_id = ?
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT q.*,
                   CASE
                       WHEN q.note_id IS NULL THEN 'All Modules'
                       ELSE m.module_name
                   END as module_name,
                   q.note_id
            FROM quizzes q
            LEFT JOIN modules m ON q.module_id = m.module_id
            WHERE q.quiz_id = ? AND q.user_id = ?
        ");
    }
} else {
    // If note_id column doesn't exist, use a simpler query
    $stmt = $conn->prepare("
        SELECT q.*, m.module_name
        FROM quizzes q
        JOIN modules m ON q.module_id = m.module_id
        WHERE q.quiz_id = ? AND q.user_id = ?
    ");
}
$stmt->execute([$quiz_id, $user_id]);
$quiz = $stmt->fetch();

// Get note details if quiz is based on a note and note_id column exists
$note_title = '';
if ($column_exists && isset($quiz['note_id']) && $quiz['note_id'] > 0) {
    $stmt = $conn->prepare("SELECT title FROM notes WHERE note_id = ? AND user_id = ?");
    $stmt->execute([$quiz['note_id'], $user_id]);
    $note = $stmt->fetch();
    if ($note) {
        $note_title = $note['title'];
    }
}

// Check if quiz exists
if (!$quiz) {
    header('Location: quizzes.php?error=Quiz not found');
    exit;
}

// Get quiz questions
$stmt = $conn->prepare("
    SELECT *
    FROM quiz_questions
    WHERE quiz_id = ?
    ORDER BY question_id ASC
");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

// Check if quiz_options table exists
$has_options_table = false;
try {
    $check_table = $conn->prepare("SHOW TABLES LIKE 'quiz_options'");
    $check_table->execute();
    $has_options_table = $check_table->fetch() !== false;
} catch (PDOException $e) {
    // Table doesn't exist, continue with basic quiz
}

// Get question options if the table exists
$question_options = [];
if ($has_options_table) {
    foreach ($questions as $question) {
        $options_stmt = $conn->prepare("
            SELECT option_id, option_text, is_correct
            FROM quiz_options
            WHERE question_id = ?
            ORDER BY option_id ASC
        ");
        $options_stmt->execute([$question['question_id']]);
        $options = $options_stmt->fetchAll();

        if (count($options) > 0) {
            $question_options[$question['question_id']] = $options;
        }
    }
}

// All quizzes are now multiple-choice format

// Check if quiz has questions
if (count($questions) === 0) {
    header('Location: quizzes.php?error=Quiz has no questions');
    exit;
}

// Handle quiz submission
$quiz_completed = false;
$score = 0;
$total_questions = count($questions);
$user_answers = [];
$feedback = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_quiz') {
    $quiz_completed = true;

    foreach ($questions as $index => $question) {
        $question_id = $question['question_id'];

        // All questions are now multiple-choice format
        $selected_option_id = isset($_POST['option_' . $question_id]) ? intval($_POST['option_' . $question_id]) : 0;
        $selected_option_text = '';
        $correct_option_text = '';
        $is_correct = false;

        // Find the selected option text and check if it's correct
        if (isset($question_options[$question_id])) {
            foreach ($question_options[$question_id] as $option) {
                if ($option['option_id'] == $selected_option_id) {
                    $selected_option_text = $option['option_text'];
                    $is_correct = ($option['is_correct'] == 1);
                }
                if ($option['is_correct'] == 1) {
                    $correct_option_text = $option['option_text'];
                }
            }
        }

        // Store the user's answer
        $user_answers[$question_id] = [
            'option_id' => $selected_option_id,
            'option_text' => $selected_option_text
        ];

        if ($is_correct) {
            $score++;
            $feedback[$question_id] = [
                'status' => 'correct',
                'message' => 'Correct!'
            ];
        } else {
            $feedback[$question_id] = [
                'status' => 'incorrect',
                'message' => 'Incorrect. The correct answer is: ' . $correct_option_text
            ];
        }
    }
}

// Calculate percentage score
$percentage = $total_questions > 0 ? round(($score / $total_questions) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyNotes - Take Quiz</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="css/form-elements.css">
    <link rel="stylesheet" href="css/quiz.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>StudyNotes</h1>
                <p class="tagline">Take Quiz</p>
            </div>
            <div class="user-profile">
                <span class="user-name"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <main>
            <div class="dashboard-container">
                <?php
                $active_page = 'quizzes';
                include('includes/sidebar.php');
                ?>

                <div class="content">
                    <div class="page-header">
                        <h2>Quiz: <?php echo htmlspecialchars($quiz['quiz_title']); ?></h2>
                        <div class="page-actions">
                            <?php if ($column_exists && isset($quiz['note_id']) && $quiz['note_id'] > 0): ?>
                                <a href="view_note.php?id=<?php echo $quiz['note_id']; ?>&from_quiz=<?php echo $quiz_id; ?>" class="btn btn-slim btn-nav">
                                    <i class="fas fa-sticky-note"></i> View Original Note
                                </a>
                            <?php endif; ?>
                            <a href="quizzes.php" class="btn btn-slim btn-nav"><i class="fas fa-arrow-left"></i> Back to Quizzes</a>
                        </div>
                    </div>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="success-message">
                            <?php echo htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['warning'])): ?>
                        <div class="warning-message">
                            <div class="warning-content">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div class="warning-text">
                                    <div class="warning-title">AI Rate Limit Reached</div>
                                    <div><?php echo htmlspecialchars($_GET['warning']); ?></div>
                                    <div class="warning-subtitle">💡 Tip: Try again in a few hours for AI-powered, content-specific questions tailored to your notes.</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="quiz-container">
                        <div class="quiz-info-bar">
                            <div class="quiz-meta">
                                <span class="module-badge"><?php echo htmlspecialchars($quiz['module_name']); ?></span>
                                <?php if ($difficulty_column_exists && isset($quiz['difficulty'])): ?>
                                    <?php
                                    $difficulty = $quiz['difficulty'];
                                    $difficulty_icons = [
                                        'easy' => 'fas fa-seedling',
                                        'medium' => 'fas fa-balance-scale',
                                        'hard' => 'fas fa-fire'
                                    ];
                                    $icon = isset($difficulty_icons[$difficulty]) ? $difficulty_icons[$difficulty] : 'fas fa-chart-line';
                                    ?>
                                    <span class="difficulty-badge <?php echo htmlspecialchars($difficulty); ?>">
                                        <i class="<?php echo $icon; ?>"></i>
                                        <?php echo ucfirst(htmlspecialchars($difficulty)); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="question-count"><i class="fas fa-question-circle"></i> <?php echo count($questions); ?> questions</span>
                            </div>
                            <?php if ($quiz_completed): ?>
                                <div class="quiz-score <?php echo $percentage >= 70 ? 'high-score' : ($percentage >= 40 ? 'medium-score' : 'low-score'); ?>">
                                    <span class="score-label">Score:</span>
                                    <span class="score-value"><?php echo $score; ?>/<?php echo $total_questions; ?> (<?php echo $percentage; ?>%)</span>
                                </div>
                            <?php endif; ?>
                        </div>



                        <?php if (!$quiz_completed): ?>
                            <form method="post" action="take_quiz.php?id=<?php echo $quiz_id; ?>" id="quizForm">
                                <input type="hidden" name="action" value="submit_quiz">

                                <?php foreach ($questions as $index => $question): ?>
                                    <div class="question-card">
                                        <div class="question-number">Question <?php echo $index + 1; ?></div>
                                        <div class="question-content-container">
                                            <div class="question-text" id="questionText_<?php echo $question['question_id']; ?>"><?php echo htmlspecialchars($question['question_text']); ?></div>
                                            <div class="question-expand-collapse" id="expandCollapseBtn_<?php echo $question['question_id']; ?>" style="display: none;">
                                                <button type="button" class="btn btn-sm btn-slim btn-accent toggle-question-btn" data-question-id="<?php echo $question['question_id']; ?>">
                                                    <i class="fas fa-chevron-down"></i> <span>Show More</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- All questions are now multiple-choice -->
                                        <div class="answer-options">
                                            <div class="options-label">Select your answer:</div>
                                            <?php
                                            // Shuffle the options to randomize the order
                                            $options = $question_options[$question['question_id']];
                                            shuffle($options);

                                            foreach ($options as $option):
                                            ?>
                                                <div class="option-item">
                                                    <input type="radio"
                                                           id="option_<?php echo $option['option_id']; ?>"
                                                           name="option_<?php echo $question['question_id']; ?>"
                                                           value="<?php echo $option['option_id']; ?>"
                                                           required>
                                                    <label for="option_<?php echo $option['option_id']; ?>"><?php echo htmlspecialchars($option['option_text']); ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="quiz-actions">
                                    <button type="submit" class="btn btn-primary btn-slim btn-action"><i class="fas fa-check-circle"></i> Submit Answers</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="quiz-results">
                                <div class="results-header">
                                    <h3>Quiz Results</h3>
                                    <div class="results-summary">
                                        <div class="result-item">
                                            <span class="result-label">Total Questions:</span>
                                            <span class="result-value"><?php echo $total_questions; ?></span>
                                        </div>
                                        <div class="result-item">
                                            <span class="result-label">Correct Answers:</span>
                                            <span class="result-value"><?php echo $score; ?></span>
                                        </div>
                                        <div class="result-item">
                                            <span class="result-label">Score:</span>
                                            <span class="result-value <?php echo $percentage >= 70 ? 'high-score' : ($percentage >= 40 ? 'medium-score' : 'low-score'); ?>"><?php echo $percentage; ?>%</span>
                                        </div>
                                    </div>
                                </div>

                                <?php foreach ($questions as $index => $question): ?>
                                    <?php $question_id = $question['question_id']; ?>
                                    <div class="question-card <?php echo $feedback[$question_id]['status']; ?>">
                                        <div class="question-number">Question <?php echo $index + 1; ?></div>
                                        <div class="question-content-container">
                                            <div class="question-text" id="questionText_result_<?php echo $question_id; ?>"><?php echo htmlspecialchars($question['question_text']); ?></div>
                                            <div class="question-expand-collapse" id="expandCollapseBtn_result_<?php echo $question_id; ?>" style="display: none;">
                                                <button type="button" class="btn btn-sm btn-slim btn-accent toggle-question-btn" data-question-id="result_<?php echo $question_id; ?>">
                                                    <i class="fas fa-chevron-down"></i> <span>Show More</span>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="answer-section">
                                            <!-- All questions are now multiple-choice -->
                                            <div class="options-results">
                                                <?php
                                                $selected_option_id = isset($user_answers[$question_id]['option_id']) ? $user_answers[$question_id]['option_id'] : 0;
                                                $selected_option_text = isset($user_answers[$question_id]['option_text']) ? $user_answers[$question_id]['option_text'] : '';

                                                foreach ($question_options[$question_id] as $option):
                                                    $option_class = '';
                                                    if ($option['is_correct'] == 1) {
                                                        $option_class = 'correct-option';
                                                    }
                                                    if ($option['option_id'] == $selected_option_id && $option['is_correct'] != 1) {
                                                        $option_class = 'incorrect-option';
                                                    }
                                                ?>
                                                    <div class="option-result <?php echo $option_class; ?>">
                                                        <span class="option-marker">
                                                            <?php if ($option['option_id'] == $selected_option_id): ?>
                                                                <i class="fas fa-check-circle"></i>
                                                            <?php elseif ($option['is_correct'] == 1): ?>
                                                                <i class="fas fa-circle"></i>
                                                            <?php else: ?>
                                                                <i class="far fa-circle"></i>
                                                            <?php endif; ?>
                                                        </span>
                                                        <span class="option-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="feedback-message">
                                                <?php if ($feedback[$question_id]['status'] === 'correct'): ?>
                                                    <i class="fas fa-check-circle"></i> Correct!
                                                <?php else: ?>
                                                    <i class="fas fa-times-circle"></i> Incorrect
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="quiz-actions">
                                    <a href="take_quiz.php?id=<?php echo $quiz_id; ?>" class="btn btn-slim btn-action"><i class="fas fa-redo"></i> Retake Quiz</a>
                                    <a href="quizzes.php" class="btn btn-slim btn-nav"><i class="fas fa-arrow-left"></i> Back to Quizzes</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> StudyNotes. All rights reserved.</p>
        </footer>
    </div>

    <script src="js/common.js"></script>
    <script src="js/quiz.js"></script>
</body>
</html>


