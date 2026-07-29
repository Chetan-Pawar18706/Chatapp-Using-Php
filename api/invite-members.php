<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Invite Members to Group
 * ChatApp - POST /api/invite-members.php
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
$member_ids = $input['member_ids'] ?? [];
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();

if (!$group_id || empty($member_ids)) {
    send_error('Group ID and members are required');
}

// Check if user is admin or moderator
$role_sql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1";
$role = db_fetch_single($role_sql, [$group_id, $user_id], 'ii');

if (!$role || !in_array($role['role'], ['admin', 'moderator'])) {
    send_error('Only admins and moderators can invite members');
}

// Get group name
$group_sql = "SELECT name FROM groups WHERE id = ? LIMIT 1";
$group = db_fetch_single($group_sql, [$group_id], 'i');

$invited = [];
$already_members = [];

foreach ($member_ids as $member_id) {
    $member_id = (int)$member_id;
    if ($member_id <= 0 || $member_id === $user_id) continue;
    
    // Check if already a member
    $check_sql = "SELECT id FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1";
    $existing = db_fetch_single($check_sql, [$group_id, $member_id], 'ii');
    
    if ($existing) {
        $already_members[] = $member_id;
        continue;
    }
    
    // Add member
    $add_sql = "INSERT INTO group_members (group_id, user_id, role, joined_at) VALUES (?, ?, 'member', NOW())";
    $result = db_execute($add_sql, [$group_id, $member_id], 'ii');
    
    if ($result) {
        $invited[] = $member_id;
        
        // Get inviter name
        $inviter_sql = "SELECT username FROM users WHERE id = ? LIMIT 1";
        $inviter = db_fetch_single($inviter_sql, [$user_id], 'i');
        
        // Create notification in main notifications table
        require_once __DIR__ . '/../includes/notification_helpers.php';
        create_notification(
            $member_id,
            $user_id,
            'group_invite',
            'Group Invitation',
            'You were added to ' . $group['name'] . ' by ' . ($inviter['username'] ?? 'Unknown'),
            ['group_id' => $group_id]
        );
        
        // Also keep existing group_notifications
        $notif_sql = "INSERT INTO group_notifications (group_id, user_id, notification_type, message, created_by, created_at)
                     VALUES (?, ?, 'member_joined', ?, ?, NOW())";
        $msg = "You were added to {$group['name']} by " . ($inviter['username'] ?? 'Unknown');
        db_execute($notif_sql, [$group_id, $member_id, $msg, $user_id], 'iisi');
        
        // Notify all group members
        $members_sql = "SELECT user_id FROM group_members WHERE group_id = ? AND user_id != ?";
        $members = db_fetch_all($members_sql, [$group_id, $member_id], 'ii');
        
        $member_name_sql = "SELECT username FROM users WHERE id = ? LIMIT 1";
        $member_name = db_fetch_single($member_name_sql, [$member_id], 'i');
        
        foreach ($members as $m) {
            $system_notif_sql = "INSERT INTO group_notifications (group_id, user_id, notification_type, message, created_by, created_at)
                                VALUES (?, ?, 'member_joined', ?, ?, NOW())";
            $sys_msg = ($member_name['username'] ?? 'Someone') . " joined the group";
            db_execute($system_notif_sql, [$group_id, $m['user_id'], $sys_msg, $member_id], 'iisi');
        }
    }
}

send_success('Members invited', [
    'invited_count' => count($invited),
    'already_members_count' => count($already_members)
]);
