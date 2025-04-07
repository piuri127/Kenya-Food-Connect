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
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// Prepare base SQL query
$base_query = "SELECT * FROM products WHERE vendor_id = ?";
$params = [$vendor_id];
$param_types = "i";

// Add search condition if search query exists
if (!empty($search_query)) {
    $base_query .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $param_types .= "ss";
}

// Add status filter
if (!empty($status_filter)) {
    $base_query .= " AND status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

// Add category filter
if (!empty($category_filter)) {
    $base_query .= " AND category = ?";
    $params[] = $category_filter;
    $param_types .= "s";
}

// Count total results for pagination
$count_stmt = $conn->prepare(str_replace("*", "COUNT(*) as total", $base_query));
$count_params = $params; // Create a copy of params array
$count_stmt->bind_param($param_types, ...$count_params);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_results = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_results / $results_per_page);

// Add pagination to query
$paginated_query = $base_query . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $results_per_page;
$params[] = $offset;
$param_types .= "ii";

// Prepare and execute final query
$stmt = $conn->prepare($paginated_query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);

// Fetch unique categories for filter dropdown
$categories_stmt = $conn->prepare("SELECT DISTINCT category FROM products WHERE vendor_id = ?");
$categories_stmt->bind_param("i", $vendor_id);
$categories_stmt->execute();
$categories_result = $categories_stmt->get_result();
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - Vendor Dashboard</title>
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
                    <h1>My Products</h1>
                    <a href="add_product.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Product
                    </a>
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
                
                <div class="products-filter">
                    <form method="get" class="filter-form">
                        <div class="filter-row">
                            <div class="search-container">
                                <input type="text" name="search" placeholder="Search products..." 
                                       value="<?php echo htmlspecialchars($search_query); ?>">
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            
                            <select name="status">
                                <option value="">All Statuses</option>
                                <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="sold" <?php echo $status_filter === 'sold' ? 'selected' : ''; ?>>Sold</option>
                                <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                            </select>
                            
                            <select name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['category']); ?>" 
                                        <?php echo $category_filter === $category['category'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucfirst($category['category'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>

                <div class="products-grid">
                    <?php if (empty($products)): ?>
                        <div class="no-products">
                            <p>No products found.</p>
                            <a href="add_product.php" class="btn btn-primary">Add Your First Product</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="../<?php echo htmlspecialchars($product['image'] ?? 'images/placeholder.png'); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <span class="product-status <?php echo htmlspecialchars($product['status']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($product['status'])); ?>
                                    </span>
                                </div>
                                <div class="product-details">
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="product-category">
                                        <?php echo htmlspecialchars(ucfirst($product['category'])); ?>
                                    </p>
                                    <div class="product-pricing">
                                        <span class="original-price">
                                            KSh <?php echo number_format($product['original_price'], 2); ?>
                                        </span>
                                        <span class="discounted-price">
                                            KSh <?php echo number_format($product['price'], 2); ?>
                                        </span>
                                    </div>
                                    <div class="product-meta">
                                        <span>
                                            <i class="fas fa-box"></i> 
                                            <?php echo $product['quantity'] . ' ' . htmlspecialchars($product['unit']); ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-calendar"></i> 
                                            Expires: <?php echo date('d M Y', strtotime($product['expiration_date'])); ?>
                                        </span>
                                    </div>
                                    <div class="product-actions">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-secondary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form action="../api/vendor/delete_product.php" method="POST" 
                                              class="delete-product-form" onsubmit="return confirmDelete();">
                                            <input type="hidden" name="product_id" 
                                                   value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; 
                                    echo !empty($search_query) ? '&search=' . urlencode($search_query) : '';
                                    echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : '';
                                    echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; 
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
        // Confirm product deletion
        function confirmDelete() {
            return confirm('Are you sure you want to delete this product? This action cannot be undone.');
        }

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