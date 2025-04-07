<?php
session_start();
require_once 'C:/Users/Purity/Desktop/php/htdocs/kenya-connect/finalproject/php/config.php';

// Check if user is logged in and is a vendor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'vendor') {
    // Redirect to login if not authenticated
    header("Location: login.php");
    exit();
}

// Fetch vendor details
$vendor_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM vendors WHERE id = ?");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();
$vendor = $result->fetch_assoc();

// Fetch recent products
$products_stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE vendor_id = ? 
    ORDER BY created_at DESC 
    LIMIT 6
");
$products_stmt->bind_param("i", $vendor_id);
$products_stmt->execute();
$products_result = $products_stmt->get_result();
$recent_products = $products_result->fetch_all(MYSQLI_ASSOC);

// Fetch recent orders
$orders_stmt = $conn->prepare("
    SELECT o.*, c.name AS consumer_name 
    FROM orders o 
    JOIN consumers c ON o.consumer_id = c.id 
    JOIN order_items oi ON o.id = oi.order_id 
    JOIN products p ON oi.product_id = p.id 
    WHERE p.vendor_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$orders_stmt->bind_param("i", $vendor_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$recent_orders = $orders_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - Kenya Connect</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/vendor_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="dashboard-body vendor-dashboard">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
       
        
        <section class="welcome-section">
            <div class="welcome-content">
                <h1>Welcome, <?php echo htmlspecialchars($vendor['business_name']); ?>!</h1>
                <p>Manage your surplus food listings and reduce food waste</p>
                <a href="add_product.php" class="btn btn-primary">List New Product</a>
            </div>
        </section>

        <div class="dashboard-grid">
            <section class="dashboard-section recent-products">
                <h2>Recent Products</h2>
                <div class="product-grid" id="products-container">
                    <?php if (!empty($recent_products)): ?>
                        <?php foreach ($recent_products as $product): ?>
                            <div class="product-card">
                                <img src="<?php echo htmlspecialchars($product['image'] ?? '../images/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <div class="product-details">
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="product-price">
                                        <?php echo $product['listing_type'] === 'donate' ? 'Free Donation' : 'KSh ' . number_format($product['price'], 2); ?>
                                    </p>
                                    <span class="product-status <?php echo $product['status']; ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm">Edit</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No products listed yet. <a href="add_product.php">Add your first product</a></p>
                    <?php endif; ?>
                </div>
                <a href="products.php" class="btn btn-secondary view-all-btn">View All Products</a>
            </section>

            <section class="dashboard-section recent-orders">
                <h2>Recent Orders</h2>
                <?php if (!empty($recent_orders)): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td><?php echo htmlspecialchars($order['consumer_name']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                    <td>KSh <?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($order['status']); ?>">
                                            <?php echo htmlspecialchars($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No recent orders. Start listing your surplus food!</p>
                <?php endif; ?>
                <a href="orders.php" class="btn btn-secondary view-all-btn">View All Orders</a>
            </section>

            <section class="dashboard-section business-summary">
                <h2>Business Summary</h2>
                <div class="summary-grid">
                    <div class="summary-card">
                        <i class="fas fa-box"></i>
                        <h3>Total Products</h3>
                        <p><?php echo count($recent_products); ?></p>
                    </div>
                    <div class="summary-card">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Total Orders</h3>
                        <p><?php echo count($recent_orders); ?></p>
                    </div>
                    <div class="summary-card">
                        <i class="fas fa-dollar-sign"></i>
                        <h3>Total Revenue</h3>
                        <p>KSh <?php 
                            $total_revenue = array_sum(array_column($recent_orders, 'total_amount'));
                            echo number_format($total_revenue, 2); 
                        ?></p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>
</html>