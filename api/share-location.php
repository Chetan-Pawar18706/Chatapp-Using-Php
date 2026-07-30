<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Share Live Location
 * ChatApp - POST /api/share-location.php
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
$latitude = (float)($input['latitude'] ?? 0);
$longitude = (float)($input['longitude'] ?? 0);
$accuracy = (float)($input['accuracy'] ?? 0);
$receiver_id = !empty($input['receiver_id']) ? (int)$input['receiver_id'] : null;
$group_id = !empty($input['group_id']) ? (int)$input['group_id'] : null;
$duration_hours = (int)($input['duration_hours'] ?? 1);

if (!$latitude || !$longitude) {
    send_error('Latitude and longitude are required');
}

if (!$receiver_id && !$group_id) {
    send_error('Receiver or group is required');
}

$expires_at = date('Y-m-d H:i:s', strtotime("+{$duration_hours} hours"));

// Deactivate previous active locations from this user
db_execute("UPDATE live_locations SET is_active = 0 WHERE user_id = ? AND is_active = 1", [$user_id], 'i');

// Insert new location
$sql = "INSERT INTO live_locations (user_id, receiver_id, group_id, latitude, longitude, accuracy, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$result = db_execute($sql, [$user_id, $receiver_id, $group_id, $latitude, $longitude, $accuracy, $expires_at], 'iiddfds');

if ($result) {
    $location_id = mysqli_insert_id($conn);
    
    // Also send as a message
    $content = "📍 Live Location Shared";
    $msg_sql = "INSERT INTO messages (sender_id, receiver_id, group_id, content, message_type, created_at)
                VALUES (?, ?, ?, ?, 'location', NOW())";
    db_execute($msg_sql, [$user_id, $receiver_id, $group_id, $content], 'iisss');
    
    send_success('Location shared', [
        'location_id' => (int)$location_id,
        'expires_at' => $expires_at
    ]);
} else {
    send_error('Failed to share location');
}
