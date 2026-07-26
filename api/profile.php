<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Get User Profile
 * ChatApp - GET /api/profile.php
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

// Verify session security
if (!session_verify_security()) {
    send_error('Session expired. Please login again.', 401);
}

// Check if user is logged in
if (!session_is_logged_in()) {
    send_error('Please login to access this resource', 401);
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Method not allowed', 405);
}

$user_id = session_get_user_id();

// Get fresh user data from database
$sql = "SELECT id, username, email, friend_code, avatar, bio, 
        created_at, last_seen 
        FROM users 
        WHERE id = ? AND status = 'active' 
        LIMIT 1";
$user = db_fetch_single($sql, [$user_id], 'i');

if (!$user) {
    // User not found, logout
    app_session_destroy();
    send_error('User not found', 404);
}

// Prepare response data
$profile_data = [
    'id' => (int)$user['id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'friend_code' => $user['friend_code'],
    'avatar' => $user['avatar'],
    'bio' => $user['bio'],
    'member_since' => format_date($user['created_at']),
    'last_seen' => $user['last_seen'] ? time_ago($user['last_seen']) : 'Online'
];

send_success('Profile loaded', $profile_data);
