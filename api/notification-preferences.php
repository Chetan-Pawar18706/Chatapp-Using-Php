<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Notification Preferences API
 * ChatApp - Update User Notification Settings
 * =====================================================
 */

define('APP_RUNNING', true);

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/notification_helpers.php';

init_session();

// Check if user is logged in
if (!is_logged_in()) {
    send_json_response(401, ['success' => false, 'message' => 'Unauthorized']);
}

$user_id = get_user_id();

// Handle GET - fetch preferences
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT * FROM notification_preferences WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $prefs = mysqli_fetch_assoc($result);
    
    if (!$prefs) {
        // Return defaults
        $prefs = [
            'friend_requests' => 1,
            'messages' => 1,
            'mentions' => 1,
            'group_invites' => 1,
            'group_messages' => 1,
            'system' => 1,
            'email_notifications' => 0,
            'push_notifications' => 1,
            'sound_enabled' => 1
        ];
    }
    
    send_json_response(200, [
        'success' => true,
        'data' => $prefs
    ]);
}

// Handle POST - update preferences
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        send_json_response(403, ['success' => false, 'message' => 'Invalid CSRF token']);
    }
    
    // Get preferences
    $friend_requests = isset($_POST['friend_requests']) ? 1 : 0;
    $messages = isset($_POST['messages']) ? 1 : 0;
    $mentions = isset($_POST['mentions']) ? 1 : 0;
    $group_invites = isset($_POST['group_invites']) ? 1 : 0;
    $group_messages = isset($_POST['group_messages']) ? 1 : 0;
    $system = isset($_POST['system']) ? 1 : 0;
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;
    $sound_enabled = isset($_POST['sound_enabled']) ? 1 : 0;
    
    // Upsert preferences
    $query = "INSERT INTO notification_preferences 
              (user_id, friend_requests, messages, mentions, group_invites, group_messages, system, email_notifications, push_notifications, sound_enabled, created_at, updated_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
              ON DUPLICATE KEY UPDATE 
              friend_requests = VALUES(friend_requests),
              messages = VALUES(messages),
              mentions = VALUES(mentions),
              group_invites = VALUES(group_invites),
              group_messages = VALUES(group_messages),
              system = VALUES(system),
              email_notifications = VALUES(email_notifications),
              push_notifications = VALUES(push_notifications),
              sound_enabled = VALUES(sound_enabled),
              updated_at = NOW()";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'iiiiiiiii', 
        $user_id, 
        $friend_requests, 
        $messages, 
        $mentions, 
        $group_invites, 
        $group_messages, 
        $system, 
        $email_notifications, 
        $push_notifications, 
        $sound_enabled
    );
    
    if (mysqli_stmt_execute($stmt)) {
        send_json_response(200, [
            'success' => true,
            'message' => 'Notification preferences updated'
        ]);
    } else {
        send_json_response(500, ['success' => false, 'message' => 'Failed to update preferences']);
    }
}
