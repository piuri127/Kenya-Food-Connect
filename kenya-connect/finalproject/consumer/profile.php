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

$consumer_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get form data
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $city = isset($_POST['city']) ? trim($_POST['city']) : '';
        $area = isset($_POST['area']) ? trim($_POST['area']) : '';
        $preferences = isset($_POST['preferences']) ? trim($_POST['preferences']) : '';
        
        // Validate required fields
        if (empty($name) || empty($email)) {
            throw new Exception("Name and email are required");
        }
        
        // Check if the email already exists (but not the user's own email)
        $check_email_query = "SELECT id FROM consumers WHERE email = ? AND id != ?";
        $check_stmt = $conn->prepare($check_email_query);
        $check_stmt->bind_param("si", $email, $consumer_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Email already exists. Please use a different email.");
        }
        
        // Update consumer profile
        $update_query = "
            UPDATE consumers 
            SET name = ?, email = ?, phone = ?, city = ?, area = ?, preferences = ?, updated_at = NOW()
            WHERE id = ?
        ";
        
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssssssi", $name, $email, $phone, $city, $area, $preferences, $consumer_id);
        $result = $update_stmt->execute();
        
        if (!$result) {
            throw new Exception("Failed to update profile");
        }
        
        // Check if password change was requested
        if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match");
            }
            
            // Verify current password
            $password_query = "SELECT password FROM consumers WHERE id = ?";
            $password_stmt = $conn->prepare($password_query);
            $password_stmt->bind_param("i", $consumer_id);
            $password_stmt->execute();
            $password_result = $password_stmt->get_result();
            $user = $password_result->fetch_assoc();
            
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }
            
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $update_password_query = "UPDATE consumers SET password = ? WHERE id = ?";
            $update_password_stmt = $conn->prepare($update_password_query);
            $update_password_stmt->bind_param("si", $hashed_password, $consumer_id);
            $password_result = $update_password_stmt->execute();
            
            if (!$password_result) {
                throw new Exception("Failed to update password");
            }
        }
        
        // Update session data
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        
        $success_message = "Profile updated successfully";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch consumer details
try {
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
    error_log("Profile Error: " . $e->getMessage());
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
    <title>My Profile - Kenya Connect</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/consumer_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .profile-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .profile-header {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .profile-header h1 {
            margin-bottom: 5px;
        }
        
        .profile-header p {
            color: #666;
        }
        
        .profile-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .profile-content {
                grid-template-columns: 1fr;
            }
        }
        
        .sidebar {
            background-color: #f5f5f5;
            border-radius: 8px;
            padding: 20px;
        }
        
        .profile-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .profile-menu li {
            margin-bottom: 10px;
        }
        
        .profile-menu a {
            display: flex;
            align-items: center;
            padding: 10px;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .profile-menu a:hover, .profile-menu a.active {
            background-color: #e0e0e0;
        }
        
        .profile-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .profile-details {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
        }
        
        .profile-section {
            margin-bottom: 30px;
        }
        
        .profile-section h2 {
            margin-top: 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .profile-form-group {
            margin-bottom: 20px;
        }
        
        .profile-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .profile-form-group input, 
        .profile-form-group textarea,
        .profile-form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .profile-form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .profile-form-group {
            flex: 1;
        }
        
        .update-button {
            padding: 10px 20px;
            font-size: 1rem;
            margin-top: 10px;
        }
        
        .reset-password-section {
            margin-top: 40px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            border-color: #4caf50;
            color: #2e7d32;
        }
        
        .alert-error {
            background-color: #ffebee;
            border-color: #f44336;
            color: #c62828;
        }
        
        .profile-stat {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e8f5e9;
            color: #4caf50;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
        }
        
        .stat-info h3 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .stat-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
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
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> <span class="cart-count">0</span></a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-user-circle"></i> 
                        <span id="user-name"><?php echo htmlspecialchars($consumer['name']); ?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="profile.php" class="active">Profile</a></li>
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
        <div class="profile-container">
            <div class="profile-header">
                <h1>My Profile</h1>
                <p>Manage your account information and preferences</p>
            </div>
            
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <div class="profile-content">
                <div class="sidebar">
                    <ul class="profile-menu">
                        <li><a href="#personal-info" class="active"><i class="fas fa-user"></i> Personal Information</a></li>
                        <li><a href="#password"><i class="fas fa-lock"></i> Change Password</a></li>
                        <li><a href="#preferences"><i class="fas fa-heart"></i> Food Preferences</a></li>
                        <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Order History</a></li>
                    </ul>
                    
                    <?php
                    // Get order stats
                    $stats_query = "SELECT COUNT(*) as total_orders FROM orders WHERE consumer_id = ?";
                    $stats_stmt = $conn->prepare($stats_query);
                    $stats_stmt->bind_param("i", $consumer_id);
                    $stats_stmt->execute();
                    $stats_result = $stats_stmt->get_result();
                    $stats = $stats_result->fetch_assoc();
                    
                    // Get saved food
                    $saved_query = "SELECT COUNT(*) as saved_items FROM cart_items WHERE consumer_id = ?";
                    $saved_stmt = $conn->prepare($saved_query);
                    $saved_stmt->bind_param("i", $consumer_id);
                    $saved_stmt->execute();
                    $saved_result = $saved_stmt->get_result();
                    $saved = $saved_result->fetch_assoc();
                    ?>
                    
                    <div class="profile-stats">
                        <div class="profile-stat">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_orders']; ?></h3>
                                <p>Total Orders</p>
                            </div>
                        </div>
                        
                        <div class="profile-stat">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $saved['saved_items']; ?></h3>
                                <p>Items in Cart</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="profile-details">
                    <form method="POST" action="">
                        <div class="profile-section" id="personal-info">
                            <h2>Personal Information</h2>
                            
                            <div class="profile-form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($consumer['name']); ?>" required>
                            </div>
                            
                            <div class="profile-form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($consumer['email']); ?>" required>
                            </div>
                            
                            <div class="profile-form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($consumer['phone']); ?>">
                            </div>
                            
                            <div class="form-row">
                                <div class="profile-form-group">
                                    <label for="city">City</label>
                                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($consumer['city']); ?>">
                                </div>
                                
                                <div class="profile-form-group">
                                    <label for="area">Area/Neighborhood</label>
                                    <input type="text" id="area" name="area" value="<?php echo htmlspecialchars($consumer['area']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="profile-section" id="preferences">
                            <h2>Food Preferences</h2>
                            
                            <div class="profile-form-group">
                                <label for="preferences">Food Preferences (e.g., vegetarian, allergies, etc.)</label>
                                <textarea id="preferences" name="preferences"><?php echo htmlspecialchars($consumer['preferences']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="profile-section reset-password-section" id="password">
                            <h2>Change Password</h2>
                            <p>Leave blank if you don't want to change your password</p>
                            
                            <div class="profile-form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password">
                            </div>
                            
                            <div class="profile-form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password">
                            </div>
                            
                            <div class="profile-form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary update-button">Update Profile</button>
                    </form>
                </div>
            </div>
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
        // Get cart count when page loads
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
            
            // Menu active state
            const menuLinks = document.querySelectorAll('.profile-menu a');
            menuLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    menuLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>