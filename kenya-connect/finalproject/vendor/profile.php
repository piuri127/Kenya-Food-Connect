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

// Fetch vendor details
$vendor_query = "SELECT * FROM vendors WHERE id = ?";
$vendor_stmt = $conn->prepare($vendor_query);
$vendor_stmt->bind_param("i", $vendor_id);
$vendor_stmt->execute();
$vendor_result = $vendor_stmt->get_result();
$vendor = $vendor_result->fetch_assoc();

// Handle profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Validate and sanitize input
    $name = trim($_POST['name']);
    $business_name = trim($_POST['business_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $county = trim($_POST['county']);
    $description = trim($_POST['description']);
    
    // Basic validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    // Check if email already exists (excluding current vendor)
    $email_check_query = "SELECT id FROM vendors WHERE email = ? AND id != ?";
    $email_check_stmt = $conn->prepare($email_check_query);
    $email_check_stmt->bind_param("si", $email, $vendor_id);
    $email_check_stmt->execute();
    $email_check_result = $email_check_stmt->get_result();
    
    if ($email_check_result->num_rows > 0) {
        $errors[] = "Email is already in use by another vendor";
    }
    
    // Process profile image if uploaded
    $profile_image = isset($vendor['profile_image']) ? $vendor['profile_image'] : ''; // Default to empty if not set
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
            $errors[] = "Invalid image format. Only JPEG, PNG, and GIF are allowed";
        } elseif ($_FILES['profile_image']['size'] > $max_size) {
            $errors[] = "Image size too large. Maximum size is 2MB";
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = '../uploads/vendors/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'vendor_' . $vendor_id . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                // Delete previous profile image if exists
                if (!empty($profile_image) && file_exists('../' . $profile_image)) {
                    unlink('../' . $profile_image);
                }
                
                $profile_image = 'uploads/vendors/' . $new_filename;
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        // Check which columns exist in the vendors table
        $table_info_query = "DESCRIBE vendors";
        $table_info_stmt = $conn->query($table_info_query);
        $columns = [];
        
        while ($row = $table_info_stmt->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // Build the update query based on existing columns
        $update_fields = [];
        $params = [];
        $param_types = "";
        
        if (in_array('name', $columns)) {
            $update_fields[] = "name = ?";
            $params[] = $name;
            $param_types .= "s";
        }
        
        if (in_array('business_name', $columns)) {
            $update_fields[] = "business_name = ?";
            $params[] = $business_name;
            $param_types .= "s";
        } elseif (in_array('business-name', $columns)) {
            $update_fields[] = "`business-name` = ?";
            $params[] = $business_name;
            $param_types .= "s";
        }
        
        if (in_array('email', $columns)) {
            $update_fields[] = "email = ?";
            $params[] = $email;
            $param_types .= "s";
        }
        
        if (in_array('phone', $columns)) {
            $update_fields[] = "phone = ?";
            $params[] = $phone;
            $param_types .= "s";
        }
        
        if (in_array('address', $columns)) {
            $update_fields[] = "address = ?";
            $params[] = $address;
            $param_types .= "s";
        }
        
        if (in_array('city', $columns)) {
            $update_fields[] = "city = ?";
            $params[] = $city;
            $param_types .= "s";
        }
        
        if (in_array('county', $columns)) {
            $update_fields[] = "county = ?";
            $params[] = $county;
            $param_types .= "s";
        } elseif (in_array('area', $columns)) {
            $update_fields[] = "area = ?";
            $params[] = $county;
            $param_types .= "s";
        }
        
        if (in_array('description', $columns)) {
            $update_fields[] = "description = ?";
            $params[] = $description;
            $param_types .= "s";
        } elseif (in_array('business_description', $columns)) {
            $update_fields[] = "business_description = ?";
            $params[] = $description;
            $param_types .= "s";
        } elseif (in_array('business-description', $columns)) {
            $update_fields[] = "`business-description` = ?";
            $params[] = $description;
            $param_types .= "s";
        }
        
        if (in_array('profile_image', $columns)) {
            $update_fields[] = "profile_image = ?";
            $params[] = $profile_image;
            $param_types .= "s";
        }
        
        // Add updated_at if it exists
        if (in_array('updated_at', $columns)) {
            $update_fields[] = "updated_at = NOW()";
        }
        
        // Add vendor_id to params
        $params[] = $vendor_id;
        $param_types .= "i";
        
        // Create update query
        $update_query = "UPDATE vendors SET " . implode(", ", $update_fields) . " WHERE id = ?";
                        
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param($param_types, ...$params);
        
        if ($update_stmt->execute()) {
            // Update session data
            $_SESSION['name'] = $name;
            
            // Fetch updated vendor info
            $vendor_stmt->execute();
            $vendor_result = $vendor_stmt->get_result();
            $vendor = $vendor_result->fetch_assoc();
            
            $success_message = "Profile updated successfully";
        } else {
            $error_message = "Failed to update profile. Please try again.";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    $password_errors = [];
    
    if (empty($current_password)) {
        $password_errors[] = "Current password is required";
    }
    
    if (empty($new_password)) {
        $password_errors[] = "New password is required";
    } elseif (strlen($new_password) < 8) {
        $password_errors[] = "New password must be at least 8 characters long";
    }
    
    if ($new_password !== $confirm_password) {
        $password_errors[] = "New passwords do not match";
    }
    
    // Verify current password
    if (empty($password_errors)) {
        $password_query = "SELECT password FROM vendors WHERE id = ?";
        $password_stmt = $conn->prepare($password_query);
        $password_stmt->bind_param("i", $vendor_id);
        $password_stmt->execute();
        $password_result = $password_stmt->get_result();
        $password_data = $password_result->fetch_assoc();
        
        if (!password_verify($current_password, $password_data['password'])) {
            $password_errors[] = "Current password is incorrect";
        }
    }
    
    // Update password if no errors
    if (empty($password_errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_password_query = "UPDATE vendors SET password = ? WHERE id = ?";
        $update_password_stmt = $conn->prepare($update_password_query);
        $update_password_stmt->bind_param("si", $hashed_password, $vendor_id);
        
        if ($update_password_stmt->execute()) {
            $success_message = "Password changed successfully";
        } else {
            $error_message = "Failed to change password. Please try again.";
        }
    } else {
        $error_message = implode("<br>", $password_errors);
    }
}

// Count vendor products
$products_count_query = "SELECT COUNT(*) as count FROM products WHERE vendor_id = ?";
$products_count_stmt = $conn->prepare($products_count_query);
$products_count_stmt->bind_param("i", $vendor_id);
$products_count_stmt->execute();
$products_count_result = $products_count_stmt->get_result();
$products_count = $products_count_result->fetch_assoc()['count'];

// Count orders with vendor's products
$orders_count_query = "SELECT COUNT(DISTINCT o.id) as count 
                      FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE p.vendor_id = ?";
$orders_count_stmt = $conn->prepare($orders_count_query);
$orders_count_stmt->bind_param("i", $vendor_id);
$orders_count_stmt->execute();
$orders_count_result = $orders_count_stmt->get_result();
$orders_count = $orders_count_result->fetch_assoc()['count'];

// Get correct field names based on registration form
$contact_person = isset($vendor['contact_person']) ? $vendor['contact_person'] : 
    (isset($vendor['contact-person']) ? $vendor['contact-person'] : 
    (isset($vendor['name']) ? $vendor['name'] : ''));

$business_name = isset($vendor['business_name']) ? $vendor['business_name'] : 
    (isset($vendor['business-name']) ? $vendor['business-name'] : '');

$description = isset($vendor['description']) ? $vendor['description'] : 
    (isset($vendor['business_description']) ? $vendor['business_description'] : 
    (isset($vendor['business-description']) ? $vendor['business-description'] : ''));

$county = isset($vendor['county']) ? $vendor['county'] : 
    (isset($vendor['area']) ? $vendor['area'] : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Vendor Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/vendor_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Additional custom styles for profile page */
        .dashboard-main {
            padding: 25px;
            background-color: #f8f9fa;
        }
        
        .page-header {
            margin-bottom: 25px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        
        .page-header h1 {
            color: #2E7D32;
            font-size: 28px;
            font-weight: 600;
        }
        
        .flash-message {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            position: relative;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .flash-message.success {
            background-color: #E8F5E9;
            border-left: 5px solid #4CAF50;
            color: #2E7D32;
        }
        
        .flash-message.error {
            background-color: #FFEBEE;
            border-left: 5px solid #F44336;
            color: #C62828;
        }
        
        .flash-message p {
            margin: 0;
            padding-right: 30px;
        }
        
        .flash-message .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        
        .flash-message .close-btn:hover {
            opacity: 1;
        }
        
        .profile-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .profile-sidebar {
            flex: 0 0 300px;
            background: linear-gradient(to bottom, #5A9564, #3a6b47);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 25px;
            color: white;
            text-align: center;
            height: fit-content;
        }
        
        .profile-image-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .profile-stats {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 25px;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 8px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .stat-value {
            font-weight: 600;
            font-size: 16px;
        }
        
        .profile-content {
            flex: 1;
            min-width: 300px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .profile-tabs {
            display: flex;
            background-color: #f1f5f1;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .tab-btn {
            padding: 15px 25px;
            border: none;
            background: none;
            font-size: 15px;
            font-weight: 500;
            color: #666;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .tab-btn:hover {
            color: #4CAF50;
            background-color: rgba(76, 175, 80, 0.05);
        }
        
        .tab-btn.active {
            color: #4CAF50;
            font-weight: 600;
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 3px;
            background-color: #4CAF50;
        }
        
        .tab-content {
            padding: 30px;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            flex: 1;
            min-width: 250px;
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus,
        .form-group textarea:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
            outline: none;
        }
        
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px dashed #ddd;
            border-radius: 6px;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #888;
            font-size: 12px;
        }
        
        .form-actions {
            margin-top: 30px;
            text-align: right;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #388E3C;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 14px;
            color: #777;
            text-align: center;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-container {
                flex-direction: column;
            }
            
            .profile-sidebar {
                flex: 0 0 100%;
                margin-bottom: 20px;
            }
            
            .tab-btn {
                padding: 12px 15px;
                font-size: 14px;
            }
            
            .tab-content {
                padding: 20px;
            }
        }
    </style>
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
                    <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                </div>
                
                <?php if (!empty($success_message)): ?>
                    <div class="flash-message success">
                        <p><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></p>
                        <button class="close-btn"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="flash-message error">
                        <p><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></p>
                        <button class="close-btn"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>
                
                <div class="profile-container">
                    <div class="profile-sidebar">
                        <div class="profile-image-container">
                            <img src="../<?php echo !empty($vendor['profile_image']) ? htmlspecialchars($vendor['profile_image']) : 'images/default-profile.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($contact_person); ?>" class="profile-image">
                        </div>
                        <h3><?php echo htmlspecialchars($contact_person); ?></h3>
                        <p><?php echo htmlspecialchars($business_name); ?></p>
                        
                        <div class="profile-stats">
                            <div class="stat-item">
                                <span class="stat-label">Products</span>
                                <span class="stat-value"><?php echo $products_count; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Orders</span>
                                <span class="stat-value"><?php echo $orders_count; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Joined</span>
                                <span class="stat-value"><?php 
                                    echo isset($vendor['created_at']) ? date('M Y', strtotime($vendor['created_at'])) : date('M Y'); 
                                ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-content">
                        <div class="profile-tabs">
                            <button class="tab-btn active" data-tab="profile-info">
                                <i class="fas fa-user"></i> Profile Information
                            </button>
                            <button class="tab-btn" data-tab="change-password">
                                <i class="fas fa-lock"></i> Change Password
                            </button>
                        </div>
                        
                        <div class="tab-content" id="profile-info">
                            <form action="" method="POST" enctype="multipart/form-data" class="profile-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($contact_person); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="business_name">Business Name</label>
                                        <input type="text" id="business_name" name="business_name" value="<?php echo htmlspecialchars($business_name); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($vendor['email'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($vendor['phone'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($vendor['address'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($vendor['city'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="county">County/Area</label>
                                        <input type="text" id="county" name="county" value="<?php echo htmlspecialchars($county); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">Business Description</label>
                                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="profile_image">Profile Image</label>
                                    <input type="file" id="profile_image" name="profile_image" accept="image/*">
                                    <small>Max file size: 2MB. Allowed formats: JPEG, PNG, GIF</small>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="tab-content" id="change-password" style="display: none;">
                            <form action="" method="POST" class="password-form">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" required>
                                    <small>Must be at least 8 characters long</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="change_password" class="btn btn-primary">