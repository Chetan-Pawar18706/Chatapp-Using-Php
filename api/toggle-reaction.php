<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Toggle Message Reaction
 * ChatApp - POST /api/toggle-reaction.php
 * Add or remove a reaction on a message
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

$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid CSRF token', 403);
}

$user_id = session_get_user_id();
$message_id = intval($input['message_id'] ?? 0);
$emoji = trim($input['emoji'] ?? '');

if (!$message_id) {
    send_error('Message ID is required');
}

if (empty($emoji)) {
    send_error('Emoji is required');
}

$allowed_emojis = ['👍', '❤️', '😂', '😮', '😢', '🙏', '🔥', '👏'];
if (!in_array($emoji, $allowed_emojis)) {
    send_error('Invalid emoji');
}

// Check if reaction exists
$existing = db_fetch_single(
    "SELECT id FROM message_reactions WHERE message_id = ? AND user_id = ?",
    [$message_id, $user_id],
    'ii'
);

if ($existing) {
    // Toggle: remove if same emoji, replace if different
    $current = db_fetch_single(
        "SELECT emoji FROM message_reactions WHERE message_id = ? AND user_id = ?",
        [$message_id, $user_id],
        'ii'
    );
    
    if ($current && $current['emoji'] === $emoji) {
        // Remove reaction
        db_execute("DELETE FROM message_reactions WHERE message_id = ? AND user_id = ?", [$message_id, $user_id], 'ii');
        send_success('Reaction removed', ['action' => 'removed', 'emoji' => $emoji]);
    } else {
        // Update reaction
        db_execute("UPDATE message_reactions SET emoji = ? WHERE message_id = ? AND user_id = ?", [$emoji, $message_id, $user_id], 'sii');
        send_success('Reaction updated', ['action' => 'updated', 'emoji' => $emoji]);
    }
} else {
    // Add new reaction
    db_execute(
        "INSERT INTO message_reactions (message_id, user_id, emoji, created_at) VALUES (?, ?, ?, NOW())",
        [$message_id, $user_id, $emoji],
        'iis'
    );
    send_success('Reaction added', ['action' => 'added', 'emoji' => $emoji]);
}
