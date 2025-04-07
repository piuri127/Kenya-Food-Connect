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

// Check if item_id is provided
if (!isset($data['item_id']) || !is_numeric($data['item_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid item ID'
    ]);
    exit();
}

$cart_item_id = (int)$data['item_id'];
$consumer_id = $_SESSION['user_id'];

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
    
    // Delete the cart item
    $delete_query = "
        DELETE FROM cart_items 
        WHERE id = ? AND consumer_id = ?
    ";
    
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $cart_item_id, $consumer_id);
    $delete_result = $delete_stmt->execute();
    
    if (!$delete_result) {
        throw new Exception("Failed to remove from cart: " . $conn->error);
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
        'message' => 'Item removed from cart',
        'cart_count' => $cart_count
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Remove From Cart API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while removing item from cart'
    ]);
}
?>