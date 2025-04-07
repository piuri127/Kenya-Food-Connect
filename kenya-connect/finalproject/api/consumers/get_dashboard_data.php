<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include database connection
require_once 'C:/Users/Purity/Desktop/php/htdocs/kenya-connect/finalproject/php/config.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'consumer') {
    http_response_code(401);
    echo json_encode([
        'error' => 'Unauthorized access',
        'message' => 'You must be logged in as a consumer to access this data'
    ]);
    exit();
}

try {
    $consumer_id = $_SESSION['user_id'];
    
    // Fetch nearby products
    $products_stmt = $conn->prepare("
        SELECT p.*, v.business_name, v.city, v.area 
        FROM products p 
        JOIN vendors v ON p.vendor_id = v.id 
        WHERE p.status = 'available' 
        AND v.city = (SELECT city FROM consumers WHERE id = ?)
        AND p.expiration_date >= CURDATE() 
        ORDER BY p.created_at DESC 
        LIMIT 6
    ");
    $products_stmt->bind_param("i", $consumer_id);
    $products_stmt->execute();
    $products_result = $products_stmt->get_result();
    
    $nearby_products = [];
    while ($row = $products_result->fetch_assoc()) {
        $nearby_products[] = $row;
    }
    
    // Fetch recent orders
    $orders_stmt = $conn->prepare("
        SELECT o.*, COUNT(oi.id) as item_count 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.consumer_id = ? 
        GROUP BY o.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $orders_stmt->bind_param("i", $consumer_id);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
    
    $recent_orders = [];
    while ($row = $orders_result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
    
    // Prepare response
    $response = [
        'nearby_products' => $nearby_products,
        'recent_orders' => $recent_orders
    ];
    
    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log the error
    error_log("Dashboard Data Error: " . $e->getMessage());
    
    // Send error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage()
    ]);
}
exit();
?>