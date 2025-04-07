<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'consumer') {
    header("Location: dashboard.php");
    exit();
}

// Initialize error message variable
$error_message = '';

// Check for error message from registration process
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumer Registration - Kenya Connect</title>
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
            <p>Create your consumer account</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div id="error-message" class="alert error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form id="consumer-registration-form" class="auth-form" action="../php/register_process.php" method="POST">
            <h2>Consumer Registration</h2>
            
            <!-- Hidden Input to Define User Type -->
            <input type="hidden" name="user_type" value="consumer">
            
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" 
                       required 
                       minlength="2" 
                       maxlength="100" 
                       pattern="^[A-Za-z\s]+$" 
                       title="Name must be 2-100 characters long and contain only letters">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       required 
                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" 
                       title="Please enter a valid email address">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" 
                       placeholder="+254..." 
                       required 
                       pattern="^\+?[0-9]{10,14}$" 
                       title="Phone number must be 10-14 digits, can start with +">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" 
                           required 
                           minlength="8" 
                           pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$" 
                           title="Password must be at least 8 characters long, contain uppercase and lowercase letters, a number, and a special character">
                    <i class="fas fa-eye toggle-password"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <div class="password-field">
                    <input type="password" id="confirm-password" name="confirm-password" 
                           required 
                           minlength="8">
                    <i class="fas fa-eye toggle-password"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label>Location</label>
                <div class="location-group">
                    <input type="text" id="city" name="city" 
                           placeholder="City" 
                           required 
                           minlength="2" 
                           maxlength="50" 
                           pattern="^[A-Za-z\s]+$" 
                           title="City must be 2-50 characters long and contain only letters">
                    <input type="text" id="area" name="area" 
                           placeholder="Area/Neighborhood" 
                           required 
                           minlength="2" 
                           maxlength="50" 
                           pattern="^[A-Za-z0-9\s]+$" 
                           title="Area must be 2-50 characters long">
                </div>
            </div>
            
            <div class="consumer-preferences">
                <label>Food Preferences (Optional)</label>
                <div class="preference-options">
                    <span class="preference-option" data-value="fruits">Fruits</span>
                    <span class="preference-option" data-value="vegetables">Vegetables</span>
                    <span class="preference-option" data-value="dairy">Dairy</span>
                    <span class="preference-option" data-value="bakery">Bakery</span>
                    <span class="preference-option" data-value="meat">Meat</span>
                    <span class="preference-option" data-value="grains">Grains</span>
                </div>
                <input type="hidden" id="preferences" name="preferences">
            </div>
            
            <div class="form-group">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the <a href="../terms.html">Terms of Service</a> and <a href="../privacy.html">Privacy Policy</a></label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            
            <div class="form-footer">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </form>
    </div>
    
    <script src="../js/script.js"></script>
    <script>
        // Password match validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm-password');
        
        function validatePasswordMatch() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords do not match");
            } else {
                confirmPassword.setCustomValidity("");
            }
        }
        
        password.addEventListener('input', validatePasswordMatch);
        confirmPassword.addEventListener('input', validatePasswordMatch);

        // Initialize preference options
        document.querySelectorAll('.preference-option').forEach(function(option) {
            option.addEventListener('click', function() {
                this.classList.toggle('selected');
                
                // Update hidden input with selected preferences
                const selected = document.querySelectorAll('.preference-option.selected');
                const values = Array.from(selected).map(el => el.getAttribute('data-value'));
                document.getElementById('preferences').value = values.join(',');
            });
        });

        // Optional: Add client-side validation for form submission
        document.getElementById('consumer-registration-form').addEventListener('submit', function(e) {
            const name = document.getElementById('name');
            const email = document.getElementById('email');
            const phone = document.getElementById('phone');
            const password = document.getElementById('password');
            const city = document.getElementById('city');
            const area = document.getElementById('area');
            const terms = document.getElementById('terms');

            // Validate name
            if (!/^[A-Za-z\s]{2,100}$/.test(name.value)) {
                e.preventDefault();
                alert('Please enter a valid name (2-100 letters)');
                return;
            }

            // Validate email
            if (!/^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/.test(email.value)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }

            // Validate phone
            if (!/^\+?[0-9]{10,14}$/.test(phone.value)) {
                e.preventDefault();
                alert('Please enter a valid phone number');
                return;
            }

            // Validate password complexity
            if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(password.value)) {
                e.preventDefault();
                alert('Password must be at least 8 characters long, contain uppercase and lowercase letters, a number, and a special character');
                return;
            }

            // Validate city and area
            if (!/^[A-Za-z\s]{2,50}$/.test(city.value)) {
                e.preventDefault();
                alert('Please enter a valid city name');
                return;
            }

            if (!/^[A-Za-z0-9\s]{2,50}$/.test(area.value)) {
                e.preventDefault();
                alert('Please enter a valid area name');
                return;
            }

            // Validate terms
            if (!terms.checked) {
                e.preventDefault();
                alert('You must agree to the Terms of Service and Privacy Policy');
                return;
            }
        });
    </script>
</body>
</html>