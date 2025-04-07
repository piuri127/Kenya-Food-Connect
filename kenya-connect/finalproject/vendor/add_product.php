<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Check if user is logged in and is a vendor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'vendor') {
    // Redirect to login page
    header("Location: ../vendor/login.php");
    exit();
}

// Include database connection
require_once 'C:/Users/Purity/Desktop/php/htdocs/kenya-connect/finalproject/php/config.php';

// Get vendor information
$vendor_id = $_SESSION['user_id'];
$vendor_name = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Vendor Dashboard - Kenya Connect</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/vendor_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="dashboard-page">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="dashboard-content">
            <!-- Top Navigation -->
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
                            <a href="#" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main Dashboard Content -->
            <div class="dashboard-main">
                <div class="page-header">
                    <h1>Add New Product</h1>
                    
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
                
                <div class="content-card">
                    <div class="card-header">
                        <h2>Product Information</h2>
                        <p>Fill in the details to add a new surplus food product</p>
                    </div>
                    
                    <div class="card-body">
                        <form id="add-product-form" class="form-layout" action="../api/vendor/product_process.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="vendor_id" value="<?php echo $vendor_id; ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="product-name">Product Name <span class="required">*</span></label>
                                    <input type="text" id="product-name" name="product_name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="category">Category <span class="required">*</span></label>
                                    <select id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="fruits">Fruits</option>
                                        <option value="vegetables">Vegetables</option>
                                        <option value="dairy">Dairy</option>
                                        <option value="bakery">Bakery</option>
                                        <option value="prepared-meals">Prepared Meals</option>
                                        <option value="dry-goods">Dry Goods</option>
                                        <option value="beverages">Beverages</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="quantity">Quantity Available <span class="required">*</span></label>
                                    <input type="number" id="quantity" name="quantity" min="1" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="unit">Unit <span class="required">*</span></label>
                                    <select id="unit" name="unit" required>
                                        <option value="">Select Unit</option>
                                        <option value="kg">Kilogram (kg)</option>
                                        <option value="g">Gram (g)</option>
                                        <option value="piece">Piece</option>
                                        <option value="dozen">Dozen</option>
                                        <option value="box">Box</option>
                                        <option value="liter">Liter (L)</option>
                                        <option value="ml">Milliliter (ml)</option>
                                        <option value="package">Package</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="original-price">Original Price (KSh) <span class="required">*</span></label>
                                    <input type="number" id="original-price" name="original_price" min="0" step="0.01" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="discounted-price">Discounted Price (KSh) <span class="required">*</span></label>
                                    <input type="number" id="discounted-price" name="discounted_price" min="0" step="0.01" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="expiry-date">Expiry Date <span class="required">*</span></label>
                                    <input type="date" id="expiry-date" name="expiry_date" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="availability">Availability</label>
                                    <select id="availability" name="availability">
                                        <option value="available">Available Now</option>
                                        <option value="future">Available Later</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description <span class="required">*</span></label>
                                <textarea id="description" name="description" rows="4" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="storage-instructions">Storage Instructions</label>
                                <textarea id="storage-instructions" name="storage_instructions" rows="2"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Product Image</label>
                                <div class="image-upload-container" id="product-image-container">
                                    <div class="image-preview">
                                        <img id="product-image-preview" src="../images/placeholder.png" alt="Product Image Preview">
                                    </div>
                                    <div class="upload-controls">
                                        <label for="product-image" class="upload-btn">
                                            <i class="fas fa-upload"></i> Choose Image
                                        </label>
                                        <input type="file" id="product-image" name="product_image" class="file-input" data-preview="#product-image-preview" data-container="#product-image-container" accept="image/*">
                                        <p class="help-text">Recommended size: 800x600px, Max 2MB</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="window.location.href='products.php'">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="dashboard-footer">
                <p>&copy; <?php echo date('Y'); ?> Kenya Connect. All rights reserved.</p>
            </footer>
        </main>
    </div>
    
    <script src="../js/script.js"></script>
    <script>
        // Set minimum expiry date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('expiry-date').setAttribute('min', today);
        
        // Validate discounted price is less than original price
        document.getElementById('add-product-form').addEventListener('submit', function(e) {
            const originalPrice = parseFloat(document.getElementById('original-price').value);
            const discountedPrice = parseFloat(document.getElementById('discounted-price').value);
            
            if (discountedPrice >= originalPrice) {
                e.preventDefault();
                alert('Discounted price must be less than the original price');
                return;
            }
        });
    </script>
</body>
</html>