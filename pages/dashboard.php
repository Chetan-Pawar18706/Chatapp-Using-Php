<?php
/**
 * =====================================================
 * Dashboard Page
 * ChatApp - Protected User Dashboard
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
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
<html lang="en">
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
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <span class="logo-icon">
                    <i class="fas fa-comments"></i>
                </span>
                <span class="logo-text">ChatApp</span>
            </div>
            <button class="sidebar-close" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- User Profile Mini -->
        <div class="sidebar-user">
            <div class="user-avatar" id="sidebarAvatar">
                <?php echo htmlspecialchars(substr($user_data['username'] ?? 'U', 0, 1)); ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user_data['username'] ?? 'User'); ?></div>
                <div class="user-status">
                    <span class="status-dot online"></span>
                    <span>Online</span>
                </div>
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <li class="nav-item active" data-section="dashboard">
                    <a href="#" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item" data-section="chats">
                    <a href="#" class="nav-link">
                        <i class="fas fa-message"></i>
                        <span>Chats</span>
                        <span class="badge" id="unreadBadge">0</span>
                    </a>
                </li>
                <li class="nav-item" data-section="friends">
                    <a href="#" class="nav-link">
                        <i class="fas fa-user-group"></i>
                        <span>Friends</span>
                    </a>
                </li>
                <li class="nav-item" data-section="requests">
                    <a href="#" class="nav-link">
                        <i class="fas fa-user-plus"></i>
                        <span>Requests</span>
                        <span class="badge" id="requestBadge">0</span>
                    </a>
                </li>
                <li class="nav-item" data-section="groups">
                    <a href="#" class="nav-link">
                        <i class="fas fa-people-group"></i>
                        <span>Groups</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="search.php" class="nav-link">
                        <i class="fas fa-search"></i>
                        <span>Search</span>
                    </a>
                </li>
                <li class="nav-item" data-section="settings">
                    <a href="#" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <a href="#" class="nav-link logout-btn" data-action="logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>
    
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
                    <div class="user-avatar" id="navbarAvatar">
                        <?php echo htmlspecialchars(substr($user_data['username'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="dropdown-header">
                            <div class="user-avatar large" id="dropdownAvatar">
                                <?php echo htmlspecialchars(substr($user_data['username'] ?? 'U', 0, 1)); ?>
                            </div>
                            <div>
                                <div class="user-name"><?php echo htmlspecialchars($user_data['username'] ?? 'User'); ?></div>
                                <div class="user-email"><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item" data-section="settings">
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
                                    <?php echo htmlspecialchars(substr($user_data['username'] ?? 'U', 0, 1)); ?>
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
            
            <!-- Friends Section -->
            <section class="content-section" id="section-friends">
                <div class="section-header">
                    <h2><i class="fas fa-user-group"></i> Friends</h2>
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
            
            <!-- Requests Section -->
            <section class="content-section" id="section-requests">
                <div class="section-header">
                    <h2><i class="fas fa-user-plus"></i> Friend Requests</h2>
                </div>
                
                <!-- Request Tabs -->
                <div class="request-tabs">
                    <button class="request-tab active" data-tab="received">
                        <i class="fas fa-inbox"></i> Received
                        <span class="tab-badge" id="receivedRequestsBadge">0</span>
                    </button>
                    <button class="request-tab" data-tab="sent">
                        <i class="fas fa-paper-plane"></i> Sent
                        <span class="tab-badge" id="sentRequestsBadge">0</span>
                    </button>
                </div>
                
                <!-- Received Requests -->
                <div class="request-tab-content active" id="receivedRequestsTab">
                    <div class="requests-list" id="receivedRequestsList">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No received requests</p>
                            <span>When someone sends you a request, it will appear here</span>
                        </div>
                    </div>
                </div>
                
                <!-- Sent Requests -->
                <div class="request-tab-content" id="sentRequestsTab">
                    <div class="requests-list" id="sentRequestsList">
                        <div class="empty-state">
                            <i class="fas fa-paper-plane"></i>
                            <p>No sent requests</p>
                            <span>Your outgoing requests will appear here</span>
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
            
            <!-- Settings Section -->
            <section class="content-section" id="section-settings">
                <div class="section-header">
                    <h2>Settings</h2>
                </div>
                
                <!-- Profile Settings -->
                <div class="card settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user"></i> Profile Settings</h3>
                    </div>
                    <div class="card-body">
                        <form id="profileSettingsForm">
                            <div class="form-group">
                                <label for="settingsUsername">Username</label>
                                <input type="text" class="form-control" id="settingsUsername" 
                                       value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="settingsBio">Bio</label>
                                <textarea class="form-control" id="settingsBio" rows="3" 
                                          placeholder="Tell us about yourself..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" id="saveProfileBtn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Email Settings -->
                <div class="card settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-envelope"></i> Email Settings</h3>
                    </div>
                    <div class="card-body">
                        <form id="emailSettingsForm">
                            <div class="form-group">
                                <label for="settingsEmail">New Email</label>
                                <input type="email" class="form-control" id="settingsEmail" 
                                       value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="emailPassword">Current Password</label>
                                <input type="password" class="form-control" id="emailPassword" 
                                       placeholder="Enter current password to confirm">
                            </div>
                            <button type="submit" class="btn btn-primary" id="saveEmailBtn">
                                <i class="fas fa-save"></i> Update Email
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Password Settings -->
                <div class="card settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-lock"></i> Change Password</h3>
                    </div>
                    <div class="card-body">
                        <form id="passwordSettingsForm">
                            <div class="form-group">
                                <label for="currentPassword">Current Password</label>
                                <input type="password" class="form-control" id="currentPassword" 
                                       placeholder="Enter current password">
                            </div>
                            <div class="form-group">
                                <label for="newPassword">New Password</label>
                                <input type="password" class="form-control" id="newPassword" 
                                       placeholder="Enter new password">
                            </div>
                            <div class="form-group">
                                <label for="confirmNewPassword">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirmNewPassword" 
                                       placeholder="Confirm new password">
                            </div>
                            <button type="submit" class="btn btn-primary" id="savePasswordBtn">
                                <i class="fas fa-save"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Danger Zone -->
                <div class="card settings-card danger-zone">
                    <div class="card-header">
                        <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                    </div>
                    <div class="card-body">
                        <p>Once you delete your account, there is no going back. Please be certain.</p>
                        <button class="btn btn-danger" id="deleteAccountBtn">
                            <i class="fas fa-trash"></i> Delete Account
                        </button>
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
