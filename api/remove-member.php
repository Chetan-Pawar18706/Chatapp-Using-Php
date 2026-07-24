<?php
/**
 * =====================================================
 * API: Remove Member from Group
 * ChatApp - POST /api/remove-member.php
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
$member_id = (int)($input['member_id'] ?? 0);
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token) && !is_ajax_request()) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();

if (!$group_id || !$member_id) {
    send_error('Group ID and Member ID are required');
}

// Check requester's role
$role_sql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1";
$role = db_fetch_single($role_sql, [$group_id, $user_id], 'ii');

if (!$role) {
    send_error('You are not a member of this group');
}

// Check target member's role
$target_role_sql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1";
$target_role = db_fetch_single($target_role_sql, [$group_id, $member_id], 'ii');

if (!$target_role) {
    send_error('Member not found in this group');
}

// Check permissions
if ($role['role'] !== 'admin') {
    if ($role['role'] === 'moderator' && $target_role['role'] === 'admin') {
        send_error('Moderators cannot remove admins');
    }
    if ($role['role'] !== 'admin' && $target_role['role'] === 'admin') {
        send_error('Only admins can remove other admins');
    }
}

// Cannot remove yourself
if ($member_id === $user_id) {
    send_error('Use leave group instead');
}

// Remove member
$delete_sql = "DELETE FROM group_members WHERE group_id = ? AND user_id = ?";
$result = db_execute($delete_sql, [$group_id, $member_id], 'ii');

if ($result) {
    // Get usernames
    $remover_sql = "SELECT username FROM users WHERE id = ? LIMIT 1";
    $remover = db_fetch_single($remover_sql, [$user_id], 'i');
    
    $removed_sql = "SELECT username FROM users WHERE id = ? LIMIT 1";
    $removed = db_fetch_single($removed_sql, [$member_id], 'i');
    
    // Notify remaining members
    $members_sql = "SELECT user_id FROM group_members WHERE group_id = ?";
    $members = db_fetch_all($members_sql, [$group_id], 'i');
    
    foreach ($members as $m) {
        $notif_sql = "INSERT INTO group_notifications (group_id, user_id, notification_type, message, created_by, created_at)
                     VALUES (?, ?, 'member_removed', ?, ?, NOW())";
        $msg = ($removed['username'] ?? 'Someone') . ' was removed by ' . ($remover['username'] ?? 'Admin');
        db_execute($notif_sql, [$group_id, $m['user_id'], $msg, $user_id], 'iisi');
    }
    
    // Notify removed user
    $group_sql = "SELECT name FROM groups WHERE id = ? LIMIT 1";
    $group = db_fetch_single($group_sql, [$group_id], 'i');
    
    $removee_notif_sql = "INSERT INTO group_notifications (group_id, user_id, notification_type, message, created_by, created_at)
                         VALUES (?, ?, 'member_removed', ?, ?, NOW())";
    $removee_msg = "You were removed from " . ($group['name'] ?? 'the group');
    db_execute($removee_notif_sql, [$group_id, $member_id, $removee_msg, $user_id], 'iisi');
    
    log_activity($user_id, 'member_removed', ['group_id' => $group_id, 'removed_id' => $member_id]);
    
    send_success('Member removed from group');
} else {
    send_error('Failed to remove member');
}
