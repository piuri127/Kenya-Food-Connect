<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start or resume the session
session_start();

// Detailed logging function
function logout_log($message) {
    $log_file = __DIR__ . '/logout_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Log the logout attempt
    $user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'unknown';
    $user_email = isset($_SESSION['email']) ? $_SESSION['email'] : 'N/A';
    logout_log("Logout attempt for user: $user_email (Type: $user_type)");

    // Unset all session variables
    $_SESSION = array();

    // Destroy the session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Completely destroy the session
    session_destroy();

    // Clear any client-side storage
    echo json_encode([
        'status' => 'success',
        'message' => 'Logged out successfully',
        'redirect' => '../index.html'
    ]);

    // Log successful logout
    logout_log("Successful logout for user: $user_email");

} catch (Exception $e) {
    // Log any errors during logout
    logout_log("Logout error: " . $e->getMessage());

    // Send error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Logout failed',
        'redirect' => '../index.html'
    ]);
}
exit();
?>