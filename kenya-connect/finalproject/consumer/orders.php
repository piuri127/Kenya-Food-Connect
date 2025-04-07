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
    error_log("Orders Page Error: " . $e->getMessage());
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
    <title>My Orders - Kenya Connect</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/consumer_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Additional styles for orders page */
        .orders-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .orders-filter {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .filter-group label {
            margin-right: 10px;
            font-weight: 500;
        }
        
        .filter-group select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .search-box {
            display: flex;
        }
        
        .search-box input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            width: 250px;
        }
        
        .search-box button {
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            padding: 8px 15px;
            cursor: pointer;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th, 
        .orders-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .orders-table th {
            background-color: #f9f9f9;
            font-weight: 600;
        }
        
        .orders-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .pending {
            background-color: #fff8e1;
            color: #f57c00;
        }
        
        .processing {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .completed {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .cancelled {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        .ready {
            background-color: #e0f7fa;
            color: #0097a7;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination a {
            padding: 8px 12px;
            margin: 0 4px;
            border: 1px solid #ddd;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .pagination a.active {
            background-color: #4caf50;
            color: white;
            border: 1px solid #4caf50;
        }
        
        .pagination a:hover:not(.active) {
            background-color: #f5f5f5;
        }
        
        .no-orders {
            text-align: center;
            padding: 40px 0;
            color: #666;
        }
        
        .no-orders i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .order-actions .btn {
            margin-right: 5px;
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
                <li><a href="orders.php" class="active">My Orders</a></li>
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
        <h1 class="page-title">My Orders</h1>
        
        <section class="orders-container">
            <div class="orders-filter">
                <div class="filter-group">
                    <label for="status-filter">Status:</label>
                    <select id="status-filter">
                        <option value="all">All Orders</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="ready">Ready for Pickup</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="time-filter">Time:</label>
                    <select id="time-filter">
                        <option value="all">All Time</option>
                        <option value="week">Last 7 Days</option>
                        <option value="month">Last 30 Days</option>
                        <option value="quarter">Last 3 Months</option>
                    </select>
                </div>
                
                <div class="search-box">
                    <input type="text" id="search-input" placeholder="Search by order number, vendor...">
                    <button id="search-button"><i class="fas fa-search"></i></button>
                </div>
            </div>
            
            <div id="orders-table-container">
                <div id="loading-indicator" class="loading">Loading orders...</div>
                <!-- Orders will be loaded here via JavaScript -->
            </div>
            
            <div id="pagination" class="pagination">
                <!-- Pagination links will be added here -->
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
        // Global state for orders
        let ordersState = {
            orders: [],
            filteredOrders: [],
            currentPage: 1,
            itemsPerPage: 10,
            statusFilter: 'all',
            timeFilter: 'all',
            searchTerm: ''
        };

        // Helper to format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return `${date.getDate()} ${months[date.getMonth()]}, ${date.getFullYear()}`;
        }

        // Helper to create status badge
        function getStatusBadge(status) {
            return `<span class="status-badge ${status.toLowerCase()}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
        }

        // Create order row HTML
        function createOrderRow(order) {
            return `
                <tr>
                    <td>${order.order_number}</td>
                    <td>${formatDate(order.created_at)}</td>
                    <td>${order.vendor_name}</td>
                    <td>${order.item_count} items</td>
                    <td>KSh ${parseFloat(order.total_amount).toFixed(2)}</td>
                    <td>${getStatusBadge(order.status)}</td>
                    <td class="order-actions">
                        <a href="order-details.php?id=${order.id}" class="btn btn-sm">View</a>
                        ${order.status === 'pending' ? 
                          `<button class="btn btn-sm btn-danger" onclick="cancelOrder(${order.id})">Cancel</button>` : ''}
                    </td>
                </tr>
            `;
        }

        // Function to filter orders
        function filterOrders() {
            const { orders, statusFilter, timeFilter, searchTerm } = ordersState;
            
            let filtered = [...orders];
            
            // Filter by status
            if (statusFilter !== 'all') {
                filtered = filtered.filter(order => order.status.toLowerCase() === statusFilter);
            }
            
            // Filter by time
            const now = new Date();
            if (timeFilter === 'week') {
                const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                filtered = filtered.filter(order => new Date(order.created_at) >= weekAgo);
            } else if (timeFilter === 'month') {
                const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
                filtered = filtered.filter(order => new Date(order.created_at) >= monthAgo);
            } else if (timeFilter === 'quarter') {
                const quarterAgo = new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000);
                filtered = filtered.filter(order => new Date(order.created_at) >= quarterAgo);
            }
            
            // Filter by search term
            if (searchTerm.trim() !== '') {
                const term = searchTerm.toLowerCase();
                filtered = filtered.filter(order => 
                    order.order_number.toLowerCase().includes(term) ||
                    order.vendor_name.toLowerCase().includes(term)
                );
            }
            
            ordersState.filteredOrders = filtered;
            ordersState.currentPage = 1; // Reset to first page after filtering
            renderOrders();
        }

        // Function to render orders table
        function renderOrders() {
            const { filteredOrders, currentPage, itemsPerPage } = ordersState;
            const tableContainer = document.getElementById('orders-table-container');
            
            // Calculate pagination
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const displayedOrders = filteredOrders.slice(startIndex, endIndex);
            const totalPages = Math.ceil(filteredOrders.length / itemsPerPage);
            
            // Clear loading indicator
            tableContainer.innerHTML = '';
            
            // If no orders found
            if (filteredOrders.length === 0) {
                tableContainer.innerHTML = `
                    <div class="no-orders">
                        <i class="fas fa-receipt"></i>
                        <h3>No Orders Found</h3>
                        <p>You don't have any orders matching the selected filters.</p>
                        <a href="search.php" class="btn btn-primary">Find Food</a>
                    </div>
                `;
                document.getElementById('pagination').innerHTML = '';
                return;
            }
            
            // Create the table
            let tableHTML = `
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            // Add order rows
            displayedOrders.forEach(order => {
                tableHTML += createOrderRow(order);
            });
            
            tableHTML += `
                    </tbody>
                </table>
            `;
            
            tableContainer.innerHTML = tableHTML;
            
            // Render pagination
            renderPagination(totalPages);
        }

        // Function to render pagination
        function renderPagination(totalPages) {
            const pagination = document.getElementById('pagination');
            
            if (totalPages <= 1) {
                pagination.innerHTML = '';
                return;
            }
            
            let paginationHTML = '';
            
            // Previous button
            paginationHTML += `
                <a href="#" onclick="changePage(${Math.max(1, ordersState.currentPage - 1)}); return false;"
                   ${ordersState.currentPage === 1 ? 'class="disabled"' : ''}>
                   &laquo; Prev
                </a>
            `;
            
            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                paginationHTML += `
                    <a href="#" onclick="changePage(${i}); return false;"
                       ${ordersState.currentPage === i ? 'class="active"' : ''}>
                       ${i}
                    </a>
                `;
            }
            
            // Next button
            paginationHTML += `
                <a href="#" onclick="changePage(${Math.min(totalPages, ordersState.currentPage + 1)}); return false;"
                   ${ordersState.currentPage === totalPages ? 'class="disabled"' : ''}>
                   Next &raquo;
                </a>
            `;
            
            pagination.innerHTML = paginationHTML;
        }

        // Function to change page
        function changePage(page) {
            ordersState.currentPage = page;
            renderOrders();
            window.scrollTo(0, 0);
        }

        // Function to cancel order
        function cancelOrder(orderId) {
            if (!confirm('Are you sure you want to cancel this order?')) {
                return;
            }
            
            fetch('../api/consumers/cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ order_id: orderId }),
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order cancelled successfully');
                    // Update order status in the state
                    ordersState.orders = ordersState.orders.map(order => {
                        if (order.id === orderId) {
                            return { ...order, status: 'cancelled' };
                        }
                        return order;
                    });
                    filterOrders(); // Re-apply filters and render
                } else {
                    alert(data.message || 'Could not cancel order');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error cancelling order. Please try again.');
            });
        }

        // Add event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Get cart count
            fetch('../api/consumers/get_cart_count.php', {
                method: 'GET',
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('.cart-count').textContent = data.count;
                }
            })
            .catch(error => console.error('Error fetching cart count:', error));
            
            // Fetch orders
            fetch('../api/consumers/get_orders.php', {
                method: 'GET',
                credentials: 'include'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Not authorized or server error');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    ordersState.orders = data.orders;
                    ordersState.filteredOrders = [...data.orders];
                    renderOrders();
                } else {
                    throw new Error(data.message || 'Error fetching orders');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const tableContainer = document.getElementById('orders-table-container');
                tableContainer.innerHTML = `
                    <div class="error-message">
                        <p>Error loading orders: ${error.message}</p>
                        <p><a href="dashboard.php">Return to Dashboard</a></p>
                    </div>
                `;
            });
            
            // Add event listeners for filters
            document.getElementById('status-filter').addEventListener('change', function() {
                ordersState.statusFilter = this.value;
                filterOrders();
            });
            
            document.getElementById('time-filter').addEventListener('change', function() {
                ordersState.timeFilter = this.value;
                filterOrders();
            });
            
            document.getElementById('search-button').addEventListener('click', function() {
                ordersState.searchTerm = document.getElementById('search-input').value;
                filterOrders();
            });
            
            document.getElementById('search-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    ordersState.searchTerm = this.value;
                    filterOrders();
                }
            });
        });
    </script>
</body>
</html>