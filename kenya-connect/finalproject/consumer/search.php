<?php
// Start session
session_start();

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'consumer') {
    // Redirect to login if not authenticated
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'C:/Users/Purity/Desktop/php/htdocs/kenya-connect/finalproject/php/config.php';

// Get consumer's city and area
$consumer_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT city, area FROM consumers WHERE id = ?");
$stmt->bind_param("i", $consumer_id);
$stmt->execute();
$result = $stmt->get_result();
$consumer = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Food - Kenya Connect</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/search.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="search-page">
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
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-user-circle"></i> 
                        <span id="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="profile.php">Profile</a></li>
                        <li><a href="../api/auth/logout.php" id="logout-link">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </header>

    <main class="search-container">
        <div class="search-header">
            <h1>Find Surplus Food Near You</h1>
            <p>Discover affordable and donated food in <?php echo htmlspecialchars($consumer['area'] . ', ' . $consumer['city']); ?></p>
        </div>

        <div class="search-filters">
            <div class="filter-row">
                <div class="search-input-container">
                    <input type="text" id="search-input" placeholder="Search food items...">
                    <button id="search-btn"><i class="fas fa-search"></i></button>
                </div>

                <div class="filter-selects">
                    <select id="category-filter">
                        <option value="">All Categories</option>
                        <option value="fruits">Fruits</option>
                        <option value="vegetables">Vegetables</option>
                        <option value="dairy">Dairy</option>
                        <option value="bakery">Bakery</option>
                        <option value="prepared-meals">Prepared Meals</option>
                        <option value="dry-goods">Dry Goods</option>
                        <option value="beverages">Beverages</option>
                    </select>

                    <select id="listing-type-filter">
                        <option value="">All Listings</option>
                        <option value="sell">For Sale</option>
                        <option value="donate">Free Donations</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="products-container" class="products-grid">
            <!-- Products will be dynamically loaded here -->
        </div>

        <div id="pagination" class="pagination">
            <!-- Pagination controls will be added dynamically -->
        </div>
    </main>

    <footer>
        <p>&copy; 2025 Kenya Connect. All rights reserved.</p>
    </footer>

    <script>
        // Fetch products based on filters
        function fetchProducts(page = 1) {
            const searchQuery = document.getElementById('search-input').value;
            const categoryFilter = document.getElementById('category-filter').value;
            const listingTypeFilter = document.getElementById('listing-type-filter').value;

            const queryParams = new URLSearchParams({
                search: searchQuery,
                category: categoryFilter,
                listing_type: listingTypeFilter,
                page: page
            });

            fetch(`../api/consumers/search_products.php?${queryParams}`, {
                method: 'GET',
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                const productsContainer = document.getElementById('products-container');
                const paginationContainer = document.getElementById('pagination');

                // Clear previous products
                productsContainer.innerHTML = '';

                // Render products
                data.products.forEach(product => {
                    const productCard = createProductCard(product);
                    productsContainer.innerHTML += productCard;
                });

                // Render pagination
                renderPagination(data.total_pages, page);
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('products-container').innerHTML = 
                    '<p>Error loading products. Please try again.</p>';
            });
        }

        // Create product card HTML
        function createProductCard(product) {
            return `
                <div class="product-card">
                    <div class="product-image">
                        <img src="${product.image || '../images/placeholder.png'}" alt="${product.name}">
                        <span class="listing-type ${product.listing_type === 'donate' ? 'donation' : 'sale'}">
                            ${product.listing_type === 'donate' ? 'Free' : 'For Sale'}
                        </span>
                    </div>
                    <div class="product-details">
                        <h3>${product.name}</h3>
                        <p class="product-price">
                            ${product.listing_type === 'sell' 
                                ? `KSh ${parseFloat(product.price).toFixed(2)}` 
                                : 'Free Donation'}
                        </p>
                        <p class="product-vendor">
                            <i class="fas fa-store"></i> ${product.business_name}
                        </p>
                        <p class="product-location">
                            <i class="fas fa-map-marker-alt"></i> 
                            ${product.area}, ${product.city}
                        </p>
                        <div class="product-actions">
                            <button onclick="addToCart(${product.id})" class="btn btn-primary">
                                ${product.listing_type === 'donate' ? 'Reserve' : 'Add to Cart'}
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        // Render pagination controls
        function renderPagination(totalPages, currentPage) {
            const paginationContainer = document.getElementById('pagination');
            paginationContainer.innerHTML = '';

            for (let i = 1; i <= totalPages; i++) {
                const pageButton = document.createElement('button');
                pageButton.textContent = i;
                pageButton.classList.add('page-btn');
                if (i === currentPage) {
                    pageButton.classList.add('active');
                }
                pageButton.addEventListener('click', () => fetchProducts(i));
                paginationContainer.appendChild(pageButton);
            }
        }

        // Add to cart function
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

        // Event listeners
        document.getElementById('search-btn').addEventListener('click', () => fetchProducts());
        document.getElementById('category-filter').addEventListener('change', () => fetchProducts());
        document.getElementById('listing-type-filter').addEventListener('change', () => fetchProducts());

        // Initial load
        fetchProducts();
    </script>
</body>
</html>