<?php
/**
 * =====================================================
 * Shared Sidebar Component
 * ChatApp - Consistent Navigation Across All Pages
 * =====================================================
 */

if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

/**
 * Render Sidebar Navigation
 * 
 * @param string $active_page Current page name (e.g., 'dashboard', 'chat', 'search')
 * @param array $user_data User data array
 * @param int $user_id User ID
 * @return string HTML output
 */
function render_sidebar($active_page, $user_data, $user_id) {
    $username = htmlspecialchars($user_data['username'] ?? 'User');
    $avatar_html = render_avatar_html($user_data['avatar'] ?? null, $user_data['username'] ?? 'User');
    
    $nav_items = [
        ['page' => 'dashboard', 'icon' => 'fa-home', 'label' => 'Dashboard', 'url' => 'dashboard.php'],
        ['page' => 'chat', 'icon' => 'fa-message', 'label' => 'Chats', 'url' => 'chat.php'],
        ['page' => 'friends', 'icon' => 'fa-user-group', 'label' => 'Friends', 'url' => 'dashboard.php#friends'],
        ['page' => 'requests', 'icon' => 'fa-user-plus', 'label' => 'Requests', 'url' => 'dashboard.php#requests'],
        ['page' => 'groups', 'icon' => 'fa-people-group', 'label' => 'Groups', 'url' => 'dashboard.php#groups'],
        ['page' => 'group-chat', 'icon' => 'fa-people-group', 'label' => 'Group Chat', 'url' => 'group-chat.php'],
        ['page' => 'notifications', 'icon' => 'fa-bell', 'label' => 'Notifications', 'url' => 'notifications.php'],
        ['page' => 'media', 'icon' => 'fa-photo-film', 'label' => 'Media', 'url' => 'media.php'],
        ['page' => 'search', 'icon' => 'fa-search', 'label' => 'Search', 'url' => 'search.php'],
        ['page' => 'settings', 'icon' => 'fa-cog', 'label' => 'Settings', 'url' => 'settings.php'],
    ];
    
    $html = '<aside class="sidebar collapsed" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <span class="logo-icon"><i class="fas fa-comments"></i></span>
                <span class="logo-text">ChatApp</span>
            </div>
            <button class="sidebar-close" id="sidebarClose"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="sidebar-user">
            ' . $avatar_html . '
            <div class="user-info">
                <div class="user-name">' . $username . '</div>
                <div class="user-status">
                    <span class="status-dot online"></span>
                    <span>Online</span>
                </div>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav-list">';
    
    foreach ($nav_items as $item) {
        $active_class = ($item['page'] === $active_page) ? ' active' : '';
        $html .= '<li class="nav-item' . $active_class . '">
                    <a href="' . $item['url'] . '" class="nav-link">
                        <i class="fas ' . $item['icon'] . '"></i>
                        <span>' . $item['label'] . '</span>
                    </a>
                </li>';
    }
    
    $html .= '</ul>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../api/logout.php" class="nav-link logout-btn" onclick="if(!confirm(\'Are you sure you want to logout?\')) return false;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>';
    
    return $html;
}

/**
 * Render Top Navbar
 * 
 * @param string $page_title Title to display in the navbar
 * @param array $user_data User data array
 * @param int $user_id User ID
 * @return string HTML output
 */
function render_top_navbar($page_title, $user_data, $user_id) {
    $username = htmlspecialchars($user_data['username'] ?? 'User');
    $email = htmlspecialchars($user_data['email'] ?? '');
    $avatar_html = render_avatar_html($user_data['avatar'] ?? null, $user_data['username'] ?? 'User', 'user-avatar');
    
    $html = '<header class="top-navbar">
        <div class="navbar-left">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title">' . htmlspecialchars($page_title) . '</h1>
        </div>
        
        <div class="navbar-right">';
    
    // Notification bell
    $html .= render_notification_bell($user_id);
    
    // User menu
    $html .= '<div class="navbar-user">
            <div id="navbarAvatar">' . $avatar_html . '</div>
            <div class="user-dropdown" id="userDropdown">
                <div class="dropdown-header">
                    <div class="user-avatar large">' . $avatar_html . '</div>
                    <div>
                        <div class="user-name">' . $username . '</div>
                        <div class="user-email">' . $email . '</div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="settings.php" class="dropdown-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="../api/logout.php" class="dropdown-item" onclick="if(!confirm(\'Are you sure you want to logout?\')) return false;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</header>';
    
    return $html;
}

/**
 * Render Sidebar JavaScript (for non-dashboard pages)
 * 
 * @return string JS code
 */
function render_sidebar_scripts() {
    return '<script>
    document.addEventListener("DOMContentLoaded", function() {
        var sidebar = document.getElementById("sidebar");
        var sidebarToggle = document.getElementById("sidebarToggle");
        var sidebarClose = document.getElementById("sidebarClose");
        var sidebarOverlay = document.getElementById("sidebarOverlay");
        var navbarAvatar = document.getElementById("navbarAvatar");
        var userDropdown = document.getElementById("userDropdown");
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener("click", function() {
                sidebar.classList.add("show");
                sidebarOverlay.classList.add("show");
                document.body.style.overflow = "hidden";
            });
        }
        
        function closeSidebar() {
            sidebar.classList.remove("show");
            sidebarOverlay.classList.remove("show");
            document.body.style.overflow = "";
        }
        
        if (sidebarClose) sidebarClose.addEventListener("click", closeSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener("click", closeSidebar);
        
        if (navbarAvatar && userDropdown) {
            navbarAvatar.addEventListener("click", function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle("show");
            });
            document.addEventListener("click", function() {
                userDropdown.classList.remove("show");
            });
        }
    });
    </script>';
}
