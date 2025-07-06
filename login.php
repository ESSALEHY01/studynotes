<?php
session_start();
require_once('db_connection.php');
require_once('includes/functions.php');



$error_message = '';

// Check if user is already logged in via cookie
if (!isset($_SESSION['is_logged_in']) && isset($_COOKIE['user_remember'])) {
    $cookie_data = json_decode($_COOKIE['user_remember'], true);

    if (isset($cookie_data['user_id']) && isset($cookie_data['token'])) {
        // Verify the remember token
        $user_id = $cookie_data['user_id'];
        $token = $cookie_data['token'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        // In a real app, you would store and verify the token in the database
        // For this demo, we'll use a simple hash of the user's username and password
        $expected_token = hash('sha256', $user['username'] . $user['password'] . 'studynotes_user_salt');

        if ($user && $token === $expected_token) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_logged_in'] = true;

            // Redirect to dashboard
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
        // Query to check if user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // If user exists and password matches
        if ($user && $password === $user['password']) { // In a real app, use password_verify() for hashed passwords
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_logged_in'] = true;

            // Set remember me cookie if requested
            if ($remember_me) {
                // Create a token (in a real app, store this in the database)
                $token = hash('sha256', $user['username'] . $user['password'] . 'studynotes_user_salt');

                // Store user ID and token in a cookie that expires in 30 days
                $cookie_data = json_encode([
                    'user_id' => $user['user_id'],
                    'token' => $token
                ]);

                setcookie('user_remember', $cookie_data, time() + (86400 * 30), '/', '', false, true);
            }

            // Redirect to dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $error_message = 'Invalid username or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyNotes - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>StudyNotes</h1>
                <p class="tagline">Organize your learning journey</p>
            </div>
        </header>

        <main>
            <div class="form-container">
                <div class="form-card">
                    <h2><i class="fas fa-user-graduate"></i> Student Login</h2>

                    <?php if (!empty($error_message)): ?>
                        <div class="error-message">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="post">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" placeholder="Enter your username" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        </div>

                        <div class="form-group remember-me">
                            <input type="checkbox" name="remember_me" id="remember_me">
                            <label for="remember_me" class="remember-label">Remember Me</label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary btn-full-width">Login</button>
                        </div>
                    </form>

                    <div class="back-link">
                        <a href="index.php" class="btn-sm"><i class="fas fa-arrow-left"></i> Back to Home</a>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 StudyNotes. All rights reserved.</p>
        </footer>
    </div>

    <script src="script.js"></script>
</body>
</html>