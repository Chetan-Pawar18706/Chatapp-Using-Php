<?php
/**
 * =====================================================
 * API: Update Member Role
 * ChatApp - POST /api/update-role.php
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
$new_role = $input['new_role'] ?? '';
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token) && !is_ajax_request()) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();

if (!$group_id || !$member_id || !in_array($new_role, ['admin', 'moderator', 'member'])) {
    send_error('Invalid parameters');
}

// Check requester is admin
$role_sql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1";
$role = db_fetch_single($role_sql, [$group_id, $user_id], 'ii');

if (!$role || $role['role'] !== 'admin') {
    send_error('Only admins can change roles');
}

// Check target is member
$target_sql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1";
$target = db_fetch_single($target_sql, [$group_id, $member_id], 'ii');

if (!$target) {
    send_error('Member not found');
}

// Update role
$update_sql = "UPDATE group_members SET role = ? WHERE group_id = ? AND user_id = ?";
$result = db_execute($update_sql, [$new_role, $group_id, $member_id], 'sii');

if ($result) {
    // Get usernames
    $admin_sql = "SELECT username FROM users WHERE id = ? LIMIT 1";
    $admin = db_fetch_single($admin_sql, [$user_id], 'i');
    
    $member_sql = "SELECT username FROM users WHERE id = ? LIMIT 1";
    $member = db_fetch_single($member_sql, [$member_id], 'i');
    
    // Notify all members
    $members_sql = "SELECT user_id FROM group_members WHERE group_id = ?";
    $members = db_fetch_all($members_sql, [$group_id], 'i');
    
    foreach ($members as $m) {
        $notif_sql = "INSERT INTO group_notifications (group_id, user_id, notification_type, message, created_by, created_at)
                     VALUES (?, ?, 'role_changed', ?, ?, NOW())";
        $msg = ($member['username'] ?? 'Someone') . ' was promoted to ' . ucfirst($new_role) . ' by ' . ($admin['username'] ?? 'Admin');
        db_execute($notif_sql, [$group_id, $m['user_id'], $msg, $user_id], 'iisi');
    }
    
    log_activity($user_id, 'role_changed', ['group_id' => $group_id, 'member_id' => $member_id, 'new_role' => $new_role]);
    
    send_success('Role updated successfully', ['new_role' => $new_role]);
} else {
    send_error('Failed to update role');
}
