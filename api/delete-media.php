<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Media Delete API
 * ChatApp - Secure File Deletion Handler
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

// Only accept POST or DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    send_json_response(405, ['success' => false, 'message' => 'Method not allowed']);
}

// Verify CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        send_json_response(403, ['success' => false, 'message' => 'Invalid CSRF token']);
    }
}

// Get media ID
$media_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

if ($media_id <= 0) {
    send_json_response(400, ['success' => false, 'message' => 'Invalid media ID']);
}

// Get file info from database
$query = "SELECT * FROM media WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $media_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$media = mysqli_fetch_assoc($result);

if (!$media) {
    send_json_response(404, ['success' => false, 'message' => 'File not found']);
}

$user_id = get_user_id();

// Check if user has permission to delete this file
$can_delete = false;

if ($media['user_id'] == $user_id) {
    // User uploaded the file
    $can_delete = true;
} elseif ($media['group_id'] > 0) {
    // Check if user is admin or moderator of the group
    $role_query = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ?";
    $role_stmt = mysqli_prepare($conn, $role_query);
    mysqli_stmt_bind_param($role_stmt, 'ii', $media['group_id'], $user_id);
    mysqli_stmt_execute($role_stmt);
    $role_result = mysqli_stmt_get_result($role_stmt);
    $member = mysqli_fetch_assoc($role_result);
    
    if ($member && in_array($member['role'], ['admin', 'moderator'])) {
        $can_delete = true;
    }
}

if (!$can_delete) {
    send_json_response(403, ['success' => false, 'message' => 'Permission denied']);
}

// Get file paths before deletion
$filePath = dirname(__DIR__) . '/' . $media['file_path'];
$thumbnailPath = $media['thumbnail_path'] ? dirname(__DIR__) . '/' . $media['thumbnail_path'] : null;

// Delete file from database
$delete_query = "DELETE FROM media WHERE id = ?";
$delete_stmt = mysqli_prepare($conn, $delete_query);
mysqli_stmt_bind_param($delete_stmt, 'i', $media_id);

if (mysqli_stmt_execute($delete_stmt)) {
    // Delete physical files
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    if ($thumbnailPath && file_exists($thumbnailPath)) {
        unlink($thumbnailPath);
    }
    
    // Log activity
    log_activity($user_id, 'media_delete', "Deleted file: " . $media['original_name']);
    
    send_json_response(200, [
        'success' => true,
        'message' => 'File deleted successfully'
    ]);
} else {
    send_json_response(500, ['success' => false, 'message' => 'Failed to delete file record']);
}
