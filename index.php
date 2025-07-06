<?php
session_start();
require_once('db_connection.php');
require_once('includes/functions.php');

// Test database connection
try {
    $test = $conn->query("SELECT 1");
    echo "<!-- Database connection successful -->";
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if site is in maintenance mode and user is not an admin
if (function_exists('is_maintenance_mode') && function_exists('is_admin')) {
    if (is_maintenance_mode($conn) && !is_admin()) {
        // Only redirect if maintenance.php exists
        if (file_exists('maintenance.php')) {
            header('Location: maintenance.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyNotes - Welcome</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/common.css">
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
            <section class="welcome-section">
                <h2>Welcome to StudyNotes</h2>
                <p>Your personal note-taking and study organization platform. Keep all your academic notes organized by module and access them anytime, anywhere.</p>

                <div class="features">
                    <div class="feature-card">
                        <i class="fas fa-book-open"></i>
                        <h3>Organize Notes</h3>
                        <p>Create and manage notes by module</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-edit"></i>
                        <h3>Edit & Update</h3>
                        <p>Easily edit and update your notes</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-graduation-cap"></i>
                        <h3>Boost Learning</h3>
                        <p>Improve your study efficiency</p>
                    </div>
                </div>
            </section>

            <section class="access-section">
                <div class="access-card">
                    <h3>Student Access</h3>
                    <p>Login to manage your notes and modules</p>
                    <a href="login.php" class="btn">Login</a>
                </div>
                <div class="access-card admin">
                    <h3>Admin Access</h3>
                    <p>Administrative tools and user management</p>
                    <a href="admin/login.php" class="btn admin-btn">Admin Login</a>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; 2025 StudyNotes. All rights reserved.</p>
        </footer>
    </div>

    <script src="script.js"></script>
</body>
</html>