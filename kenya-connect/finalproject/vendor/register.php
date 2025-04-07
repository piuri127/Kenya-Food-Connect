<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'vendor') {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Registration - Kenya Connect</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="form-page vendor-form-page">
    <div class="form-container">
        <div class="form-header">
            <a href="../index.html" class="logo-link">
                <h1>Kenya Connect</h1>
            </a>
            <p>Create your vendor account</p>
        </div>
        
        <?php
        // Display any error messages
        if (isset($_SESSION['registration_error'])) {
            echo '<div class="alert error">' . htmlspecialchars($_SESSION['registration_error']) . '</div>';
            unset($_SESSION['registration_error']);
        }
        ?>
        
        <form id="vendor-registration-form" class="auth-form" action="../php/register_process.php" method="POST" enctype="multipart/form-data">
            <h2>Vendor Registration</h2>
            
            <!-- Hidden Input to Define User Type -->
            <input type="hidden" name="user_type" value="vendor">

            <div class="form-group">
                <label for="business-name">Business Name</label>
                <input type="text" id="business-name" name="business-name" required 
                       minlength="2" maxlength="100"
                       pattern="^[a-zA-Z0-9\s]+$"
                       title="Business name must be 2-100 characters long and contain only letters, numbers, and spaces">
            </div>
            
            <div class="form-group">
                <label for="contact-person">Contact Person</label>
                <input type="text" id="contact-person" name="contact-person" required 
                       minlength="2" maxlength="100"
                       pattern="^[a-zA-Z\s]+$"
                       title="Contact person name must be 2-100 characters long and contain only letters">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                       title="Please enter a valid email address">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" 
                       placeholder="+254..." required 
                       pattern="^\+?[0-9]{10,14}$"
                       title="Phone number must be 10-14 digits, can start with +">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" required
                           minlength="8"
                           pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                           title="Password must be at least 8 characters long, contain uppercase and lowercase letters, a number, and a special character">
                    <i class="fas fa-eye toggle-password"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <div class="password-field">
                    <input type="password" id="confirm-password" name="confirm-password" required>
                    <i class="fas fa-eye toggle-password"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label>Business Location</label>
                <div class="location-group">
                    <input type="text" id="city" name="city" placeholder="City" required 
                           minlength="2" maxlength="50"
                           pattern="^[a-zA-Z\s]+$"
                           title="City name must be 2-50 characters long">
                    <input type="text" id="area" name="area" placeholder="Area/Neighborhood" required
                           minlength="2" maxlength="50"
                           pattern="^[a-zA-Z0-9\s]+$"
                           title="Area name must be 2-50 characters long">
                </div>
            </div>
            
            <div class="business-details">
                <div class="form-group">
                    <label for="business-type">Business Type</label>
                    <select id="business-type" name="business-type" required>
                        <option value="">Select Business Type</option>
                        <option value="restaurant">Restaurant</option>
                        <option value="grocery">Grocery Store</option>
                        <option value="bakery">Bakery</option>
                        <option value="farm">Farm</option>
                        <option value="supermarket">Supermarket</option>
                        <option value="hotel">Hotel</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="business-description">Brief Description</label>
                    <textarea id="business-description" name="business-description" 
                              rows="3" 
                              maxlength="500"
                              placeholder="Tell us about your business and the types of food you typically have as surplus"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="license">Business License/Permit (Optional)</label>
                    <div class="upload-area">
                        <input type="file" id="license" name="license" 
                               accept=".pdf,.jpg,.jpeg,.png" 
                               max-file-size="5242880">
                        <small>Max file size: 5MB</small>
                    </div>
                </div>
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

        // File size validation
        document.getElementById('license').addEventListener('change', function(e) {
            const maxSize = 5 * 1024 * 1024; // 5MB
            const file = e.target.files[0];
            
            if (file && file.size > maxSize) {
                alert('File is too large. Maximum file size is 5MB.');
                e.target.value = ''; // Clear the file input
            }
        });
    </script>
</body>
</html>