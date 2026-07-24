<?php
/**
 * =====================================================
 * Get Media API
 * ChatApp - Retrieve Media Files
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

$user_id = get_user_id();
$receiver_id = intval($_GET['receiver_id'] ?? 0);
$group_id = intval($_GET['group_id'] ?? 0);
$page = intval($_GET['page'] ?? 1);
$limit = intval($_GET['limit'] ?? 20);
$offset = ($page - 1) * $limit;

// Build query based on context
if ($receiver_id > 0) {
    // Get media in conversation with specific user
    $query = "SELECT m.* FROM media m 
              WHERE (m.user_id = ? AND m.receiver_id = ?) 
                 OR (m.user_id = ? AND m.receiver_id = ?)
              ORDER BY m.created_at DESC 
              LIMIT ? OFFSET ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'iiiiii', $user_id, $receiver_id, $receiver_id, $user_id, $limit, $offset);
    
    // Count total
    $count_query = "SELECT COUNT(*) as total FROM media m 
                    WHERE (m.user_id = ? AND m.receiver_id = ?) 
                       OR (m.user_id = ? AND m.receiver_id = ?)";
    $count_stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($count_stmt, 'iiii', $user_id, $receiver_id, $receiver_id, $user_id);
    
} elseif ($group_id > 0) {
    // Check if user is member of the group
    $member_query = "SELECT id FROM group_members WHERE group_id = ? AND user_id = ?";
    $member_stmt = mysqli_prepare($conn, $member_query);
    mysqli_stmt_bind_param($member_stmt, 'ii', $group_id, $user_id);
    mysqli_stmt_execute($member_stmt);
    $member_result = mysqli_stmt_get_result($member_stmt);
    
    if (mysqli_num_rows($member_result) === 0) {
        send_json_response(403, ['success' => false, 'message' => 'Access denied']);
    }
    
    // Get media in group
    $query = "SELECT m.* FROM media m 
              WHERE m.group_id = ?
              ORDER BY m.created_at DESC 
              LIMIT ? OFFSET ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'iii', $group_id, $limit, $offset);
    
    // Count total
    $count_query = "SELECT COUNT(*) as total FROM media m WHERE m.group_id = ?";
    $count_stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($count_stmt, 'i', $group_id);
    
} else {
    // Get all media uploaded by user
    $query = "SELECT m.* FROM media m 
              WHERE m.user_id = ?
              ORDER BY m.created_at DESC 
              LIMIT ? OFFSET ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'iii', $user_id, $limit, $offset);
    
    // Count total
    $count_query = "SELECT COUNT(*) as total FROM media m WHERE m.user_id = ?";
    $count_stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($count_stmt, 'i', $user_id);
}

// Execute queries
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$media = [];

while ($row = mysqli_fetch_assoc($result)) {
    $media[] = [
        'id' => $row['id'],
        'file_name' => $row['file_name'],
        'original_name' => $row['original_name'],
        'file_path' => $row['file_path'],
        'thumbnail_path' => $row['thumbnail_path'],
        'file_size' => $row['file_size'],
        'file_size_formatted' => format_file_size($row['file_size']),
        'file_type' => $row['file_type'],
        'file_extension' => $row['file_extension'],
        'category' => $row['category'],
        'icon' => get_file_icon($row['category'], $row['file_extension']),
        'is_image' => $row['category'] === 'images',
        'is_video' => $row['category'] === 'videos',
        'created_at' => $row['created_at']
    ];
}

// Get total count
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total = mysqli_fetch_assoc($count_result)['total'];

send_json_response(200, [
    'success' => true,
    'data' => [
        'media' => $media,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'pages' => ceil($total / $limit)
    ]
]);
