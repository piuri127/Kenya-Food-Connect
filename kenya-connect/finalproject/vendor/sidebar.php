<?php
// Ensure the sidebar is only accessed after authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'vendor') {
    header("Location: ../index.html");
    exit();
}

// Fetch vendor details if needed
$vendor_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT business_name FROM vendors WHERE id = ?");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();
$vendor = $result->fetch_assoc();
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h2><?php echo htmlspecialchars($vendor['business_name']); ?></h2>
        <p>Vendor Dashboard</p>
    </div>
    
    <nav class="sidebar-menu">
        <ul>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <i class="fas fa-home"></i> 
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'add_product.php') ? 'active' : ''; ?>">
                <a href="add_product.php">
                    <i class="fas fa-plus-circle"></i> 
                    <span>Add Product</span>
                </a>
            </li>
            
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>">
                <a href="products.php">
                    <i class="fas fa-box"></i> 
                    <span>My Products</span>
                </a>
            </li>
            
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php') ? 'active' : ''; ?>">
                <a href="orders.php">
                    <i class="fas fa-shopping-cart"></i> 
                    <span>Orders</span>
                </a>
            </li>
            
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
                <a href="profile.php">
                    <i class="fas fa-user"></i> 
                    <span>Profile</span>
                </a>
            </li>
            
            <li>
                <a href="../api/auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i> 
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <p>&copy; <?php echo date('Y'); ?> Kenya Connect</p>
    </div>
</div>

<style>
.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background-color: #2c3e50;
    color: white;
    padding: 20px 0;
    transition: width 0.3s ease;
}

.sidebar-header {
    text-align: center;
    padding: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-menu ul {
    list-style: none;
    padding: 0;
}

.sidebar-menu li {
    padding: 10px 20px;
    transition: background-color 0.3s ease;
}

.sidebar-menu li:hover {
    background-color: rgba(255,255,255,0.1);
}

.sidebar-menu li.active {
    background-color: rgba(255,255,255,0.2);
}

.sidebar-menu a {
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
}

.sidebar-menu i {
    margin-right: 10px;
}

.sidebar-footer {
    position: absolute;
    bottom: 0;
    width: 100%;
    text-align: center;
    padding: 10px;
    background-color: rgba(0,0,0,0.2);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Optional: Mobile sidebar toggle
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
        });
    }
});
</script>