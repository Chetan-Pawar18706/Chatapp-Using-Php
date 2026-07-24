<?php
/**
 * =====================================================
 * Notifications Page
 * ChatApp - View All Notifications
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notification_helpers.php';
require_once __DIR__ . '/../includes/notification_component.php';

init_session();

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$user_id = get_user_id();

// Get user info
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Notifications - ChatApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
</head>
<body class="dark-theme">
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="dashboard.php" class="logo">
                <i class="fas fa-comments"></i>
                <span>ChatApp</span>
            </a>
        </div>
        <div class="navbar-nav">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="chat.php" class="nav-link">
                <i class="fas fa-message"></i>
                <span>Chats</span>
            </a>
            <a href="notifications.php" class="nav-link active">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
        </div>
        <div class="navbar-actions">
            <?php echo render_notification_bell($user_id); ?>
            
            <div class="user-menu">
                <button class="user-btn">
                    <div class="avatar">
                        <?php if ($user['avatar']): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar">
                        <?php else: ?>
                            <div class="avatar-initials">
                                <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </button>
                <div class="dropdown-menu">
                    <a href="dashboard.php" class="dropdown-item">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="settings.php" class="dropdown-item">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <hr class="dropdown-divider">
                    <a href="../api/logout.php" class="dropdown-item text-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <?php echo render_notifications_page($user_id); ?>
    </main>

    <!-- Scripts -->
    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script>
        // Initialize page-specific functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Filter tabs on full page
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filter = this.dataset.filter;
                    filterNotifications(filter);
                });
            });
            
            function filterNotifications(type) {
                document.querySelectorAll('.notification-item').forEach(item => {
                    if (type === 'all' || item.dataset.type === type) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>
