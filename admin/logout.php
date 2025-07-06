<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear the remember me cookie if it exists
if (isset($_COOKIE['admin_remember'])) {
    // Set the cookie to expire in the past
    setcookie('admin_remember', '', time() - 3600, '/', '', false, true);
}

// Redirect to login page
header('Location: login.php');
exit;
?>