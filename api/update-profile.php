<?php
/**
 * =====================================================
 * Profile Update API
 * ChatApp - Update User Profile
 * =====================================================
 */

define('APP_RUNNING', true);

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';

init_session();

// Check if user is logged in
if (!is_logged_in()) {
    send_json_response(401, ['success' => false, 'message' => 'Unauthorized']);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(405, ['success' => false, 'message' => 'Method not allowed']);
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    send_json_response(403, ['success' => false, 'message' => 'Invalid CSRF token']);
}

$user_id = get_user_id();

// Check rate limit
if (!check_rate_limit('profile_update_' . $user_id, 10, 60)) {
    send_json_response(429, ['success' => false, 'message' => 'Too many requests. Please wait.']);
}

// Get and sanitize input
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$about = trim($_POST['about'] ?? '');
$status = $_POST['status'] ?? 'online';
$timezone = $_POST['timezone'] ?? 'UTC';
$language = $_POST['language'] ?? 'en';

// Validation
$errors = [];

// Username validation
if (empty($username)) {
    $errors[] = 'Username is required';
} elseif (strlen($username) < 3 || strlen($username) > 30) {
    $errors[] = 'Username must be 3-30 characters';
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = 'Username can only contain letters, numbers, and underscores';
} else {
    // Check if username is taken by another user
    $check_query = "SELECT id FROM users WHERE username = ? AND id != ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, 'si', $username, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        $errors[] = 'Username is already taken';
    }
}

// Email validation
if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
} else {
    // Check if email is taken by another user
    $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, 'si', $email, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        $errors[] = 'Email is already registered';
    }
}

// Bio validation
if (strlen($bio) > 150) {
    $errors[] = 'Bio must be 150 characters or less';
}

// About validation
if (strlen($about) > 1000) {
    $errors[] = 'About must be 1000 characters or less';
}

// Status validation
$valid_statuses = ['online', 'busy', 'away', 'invisible'];
if (!in_array($status, $valid_statuses)) {
    $errors[] = 'Invalid status';
}

// Return errors if any
if (!empty($errors)) {
    send_json_response(400, ['success' => false, 'message' => implode(', ', $errors)]);
}

// Update profile
$query = "UPDATE users SET 
    username = ?,
    email = ?,
    bio = ?,
    about = ?,
    status = ?,
    timezone = ?,
    language = ?,
    updated_at = NOW()
    WHERE id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'sssssssi', 
    $username, 
    $email, 
    $bio, 
    $about, 
    $status, 
    $timezone, 
    $language, 
    $user_id
);

if (mysqli_stmt_execute($stmt)) {
    // Log activity
    log_activity($user_id, 'profile_update', 'Updated profile settings');
    
    send_json_response(200, [
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => [
            'username' => $username,
            'email' => $email,
            'bio' => $bio,
            'about' => $about,
            'status' => $status,
            'timezone' => $timezone,
            'language' => $language
        ]
    ]);
} else {
    send_json_response(500, ['success' => false, 'message' => 'Failed to update profile']);
}
