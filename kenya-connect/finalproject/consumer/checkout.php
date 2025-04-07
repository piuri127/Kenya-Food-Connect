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
    
    // Get cart items
    $query = "
        SELECT ci.id, ci.product_id, ci.quantity, 
               p.name, p.price, p.listing_type, p.expiration_date, p.image,
               v.id AS vendor_id, v.business_name AS vendor_name, v.area, v.city
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        JOIN vendors v ON p.vendor_id = v.id
        WHERE ci.consumer_id = ?
        ORDER BY v.business_name
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $consumer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart_items = [];
    $vendors = [];
    $subtotal = 0;
    
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        
        // Group by vendor
        if (!isset($vendors[$row['vendor_id']])) {
            $vendors[$row['vendor_id']] = [
                'id' => $row['vendor_id'],
                'name' => $row['vendor_name'],
                'area' => $row['area'],
                'city' => $row['city'],
                'items' => [],
                'subtotal' => 0
            ];
        }
        
        $item_total = $row['listing_type'] === 'sell' ? $row['price'] * $row['quantity'] : 0;
        $vendors[$row['vendor_id']]['items'][] = $row;
        $vendors[$row['vendor_id']]['subtotal'] += $item_total;
        $subtotal += $item_total;
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Checkout Error: " . $e->getMessage());
    // Redirect to cart with error
    $_SESSION['error'] = "An error occurred. Please try again.";
    header("Location: cart.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Kenya Connect</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/consumer_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .checkout-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .checkout-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .checkout-header h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .checkout-steps {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            padding: 0 20px;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            right: -50px;
            width: 100px;
            height: 2px;
            background-color: #ddd;
        }
        
        .step.active:not(:last-child)::after {
            background-color: #4caf50;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #ddd;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .step.active .step-number {
            background-color: #4caf50;
        }
        
        .step.completed .step-number {
            background-color: #4caf50;
        }
        
        .step-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .step.active .step-label {
            color: #333;
            font-weight: 600;
        }
        
        .checkout-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .checkout-content {
                grid-template-columns: 1fr;
            }
        }
        
        .order-details {
            margin-bottom: 30px;
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
        
        .vendor-items {
            padding: 15px;
        }
        
        .checkout-item {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .checkout-item:last-child {
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
        
        .order-form {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
        
        .payment-options {
            margin-bottom: 20px;
        }
        
        .payment-option {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            cursor: pointer;
        }
        
        .payment-option:hover {
            background-color: #f5f5f5;
        }
        
        .payment-option.selected {
            border-color: #4caf50;
            background-color: #e8f5e9;
        }
        
        .payment-option input {
            margin-right: 10px;
        }
        
        .payment-icon {
            margin-right: 10px;
            font-size: 1.2rem;
            color: #666;
        }
        
        .order-summary {
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
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
        
        .checkout-btn {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            font-size: 1.1rem;
        }
        
        .error-message {
            color: #e53935;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .empty-cart {
            text-align: center;
            padding: 40px 0;
        }
        
        .empty-cart i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .pickup-time-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
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
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> <span class="cart-count"><?php echo count($cart_items); ?></span></a></li>
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
        <div class="checkout-container">
            <div class="checkout-header">
                <h1>Checkout</h1>
            </div>
            
            <div class="checkout-steps">
                <div class="step completed">
                    <div class="step-number">1</div>
                    <div class="step-label">Cart</div>
                </div>
                <div class="step active">
                    <div class="step-number">2</div>
                    <div class="step-label">Checkout</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-label">Confirmation</div>
                </div>
            </div>
            
            <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your Cart is Empty</h2>
                <p>Add some items to your cart before proceeding to checkout.</p>
                <a href="search.php" class="btn btn-primary">Find Food</a>
            </div>
            <?php else: ?>
            <div class="checkout-content">
                <div class="order-details">
                    <h2>Order Details</h2>
                    
                    <?php foreach ($vendors as $vendor): ?>
                    <div class="vendor-group">
                        <div class="vendor-header">
                            <div class="vendor-name">
                                <i class="fas fa-store"></i>
                                <?php echo htmlspecialchars($vendor['name']); ?>
                            </div>
                            <div class="vendor-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($vendor['area'] . ', ' . $vendor['city']); ?>
                            </div>
                        </div>
                        <div class="vendor-items">
                            <?php foreach ($vendor['items'] as $item): ?>
                            <div class="checkout-item">
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
                                            <?php echo $item['listing_type'] === 'sell' 
                                                ? 'KSh ' . number_format($item['price'], 2) . ' x ' . $item['quantity']
                                                : 'Free (Donation)'; ?>
                                        </span>
                                        <span>
                                            <?php echo $item['listing_type'] === 'sell' 
                                                ? 'KSh ' . number_format($item['price'] * $item['quantity'], 2)
                                                : 'Free'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="vendor-subtotal">
                            Subtotal: KSh <?php echo number_format($vendor['subtotal'], 2); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-form">
                    <h2>Payment & Pickup</h2>
                    
                    <form id="checkout-form" action="../api/consumers/place_order.php" method="POST">
                        <div class="payment-options">
                            <div class="payment-option selected" onclick="selectPaymentMethod('cash')">
                                <input type="radio" name="payment_method" value="cash" id="payment-cash" checked>
                                <label for="payment-cash">Cash on Pickup</label>
                            </div>
                            <div class="payment-option" onclick="selectPaymentMethod('mpesa')">
                                <input type="radio" name="payment_method" value="mpesa" id="payment-mpesa">
                                <label for="payment-mpesa">M-Pesa</label>
                            </div>
                        </div>
                        
                        <div id="mpesa-fields" style="display: none;">
                            <div class="form-group">
                                <label for="phone-number">M-Pesa Phone Number</label>
                                <input type="text" id="phone-number" name="phone_number" placeholder="e.g., 07XXXXXXXX" value="<?php echo htmlspecialchars($consumer['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="pickup-date">Preferred Pickup Date</label>
                            <input type="date" id="pickup-date" name="pickup_date" required min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <div class="pickup-time-group">
                                <div>
                                    <label for="pickup-time-from">Pickup Time (From)</label>
                                    <input type="time" id="pickup-time-from" name="pickup_time_from" required>
                                </div>
                                <div>
                                    <label for="pickup-time-to">Pickup Time (To)</label>
                                    <input type="time" id="pickup-time-to" name="pickup_time_to" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Special Instructions (Optional)</label>
                            <textarea id="notes" name="notes" placeholder="Any special instructions for pickup or dietary concerns..."></textarea>
                        </div>
                        
                        <div class="order-summary">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>KSh <?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Service Fee</span>
                                <span>KSh 0.00</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span>KSh <?php echo number_format($subtotal, 2); ?></span>
                            </div>
                        </div>
                        
                        <input type="hidden" name="total_amount" value="<?php echo $subtotal; ?>">
                        
                        <button type="submit" class="btn btn-primary checkout-btn">
                            Place Order
                        </button>
                    </form>
                </div>
            </div>
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
        // Function to select payment method
        function selectPaymentMethod(method) {
            // Update radio button
            document.getElementById('payment-' + method).checked = true;
            
            // Update styling
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            event.currentTarget.classList.add('selected');
            
            // Show/hide M-Pesa fields
            if (method === 'mpesa') {
                document.getElementById('mpesa-fields').style.display = 'block';
            } else {
                document.getElementById('mpesa-fields').style.display = 'none';
            }
        }
        
        // Set default date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('pickup-date').value = today;
            
            // Set default pickup time window (e.g., 10 AM to 12 PM)
            document.getElementById('pickup-time-from').value = '10:00';
            document.getElementById('pickup-time-to').value = '12:00';
        });
    </script>
</body>
</html>