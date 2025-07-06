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

// Get summaries for this user
if ($module_filter) {
    $stmt = $conn->prepare("
        SELECT s.*, n.title as note_title, n.note_id, m.module_name, m.module_id
        FROM ai_summaries s
        JOIN notes n ON s.note_id = n.note_id
        JOIN modules m ON n.module_id = m.module_id
        WHERE n.user_id = ? AND m.module_id = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$user_id, $module_filter]);
} else {
    $stmt = $conn->prepare("
        SELECT s.*, n.title as note_title, n.note_id, m.module_name, m.module_id
        FROM ai_summaries s
        JOIN notes n ON s.note_id = n.note_id
        JOIN modules m ON n.module_id = m.module_id
        WHERE n.user_id = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$user_id]);
}
$summaries = $stmt->fetchAll();

// Get notes for this user (for generating new summaries)
$stmt = $conn->prepare("
    SELECT n.*, m.module_name
    FROM notes n
    JOIN modules m ON n.module_id = m.module_id
    WHERE n.user_id = ?
    ORDER BY n.title ASC
");
$stmt->execute([$user_id]);
$notes = $stmt->fetchAll();

// Handle summary actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete summary
    if (isset($_POST['action']) && $_POST['action'] === 'delete_summary') {
        $summary_id = intval($_POST['summary_id']);

        try {
            $stmt = $conn->prepare("DELETE FROM ai_summaries WHERE summary_id = ? AND note_id IN (SELECT note_id FROM notes WHERE user_id = ?)");
            $stmt->execute([$summary_id, $user_id]);

            // Redirect to refresh page
            header('Location: summaries.php?success=Summary deleted successfully');
            exit;
        } catch (PDOException $e) {
            $error = "Error deleting summary: " . $e->getMessage();
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
    <title>StudyNotes - Summaries</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="css/form-elements.css">
    <link rel="stylesheet" href="css/summary.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>StudyNotes</h1>
                <p class="tagline">AI-Powered Summaries</p>
            </div>
            <div class="user-profile">
                <span class="user-name"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <main>
            <div class="dashboard-container">
                <?php
                $active_page = 'summaries';
                include('includes/sidebar.php');
                ?>

                <div class="content">
                    <div class="page-header">
                        <h2>
                            Your Summaries
                            <?php if ($module_filter && !empty($filter_module_name)): ?>
                                <span class="filter-badge">
                                    <?php echo htmlspecialchars($filter_module_name); ?>
                                    <a href="summaries.php"><i class="fas fa-times"></i></a>
                                </span>
                            <?php endif; ?>
                        </h2>
                        <button id="generateSummaryBtn" class="btn btn-slim btn-action"><i class="fas fa-plus"></i> Generate New Summary</button>
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

                    <!-- Summaries Grid -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (count($summaries) > 0): ?>
                                <div class="grid-container">
                                    <?php foreach ($summaries as $summary): ?>
                                        <div class="content-card summary-card">
                                            <div class="content-card-header">
                                                <h4><?php echo htmlspecialchars($summary['note_title']); ?></h4>
                                                <span class="module-badge"><?php echo htmlspecialchars($summary['module_name']); ?></span>
                                            </div>
                                            <div class="content-card-body summary-preview">
                                                <?php echo substr(htmlspecialchars($summary['summary_content']), 0, 150) . '...'; ?>
                                            </div>
                                            <div class="content-card-footer">
                                                <span class="content-card-date"><i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($summary['created_at'])); ?></span>
                                                <div class="content-card-actions">
                                                    <a href="view_summary.php?id=<?php echo $summary['summary_id']; ?>" class="btn-sm"><i class="fas fa-eye"></i> View</a>
                                                    <button class="btn-sm btn-danger delete-summary-btn" data-id="<?php echo $summary['summary_id']; ?>" data-title="<?php echo htmlspecialchars($summary['note_title']); ?>">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-file-alt"></i>
                                    <h3>No Summaries Yet</h3>
                                    <p>Generate your first summary by clicking the "Generate New Summary" button above.</p>
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

    <!-- Generate Summary Modal -->
    <div id="generateSummaryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Generate New Summary</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="generateSummaryForm">
                    <div class="form-group">
                        <label for="note_id"><i class="fas fa-sticky-note"></i> Select Note</label>
                        <div class="custom-select-container">
                            <select id="note_id" name="note_id" class="custom-select" required>
                                <option value="" disabled selected>Choose a note</option>
                                <?php foreach ($notes as $note): ?>
                                    <option value="<?php echo $note['note_id']; ?>">
                                        <?php echo htmlspecialchars($note['title']); ?> (<?php echo htmlspecialchars($note['module_name']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="max_length"><i class="fas fa-text-width"></i> Maximum Length (characters)</label>
                        <input type="number" id="max_length" name="max_length" min="100" max="2000" value="500" required>
                    </div>

                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary btn-slim cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-slim">
                            <i class="fas fa-magic"></i> Generate Summary
                        </button>
                    </div>
                </form>
                <div id="generationProgress" class="generation-progress" style="display: none;">
                    <div class="spinner"></div>
                    <p>Generating summary with AI...</p>
                    <p class="small">This may take a few moments.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Summary Modal -->
    <div id="deleteSummaryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Summary</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the summary for <strong id="delete_summary_title"></strong>?</p>
                <p class="warning">This action cannot be undone.</p>
                <form action="summaries.php" method="post">
                    <input type="hidden" name="action" value="delete_summary">
                    <input type="hidden" name="summary_id" id="delete_summary_id">
                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary btn-slim cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-slim">Delete Summary</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/common.js"></script>
    <script src="js/shared-modal.js"></script>
    <script src="js/summary.js"></script>
</body>
</html>
