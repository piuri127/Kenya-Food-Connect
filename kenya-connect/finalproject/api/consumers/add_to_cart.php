<?php
// Enable comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Include database connection
require_once 'C:/Users/Purity/Desktop/php/htdocs/kenya-connect/finalproject/php/config.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'consumer') {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit();
}

// Get the JSON data from the request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Check if required data is provided
if (!isset($data['product_id']) || !is_numeric($data['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product ID'
    ]);
    exit();
}

$product_id = (int)$data['product_id'];
$quantity = isset($data['quantity']) && is_numeric($data['quantity']) ? (int)$data['quantity'] : 1;
$consumer_id = $_SESSION['user_id'];

// Validate quantity
if ($quantity < 1 || $quantity > 10) {
    echo json_encode([
        'success' => false,
        'message' => 'Quantity must be between 1 and 10'
    ]);
    exit();
}

try {
    // First, check if the product exists and is available
    $product_query = "
        SELECT id, name, availability, expiration_date 
        FROM products 
        WHERE id = ? AND availability = 'available'
    ";
    
    $product_stmt = $conn->prepare($product_query);
    $product_stmt->bind_param("i", $product_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    
    if ($product_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not available'
        ]);
        exit();
    }
    
    $product = $product_result->fetch_assoc();
    
    // Check if product has expired
    $today = date('Y-m-d');
    if ($product['expiration_date'] < $today) {
        echo json_encode([
            'success' => false,
            'message' => 'Product has expired'
        ]);
        exit();
    }
    
    // Check if the product is already in the cart
    $check_query = "
        SELECT id, quantity 
        FROM cart_items 
        WHERE consumer_id = ? AND product_id = ?
    ";
    
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $consumer_id, $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update quantity if already in cart
        $cart_item = $check_result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;
        
        // Cap at maximum of 10
        if ($new_quantity > 10) {
            $new_quantity = 10;
        }
        
        $update_query = "
            UPDATE cart_items 
            SET quantity = ?, updated_at = NOW() 
            WHERE id = ?
        ";
        
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $new_quantity, $cart_item['id']);
        $update_result = $update_stmt->execute();
        
        if (!$update_result) {
            throw new Exception("Failed to update cart: " . $conn->error);
        }
        
        $message = 'Item quantity updated in cart';
    } else {
        // Insert new cart item
        $insert_query = "
            INSERT INTO cart_items (consumer_id, product_id, quantity, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW())
        ";
        
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iii", $consumer_id, $product_id, $quantity);
        $insert_result = $insert_stmt->execute();
        
        if (!$insert_result) {
            throw new Exception("Failed to add to cart: " . $conn->error);
        }
        
        $message = 'Item added to cart';
    }
    
    // Get total items in cart for response
    $count_query = "
        SELECT SUM(quantity) AS count 
        FROM cart_items 
        WHERE consumer_id = ?
    ";
    
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $consumer_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $cart_count = $count_row['count'] ? (int)$count_row['count'] : 0;
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'cart_count' => $cart_count
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Add to Cart API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while adding to cart'
    ]);
}
?>