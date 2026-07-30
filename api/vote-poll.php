<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Vote on Poll
 * ChatApp - POST /api/vote-poll.php
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
$poll_id = (int)($input['poll_id'] ?? 0);
$option_id = (int)($input['option_id'] ?? 0);

if (!$poll_id || !$option_id) {
    send_error('Poll ID and Option ID are required');
}

// Check if poll exists and is not expired
$poll = db_fetch_single(
    "SELECT id, is_multiple, expires_at FROM polls WHERE id = ?",
    [$poll_id],
    'i'
);

if (!$poll) {
    send_error('Poll not found');
}

if ($poll['expires_at'] && strtotime($poll['expires_at']) < time()) {
    send_error('This poll has expired');
}

// Check if option belongs to this poll
$option = db_fetch_single(
    "SELECT id FROM poll_options WHERE id = ? AND poll_id = ?",
    [$option_id, $poll_id],
    'ii'
);

if (!$option) {
    send_error('Invalid option');
}

// Check if user already voted
$existing_vote = db_fetch_single(
    "SELECT id, option_id FROM poll_votes WHERE poll_id = ? AND user_id = ?",
    [$poll_id, $user_id],
    'ii'
);

if ($existing_vote) {
    if (!$poll['is_multiple']) {
        send_error('You have already voted on this poll');
    }
    // For multiple choice, check if already voted on this specific option
    if ((int)$existing_vote['option_id'] === $option_id) {
        send_error('You have already voted on this option');
    }
}

// Insert vote
$sql = "INSERT INTO poll_votes (poll_id, option_id, user_id) VALUES (?, ?, ?)";
$result = db_execute($sql, [$poll_id, $option_id, $user_id], 'iii');

if ($result) {
    send_success('Vote recorded');
} else {
    send_error('Failed to record vote');
}
