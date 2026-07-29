<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Upload Profile Photo API
 * ChatApp - Avatar & Cover Photo Upload
 * =====================================================
 */

define('APP_RUNNING', true);

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/media.php';

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
if (!check_rate_limit('photo_upload_' . $user_id, 5, 60)) {
    send_json_response(429, ['success' => false, 'message' => 'Too many uploads. Please wait.']);
}

// Get upload type
$type = $_POST['type'] ?? 'avatar';
if (!in_array($type, ['avatar', 'cover'])) {
    send_json_response(400, ['success' => false, 'message' => 'Invalid upload type']);
}

// Handle avatar removal
if (isset($_POST['remove']) && $_POST['remove'] == '1' && $type === 'avatar') {
    $user_query = "SELECT avatar FROM users WHERE id = ?";
    $user_stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user = mysqli_fetch_assoc($user_result);
    
    // Delete old avatar file
    if (!empty($user['avatar']) && file_exists(dirname(__DIR__) . '/' . $user['avatar'])) {
        unlink(dirname(__DIR__) . '/' . $user['avatar']);
    }
    
    // Set avatar to NULL
    $query = "UPDATE users SET avatar = NULL, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        log_activity($user_id, 'photo_remove', 'Removed avatar');
        send_json_response(200, ['success' => true, 'message' => 'Avatar removed successfully']);
    } else {
        send_json_response(500, ['success' => false, 'message' => 'Failed to remove avatar']);
    }
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    send_json_response(400, ['success' => false, 'message' => 'No file uploaded']);
}

$file = $_FILES['file'];

// Get file extension
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Check if extension is allowed (images only)
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($extension, $allowed)) {
    send_json_response(400, [
        'success' => false,
        'message' => 'Only JPG, PNG, GIF, and WEBP images are allowed'
    ]);
}

// Check file size (max 5MB for profile photos)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    send_json_response(400, [
        'success' => false,
        'message' => 'File too large. Maximum size: 5 MB'
    ]);
}

// Validate MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mimeType, $allowedMimes)) {
    send_json_response(400, [
        'success' => false,
        'message' => 'Invalid file type'
    ]);
}

// Validate image
$imageInfo = @getimagesize($file['tmp_name']);
if (!$imageInfo) {
    send_json_response(400, ['success' => false, 'message' => 'Invalid image file']);
}

// Generate unique filename
$uniqueFilename = 'profile_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $extension;

// Storage path
$storagePath = UPLOAD_PATH . '/avatars';
if (!is_dir($storagePath)) {
    mkdir($storagePath, 0755, true);
}

$filePath = $storagePath . '/' . $uniqueFilename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    send_json_response(500, ['success' => false, 'message' => 'Failed to save file']);
}

// Set permissions
chmod($filePath, 0644);

// Get relative path (with ../ for pages in /pages/ directory)
$relativePath = '../storage/uploads/avatars/' . $uniqueFilename;

// Get current user info to delete old photo
$user_query = "SELECT avatar, cover_photo FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Update user record
if ($type === 'avatar') {
    $query = "UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?";
} else {
    $query = "UPDATE users SET cover_photo = ?, updated_at = NOW() WHERE id = ?";
}

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'si', $relativePath, $user_id);

if (mysqli_stmt_execute($stmt)) {
    // Delete old photo if exists
    $oldPhoto = $type === 'avatar' ? $user['avatar'] : $user['cover_photo'];
    if ($oldPhoto && file_exists(dirname(__DIR__) . '/' . $oldPhoto)) {
        unlink(dirname(__DIR__) . '/' . $oldPhoto);
    }
    
    // Update session data
    if ($type === 'avatar' && isset($_SESSION['user_data'])) {
        $_SESSION['user_data']['avatar'] = $relativePath;
    }
    
    // Log activity
    log_activity($user_id, 'photo_upload', 'Updated ' . $type . ' photo');
    
    send_json_response(200, [
        'success' => true,
        'message' => ucfirst($type) . ' photo updated successfully',
        'data' => [
            'type' => $type,
            'path' => $relativePath,
            'url' => $relativePath
        ]
    ]);
} else {
    // Delete uploaded file if database update fails
    unlink($filePath);
    send_json_response(500, ['success' => false, 'message' => 'Failed to update profile']);
}
