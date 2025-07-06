<?php
/**
 * Common header template for StudyNotes Admin
 * 
 * @param string $page_title - The title of the page
 * @param string $active_page - The currently active page (dashboard, ai_settings)
 */

// Default values
$page_title = $page_title ?? 'StudyNotes Admin';
$active_page = $active_page ?? '';

// Ensure user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
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
                        <a href="dashboard.php" <?php echo ($active_page === 'dashboard') ? 'class="active"' : ''; ?>><i class="fas fa-users"></i> User Management</a>
                        <a href="ai_settings.php" <?php echo ($active_page === 'ai_settings') ? 'class="active"' : ''; ?>><i class="fas fa-robot"></i> AI Settings</a>
                    </div>
                </div>

                <div class="content">
