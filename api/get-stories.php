<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Get Stories
 * ChatApp - GET /api/get-stories.php
 * Returns stories from friends + own stories
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

// Clean expired stories first
db_execute("DELETE FROM stories WHERE expires_at < NOW()", [], '');

// Get own stories
$own_sql = "SELECT s.*, 
            (SELECT COUNT(*) FROM story_views WHERE story_id = s.id) as view_count,
            (SELECT COUNT(*) FROM story_views WHERE story_id = s.id AND user_id = ?) as has_viewed
            FROM stories s
            WHERE s.user_id = ? AND s.expires_at > NOW()
            ORDER BY s.created_at DESC";
$own_stories = db_fetch_all($own_sql, [$user_id, $user_id], 'ii');

// Get friends' stories (users who are friends with current user)
$friends_sql = "SELECT s.*, u.username, u.avatar,
                (SELECT COUNT(*) FROM story_views WHERE story_id = s.id) as view_count,
                (SELECT COUNT(*) FROM story_views WHERE story_id = s.id AND user_id = ?) as has_viewed
                FROM stories s
                INNER JOIN users u ON s.user_id = u.id
                WHERE s.user_id != ? 
                AND s.expires_at > NOW()
                AND s.user_id IN (
                    SELECT CASE WHEN user_id = ? THEN friend_id ELSE user_id END
                    FROM friendships
                    WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'
                )
                ORDER BY 
                    (SELECT MIN(sv.viewed_at) FROM story_views sv WHERE sv.story_id = s.id AND sv.user_id = ?) ASC,
                    s.created_at DESC";
$friends_stories = db_fetch_all($friends_sql, [$user_id, $user_id, $user_id, $user_id, $user_id, $user_id], 'iiiiii');

// Group stories by user
$stories_by_user = [];
$all_stories = array_merge($own_stories, $friends_stories);

foreach ($all_stories as $story) {
    $uid = (int)$story['user_id'];
    if (!isset($stories_by_user[$uid])) {
        $stories_by_user[$uid] = [
            'user_id' => $uid,
            'username' => $story['username'] ?? null,
            'avatar' => $story['avatar'] ?? null,
            'is_own' => $uid === $user_id,
            'stories' => [],
            'has_unviewed' => false
        ];
    }
    
    $story_data = [
        'id' => (int)$story['id'],
        'content' => $story['content'],
        'media_type' => $story['media_type'],
        'media_path' => $story['media_path'],
        'bg_color' => $story['bg_color'],
        'text_color' => $story['text_color'],
        'font_size' => (int)$story['font_size'],
        'view_count' => (int)$story['view_count'],
        'has_viewed' => (bool)$story['has_viewed'],
        'created_at' => $story['created_at'],
        'expires_at' => $story['expires_at']
    ];
    
    $stories_by_user[$uid]['stories'][] = $story_data;
    
    if (!$story['has_viewed'] && !$stories_by_user[$uid]['is_own']) {
        $stories_by_user[$uid]['has_unviewed'] = true;
    }
}

// Sort: unviewed first, then own, then by latest
usort($stories_by_user, function($a, $b) use ($user_id) {
    // Own story always first
    if ($a['is_own']) return -1;
    if ($b['is_own']) return 1;
    
    // Unviewed before viewed
    if ($a['has_unviewed'] && !$b['has_unviewed']) return -1;
    if (!$a['has_unviewed'] && $b['has_unviewed']) return 1;
    
    // Latest first
    return strtotime(end($a['stories'])['created_at']) - strtotime(end($b['stories'])['created_at']);
});

send_success('Stories loaded', [
    'stories' => array_values($stories_by_user),
    'total_users' => count($stories_by_user)
]);
