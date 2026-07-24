<?php
/**
 * =====================================================
 * Global Search Helper Functions
 * ChatApp - Search System
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

/**
 * Search Users
 * 
 * @param string $query Search query
 * @param int $user_id Current user ID (to exclude)
 * @param int $limit Results limit
 * @return array Search results
 */
function search_users($query, $user_id, $limit = 20) {
    global $conn;
    
    $search_term = '%' . $query . '%';
    
    $sql = "SELECT id, username, avatar, bio, status, is_online,
            CASE 
                WHEN username LIKE ? THEN 1
                WHEN username LIKE ? THEN 2
                ELSE 3
            END as relevance
            FROM users 
            WHERE (username LIKE ? OR bio LIKE ?)
            AND id != ?
            AND status = 'active'
            ORDER BY relevance, username
            LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    $starts_with = $query . '%';
    mysqli_stmt_bind_param($stmt, 'ssssii', $starts_with, $search_term, $search_term, $search_term, $user_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['avatar_url'] = $row['avatar'] ?? null;
        $row['initials'] = strtoupper(substr($row['username'], 0, 2));
        $row['status_color'] = get_status_color($row['status'] ?? 'online');
        $users[] = $row;
    }
    
    return $users;
}

/**
 * Search Friends
 * 
 * @param string $query Search query
 * @param int $user_id Current user ID
 * @param int $limit Results limit
 * @return array Search results
 */
function search_friends($query, $user_id, $limit = 20) {
    global $conn;
    
    $search_term = '%' . $query . '%';
    
    $sql = "SELECT u.id, u.username, u.avatar, u.bio, u.status, u.is_online, u.last_seen,
            CASE 
                WHEN u.username LIKE ? THEN 1
                WHEN u.username LIKE ? THEN 2
                ELSE 3
            END as relevance
            FROM users u
            INNER JOIN friendships f ON (
                (f.user_id = ? AND f.friend_id = u.id) OR 
                (f.user_id = u.id AND f.friend_id = ?)
            )
            WHERE f.status = 'accepted'
            AND (u.username LIKE ? OR u.bio LIKE ?)
            AND u.id != ?
            ORDER BY relevance, u.username
            LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    $starts_with = $query . '%';
    mysqli_stmt_bind_param($stmt, 'ssiiisii', $starts_with, $search_term, $user_id, $user_id, $search_term, $search_term, $user_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $friends = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['avatar_url'] = $row['avatar'] ?? null;
        $row['initials'] = strtoupper(substr($row['username'], 0, 2));
        $row['status_color'] = get_status_color($row['status'] ?? 'online');
        $row['last_seen_text'] = $row['is_online'] ? 'Online' : time_elapsed_string($row['last_seen'] ?? '');
        $friends[] = $row;
    }
    
    return $friends;
}

/**
 * Search Groups
 * 
 * @param string $query Search query
 * @param int $user_id Current user ID
 * @param int $limit Results limit
 * @return array Search results
 */
function search_groups($query, $user_id, $limit = 20) {
    global $conn;
    
    $search_term = '%' . $query . '%';
    
    $sql = "SELECT g.id, g.name, g.description, g.avatar, g.created_at,
            (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
            CASE 
                WHEN g.name LIKE ? THEN 1
                WHEN g.name LIKE ? THEN 2
                ELSE 3
            END as relevance,
            CASE 
                WHEN gm.id IS NOT NULL THEN 1
                ELSE 0
            END as is_member
            FROM groups g
            LEFT JOIN group_members gm ON g.id = gm.group_id AND gm.user_id = ?
            WHERE (g.name LIKE ? OR g.description LIKE ?)
            AND g.status = 'active'
            ORDER BY is_member DESC, relevance, g.name
            LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    $starts_with = $query . '%';
    mysqli_stmt_bind_param($stmt, 'ssissi', $starts_with, $search_term, $user_id, $search_term, $search_term, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $groups = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['avatar_url'] = $row['avatar'] ?? null;
        $row['initials'] = strtoupper(substr($row['name'], 0, 2));
        $row['member_count_text'] = $row['member_count'] . ' member' . ($row['member_count'] != 1 ? 's' : '');
        $groups[] = $row;
    }
    
    return $groups;
}

/**
 * Search Messages
 * 
 * @param string $query Search query
 * @param int $user_id Current user ID
 * @param int $limit Results limit
 * @return array Search results
 */
function search_messages($query, $user_id, $limit = 20) {
    global $conn;
    
    $search_term = '%' . $query . '%';
    
    $sql = "SELECT m.id, m.content, m.created_at, m.sender_id, m.receiver_id, m.group_id,
            sender.username as sender_name, sender.avatar as sender_avatar,
            CASE 
                WHEN m.group_id IS NOT NULL THEN g.name
                WHEN m.sender_id = ? THEN receiver.username
                ELSE sender.username
            END as chat_name,
            CASE 
                WHEN m.group_id IS NOT NULL THEN 'group'
                ELSE 'personal'
            END as chat_type,
            CASE 
                WHEN m.content LIKE ? THEN 1
                ELSE 2
            END as relevance
            FROM messages m
            LEFT JOIN users sender ON m.sender_id = sender.id
            LEFT JOIN users receiver ON m.receiver_id = receiver.id
            LEFT JOIN groups g ON m.group_id = g.id
            WHERE m.content LIKE ?
            AND m.is_deleted = 0
            AND (
                m.sender_id = ? 
                OR m.receiver_id = ?
                OR m.group_id IN (
                    SELECT group_id FROM group_members WHERE user_id = ?
                )
            )
            ORDER BY relevance, m.created_at DESC
            LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    $starts_with = $query . '%';
    mysqli_stmt_bind_param($stmt, 'isiiii', $user_id, $starts_with, $search_term, $user_id, $user_id, $user_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['sender_avatar_url'] = $row['sender_avatar'] ?? null;
        $row['sender_initials'] = strtoupper(substr($row['sender_name'], 0, 2));
        $row['content_preview'] = highlight_search($row['content'], $query);
        $row['time_ago'] = time_elapsed_string($row['created_at']);
        $row['chat_url'] = $row['chat_type'] === 'group' 
            ? 'pages/group-chat.php?id=' . $row['group_id']
            : 'pages/chat.php?user=' . ($row['sender_id'] == $user_id ? $row['receiver_id'] : $row['sender_id']);
        $messages[] = $row;
    }
    
    return $messages;
}

/**
 * Save Recent Search
 * 
 * @param int $user_id User ID
 * @param string $type Search type
 * @param string $query Search query
 * @param int|null $result_id Result ID
 * @param string|null $result_name Result name
 * @param array|null $result_data Additional data
 * @return int|false Search ID or false
 */
function save_recent_search($user_id, $type, $query, $result_id = null, $result_name = null, $result_data = null) {
    global $conn;
    
    // Check for duplicate (same query in last 5 minutes)
    $check_sql = "SELECT id FROM recent_searches 
                  WHERE user_id = ? AND search_query = ? 
                  AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
                  LIMIT 1";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, 'is', $user_id, $query);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        return false; // Already exists
    }
    
    $sql = "INSERT INTO recent_searches (user_id, search_type, search_query, result_id, result_name, result_data, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $sql);
    $json_data = $result_data ? json_encode($result_data) : null;
    mysqli_stmt_bind_param($stmt, 'ississ', $user_id, $type, $query, $result_id, $result_name, $json_data);
    
    if (mysqli_stmt_execute($stmt)) {
        return mysqli_insert_id($conn);
    }
    
    return false;
}

/**
 * Get Recent Searches
 * 
 * @param int $user_id User ID
 * @param string|null $type Filter by type
 * @param int $limit Results limit
 * @return array Recent searches
 */
function get_recent_searches($user_id, $type = null, $limit = 10) {
    global $conn;
    
    $sql = "SELECT * FROM recent_searches WHERE user_id = ?";
    $params = [$user_id];
    $types = 'i';
    
    if ($type) {
        $sql .= " AND search_type = ?";
        $params[] = $type;
        $types .= 's';
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;
    $types .= 'i';
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $searches = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['result_data'] = json_decode($row['result_data'], true);
        $row['time_ago'] = time_elapsed_string($row['created_at']);
        $searches[] = $row;
    }
    
    return $searches;
}

/**
 * Delete Recent Search
 * 
 * @param int $search_id Search ID
 * @param int $user_id User ID (for security)
 * @return bool Success
 */
function delete_recent_search($search_id, $user_id) {
    global $conn;
    
    $sql = "DELETE FROM recent_searches WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $search_id, $user_id);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Clear Recent Searches
 * 
 * @param int $user_id User ID
 * @param string|null $type Clear specific type only
 * @return bool Success
 */
function clear_recent_searches($user_id, $type = null) {
    global $conn;
    
    $sql = "DELETE FROM recent_searches WHERE user_id = ?";
    $params = [$user_id];
    $types = 'i';
    
    if ($type) {
        $sql .= " AND search_type = ?";
        $params[] = $type;
        $types .= 's';
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Get Status Color
 * 
 * @param string $status User status
 * @return string Color class
 */
function get_status_color($status) {
    $colors = [
        'online' => 'success',
        'busy' => 'danger',
        'away' => 'warning',
        'invisible' => 'secondary',
        'offline' => 'secondary'
    ];
    
    return $colors[$status] ?? 'secondary';
}

/**
 * Highlight Search Term
 * 
 * @param string $text Original text
 * @param string $query Search query
 * @return string Highlighted text
 */
function highlight_search($text, $query) {
    if (empty($query)) return htmlspecialchars($text);
    
    $highlighted = preg_replace(
        '/' . preg_quote($query, '/') . '/i',
        '<mark>$0</mark>',
        htmlspecialchars($text)
    );
    
    return $highlighted;
}

/**
 * Global Search
 * 
 * @param string $query Search query
 * @param int $user_id Current user ID
 * @param array $filters Search filters
 * @return array Combined search results
 */
function global_search($query, $user_id, $filters = []) {
    $results = [
        'users' => [],
        'friends' => [],
        'groups' => [],
        'messages' => []
    ];
    
    if (strlen($query) < 2) {
        return $results;
    }
    
    $limit = $filters['limit'] ?? 10;
    $types = $filters['types'] ?? ['users', 'friends', 'groups', 'messages'];
    
    if (in_array('users', $types)) {
        $results['users'] = search_users($query, $user_id, $limit);
    }
    
    if (in_array('friends', $types)) {
        $results['friends'] = search_friends($query, $user_id, $limit);
    }
    
    if (in_array('groups', $types)) {
        $results['groups'] = search_groups($query, $user_id, $limit);
    }
    
    if (in_array('messages', $types)) {
        $results['messages'] = search_messages($query, $user_id, $limit);
    }
    
    return $results;
}
