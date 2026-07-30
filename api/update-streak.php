<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Update Streak
 * ChatApp - POST /api/update-streak.php
 * Called when a message is sent to update streaks
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
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();
$friend_id = (int)($input['friend_id'] ?? 0);

if (!$friend_id) {
    send_error('Friend ID is required');
}

$today = date('Y-m-d');

// Get existing streak
$sql = "SELECT * FROM streaks 
        WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)";
$streak = db_fetch_single($sql, [$user_id, $friend_id, $friend_id, $user_id], 'iiii');

if ($streak) {
    $last_date = $streak['last_message_date'];
    $diff = (strtotime($today) - strtotime($last_date)) / 86400;
    
    if ($diff > 1) {
        // Streak broken - check if we can use a freeze
        if ($streak['freeze_count'] > 0) {
            // Use a freeze
            $new_freeze = $streak['freeze_count'] - 1;
            $sql = "UPDATE streaks SET freeze_count = ?, last_message_date = ?, updated_at = NOW()
                    WHERE id = ?";
            db_execute($sql, [$new_freeze, $today, $streak['id']], 'isi');
            send_success('Streak saved with freeze', [
                'streak_count' => (int)$streak['streak_count'],
                'used_freeze' => true,
                'freezes_left' => $new_freeze
            ]);
        } else {
            // Reset streak
            $sql = "UPDATE streaks SET streak_count = 1, last_message_date = ?, updated_at = NOW()
                    WHERE id = ?";
            db_execute($sql, [$today, $streak['id']], 'si');
            send_success('Streak restarted', [
                'streak_count' => 1,
                'used_freeze' => false
            ]);
        }
    } elseif ($diff == 1) {
        // Continue streak
        $new_count = $streak['streak_count'] + 1;
        $sql = "UPDATE streaks SET streak_count = ?, last_message_date = ?, updated_at = NOW()
                WHERE id = ?";
        db_execute($sql, [$new_count, $today, $streak['id']], 'isi');
        send_success('Streak continued', [
            'streak_count' => $new_count,
            'used_freeze' => false
        ]);
    } else {
        // Same day - no change
        send_success('Streak unchanged', [
            'streak_count' => (int)$streak['streak_count'],
            'used_freeze' => false
        ]);
    }
} else {
    // Create new streak
    $user1 = min($user_id, $friend_id);
    $user2 = max($user_id, $friend_id);
    
    $sql = "INSERT INTO streaks (user1_id, user2_id, streak_count, last_message_date)
            VALUES (?, ?, 1, ?)";
    db_execute($sql, [$user1, $user2, $today], 'iis');
    
    send_success('New streak started', [
        'streak_count' => 1,
        'used_freeze' => false
    ]);
}
