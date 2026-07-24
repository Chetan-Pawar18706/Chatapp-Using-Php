<?php
/**
 * =====================================================
 * Notification Bell Component
 * ChatApp - Reusable Notification UI
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

/**
 * Render Notification Bell with Counter
 * 
 * @param int $user_id User ID
 * @return string HTML output
 */
function render_notification_bell($user_id) {
    $unread_count = get_unread_notification_count($user_id);
    
    $html = '
    <div class="notification-bell" id="notificationBell">
        <button class="bell-button" onclick="NotificationModule.toggleDropdown()" aria-label="Notifications">
            <i class="fas fa-bell"></i>';
    
    if ($unread_count > 0) {
        $display_count = $unread_count > 99 ? '99+' : $unread_count;
        $html .= '<span class="notification-badge" id="notificationBadge">' . $display_count . '</span>';
    }
    
    $html .= '
        </button>
        
        <div class="notification-dropdown" id="notificationDropdown">
            <div class="dropdown-header">
                <h4>Notifications</h4>
                <div class="header-actions">
                    <button class="btn-link" onclick="NotificationModule.markAllRead()" title="Mark all as read">
                        <i class="fas fa-check-double"></i>
                    </button>
                    <button class="btn-link" onclick="NotificationModule.clearAll()" title="Clear all">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
            
            <div class="notification-tabs">
                <button class="tab-btn active" data-filter="all">All</button>
                <button class="tab-btn" data-filter="unread">Unread</button>
            </div>
            
            <div class="notification-list" id="notificationList">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
            
            <div class="dropdown-footer">
                <a href="pages/notifications.php" class="view-all-link">View All Notifications</a>
            </div>
        </div>
    </div>';
    
    return $html;
}

/**
 * Render Notification Item
 * 
 * @param array $notification Notification data
 * @return string HTML output
 */
function render_notification_item($notification) {
    $id = intval($notification['id']);
    $type = $notification['type'];
    $is_read = $notification['is_read'];
    $sender_name = htmlspecialchars($notification['sender_username'] ?? 'System');
    $message = format_notification_message($notification);
    $time_ago = $notification['time_ago'];
    $icon = get_notification_icon($type);
    $color = get_notification_color($type);
    
    // Get avatar or generate initials
    $avatar_html = '';
    if (!empty($notification['sender_avatar'])) {
        $avatar_html = '<img src="' . htmlspecialchars($notification['sender_avatar']) . '" alt="' . $sender_name . '">';
    } else {
        $initials = strtoupper(substr($sender_name, 0, 2));
        $avatar_html = '<div class="avatar-initials small">' . $initials . '</div>';
    }
    
    // Determine action URL based on type
    $action_url = '#';
    switch ($type) {
        case 'friend_request':
            $action_url = 'pages/dashboard.php#friends';
            break;
        case 'message':
            $action_url = 'pages/chat.php?user=' . ($notification['data']['sender_id'] ?? '');
            break;
        case 'mention':
        case 'group_invite':
            $action_url = 'pages/group-chat.php?id=' . ($notification['data']['group_id'] ?? '');
            break;
    }
    
    $html = '
    <div class="notification-item ' . ($is_read ? '' : 'unread') . '" 
         data-id="' . $id . '" 
         data-type="' . $type . '"
         onclick="NotificationModule.handleClick(' . $id . ', \'' . $action_url . '\')">
        
        <div class="notification-avatar ' . $color . '">
            ' . $avatar_html . '
            <span class="type-icon">
                <i class="fas ' . $icon . '"></i>
            </span>
        </div>
        
        <div class="notification-content">
            <p class="notification-message">' . $message . '</p>
            <span class="notification-time">' . $time_ago . '</span>
        </div>
        
        <div class="notification-actions">
            ' . (!$is_read ? '<button class="action-btn" onclick="event.stopPropagation(); NotificationModule.markRead(' . $id . ')" title="Mark as read"><i class="fas fa-circle"></i></button>' : '') . '
            <button class="action-btn" onclick="event.stopPropagation(); NotificationModule.delete(' . $id . ')" title="Delete"><i class="fas fa-times"></i></button>
        </div>
    </div>';
    
    return $html;
}

/**
 * Render Full Notifications Page
 * 
 * @param int $user_id User ID
 * @return string HTML output
 */
function render_notifications_page($user_id) {
    $notifications = get_user_notifications($user_id, 50, 0);
    $unread_count = get_unread_notification_count($user_id);
    
    $html = '<div class="notifications-page">';
    
    // Header
    $html .= '
    <div class="page-header">
        <h2><i class="fas fa-bell"></i> Notifications</h2>
        <div class="header-actions">
            <span class="unread-count">' . $unread_count . ' unread</span>
            <button class="btn btn-outline" onclick="NotificationModule.markAllRead()">Mark All Read</button>
            <button class="btn btn-outline btn-danger" onclick="NotificationModule.clearAll()">Clear All</button>
        </div>
    </div>';
    
    // Filter tabs
    $html .= '
    <div class="filter-tabs">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="friend_request">Friends</button>
        <button class="filter-btn" data-filter="message">Messages</button>
        <button class="filter-btn" data-filter="mention">Mentions</button>
        <button class="filter-btn" data-filter="group_invite">Groups</button>
        <button class="filter-btn" data-filter="system">System</button>
    </div>';
    
    // Notifications list
    $html .= '<div class="notifications-list" id="allNotifications">';
    
    if (empty($notifications)) {
        $html .= '
        <div class="empty-state">
            <i class="fas fa-bell-slash"></i>
            <h4>No notifications yet</h4>
            <p>You\'ll see notifications here when you have new activity</p>
        </div>';
    } else {
        foreach ($notifications as $notification) {
            $html .= render_notification_item($notification);
        }
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}
