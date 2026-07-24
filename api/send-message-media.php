<?php
/**
 * =====================================================
 * Send Message with Media API
 * ChatApp - Send Message with File Attachment
 * =====================================================
 */

define('APP_RUNNING', true);

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/media.php';
require_once dirname(__DIR__) . '/includes/media_helpers.php';

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
$receiver_id = intval($_POST['receiver_id'] ?? 0);
$group_id = intval($_POST['group_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
$media_id = intval($_POST['media_id'] ?? 0);
$reply_to = intval($_POST['reply_to'] ?? 0);

// Validate inputs
if ($receiver_id <= 0 && $group_id <= 0) {
    send_json_response(400, ['success' => false, 'message' => 'Invalid receiver or group']);
}

if (empty($message) && $media_id <= 0) {
    send_json_response(400, ['success' => false, 'message' => 'Message or media required']);
}

// Check rate limit
if (!check_rate_limit('send_message_' . $user_id, 60, 60)) {
    send_json_response(429, ['success' => false, 'message' => 'Too many messages. Please wait.']);
}

// Handle file upload if present
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['file'];
    
    // Get file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check if extension is allowed
    if (!is_extension_allowed($extension)) {
        send_json_response(400, [
            'success' => false,
            'message' => 'File type not allowed'
        ]);
    }
    
    // Get file category
    $category = get_file_category($extension);
    
    // Check file size
    $maxSize = SIZE_LIMITS[$category] ?? MAX_FILE_SIZE;
    if ($file['size'] > $maxSize) {
        send_json_response(400, [
            'success' => false,
            'message' => 'File too large. Maximum size: ' . format_file_size($maxSize)
        ]);
    }
    
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!is_mime_allowed($mimeType, $category)) {
        send_json_response(400, [
            'success' => false,
            'message' => 'Invalid file type'
        ]);
    }
    
    // Generate unique filename
    $uniqueFilename = generate_unique_filename($file['name']);
    
    // Get storage path
    $storagePath = get_storage_path($category);
    
    // Create directory if needed
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
    
    // Create thumbnail for images
    $thumbnailPath = null;
    if ($category === 'images') {
        $thumbnailPath = THUMBNAIL_PATH . '/thumb_' . $uniqueFilename;
        create_thumbnail($filePath, $thumbnailPath);
    }
    
    // Store in database
    $relativePath = 'storage/uploads/' . $category . '/' . $uniqueFilename;
    $thumbnailRelativePath = $thumbnailPath ? 'storage/thumbnails/thumb_' . $uniqueFilename : null;
    
    $query = "INSERT INTO media (user_id, file_name, original_name, file_path, thumbnail_path, file_size, file_type, file_extension, category, receiver_id, group_id, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'issssssiiis',
        $user_id,
        $uniqueFilename,
        $file['name'],
        $relativePath,
        $thumbnailRelativePath,
        $file['size'],
        $mimeType,
        $extension,
        $category,
        $receiver_id,
        $group_id
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $media_id = mysqli_insert_id($conn);
    } else {
        unlink($filePath);
        if ($thumbnailPath && file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
        send_json_response(500, ['success' => false, 'message' => 'Failed to save file record']);
    }
}

// Validate reply_to if provided
if ($reply_to > 0) {
    if ($group_id > 0) {
        $reply_query = "SELECT id FROM group_messages WHERE id = ? AND group_id = ?";
        $reply_stmt = mysqli_prepare($conn, $reply_query);
        mysqli_stmt_bind_param($reply_stmt, 'ii', $reply_to, $group_id);
    } else {
        $reply_query = "SELECT id FROM messages WHERE id = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))";
        $reply_stmt = mysqli_prepare($conn, $reply_query);
        mysqli_stmt_bind_param($reply_stmt, 'iiiii', $reply_to, $user_id, $receiver_id, $receiver_id, $user_id);
    }
    
    mysqli_stmt_execute($reply_stmt);
    $reply_result = mysqli_stmt_get_result($reply_stmt);
    
    if (mysqli_num_rows($reply_result) === 0) {
        send_json_response(400, ['success' => false, 'message' => 'Invalid reply target']);
    }
}

// Insert message
if ($group_id > 0) {
    // Check if user is member of the group
    $member_query = "SELECT id FROM group_members WHERE group_id = ? AND user_id = ?";
    $member_stmt = mysqli_prepare($conn, $member_query);
    mysqli_stmt_bind_param($member_stmt, 'ii', $group_id, $user_id);
    mysqli_stmt_execute($member_stmt);
    $member_result = mysqli_stmt_get_result($member_stmt);
    
    if (mysqli_num_rows($member_result) === 0) {
        send_json_response(403, ['success' => false, 'message' => 'Not a member of this group']);
    }
    
    // Insert group message
    $query = "INSERT INTO group_messages (group_id, user_id, message, media_id, reply_to, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'iisii', $group_id, $user_id, $message, $media_id, $reply_to);
    
} else {
    // Check if users are friends or have existing conversation
    $friend_query = "SELECT id FROM friendships WHERE 
                     ((requester_id = ? AND receiver_id = ?) OR (requester_id = ? AND receiver_id = ?)) 
                     AND status = 'accepted'";
    $friend_stmt = mysqli_prepare($conn, $friend_query);
    mysqli_stmt_bind_param($friend_stmt, 'iiii', $user_id, $receiver_id, $receiver_id, $user_id);
    mysqli_stmt_execute($friend_stmt);
    $friend_result = mysqli_stmt_get_result($friend_stmt);
    
    $conv_query = "SELECT id FROM messages WHERE 
                   (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) LIMIT 1";
    $conv_stmt = mysqli_prepare($conn, $conv_query);
    mysqli_stmt_bind_param($conv_stmt, 'iiii', $user_id, $receiver_id, $receiver_id, $user_id);
    mysqli_stmt_execute($conv_stmt);
    $conv_result = mysqli_stmt_get_result($conv_stmt);
    
    if (mysqli_num_rows($friend_result) === 0 && mysqli_num_rows($conv_result) === 0) {
        send_json_response(403, ['success' => false, 'message' => 'Cannot send message to this user']);
    }
    
    // Insert personal message
    $query = "INSERT INTO messages (sender_id, receiver_id, message, media_id, reply_to, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'iisii', $user_id, $receiver_id, $message, $media_id, $reply_to);
}

if (mysqli_stmt_execute($stmt)) {
    $message_id = mysqli_insert_id($conn);
    
    // Get media data if attached
    $media_data = null;
    if ($media_id > 0) {
        $media_query = "SELECT * FROM media WHERE id = ?";
        $media_stmt = mysqli_prepare($conn, $media_query);
        mysqli_stmt_bind_param($media_stmt, 'i', $media_id);
        mysqli_stmt_execute($media_stmt);
        $media_result = mysqli_stmt_get_result($media_stmt);
        $media_data = mysqli_fetch_assoc($media_result);
        
        if ($media_data) {
            $media_data['file_size_formatted'] = format_file_size($media_data['file_size']);
            $media_data['icon'] = get_file_icon($media_data['category'], $media_data['file_extension']);
            $media_data['is_image'] = $media_data['category'] === 'images';
            $media_data['is_video'] = $media_data['category'] === 'videos';
        }
    }
    
    // Get sender info
    $sender_query = "SELECT username, avatar FROM users WHERE id = ?";
    $sender_stmt = mysqli_prepare($conn, $sender_query);
    mysqli_stmt_bind_param($sender_stmt, 'i', $user_id);
    mysqli_stmt_execute($sender_stmt);
    $sender_result = mysqli_stmt_get_result($sender_stmt);
    $sender = mysqli_fetch_assoc($sender_result);
    
    send_json_response(200, [
        'success' => true,
        'message' => 'Message sent successfully',
        'data' => [
            'id' => $message_id,
            'sender_id' => $user_id,
            'sender_name' => $sender['username'],
            'sender_avatar' => $sender['avatar'],
            'receiver_id' => $receiver_id,
            'group_id' => $group_id,
            'message' => $message,
            'media_id' => $media_id,
            'media' => $media_data,
            'reply_to' => $reply_to,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    send_json_response(500, ['success' => false, 'message' => 'Failed to send message']);
}
