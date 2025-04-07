<?php
// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Enable detailed error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection details
$host = '127.0.0.1';
$username = 'root';
$password = '4321@Piuri'; 
$database = 'kenya_connect_new';
$port = 3306;

try {
    // Enable MySQLi error reporting
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Create a connection
    $conn = new mysqli($host, $username, $password, $database, $port);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset to utf8mb4 to support special characters
    $conn->set_charset("utf8mb4");

    // Optional: Log successful connection
    error_log("Database connection successful", 3, "database_connection.log");

} catch (Exception $e) {
    // Log the error in a file
    error_log("Database Connection Error: " . $e->getMessage(), 3, "error_log.txt");

    // Detailed error for debugging
    die("Database Connection Error: " . $e->getMessage());
}
?>