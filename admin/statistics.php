<?php
/**
 * =====================================================
 * Admin Statistics
 * ChatApp - Detailed Analytics
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

// Gather statistics
$stats = [];

// User stats
$user_stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) as banned,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week,
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as month
FROM users"));

// Message stats
$msg_stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week,
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as month
FROM messages"));

// Group stats
$group_stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
FROM groups"));

// Daily registration data (30 days)
$daily_users = [];
$result = mysqli_query($conn, "SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at) ORDER BY date");
while ($row = mysqli_fetch_assoc($result)) {
    $daily_users[] = $row;
}

// Daily message data (30 days)
$daily_msgs = [];
$result = mysqli_query($conn, "SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at) ORDER BY date");
while ($row = mysqli_fetch_assoc($result)) {
    $daily_msgs[] = $row;
}

// Top users by messages
$top_users = [];
$result = mysqli_query($conn, "SELECT u.username, u.avatar, COUNT(m.id) as msg_count
    FROM messages m JOIN users u ON m.sender_id = u.id
    GROUP BY m.sender_id ORDER BY msg_count DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($result)) {
    $top_users[] = $row;
}

// Top groups by activity
$top_groups = [];
$result = mysqli_query($conn, "SELECT g.name, COUNT(gm.id) as msg_count
    FROM group_messages gm JOIN groups g ON gm.group_id = g.id
    GROUP BY gm.group_id ORDER BY msg_count DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($result)) {
    $top_groups[] = $row;
}

$page_title = 'Statistics';
include 'includes/header.php';
?>

<!-- Overview Stats -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($user_stats['total']); ?></h3>
            <p>Total Users</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-user-check"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($user_stats['active']); ?></h3>
            <p>Active Users</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan"><i class="fas fa-comments"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($msg_stats['total']); ?></h3>
            <p>Total Messages</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="fas fa-user-group"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($group_stats['total']); ?></h3>
            <p>Total Groups</p>
        </div>
    </div>
</div>

<!-- Growth Stats -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="admin-card">
            <div class="card-body text-center">
                <h4 style="color: var(--text-secondary); margin-bottom: 12px;">Today</h4>
                <div class="d-flex justify-content-around">
                    <div>
                        <h3 class="mb-0"><?php echo number_format($user_stats['today']); ?></h3>
                        <small style="color: var(--text-muted);">New Users</small>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo number_format($msg_stats['today']); ?></h3>
                        <small style="color: var(--text-muted);">Messages</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-card">
            <div class="card-body text-center">
                <h4 style="color: var(--text-secondary); margin-bottom: 12px;">This Week</h4>
                <div class="d-flex justify-content-around">
                    <div>
                        <h3 class="mb-0"><?php echo number_format($user_stats['week']); ?></h3>
                        <small style="color: var(--text-muted);">New Users</small>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo number_format($msg_stats['week']); ?></h3>
                        <small style="color: var(--text-muted);">Messages</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-card">
            <div class="card-body text-center">
                <h4 style="color: var(--text-secondary); margin-bottom: 12px;">This Month</h4>
                <div class="d-flex justify-content-around">
                    <div>
                        <h3 class="mb-0"><?php echo number_format($user_stats['month']); ?></h3>
                        <small style="color: var(--text-muted);">New Users</small>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo number_format($msg_stats['month']); ?></h3>
                        <small style="color: var(--text-muted);">Messages</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="card-header"><h2>Daily Activity (30 Days)</h2></div>
            <div class="card-body">
                <div class="chart-container"><canvas id="dailyChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="card-header"><h2>User Status</h2></div>
            <div class="card-body">
                <div class="chart-container"><canvas id="statusChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Top Lists -->
<div class="row">
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="card-header"><h2>Top Users by Messages</h2></div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead><tr><th>#</th><th>User</th><th>Messages</th></tr></thead>
                        <tbody>
                            <?php foreach ($top_users as $i => $u): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td>
                                    <div class="user-cell">
                                        <?php echo admin_user_avatar($u, 32); ?>
                                        <span><?php echo htmlspecialchars($u['username']); ?></span>
                                    </div>
                                </td>
                                <td><strong><?php echo number_format($u['msg_count']); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($top_users)): ?>
                            <tr><td colspan="3" class="text-center">No data</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="card-header"><h2>Top Groups by Activity</h2></div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead><tr><th>#</th><th>Group</th><th>Messages</th></tr></thead>
                        <tbody>
                            <?php foreach ($top_groups as $i => $g): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo htmlspecialchars($g['name']); ?></td>
                                <td><strong><?php echo number_format($g['msg_count']); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($top_groups)): ?>
                            <tr><td colspan="3" class="text-center">No data</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Prepare chart data
$labels = [];
$user_data = [];
$msg_data = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('M d', strtotime($date));
    $uc = 0;
    foreach ($daily_users as $d) { if ($d['date'] === $date) { $uc = $d['count']; break; } }
    $user_data[] = $uc;
    $mc = 0;
    foreach ($daily_msgs as $d) { if ($d['date'] === $date) { $mc = $d['count']; break; } }
    $msg_data[] = $mc;
}

$extra_scripts = '<script>
const labels = ' . json_encode($labels) . ';
const userData = ' . json_encode($user_data) . ';
const msgData = ' . json_encode($msg_data) . ';

createChart("dailyChart", "line", {
    labels: labels,
    datasets: [
        { label: "Users", data: userData, borderColor: "#6366f1", backgroundColor: "rgba(99,102,241,0.1)", fill: true, tension: 0.4 },
        { label: "Messages", data: msgData, borderColor: "#22c55e", backgroundColor: "rgba(34,197,94,0.1)", fill: true, tension: 0.4 }
    ]
});

createChart("statusChart", "doughnut", {
    labels: ["Active", "Banned", "Inactive"],
    datasets: [{
        data: [' . ($user_stats['active'] ?? 0) . ', ' . ($user_stats['banned'] ?? 0) . ', ' . (($user_stats['total'] ?? 0) - ($user_stats['active'] ?? 0) - ($user_stats['banned'] ?? 0)) . '],
        backgroundColor: ["#22c55e", "#ef4444", "#64748b"]
    }]
}, { plugins: { legend: { position: "bottom" } } });
</script>';

include 'includes/footer.php';
?>
