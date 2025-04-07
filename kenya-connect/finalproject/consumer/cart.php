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

// Fetch consumer details
try {
    $consumer_id = $_SESSION['user_id'];
    
    // Prepare statement to fetch consumer details
    $stmt = $conn->prepare("SELECT * FROM consumers WHERE id = ?");
    $stmt->bind_param("i", $consumer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Consumer not found");
    }
    
    $consumer = $result->fetch_assoc();
    
    // Check if cart_items table exists, create if it doesn't
    $table_check = $conn->query("SHOW TABLES LIKE 'cart_items'");
    if ($table_check->num_rows == 0) {
        // Table doesn't exist, create it
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
        $conn->query($create_table_sql);
    }
    
    // Get current cart count for display
    $count_query = "SELECT SUM(quantity) as count FROM cart_items WHERE consumer_id = ?";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $consumer_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $cart_count = $count_row['count'] ? (int)$count_row['count'] : 0;
    
    // Get cart items directly from the database
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
    $stmt->bind_param("i", $consumer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart_items = [];
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Cart Error: " . $e->getMessage());
    // Redirect to login with error
    $_SESSION['error'] = "An error occurred. Please log in again.";
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Kenya Connect</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/consumer_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .cart-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .cart-header h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .cart-count-badge {
            background-color: #4caf50;
            color: white;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            margin-left: 8px;
        }
        
        .cart-empty {
            text-align: center;
            padding: 40px 0;
        }
        
        .cart-empty i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .cart-empty p {
            color: #666;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .cart-items {
            margin-bottom: 20px;
        }
        
        .cart-item {
            display: flex;
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            position: relative;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            border-radius: 8px;
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
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .item-vendor {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .item-price {
            font-weight: 500;
            font-size: 1.1rem;
            color: #333;
        }
        
        .item-free {
            color: #4caf50;
            font-weight: 500;
        }
        
        .item-actions {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            margin-right: 20px;
        }
        
        .quantity-btn {
            width: 32px;
            height: 32px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            user-select: none;
        }
        
        .quantity-btn:hover {
            background-color: #e9e9e9;
        }
        
        .quantity-btn.disabled {
            color: #ccc;
            cursor: not-allowed;
        }
        
        .quantity-input {
            width: 45px;
            height: 32px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 0 8px;
        }
        
        .remove-btn {
            color: #e53935;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        
        .remove-btn i {
            margin-right: 5px;
        }
        
        .item-total {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            padding-left: 15px;
        }
        
        .total-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }
        
        .cart-summary {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }
        
        .summary-row:not(:last-child) {
            border-bottom: 1px solid #eee;
        }
        
        .summary-row.total {
            font-weight: 600;
            font-size: 1.2rem;
            padding-top: 15px;
            border-top: 2px solid #ddd;
            margin-top: 10px;
        }
        
        .checkout-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        
        .continue-shopping {
            display: flex;
            align-items: center;
            color: #333;
            text-decoration: none;
        }
        
        .continue-shopping i {
            margin-right: 5px;
        }
        
        .checkout-btn {
            padding: 12px 25px;
            font-size: 1.1rem;
        }
        
        .checkout-note {
            margin-top: 15px;
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Vendor grouping */
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
        
        .vendor-items {
            padding: 0 15px;
        }
        
        /* Animation for removing items */
        .fade-out {
            animation: fadeOut 0.5s;
            opacity: 0;
            height: 0;
            padding: 0;
            margin: 0;
            overflow: hidden;
        }
        
        @keyframes fadeOut {
            from { opacity: 1; height: auto; }
            to { opacity: 0; height: 0; }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .cart-item {
                flex-wrap: wrap;
            }
            
            .item-total {
                width: 100%;
                flex-direction: row;
                justify-content: space-between;
                padding: 10px 0 0;
                margin-top: 10px;
                border-top: 1px dashed #eee;
            }
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
                <li><a href="cart.php" class="active"><i class="fas fa-shopping-cart"></i> <span class="cart-count"><?php echo $cart_count; ?></span></a></li>
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
        <div class="cart-container">
            <div class="cart-header">
                <h1>Shopping Cart <span class="cart-count-badge"><?php echo $cart_count; ?></span></h1>
            </div>
            
            <?php if (empty($cart_items)): ?>
                <div class="cart-empty">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Cart Empty</h2>
                    <p>Your cart is empty. Add some items to continue.</p>
                    <a href="search.php" class="btn btn-primary">Find Food</a>
                </div>
            <?php else: ?>
                <?php
                // Group items by vendor
                $vendors = [];
                $subtotal = 0;
                
                foreach ($cart_items as $item) {
                    if (!isset($vendors[$item['vendor_id']])) {
                        $vendors[$item['vendor_id']] = [
                            'id' => $item['vendor_id'],
                            'name' => $item['vendor_name'],
                            'items' => []
                        ];
                    }
                    
                    $vendors[$item['vendor_id']]['items'][] = $item;
                    
                    // Calculate subtotal
                    if ($item['listing_type'] === 'sell') {
                        $subtotal += $item['price'] * $item['quantity'];
                    }
                }
                ?>
                
                <div id="cart-items-container">
                    <?php foreach ($vendors as $vendor): ?>
                        <div class="vendor-group" id="vendor-<?php echo $vendor['id']; ?>">
                            <div class="vendor-header">
                                <div class="vendor-name">
                                    <i class="fas fa-store"></i>
                                    <?php echo htmlspecialchars($vendor['name']); ?>
                                </div>
                            </div>
                            <div class="vendor-items">
                                <?php foreach ($vendor['items'] as $item): ?>
                                    <?php
                                    $itemTotal = $item['listing_type'] === 'sell' ? ($item['price'] * $item['quantity']) : 0;
                                    
                                    // Handle image path properly
$productImage = '../images/placeholder-food.jpg';
if (!empty($item['image'])) {
    if (strpos($item['image'], 'http') === 0) {
        // Absolute URL
        $productImage = $item['image'];
    } else {
        // Relative path - prepend base URL if needed
        if (strpos($item['image'], '/') === 0) {
            $productImage = $item['image']; // Path already starts with /
        } else {
            $productImage = '/' . $item['image']; // Add leading slash
        }
    }
}
                                    ?>
                                    
                                    <div class="cart-item" id="cart-item-<?php echo $item['id']; ?>" data-id="<?php echo $item['id']; ?>">
                                        <div class="item-image">
                                            <img src="<?php echo htmlspecialchars($productImage); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='../images/placeholder-food.jpg'">
                                        </div>
                                        <div class="item-details">
                                            <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                            <p class="item-vendor"><?php echo htmlspecialchars($vendor['name']); ?></p>
                                            <p class="<?php echo $item['listing_type'] === 'sell' ? 'item-price' : 'item-free'; ?>">
                                                <?php if ($item['listing_type'] === 'sell'): ?>
                                                    KSh <?php echo number_format((float)$item['price'], 2); ?>
                                                <?php else: ?>
                                                    Free (Donation)
                                                <?php endif; ?>
                                            </p>
                                            <div class="item-actions">
                                                <div class="quantity-control">
                                                    <div class="quantity-btn <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>" 
                                                         onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">
                                                        <i class="fas fa-minus"></i>
                                                    </div>
                                                    <input type="text" class="quantity-input" value="<?php echo $item['quantity']; ?>" 
                                                           onchange="updateQuantityInput(<?php echo $item['id']; ?>, this.value)" min="1" max="10">
                                                    <div class="quantity-btn" 
                                                         onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">
                                                        <i class="fas fa-plus"></i>
                                                    </div>
                                                </div>
                                                <button class="remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)">
                                                    <i class="fas fa-trash-alt"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                        <div class="item-total">
                                            <div class="total-price">
                                                <?php if ($item['listing_type'] === 'sell'): ?>
                                                    KSh <?php echo number_format($itemTotal, 2); ?>
                                                <?php else: ?>
                                                    Free
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal (<?php echo $cart_count; ?> item<?php echo $cart_count !== 1 ? 's' : ''; ?>)</span>
                        <span>KSh <?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>KSh <?php echo number_format($subtotal, 2); ?></span>
                    </div>
                </div>
                
                <div class="checkout-section">
                    <a href="search.php" class="continue-shopping">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                    <a href="checkout.php" class="btn btn-primary checkout-btn">
                        Proceed to Checkout
                    </a>
                </div>
                
                <p class="checkout-note">
                    * Please note that you will need to pick up your order from the respective vendors.
                </p>
            <?php endif; ?>
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
    <script>
        // Function to update item quantity
        function updateQuantity(itemId, newQuantity) {
            // Validate quantity
            if (newQuantity < 1) {
                return;
            }
            
            // Update on server
            fetch('../api/consumers/update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    item_id: itemId,
                    quantity: newQuantity
                }),
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to reflect changes
                    window.location.reload();
                } else {
                    alert(data.message || 'Error updating quantity');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating quantity. Please try again.');
            });
        }

        // Handle quantity input change
        function updateQuantityInput(itemId, value) {
            // Parse and validate input
            let quantity = parseInt(value);
            if (isNaN(quantity) || quantity < 1) {
                quantity = 1;
            } else if (quantity > 10) {
                quantity = 10;
            }
            
            // Update quantity
            updateQuantity(itemId, quantity);
        }

        // Remove item from cart
        function removeItem(itemId) {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                return;
            }
            
            // Remove on server
            fetch('../api/consumers/remove_from_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    item_id: itemId
                }),
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to reflect changes
                    window.location.reload();
                } else {
                    alert(data.message || 'Error removing item');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error removing item. Please try again.');
            });
        }
    </script>
</body>
</html>