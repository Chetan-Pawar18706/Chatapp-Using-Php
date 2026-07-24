<?php
/**
 * =====================================================
 * API: Get Groups
 * ChatApp - GET /api/groups.php
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

// Get user's groups
$sql = "SELECT 
            g.id as group_id,
            g.name,
            g.description,
            g.avatar,
            g.created_by,
            gm.role,
            gm.joined_at,
            u.username as creator_name,
            (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
        FROM groups g
        INNER JOIN group_members gm ON g.id = gm.group_id
        INNER JOIN users u ON g.created_by = u.id
        WHERE gm.user_id = ? AND g.status = 'active'
        ORDER BY gm.joined_at DESC
        LIMIT 20";

$groups = db_fetch_all($sql, [$user_id], 'i');

$formatted_groups = [];
foreach ($groups as $group) {
    $formatted_groups[] = [
        'group_id' => (int)$group['group_id'],
        'name' => $group['name'],
        'description' => $group['description'],
        'avatar' => $group['avatar'],
        'role' => $group['role'],
        'joined_at' => time_ago($group['joined_at']),
        'creator' => $group['creator_name'],
        'member_count' => (int)$group['member_count']
    ];
}

send_success('Groups loaded', ['groups' => $formatted_groups]);
