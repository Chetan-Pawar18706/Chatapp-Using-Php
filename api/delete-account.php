<?php
/**
 * =====================================================
 * Delete Account API
 * ChatApp - Permanent Account Deletion
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
if (!check_rate_limit('delete_account_' . $user_id, 1, 60)) {
    send_json_response(429, ['success' => false, 'message' => 'Too many attempts. Please wait.']);
}

// Get confirmation text
$confirm_text = trim($_POST['confirm_text'] ?? '');

if ($confirm_text !== 'DELETE') {
    send_json_response(400, ['success' => false, 'message' => 'Please type DELETE to confirm']);
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get user files for deletion
    $media_query = "SELECT file_path, thumbnail_path FROM media WHERE user_id = ?";
    $media_stmt = mysqli_prepare($conn, $media_query);
    mysqli_stmt_bind_param($media_stmt, 'i', $user_id);
    mysqli_stmt_execute($media_stmt);
    $media_result = mysqli_stmt_get_result($media_stmt);
    
    $files_to_delete = [];
    while ($media = mysqli_fetch_assoc($media_result)) {
        $files_to_delete[] = dirname(__DIR__) . '/' . $media['file_path'];
        if ($media['thumbnail_path']) {
            $files_to_delete[] = dirname(__DIR__) . '/' . $media['thumbnail_path'];
        }
    }
    
    // Get user avatar and cover photo
    $user_query = "SELECT avatar, cover_photo FROM users WHERE id = ?";
    $user_stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user = mysqli_fetch_assoc($user_result);
    
    if ($user['avatar']) {
        $files_to_delete[] = dirname(__DIR__) . '/' . $user['avatar'];
    }
    if ($user['cover_photo']) {
        $files_to_delete[] = dirname(__DIR__) . '/' . $user['cover_photo'];
    }
    
    // Delete in order (foreign keys will handle most)
    $tables = [
        'group_messages_read',
        'group_notifications',
        'typing_status',
        'block_list',
        'media',
        'group_members',
        'messages',
        'friendships',
        'user_sessions',
        'activity_log',
        'rate_limits'
    ];
    
    foreach ($tables as $table) {
        $delete_query = "DELETE FROM $table WHERE user_id = ? OR created_by = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, 'ii', $user_id, $user_id);
        mysqli_stmt_execute($delete_stmt);
    }
    
    // Delete user
    $delete_user_query = "DELETE FROM users WHERE id = ?";
    $delete_user_stmt = mysqli_prepare($conn, $delete_user_query);
    mysqli_stmt_bind_param($delete_user_stmt, 'i', $user_id);
    mysqli_stmt_execute($delete_user_stmt);
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Delete physical files
    foreach ($files_to_delete as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    // Log activity (before session destroy)
    log_activity($user_id, 'account_deleted', 'Account permanently deleted');
    
    // Destroy session
    app_session_destroy();
    
    send_json_response(200, [
        'success' => true,
        'message' => 'Account deleted successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    
    send_json_response(500, [
        'success' => false,
        'message' => 'Failed to delete account'
    ]);
}
