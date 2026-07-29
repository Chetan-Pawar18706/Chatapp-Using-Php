<?php
/**
 * =====================================================
 * Admin Logs
 * ChatApp - Activity & Security Logs
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
$tab = $_GET['tab'] ?? 'admin';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = ADMIN_PER_PAGE;
$logs = [];
$total = 0;
$total_pages = 1;

if ($tab === 'security') {
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM security_log");
    $total = $count_result ? (int)mysqli_fetch_assoc($count_result)['total'] : 0;
    $total_pages = max(1, ceil($total / $per_page));
    $offset = ($page - 1) * $per_page;

    $query = "SELECT * FROM security_log ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ii', $per_page, $offset);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $logs[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM admin_activity_log");
    $total = $count_result ? (int)mysqli_fetch_assoc($count_result)['total'] : 0;
    $total_pages = max(1, ceil($total / $per_page));
    $offset = ($page - 1) * $per_page;

    $query = "SELECT al.*, au.username as admin_name 
              FROM admin_activity_log al 
              LEFT JOIN admin_users au ON al.admin_id = au.id 
              ORDER BY al.created_at DESC 
              LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ii', $per_page, $offset);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $logs[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

$page_title = 'Logs';
include 'includes/header.php';
?>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'admin' ? 'active' : ''; ?>" href="logs.php?tab=admin">Admin Activity</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'security' ? 'active' : ''; ?>" href="logs.php?tab=security">Security Logs</a>
    </li>
</ul>

<div class="admin-card">
    <div class="card-header">
        <h2><?php echo $tab === 'admin' ? 'Admin Activity' : 'Security Logs'; ?> (<?php echo number_format($total); ?>)</h2>
        <button class="btn btn-sm btn-secondary" onclick="exportCSV('<?php echo $tab === 'admin' ? 'adminLogsTable' : 'securityLogsTable'; ?>', '<?php echo $tab; ?>-logs.csv')">
            <i class="fas fa-download"></i> Export
        </button>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <?php if ($tab === 'admin'): ?>
            <table class="admin-table" id="adminLogsTable">
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>IP Address</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['admin_name'] ?? 'System'); ?></td>
                        <td><span class="badge bg-info"><?php echo htmlspecialchars($log['action']); ?></span></td>
                        <td><?php echo $log['target_type'] ? htmlspecialchars($log['target_type']) . ' #' . $log['target_id'] : '-'; ?></td>
                        <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
                        <td><?php echo admin_time_ago($log['created_at']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="5"><div class="empty-state"><i class="fas fa-file-lines"></i><h3>No logs found</h3></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php else: ?>
            <table class="admin-table" id="securityLogsTable">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>IP Address</th>
                        <th>User</th>
                        <th>Details</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><span class="badge bg-warning"><?php echo htmlspecialchars($log['event']); ?></span></td>
                        <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
                        <td><?php echo $log['user_id'] ? 'User #' . $log['user_id'] : '-'; ?></td>
                        <td><?php echo admin_truncate(htmlspecialchars($log['details'] ?? ''), 40); ?></td>
                        <td><?php echo admin_time_ago($log['created_at']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="5"><div class="empty-state"><i class="fas fa-shield-halved"></i><h3>No security logs</h3></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer">
        <?php echo admin_pagination($total, $per_page, $page, "logs.php?tab={$tab}"); ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
