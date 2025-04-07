<?php
// Include session check
require_once '../php/session_check.php';

// Check if user is logged in and is a consumer
check_login();
check_consumer();

// Include database connection
require_once 'C:/Users/Purity/Desktop/php/htdocs/kenya-connect/finalproject/php/config.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    // Invalid product ID, redirect to search page
    header("Location: search.php");
    exit();
}

// Get product data
$stmt = $conn->prepare("
    SELECT p.*, v.business_name, v.city, v.area, v.id as vendor_id
    FROM products p 
    JOIN vendors v ON p.vendor_id = v.id 
    WHERE p.id = ? AND p.status = 'available'
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Product not found or not available, redirect to search page
    header("Location: search.php");
    exit();
}

$product = $result->fetch_assoc();

// Get consumer data
$consumer_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM consumers WHERE id = ?");
$stmt->bind_param("i", $consumer_id);
$stmt->execute();
$consumer_result = $stmt->get_result();
$consumer = $consumer_result->fetch_assoc();

// Get similar products from the same vendor
$stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE vendor_id = ? AND id != ? AND status = 'available' 
    ORDER BY created_at DESC 
    LIMIT 3
");
$stmt->bind_param("ii", $product['vendor_id'], $product_id);
$stmt->execute();
$similar_result = $stmt->get_result();
$similar_products = [];
while ($row = $similar_result->fetch_assoc()) {
    $similar_products[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Kenya Connect</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="consumer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="../index.html">
                    <h1>Kenya Connect</h1>
                </a>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="search.php" class="active">Find Food</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> <span class="cart-count">0</span></a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($consumer['name']); ?></a>
                    <ul class="dropdown-menu">
                        <li><a href="profile.php">Profile</a></li>
                        <li><a href="../php/logout.php">Logout</a></li>
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

    <main class="content-container">
        <div class="breadcrumbs">
            <a href="dashboard.php">Home</a> &gt;
            <a href="search.php">Find Food</a> &gt;
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>
        
        <section class="product-detail-section">
            <div class="product-detail-container">
                <div class="product-gallery">
                    <?php if (!empty($product['image'])): ?>
                        <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-main-image">
                    <?php else: ?>
                        <img src="/api/placeholder/500/400" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-main-image">
                    <?php endif; ?>
                    
                    <div class="product-badges">
                        <span class="badge <?php echo $product['listing_type'] == 'donate' ? 'donation' : 'sale'; ?>">
                            <?php echo $product['listing_type'] == 'donate' ? 'Free' : 'For Sale'; ?>
                        </span>
                        
                        <?php 
                            $expiry_date = new DateTime($product['expiration_date']);
                            $today = new DateTime();
                            $days_left = $today->diff($expiry_date)->days;
                            
                            if ($days_left <= 1) {
                                echo '<span class="badge urgent">Expires Tomorrow</span>';
                            } elseif ($days_left <= 3) {
                                echo '<span class="badge warning">Expires Soon</span>';
                            }
                        ?>
                    </div>
                </div>
                
                <div class="product-info">
                    <h1 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <?php if ($product['listing_type'] == 'sell'): ?>
                        <p class="product-price">KSh <?php echo number_format($product['price'], 2); ?> per <?php echo htmlspecialchars($product['unit']); ?></p>
                    <?php else: ?>
                        <p class="product-price free">Free (Donation)</p>
                    <?php endif; ?>
                    
                    <div class="product-meta">
                        <p class="product-vendor">
                            <i class="fas fa-store"></i> 
                            <?php echo htmlspecialchars($product['business_name']); ?>
                        </p>
                        
                        <p class="product-location">
                            <i class="fas fa-map-marker-alt"></i> 
                            <?php echo htmlspecialchars($product['area'] . ', ' . $product['city']); ?>
                        </p>
                        
                        <p class="product-expiry">
                            <i class="fas fa-calendar-alt"></i>
                            Expires on <?php echo date('d M, Y', strtotime($product['expiration_date'])); ?> 
                            (<?php echo $days_left; ?> days left)
                        </p>
                        
                        <p class="product-quantity">
                            <i class="fas fa-weight"></i>
                            Available: <?php echo $product['quantity'] . ' ' . $product['unit']; ?>
                        </p>
                    </div>
                    
                    <div class="product-description">
                        <h3>Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                    
                    <form action="../php/cart_process.php" method="POST" class="add-to-cart-form">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        
                        <div class="quantity-selection">
                            <label for="quantity">Quantity:</label>
                            <div class="quantity-control">
                                <button type="button" class="quantity-btn decrement">-</button>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['quantity']; ?>">
                                <button type="button" class="quantity-btn increment">+</button>
                            </div>
                        </div>
                        
                        <div class="product-total">
                            <?php if ($product['listing_type'] == 'sell'): ?>
                                <p>Total: <span class="product-total-price" data-unit-price="<?php echo $product['price']; ?>">KSh <?php echo number_format($product['price'], 2); ?></span></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-actions">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <?php echo $product['listing_type'] == 'donate' ? 'Reserve Now' : 'Add to Cart'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
        
        <?php if (count($similar_products) > 0): ?>
        <section class="similar-products-section">
            <h2>More from this Vendor</h2>
            
            <div class="similar-products">
                <?php foreach ($similar_products as $similar): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if (!empty($similar['image'])): ?>
                            <img src="../uploads/products/<?php echo htmlspecialchars($similar['image']); ?>" alt="<?php echo htmlspecialchars($similar['name']); ?>">
                        <?php else: ?>
                            <img src="/api/placeholder/200/150" alt="<?php echo htmlspecialchars($similar['name']); ?>">
                        <?php endif; ?>
                        
                        <span class="listing-type <?php echo $similar['listing_type'] == 'donate' ? 'donation' : 'sale'; ?>">
                            <?php echo $similar['listing_type'] == 'donate' ? 'Free' : 'For Sale'; ?>
                        </span>
                    </div>
                    <div class="product-details">
                        <h3><?php echo htmlspecialchars($similar['name']); ?></h3>
                        
                        <?php if ($similar['listing_type'] == 'sell'): ?>
                            <p class="product-price">KSh <?php echo number_format($similar['price'], 2); ?></p>
                        <?php else: ?>
                            <p class="product-price">Free (Donation)</p>
                        <?php endif; ?>
                        
                        <p class="product-expiry">
                            <i class="fas fa-clock"></i>
                            <?php 
                                $expiry_date = new DateTime($similar['expiration_date']);
                                $today = new DateTime();
                                $days_left = $today->diff($expiry_date)->days;
                                echo "Expires in " . $days_left . " days";
                            ?>
                        </p>
                        
                        <div class="product-actions">
                            <a href="product.php?id=<?php echo $similar['id']; ?>" class="btn btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
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
        // Quantity control for this specific page
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInput = document.getElementById('quantity');
            const decrementBtn = document.querySelector('.decrement');
            const incrementBtn = document.querySelector('.increment');
            const totalPriceElement = document.querySelector('.product-total-price');
            
            if (quantityInput && decrementBtn && incrementBtn) {
                decrementBtn.addEventListener('click', function() {
                    let currentValue = parseInt(quantityInput.value);
                    if (currentValue > 1) {
                        quantityInput.value = currentValue - 1;
                        updateTotalPrice();
                    }
                });
                
                incrementBtn.addEventListener('click', function() {
                    let currentValue = parseInt(quantityInput.value);
                    let maxValue = parseInt(quantityInput.getAttribute('max'));
                    if (currentValue < maxValue) {
                        quantityInput.value = currentValue + 1;
                        updateTotalPrice();
                    }
                });
                
                quantityInput.addEventListener('change', function() {
                    let currentValue = parseInt(quantityInput.value);
                    let maxValue = parseInt(quantityInput.getAttribute('max'));
                    
                    if (currentValue < 1) {
                        quantityInput.value = 1;
                    } else if (currentValue > maxValue) {
                        quantityInput.value = maxValue;
                    }
                    
                    updateTotalPrice();
                });
                
                function updateTotalPrice() {
                    if (totalPriceElement) {
                        const unitPrice = parseFloat(totalPriceElement.getAttribute('data-unit-price'));
                        const quantity = parseInt(quantityInput.value);
                        const totalPrice = unitPrice * quantity;
                        totalPriceElement.textContent = 'KSh ' + totalPrice.toFixed(2);
                    }
                }
            }
        });
    </script>
</body>
</html>