<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Leave Group
 * ChatApp - POST /api/leave-group.php
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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$group_id = (int)($input['group_id'] ?? 0);
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();

if (!$group_id) {
    send_error('Group ID is required');
}

// Check membership
$member_sql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1";
$membership = db_fetch_single($member_sql, [$group_id, $user_id], 'ii');

if (!$membership) {
    send_error('You are not a member of this group');
}

// Check if creator (creator cannot leave, must delete group)
$group_sql = "SELECT created_by FROM groups WHERE id = ? LIMIT 1";
$group = db_fetch_single($group_sql, [$group_id], 'i');

if ($group && (int)$group['created_by'] === $user_id) {
    send_error('Group creator cannot leave. Delete the group instead.');
}

// Remove from group
$delete_sql = "DELETE FROM group_members WHERE group_id = ? AND user_id = ?";
$result = db_execute($delete_sql, [$group_id, $user_id], 'ii');

if ($result) {
    // Get user name
    $user_sql = "SELECT username FROM users WHERE id = ? LIMIT 1";
    $user = db_fetch_single($user_sql, [$user_id], 'i');
    
    // Notify remaining members
    $members_sql = "SELECT user_id FROM group_members WHERE group_id = ?";
    $members = db_fetch_all($members_sql, [$group_id], 'i');
    
    foreach ($members as $m) {
        $notif_sql = "INSERT INTO group_notifications (group_id, user_id, notification_type, message, created_by, created_at)
                     VALUES (?, ?, 'member_left', ?, ?, NOW())";
        $msg = ($user['username'] ?? 'Someone') . ' left the group';
        db_execute($notif_sql, [$group_id, $m['user_id'], $msg, $user_id], 'iisi');
    }
    
    log_activity($user_id, 'group_left', ['group_id' => $group_id]);
    
    send_success('You left the group');
} else {
    send_error('Failed to leave group');
}
