<?php
/**
 * =====================================================
 * Global Search Page
 * ChatApp - Professional Search Interface
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notification_helpers.php';
require_once __DIR__ . '/../includes/notification_component.php';
require_once __DIR__ . '/../includes/search_helpers.php';

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

// Get recent searches
$recent_searches = get_recent_searches($user_id, null, 10);

// Get initial search query from URL
$initial_query = trim($_GET['q'] ?? '');
$initial_type = $_GET['type'] ?? 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Search - ChatApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/search.css">
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
            <a href="search.php" class="nav-link active">
                <i class="fas fa-search"></i>
                <span>Search</span>
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
        <div class="search-container">
            <!-- Search Header -->
            <div class="search-header">
                <h2><i class="fas fa-search"></i> Global Search</h2>
            </div>

            <!-- Search Box -->
            <div class="search-box-wrapper">
                <div class="search-input-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" 
                           id="searchInput" 
                           class="search-input" 
                           placeholder="Search users, friends, groups, messages..."
                           value="<?php echo htmlspecialchars($initial_query); ?>"
                           autocomplete="off">
                    <button class="clear-search-btn" id="clearSearch" style="display: none;">
                        <i class="fas fa-times"></i>
                    </button>
                    <button class="search-submit-btn" id="searchBtn">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
                
                <!-- Search Filters -->
                <div class="search-filters">
                    <button class="filter-btn active" data-filter="all">
                        <i class="fas fa-globe"></i> All
                    </button>
                    <button class="filter-btn" data-filter="friends">
                        <i class="fas fa-user-friends"></i> Friends
                    </button>
                    <button class="filter-btn" data-filter="users">
                        <i class="fas fa-users"></i> Users
                    </button>
                    <button class="filter-btn" data-filter="groups">
                        <i class="fas fa-people-group"></i> Groups
                    </button>
                    <button class="filter-btn" data-filter="messages">
                        <i class="fas fa-comments"></i> Messages
                    </button>
                </div>
            </div>

            <!-- Search Results -->
            <div class="search-results-container" id="searchResults">
                <!-- Loading State -->
                <div class="loading-state" id="loadingState" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Searching...</p>
                </div>

                <!-- Empty State -->
                <div class="empty-state" id="emptyState">
                    <i class="fas fa-search"></i>
                    <h4>Search ChatApp</h4>
                    <p>Find users, friends, groups, and messages</p>
                </div>

                <!-- No Results State -->
                <div class="no-results-state" id="noResults" style="display: none;">
                    <i class="fas fa-sad-tear"></i>
                    <h4>No results found</h4>
                    <p>Try different keywords or check your filters</p>
                </div>

                <!-- Results Content -->
                <div class="results-content" id="resultsContent" style="display: none;">
                    <!-- Users Section -->
                    <div class="results-section" id="usersSection" style="display: none;">
                        <div class="section-header">
                            <h3><i class="fas fa-users"></i> Users</h3>
                            <button class="see-all-btn" data-type="users">See All</button>
                        </div>
                        <div class="results-list" id="usersList"></div>
                    </div>

                    <!-- Friends Section -->
                    <div class="results-section" id="friendsSection" style="display: none;">
                        <div class="section-header">
                            <h3><i class="fas fa-user-friends"></i> Friends</h3>
                            <button class="see-all-btn" data-type="friends">See All</button>
                        </div>
                        <div class="results-list" id="friendsList"></div>
                    </div>

                    <!-- Groups Section -->
                    <div class="results-section" id="groupsSection" style="display: none;">
                        <div class="section-header">
                            <h3><i class="fas fa-people-group"></i> Groups</h3>
                            <button class="see-all-btn" data-type="groups">See All</button>
                        </div>
                        <div class="results-list" id="groupsList"></div>
                    </div>

                    <!-- Messages Section -->
                    <div class="results-section" id="messagesSection" style="display: none;">
                        <div class="section-header">
                            <h3><i class="fas fa-comments"></i> Messages</h3>
                            <button class="see-all-btn" data-type="messages">See All</button>
                        </div>
                        <div class="results-list" id="messagesList"></div>
                    </div>
                </div>
            </div>

            <!-- Recent Searches -->
            <div class="recent-searches" id="recentSearches" <?php echo empty($recent_searches) ? 'style="display: none;"' : ''; ?>>
                <div class="recent-header">
                    <h3><i class="fas fa-history"></i> Recent Searches</h3>
                    <button class="clear-all-btn" id="clearRecent">
                        <i class="fas fa-trash-alt"></i> Clear All
                    </button>
                </div>
                <div class="recent-list" id="recentList">
                    <?php foreach ($recent_searches as $search): ?>
                        <div class="recent-item" data-id="<?php echo $search['id']; ?>" data-type="<?php echo $search['search_type']; ?>">
                            <div class="recent-icon">
                                <i class="fas <?php 
                                    echo $search['search_type'] === 'user' ? 'fa-user' : 
                                         ($search['search_type'] === 'group' ? 'fa-people-group' : 'fa-comment');
                                ?>"></i>
                            </div>
                            <div class="recent-info">
                                <span class="recent-query"><?php echo htmlspecialchars($search['search_query']); ?></span>
                                <span class="recent-time"><?php echo $search['time_ago']; ?></span>
                            </div>
                            <button class="delete-recent-btn" data-id="<?php echo $search['id']; ?>">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/search.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script>
        // Set initial query if present
        <?php if ($initial_query): ?>
        document.addEventListener('DOMContentLoaded', function() {
            SearchModule.performSearch('<?php echo addslashes($initial_query); ?>');
        });
        <?php endif; ?>
    </script>
</body>
</html>
