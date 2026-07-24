<?php
/**
 * =====================================================
 * Export Data API
 * ChatApp - Export User Data
 * =====================================================
 */

define('APP_RUNNING', true);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';

init_session();

// Check if user is logged in
if (!is_logged_in()) {
    send_json_response(401, ['success' => false, 'message' => 'Unauthorized']);
}

$user_id = get_user_id();
$type = $_GET['type'] ?? 'all';

// Rate limit check
if (!check_rate_limit('export_' . $user_id, 5, 3600)) {
    send_json_response(429, ['success' => false, 'message' => 'Too many export requests. Please try again later.']);
}

// Get user data
$user_query = "SELECT id, username, email, bio, about, friend_code, created_at, theme, language, status FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

$exportData = [
    'export_date' => date('Y-m-d H:i:s'),
    'user' => $user
];

// Export based on type
switch ($type) {
    case 'messages':
        $exportData['messages'] = exportMessages($user_id);
        break;
        
    case 'media':
        $exportData['media'] = exportMedia($user_id);
        break;
        
    case 'all':
    default:
        $exportData['messages'] = exportMessages($user_id);
        $exportData['media'] = exportMedia($user_id);
        $exportData['friends'] = exportFriends($user_id);
        $exportData['groups'] = exportGroups($user_id);
        break;
}

// Create JSON file
$jsonContent = json_encode($exportData, JSON_PRETTY_PRINT);
$filename = 'chatapp-export-' . $type . '-' . date('Y-m-d') . '.json';
$tempFile = sys_get_temp_dir() . '/' . $filename;
file_put_contents($tempFile, $jsonContent);

// Log activity
log_activity($user_id, 'data_export', "Exported $type data");

// Send file
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tempFile));
header('Cache-Control: no-cache, must-revalidate');

readfile($tempFile);
unlink($tempFile);
exit;

/**
 * Export Messages
 */
function exportMessages($user_id) {
    global $conn;
    
    $query = "SELECT m.*, 
              sender.username as sender_name,
              receiver.username as receiver_name,
              g.name as group_name
              FROM messages m
              LEFT JOIN users sender ON m.sender_id = sender.id
              LEFT JOIN users receiver ON m.receiver_id = receiver.id
              LEFT JOIN groups g ON m.group_id = g.id
              WHERE m.sender_id = ? OR m.receiver_id = ?
              ORDER BY m.created_at ASC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = [
            'id' => $row['id'],
            'from' => $row['sender_name'],
            'to' => $row['receiver_name'] ?? $row['group_name'],
            'content' => $row['content'],
            'type' => $row['message_type'],
            'date' => $row['created_at']
        ];
    }
    
    return $messages;
}

/**
 * Export Media
 */
function exportMedia($user_id) {
    global $conn;
    
    $query = "SELECT * FROM media WHERE user_id = ? ORDER BY created_at ASC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $media = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $media[] = [
            'id' => $row['id'],
            'filename' => $row['original_name'],
            'type' => $row['file_type'],
            'category' => $row['category'],
            'size' => $row['file_size'],
            'uploaded' => $row['created_at']
        ];
    }
    
    return $media;
}

/**
 * Export Friends
 */
function exportFriends($user_id) {
    global $conn;
    
    $query = "SELECT u.username, u.email, f.created_at as friends_since
              FROM friendships f
              JOIN users u ON (f.friend_id = u.id)
              WHERE f.user_id = ? AND f.status = 'accepted'
              UNION
              SELECT u.username, u.email, f.created_at as friends_since
              FROM friendships f
              JOIN users u ON (f.user_id = u.id)
              WHERE f.friend_id = ? AND f.status = 'accepted'";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $friends = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $friends[] = [
            'username' => $row['username'],
            'friends_since' => $row['friends_since']
        ];
    }
    
    return $friends;
}

/**
 * Export Groups
 */
function exportGroups($user_id) {
    global $conn;
    
    $query = "SELECT g.id, g.name, g.description, gm.role, gm.joined_at
              FROM groups g
              JOIN group_members gm ON g.id = gm.group_id
              WHERE gm.user_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $groups = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $groups[] = [
            'name' => $row['name'],
            'description' => $row['description'],
            'role' => $row['role'],
            'joined' => $row['joined_at']
        ];
    }
    
    return $groups;
}
