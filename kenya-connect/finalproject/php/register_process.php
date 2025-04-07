<?php
// Enable comprehensive error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include database connection
require_once 'C:/Users/Purity/Desktop/php/htdocs/kenya-connect/finalproject/php/config.php';

// Debug log function with detailed error tracking
function detailed_log($message, $file = 'registration_debug.log') {
    $timestamp = date('Y-m-d H:i:s');
    $log_path = __DIR__ . '/' . $file;
    $full_message = "[$timestamp] $message\n";
    file_put_contents($log_path, $full_message, FILE_APPEND);
}

// Log the start of registration process
detailed_log("Registration process started");
detailed_log("Full POST data: " . print_r($_POST, true));

// Function to sanitize user input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Debug: Check database connection
        if (!$conn) {
            throw new Exception("Database connection is null");
        }

        // Get user type (vendor or consumer)
        $user_type = isset($_POST['user_type']) ? sanitize_input($_POST['user_type']) : '';
        detailed_log("User type: $user_type");

        // Validate user type
        if ($user_type !== 'vendor' && $user_type !== 'consumer') {
            throw new Exception("Invalid user type: $user_type");
        }

        // Common fields for both vendor and consumer
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm-password'];
        $phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
        $city = isset($_POST['city']) ? sanitize_input($_POST['city']) : '';
        $area = isset($_POST['area']) ? sanitize_input($_POST['area']) : '';

        // Validate required fields
        if (empty($email) || empty($password) || empty($confirm_password)) {
            throw new Exception("Required fields are missing");
        }

        // Check if passwords match
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match");
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        detailed_log("Password hashed successfully");

        // Determine the database table
        $table = ($user_type == 'vendor') ? 'vendors' : 'consumers';

        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM $table WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            throw new Exception("Email already exists");
        }

        // Handle file uploads for vendor license
        $business_license = '';
        if ($user_type == 'vendor' && isset($_FILES['license']) && $_FILES['license']['error'] == 0) {
            $upload_dir = '../uploads/licenses/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = basename($_FILES['license']['name']);
            $target_file = $upload_dir . time() . '_' . $file_name;
            
            if (move_uploaded_file($_FILES['license']['tmp_name'], $target_file)) {
                $business_license = $target_file;
                detailed_log("License uploaded: $business_license");
            }
        }

        // Prepare insertion based on user type
        if ($user_type == 'vendor') {
            // Get vendor-specific fields
            $business_name = sanitize_input($_POST['business-name']);
            $contact_person = sanitize_input($_POST['contact-person']);
            $business_type = isset($_POST['business-type']) ? sanitize_input($_POST['business-type']) : '';
            $business_description = isset($_POST['business-description']) ? sanitize_input($_POST['business-description']) : '';

            $stmt = $conn->prepare("
                INSERT INTO vendors (
                    business_name, contact_person, email, phone, 
                    password, city, area, business_type, 
                    business_description, license_file
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "ssssssssss", 
                $business_name, 
                $contact_person, 
                $email, 
                $phone, 
                $hashed_password, 
                $city, 
                $area, 
                $business_type, 
                $business_description, 
                $business_license
            );
        } else {
            // For consumers
            $name = sanitize_input($_POST['name']);
            $preferences = isset($_POST['preferences']) ? sanitize_input($_POST['preferences']) : '';

            $stmt = $conn->prepare("
                INSERT INTO consumers (
                    name, email, phone, 
                    password, city, area, 
                    preferences
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "sssssss", 
                $name, 
                $email, 
                $phone, 
                $hashed_password, 
                $city, 
                $area, 
                $preferences
            );
        }

        // Execute insertion
        if (!$stmt->execute()) {
            throw new Exception("Registration failed: " . $stmt->error);
        }

        // Log successful registration
        detailed_log("Successful registration for $email");

        // Set session variables
        $_SESSION['user_id'] = $conn->insert_id;
        $_SESSION['email'] = $email;
        $_SESSION['user_type'] = $user_type;
        
        if ($user_type == 'vendor') {
            $_SESSION['name'] = $business_name;
        } else {
            $_SESSION['name'] = $name;
        }

        // Redirect to appropriate dashboard
        $redirect_page = ($user_type == 'vendor') ? "../vendor/dashboard.php" : "../consumer/dashboard.php";
        header("Location: $redirect_page");
        exit();

    } catch (Exception $e) {
        // Log the full error details
        detailed_log("Registration Error: " . $e->getMessage());
        detailed_log("Full POST Data: " . print_r($_POST, true));
        
        // Set error message in session
        $_SESSION['error'] = $e->getMessage();

        // Redirect back to registration page
        $registration_page = ($user_type == 'vendor') ? "../vendor/register.php" : "../consumer/register.php";
        header("Location: $registration_page?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Not a POST request
    detailed_log("ERROR: Not a POST request");
    header("Location: ../index.html");
    exit();
}
?>