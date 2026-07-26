<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: User Registration
 * ChatApp - POST /api/register.php
 * =====================================================
 */

// Define app is running
define('APP_RUNNING', true);

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialize session
session_initialize();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Method not allowed', 405);
}

// Check rate limit
$client_ip = get_client_ip();
if (class_exists('Security')) {
    $security = Security::getInstance();
    if (!$security->checkRateLimit($client_ip, 'register', 3)) {
        send_error('Too many registration attempts. Please try again later.', 429);
    }
} else {
    if (!check_rate_limit($client_ip, 'register', 5, 900)) {
        send_error('Too many registration attempts. Please try again later.', 429);
    }
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Fallback to POST data if not JSON
if (!$input) {
    $input = $_POST;
}

// Validate CSRF token (for non-AJAX requests)
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!session_validate_csrf($csrf_token)) {
    if (!is_ajax_request()) {
        send_error('Invalid security token', 403);
    }
}

// Extract and sanitize input
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

// Basic validation
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    send_error('All fields are required');
}

// Validate using Security class
if (class_exists('Security')) {
    $security = Security::getInstance();
    
    // Validate email
    $emailValidation = $security->validateInput($email, 'email');
    if (!$emailValidation['valid']) {
        send_error($emailValidation['error']);
    }
    
    // Validate username
    $usernameValidation = $security->validateInput($username, 'username');
    if (!$usernameValidation['valid']) {
        send_error($usernameValidation['error']);
    }
    
    // Validate password strength
    $passwordValidation = $security->validatePasswordStrength($password);
    if (!$passwordValidation['valid']) {
        send_error(implode(', ', $passwordValidation['errors']));
    }
    
    // Check for suspicious content
    if ($security->containsSuspiciousContent($username)) {
        send_error('Username contains prohibited content');
    }
} else {
    // Fallback validation
    if (!validate_email($email)) {
        send_error('Invalid email format');
    }
    
    if (!validate_username($username)) {
        send_error('Username must be 3-50 characters (letters, numbers, underscores)');
    }
    
    if (!validate_password_strength($password)) {
        send_error('Password must be at least 8 characters with uppercase, lowercase, and number');
    }
}

// Check password match
if ($password !== $confirm_password) {
    send_error('Passwords do not match');
}

// Register the user
$result = register_user([
    'username' => $username,
    'email' => $email,
    'password' => $password,
    'confirm_password' => $confirm_password
]);

if ($result['success']) {
    // Log success
    log_activity($result['user_id'], 'registration', [
        'username' => $username,
        'email' => $email
    ]);
    
    send_success($result['message'], [
        'user_id' => $result['user_id'],
        'username' => $result['username'],
        'email' => $result['email'],
        'friend_code' => $result['friend_code']
    ]);
} else {
    send_error($result['message']);
}
