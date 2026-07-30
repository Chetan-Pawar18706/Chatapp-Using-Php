<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Get Live Locations
 * ChatApp - GET /api/get-location.php
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

// Clean expired locations
db_execute("UPDATE live_locations SET is_active = 0 WHERE expires_at < NOW() AND is_active = 1", [], '');

// Get active locations from friends
$sql = "SELECT ll.*, u.username, u.avatar
        FROM live_locations ll
        INNER JOIN users u ON ll.user_id = u.id
        WHERE ll.is_active = 1
        AND ll.expires_at > NOW()
        AND (
            ll.receiver_id = ?
            OR ll.user_id IN (
                SELECT CASE WHEN user_id = ? THEN friend_id ELSE user_id END
                FROM friendships
                WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'
            )
        )
        ORDER BY ll.updated_at DESC";

$locations = db_fetch_all($sql, [$user_id, $user_id, $user_id, $user_id], 'iiii');

$formatted = [];
foreach ($locations as $loc) {
    $formatted[] = [
        'id' => (int)$loc['id'],
        'user_id' => (int)$loc['user_id'],
        'username' => $loc['username'],
        'avatar' => $loc['avatar'],
        'latitude' => (float)$loc['latitude'],
        'longitude' => (float)$loc['longitude'],
        'accuracy' => (float)$loc['accuracy'],
        'expires_at' => $loc['expires_at'],
        'updated_at' => $loc['updated_at']
    ];
}

send_success('Locations loaded', ['locations' => $formatted]);
