<?php
// Enable comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Log file for debugging
$log_file = 'C:/Users/Purity/Desktop/php/htdocs/kenya-connect/finalproject/cart_debug.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Cart API called\n", FILE_APPEND);

try {
    // Include database connection
    require_once 'C:/Users/Purity/Desktop/php/htdocs/kenya-connect/finalproject/php/config.php';

    // Log database connection status
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Config included\n", FILE_APPEND);
    
    // Check if connection is valid
    if (!$conn || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn ? $conn->connect_error : "Connection object is null"));
    }
    
    // Check if user is logged in and is a consumer
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'consumer') {
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit();
    }

    $consumer_id = $_SESSION['user_id'];
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Consumer ID: $consumer_id\n", FILE_APPEND);
    
    // Check if cart_items table exists
    $tables_query = "SHOW TABLES LIKE 'cart_items'";
    $tables_result = $conn->query($tables_query);
    $table_exists = $tables_result->num_rows > 0;
    
    // If table doesn't exist, create it
    if (!$table_exists) {
        $create_table_sql = "
            CREATE TABLE `cart_items` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `consumer_id` int(11) NOT NULL,
              `product_id` int(11) NOT NULL,
              `quantity` int(11) NOT NULL DEFAULT 1,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `consumer_id` (`consumer_id`),
              KEY `product_id` (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $result = $conn->query($create_table_sql);
        if (!$result) {
            throw new Exception("Failed to create cart_items table: " . $conn->error);
        }
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Created cart_items table\n", FILE_APPEND);
    }
    
    // First check if consumer has any items in the cart
    $check_query = "SELECT COUNT(*) as count FROM cart_items WHERE consumer_id = ?";
    $check_stmt = $conn->prepare($check_query);
    if (!$check_stmt) {
        throw new Exception("Failed to prepare check query: " . $conn->error);
    }
    
    $check_stmt->bind_param("i", $consumer_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Cart items count: " . $check_row['count'] . "\n", FILE_APPEND);
    
    if ($check_row['count'] == 0) {
        // No items in cart
        echo json_encode([
            'success' => true,
            'items' => [],
            'count' => 0,
            'consumer_id' => $consumer_id
        ]);
        exit();
    }
    
    // Query to get cart items with product and vendor details
    $query = "
        SELECT ci.id, ci.product_id, ci.quantity, 
               p.name, p.description, p.price, p.image, p.listing_type, p.expiration_date,
               v.id AS vendor_id, v.business_name AS vendor_name, v.area, v.city
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        JOIN vendors v ON p.vendor_id = v.id
        WHERE ci.consumer_id = ?
        ORDER BY v.business_name, p.name
    ";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare main query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $consumer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Log the number of rows returned
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Query returned " . $result->num_rows . " rows\n", FILE_APPEND);
    
    $cart_items = [];
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = [
            'id' => $row['id'],
            'product_id' => $row['product_id'],
            'quantity' => $row['quantity'],
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => $row['price'],
            'image' => $row['image'],
            'listing_type' => $row['listing_type'],
            'expiration_date' => $row['expiration_date'],
            'vendor_id' => $row['vendor_id'],
            'vendor_name' => $row['vendor_name'],
            'area' => $row['area'],
            'city' => $row['city']
        ];
    }
    
    // Log successful response
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Returning " . count($cart_items) . " items\n", FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'items' => $cart_items,
        'count' => count($cart_items),
        'consumer_id' => $consumer_id
    ]);
    
} catch (Exception $e) {
    // Log detailed error information
    $error_message = date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n";
    $error_message .= "Stack trace: " . $e->getTraceAsString() . "\n";
    file_put_contents($log_file, $error_message, FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching cart data: ' . $e->getMessage(),
        'debug_info' => isset($conn) ? $conn->error : 'No connection'
    ]);
}
?>