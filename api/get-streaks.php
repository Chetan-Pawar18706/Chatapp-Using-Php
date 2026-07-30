<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Get Streaks
 * ChatApp - GET /api/get-streaks.php
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

$user_id = session_get_user_id();

// Get user's streaks
$sql = "SELECT s.*, 
        CASE WHEN s.user1_id = ? THEN u2.username ELSE u1.username END as friend_name,
        CASE WHEN s.user1_id = ? THEN u2.avatar ELSE u1.avatar END as friend_avatar,
        CASE WHEN s.user1_id = ? THEN u2.id ELSE u1.id END as friend_id
        FROM streaks s
        LEFT JOIN users u1 ON s.user1_id = u1.id
        LEFT JOIN users u2 ON s.user2_id = u2.id
        WHERE (s.user1_id = ? OR s.user2_id = ?)
        ORDER BY s.streak_count DESC";

$streaks = db_fetch_all($sql, [$user_id, $user_id, $user_id, $user_id, $user_id], 'iiiii');

$formatted = [];
foreach ($streaks as $streak) {
    $today = date('Y-m-d');
    $last_msg = $streak['last_message_date'];
    $diff = (strtotime($today) - strtotime($last_msg)) / 86400;
    
    // Check if streak is still active (message sent within last 24 hours)
    $is_active = $diff <= 1;
    
    // Check if streak is frozen
    $is_frozen = $streak['freeze_count'] > 0 && !$is_active;
    
    $formatted[] = [
        'id' => (int)$streak['id'],
        'friend_id' => (int)$streak['friend_id'],
        'friend_name' => $streak['friend_name'],
        'friend_avatar' => $streak['friend_avatar'],
        'streak_count' => (int)$streak['streak_count'],
        'is_active' => $is_active,
        'is_frozen' => $is_frozen,
        'freeze_count' => (int)$streak['freeze_count'],
        'last_message_date' => $last_msg,
        'started_at' => $streak['started_at']
    ];
}

send_success('Streaks loaded', ['streaks' => $formatted]);
