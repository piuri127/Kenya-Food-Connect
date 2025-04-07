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

try {
    $consumer_id = $_SESSION['user_id'];
    
    // Prepare statement to fetch orders
    $query = "
        SELECT o.id, o.order_number, o.created_at, o.status, o.total_amount,
               v.business_name AS vendor_name,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
        FROM orders o
        JOIN vendors v ON o.vendor_id = v.id
        WHERE o.consumer_id = ?
        ORDER BY o.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $consumer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = [
            'id' => $row['id'],
            'order_number' => $row['order_number'],
            'created_at' => $row['created_at'],
            'status' => $row['status'],
            'total_amount' => $row['total_amount'],
            'vendor_name' => $row['vendor_name'],
            'item_count' => $row['item_count']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Get Orders API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching orders'
    ]);
}
?>