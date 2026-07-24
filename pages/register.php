<?php
/**
 * =====================================================
 * Registration Page
 * ChatApp - New User Registration
 * =====================================================
 */

// Define app is running
define('APP_RUNNING', true);

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialize session
session_initialize();

// Check if already logged in
if (session_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

// Generate CSRF token
$csrf_token = session_generate_csrf();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title>Register - ChatApp</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-card">
                <!-- Logo -->
                <div class="auth-logo">
                    <div class="logo-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h1>Create Account</h1>
                    <p>Join ChatApp and start chatting</p>
                </div>
                
                <!-- Alert Message -->
                <div id="registerAlert" class="alert"></div>
                
                <!-- Registration Form -->
                <form id="registerForm" class="auth-form" novalidate>
                    <!-- Username -->
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="username" 
                                name="username" 
                                placeholder="Choose a username"
                                required
                                minlength="3"
                                maxlength="50"
                                pattern="[a-zA-Z0-9_]{3,50}"
                                autocomplete="username"
                            >
                        </div>
                        <div class="form-text">3-50 characters, letters, numbers, and underscores only</div>
                    </div>
                    
                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="email" 
                                name="email" 
                                placeholder="Enter your email"
                                required
                                autocomplete="email"
                            >
                        </div>
                    </div>
                    
                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="password" 
                                name="password" 
                                placeholder="Create a password"
                                required
                                minlength="8"
                                data-strength="passwordStrength"
                                data-strength-text="passwordStrengthText"
                                autocomplete="new-password"
                            >
                            <span class="input-group-text" onclick="togglePasswordVisibility('password', 'togglePasswordIcon')">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </span>
                        </div>
                        <!-- Password Strength Indicator -->
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-bar-fill" id="passwordStrength"></div>
                            </div>
                            <div class="strength-text" id="passwordStrengthText"></div>
                        </div>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder="Confirm your password"
                                required
                                minlength="8"
                                autocomplete="new-password"
                            >
                            <span class="input-group-text" onclick="togglePasswordVisibility('confirm_password', 'toggleConfirmIcon')">
                                <i class="fas fa-eye" id="toggleConfirmIcon"></i>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Terms Agreement -->
                    <div class="form-check mb-4">
                        <input 
                            type="checkbox" 
                            class="form-check-input" 
                            id="terms" 
                            required
                        >
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#" class="text-primary-custom">Terms of Service</a> and <a href="#" class="text-primary-custom">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" id="registerBtn" class="btn btn-auth btn-primary-custom">
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </button>
                </form>
                
                <!-- Divider -->
                <div class="auth-divider">
                    <span>Already have an account?</span>
                </div>
                
                <!-- Login Link -->
                <div class="auth-footer">
                    <p>
                        <a href="../login.php">Sign in to your account</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/app.js"></script>
</body>
</html>
