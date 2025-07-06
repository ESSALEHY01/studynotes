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

// Get all modules for this user
$stmt = $conn->prepare("SELECT * FROM modules WHERE user_id = ? ORDER BY module_name ASC");
$stmt->execute([$user_id]);
$modules = $stmt->fetchAll();

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
                header('Location: modules.php?success=Module added successfully');
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
            header('Location: modules.php?success=Module deleted successfully');
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
                header('Location: modules.php?success=Module updated successfully');
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
    <title>StudyNotes - Modules</title>
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
                <p class="tagline">Your Study Modules</p>
            </div>
            <div class="user-profile">
                <span class="user-name"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <main>
            <div class="dashboard-container">
                <?php
                $active_page = 'modules';
                include('includes/sidebar.php');
                ?>

                <div class="content">
                    <div class="page-header">
                        <h2>Your Modules</h2>
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



                    <!-- Modules List -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (count($modules) > 0): ?>
                                <div class="grid-container" id="modulesGrid">
                                    <?php foreach ($modules as $module): ?>
                                        <div class="content-card">
                                            <div class="content-card-header">
                                                <h4><?php echo htmlspecialchars($module['module_name']); ?></h4>
                                                <span class="content-card-date"><i class="fas fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($module['created_at'])); ?></span>
                                            </div>
                                            <div class="content-card-body">
                                                <?php echo !empty($module['module_description']) ? htmlspecialchars($module['module_description']) : 'No description provided.'; ?>

                                                <?php
                                                // Get note count for this module
                                                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notes WHERE module_id = ? AND user_id = ?");
                                                $stmt->execute([$module['module_id'], $user_id]);
                                                $note_count = $stmt->fetch()['count'];
                                                ?>

                                                <div class="module-stats">
                                                    <span class="note-count"><i class="fas fa-sticky-note"></i> <?php echo $note_count; ?> Notes</span>
                                                </div>
                                            </div>
                                            <div class="content-card-footer">
                                                <a href="notes.php?module=<?php echo $module['module_id']; ?>" class="btn-sm"><i class="fas fa-sticky-note"></i> View Notes</a>
                                                <div class="content-card-actions">
                                                    <button class="btn-sm edit-module-btn" data-id="<?php echo $module['module_id']; ?>" data-name="<?php echo htmlspecialchars($module['module_name']); ?>" data-description="<?php echo htmlspecialchars($module['module_description']); ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn-sm btn-danger delete-module-btn" data-id="<?php echo $module['module_id']; ?>" data-name="<?php echo htmlspecialchars($module['module_name']); ?>">
                                                        <i class="fas fa-trash"></i> Delete
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
                <form action="modules.php" method="post">
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
                        <button type="button" class="btn btn-secondary btn-slim cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-slim">Create Module</button>
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
                <form action="modules.php" method="post">
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
                        <button type="button" class="btn btn-secondary btn-slim cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-slim">Update Module</button>
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
                <form action="modules.php" method="post">
                    <input type="hidden" name="action" value="delete_module">
                    <input type="hidden" name="module_id" id="delete_module_id">
                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary btn-slim cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-slim">Delete Module</button>
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
