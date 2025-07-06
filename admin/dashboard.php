
<?php
session_start();
require_once('../db_connection.php');

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Get users from database
$stmt = $conn->prepare("SELECT * FROM users ORDER BY username ASC");
$stmt->execute();
$users = $stmt->fetchAll();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new user
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        // Validate input
        if (!empty($username) && !empty($password)) {
            try {
                $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt->execute([$username, $password]); // In a real app, use password_hash()

                // Redirect to refresh page
                header('Location: dashboard.php?success=User added successfully');
                exit;
            } catch (PDOException $e) {
                $error = "Error adding user: " . $e->getMessage();
            }
        } else {
            $error = "Username and password are required";
        }
    }

    // Delete user
    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $user_id = $_POST['user_id'];

        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);

            // Redirect to refresh page
            header('Location: dashboard.php?success=User deleted successfully');
            exit;
        } catch (PDOException $e) {
            $error = "Error deleting user: " . $e->getMessage();
        }
    }

    // Edit user
    if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        $user_id = $_POST['user_id'];
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        // Validate input
        if (!empty($username)) {
            try {
                if (!empty($password)) {
                    // Update both username and password
                    $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE user_id = ?");
                    $stmt->execute([$username, $password, $user_id]); // In a real app, use password_hash()
                } else {
                    // Update username only
                    $stmt = $conn->prepare("UPDATE users SET username = ? WHERE user_id = ?");
                    $stmt->execute([$username, $user_id]);
                }

                // Redirect to refresh page
                header('Location: dashboard.php?success=User updated successfully');
                exit;
            } catch (PDOException $e) {
                $error = "Error updating user: " . $e->getMessage();
            }
        } else {
            $error = "Username is required";
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
    <title>StudyNotes - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #65350F;
            --secondary-color: #A67B5B;
            --highlight-color: #E8871E;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>StudyNotes</h1>
                <p class="tagline">Admin Dashboard</p>
            </div>
            <div class="admin-profile">
                <span class="admin-name"><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <main>
            <div class="dashboard-container">
                <div class="sidebar">
                    <div class="sidebar-menu">
                        <a href="dashboard.php" class="active"><i class="fas fa-users"></i> User Management</a>
                        <a href="ai_settings.php"><i class="fas fa-robot"></i> AI Settings</a>
                    </div>
                </div>

                <div class="content">
                    <div class="page-header">
                        <h2>User Management</h2>
                        <button id="addUserBtn" class="btn"><i class="fas fa-plus"></i> Add New User</button>
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

                    <div class="card">
                        <div class="card-body">
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th data-sortable="true">ID</th>
                                        <th data-sortable="true">Username</th>
                                        <th data-sortable="true">Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($users) > 0): ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                                <td class="actions">
                                                    <button class="btn-icon edit-btn" data-id="<?php echo $user['user_id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn-icon delete-btn" data-id="<?php echo $user['user_id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="no-data">No users found. Add your first user!</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 StudyNotes. All rights reserved.</p>
        </footer>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New User</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form action="dashboard.php" method="post">
                    <input type="hidden" name="action" value="add_user">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary cancel-btn">Cancel</button>
                        <button type="submit" class="btn">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content enhanced-modal">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> Edit User</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form action="dashboard.php" method="post" id="editUserForm">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="edit_user_id">

                    <div class="form-group">
                        <label for="edit_username">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <div class="input-wrapper">
                            <input type="text" id="edit_username" name="username" required>
                            <div class="input-validation" id="username-validation"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div class="input-wrapper">
                            <input type="password" id="edit_password" name="password" placeholder="Leave blank to keep current">
                            <span class="toggle-password" id="toggleEditPassword">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <p class="help-text">Leave blank to keep the current password</p>
                    </div>

                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary cancel-btn">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete User</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete user <strong id="delete_username"></strong>?</p>
                <p class="warning">This action cannot be undone. All user data, modules, and notes will be deleted.</p>
                <form action="dashboard.php" method="post">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/common.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>
