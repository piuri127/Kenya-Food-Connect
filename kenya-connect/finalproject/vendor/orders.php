<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Check if user is logged in and is a vendor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'vendor') {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'C:/Users/Purity/Desktop/php/htdocs/kenya-connect/finalproject/php/config.php';

// Get vendor information
$vendor_id = $_SESSION['user_id'];
$vendor_name = $_SESSION['name'];

// Pagination setup
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$results_per_page = 10;
$offset = ($page - 1) * $results_per_page;

// Search and filter variables
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_filter = isset($_GET['date_range']) ? trim($_GET['date_range']) : '';

// Prepare base SQL query for orders that include this vendor's products
// Adjusted to match your actual database columns
$base_query = "
    SELECT o.id as order_id, o.order_number, o.created_at as order_date, 
           o.status as order_status, o.total_amount, o.consumer_id,
           c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
           COUNT(oi.id) as items_count
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN consumers c ON o.consumer_id = c.id
    WHERE p.vendor_id = ?
";

$group_by = " GROUP BY o.id";
$params = [$vendor_id];
$param_types = "i";

// Add search condition if search query exists
if (!empty($search_query)) {
    $base_query .= " AND (o.order_number LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $param_types .= "sss";
}

// Add status filter
if (!empty($status_filter)) {
    $base_query .= " AND o.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

// Add date filter
if (!empty($date_filter)) {
    // Parse date_filter (e.g., "last7days", "last30days", "thismonth", etc.)
    switch ($date_filter) {
        case 'today':
            $base_query .= " AND DATE(o.created_at) = CURDATE()";
            break;
        case 'yesterday':
            $base_query .= " AND DATE(o.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'last7days':
            $base_query .= " AND DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'last30days':
            $base_query .= " AND DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
        case 'thismonth':
            $base_query .= " AND MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
            break;
        case 'lastmonth':
            $base_query .= " AND MONTH(o.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
                         AND YEAR(o.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
            break;
    }
}

// Count total results for pagination
$count_query = "SELECT COUNT(DISTINCT o.id) as total 
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN consumers c ON o.consumer_id = c.id
                WHERE p.vendor_id = ?";

// Add the same conditions as the base query
if (!empty($search_query)) {
    $count_query .= " AND (o.order_number LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
}
if (!empty($status_filter)) {
    $count_query .= " AND o.status = ?";
}
if (!empty($date_filter)) {
    // Add the same date condition as above
    switch ($date_filter) {
        case 'today':
            $count_query .= " AND DATE(o.created_at) = CURDATE()";
            break;
        case 'yesterday':
            $count_query .= " AND DATE(o.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'last7days':
            $count_query .= " AND DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'last30days':
            $count_query .= " AND DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
        case 'thismonth':
            $count_query .= " AND MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
            break;
        case 'lastmonth':
            $count_query .= " AND MONTH(o.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
                         AND YEAR(o.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
            break;
    }
}

$count_stmt = $conn->prepare($count_query);
$count_params = $params; // Create a copy of params array
$count_stmt->bind_param($param_types, ...$count_params);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_results = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_results / $results_per_page);

// Add pagination and ordering to the final query
$final_query = $base_query . $group_by . " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $results_per_page;
$params[] = $offset;
$param_types .= "ii";

// Prepare and execute final query
$stmt = $conn->prepare($final_query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Vendor Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/vendor_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="dashboard-page">
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="dashboard-content">
            <header class="dashboard-header">
                <button class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-right">
                    <div class="user-dropdown">
                        <button class="dropdown-toggle">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars($vendor_name); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                            <a href="../api/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </header>
            
            <div class="dashboard-main">
                <div class="page-header">
                    <h1>My Orders</h1>
                </div>

                <?php
                // Display any flash messages
                if (isset($_SESSION['message'])) {
                    echo '<div class="flash-message ' . $_SESSION['message_type'] . '">
                        <p>' . htmlspecialchars($_SESSION['message']) . '</p>
                        <button class="close-btn"><i class="fas fa-times"></i></button>
                    </div>';
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                }
                ?>
                
                <div class="orders-filter">
                    <form method="get" class="filter-form">
                        <div class="filter-row">
                            <div class="search-container">
                                <input type="text" name="search" placeholder="Search by order #, customer..." 
                                       value="<?php echo htmlspecialchars($search_query); ?>">
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            
                            <select name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            
                            <select name="date_range">
                                <option value="">All Time</option>
                                <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="yesterday" <?php echo $date_filter === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                                <option value="last7days" <?php echo $date_filter === 'last7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="last30days" <?php echo $date_filter === 'last30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                                <option value="thismonth" <?php echo $date_filter === 'thismonth' ? 'selected' : ''; ?>>This Month</option>
                                <option value="lastmonth" <?php echo $date_filter === 'lastmonth' ? 'selected' : ''; ?>>Last Month</option>
                            </select>
                        </div>
                    </form>
                </div>

                <div class="orders-table-container">
                    <?php if (empty($orders)): ?>
                        <div class="no-orders">
                            <p>No orders found.</p>
                        </div>
                    <?php else: ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="order-number">
                                            <span><?php echo htmlspecialchars($order['order_number']); ?></span>
                                        </td>
                                        <td class="order-date">
                                            <?php echo date('M d, Y', strtotime($order['order_date'])); ?>
                                            <span class="order-time"><?php echo date('h:i A', strtotime($order['order_date'])); ?></span>
                                        </td>
                                        <td class="customer-info">
                                            <div><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest Customer'); ?></div>
                                            <span class="customer-email"><?php echo htmlspecialchars($order['customer_email'] ?? 'No email provided'); ?></span>
                                        </td>
                                        <td class="order-items">
                                            <?php echo $order['items_count']; ?> items
                                        </td>
                                        <td class="order-total">
                                            KSh <?php echo number_format($order['total_amount'], 2); ?>
                                        </td>
                                        <td class="order-status">
                                            <span class="status-badge <?php echo strtolower($order['order_status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($order['order_status'])); ?>
                                            </span>
                                        </td>
                                        <td class="order-actions">
                                            <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if ($order['order_status'] === 'pending'): ?>
                                                <a href="../api/vendor/update_order_status.php?id=<?php echo $order['order_id']; ?>&status=processing" 
                                                   class="btn btn-sm btn-secondary" 
                                                   onclick="return confirm('Are you sure you want to mark this order as processing?');">
                                                    <i class="fas fa-box"></i> Process
                                                </a>
                                            <?php elseif ($order['order_status'] === 'processing'): ?>
                                                <a href="../api/vendor/update_order_status.php?id=<?php echo $order['order_id']; ?>&status=shipped" 
                                                   class="btn btn-sm btn-secondary" 
                                                   onclick="return confirm('Are you sure you want to mark this order as shipped?');">
                                                    <i class="fas fa-shipping-fast"></i> Ship
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; 
                                    echo !empty($search_query) ? '&search=' . urlencode($search_query) : '';
                                    echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : '';
                                    echo !empty($date_filter) ? '&date_range=' . urlencode($date_filter) : ''; 
                                ?>" 
                               class="<?php echo $page == $i ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <footer class="dashboard-footer">
                <p>&copy; <?php echo date('Y'); ?> Kenya Connect. All rights reserved.</p>
            </footer>
        </main>
    </div>
    
    <script src="../js/script.js"></script>
    <script>
        // Handle filter form submission on change
        document.querySelectorAll('.filter-form select').forEach(select => {
            select.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });

        // Close flash messages
        const closeButtons = document.querySelectorAll('.close-btn');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.flash-message').style.display = 'none';
            });
        });
    </script>
</body>
</html>