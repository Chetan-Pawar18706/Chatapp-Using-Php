<?php
/**
 * =====================================================
 * API: Create Group
 * ChatApp - POST /api/create-group.php
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

$name = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$member_ids = $input['members'] ?? [];
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token) && !is_ajax_request()) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();

// Validate input
if (empty($name)) {
    send_error('Group name is required');
}

if (strlen($name) < 2 || strlen($name) > 100) {
    send_error('Group name must be 2-100 characters');
}

if (!empty($description) && strlen($description) > 500) {
    send_error('Description must be less than 500 characters');
}

// Create group
$sql = "INSERT INTO groups (name, description, created_by, status, created_at) 
        VALUES (?, ?, ?, 'active', NOW())";
$result = db_execute($sql, [$name, $description, $user_id], 'sss');

if (!$result) {
    send_error('Failed to create group');
}

$group_id = db_insert_id();

// Add creator as admin
$admin_sql = "INSERT INTO group_members (group_id, user_id, role, joined_at) VALUES (?, ?, 'admin', NOW())";
db_execute($admin_sql, [$group_id, $user_id], 'ii');

// Add invited members
if (!empty($member_ids) && is_array($member_ids)) {
    $valid_members = [];
    foreach ($member_ids as $member_id) {
        $member_id = (int)$member_id;
        if ($member_id > 0 && $member_id !== $user_id) {
            // Verify friendship
            $friend_sql = "SELECT id FROM friendships 
                          WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))
                          AND status = 'accepted' LIMIT 1";
            $friend = db_fetch_single($friend_sql, [$user_id, $member_id, $member_id, $user_id], 'iiii');
            
            if ($friend) {
                $member_sql = "INSERT INTO group_members (group_id, user_id, role, joined_at) 
                              VALUES (?, ?, 'member', NOW())
                              ON DUPLICATE KEY UPDATE role = 'member'";
                db_execute($member_sql, [$group_id, $member_id], 'ii');
                $valid_members[] = $member_id;
                
                // Create notification
                $notif_sql = "INSERT INTO group_notifications (group_id, user_id, notification_type, message, created_by, created_at)
                             VALUES (?, ?, 'member_joined', ?, ?, NOW())";
                db_execute($notif_sql, [$group_id, $member_id, 'You were added to the group', $user_id], 'iisi');
            }
        }
    }
}

// Log activity
log_activity($user_id, 'group_created', ['group_id' => $group_id, 'group_name' => $name]);

send_success('Group created successfully', [
    'group_id' => (int)$group_id,
    'name' => $name,
    'description' => $description
]);
