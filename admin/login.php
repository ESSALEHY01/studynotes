<?php
session_start();
require_once('../db_connection.php');

$error_message = '';

// Check if admin is already logged in via cookie
if (!isset($_SESSION['is_admin']) && isset($_COOKIE['admin_remember'])) {
    $cookie_data = json_decode($_COOKIE['admin_remember'], true);

    if (isset($cookie_data['admin_id']) && isset($cookie_data['token'])) {
        // Verify the remember token
        $admin_id = $cookie_data['admin_id'];
        $token = $cookie_data['token'];

        $stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch();

        // In a real app, you would store and verify the token in the database
        // For this demo, we'll use a simple hash of the admin's username and password
        $expected_token = hash('sha256', $admin['username'] . $admin['password'] . 'studynotes_salt');

        if ($admin && $token === $expected_token) {
            // Set session variables
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['is_admin'] = true;

            // Redirect to admin dashboard
            header('Location: dashboard.php');
            exit;
        }
    }
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get username and password from form
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $remember_me = isset($_POST['remember_me']) ? true : false;

    // Validate input
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password';
    } else {
        // Query to check if admin exists
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        // If admin exists and password matches
        if ($admin && $password === $admin['password']) { // In a real app, use password_verify() for hashed passwords
            // Set session variables
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['is_admin'] = true;

            // Set remember me cookie if requested
            if ($remember_me) {
                // Create a token (in a real app, store this in the database)
                $token = hash('sha256', $admin['username'] . $admin['password'] . 'studynotes_salt');

                // Store admin ID and token in a cookie that expires in 30 days
                $cookie_data = json_encode([
                    'admin_id' => $admin['admin_id'],
                    'token' => $token
                ]);

                setcookie('admin_remember', $cookie_data, time() + (86400 * 30), '/', '', false, true);
            }

            // Redirect to admin dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $error_message = 'Invalid admin credentials';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyNotes - Admin Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>StudyNotes</h1>
                <p class="tagline">Admin Portal</p>
            </div>
        </header>

        <main>
            <div class="form-container">
                <div class="form-card">
                    <h2><i class="fas fa-user-shield"></i> Admin Login</h2>

                    <?php if (!empty($error_message)): ?>
                        <div class="error-message">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="post">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" placeholder="Enter admin username" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter admin password" required>
                        </div>

                        <div class="form-group remember-me">
                            <input type="checkbox" name="remember_me" id="remember_me">
                            <label for="remember_me" class="remember-label">Remember Me</label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary btn-full-width">Admin Login</button>
                        </div>
                    </form>

                    <div class="back-link">
                        <a href="../index.php" class="btn-sm"><i class="fas fa-arrow-left"></i> Back to Home</a>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 StudyNotes. All rights reserved.</p>
        </footer>
    </div>

    <script src="../script.js"></script>
</body>
</html>