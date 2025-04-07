<?php
// Enable comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include database connection
require_once 'C:/Users/Purity/Desktop/php/htdocs/kenya-connect/finalproject/php/config.php';

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'consumer') {
    // If AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
    } else {
        // Redirect to login if not authenticated
        header("Location: ../../consumer/login.php");
    }
    exit();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    // If AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request method'
        ]);
    } else {
        // Redirect to checkout
        header("Location: ../../consumer/checkout.php");
    }
    exit();
}

// Variable to track transaction status
$transaction_started = false;

try {
    $consumer_id = $_SESSION['user_id'];
    
    // Get form data
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
    $pickup_date = isset($_POST['pickup_date']) ? $_POST['pickup_date'] : '';
    $pickup_time_from = isset($_POST['pickup_time_from']) ? $_POST['pickup_time_from'] : '';
    $pickup_time_to = isset($_POST['pickup_time_to']) ? $_POST['pickup_time_to'] : '';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $total_amount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0;
    
    // Validate data
    if (empty($pickup_date) || empty($pickup_time_from) || empty($pickup_time_to)) {
        throw new Exception("Please provide pickup date and time");
    }
    
    // Format pickup time
    $pickup_time = $pickup_date . ' ' . $pickup_time_from . ' - ' . $pickup_time_to;
    
    // Start transaction
    $conn->begin_transaction();
    $transaction_started = true;
    
    // Get cart items grouped by vendor
    $cart_query = "
        SELECT ci.id, ci.product_id, ci.quantity, 
               p.price, p.listing_type, p.vendor_id
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.consumer_id = ?
    ";
    
    $cart_stmt = $conn->prepare($cart_query);
    $cart_stmt->bind_param("i", $consumer_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    $vendor_orders = [];
    while ($item = $cart_result->fetch_assoc()) {
        $vendor_id = $item['vendor_id'];
        
        if (!isset($vendor_orders[$vendor_id])) {
            $vendor_orders[$vendor_id] = [
                'items' => [],
                'subtotal' => 0
            ];
        }
        
        $item_price = $item['listing_type'] === 'sell' ? $item['price'] : 0;
        $item_total = $item_price * $item['quantity'];
        
        $vendor_orders[$vendor_id]['items'][] = [
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'price' => $item_price,
            'total' => $item_total
        ];
        
        $vendor_orders[$vendor_id]['subtotal'] += $item_total;
    }
    
    // Generate a unique order number
    $order_number = 'KC-' . date('Ymd') . '-' . uniqid();
    
    // Process each vendor order
    foreach ($vendor_orders as $vendor_id => $vendor_order) {
        // Create an order for this vendor
        $order_query = "
            INSERT INTO orders (
                order_number, consumer_id, vendor_id, 
                status, total_amount, notes, pickup_time,
                created_at, updated_at
            ) VALUES (
                ?, ?, ?, 
                'pending', ?, ?, ?,
                NOW(), NOW()
            )
        ";
        
        $order_stmt = $conn->prepare($order_query);
        $order_stmt->bind_param(
            "siidss", 
            $order_number, 
            $consumer_id, 
            $vendor_id, 
            $vendor_order['subtotal'], 
            $notes, 
            $pickup_time
        );
        $order_stmt->execute();
        
        $order_id = $conn->insert_id;
        
        // Add order items
        foreach ($vendor_order['items'] as $item) {
            $item_query = "
                INSERT INTO order_items (
                    order_id, product_id, quantity, price
                ) VALUES (
                    ?, ?, ?, ?
                )
            ";
            
            $item_stmt = $conn->prepare($item_query);
            $item_stmt->bind_param(
                "iiid", 
                $order_id, 
                $item['product_id'], 
                $item['quantity'], 
                $item['price']
            );
            $item_stmt->execute();
        }
    }
    
    // Clear the cart
    $clear_cart_query = "DELETE FROM cart_items WHERE consumer_id = ?";
    $clear_cart_stmt = $conn->prepare($clear_cart_query);
    $clear_cart_stmt->bind_param("i", $consumer_id);
    $clear_cart_stmt->execute();
    
    // Commit transaction
    $conn->commit();
    $transaction_started = false;
    
    // Set success message
    $_SESSION['success_message'] = "Your order has been placed successfully! Order #" . $order_number;
    
    // If AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully',
            'order_number' => $order_number
        ]);
    } else {
        // Redirect to order confirmation
        header("Location: ../../consumer/order-confirmation.php?order=" . $order_number);
    }
    
} catch (Exception $e) {
    // Rollback transaction if it was started
    if ($transaction_started) {
        $conn->rollback();
    }
    
    // Log error
    error_log("Place Order Error: " . $e->getMessage());
    
    // If AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    } else {
        // Set error message
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
        // Redirect back to checkout
        header("Location: ../../consumer/checkout.php");
    }
}
exit();
?>