<?php
session_start();
require_once('../db_connection.php');
require_once('../includes/functions.php');

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_quiz_options_table') {
        try {
            // Check if the table already exists
            $stmt = $conn->prepare("SHOW TABLES LIKE 'quiz_options'");
            $stmt->execute();
            $table_exists = $stmt->fetch();
            
            if (!$table_exists) {
                // Create the quiz_options table if it doesn't exist
                $sql = "CREATE TABLE quiz_options (
                    option_id INT AUTO_INCREMENT PRIMARY KEY,
                    question_id INT NOT NULL,
                    option_text TEXT NOT NULL,
                    is_correct BOOLEAN NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (question_id) REFERENCES quiz_questions(question_id) ON DELETE CASCADE
                )";
                $conn->exec($sql);
                $success = "The quiz_options table has been created successfully.";
            } else {
                $message = "The quiz_options table already exists.";
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Include header
$page_title = "Update Database for MCQ";
include('../includes/admin_header.php');
?>

<div class="content-wrapper">
    <div class="content-header">
        <h1>Update Database for Multiple-Choice Questions</h1>
        <p>Use this page to update your database structure to support multiple-choice questions.</p>
    </div>

    <?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <?php echo $success; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
    <div class="alert alert-info">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <div class="content-body">
        <div class="card">
            <div class="card-header">
                <h2>Multiple-Choice Questions Database Updates</h2>
            </div>
            <div class="card-body">
                <div class="update-item">
                    <h3>Create Quiz Options Table</h3>
                    <p>This update creates a new table to store multiple-choice options for quiz questions.</p>
                    
                    <?php
                    // Check if the table already exists
                    $table_exists = false;
                    try {
                        $stmt = $conn->prepare("SHOW TABLES LIKE 'quiz_options'");
                        $stmt->execute();
                        $table_exists = $stmt->fetch() !== false;
                    } catch(PDOException $e) {
                        $error = "Database error: " . $e->getMessage();
                    }
                    
                    if ($table_exists): 
                    ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> The quiz_options table already exists in the database.
                        </div>
                    <?php else: ?>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="create_quiz_options_table">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-database"></i> Create Quiz Options Table
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .update-item {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    .update-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    
    .alert i {
        margin-right: 10px;
    }
</style>

<?php include('../includes/admin_footer.php'); ?>
