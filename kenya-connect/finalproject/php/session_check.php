<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function check_login() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        // User is not logged in, redirect to login page
        header("Location: ../index.html");
        exit();
    }
}

// Check if user is a vendor
function check_vendor() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'vendor') {
        // User is not a vendor, redirect to appropriate page
        if (isset($_SESSION['user_id'])) {
            // User is logged in but not a vendor
            header("Location: ../consumer/dashboard.php");
        } else {
            // User is not logged in
            header("Location: ../index.html");
        }
        exit();
    }
}

// Check if user is a consumer
function check_consumer() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'consumer') {
        // User is not a consumer, redirect to appropriate page
        if (isset($_SESSION['user_id'])) {
            // User is logged in but not a consumer
            header("Location: ../vendor/dashboard.php");
        } else {
            // User is not logged in
            header("Location: ../index.html");
        }
        exit();
    }
}
?>