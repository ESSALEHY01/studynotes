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
    if ($_POST['action'] === 'add_note_id_column') {
        try {
            // Check if the column already exists
            $stmt = $conn->prepare("SHOW COLUMNS FROM quizzes LIKE 'note_id'");
            $stmt->execute();
            $column_exists = $stmt->fetch();

            if (!$column_exists) {
                // Add the note_id column if it doesn't exist
                $sql = "ALTER TABLE quizzes ADD COLUMN note_id INT DEFAULT NULL";
                $conn->exec($sql);
                $success = "The note_id column has been added to the quizzes table successfully.";
            } else {
                $message = "The note_id column already exists in the quizzes table.";
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Include header
$page_title = "Update Database";
include('../includes/admin_header.php');
?>

<div class="content-wrapper">
    <div class="content-header">
        <h1>Update Database Structure</h1>
        <p>Use this page to update your database structure for new features.</p>
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
                <h2>Database Updates</h2>
            </div>
            <div class="card-body">
                <div class="update-item">
                    <h3>Add note_id Column to Quizzes Table</h3>
                    <p>This update adds a note_id column to the quizzes table, which is required for the relationship between quizzes and notes.</p>

                    <?php
                    // Check if the column already exists
                    $column_exists = false;
                    try {
                        $stmt = $conn->prepare("SHOW COLUMNS FROM quizzes LIKE 'note_id'");
                        $stmt->execute();
                        $column_exists = $stmt->fetch() !== false;
                    } catch(PDOException $e) {
                        $error = "Database error: " . $e->getMessage();
                    }

                    if ($column_exists):
                    ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> The note_id column already exists in the quizzes table.
                        </div>
                    <?php else: ?>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="add_note_id_column">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-database"></i> Add note_id Column
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="update-item">
                    <h3>Multiple-Choice Questions Update</h3>
                    <p>Update the database to support multiple-choice questions with answer options.</p>

                    <a href="update_mcq_schema.php" class="btn btn-primary">
                        <i class="fas fa-tasks"></i> Go to MCQ Database Update
                    </a>
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
