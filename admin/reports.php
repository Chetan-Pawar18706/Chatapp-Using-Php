<?php
/**
 * =====================================================
 * Admin Reports Management
 * ChatApp - User Reports
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

$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = ADMIN_PER_PAGE;

$where = [];
$params = [];
$types = '';

if (!empty($status) && in_array($status, ['pending', 'reviewed', 'resolved', 'dismissed'])) {
    $where[] = "r.status = ?";
    $params[] = $status;
    $types .= 's';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$count_query = "SELECT COUNT(*) as total FROM user_reports r {$where_clause}";
$count_stmt = mysqli_prepare($conn, $count_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];
$total_pages = ceil($total / $per_page);
$offset = ($page - 1) * $per_page;

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$query = "SELECT r.*, 
          reporter.username as reporter_name,
          reported.username as reported_name,
          admin.username as reviewer_name
          FROM user_reports r
          LEFT JOIN users reporter ON r.reporter_id = reporter.id
          LEFT JOIN users reported ON r.reported_user_id = reported.id
          LEFT JOIN admin_users admin ON r.reviewed_by = admin.id
          {$where_clause}
          ORDER BY r.created_at DESC
          LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$reports = [];
while ($row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    $reports[] = $row;
}

$page_title = 'Reports';
include 'includes/header.php';
?>

<div class="admin-card mb-4">
    <div class="card-body">
        <form method="GET" class="filters-bar">
            <div class="filter-group">
                <label for="report-status" class="sr-only">Status</label>
                <select id="report-status" name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="reviewed" <?php echo $status === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                    <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="dismissed" <?php echo $status === 'dismissed' ? 'selected' : ''; ?>>Dismissed</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
            <a href="reports.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="card-header">
        <h2>Reports (<?php echo number_format($total); ?>)</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Reporter</th>
                        <th>Reported User</th>
                        <th>Reason</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                    <tr>
                        <td>
                            <a href="user-view.php?id=<?php echo $report['reporter_id']; ?>">
                                <?php echo htmlspecialchars($report['reporter_name']); ?>
                            </a>
                        </td>
                        <td>
                            <a href="user-view.php?id=<?php echo $report['reported_user_id']; ?>">
                                <?php echo htmlspecialchars($report['reported_name']); ?>
                            </a>
                        </td>
                        <td><?php echo admin_report_reason($report['reason']); ?></td>
                        <td><?php echo admin_truncate(htmlspecialchars($report['description'] ?? ''), 40); ?></td>
                        <td><?php echo admin_status_badge($report['status']); ?></td>
                        <td><?php echo admin_time_ago($report['created_at']); ?></td>
                        <td>
                            <?php if ($report['status'] === 'pending'): ?>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-success" onclick="updateReportStatus(<?php echo $report['id']; ?>, 'resolved')" data-bs-toggle="tooltip" title="Resolve">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="updateReportStatus(<?php echo $report['id']; ?>, 'dismissed')" data-bs-toggle="tooltip" title="Dismiss">
                                    <i class="fas fa-times"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="toggleUserStatus(<?php echo $report['reported_user_id']; ?>, 'ban')" data-bs-toggle="tooltip" title="Ban User">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </div>
                            <?php else: ?>
                            <span style="color: var(--text-muted);">Reviewed by <?php echo htmlspecialchars($report['reviewer_name'] ?? 'System'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($reports)): ?>
                    <tr><td colspan="7"><div class="empty-state"><i class="fas fa-flag"></i><h3>No reports found</h3></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer">
        <?php
        $base_url = 'reports.php?' . http_build_query(['status' => $status]);
        echo admin_pagination($total, $per_page, $page, $base_url . '&page=');
        ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
