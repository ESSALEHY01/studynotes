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

// Get all modules for this user (for dropdown)
$stmt = $conn->prepare("SELECT * FROM modules WHERE user_id = ? ORDER BY module_name ASC");
$stmt->execute([$user_id]);
$modules = $stmt->fetchAll();

// Check if filtering by module
$module_filter = isset($_GET['module']) ? intval($_GET['module']) : null;
$filter_module_name = '';

if ($module_filter) {
    // Get module name for display
    $stmt = $conn->prepare("SELECT module_name FROM modules WHERE module_id = ? AND user_id = ?");
    $stmt->execute([$module_filter, $user_id]);
    $module = $stmt->fetch();
    if ($module) {
        $filter_module_name = $module['module_name'];
    }
}

// Check if note_id column exists in quizzes table for proper handling
$column_exists = false;
try {
    $check_column = $conn->prepare("SHOW COLUMNS FROM quizzes LIKE 'note_id'");
    $check_column->execute();
    $column_exists = $check_column->fetch() !== false;
} catch (PDOException $e) {
    // Column doesn't exist, continue with basic query
}

// Get quizzes for this user
if ($module_filter) {
    if ($column_exists) {
        $stmt = $conn->prepare("
            SELECT q.*,
                   CASE
                       WHEN q.note_id IS NULL THEN 'All Modules'
                       ELSE m.module_name
                   END as module_name,
                   (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.quiz_id) as question_count
            FROM quizzes q
            LEFT JOIN modules m ON q.module_id = m.module_id
            WHERE q.user_id = ? AND q.module_id = ?
            ORDER BY q.created_at DESC
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT q.*, m.module_name,
                   (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.quiz_id) as question_count
            FROM quizzes q
            JOIN modules m ON q.module_id = m.module_id
            WHERE q.user_id = ? AND q.module_id = ?
            ORDER BY q.created_at DESC
        ");
    }
    $stmt->execute([$user_id, $module_filter]);
} else {
    if ($column_exists) {
        $stmt = $conn->prepare("
            SELECT q.*,
                   CASE
                       WHEN q.note_id IS NULL THEN 'All Modules'
                       ELSE m.module_name
                   END as module_name,
                   (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.quiz_id) as question_count
            FROM quizzes q
            LEFT JOIN modules m ON q.module_id = m.module_id
            WHERE q.user_id = ?
            ORDER BY q.created_at DESC
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT q.*, m.module_name,
                   (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.quiz_id) as question_count
            FROM quizzes q
            JOIN modules m ON q.module_id = m.module_id
            WHERE q.user_id = ?
            ORDER BY q.created_at DESC
        ");
    }
    $stmt->execute([$user_id]);
}
$quizzes = $stmt->fetchAll();

// Get notes for this user (for generating new quizzes)
$stmt = $conn->prepare("
    SELECT n.*, m.module_name
    FROM notes n
    JOIN modules m ON n.module_id = m.module_id
    WHERE n.user_id = ?
    ORDER BY n.title ASC
");
$stmt->execute([$user_id]);
$notes = $stmt->fetchAll();

// Handle quiz actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete quiz
    if (isset($_POST['action']) && $_POST['action'] === 'delete_quiz') {
        $quiz_id = intval($_POST['quiz_id']);

        try {
            // Start transaction
            $conn->beginTransaction();

            // Delete quiz questions
            $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE quiz_id = ?");
            $stmt->execute([$quiz_id]);

            // Delete quiz
            $stmt = $conn->prepare("DELETE FROM quizzes WHERE quiz_id = ? AND user_id = ?");
            $stmt->execute([$quiz_id, $user_id]);

            // Commit transaction
            $conn->commit();

            // Redirect to refresh page
            header('Location: quizzes.php?success=Quiz deleted successfully');
            exit;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $error = "Error deleting quiz: " . $e->getMessage();
        }
    }
}

// Get success or error messages
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($error) ? $error : (isset($_GET['error']) ? $_GET['error'] : '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyNotes - Quizzes</title>
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
                <p class="tagline">AI-Powered Quizzes</p>
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
                        <h2>
                            Your Quizzes
                            <?php if ($module_filter && !empty($filter_module_name)): ?>
                                <span class="filter-badge">
                                    <?php echo htmlspecialchars($filter_module_name); ?>
                                    <a href="quizzes.php"><i class="fas fa-times"></i></a>
                                </span>
                            <?php endif; ?>
                        </h2>
                        <button id="generateQuizBtn" class="btn btn-slim btn-action"><i class="fas fa-plus"></i> Generate New Quiz</button>
                    </div>

                    <?php if (!empty($success)): ?>
                        <div class="success-message">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="error-message">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Quizzes Grid -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (count($quizzes) > 0): ?>
                                <div class="grid-container">
                                    <?php foreach ($quizzes as $quiz): ?>
                                        <div class="content-card quiz-card">
                                            <div class="content-card-header">
                                                <h4><?php echo htmlspecialchars($quiz['quiz_title']); ?></h4>
                                                <span class="module-badge"><?php echo htmlspecialchars($quiz['module_name']); ?></span>
                                            </div>
                                            <div class="content-card-body quiz-info">
                                                <p><i class="fas fa-question-circle"></i> <?php echo $quiz['question_count']; ?> questions</p>
                                                <p><i class="fas fa-calendar"></i> Created: <?php echo date('M d, Y', strtotime($quiz['created_at'])); ?></p>
                                            </div>
                                            <div class="content-card-footer">
                                                <span class="content-card-date"><i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($quiz['created_at'])); ?></span>
                                                <div class="content-card-actions">
                                                    <a href="take_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn-sm"><i class="fas fa-play"></i> Take Quiz</a>
                                                    <button class="btn-sm btn-danger delete-quiz-btn" data-id="<?php echo $quiz['quiz_id']; ?>" data-title="<?php echo htmlspecialchars($quiz['quiz_title']); ?>">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-question-circle"></i>
                                    <h3>No Quizzes Yet</h3>
                                    <p>Generate your first quiz by clicking the "Generate New Quiz" button above.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> StudyNotes. All rights reserved.</p>
        </footer>
    </div>

    <!-- Generate Quiz Modal -->
    <div id="generateQuizModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Generate New Quiz</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="generateQuizForm">
                    <div class="form-group">
                        <label for="note_id"><i class="fas fa-sticky-note"></i> Select Note</label>
                        <div class="custom-select-container">
                            <select id="note_id" name="note_id" class="custom-select" required>
                                <option value="" disabled selected>Choose a note</option>
                                <?php foreach ($notes as $note): ?>
                                    <option value="<?php echo $note['note_id']; ?>" data-module="<?php echo $note['module_id']; ?>">
                                        <?php echo htmlspecialchars($note['title']); ?> (<?php echo htmlspecialchars($note['module_name']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="quiz_title"><i class="fas fa-heading"></i> Quiz Title</label>
                        <input type="text" id="quiz_title" name="quiz_title" required>
                    </div>

                    <div class="form-group">
                        <label for="num_questions"><i class="fas fa-list-ol"></i> Number of Questions</label>
                        <input type="number" id="num_questions" name="num_questions" min="1" max="20" value="5" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-sliders-h"></i> Difficulty Level</label>
                        <div class="difficulty-radio-group">
                            <div class="difficulty-radio-item easy">
                                <input type="radio" id="modal_easy" name="difficulty" value="easy">
                                <label for="modal_easy" class="difficulty-radio-label">
                                    <i class="fas fa-seedling"></i>
                                    <span>Easy</span>
                                </label>
                            </div>
                            <div class="difficulty-radio-item medium">
                                <input type="radio" id="modal_medium" name="difficulty" value="medium" checked>
                                <label for="modal_medium" class="difficulty-radio-label">
                                    <i class="fas fa-balance-scale"></i>
                                    <span>Medium</span>
                                </label>
                            </div>
                            <div class="difficulty-radio-item hard">
                                <input type="radio" id="modal_hard" name="difficulty" value="hard">
                                <label for="modal_hard" class="difficulty-radio-label">
                                    <i class="fas fa-fire"></i>
                                    <span>Hard</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="module_id" name="module_id" value="0">

                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary btn-slim cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-slim btn-action">
                            <i class="fas fa-magic"></i> Generate Quiz
                        </button>
                    </div>
                </form>
                <div id="generationProgress" class="generation-progress" style="display: none;">
                    <div class="spinner"></div>
                    <p>Generating quiz questions with AI...</p>
                    <p class="small">This may take a few moments.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Quiz Modal -->
    <div id="deleteQuizModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Quiz</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete quiz <strong id="delete_quiz_title"></strong>?</p>
                <p class="warning">This action cannot be undone.</p>
                <form action="quizzes.php" method="post">
                    <input type="hidden" name="action" value="delete_quiz">
                    <input type="hidden" name="quiz_id" id="delete_quiz_id">
                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary btn-slim cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-slim">Delete Quiz</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/common.js"></script>
    <script src="js/shared-modal.js"></script>
    <script src="js/quiz.js"></script>
</body>
</html>
