<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Send Message
 * ChatApp - POST /api/send-message.php
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

session_initialize();

if (!session_verify_security() || !session_is_logged_in()) {
    send_error('Unauthorized', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Method not allowed', 405);
}

// Check rate limit for messaging
$client_ip = get_client_ip();
if (class_exists('Security')) {
    $security = Security::getInstance();
    if (!$security->checkRateLimit($client_ip, 'message', 60)) {
        send_error('Too many requests. Please slow down.', 429);
    }
} else {
    if (!check_rate_limit($client_ip, 'message', 60, 60)) {
        send_error('Too many requests. Please slow down.', 429);
    }
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$receiver_id = (int)($input['receiver_id'] ?? 0);
$content = trim($input['content'] ?? '');
$message_type = $input['message_type'] ?? 'text';
$reply_to_id = !empty($input['reply_to_id']) ? (int)$input['reply_to_id'] : null;
$auto_delete = $input['auto_delete'] ?? '12hours';
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

// Validate auto_delete value
$valid_auto_delete = ['view_once', '12hours'];
if (!in_array($auto_delete, $valid_auto_delete)) {
    $auto_delete = '12hours';
}

if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();

// Validate input using Security class
if (class_exists('Security')) {
    $security = Security::getInstance();
    
    if (!$receiver_id) {
        send_error('Receiver ID is required');
    }
    
    if (empty($content)) {
        send_error('Message content is required');
    }
    
    $msgValidation = $security->validateInput($content, 'message');
    if (!$msgValidation['valid']) {
        send_error($msgValidation['error']);
    }
    
    // Check for suspicious content
    if ($security->containsSuspiciousContent($content)) {
        send_error('Message contains prohibited content');
    }
} else {
    // Fallback validation
    if (!$receiver_id) {
        send_error('Receiver ID is required');
    }
    
    if (empty($content)) {
        send_error('Message content is required');
    }
    
    if (strlen($content) > 5000) {
        send_error('Message too long (max 5000 characters)');
    }
}

// Verify friendship
$friendship_sql = "SELECT id FROM friendships 
                   WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))
                   AND status = 'accepted' LIMIT 1";
$friendship = db_fetch_single($friendship_sql, [$user_id, $receiver_id, $receiver_id, $user_id], 'iiii');

if (!$friendship) {
    send_error('You are not friends with this user');
}

// Check if blocked
$block_sql = "SELECT id FROM block_list 
              WHERE (user_id = ? AND blocked_user_id = ?) 
              OR (user_id = ? AND blocked_user_id = ?) 
              LIMIT 1";
$blocked = db_fetch_single($block_sql, [$user_id, $receiver_id, $receiver_id, $user_id], 'iiii');

if ($blocked) {
    send_error('Unable to send message');
}

// Validate reply_to if provided
if ($reply_to_id) {
    $reply_check_sql = "SELECT id FROM messages WHERE id = ? 
                       AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))";
    $reply_check = db_fetch_single($reply_check_sql, [$reply_to_id, $user_id, $receiver_id, $receiver_id, $user_id], 'iiii');
    
    if (!$reply_check) {
        $reply_to_id = null; // Invalid reply, set to null
    }
}

// Check if auto_delete column exists
$has_auto_delete = false;
try {
    $check_col = db_fetch_single("SHOW COLUMNS FROM messages LIKE 'auto_delete'", [], '');
    $has_auto_delete = (bool)$check_col;
} catch (Exception $e) {
    $has_auto_delete = false;
}

// Encrypt message content
$encrypted_content = encrypt_message($content);

// Insert message
if ($has_auto_delete) {
    $sql = "INSERT INTO messages (sender_id, receiver_id, content, message_type, reply_to_id, auto_delete, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $result = db_execute($sql, [$user_id, $receiver_id, $encrypted_content, $message_type, $reply_to_id, $auto_delete], 'iissss');
} else {
    $sql = "INSERT INTO messages (sender_id, receiver_id, content, message_type, reply_to_id, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $result = db_execute($sql, [$user_id, $receiver_id, $encrypted_content, $message_type, $reply_to_id], 'iisss');
}

if ($result) {
    $message_id = db_insert_id();
    
    // Create notification for receiver
    require_once __DIR__ . '/../includes/notification_helpers.php';
    create_notification(
        $receiver_id,
        $user_id,
        'message',
        'New Message',
        null,
        ['message_id' => $message_id, 'sender_id' => $user_id]
    );
    
    // Get the inserted message with full details
    $get_msg_sql = "SELECT m.*, su.username as sender_name, su.avatar as sender_avatar
                    FROM messages m
                    LEFT JOIN users su ON m.sender_id = su.id
                    WHERE m.id = ? LIMIT 1";
    $message = db_fetch_single($get_msg_sql, [$message_id], 'i');
    
    // Build reply data
    $reply_data = null;
    if ($reply_to_id && $message) {
        $reply_sql = "SELECT rm.content, ru.username as sender_name 
                     FROM messages rm 
                     LEFT JOIN users ru ON rm.sender_id = ru.id
                     WHERE rm.id = ? LIMIT 1";
        $reply_msg = db_fetch_single($reply_sql, [$reply_to_id], 'i');
        if ($reply_msg) {
            $reply_data = [
                'id' => $reply_to_id,
                'content' => decrypt_message($reply_msg['content']),
                'sender_name' => $reply_msg['sender_name']
            ];
        }
    }
    
    // Update typing status to not typing
    $typing_sql = "UPDATE typing_status SET is_typing = 0 
                   WHERE user_id = ? AND chat_with_user_id = ?";
    db_execute($typing_sql, [$user_id, $receiver_id], 'ii');
    
    log_activity($user_id, 'message_sent', [
        'receiver_id' => $receiver_id,
        'message_id' => $message_id
    ]);
    
    $formatted_message = [
        'id' => (int)$message_id,
        'sender_id' => (int)$user_id,
        'content' => $content,
        'message_type' => $message_type,
        'is_sender' => true,
        'is_deleted' => false,
        'status' => 'sent',
        'reply_to' => $reply_data,
        'timestamp' => format_date($message['created_at'] ?? date('Y-m-d H:i:s'), 'h:i A'),
        'date' => format_date($message['created_at'] ?? date('Y-m-d H:i:s'), 'M d, Y')
    ];
    
    send_success('Message sent', ['message' => $formatted_message]);
} else {
    send_error('Failed to send message');
}
