<?php
/**
 * =====================================================
 * Admin Users Management
 * ChatApp - User List & Management
 * =====================================================
 */

define('APP_RUNNING', true);

require_once dirname(__DIR__) . '/config/database.php';
require_once 'config.php';
require_once 'auth.php';
require_once 'helpers.php';

admin_session_init();

// Check authentication
if (!admin_verify_session()) {
    header('Location: index.php');
    exit;
}

$conn = db_connect();

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = ADMIN_PER_PAGE;

// Build query
$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(username LIKE ? OR email LIKE ? OR friend_code LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

if (!empty($status) && in_array($status, ['active', 'inactive', 'banned'])) {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users {$where_clause}";
$count_stmt = mysqli_prepare($conn, $count_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];
$total_pages = ceil($total / $per_page);
$offset = ($page - 1) * $per_page;

// Get users
$query = "SELECT id, username, email, friend_code, avatar, status, is_online, 
          created_at, last_seen 
          FROM users {$where_clause} 
          ORDER BY created_at DESC 
          LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

$page_title = 'Users Management';
include 'includes/header.php';
?>

<!-- Search & Filters -->
<div class="admin-card mb-4">
    <div class="card-body">
        <form method="GET" class="filters-bar">
            <div class="filter-group" style="flex: 1;">
                <label for="user-search" class="sr-only">Search users</label>
                <i class="fas fa-search" style="color: var(--text-muted);"></i>
                <input type="text" id="user-search" name="search" class="form-control" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1;" autocomplete="off">
            </div>
            <div class="filter-group">
                <label for="user-status" class="sr-only">Status</label>
                <select id="user-status" name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="banned" <?php echo $status === 'banned' ? 'selected' : ''; ?>>Banned</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="users.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear
            </a>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="admin-card">
    <div class="card-header">
        <h2>Users (<?php echo number_format($total); ?>)</h2>
        <div class="btn-group">
            <button class="btn btn-sm btn-secondary" onclick="exportCSV('usersTable', 'users.csv')">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="admin-table" id="usersTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Friend Code</th>
                        <th>Status</th>
                        <th>Online</th>
                        <th>Joined</th>
                        <th>Last Seen</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <?php echo admin_user_avatar($user, 40); ?>
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td><code><?php echo htmlspecialchars($user['friend_code']); ?></code></td>
                        <td><?php echo admin_status_badge($user['status']); ?></td>
                        <td>
                            <?php if ($user['is_online']): ?>
                                <span class="badge bg-success">Online</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Offline</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo admin_time_ago($user['created_at']); ?></td>
                        <td><?php echo $user['last_seen'] ? admin_time_ago($user['last_seen']) : 'Never'; ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="user-view.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline" data-bs-toggle="tooltip" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($user['status'] !== 'banned'): ?>
                                <button class="btn btn-sm btn-warning" onclick="toggleUserStatus(<?php echo $user['id']; ?>, 'ban')" data-bs-toggle="tooltip" title="Ban">
                                    <i class="fas fa-ban"></i>
                                </button>
                                <?php else: ?>
                                <button class="btn btn-sm btn-success" onclick="toggleUserStatus(<?php echo $user['id']; ?>, 'unban')" data-bs-toggle="tooltip" title="Unban">
                                    <i class="fas fa-check"></i>
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)" data-bs-toggle="tooltip" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <h3>No users found</h3>
                                <p>Try adjusting your search or filters</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer">
        <?php
        $base_url = 'users.php?' . http_build_query(array_filter(['search' => $search, 'status' => $status]));
        echo admin_pagination($total, $per_page, $page, $base_url . '&page=');
        ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
