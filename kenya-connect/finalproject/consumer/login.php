<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Optional: If already logged in, redirect to dashboard
if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'consumer') {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumer Login - Kenya Connect</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="form-page">
    <div class="form-container">
        <div class="form-header">
            <a href="../index.html" class="logo-link">
                <h1>Kenya Connect</h1>
            </a>
            <p>Find affordable and free food in your area</p>
        </div>
        
        <?php
        // Display any error messages from login attempts
        if(isset($_SESSION['error'])) {
            echo '<div class="alert error">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']); // Clear the error after displaying
        }
        ?>
        <div id="message-container" class="alert" style="display: none;"></div>
        
        <form id="consumer-login-form" class="auth-form" method="POST" action="../php/login_process.php">
            <h2>Consumer Login</h2>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" required>
                    <i class="fas fa-eye toggle-password"></i>
                </div>
            </div>
            
            <input type="hidden" name="user_type" value="consumer">
            
            <div class="form-group remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
                <a href="forgot-password.html" class="forgot-password">Forgot Password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Login</button>
            
            <div class="form-footer">
                <p>Don't have an account? <a href="register.php">Register</a></p>
            </div>
        </form>
    </div>
    
    <script src="../js/script.js"></script>
    <script>
        // Password toggle functionality
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        // Optional: Add client-side form validation
        document.getElementById('consumer-login-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const messageContainer = document.getElementById('message-container');

            // Basic validation
            if (!email || !password) {
                e.preventDefault();
                messageContainer.textContent = 'Please enter both email and password';
                messageContainer.style.display = 'block';
                messageContainer.classList.add('error');
            }
        });
    </script>
</body>
</html>