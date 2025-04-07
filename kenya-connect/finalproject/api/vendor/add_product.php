<?php
// Enable comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include database connection
require_once 'C:/Users/Purity/Desktop/php/htdocs/kenya-connect/finalproject/php/config.php';

// Function to handle file upload
function uploadProductImage($file) {
    // Validate file input
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No file uploaded or upload error occurred.");
    }

    // Define upload directory
    $upload_dir = '../../uploads/products/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . basename($file['name']);
    $target_path = $upload_dir . $filename;
    
    // Validate file type
    $image_file_type = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
    $valid_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($image_file_type, $valid_types)) {
        throw new Exception("Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.");
    }
    
    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("File is too large. Maximum size is 5MB.");
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Return relative path from project root
        return 'uploads/products/' . $filename;
    } else {
        throw new Exception("Failed to move uploaded file.");
    }
}

// Validate input function
function validateInput($input, $type = 'string', $required = true) {
    // Remove whitespace
    $input = trim($input);
    
    // Check if required
    if ($required && empty($input)) {
        throw new Exception("Required field cannot be empty.");
    }
    
    // Validate based on type
    switch ($type) {
        case 'string':
            // Sanitize string input
            return htmlspecialchars($input);
        
        case 'number':
            // Validate numeric input
            if (!is_numeric($input)) {
                throw new Exception("Invalid numeric input.");
            }
            return floatval($input);
        
        case 'date':
            // Validate date format
            if (!strtotime($input)) {
                throw new Exception("Invalid date format.");
            }
            return $input;
        
        default:
            return $input;
    }
}

// Main product addition process
try {
    // Check if user is authenticated as a vendor
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'vendor') {
        throw new Exception("Unauthorized access");
    }

    // Log incoming data for debugging
    error_log("Incoming POST data: " . print_r($_POST, true));
    error_log("Incoming FILES data: " . print_r($_FILES, true));

    // Validate vendor ID
    $vendor_id = validateInput($_POST['vendor_id'], 'number');
    
    // Validate product details
    $product_name = validateInput($_POST['product_name']);
    $category = validateInput($_POST['category']);
    $quantity = validateInput($_POST['quantity'], 'number');
    $unit = validateInput($_POST['unit']);
    $original_price = validateInput($_POST['original_price'], 'number');
    $discounted_price = validateInput($_POST['discounted_price'], 'number');
    $expiry_date = validateInput($_POST['expiry_date'], 'date');
    $availability = validateInput($_POST['availability']);
    $description = validateInput($_POST['description']);
    $storage_instructions = isset($_POST['storage_instructions']) ? validateInput($_POST['storage_instructions']) : '';

    // Validate pricing
    if ($discounted_price >= $original_price) {
        throw new Exception("Discounted price must be less than the original price.");
    }

    // Handle image upload
    $image_path = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $image_path = uploadProductImage($_FILES['product_image']);
    }

    // Prepare SQL statement
    $stmt = $conn->prepare("
        INSERT INTO products (
            vendor_id, name, category, quantity, unit, 
            original_price, price, expiration_date, 
            availability, description, storage_instructions, 
            image, status, listing_type, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, 
            ?, ?, ?, 
            ?, 'available', 'sell', NOW()
        )
    ");

    $stmt->bind_param(
        "isssdddssssss", 
        $vendor_id, $product_name, $category, $quantity, $unit,
        $original_price, $discounted_price, $expiry_date,
        $availability, $description, $storage_instructions,
        $image_path
    );

    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Failed to add product: " . $stmt->error);
    }

    // Set success session message
    $_SESSION['message'] = "Product added successfully!";
    $_SESSION['message_type'] = "success";

    // Redirect to products page
    header("Location: ../../vendor/products.php");
    exit();

} catch (Exception $e) {
    // Log the error
    error_log("Product Addition Error: " . $e->getMessage());

    // Set error session message
    $_SESSION['message'] = $e->getMessage();
    $_SESSION['message_type'] = "error";

    // Redirect back to add product page
    header("Location: ../../vendor/add_product.php");
    exit();
}
?>