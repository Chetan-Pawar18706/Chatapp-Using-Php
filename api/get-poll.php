<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Get Poll
 * ChatApp - GET /api/get-poll.php?id=poll_id
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
$poll_id = (int)($_GET['id'] ?? 0);

if (!$poll_id) {
    send_error('Poll ID is required');
}

// Get poll details
$poll = db_fetch_single(
    "SELECT p.*, u.username as creator_name, u.avatar as creator_avatar
     FROM polls p
     LEFT JOIN users u ON p.user_id = u.id
     WHERE p.id = ?",
    [$poll_id],
    'i'
);

if (!$poll) {
    send_error('Poll not found');
}

// Get options with vote counts
$options_sql = "SELECT po.*, 
                (SELECT COUNT(*) FROM poll_votes WHERE option_id = po.id) as vote_count
                FROM poll_options po
                WHERE po.poll_id = ?
                ORDER BY po.id";
$options = db_fetch_all($options_sql, [$poll_id], 'i');

// Check if current user has voted
$user_vote = db_fetch_single(
    "SELECT option_id FROM poll_votes WHERE poll_id = ? AND user_id = ?",
    [$poll_id, $user_id],
    'ii'
);

$total_votes = array_sum(array_column($options, 'vote_count'));

// Format options
$formatted_options = [];
foreach ($options as $opt) {
    $vote_count = (int)$opt['vote_count'];
    $percentage = $total_votes > 0 ? round(($vote_count / $total_votes) * 100) : 0;
    
    $formatted_options[] = [
        'id' => (int)$opt['id'],
        'text' => $opt['option_text'],
        'vote_count' => $vote_count,
        'percentage' => $percentage,
        'has_voted' => $user_vote && (int)$user_vote['option_id'] === (int)$opt['id']
    ];
}

$is_expired = $poll['expires_at'] && strtotime($poll['expires_at']) < time();

send_success('Poll loaded', [
    'poll' => [
        'id' => (int)$poll['id'],
        'question' => $poll['question'],
        'is_multiple' => (bool)$poll['is_multiple'],
        'is_anonymous' => (bool)$poll['is_anonymous'],
        'creator_name' => $poll['creator_name'],
        'creator_avatar' => $poll['creator_avatar'],
        'expires_at' => $poll['expires_at'],
        'is_expired' => $is_expired,
        'created_at' => $poll['created_at']
    ],
    'options' => $formatted_options,
    'total_votes' => $total_votes,
    'has_voted' => (bool)$user_vote
]);
