document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    
    if (togglePasswordButtons) {
        togglePasswordButtons.forEach(button => {
            button.addEventListener('click', function() {
                const passwordField = this.previousElementSibling;
                
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    passwordField.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            });
        });
    }
    
    // Handle consumer preference selection
    const preferenceOptions = document.querySelectorAll('.preference-option');
    const preferencesInput = document.getElementById('preferences');
    
    preferenceOptions.forEach(option => {
        option.addEventListener("click", function () {
            let selectedValues = preferencesInput.value ? preferencesInput.value.split(",") : [];
            let value = this.getAttribute("data-value");

            if (selectedValues.includes(value)) {
                selectedValues = selectedValues.filter(v => v !== value);
            } else {
                selectedValues.push(value);
            }

            preferencesInput.value = selectedValues.join(",");
        });
    })
})
    
    // Handle file upload UI for vendor registration
    const licenseUpload = document.getElementById('license-upload');
    const licenseInput = document.getElementById('license');
    
    if (licenseUpload && licenseInput) {
        licenseUpload.addEventListener('click', function() {
            licenseInput.click();
        });
        
        licenseInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const fileName = this.files[0].name;
                const uploadIcon = licenseUpload.querySelector('i');
                const uploadText = licenseUpload.querySelector('p');
                
                uploadIcon.classList.remove('fa-file-upload');
                uploadIcon.classList.add('fa-check-circle');
                uploadText.textContent = `File selected: ${fileName}`;
                licenseUpload.style.borderColor = '#4CAF50';
            }
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('.auth-form');
    
    if (forms) {
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                
                // Basic validation
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        highlightError(field);
                    } else {
                        removeError(field);
                    }
                });
                
                // Email validation
                const emailField = form.querySelector('input[type="email"]');
                if (emailField && !validateEmail(emailField.value)) {
                    isValid = false;
                    highlightError(emailField, 'Please enter a valid email address');
                }
                
                // Password validation
                const passwordField = form.querySelector('input[name="password"]');
                const confirmPasswordField = form.querySelector('input[name="confirm-password"]');
                
                if (passwordField && passwordField.value.length < 8) {
                    isValid = false;
                    highlightError(passwordField, 'Password must be at least 8 characters');
                }
                
                if (passwordField && confirmPasswordField && passwordField.value !== confirmPasswordField.value) {
                    isValid = false;
                    highlightError(confirmPasswordField, 'Passwords do not match');
                }
                
                // If all validations pass, simulate form submission
                if (isValid) {
                    // Get form ID to determine which page to redirect to
                    const formId = form.id;
                    
                    // Show loading state
                    const submitButton = form.querySelector('button[type="submit"]');
                    const originalText = submitButton.textContent;
                    submitButton.textContent = 'Please wait...';
                    submitButton.disabled = true;
                    
                    // Simulate API call with timeout
                    setTimeout(function() {
                        // Determine redirect based on form ID
                        if (formId === 'consumer-login-form' || formId === 'consumer-registration-form') {
                            window.location.href = 'dashboard.php';
                        } else if (formId === 'vendor-login-form' || formId === 'vendor-registration-form') {
                            window.location.href = 'dashboard.php';
                        } else {
                            // Default redirect to home page
                            window.location.href = '../index.html';
                        }
                    }, 1500);
                }
            });
        });
    }
    
    // Helper functions for form validation
    function highlightError(field, message) {
        field.classList.add('error');
        
        // Create or update error message
        let errorMessage = field.nextElementSibling;
        if (!errorMessage || !errorMessage.classList.contains('error-message')) {
            errorMessage = document.createElement('span');
            errorMessage.classList.add('error-message');
            field.parentNode.insertBefore(errorMessage, field.nextSibling);
        }
        
        errorMessage.textContent = message || 'This field is required';
    }
    
    function removeError(field) {
        field.classList.remove('error');
        
        // Remove error message if it exists
        const errorMessage = field.nextElementSibling;
        if (errorMessage && errorMessage.classList.contains('error-message')) {
            errorMessage.remove();
        }
    }
    
    function validateEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }