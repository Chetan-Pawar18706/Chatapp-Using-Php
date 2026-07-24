<?php
/**
 * =====================================================
 * API: Get Group Info
 * ChatApp - GET /api/group-info.php
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Method not allowed', 405);
}

$user_id = session_get_user_id();
$group_id = (int)($_GET['group_id'] ?? 0);

if (!$group_id) {
    send_error('Group ID is required');
}

// Check membership
$member_sql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1";
$membership = db_fetch_single($member_sql, [$group_id, $user_id], 'ii');

if (!$membership) {
    send_error('You are not a member of this group');
}

// Get group info
$group_sql = "SELECT g.*, u.username as creator_name 
              FROM groups g 
              LEFT JOIN users u ON g.created_by = u.id 
              WHERE g.id = ? AND g.status = 'active' LIMIT 1";
$group = db_fetch_single($group_sql, [$group_id], 'i');

if (!$group) {
    send_error('Group not found');
}

// Get members
$members_sql = "SELECT 
                    gm.user_id,
                    gm.role,
                    gm.joined_at,
                    u.username,
                    u.avatar,
                    u.is_online,
                    u.last_seen
                FROM group_members gm
                INNER JOIN users u ON gm.user_id = u.id
                WHERE gm.group_id = ?
                ORDER BY 
                    CASE gm.role 
                        WHEN 'admin' THEN 1 
                        WHEN 'moderator' THEN 2 
                        ELSE 3 
                    END,
                    u.username ASC";

$members = db_fetch_all($members_sql, [$group_id], 'i');

$formatted_members = [];
foreach ($members as $member) {
    $formatted_members[] = [
        'user_id' => (int)$member['user_id'],
        'username' => $member['username'],
        'avatar' => $member['avatar'],
        'role' => $member['role'],
        'is_online' => (bool)$member['is_online'],
        'last_seen' => $member['last_seen'] ? time_ago($member['last_seen']) : 'Never',
        'joined_at' => format_date($member['joined_at'], 'M d, Y')
    ];
}

// Get recent notifications
$notif_sql = "SELECT gn.*, u.username as created_by_name
              FROM group_notifications gn
              LEFT JOIN users u ON gn.created_by = u.id
              WHERE gn.group_id = ? AND gn.user_id = ?
              ORDER BY gn.created_at DESC
              LIMIT 10";
$notifications = db_fetch_all($notif_sql, [$group_id, $user_id], 'ii');

$formatted_notifications = [];
foreach ($notifications as $notif) {
    $formatted_notifications[] = [
        'id' => (int)$notif['id'],
        'type' => $notif['notification_type'],
        'message' => $notif['message'],
        'created_by' => $notif['created_by_name'],
        'is_read' => (bool)$notif['is_read'],
        'created_at' => time_ago($notif['created_at'])
    ];
}

send_success('Group info loaded', [
    'group' => [
        'id' => (int)$group['id'],
        'name' => $group['name'],
        'description' => $group['description'],
        'avatar' => $group['avatar'],
        'creator_name' => $group['creator_name'],
        'created_at' => format_date($group['created_at'], 'M d, Y'),
        'member_count' => count($members),
        'my_role' => $membership['role']
    ],
    'members' => $formatted_members,
    'notifications' => $formatted_notifications
]);
