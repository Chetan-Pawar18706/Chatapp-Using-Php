<?php
/**
 * =====================================================
 * Login Page
 * ChatApp - User Authentication
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

session_initialize();

if (class_exists('Security')) {
    $security = Security::getInstance();
    $security->sendSecurityHeaders();
}

if (session_is_logged_in()) {
    header('Location: pages/dashboard.php');
    exit;
}

if (!session_is_logged_in()) {
    $remember_user = session_validate_remember_me();
    if ($remember_user) {
        session_set_user($remember_user, false);
        header('Location: pages/dashboard.php');
        exit;
    }
}

$csrf_token = session_generate_csrf();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title>Login - ChatApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-logo">
                    <div class="logo-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h1>Welcome Back</h1>
                    <p>Sign in to continue to ChatApp</p>
                </div>
                
                <div id="loginAlert" class="alert"></div>
                
                <form id="loginForm" class="auth-form" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username or Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter username or email" required autocomplete="username">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                            <span class="input-group-text password-toggle" id="passwordToggle">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end align-items-center mb-4">
                        <a href="pages/forgot-password.php" class="btn-link-custom">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" id="loginBtn" class="btn btn-auth btn-primary-custom">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
                
                <div class="auth-divider"><span>New to ChatApp?</span></div>
                
                <div class="auth-footer">
                    <p><a href="pages/register.php">Create an account</a></p>
                </div>

                <div class="text-center mt-3">
                    <a href="index.php" style="color: var(--text-muted); font-size: 0.85rem; text-decoration: none;">
                        <i class="fas fa-arrow-left me-1"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
