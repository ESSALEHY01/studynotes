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

// Get modules for this user
$stmt = $conn->prepare("SELECT * FROM modules WHERE user_id = ? ORDER BY module_name ASC");
$stmt->execute([$user_id]);
$modules = $stmt->fetchAll();

// Get recent notes for this user
$stmt = $conn->prepare("
    SELECT n.*, m.module_name
    FROM notes n
    JOIN modules m ON n.module_id = m.module_id
    WHERE n.user_id = ?
    ORDER BY n.updated_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_notes = $stmt->fetchAll();

// Count total notes
$stmt = $conn->prepare("SELECT COUNT(*) as note_count FROM notes WHERE user_id = ?");
$stmt->execute([$user_id]);
$note_count = $stmt->fetch()['note_count'];

// Count total modules
$stmt = $conn->prepare("SELECT COUNT(*) as module_count FROM modules WHERE user_id = ?");
$stmt->execute([$user_id]);
$module_count = $stmt->fetch()['module_count'];

// Count total quizzes
$stmt = $conn->prepare("SELECT COUNT(*) as quiz_count FROM quizzes WHERE user_id = ?");
$stmt->execute([$user_id]);
$quiz_count = $stmt->fetch()['quiz_count'];

// Count total summaries
$stmt = $conn->prepare("
    SELECT COUNT(*) as summary_count
    FROM ai_summaries s
    JOIN notes n ON s.note_id = n.note_id
    WHERE n.user_id = ?
");
$stmt->execute([$user_id]);
$summary_count = $stmt->fetch()['summary_count'];

// Handle module actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new module
    if (isset($_POST['action']) && $_POST['action'] === 'add_module') {
        $module_name = trim($_POST['module_name']);
        $module_description = trim($_POST['module_description']);

        // Validate input
        if (!empty($module_name)) {
            try {
                $stmt = $conn->prepare("INSERT INTO modules (module_name, module_description, user_id) VALUES (?, ?, ?)");
                $stmt->execute([$module_name, $module_description, $user_id]);

                // Redirect to refresh page
                header('Location: dashboard.php?success=Module added successfully');
                exit;
            } catch (PDOException $e) {
                $error = "Error adding module: " . $e->getMessage();
            }
        } else {
            $error = "Module name is required";
        }
    }

    // Delete module
    if (isset($_POST['action']) && $_POST['action'] === 'delete_module') {
        $module_id = $_POST['module_id'];

        try {
            $stmt = $conn->prepare("DELETE FROM modules WHERE module_id = ? AND user_id = ?");
            $stmt->execute([$module_id, $user_id]);

            // Redirect to refresh page
            header('Location: dashboard.php?success=Module deleted successfully');
            exit;
        } catch (PDOException $e) {
            $error = "Error deleting module: " . $e->getMessage();
        }
    }

    // Edit module
    if (isset($_POST['action']) && $_POST['action'] === 'edit_module') {
        $module_id = $_POST['module_id'];
        $module_name = trim($_POST['module_name']);
        $module_description = trim($_POST['module_description']);

        // Validate input
        if (!empty($module_name)) {
            try {
                $stmt = $conn->prepare("UPDATE modules SET module_name = ?, module_description = ? WHERE module_id = ? AND user_id = ?");
                $stmt->execute([$module_name, $module_description, $module_id, $user_id]);

                // Redirect to refresh page
                header('Location: dashboard.php?success=Module updated successfully');
                exit;
            } catch (PDOException $e) {
                $error = "Error updating module: " . $e->getMessage();
            }
        } else {
            $error = "Module name is required";
        }
    }
}

// Get success or error messages
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($error) ? $error : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyNotes - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>StudyNotes</h1>
                <p class="tagline">Student Dashboard</p>
            </div>
            <div class="user-profile">
                <span class="user-name"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <main>
            <div class="dashboard-container">
                <?php
                $active_page = 'dashboard';
                include('includes/sidebar.php');
                ?>

                <div class="content">
                    <div class="page-header">
                        <h2>Dashboard</h2>
                        <button id="addModuleBtn" class="btn btn-slim btn-action"><i class="fas fa-plus"></i> Add New Module</button>
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

                    <!-- Dashboard Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-sticky-note"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $note_count; ?></h3>
                                <p>Total Notes</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $module_count; ?></h3>
                                <p>Total Modules</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $quiz_count; ?></h3>
                                <p>AI Quizzes</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $summary_count; ?></h3>
                                <p>AI Summaries</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Notes -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Recent Notes</h3>
                            <a href="notes.php" class="view-all">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (count($recent_notes) > 0): ?>
                                <div class="notes-grid">
                                    <?php foreach ($recent_notes as $note): ?>
                                        <div class="note-card">
                                            <div class="note-header">
                                                <h4><?php echo htmlspecialchars($note['title']); ?></h4>
                                                <span class="module-badge"><?php echo htmlspecialchars($note['module_name']); ?></span>
                                            </div>
                                            <div class="note-preview">
                                                <?php echo substr(strip_tags($note['content']), 0, 100) . '...'; ?>
                                            </div>
                                            <div class="note-footer">
                                                <span class="note-date"><i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($note['updated_at'])); ?></span>
                                                <a href="view_note.php?id=<?php echo $note['note_id']; ?>" class="btn-sm">View</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-data">
                                    <p>You haven't created any notes yet. <a href="notes.php?action=new">Create your first note</a>.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Modules -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Your Modules</h3>
                            <a href="modules.php" class="view-all">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (count($modules) > 0): ?>
                                <div class="modules-grid">
                                    <?php foreach ($modules as $module): ?>
                                        <div class="module-card">
                                            <div class="module-header">
                                                <h4><?php echo htmlspecialchars($module['module_name']); ?></h4>
                                            </div>
                                            <div class="module-description">
                                                <?php echo !empty($module['module_description']) ? htmlspecialchars($module['module_description']) : 'No description provided.'; ?>
                                            </div>
                                            <div class="module-footer">
                                                <a href="notes.php?module=<?php echo $module['module_id']; ?>" class="btn-sm">View Notes</a>
                                                <div class="module-actions">
                                                    <button class="btn-icon edit-module-btn" data-id="<?php echo $module['module_id']; ?>" data-name="<?php echo htmlspecialchars($module['module_name']); ?>" data-description="<?php echo htmlspecialchars($module['module_description']); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn-icon delete-module-btn" data-id="<?php echo $module['module_id']; ?>" data-name="<?php echo htmlspecialchars($module['module_name']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-data">
                                    <p>You haven't created any modules yet. Click the "Add New Module" button to get started.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 StudyNotes. All rights reserved.</p>
        </footer>
    </div>

    <!-- Add Module Modal -->
    <div id="addModuleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Module</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form action="dashboard.php" method="post">
                    <input type="hidden" name="action" value="add_module">
                    <div class="form-group">
                        <label for="module_name">Module Name</label>
                        <input type="text" id="module_name" name="module_name" required>
                    </div>
                    <div class="form-group">
                        <label for="module_description">Description (Optional)</label>
                        <textarea id="module_description" name="module_description" rows="4"></textarea>
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary cancel-btn">Cancel</button>
                        <button type="submit" class="btn">Create Module</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Module Modal -->
    <div id="editModuleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Module</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form action="dashboard.php" method="post">
                    <input type="hidden" name="action" value="edit_module">
                    <input type="hidden" name="module_id" id="edit_module_id">
                    <div class="form-group">
                        <label for="edit_module_name">Module Name</label>
                        <input type="text" id="edit_module_name" name="module_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_module_description">Description (Optional)</label>
                        <textarea id="edit_module_description" name="module_description" rows="4"></textarea>
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary cancel-btn">Cancel</button>
                        <button type="submit" class="btn">Update Module</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Module Modal -->
    <div id="deleteModuleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Module</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete module <strong id="delete_module_name"></strong>?</p>
                <p class="warning">This action cannot be undone. All notes in this module will be deleted.</p>
                <form action="dashboard.php" method="post">
                    <input type="hidden" name="action" value="delete_module">
                    <input type="hidden" name="module_id" id="delete_module_id">
                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Module</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/common.js"></script>
    <script src="js/shared-modal.js"></script>
    <script src="js/user.js"></script>
</body>
</html>
