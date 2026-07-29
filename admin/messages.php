<?php
/**
 * =====================================================
 * Admin Messages Oversight
 * ChatApp - View Messages
 * =====================================================
 */

define('APP_RUNNING', true);

require_once dirname(__DIR__) . '/config/database.php';
require_once 'config.php';
require_once 'auth.php';
require_once 'helpers.php';

admin_session_init();

if (!admin_verify_session()) {
    header('Location: index.php');
    exit;
}

$conn = db_connect();

$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? 'personal';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = ADMIN_PER_PAGE;

$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "m.content LIKE ?";
    $params[] = "%{$search}%";
    $types .= 's';
}

if ($type === 'group') {
    $table = 'group_messages m';
    $join = 'LEFT JOIN users u ON m.user_id = u.id LEFT JOIN `groups` g ON m.group_id = g.id';
    $select = "m.*, u.username, g.name as group_name";
    $where[] = "m.id IS NOT NULL";
} else {
    $table = 'messages m';
    $join = 'LEFT JOIN users u ON m.sender_id = u.id LEFT JOIN users r ON m.receiver_id = r.id';
    $select = "m.*, u.username as sender_name, r.username as receiver_name";
    $where[] = "m.id IS NOT NULL";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$count_query = "SELECT COUNT(*) as total FROM {$table} {$join} {$where_clause}";
$count_stmt = mysqli_prepare($conn, $count_query);
if ($count_stmt) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($count_stmt, $types, ...$params);
    }
    mysqli_stmt_execute($count_stmt);
    $total = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];
} else {
    $total = 0;
}
$total_pages = ceil($total / $per_page);
$offset = ($page - 1) * $per_page;

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$query = "SELECT {$select} FROM {$table} {$join} {$where_clause} ORDER BY m.created_at DESC LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $query);
$messages = [];
if ($stmt) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $messages[] = $row;
        }
    }
}

$page_title = 'Messages';
include 'includes/header.php';
?>

<div class="admin-card mb-4">
    <div class="card-body">
        <form method="GET" class="filters-bar">
            <div class="filter-group">
                <label for="msg-type" class="sr-only">Message type</label>
                <select id="msg-type" name="type" class="form-select">
                    <option value="personal" <?php echo $type === 'personal' ? 'selected' : ''; ?>>Personal</option>
                    <option value="group" <?php echo $type === 'group' ? 'selected' : ''; ?>>Group</option>
                </select>
            </div>
            <div class="filter-group" style="flex: 1;">
                <label for="msg-search" class="sr-only">Search messages</label>
                <input type="text" id="msg-search" name="search" class="form-control" placeholder="Search messages..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1;" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            <a href="messages.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="card-header">
        <h2>Messages (<?php echo number_format($total); ?>)</h2>
        <button class="btn btn-sm btn-secondary" onclick="exportCSV('messagesTable', 'messages.csv')">
            <i class="fas fa-download"></i> Export
        </button>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="admin-table" id="messagesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <?php if ($type === 'group'): ?>
                            <th>Group</th>
                        <?php else: ?>
                            <th>From</th>
                            <th>To</th>
                        <?php endif; ?>
                        <th>Content</th>
                        <th>Type</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                    <tr>
                        <td><?php echo $msg['id']; ?></td>
                        <?php if ($type === 'group'): ?>
                            <td><?php echo htmlspecialchars($msg['group_name'] ?? 'Unknown'); ?></td>
                        <?php else: ?>
                            <td><?php echo htmlspecialchars($msg['sender_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($msg['receiver_name'] ?? 'Unknown'); ?></td>
                        <?php endif; ?>
                        <td><?php echo admin_truncate(htmlspecialchars($msg['content'] ?? $msg['message'] ?? ''), 50); ?></td>
                        <td><span class="badge bg-secondary"><?php echo ucfirst($msg['message_type'] ?? 'text'); ?></span></td>
                        <td><?php echo admin_time_ago($msg['created_at']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($messages)): ?>
                    <tr><td colspan="6"><div class="empty-state"><i class="fas fa-comments"></i><h3>No messages found</h3></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer">
        <?php
        $base_url = 'messages.php?' . http_build_query(['search' => $search, 'type' => $type]);
        echo admin_pagination($total, $per_page, $page, $base_url);
        ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
