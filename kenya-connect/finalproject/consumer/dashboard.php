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
} catch (Exception $e) {
    // Log error
    error_log("Dashboard Error: " . $e->getMessage());
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
    <title>Consumer Dashboard - Kenya Connect</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/consumer_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
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
        <div id="loading-indicator" class="loading">Loading dashboard data...</div>
        
        <section class="welcome-section" style="display: none;" id="welcome-section">
            <div class="welcome-content">
                <h1>Welcome back, <span id="welcome-name"><?php echo htmlspecialchars($consumer['name']); ?></span>!</h1>
                <p>Find surplus food from vendors near <?php echo htmlspecialchars($consumer['area'] . ', ' . $consumer['city']); ?>.</p>
                <a href="search.php" class="btn btn-primary">Find Food Now</a>
            </div>
        </section>

        <section class="dashboard-section" style="display: none;" id="nearby-products-section">
            <h2>Nearby Available Food</h2>
            <div id="products-container" class="product-grid">
                <!-- Products will be loaded here via JavaScript -->
            </div>
            <div class="view-all-link">
                <a href="search.php" class="btn btn-secondary">View All Available Food</a>
            </div>
        </section>

        <section class="dashboard-section" style="display: none;" id="recent-orders-section">
            <h2>Recent Orders</h2>
            <div id="orders-container" class="orders-table-container">
                <!-- Orders will be loaded here via JavaScript -->
            </div>
            <div class="view-all-link">
                <a href="orders.php" class="btn btn-secondary">View All Orders</a>
            </div>
        </section>
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
        // Helper function to create product card HTML
        function createProductCard(product) {
            // Calculate days left until expiration
            function calculateDaysLeft(expiryDate) {
                const today = new Date();
                const expiry = new Date(expiryDate);
                const diffTime = expiry - today;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                return diffDays > 0 ? diffDays : 0;
            }

            // Default placeholder if no image
            const productImage = product.image || '/api/placeholder/300/200';
            
            return `
                <div class="product-card">
                    <div class="product-image">
                        <img src="${productImage}" alt="${product.name}">
                        <span class="listing-type ${product.listing_type === 'donate' ? 'donation' : 'sale'}">
                            ${product.listing_type === 'donate' ? 'Free' : 'For Sale'}
                        </span>
                    </div>
                    <div class="product-details">
                        <h3>${product.name}</h3>
                        
                        ${product.listing_type === 'sell' 
                            ? `<p class="product-price">KSh ${parseFloat(product.price).toFixed(2)}</p>`
                            : `<p class="product-price">Free (Donation)</p>`
                        }
                        
                        <p class="product-location">
                            <i class="fas fa-map-marker-alt"></i> 
                            ${product.area}, ${product.city}
                        </p>
                        
                        <p class="product-expiry">
                            <i class="fas fa-clock"></i>
                            Expires in ${calculateDaysLeft(product.expiration_date)} days
                        </p>
                        
                        <div class="product-actions">
                            <a href="product.html?id=${product.id}" class="btn btn-sm">View Details</a>
                            <button onclick="addToCart(${product.id})" class="btn btn-sm btn-primary">
                                ${product.listing_type === 'donate' ? 'Reserve' : 'Add to Cart'}
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        // Helper function to create order row HTML
        function createOrderRow(order) {
            // Helper to get month name
            function getMonthName(monthIndex) {
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                return months[monthIndex];
            }

            // Format date
            const date = new Date(order.created_at);
            const formattedDate = `${date.getDate()} ${getMonthName(date.getMonth())}, ${date.getFullYear()}`;
            
            return `
                <tr>
                    <td>${order.order_number}</td>
                    <td>${formattedDate}</td>
                    <td>${order.item_count} items</td>
                    <td>KSh ${parseFloat(order.total_amount).toFixed(2)}</td>
                    <td>
                        <span class="status-badge ${order.status.toLowerCase()}">
                            ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                        </span>
                    </td>
                    <td>
                        <a href="order-details.html?id=${order.id}" class="btn btn-sm">View</a>
                    </td>
                </tr>
            `;
        }

        // Function to add item to cart
        function addToCart(productId) {
            fetch('../api/consumers/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ product_id: productId, quantity: 1 }),
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                // Update cart count
                if (data.cart_count) {
                    document.querySelector('.cart-count').textContent = data.cart_count;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding to cart. Please try again.');
            });
        }

        // Fetch dashboard data
        document.addEventListener('DOMContentLoaded', function() {
            fetch('../api/consumers/get_dashboard_data.php', {
                method: 'GET',
                credentials: 'include'
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Not authorized or server error');
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);

                // Hide loading indicator
                document.getElementById('loading-indicator').style.display = 'none';
                
                // Show welcome section
                document.getElementById('welcome-section').style.display = 'block';
                
                // Display nearby products
                if (data.nearby_products && data.nearby_products.length > 0) {
                    document.getElementById('nearby-products-section').style.display = 'block';
                    const productsContainer = document.getElementById('products-container');
                    productsContainer.innerHTML = ''; // Clear any existing content
                    
                    data.nearby_products.forEach(product => {
                        productsContainer.innerHTML += createProductCard(product);
                    });
                }
                
                // Display recent orders
                if (data.recent_orders && data.recent_orders.length > 0) {
                    document.getElementById('recent-orders-section').style.display = 'block';
                    const ordersContainer = document.getElementById('orders-container');
                    
                    ordersContainer.innerHTML = `
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="orders-tbody">
                            </tbody>
                        </table>
                    `;
                    
                    const ordersTbody = document.getElementById('orders-tbody');
                    
                    data.recent_orders.forEach(order => {
                        ordersTbody.innerHTML += createOrderRow(order);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const loadingIndicator = document.getElementById('loading-indicator');
                loadingIndicator.innerHTML = 'Error loading dashboard data. <a href="login.php">Please log in again</a>.';
                loadingIndicator.classList.add('error');
            });
        });
    </script>
</body>
</html>