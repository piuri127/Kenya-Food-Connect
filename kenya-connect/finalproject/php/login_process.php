<?php
// Start session
session_start();
// Include database connection
require_once 'C:/Users/Purity/Desktop/php/htdocs/kenya-connect/finalproject/php/config.php';
// Sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get login credentials
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $user_type = sanitize_input($_POST['user_type']);
    try {
        // Determine which table to query based on user type
        $table = ($user_type == 'vendor') ? 'vendors' : 'consumers';
        
        // Prepare SQL statement with different column selection based on user type
        if ($user_type == 'vendor') {
            $stmt = $conn->prepare("
                SELECT id, business_name as name, email, password 
                FROM $table 
                WHERE email = ?
            ");
        } else {
            $stmt = $conn->prepare("
                SELECT id, name, email, password 
                FROM $table 
                WHERE email = ?
            ");
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            // User found
            $user = $result->fetch_assoc();
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $user_type;
                
                // Use the standardized 'name' field (which comes from either business_name or name)
                $_SESSION['name'] = $user['name'];
                
                // Redirect to appropriate dashboard
                if ($user_type == 'vendor') {
                    header("Location: ../vendor/dashboard.php");
                } else {
                    header("Location: ../consumer/dashboard.php");
                }
                exit();
            } else {
                // Password is incorrect
                $_SESSION['error'] = "Invalid email or password";
            }
        } else {
            // User not found
            $_SESSION['error'] = "Invalid email or password";
        }
        // Redirect back to login page if authentication failed
        if ($user_type == 'vendor') {
            header("Location: ../vendor/login.php");
        } else {
            header("Location: ../consumer/login.php");
        }
        exit();
    } catch (Exception $e) {
        // Log the error
        error_log("Login Error: " . $e->getMessage());
        
        // Set error message
        $_SESSION['error'] = "An unexpected error occurred";
        
        // Redirect back to login page
        if ($user_type == 'vendor') {
            header("Location: ../vendor/login.php");
        } else {
            header("Location: ../consumer/login.php");
        }
        exit();
    }
} else {
    // If not a POST request, redirect to home page
    header("Location: ../index.html");
    exit();
}
?>