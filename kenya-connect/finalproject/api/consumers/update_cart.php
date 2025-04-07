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
if (!isset($data['item_id']) || !isset($data['quantity']) || !is_numeric($data['item_id']) || !is_numeric($data['quantity'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid data provided'
    ]);
    exit();
}

$cart_item_id = (int)$data['item_id'];
$quantity = (int)$data['quantity'];
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
    // First, check if the cart item exists and belongs to this consumer
    $check_query = "
        SELECT id FROM cart_items 
        WHERE id = ? AND consumer_id = ?
    ";
    
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $cart_item_id, $consumer_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cart item not found'
        ]);
        exit();
    }
    
    // Update the quantity
    $update_query = "
        UPDATE cart_items 
        SET quantity = ?, updated_at = NOW() 
        WHERE id = ? AND consumer_id = ?
    ";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("iii", $quantity, $cart_item_id, $consumer_id);
    $update_result = $update_stmt->execute();
    
    if (!$update_result) {
        throw new Exception("Failed to update cart: " . $conn->error);
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
        'message' => 'Cart updated successfully',
        'cart_count' => $cart_count
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Update Cart API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating cart'
    ]);
}
?>