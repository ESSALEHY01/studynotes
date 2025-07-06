<?php
/**
 * Database Connection File
 * 
 * Establishes connection to the MySQL database
 */

// Database configuration
$db_host = 'localhost';      // Database host
$db_name = 'study_notes_db'; // Database name
$db_user = 'root';           // Database username (change as needed)
$db_pass = '';               // Database password (change as needed)

// Create connection
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Uncomment for debugging:
    // echo "Connected successfully";
} catch(PDOException $e) {
    // For production, you might want to log the error instead
    // error_log("Connection failed: " . $e->getMessage());
    
    // For development purposes, showing the error
    die("Connection failed: " . $e->getMessage());
}
?>