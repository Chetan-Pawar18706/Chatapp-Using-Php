<?php
/**
 * =====================================================
 * Admin Header
 * ChatApp - Common Admin Header
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

$admin_data = admin_get_data();
$admin_avatar = admin_get_avatar($admin_data);
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($admin_data['theme'] ?? 'dark'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo admin_csrf_token(); ?>">
    <title><?php echo $page_title ?? 'Admin'; ?> - ChatApp Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Admin CSS -->
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-logo">
                    <i class="fas fa-shield-halved"></i>
                    <span>ChatApp Admin</span>
                </a>
                <button class="sidebar-close" id="sidebarClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                        <a href="dashboard.php">
                            <i class="fas fa-chart-pie"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'users' ? 'active' : ''; ?>">
                        <a href="users.php">
                            <i class="fas fa-users"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'groups' ? 'active' : ''; ?>">
                        <a href="groups.php">
                            <i class="fas fa-user-group"></i>
                            <span>Groups</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'messages' ? 'active' : ''; ?>">
                        <a href="messages.php">
                            <i class="fas fa-comments"></i>
                            <span>Messages</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                        <a href="reports.php">
                            <i class="fas fa-flag"></i>
                            <span>Reports</span>
                            <?php
                            $pending_reports = 0;
                            $report_query = "SELECT COUNT(*) as count FROM user_reports WHERE status = 'pending'";
                            $report_result = mysqli_query($conn, $report_query);
                            if ($report_result) {
                                $report_row = mysqli_fetch_assoc($report_result);
                                $pending_reports = $report_row['count'];
                            }
                            if ($pending_reports > 0):
                            ?>
                                <span class="badge bg-danger"><?php echo $pending_reports; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'blocked' ? 'active' : ''; ?>">
                        <a href="blocked.php">
                            <i class="fas fa-ban"></i>
                            <span>Blocked Users</span>
                        </a>
                    </li>
                    
                    <li class="nav-section">System</li>
                    
                    <li class="<?php echo $current_page === 'logs' ? 'active' : ''; ?>">
                        <a href="logs.php">
                            <i class="fas fa-file-lines"></i>
                            <span>Logs</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'statistics' ? 'active' : ''; ?>">
                        <a href="statistics.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Statistics</span>
                        </a>
                    </li>
                    <?php if (admin_has_permission('manage_settings')): ?>
                    <li class="<?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                        <a href="settings.php">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../index.php" target="_blank" class="sidebar-link">
                    <i class="fas fa-external-link-alt"></i>
                    <span>View Site</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <!-- Top Bar -->
            <header class="admin-header">
                <div class="header-left">
                    <button class="btn-icon" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title"><?php echo $page_title ?? 'Dashboard'; ?></h1>
                </div>
                
                <div class="header-right">
                    <div class="header-search">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search..." id="globalSearch">
                    </div>
                    
                    <button class="btn-icon" id="notificationsBtn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notifBadge">0</span>
                    </button>
                    
                    <div class="admin-profile dropdown">
                        <button class="profile-btn dropdown-toggle" data-bs-toggle="dropdown">
                            <img src="<?php echo $admin_avatar; ?>" alt="Admin" class="profile-avatar">
                            <div class="profile-info">
                                <span class="profile-name"><?php echo htmlspecialchars($admin_data['full_name'] ?? $admin_data['username']); ?></span>
                                <span class="profile-role"><?php echo ADMIN_ROLES[$admin_data['role']] ?? $admin_data['role']; ?></span>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="api/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="admin-content">
