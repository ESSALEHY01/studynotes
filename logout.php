<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear the remember me cookie if it exists
if (isset($_COOKIE['user_remember'])) {
    // Set the cookie to expire in the past
    setcookie('user_remember', '', time() - 3600, '/', '', false, true);
}

// Redirect to login page
header('Location: login.php');
exit;
?>
