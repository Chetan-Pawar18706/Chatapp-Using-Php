<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Check Auth Status
 * ChatApp - GET /api/check-auth.php
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

// Check authentication status
$is_logged_in = session_is_logged_in();
$user_data = null;

if ($is_logged_in) {
    // Verify session security
    if (!session_verify_security()) {
        $is_logged_in = false;
    } else {
        $user_data = session_get_user_data();
    }
}

// Try remember me if not logged in
if (!$is_logged_in) {
    $remember_user = session_validate_remember_me();
    if ($remember_user) {
        $is_logged_in = true;
        session_set_user($remember_user, false);
        $user_data = [
            'id' => (int)$remember_user['id'],
            'username' => $remember_user['username'],
            'email' => $remember_user['email'],
            'friend_code' => $remember_user['friend_code'],
            'avatar' => $remember_user['avatar']
        ];
    }
}

// Generate new CSRF token
$csrf_token = session_generate_csrf();

// Send response
send_success('Auth status checked', [
    'logged_in' => $is_logged_in,
    'user' => $user_data,
    'csrf_token' => $csrf_token
]);
