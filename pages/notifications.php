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
require_once __DIR__ . '/../includes/sidebar.php';

init_session();

if (!is_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$user_id = get_user_id();

$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

$csrf_token = session_generate_csrf();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars(get_user_theme()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Notifications - ChatApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/notifications.css" rel="stylesheet">
</head>
<body>
    <?php echo render_sidebar('notifications', $user, $user_id); ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-wrapper">
        <?php echo render_top_navbar('Notifications', $user, $user_id); ?>

        <main class="main-content">
            <?php echo render_notifications_page($user_id); ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <?php echo render_sidebar_scripts(); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.filter-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.filter-btn').forEach(function(b) { b.classList.remove('active'); });
                    this.classList.add('active');
                    var filter = this.dataset.filter;
                    document.querySelectorAll('.notification-item').forEach(function(item) {
                        if (filter === 'all' || item.dataset.type === filter) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>
