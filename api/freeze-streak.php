<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Buy Streak Freeze
 * ChatApp - POST /api/freeze-streak.php
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
$streak_id = (int)($input['streak_id'] ?? 0);

if (!$streak_id) {
    send_error('Streak ID is required');
}

// Check if streak exists and belongs to user
$streak = db_fetch_single(
    "SELECT * FROM streaks WHERE id = ? AND (user1_id = ? OR user2_id = ?)",
    [$streak_id, $user_id, $user_id],
    'iii'
);

if (!$streak) {
    send_error('Streak not found');
}

// Add a freeze (in a real app, this would cost coins/points)
$sql = "UPDATE streaks SET freeze_count = freeze_count + 1, updated_at = NOW() WHERE id = ?";
db_execute($sql, [$streak_id], 'i');

send_success('Freeze added', [
    'freeze_count' => (int)$streak['freeze_count'] + 1
]);
