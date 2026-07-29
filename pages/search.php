<?php
/**
 * =====================================================
 * Global Search Page
 * ChatApp - Professional Search Interface
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notification_helpers.php';
require_once __DIR__ . '/../includes/notification_component.php';
require_once __DIR__ . '/../includes/search_helpers.php';
require_once __DIR__ . '/../includes/sidebar.php';

session_initialize();

if (!session_is_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$user_id = session_get_user_id();
$user = db_fetch_single("SELECT * FROM users WHERE id = ?", [$user_id], 'i');
$csrf_token = session_generate_csrf();

$recent_searches = get_recent_searches($user_id, null, 10);
$initial_query = trim($_GET['q'] ?? '');
$initial_type = $_GET['type'] ?? 'all';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars(get_user_theme()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title>Search - ChatApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/search.css" rel="stylesheet">
    <link href="../assets/css/notifications.css" rel="stylesheet">
</head>
<body>
    <?php echo render_sidebar('search', $user, $user_id); ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-wrapper">
        <?php echo render_top_navbar('Search', $user, $user_id); ?>

        <main class="main-content">
            <div class="search-container">
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

                <div class="search-results-container" id="searchResults">
                    <div class="loading-state" id="loadingState" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Searching...</p>
                    </div>

                    <div class="empty-state" id="emptyState">
                        <i class="fas fa-search"></i>
                        <h4>Search ChatApp</h4>
                        <p>Find users, friends, groups, and messages</p>
                    </div>

                    <div class="no-results-state" id="noResults" style="display: none;">
                        <i class="fas fa-sad-tear"></i>
                        <h4>No results found</h4>
                        <p>Try different keywords or check your filters</p>
                    </div>

                    <div class="results-content" id="resultsContent" style="display: none;">
                        <div class="results-section" id="usersSection" style="display: none;">
                            <div class="section-header">
                                <h3><i class="fas fa-users"></i> Users</h3>
                                <button class="see-all-btn" data-type="users">See All</button>
                            </div>
                            <div class="results-list" id="usersList"></div>
                        </div>

                        <div class="results-section" id="friendsSection" style="display: none;">
                            <div class="section-header">
                                <h3><i class="fas fa-user-friends"></i> Friends</h3>
                                <button class="see-all-btn" data-type="friends">See All</button>
                            </div>
                            <div class="results-list" id="friendsList"></div>
                        </div>

                        <div class="results-section" id="groupsSection" style="display: none;">
                            <div class="section-header">
                                <h3><i class="fas fa-people-group"></i> Groups</h3>
                                <button class="see-all-btn" data-type="groups">See All</button>
                            </div>
                            <div class="results-list" id="groupsList"></div>
                        </div>

                        <div class="results-section" id="messagesSection" style="display: none;">
                            <div class="section-header">
                                <h3><i class="fas fa-comments"></i> Messages</h3>
                                <button class="see-all-btn" data-type="messages">See All</button>
                            </div>
                            <div class="results-list" id="messagesList"></div>
                        </div>
                    </div>
                </div>

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
    </div>

    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/search.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <?php echo render_sidebar_scripts(); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($initial_query): ?>
            SearchModule.performSearch('<?php echo addslashes($initial_query); ?>');
            <?php endif; ?>
        });
    </script>
</body>
</html>
