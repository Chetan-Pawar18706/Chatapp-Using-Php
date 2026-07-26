<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Get Friends List
 * ChatApp - GET /api/get-friends.php
 * Returns all accepted friends with details
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Method not allowed', 405);
}

$user_id = session_get_user_id();
$filter = $_GET['filter'] ?? 'all'; // all, online, offline

// Get all accepted friends
$sql = "SELECT 
            u.id,
            u.username,
            u.email,
            u.friend_code,
            u.avatar,
            u.bio,
            u.is_online,
            u.last_seen,
            f.created_at as friends_since,
            ts.is_typing,
            ts.last_typing_at
        FROM users u
        INNER JOIN friendships f ON (
            (f.user_id = ? AND f.friend_id = u.id) OR 
            (f.user_id = u.id AND f.friend_id = ?)
        )
        AND f.status = 'accepted'
        LEFT JOIN typing_status ts ON (
            (ts.user_id = u.id AND ts.chat_with_user_id = ?) OR
            (ts.user_id = ? AND ts.chat_with_user_id = u.id)
        )
        WHERE u.id != ? AND u.status = 'active'
        ORDER BY u.is_online DESC, u.username ASC";

$friends = db_fetch_all($sql, [$user_id, $user_id, $user_id, $user_id, $user_id], 'iiiii');

// Filter by online status if needed
$filtered_friends = [];
foreach ($friends as $friend) {
    if ($filter === 'online' && !$friend['is_online']) {
        continue;
    }
    if ($filter === 'offline' && $friend['is_online']) {
        continue;
    }
    
    // Get mutual friends count
    $mutual_sql = "SELECT COUNT(DISTINCT CASE 
                    WHEN f1.user_id = ? THEN f1.friend_id 
                    ELSE f1.user_id END) as count
                   FROM friendships f1
                   INNER JOIN friendships f2 ON (
                       (f2.user_id = ? AND f2.friend_id = f1.friend_id AND f2.status = 'accepted')
                       OR (f2.user_id = f1.friend_id AND f2.friend_id = ? AND f2.status = 'accepted')
                   )
                   WHERE (f1.user_id = ? OR f1.friend_id = ?) 
                   AND f1.status = 'accepted'
                   AND f1.friend_id != ?
                   AND f1.user_id != ?";
    $mutual = db_fetch_single($mutual_sql, [
        $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id
    ], 'iiiiiii');
    
    // Check if there's an unread message
    $unread_sql = "SELECT COUNT(*) as count FROM messages 
                   WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
    $unread = db_fetch_single($unread_sql, [$friend['id'], $user_id], 'ii');
    
    // Determine typing status
    $typing_with_me = false;
    $i_am_typing = false;
    
    if ($friend['is_typing']) {
        if ((int)$friend['last_typing_at'] > 0) {
            $typing_time = strtotime($friend['last_typing_at']);
            if (time() - $typing_time < 10) { // Within last 10 seconds
                // Check who is typing to whom
                $my_typing_sql = "SELECT is_typing FROM typing_status 
                                 WHERE user_id = ? AND chat_with_user_id = ?";
                $my_typing = db_fetch_single($my_typing_sql, [$user_id, $friend['id']], 'ii');
                
                $their_typing_sql = "SELECT is_typing FROM typing_status 
                                     WHERE user_id = ? AND chat_with_user_id = ?";
                $their_typing = db_fetch_single($their_typing_sql, [$friend['id'], $user_id], 'ii');
                
                if ($their_typing && $their_typing['is_typing']) {
                    $typing_with_me = true;
                }
                if ($my_typing && $my_typing['is_typing']) {
                    $i_am_typing = true;
                }
            }
        }
    }
    
    $filtered_friends[] = [
        'id' => (int)$friend['id'],
        'username' => $friend['username'],
        'email' => $friend['email'],
        'friend_code' => $friend['friend_code'],
        'avatar' => $friend['avatar'],
        'bio' => $friend['bio'],
        'is_online' => (bool)$friend['is_online'],
        'last_seen' => $friend['last_seen'] ? time_ago($friend['last_seen']) : 'Never',
        'friends_since' => format_date($friend['friends_since'], 'M Y'),
        'mutual_friends' => (int)($mutual['count'] ?? 0),
        'unread_messages' => (int)($unread['count'] ?? 0),
        'is_typing' => $typing_with_me,
        'i_am_typing' => $i_am_typing
    ];
}

send_success('Friends loaded', [
    'friends' => $filtered_friends,
    'total' => count($filtered_friends),
    'online' => count(array_filter($filtered_friends, fn($f) => $f['is_online']))
]);
