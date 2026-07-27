<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Media Download API
 * ChatApp - Secure File Download Handler
 * =====================================================
 */

define('APP_RUNNING', true);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/compat.php';
require_once dirname(__DIR__) . '/config/media.php';

init_session();

// Check if user is logged in
if (!is_logged_in()) {
    header('HTTP/1.1 401 Unauthorized');
    die('Unauthorized');
}

// Get media ID from query string
$media_id = intval($_GET['id'] ?? 0);

if ($media_id <= 0) {
    header('HTTP/1.1 400 Bad Request');
    die('Invalid media ID');
}

// Get file info from database
$query = "SELECT * FROM media WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $media_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$media = mysqli_fetch_assoc($result);

if (!$media) {
    header('HTTP/1.1 404 Not Found');
    die('File not found');
}

$user_id = get_user_id();

// Check if user has access to this file
$has_access = false;

if ($media['user_id'] == $user_id) {
    // User uploaded the file
    $has_access = true;
} elseif ($media['receiver_id'] > 0 && $media['receiver_id'] == $user_id) {
    // File was sent to this user
    $has_access = true;
} elseif ($media['group_id'] > 0) {
    // Check if user is member of the group
    $group_query = "SELECT id FROM group_members WHERE group_id = ? AND user_id = ?";
    $group_stmt = mysqli_prepare($conn, $group_query);
    mysqli_stmt_bind_param($group_stmt, 'ii', $media['group_id'], $user_id);
    mysqli_stmt_execute($group_stmt);
    $group_result = mysqli_stmt_get_result($group_stmt);
    $has_access = mysqli_num_rows($group_result) > 0;
} else {
    // Check if user is in a conversation with the sender
    $conv_query = "SELECT id FROM messages WHERE 
                   (sender_id = ? AND receiver_id = ?) OR 
                   (sender_id = ? AND receiver_id = ?) LIMIT 1";
    $conv_stmt = mysqli_prepare($conn, $conv_query);
    mysqli_stmt_bind_param($conv_stmt, 'iiii', $media['user_id'], $user_id, $user_id, $media['user_id']);
    mysqli_stmt_execute($conv_stmt);
    $conv_result = mysqli_stmt_get_result($conv_stmt);
    $has_access = mysqli_num_rows($conv_result) > 0;
}

if (!$has_access) {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied');
}

// Get the full file path
$filePath = dirname(__DIR__) . '/' . $media['file_path'];

// Check if file exists
if (!file_exists($filePath)) {
    header('HTTP/1.1 404 Not Found');
    die('File not found on server');
}

// Get file info
$fileSize = filesize($filePath);
$mimeType = $media['file_type'] ?: 'application/octet-stream';
$fileName = $media['original_name'];

// Set headers for download
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Output file
readfile($filePath);
exit;
