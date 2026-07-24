<?php
/**
 * =====================================================
 * Admin Dashboard
 * ChatApp - Main Dashboard with Statistics
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

// Get statistics
$stats = [];

// Total users
$user_query = "SELECT COUNT(*) as total FROM users";
$user_result = mysqli_query($conn, $user_query);
$stats['total_users'] = mysqli_fetch_assoc($user_result)['total'];

// Active users (online in last 5 minutes)
$active_query = "SELECT COUNT(*) as total FROM users WHERE last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
$active_result = mysqli_query($conn, $active_query);
$stats['active_users'] = mysqli_fetch_assoc($active_result)['total'];

// New users today
$today_query = "SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()";
$today_result = mysqli_query($conn, $today_query);
$stats['new_today'] = mysqli_fetch_assoc($today_result)['total'];

// Total messages
$msg_query = "SELECT COUNT(*) as total FROM messages";
$msg_result = mysqli_query($conn, $msg_query);
$stats['total_messages'] = mysqli_fetch_assoc($msg_result)['total'];

// Messages today
$msg_today_query = "SELECT COUNT(*) as total FROM messages WHERE DATE(created_at) = CURDATE()";
$msg_today_result = mysqli_query($conn, $msg_today_query);
$stats['messages_today'] = mysqli_fetch_assoc($msg_today_result)['total'];

// Total groups
$group_query = "SELECT COUNT(*) as total FROM groups WHERE status = 'active'";
$group_result = mysqli_query($conn, $group_query);
$stats['total_groups'] = mysqli_fetch_assoc($group_result)['total'];

// Pending reports
$report_query = "SELECT COUNT(*) as total FROM user_reports WHERE status = 'pending'";
$report_result = mysqli_query($conn, $report_query);
$stats['pending_reports'] = mysqli_fetch_assoc($report_result)['total'];

// Banned users
$ban_query = "SELECT COUNT(*) as total FROM users WHERE status = 'banned'";
$ban_result = mysqli_query($conn, $ban_query);
$stats['banned_users'] = mysqli_fetch_assoc($ban_result)['total'];

// User registration chart data (last 7 days)
$chart_query = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
$chart_result = mysqli_query($conn, $chart_query);
$registration_data = [];
while ($row = mysqli_fetch_assoc($chart_result)) {
    $registration_data[] = $row;
}

// Message activity chart data (last 7 days)
$msg_chart_query = "SELECT DATE(created_at) as date, COUNT(*) as count 
                    FROM messages 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC";
$msg_chart_result = mysqli_query($conn, $msg_chart_query);
$message_data = [];
while ($row = mysqli_fetch_assoc($msg_chart_result)) {
    $message_data[] = $row;
}

// Recent users
$recent_users_query = "SELECT id, username, email, avatar, created_at, status 
                       FROM users ORDER BY created_at DESC LIMIT 5";
$recent_users_result = mysqli_query($conn, $recent_users_query);
$recent_users = [];
while ($row = mysqli_fetch_assoc($recent_users_result)) {
    $recent_users[] = $row;
}

// Recent activity logs
$activity_query = "SELECT al.*, au.username as admin_name 
                   FROM admin_activity_log al 
                   LEFT JOIN admin_users au ON al.admin_id = au.id 
                   ORDER BY al.created_at DESC LIMIT 10";
$activity_result = mysqli_query($conn, $activity_query);
$recent_activity = [];
while ($row = mysqli_fetch_assoc($activity_result)) {
    $recent_activity[] = $row;
}

$page_title = 'Dashboard';
include 'includes/header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['total_users']); ?></h3>
            <p>Total Users</p>
            <div class="stat-change up">
                <i class="fas fa-arrow-up"></i>
                +<?php echo $stats['new_today']; ?> today
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['active_users']); ?></h3>
            <p>Active Now</p>
            <div class="stat-change up">
                <i class="fas fa-circle" style="font-size: 8px;"></i>
                Online
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-comments"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['total_messages']); ?></h3>
            <p>Total Messages</p>
            <div class="stat-change up">
                <i class="fas fa-arrow-up"></i>
                +<?php echo $stats['messages_today']; ?> today
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon yellow">
            <i class="fas fa-user-group"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['total_groups']); ?></h3>
            <p>Active Groups</p>
            <div class="stat-change up">
                <i class="fas fa-arrow-up"></i>
                Growing
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fas fa-flag"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['pending_reports']); ?></h3>
            <p>Pending Reports</p>
            <?php if ($stats['pending_reports'] > 0): ?>
            <div class="stat-change down">
                <i class="fas fa-exclamation-circle"></i>
                Needs attention
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fas fa-ban"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['banned_users']); ?></h3>
            <p>Banned Users</p>
            <div class="stat-change">
                <i class="fas fa-shield-halved"></i>
                Enforced
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="card-header">
                <h2>User Registrations (Last 7 Days)</h2>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline active" onclick="updateChart('users')">Users</button>
                    <button class="btn btn-sm btn-outline" onclick="updateChart('messages')">Messages</button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="card-header">
                <h2>Recent Users</h2>
                <a href="users.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="admin-table">
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <?php echo admin_user_avatar($user, 32); ?>
                                        <div class="user-info">
                                            <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                                            <p><?php echo admin_time_ago($user['created_at']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo admin_status_badge($user['status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($recent_users)): ?>
                            <tr>
                                <td colspan="2" class="text-center" style="padding: 20px;">No users yet</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activity & Reports Row -->
<div class="row">
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="card-header">
                <h2>Recent Activity</h2>
                <a href="logs.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="card-body" style="padding: 0 20px;">
                <ul class="activity-list">
                    <?php foreach ($recent_activity as $activity): ?>
                    <li class="activity-item">
                        <div class="activity-icon" style="background: <?php echo $activity['action'] === 'login' ? 'var(--success-light)' : 'var(--accent-light)'; ?>; color: <?php echo $activity['action'] === 'login' ? 'var(--success)' : 'var(--accent)'; ?>;">
                            <i class="fas fa-<?php echo $activity['action'] === 'login' ? 'sign-in-alt' : ($activity['action'] === 'logout' ? 'sign-out-alt' : 'cog'); ?>"></i>
                        </div>
                        <div class="activity-content">
                            <p>
                                <strong><?php echo htmlspecialchars($activity['admin_name'] ?? 'System'); ?></strong>
                                <?php echo htmlspecialchars($activity['action']); ?>
                                <?php if ($activity['target_type']): ?>
                                    a <?php echo htmlspecialchars($activity['target_type']); ?>
                                <?php endif; ?>
                            </p>
                            <div class="time"><?php echo admin_time_ago($activity['created_at']); ?></div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                    
                    <?php if (empty($recent_activity)): ?>
                    <li class="activity-item">
                        <div class="activity-content text-center">
                            <p>No recent activity</p>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="card-header">
                <h2>Quick Actions</h2>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <a href="users.php" class="btn btn-outline w-100" style="padding: 20px; flex-direction: column;">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <span>Manage Users</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="groups.php" class="btn btn-outline w-100" style="padding: 20px; flex-direction: column;">
                            <i class="fas fa-user-group fa-2x mb-2"></i>
                            <span>Manage Groups</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="reports.php" class="btn btn-outline w-100" style="padding: 20px; flex-direction: column;">
                            <i class="fas fa-flag fa-2x mb-2"></i>
                            <span>View Reports</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="statistics.php" class="btn btn-outline w-100" style="padding: 20px; flex-direction: column;">
                            <i class="fas fa-chart-bar fa-2x mb-2"></i>
                            <span>Statistics</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Prepare chart data
$labels = [];
$user_counts = [];
$msg_counts = [];

// Fill in missing dates
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('M d', strtotime($date));
    
    $user_count = 0;
    foreach ($registration_data as $data) {
        if ($data['date'] === $date) {
            $user_count = $data['count'];
            break;
        }
    }
    $user_counts[] = $user_count;
    
    $msg_count = 0;
    foreach ($message_data as $data) {
        if ($data['date'] === $date) {
            $msg_count = $data['count'];
            break;
        }
    }
    $msg_counts[] = $msg_count;
}

$extra_scripts = '
<script>
const labels = ' . json_encode($labels) . ';
const userData = ' . json_encode($user_counts) . ';
const msgData = ' . json_encode($msg_counts) . ';

let activityChart = createChart("activityChart", "line", {
    labels: labels,
    datasets: [{
        label: "User Registrations",
        data: userData,
        borderColor: "#6366f1",
        backgroundColor: "rgba(99, 102, 241, 0.1)",
        fill: true,
        tension: 0.4
    }]
});

function updateChart(type) {
    const dataset = type === "users" ? {
        label: "User Registrations",
        data: userData,
        borderColor: "#6366f1",
        backgroundColor: "rgba(99, 102, 241, 0.1)"
    } : {
        label: "Messages Sent",
        data: msgData,
        borderColor: "#22c55e",
        backgroundColor: "rgba(34, 197, 94, 0.1)"
    };
    
    activityChart.data.datasets = [dataset];
    activityChart.update();
    
    document.querySelectorAll(".btn-group .btn").forEach(btn => btn.classList.remove("active"));
    event.target.classList.add("active");
}
</script>
';

include 'includes/footer.php';
?>
