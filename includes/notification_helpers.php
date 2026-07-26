<?php
/**
 * =====================================================
 * Notification Helper Functions
 * ChatApp - Notification System
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

/**
 * Create a new notification
 * 
 * @param int $user_id Recipient user ID
 * @param int|null $sender_id Sender user ID
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string|null $message Notification message
 * @param array|null $data Additional data
 * @return int|false Notification ID or false
 */
function create_notification($user_id, $sender_id, $type, $title, $message = null, $data = null) {
    $conn = db_connect();
    
    // Don't notify yourself
    if ($user_id == $sender_id) {
        return false;
    }
    
    // Check user preferences
    if (!notification_enabled_for_user($user_id, $type)) {
        return false;
    }
    
    $query = "INSERT INTO notifications (user_id, sender_id, type, title, message, data, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $query);
    $jsonData = $data ? json_encode($data) : null;
    mysqli_stmt_bind_param($stmt, 'iissss', $user_id, $sender_id, $type, $title, $message, $jsonData);
    
    if (mysqli_stmt_execute($stmt)) {
        return mysqli_insert_id($conn);
    }
    
    return false;
}

/**
 * Check if notification type is enabled for user
 * 
 * @param int $user_id User ID
 * @param string $type Notification type
 * @return bool True if enabled
 */
function notification_enabled_for_user($user_id, $type) {
    $conn = db_connect();
    
    $query = "SELECT * FROM notification_preferences WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $prefs = mysqli_fetch_assoc($result);
    
    if (!$prefs) {
        return true; // Default to enabled
    }
    
    $typeMap = [
        'friend_request' => 'friend_requests',
        'friend_accept' => 'friend_requests',
        'message' => 'messages',
        'mention' => 'mentions',
        'group_invite' => 'group_invites',
        'group_join' => 'group_messages',
        'group_leave' => 'group_messages',
        'group_remove' => 'group_messages',
        'group_role_change' => 'group_messages',
        'system' => 'system'
    ];
    
    $prefKey = $typeMap[$type] ?? $type;
    
    return isset($prefs[$prefKey]) ? (bool)$prefs[$prefKey] : true;
}

/**
 * Get unread notification count for user
 * 
 * @param int $user_id User ID
 * @return int Unread count
 */
function get_unread_notification_count($user_id) {
    $conn = db_connect();
    
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    return (int)($row['count'] ?? 0);
}

/**
 * Get user notifications
 * 
 * @param int $user_id User ID
 * @param int $limit Number of notifications to get
 * @param int $offset Offset for pagination
 * @param bool $unreadOnly Get only unread notifications
 * @return array Notifications
 */
function get_user_notifications($user_id, $limit = 20, $offset = 0, $unreadOnly = false) {
    $conn = db_connect();
    
    $where = "n.user_id = ?";
    if ($unreadOnly) {
        $where .= " AND n.is_read = 0";
    }
    
    $query = "SELECT n.*, 
              u.username as sender_username, 
              u.avatar as sender_avatar
              FROM notifications n
              LEFT JOIN users u ON n.sender_id = u.id
              WHERE $where
              ORDER BY n.created_at DESC
              LIMIT ? OFFSET ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'iii', $user_id, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['data'] = json_decode($row['data'], true);
        $row['time_ago'] = time_elapsed_string($row['created_at']);
        $notifications[] = $row;
    }
    
    return $notifications;
}

/**
 * Mark notification as read
 * 
 * @param int $notification_id Notification ID
 * @param int $user_id User ID (for security)
 * @return bool Success
 */
function mark_notification_read($notification_id, $user_id) {
    $conn = db_connect();
    
    $query = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $notification_id, $user_id);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Mark all notifications as read for user
 * 
 * @param int $user_id User ID
 * @return bool Success
 */
function mark_all_notifications_read($user_id) {
    $conn = db_connect();
    
    $query = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Delete a notification
 * 
 * @param int $notification_id Notification ID
 * @param int $user_id User ID (for security)
 * @return bool Success
 */
function delete_notification($notification_id, $user_id) {
    $conn = db_connect();
    
    $query = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $notification_id, $user_id);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Delete all notifications for user
 * 
 * @param int $user_id User ID
 * @return bool Success
 */
function clear_all_notifications($user_id) {
    $conn = db_connect();
    
    $query = "DELETE FROM notifications WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Get notification icon based on type
 * 
 * @param string $type Notification type
 * @return string Font Awesome icon class
 */
function get_notification_icon($type) {
    $icons = [
        'friend_request' => 'fa-user-plus',
        'friend_accept' => 'fa-user-check',
        'message' => 'fa-comment',
        'mention' => 'fa-at',
        'group_invite' => 'fa-users',
        'group_join' => 'fa-sign-in-alt',
        'group_leave' => 'fa-sign-out-alt',
        'group_remove' => 'fa-user-minus',
        'group_role_change' => 'fa-crown',
        'system' => 'fa-bell'
    ];
    
    return $icons[$type] ?? 'fa-bell';
}

/**
 * Get notification color based on type
 * 
 * @param string $type Notification type
 * @return string Color class
 */
function get_notification_color($type) {
    $colors = [
        'friend_request' => 'primary',
        'friend_accept' => 'success',
        'message' => 'info',
        'mention' => 'warning',
        'group_invite' => 'purple',
        'group_join' => 'success',
        'group_leave' => 'secondary',
        'group_remove' => 'danger',
        'group_role_change' => 'warning',
        'system' => 'primary'
    ];
    
    return $colors[$type] ?? 'primary';
}

/**
 * Format notification message
 * 
 * @param array $notification Notification data
 * @return string Formatted message
 */
function format_notification_message($notification) {
    $sender = htmlspecialchars($notification['sender_username'] ?? 'System');
    
    switch ($notification['type']) {
        case 'friend_request':
            return "$sender sent you a friend request";
            
        case 'friend_accept':
            return "$sender accepted your friend request";
            
        case 'message':
            return "$sender sent you a message";
            
        case 'mention':
            return "$sender mentioned you";
            
        case 'group_invite':
            return "$sender invited you to a group";
            
        case 'group_join':
            return "$sender joined the group";
            
        case 'group_leave':
            return "$sender left the group";
            
        case 'group_remove':
            return "You were removed from the group";
            
        case 'group_role_change':
            return "Your role was changed in the group";
            
        case 'system':
            return $notification['message'] ?? 'New notification';
            
        default:
            return $notification['message'] ?? 'New notification';
    }
}

/**
 * Time elapsed string helper
 * 
 * @param string $datetime DateTime string
 * @return string Time ago string
 */
if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime) {
        $now = new DateTime();
        $past = new DateTime($datetime);
        $diff = $now->diff($past);
        
        if ($diff->y > 0) {
            return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        } elseif ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        } elseif ($diff->d > 0) {
            if ($diff->d >= 7) {
                return floor($diff->d / 7) . ' week' . (floor($diff->d / 7) > 1 ? 's' : '') . ' ago';
            }
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'Just now';
        }
    }
}
