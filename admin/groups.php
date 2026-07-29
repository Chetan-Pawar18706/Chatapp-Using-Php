<?php
/**
 * =====================================================
 * Admin Groups Management
 * ChatApp - Group List & Management
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

// Get filter parameters
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = ADMIN_PER_PAGE;

$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(g.name LIKE ? OR g.description LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM `groups` g {$where_clause}";
$count_stmt = mysqli_prepare($conn, $count_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];
$total_pages = ceil($total / $per_page);
$offset = ($page - 1) * $per_page;

// Get groups with member count
$query = "SELECT g.*, u.username as creator_name,
          (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
          FROM `groups` g
          LEFT JOIN users u ON g.created_by = u.id
          {$where_clause}
          ORDER BY g.created_at DESC
          LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$groups = [];
while ($row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    $groups[] = $row;
}

$page_title = 'Groups Management';
include 'includes/header.php';
?>

<div class="admin-card mb-4">
    <div class="card-body">
        <form method="GET" class="filters-bar">
            <div class="filter-group" style="flex: 1;">
                <label for="group-search" class="sr-only">Search groups</label>
                <i class="fas fa-search" style="color: var(--text-muted);"></i>
                <input type="text" id="group-search" name="search" class="form-control" placeholder="Search groups..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1;" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
            <a href="groups.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="card-header">
        <h2>Groups (<?php echo number_format($total); ?>)</h2>
        <button class="btn btn-sm btn-secondary" onclick="exportCSV('groupsTable', 'groups.csv')">
            <i class="fas fa-download"></i> Export
        </button>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="admin-table" id="groupsTable">
                <thead>
                    <tr>
                        <th>Group</th>
                        <th>Creator</th>
                        <th>Members</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($group['name']); ?></h4>
                                    <p><?php echo admin_truncate(htmlspecialchars($group['description'] ?? ''), 40); ?></p>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($group['creator_name'] ?? 'Unknown'); ?></td>
                        <td><span class="badge bg-info"><?php echo $group['member_count']; ?> members</span></td>
                        <td><?php echo admin_status_badge($group['status']); ?></td>
                        <td><?php echo admin_time_ago($group['created_at']); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="group-view.php?id=<?php echo $group['id']; ?>" class="btn btn-sm btn-outline" data-bs-toggle="tooltip" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button class="btn btn-sm btn-danger" onclick="deleteGroup(<?php echo $group['id']; ?>)" data-bs-toggle="tooltip" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($groups)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-user-group"></i>
                                <h3>No groups found</h3>
                                <p>Try adjusting your search</p>
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
        $base_url = 'groups.php?' . http_build_query(['search' => $search]);
        echo admin_pagination($total, $per_page, $page, $base_url);
        ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
