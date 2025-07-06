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

// Get user stats
$stmt = $conn->prepare("SELECT COUNT(*) as note_count FROM notes WHERE user_id = ?");
$stmt->execute([$user_id]);
$note_count = $stmt->fetch()['note_count'];

$stmt = $conn->prepare("SELECT COUNT(*) as module_count FROM modules WHERE user_id = ?");
$stmt->execute([$user_id]);
$module_count = $stmt->fetch()['module_count'];

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match";
    } else {
        // Check current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && $current_password === $user['password']) { // In a real app, use password_verify()
            // Update password
            try {
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$new_password, $user_id]); // In a real app, use password_hash()

                $success = "Password updated successfully";
            } catch (PDOException $e) {
                $error = "Error updating password: " . $e->getMessage();
            }
        } else {
            $error = "Current password is incorrect";
        }
    }
}

// Get success or error messages
$success = isset($success) ? $success : '';
$error = isset($error) ? $error : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyNotes - Profile</title>
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
                <p class="tagline">Your Profile</p>
            </div>
            <div class="user-profile">
                <span class="user-name"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <main>
            <div class="dashboard-container">
                <?php
                $active_page = 'profile';
                include('includes/sidebar.php');
                ?>

                <div class="content">
                    <div class="page-header">
                        <h2>Your Profile</h2>
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

                    <div class="profile-grid">
                        <div class="profile-card">
                            <div class="profile-avatar">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="profile-info">
                                <h3><?php echo htmlspecialchars($username); ?></h3>
                                <p>Student</p>
                            </div>
                            <div class="profile-stats">
                                <div class="profile-stat-item">
                                    <h4><?php echo $module_count; ?></h4>
                                    <p>Modules</p>
                                </div>
                                <div class="profile-stat-item">
                                    <h4><?php echo $note_count; ?></h4>
                                    <p>Notes</p>
                                </div>
                            </div>
                        </div>

                        <div class="profile-content">
                            <div class="form-card">
                                <h3>Change Password</h3>
                                <form action="profile.php" method="post">
                                    <input type="hidden" name="action" value="change_password">
                                    <div class="form-group">
                                        <label for="current_password">Current Password</label>
                                        <input type="password" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <input type="password" id="new_password" name="new_password" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" class="btn-primary">Update Password</button>
                                    </div>
                                </form>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 StudyNotes. All rights reserved.</p>
        </footer>
    </div>

    <script src="js/user.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password validation
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordForm = document.querySelector('.password-form form');

            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    if (newPasswordInput.value !== confirmPasswordInput.value) {
                        e.preventDefault();
                        alert('New passwords do not match!');
                    }
                });
            }

            // Password strength indicator
            if (newPasswordInput) {
                newPasswordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;

                    // Add strength for length
                    if (password.length >= 8) strength += 1;

                    // Add strength for containing numbers
                    if (/\d/.test(password)) strength += 1;

                    // Add strength for containing special characters
                    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 1;

                    // Add strength for containing uppercase and lowercase
                    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 1;

                    // Update UI based on strength
                    let strengthText = '';
                    let strengthColor = '';

                    switch (strength) {
                        case 0:
                        case 1:
                            strengthText = 'Weak';
                            strengthColor = '#dc3545';
                            break;
                        case 2:
                            strengthText = 'Moderate';
                            strengthColor = '#ffc107';
                            break;
                        case 3:
                            strengthText = 'Strong';
                            strengthColor = '#28a745';
                            break;
                        case 4:
                            strengthText = 'Very Strong';
                            strengthColor = '#198754';
                            break;
                    }

                    // Create or update strength indicator
                    let indicator = document.getElementById('password-strength');
                    if (!indicator) {
                        indicator = document.createElement('div');
                        indicator.id = 'password-strength';
                        indicator.style.marginTop = '5px';
                        indicator.style.fontSize = '14px';
                        this.parentNode.appendChild(indicator);
                    }

                    indicator.textContent = `Password Strength: ${strengthText}`;
                    indicator.style.color = strengthColor;
                });
            }
        });
    </script>
</body>
</html>
