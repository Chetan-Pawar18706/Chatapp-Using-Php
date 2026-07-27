<?php
/**
 * =====================================================
 * Friends Page
 * ChatApp - View & Manage Friends
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/notification_helpers.php';
require_once __DIR__ . '/../includes/notification_component.php';

session_initialize();

if (!session_verify_security()) {
    app_session_destroy();
    header('Location: ../login.php');
    exit;
}

if (!session_is_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$user_id = session_get_user_id();
$user_data = session_get_user_data();
$csrf_token = session_generate_csrf();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars(get_user_theme()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title>Friends - ChatApp</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/friends.css" rel="stylesheet">
    <link href="../assets/css/notifications.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <?php echo render_sidebar('friends', $user_data, $user_id); ?>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Main Content -->
    <div class="main-wrapper">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <div class="navbar-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Friends</h1>
            </div>
            
            <div class="navbar-right">
                <!-- Search -->
                <div class="navbar-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search users..." id="searchInput">
                    <div class="search-results" id="searchResults"></div>
                </div>
                
                <!-- Notifications -->
                <?php echo render_notification_bell($user_id); ?>
                
                <!-- User Menu -->
                <div class="navbar-user">
                    <div id="navbarAvatar">
                        <?php echo render_avatar_html($user_data['avatar'] ?? null, $user_data['username'] ?? 'User'); ?>
                    </div>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="dropdown-header">
                            <div id="dropdownAvatar">
                                <?php echo render_avatar_html($user_data['avatar'] ?? null, $user_data['username'] ?? 'User', 'user-avatar large'); ?>
                            </div>
                            <div>
                                <div class="user-name"><?php echo htmlspecialchars($user_data['username'] ?? 'User'); ?></div>
                                <div class="user-email"><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="settings.php" class="dropdown-item">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                        <a href="#" class="dropdown-item" data-action="logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <section class="content-section active" id="section-friends">
                <!-- Add Friend Card -->
                <div class="add-friend-inline">
                    <div class="add-friend-form">
                        <input type="text" class="form-control" id="friendCodeInput" 
                               placeholder="Enter friend code (e.g., CHT-84D93K)" maxlength="10">
                        <button class="btn btn-primary" id="addFriendBtn">
                            <i class="fas fa-user-plus"></i> Add Friend
                        </button>
                    </div>
                    <div id="addFriendAlert" class="alert mt-3"></div>
                </div>
                
                <!-- Search & Filter Bar -->
                <div class="friends-toolbar">
                    <div class="friends-search">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search friends..." id="friendsSearchInput">
                    </div>
                    <div class="friends-filter">
                        <button class="filter-btn active" data-filter="all">All</button>
                        <button class="filter-btn" data-filter="online">Online</button>
                        <button class="filter-btn" data-filter="offline">Offline</button>
                    </div>
                    <div class="friends-stats">
                        <span id="friendsOnlineCount">0 online</span>
                        <span class="separator">|</span>
                        <span id="friendsTotalCount">0 total</span>
                    </div>
                </div>
                
                <!-- Friends List -->
                <div class="friends-list-container">
                    <div class="friends-list" id="friendsList">
                        <div class="empty-state">
                            <i class="fas fa-user-group"></i>
                            <p>No friends yet</p>
                            <span>Search by username or friend code to add friends!</span>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/friends.js"></script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
