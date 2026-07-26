<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Auto-Delete Cleanup
 * ChatApp - Deletes messages based on auto_delete setting
 * Run via cron: php api/cleanup-auto-delete.php
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Only allow CLI or admin access
if (php_sapi_name() !== 'cli' && !isset($_GET['secret'])) {
    die('Direct access not permitted');
}

$deleted = 0;

// Delete 24-hour old messages
$sql_24h = "UPDATE messages SET is_deleted = 1 
            WHERE auto_delete = '24hours' 
            AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) 
            AND is_deleted = 0";
db_execute($sql_24h, [], '');
$deleted += mysqli_affected_rows($conn);

// Delete 1-day old messages
$sql_1day = "UPDATE messages SET is_deleted = 1 
             WHERE auto_delete = '1day' 
             AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY) 
             AND is_deleted = 0";
db_execute($sql_1day, [], '');
$deleted += mysqli_affected_rows($conn);

// Delete 7-day old messages
$sql_7day = "UPDATE messages SET is_deleted = 1 
             WHERE auto_delete = '7days' 
             AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) 
             AND is_deleted = 0";
db_execute($sql_7day, [], '');
$deleted += mysqli_affected_rows($conn);

// Delete 30-day old messages
$sql_30day = "UPDATE messages SET is_deleted = 1 
              WHERE auto_delete = '30days' 
              AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) 
              AND is_deleted = 0";
db_execute($sql_30day, [], '');
$deleted += mysqli_affected_rows($conn);

if (php_sapi_name() === 'cli') {
    echo "Auto-delete cleanup complete. $deleted messages marked as deleted.\n";
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'deleted' => $deleted]);
}
