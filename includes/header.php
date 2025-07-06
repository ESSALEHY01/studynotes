<?php
/**
 * Common header template for StudyNotes application
 * 
 * @param string $page_title - The title of the page
 * @param string $tagline - The tagline to display under the logo
 * @param array $additional_css - Additional CSS files to include
 */

// Default values
$page_title = $page_title ?? 'StudyNotes';
$tagline = $tagline ?? 'Your Study Companion';
$additional_css = $additional_css ?? [];

// Ensure user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get user information
$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="css/form-elements.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <?php foreach ($additional_css as $css_file): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($css_file); ?>">
    <?php endforeach; ?>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>StudyNotes</h1>
                <p class="tagline"><?php echo htmlspecialchars($tagline); ?></p>
            </div>
            <div class="user-profile">
                <span class="user-name"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>
