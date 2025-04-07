<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include database connection
require_once 'C:/Users/Purity/Desktop/php/htdocs/kenya-connect/finalproject/php/config.php';

// Check if user is authenticated as a vendor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'vendor') {
    $_SESSION['message'] = "Unauthorized access";
    $_SESSION['message_type'] = "error";
    header("Location: ../../vendor/add_product.php");
    exit();
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to handle file upload
function uploadProductImage($file) {
    $upload_dir = '../../uploads/products/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filename = uniqid() . '_' . basename($file['name']);
    $target_path = $upload_dir . $filename;
    
    // Validate file type
    $image_file_type = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
    $valid_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($image_file_type, $valid_types)) {
        throw new Exception("Invalid file type");
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return 'uploads/products/' . $filename;
    } else {
        throw new Exception("File upload failed");
    }
}

try {
    // Validate and sanitize inputs
    $vendor_id = intval($_SESSION['user_id']);
    $product_name = sanitize_input($_POST['product_name']);
    $category = sanitize_input($_POST['category']);
    $quantity = floatval($_POST['quantity']);
    $unit = sanitize_input($_POST['unit']);
    $original_price = floatval($_POST['original_price']);
    $discounted_price = floatval($_POST['discounted_price']);
    $expiry_date = $_POST['expiry_date'];
    $availability = sanitize_input($_POST['availability']);
    $description = sanitize_input($_POST['description']);
    $storage_instructions = isset($_POST['storage_instructions']) ? sanitize_input($_POST['storage_instructions']) : '';

    // Validate pricing
    if ($discounted_price >= $original_price) {
        throw new Exception("Discounted price must be less than original price");
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
            price, original_price, expiration_date, 
            availability, description, storage_instructions, 
            image, status, listing_type, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, 
            ?, ?, ?, 
            ?, 'available', 'sell', NOW()
        )
    ");

    // Corrected bind_param with exact number of parameters
    $stmt->bind_param(
        "isssdddsssss", 
        $vendor_id, 
        $product_name, 
        $category, 
        $quantity, 
        $unit,
        $discounted_price, 
        $original_price, 
        $expiry_date,
        $availability, 
        $description, 
        $storage_instructions,
        $image_path
    );

    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Failed to add product: " . $stmt->error);
    }

    // Set success message
    $_SESSION['message'] = "Product added successfully!";
    $_SESSION['message_type'] = "success";

    // Redirect to products page
    header("Location: ../../vendor/products.php");
    exit();

} catch (Exception $e) {
    // Log the error
    error_log("Product Addition Error: " . $e->getMessage());

    // Set error message
    $_SESSION['message'] = $e->getMessage();
    $_SESSION['message_type'] = "error";

    // Redirect back to add product page
    header("Location: ../../vendor/add_product.php");
    exit();
}
?>