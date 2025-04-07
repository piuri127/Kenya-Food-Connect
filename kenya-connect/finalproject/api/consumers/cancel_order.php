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

// Check if order_id is provided
if (!isset($data['order_id']) || !is_numeric($data['order_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid order ID'
    ]);
    exit();
}

$order_id = (int)$data['order_id'];
$consumer_id = $_SESSION['user_id'];

try {
    // Start a transaction
    $conn->begin_transaction();
    
    // First, check if the order belongs to this consumer and is in 'pending' status
    $check_query = "
        SELECT id, status FROM orders 
        WHERE id = ? AND consumer_id = ? AND status = 'pending'
    ";
    
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $order_id, $consumer_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        // Order not found, doesn't belong to this consumer, or isn't in pending status
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Order not found or cannot be cancelled'
        ]);
        exit();
    }
    
    // Update the order status to 'cancelled'
    $update_query = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $order_id);
    $update_result = $update_stmt->execute();
    
    if (!$update_result) {
        // Failed to update order
        $conn->rollback();
        throw new Exception("Failed to update order status: " . $conn->error);
    }
    
    // Commit the transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback the transaction in case of error
    $conn->rollback();
    
    // Log error
    error_log("Cancel Order API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while cancelling the order'
    ]);
}
?>