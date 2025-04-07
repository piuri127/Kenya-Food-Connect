/**
 * Kenya Connect - Main JavaScript
 * Handles all global functionality for the platform
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
    }
    
    // Vendor dashboard sidebar toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebarClose = document.querySelector('.sidebar-close');
    const dashboardContainer = document.querySelector('.dashboard-container');
    
    if (menuToggle && dashboardContainer) {
        menuToggle.addEventListener('click', function() {
            dashboardContainer.classList.toggle('sidebar-collapsed');
        });
    }
    
    if (sidebarClose && dashboardContainer) {
        sidebarClose.addEventListener('click', function() {
            dashboardContainer.classList.add('sidebar-collapsed');
        });
    }
    
    // Handle dropdown menus
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    if (dropdownToggles) {
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                this.nextElementSibling.classList.toggle('show');
                
                // Close all other dropdowns
                dropdownToggles.forEach(otherToggle => {
                    if (otherToggle !== toggle) {
                        otherToggle.nextElementSibling.classList.remove('show');
                    }
                });
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            dropdownToggles.forEach(toggle => {
                if (!toggle.contains(e.target)) {
                    toggle.nextElementSibling.classList.remove('show');
                }
            });
        });
    }
    
    // Display flash messages
    const flashMessage = document.querySelector('.flash-message');
    
    if (flashMessage) {
        // Show the message
        flashMessage.classList.add('show');
        
        // Hide after 5 seconds
        setTimeout(function() {
            flashMessage.classList.remove('show');
            
            // Remove from DOM after animation completes
            setTimeout(function() {
                flashMessage.remove();
            }, 500);
        }, 5000);
        
        // Allow closing with close button
        const closeBtn = flashMessage.querySelector('.close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                flashMessage.classList.remove('show');
                
                // Remove from DOM after animation completes
                setTimeout(function() {
                    flashMessage.remove();
                }, 500);
            });
        }
    }
    
    // Quantity increment/decrement buttons
    const quantityControls = document.querySelectorAll('.quantity-control');
    
    if (quantityControls) {
        quantityControls.forEach(control => {
            const input = control.querySelector('input');
            const decrementBtn = control.querySelector('.decrement');
            const incrementBtn = control.querySelector('.increment');
            
            decrementBtn.addEventListener('click', function() {
                let currentValue = parseInt(input.value);
                if (currentValue > parseInt(input.min)) {
                    input.value = currentValue - 1;
                    // Trigger change event
                    input.dispatchEvent(new Event('change'));
                }
            });
            
            incrementBtn.addEventListener('click', function() {
                let currentValue = parseInt(input.value);
                if (currentValue < parseInt(input.max)) {
                    input.value = currentValue + 1;
                    // Trigger change event
                    input.dispatchEvent(new Event('change'));
                }
            });
            
            // Update price when quantity changes
            input.addEventListener('change', function() {
                const priceElement = control.closest('.product-details').querySelector('.product-total-price');
                if (priceElement) {
                    const unitPrice = parseFloat(priceElement.getAttribute('data-unit-price'));
                    const totalPrice = unitPrice * parseInt(input.value);
                    priceElement.textContent = 'KSh ' + totalPrice.toFixed(2);
                }
            });
        });
    }
    
    // Handle file uploads with preview
    const fileInputs = document.querySelectorAll('.file-input');
    
    if (fileInputs) {
        fileInputs.forEach(input => {
            input.addEventListener('change', function() {
                const preview = document.querySelector(input.getAttribute('data-preview'));
                const previewContainer = document.querySelector(input.getAttribute('data-container'));
                
                if (preview && this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        if (previewContainer) {
                            previewContainer.classList.add('has-image');
                        }
                    };
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
        });
    }
    
    // Search functionality
    const searchInput = document.querySelector('.search-input');
    const searchResults = document.querySelector('.search-results');
    
    if (searchInput && searchResults) {
        let debounceTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimeout);
            
            debounceTimeout = setTimeout(function() {
                const query = searchInput.value.trim();
                
                if (query.length < 2) {
                    searchResults.innerHTML = '';
                    searchResults.classList.remove('active');
                    return;
                }
                
                // Show loading indicator
                searchResults.innerHTML = '<div class="loading">Searching...</div>';
                searchResults.classList.add('active');
                
                // Fetch search results (this would be an API call in a real application)
                // For demonstration, we're using a timeout to simulate API call
                setTimeout(function() {
                    // This would be replaced with actual search results
                    searchResults.innerHTML = `
                        <div class="search-item">
                            <img src="/api/placeholder/50/50" alt="Product">
                            <div>
                                <h4>Fresh Tomatoes</h4>
                                <p>KSh 80 per kg</p>
                            </div>
                        </div>
                        <div class="search-item">
                            <img src="/api/placeholder/50/50" alt="Product">
                            <div>
                                <h4>Cherry Tomatoes</h4>
                                <p>KSh 120 per kg</p>
                            </div>
                        </div>
                        <div class="search-footer">
                            <a href="search.php?q=${query}">View all results</a>
                        </div>
                    `;
                }, 500);
            }, 300); // 300ms debounce
        });
        
        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.remove('active');
            }
        });
    }
    
    // Initialize date pickers
    const datePickers = document.querySelectorAll('.date-picker');
    
    if (datePickers) {
        datePickers.forEach(picker => {
            // This would use a date picker library in a real application
            // For now, we're just using the browser's native date input
        });
    }
    
    // Order status updates
    const statusButtons = document.querySelectorAll('.status-update-btn');
    
    if (statusButtons) {
        statusButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const status = this.getAttribute('data-status');
                const orderId = this.getAttribute('data-order-id');
                
                if (confirm('Are you sure you want to update this order to ' + status + '?')) {
                    // This would be an API call in a real application
                    // For demonstration, we're just redirecting
                    window.location.href = `../php/update_order.php?id=${orderId}&action=${status}`;
                }
            });
        });
    }
    
    // Handle logout button click events
    const logoutButtons = document.querySelectorAll('.logout-btn, .logout-link');
    
    if (logoutButtons) {
        logoutButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                logout();
            });
        });
    }
});

// Additional global functions can be added here
function togglePasswordVisibility(inputId, toggleBtn) {
    const passwordInput = document.getElementById(inputId);
    const icon = toggleBtn.querySelector('i');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

/**
 * Handle logout process via AJAX
 * Makes a fetch request to the logout endpoint and handles the response
 */
function logout() {
    fetch('/api/auth/logout.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'Logout failed. Please try again.');
            }
        })
        .catch(error => {
            console.error('Logout error:', error);
            alert('An error occurred during logout. Please try again.');
        });
}