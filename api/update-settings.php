<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Update Settings API
 * ChatApp - Appearance, Notifications, Privacy
 * =====================================================
 */

define('APP_RUNNING', true);

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';

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

// Get form data
$theme = $_POST['theme'] ?? null;
$message_style = $_POST['message_style'] ?? null;
$compact_mode = isset($_POST['compact_mode']) ? 1 : null;
$show_status = isset($_POST['show_status']) ? 1 : null;

// Notification settings
$notify_messages = isset($_POST['notify_messages']) ? 1 : null;
$notify_friends = isset($_POST['notify_friend_requests']) ? 1 : null;
$notify_groups = isset($_POST['notify_groups']) ? 1 : null;
$notify_sound = isset($_POST['notify_sound']) ? 1 : null;
$email_messages = isset($_POST['email_messages']) ? 1 : null;
$email_security = isset($_POST['email_security']) ? 1 : null;

// Privacy settings
$profile_visibility = $_POST['who_can_see_profile'] ?? null;
$online_status = $_POST['who_can_see_status'] ?? null;
$allow_friend_requests = isset($_POST['allow_friend_requests']) ? 1 : null;
$allow_messages = $_POST['who_can_message'] ?? null;
$show_read_receipts = isset($_POST['show_read_receipts']) ? 1 : null;
$show_typing = isset($_POST['show_typing']) ? 1 : null;

// Build update query based on provided fields
$updates = [];
$params = [];
$types = '';

if ($theme !== null) {
    $valid_themes = ['dark', 'light', 'midnight', 'ocean'];
    if (in_array($theme, $valid_themes)) {
        $updates[] = 'theme = ?';
        $params[] = $theme;
        $types .= 's';
    }
}

// Store all non-column settings in JSON settings column
$extra_settings = [];
if ($message_style !== null) {
    $valid_styles = ['bubbles', 'flat'];
    if (in_array($message_style, $valid_styles)) {
        $extra_settings['message_style'] = $message_style;
    }
}
if ($compact_mode !== null) {
    $extra_settings['compact_mode'] = $compact_mode;
}
if ($show_status !== null) {
    $extra_settings['show_online_status'] = $show_status;
}

// Notification settings (store as JSON in settings column)
$notification_settings = [];
if ($notify_messages !== null) $notification_settings['notify_messages'] = $notify_messages;
if ($notify_friends !== null) $notification_settings['notify_friend_requests'] = $notify_friends;
if ($notify_groups !== null) $notification_settings['notify_groups'] = $notify_groups;
if ($notify_sound !== null) $notification_settings['notify_sound'] = $notify_sound;
if ($email_messages !== null) $notification_settings['email_messages'] = $email_messages;
if ($email_security !== null) $notification_settings['email_security'] = $email_security;

// Privacy settings
$privacy_settings = [];
if ($profile_visibility !== null) $privacy_settings['profile_visibility'] = $profile_visibility;
if ($online_status !== null) $privacy_settings['online_status'] = $online_status;
if ($allow_friend_requests !== null) $privacy_settings['allow_friend_requests'] = $allow_friend_requests;
if ($allow_messages !== null) $privacy_settings['allow_messages'] = $allow_messages;
if ($show_read_receipts !== null) $privacy_settings['show_read_receipts'] = $show_read_receipts;
if ($show_typing !== null) $privacy_settings['show_typing'] = $show_typing;

// Store settings as JSON
if (!empty($notification_settings) || !empty($privacy_settings) || !empty($extra_settings)) {
    // Get current settings
    $query = "SELECT settings FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $current = mysqli_fetch_assoc($result);
    
    $settings = json_decode($current['settings'] ?? '{}', true) ?: [];
    
    if (!empty($notification_settings)) {
        $settings['notifications'] = array_merge($settings['notifications'] ?? [], $notification_settings);
    }
    
    if (!empty($privacy_settings)) {
        $settings['privacy'] = array_merge($settings['privacy'] ?? [], $privacy_settings);
    }
    
    if (!empty($extra_settings)) {
        $settings = array_merge($settings, $extra_settings);
    }
    
    $updates[] = 'settings = ?';
    $params[] = json_encode($settings);
    $types .= 's';
}

if (empty($updates)) {
    send_json_response(400, ['success' => false, 'message' => 'No settings to update']);
}

// Add user_id to params
$params[] = $user_id;
$types .= 'i';

// Execute update
$query = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);

if (mysqli_stmt_execute($stmt)) {
    // Log activity
    log_activity($user_id, 'settings_update', 'Updated user settings');
    
    send_json_response(200, [
        'success' => true,
        'message' => 'Settings saved successfully'
    ]);
} else {
    send_json_response(500, ['success' => false, 'message' => 'Failed to save settings']);
}
