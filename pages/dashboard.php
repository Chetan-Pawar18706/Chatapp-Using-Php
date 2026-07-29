<?php
/**
 * =====================================================
 * Dashboard Page
 * ChatApp - Protected User Dashboard
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
    <title>Dashboard - ChatApp</title>
    
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
    <!-- Sidebar (shared component) -->
    <?php echo render_sidebar('dashboard', $user_data, $user_id); ?>
    
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
                <h1 class="page-title" id="pageTitle">Dashboard</h1>
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
            <!-- Dashboard Section -->
            <section class="content-section active" id="section-dashboard">
                <!-- Welcome Card -->
                <div class="welcome-card">
                    <div class="welcome-content">
                        <h2>Welcome back, <span id="welcomeUsername"><?php echo htmlspecialchars($user_data['username'] ?? 'User'); ?></span>!</h2>
                        <p>Here's what's happening with your chats today.</p>
                    </div>
                    <div class="welcome-icon">
                        <i class="fas fa-hand-wave"></i>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-user-group"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value" id="statFriends">0</div>
                            <div class="stat-label">Friends</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-message"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value" id="statMessages">0</div>
                            <div class="stat-label">Unread Messages</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value" id="statRequests">0</div>
                            <div class="stat-label">Pending Requests</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="fas fa-people-group"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value" id="statGroups">0</div>
                            <div class="stat-label">Groups</div>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Card & Friend Code -->
                <div class="dashboard-grid">
                    <!-- Profile Card -->
                    <div class="card profile-card">
                        <div class="card-header">
                            <h3><i class="fas fa-user"></i> My Profile</h3>
                        </div>
                        <div class="card-body">
                            <div class="profile-main">
                                <div class="profile-avatar large" id="profileAvatar">
                                    <?php echo render_avatar_html($user_data['avatar'] ?? null, $user_data['username'] ?? 'User', 'user-avatar large'); ?>
                                </div>
                                <div class="profile-details">
                                    <h4 class="profile-name"><?php echo htmlspecialchars($user_data['username'] ?? 'User'); ?></h4>
                                    <p class="profile-email"><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></p>
                                    <div class="online-status">
                                        <span class="status-dot online"></span>
                                        <span>Online</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Friend Code -->
                            <div class="friend-code-box">
                                <div class="friend-code-label">
                                    <i class="fas fa-share-nodes"></i>
                                    <span>Your Friend Code</span>
                                </div>
                                <div class="friend-code-value">
                                    <code id="friendCode"><?php echo htmlspecialchars($user_data['friend_code'] ?? 'N/A'); ?></code>
                                    <button class="copy-btn" id="copyFriendCode" title="Copy to clipboard">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <div class="friend-code-hint">Share this code with friends to connect</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add Friend Card -->
                    <div class="card add-friend-card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-plus"></i> Add Friend</h3>
                        </div>
                        <div class="card-body">
                            <p class="card-text">Enter a friend code to send a connection request</p>
                            <div class="add-friend-form">
                                <input type="text" class="form-control" id="friendCodeInput" 
                                       placeholder="e.g., CHT-84D93K" maxlength="10">
                                <button class="btn btn-primary" id="addFriendBtn">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>
                            <div id="addFriendAlert" class="alert mt-3"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Chats & Friend Requests -->
                <div class="dashboard-grid">
                    <!-- Recent Chats -->
                    <div class="card chats-card">
                        <div class="card-header">
                            <h3><i class="fas fa-message"></i> Recent Chats</h3>
                            <a href="#" class="view-all" data-section="chats">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="chats-list" id="recentChatsList">
                                <div class="empty-state">
                                    <i class="fas fa-comments"></i>
                                    <p>No recent chats</p>
                                    <span>Start a conversation with a friend!</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Friend Requests -->
                    <div class="card requests-card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-plus"></i> Friend Requests</h3>
                            <a href="#" class="view-all" data-section="requests">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="requests-list" id="friendRequestsList">
                                <div class="empty-state">
                                    <i class="fas fa-user-check"></i>
                                    <p>No pending requests</p>
                                    <span>You're all caught up!</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Groups Section -->
                <div class="card groups-card">
                    <div class="card-header">
                        <h3><i class="fas fa-people-group"></i> My Groups</h3>
                        <a href="#" class="view-all" data-section="groups">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="groups-grid" id="groupsList">
                            <div class="empty-state">
                                <i class="fas fa-people-group"></i>
                                <p>No groups yet</p>
                                <span>Create or join a group to get started!</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Chats Section -->
            <section class="content-section" id="section-chats">
                <div class="section-header">
                    <h2>Chats</h2>
                    <button class="icon-btn" id="lockedChatsBtn" title="Locked Chats" onclick="toggleDashboardLockedChats()">
                        <i class="fas fa-lock"></i>
                    </button>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="chats-full-list" id="chatsFullList">
                            <div class="empty-state">
                                <i class="fas fa-comments"></i>
                                <p>No conversations yet</p>
                                <span>Add friends to start chatting!</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Friends Section (Link to dedicated page) -->
            <section class="content-section" id="section-friends">
                <div class="section-header">
                    <h2><i class="fas fa-user-group"></i> Friends</h2>
                    <a href="friends.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-arrow-right"></i> View All Friends
                    </a>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="friends-list" id="friendsList">
                            <div class="empty-state">
                                <i class="fas fa-user-group"></i>
                                <p>No friends yet</p>
                                <span>Search by username or friend code to add friends!</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Requests Section (Link to dedicated page) -->
            <section class="content-section" id="section-requests">
                <div class="section-header">
                    <h2><i class="fas fa-user-plus"></i> Friend Requests</h2>
                    <a href="requests.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-arrow-right"></i> View All Requests
                    </a>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="requests-list" id="friendRequestsList">
                            <div class="empty-state">
                                <i class="fas fa-user-check"></i>
                                <p>No pending requests</p>
                                <span>You're all caught up!</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Groups Section -->
            <section class="content-section" id="section-groups">
                <div class="section-header">
                    <h2>Groups</h2>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="groups-full-list" id="groupsFullList">
                            <div class="empty-state">
                                <i class="fas fa-people-group"></i>
                                <p>No groups yet</p>
                                <span>Create a group to start chatting!</span>
                            </div>
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
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/friends.js"></script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
