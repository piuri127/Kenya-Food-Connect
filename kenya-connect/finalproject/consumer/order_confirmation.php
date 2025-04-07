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
    // Redirect to login if not authenticated
    header("Location: login.php");
    exit();
}

// Check if order number is provided
if (!isset($_GET['order']) || empty($_GET['order'])) {
    // Redirect to orders page
    header("Location: orders.php");
    exit();
}

$order_number = $_GET['order'];
$consumer_id = $_SESSION['user_id'];

// Fetch order details
try {
    // Prepare statement to fetch consumer details
    $consumer_stmt = $conn->prepare("SELECT * FROM consumers WHERE id = ?");
    $consumer_stmt->bind_param("i", $consumer_id);
    $consumer_stmt->execute();
    $consumer_result = $consumer_stmt->get_result();
    
    if ($consumer_result->num_rows === 0) {
        throw new Exception("Consumer not found");
    }
    
    $consumer = $consumer_result->fetch_assoc();
    
    // Get order details
    $order_query = "
        SELECT o.*, v.business_name AS vendor_name, v.phone AS vendor_phone,
               v.email AS vendor_email, v.area AS vendor_area, v.city AS vendor_city
        FROM orders o
        JOIN vendors v ON o.vendor_id = v.id
        WHERE o.order_number = ? AND o.consumer_id = ?
        LIMIT 1
    ";
    
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->bind_param("si", $order_number, $consumer_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    
    if ($order_result->num_rows === 0) {
        throw new Exception("Order not found");
    }
    
    $order = $order_result->fetch_assoc();
    
    // Get all orders with the same order number (multiple vendors)
    $orders_query = "
        SELECT o.id, o.vendor_id, o.total_amount, o.status,
               v.business_name AS vendor_name, v.area AS vendor_area, v.city AS vendor_city
        FROM orders o
        JOIN vendors v ON o.vendor_id = v.id
        WHERE o.order_number = ? AND o.consumer_id = ?
    ";
    
    $orders_stmt = $conn->prepare($orders_query);
    $orders_stmt->bind_param("si", $order_number, $consumer_id);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
    
    $vendors = [];
    $total_amount = 0;
    
    while ($vendor_order = $orders_result->fetch_assoc()) {
        $vendor_id = $vendor_order['vendor_id'];
        
        // Get order items for this vendor
        $items_query = "
            SELECT oi.*, p.name, p.image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ";
        
        $items_stmt = $conn->prepare($items_query);
        $items_stmt->bind_param("i", $vendor_order['id']);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        $items = [];
        $item_count = 0;
        
        while ($item = $items_result->fetch_assoc()) {
            $items[] = $item;
            $item_count += $item['quantity'];
        }
        
        $vendors[] = [
            'id' => $vendor_id,
            'name' => $vendor_order['vendor_name'],
            'area' => $vendor_order['vendor_area'],
            'city' => $vendor_order['vendor_city'],
            'total' => $vendor_order['total_amount'],
            'status' => $vendor_order['status'],
            'items' => $items,
            'item_count' => $item_count
        ];
        
        $total_amount += $vendor_order['total_amount'];
    }
} catch (Exception $e) {
    // Log error
    error_log("Order Confirmation Error: " . $e->getMessage());
    // Redirect to orders with error
    $_SESSION['error'] = "An error occurred. Please try again.";
    header("Location: orders.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Kenya Connect</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/consumer_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .confirmation-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .confirmation-header {
            text-align: center;
            padding: 30px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 30px;
        }
        
        .confirmation-header .success-icon {
            font-size: 5rem;
            color: #4caf50;
            margin-bottom: 15px;
        }
        
        .confirmation-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #333;
        }
        
        .confirmation-header p {
            font-size: 1.1rem;
            color: #666;
        }
        
        .order-number {
            font-weight: 600;
        }
        
        .confirmation-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .confirmation-details {
                grid-template-columns: 1fr;
            }
        }
        
        .detail-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
        }
        
        .detail-card h3 {
            margin-top: 0;
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .detail-card h3 i {
            margin-right: 8px;
            color: #4caf50;
        }
        
        .detail-card p {
            margin: 8px 0;
            color: #555;
        }
        
        .vendor-group {
            margin-bottom: 25px;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .vendor-header {
            background-color: #f5f5f5;
            padding: 12px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .vendor-name {
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .vendor-name i {
            margin-right: 8px;
            color: #4caf50;
        }
        
        .vendor-status {
            display: flex;
            align-items: center;
        }
        
        .vendor-items {
            padding: 15px;
        }
        
        .confirmation-item {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .confirmation-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 4px;
            overflow: hidden;
            margin-right: 15px;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .item-price {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #666;
        }
        
        .vendor-subtotal {
            padding: 10px 15px;
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
            text-align: right;
            font-weight: 600;
        }
        
        .order-summary {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        
        .summary-row.total {
            font-weight: 600;
            font-size: 1.1rem;
            border-top: 1px solid #ddd;
            margin-top: 10px;
            padding-top: 10px;
        }
        
        .next-steps {
            background-color: #e8f5e9;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .next-steps h3 {
            margin-top: 0;
            color: #2e7d32;
            margin-bottom: 15px;
        }
        
        .next-steps ol {
            padding-left: 20px;
            margin: 0;
        }
        
        .next-steps li {
            margin-bottom: 10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .action-buttons .btn {
            padding: 12px 25px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .pending {
            background-color: #fff8e1;
            color: #f57c00;
        }
        
        .processing {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .completed {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .cancelled {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        .ready {
            background-color: #e0f7fa;
            color: #0097a7;
        }
    </style>
</head>
<body class="dashboard-body">
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="../index.html">
                    <h1>Kenya Connect</h1>
                </a>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="search.php">Find Food</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> <span class="cart-count">0</span></a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-user-circle"></i> 
                        <span id="user-name"><?php echo htmlspecialchars($consumer['name']); ?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="profile.php">Profile</a></li>
                        <li><a href="../api/auth/logout.php" id="logout-link">Logout</a></li>
                    </ul>
                </li>
            </ul>
            <div class="hamburger">
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
            </div>
        </nav>
    </header>

    <main class="dashboard-container">
        <div class="confirmation-container">
            <div class="confirmation-header">
                <i class="fas fa-check-circle success-icon"></i>
                <h1>Order Confirmed!</h1>
                <p>Your order <span class="order-number">#<?php echo htmlspecialchars($order_number); ?></span> has been placed successfully.</p>
                <p>We've sent a confirmation email to <strong><?php echo htmlspecialchars($consumer['email']); ?></strong></p>
            </div>
            
            <div class="confirmation-details">
                <div class="detail-card">
                    <h3><i class="fas fa-user"></i> Customer Information</h3>
                    <p><strong><?php echo htmlspecialchars($consumer['name']); ?></strong></p>
                    <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($consumer['phone']); ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($consumer['email']); ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($consumer['area'] . ', ' . $consumer['city']); ?></p>
                </div>
                
                <div class="detail-card">
                    <h3><i class="fas fa-info-circle"></i> Order Information</h3>
                    <p><strong>Order Number:</strong> #<?php echo htmlspecialchars($order_number); ?></p>
                    <p><strong>Order Date:</strong> <?php echo date('d M, Y H:i', strtotime($order['created_at'])); ?></p>
                    <p><strong>Payment Method:</strong> <?php echo ucfirst(htmlspecialchars($order['payment_method'])); ?></p>
                    <p><strong>Pickup Time:</strong> <?php echo htmlspecialchars($order['pickup_time']); ?></p>
                </div>
            </div>
            
            <h2>Order Details</h2>
            
            <?php foreach ($vendors as $vendor): ?>
            <div class="vendor-group">
                <div class="vendor-header">
                    <div class="vendor-name">
                        <i class="fas fa-store"></i>
                        <?php echo htmlspecialchars($vendor['name']); ?>
                    </div>
                    <div class="vendor-status">
                        <span class="status-badge <?php echo strtolower($vendor['status']); ?>">
                            <?php echo ucfirst($vendor['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="vendor-items">
                    <?php foreach ($vendor['items'] as $item): ?>
                    <div class="confirmation-item">
                        <div class="item-image">
                            <?php if (!empty($item['image'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php else: ?>
                            <img src="../images/placeholder-food.jpg" alt="Food placeholder">
                            <?php endif; ?>
                        </div>
                        <div class="item-details">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-price">
                                <span>
                                    <?php if ($item['price'] > 0): ?>
                                    KSh <?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?>
                                    <?php else: ?>
                                    Free (Donation) x <?php echo $item['quantity']; ?>
                                    <?php endif; ?>
                                </span>
                                <span>
                                    <?php if ($item['price'] > 0): ?>
                                    KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                    <?php else: ?>
                                    Free
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="vendor-subtotal">
                    Subtotal: KSh <?php echo number_format($vendor['total'], 2); ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="order-summary">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>KSh <?php echo number_format($total_amount, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Service Fee</span>
                    <span>KSh 0.00</span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span>KSh <?php echo number_format($total_amount, 2); ?></span>
                </div>
            </div>
            
            <div class="next-steps">
                <h3>What's Next?</h3>
                <ol>
                    <li>The vendor will review and process your order.</li>
                    <li>You'll receive a notification when your order is ready for pickup.</li>
                    <li>Visit the vendor location during your selected pickup time.</li>
                    <li>Show your order number (#<?php echo htmlspecialchars($order_number); ?>) to the vendor.</li>
                    <li>Enjoy your food while reducing food waste!</li>
                </ol>
            </div>
            
            <div class="action-buttons">
                <a href="orders.php" class="btn btn-secondary">View All Orders</a>
                <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
        </div>
    </main>

    <footer>
        <div class="social-media">
            <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
        </div>
        <p class="copyright">&copy; 2025 Kenya Connect. All rights reserved.</p>
    </footer>

    <script src="../js/script.js"></script>
</body>
</html>