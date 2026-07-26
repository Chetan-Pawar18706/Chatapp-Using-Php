<?php
/**
 * =====================================================
 * Profile Page
 * ChatApp - View User Profile
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sidebar.php';

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

$current_user_id = session_get_user_id();
$current_user = db_fetch_single("SELECT * FROM users WHERE id = ?", [$current_user_id], 'i');
$csrf_token = session_generate_csrf();

// Get profile user ID
$profile_user_id = (int)($_GET['user_id'] ?? $current_user_id);
$is_own_profile = ($profile_user_id === $current_user_id);

// Get profile user data
$profile_user = db_fetch_single("SELECT id, username, avatar, cover_photo, bio, about, status, is_online, last_seen, created_at FROM users WHERE id = ? AND status = 'active'", [$profile_user_id], 'i');

if (!$profile_user) {
    header('Location: dashboard.php');
    exit;
}

// Check friendship status
$friendship_status = 'none';
$friendship = null;
if (!$is_own_profile) {
    $friendship = db_fetch_single(
        "SELECT id, status FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)",
        [$current_user_id, $profile_user_id, $profile_user_id, $current_user_id],
        'iiii'
    );
    if ($friendship) {
        $friendship_status = $friendship['status'];
    }
}

// Check if blocked
$is_blocked = false;
if (!$is_own_profile) {
    $block = db_fetch_single(
        "SELECT id FROM block_list WHERE (user_id = ? AND blocked_user_id = ?) OR (user_id = ? AND blocked_user_id = ?)",
        [$current_user_id, $profile_user_id, $profile_user_id, $current_user_id],
        'iiii'
    );
    $is_blocked = (bool)$block;
}

// Count stats
$media_count = db_fetch_single("SELECT COUNT(*) as c FROM media WHERE user_id = ?", [$profile_user_id], 'i');
$mutual_friends = 0;
if (!$is_own_profile && $friendship_status === 'accepted') {
    $mutual = db_fetch_single(
        "SELECT COUNT(*) as c FROM friendships f
         JOIN friendships f2 ON (f.friend_id = f2.friend_id OR f.friend_id = f2.user_id)
         WHERE f.user_id = ? AND f.status = 'accepted' 
         AND f2.user_id = ? AND f2.status = 'accepted'
         AND f2.friend_id != ?",
        [$current_user_id, $profile_user_id, $current_user_id],
        'iii'
    );
    $mutual_friends = (int)($mutual['c'] ?? 0);
}

// Get mutual friends list
$mutual_list = [];
if ($mutual_friends > 0) {
    $mutual_rows = db_fetch_all(
        "SELECT u.id, u.username, u.avatar FROM users u
         WHERE u.id IN (
             SELECT f2.friend_id FROM friendships f
             JOIN friendships f2 ON f.friend_id = f2.friend_id OR f.friend_id = f2.user_id
             WHERE f.user_id = ? AND f.status = 'accepted'
             AND f2.user_id = ? AND f2.status = 'accepted'
             AND f2.friend_id != ?
         ) AND u.id != ? LIMIT 5",
        [$current_user_id, $profile_user_id, $current_user_id, $current_user_id],
        'iiii'
    );
    $mutual_list = $mutual_rows;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars(get_user_theme()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title><?php echo htmlspecialchars($profile_user['username']); ?> - Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .profile-page {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: var(--bg-body);
        }

        .profile-cover {
            height: 240px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 40%, #a78bfa 70%, #c4b5fd 100%);
            position: relative;
            border-radius: 0 0 24px 24px;
            overflow: hidden;
        }

        .profile-cover::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: linear-gradient(to top, rgba(0,0,0,0.3), transparent);
        }

        .profile-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-cover .cover-pattern {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }

        .profile-main {
            max-width: 720px;
            margin: 0 auto 2rem;
            padding: 0 1.5rem;
        }

        .profile-card {
            background: var(--bg-sidebar);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            overflow: visible;
            box-shadow: 0 4px 24px rgba(0,0,0,0.15);
        }

        .profile-cover-wrap {
            height: 240px;
            border-radius: 20px 20px 0 0;
            overflow: hidden;
            position: relative;
        }

        .profile-cover-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-cover-wrap .cover-gradient {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 40%, #a78bfa 70%, #c4b5fd 100%);
        }

        .profile-header {
            display: flex;
            align-items: flex-end;
            gap: 1.25rem;
            padding: 0 2rem 1.25rem;
            margin-top: -50px;
            position: relative;
            z-index: 2;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid var(--bg-sidebar);
            overflow: hidden;
            flex-shrink: 0;
            background: linear-gradient(135deg, #6366f1, #a78bfa);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-avatar .avatar-initials {
            font-size: 2.2rem;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .profile-info {
            flex: 1;
            min-width: 0;
        }

        .profile-info h1 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-white);
            line-height: 1.2;
        }

        .profile-info .profile-status {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 0.3rem;
        }

        .profile-info .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--offline);
            flex-shrink: 0;
        }

        .profile-info .status-dot.online {
            background: var(--online);
            box-shadow: 0 0 0 2px rgba(34,197,94,0.3);
        }

        .profile-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .profile-body {
            padding: 0.5rem 2rem 2rem;
        }

        .profile-bio {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.7;
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--border-color);
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }

        .profile-stat {
            text-align: center;
            padding: 1rem 0.75rem;
            background: var(--bg-hover);
            border-radius: 14px;
            border: 1px solid var(--border-color);
            transition: transform 0.2s, border-color 0.2s;
        }

        .profile-stat:hover {
            transform: translateY(-2px);
            border-color: var(--primary);
        }

        .profile-stat .stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-size: 0.9rem;
        }

        .profile-stat .stat-icon.media {
            background: rgba(99,102,241,0.15);
            color: #6366f1;
        }

        .profile-stat .stat-icon.friend {
            background: rgba(34,197,94,0.15);
            color: #22c55e;
        }

        .profile-stat .stat-icon.joined {
            background: rgba(168,85,247,0.15);
            color: #a855f7;
        }

        .profile-stat .stat-value {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-white);
            line-height: 1.2;
        }

        .profile-stat .stat-label {
            font-size: 0.72rem;
            color: var(--text-secondary);
            margin-top: 0.2rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .profile-section-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
        }

        .mutual-friends {
            margin-top: 0;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border-color);
        }

        .mutual-list {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .mutual-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem 0.35rem 0.35rem;
            background: var(--bg-hover);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            text-decoration: none;
            color: var(--text-primary);
            font-size: 0.82rem;
            transition: all 0.2s;
        }

        .mutual-item:hover {
            background: var(--border-color);
            border-color: var(--primary);
            transform: translateY(-1px);
        }

        .mutual-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #6366f1, #a78bfa);
            font-size: 0.65rem;
            font-weight: 600;
            color: #fff;
            flex-shrink: 0;
        }

        .mutual-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .btn-message {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.55rem 1.1rem;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-message:hover {
            background: var(--primary-hover);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99,102,241,0.3);
        }

        .btn-friend {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            padding: 0.55rem 1.1rem;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-friend:hover {
            background: var(--bg-hover);
            border-color: var(--primary);
            color: var(--primary-light);
            transform: translateY(-1px);
        }

        .btn-friend.pending {
            border-color: rgba(245,158,11,0.5);
            color: #f59e0b;
            background: rgba(245,158,11,0.1);
        }

        .btn-friend.accepted {
            border-color: rgba(34,197,94,0.5);
            color: #22c55e;
            background: rgba(34,197,94,0.1);
        }

        @media (max-width: 768px) {
            .profile-page {
                margin-left: 0;
            }

            .profile-cover {
                height: 180px;
            }

            .profile-cover-wrap {
                height: 180px;
            }

            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 0 1rem 1rem;
                margin-top: -40px;
                gap: 0.75rem;
            }

            .profile-info h1 {
                font-size: 1.2rem;
            }

            .profile-actions {
                justify-content: center;
            }

            .profile-body {
                padding: 0 1rem 1.5rem;
            }

            .profile-stats {
                gap: 0.5rem;
            }

            .profile-stat {
                padding: 0.75rem 0.5rem;
            }

            .profile-stat .stat-value {
                font-size: 1rem;
            }

            .mutual-list {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php echo render_sidebar('profile', $current_user, $current_user_id); ?>

    <div class="profile-page">
        <div class="profile-main">
            <div class="profile-card">
                <div class="profile-cover-wrap">
                    <?php if (!empty($profile_user['cover_photo'])): ?>
                        <img src="<?php echo htmlspecialchars($profile_user['cover_photo']); ?>" alt="Cover">
                    <?php else: ?>
                        <div class="cover-gradient"></div>
                    <?php endif; ?>
                    <div class="cover-pattern"></div>
                </div>

                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php if (!empty($profile_user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($profile_user['avatar']); ?>" alt="<?php echo htmlspecialchars($profile_user['username']); ?>">
                        <?php else: ?>
                            <span class="avatar-initials"><?php echo strtoupper(substr($profile_user['username'], 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h1><?php echo htmlspecialchars($profile_user['username']); ?></h1>
                        <div class="profile-status">
                            <span class="status-dot <?php echo $profile_user['is_online'] ? 'online' : ''; ?>"></span>
                            <span><?php echo $profile_user['is_online'] ? 'Online now' : 'Last seen ' . time_ago($profile_user['last_seen']); ?></span>
                        </div>
                    </div>
                    <div class="profile-actions">
                        <?php if ($is_own_profile): ?>
                            <a href="settings.php" class="btn-message"><i class="fas fa-pen-to-square"></i> Edit</a>
                        <?php else: ?>
                            <a href="chat.php?user_id=<?php echo $profile_user_id; ?>" class="btn-message" id="msgBtn">
                                <i class="fas fa-comment"></i> Message
                            </a>
                            <?php if ($friendship_status === 'none' && !$is_blocked): ?>
                                <button class="btn-friend" id="addFriendBtn" onclick="sendFriendRequest(<?php echo $profile_user_id; ?>)">
                                    <i class="fas fa-user-plus"></i> Add
                                </button>
                            <?php elseif ($friendship_status === 'pending'): ?>
                                <button class="btn-friend pending">
                                    <i class="fas fa-clock"></i> Pending
                                </button>
                            <?php elseif ($friendship_status === 'accepted'): ?>
                                <button class="btn-friend accepted">
                                    <i class="fas fa-user-check"></i> Friends
                                </button>
                            <?php elseif ($is_blocked): ?>
                                <button class="btn-friend" style="border-color: rgba(239,68,68,0.5); color: #ef4444; background: rgba(239,68,68,0.1);">
                                    <i class="fas fa-ban"></i> Blocked
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-body">
                    <?php if (!empty($profile_user['bio'])): ?>
                        <div class="profile-bio"><?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?></div>
                    <?php endif; ?>

                    <div class="profile-stats">
                        <div class="profile-stat">
                            <div class="stat-icon media"><i class="fas fa-photo-film"></i></div>
                            <div class="stat-value"><?php echo (int)($media_count['c'] ?? 0); ?></div>
                            <div class="stat-label">Media</div>
                        </div>
                        <div class="profile-stat">
                            <div class="stat-icon friend"><i class="fas fa-user-group"></i></div>
                            <div class="stat-value"><?php echo $friendship_status === 'accepted' ? 'Yes' : 'No'; ?></div>
                            <div class="stat-label">Friend</div>
                        </div>
                        <div class="profile-stat">
                            <div class="stat-icon joined"><i class="fas fa-calendar-check"></i></div>
                            <div class="stat-value"><?php echo date('M Y', strtotime($profile_user['created_at'])); ?></div>
                            <div class="stat-label">Joined</div>
                        </div>
                    </div>

                    <?php if ($mutual_friends > 0): ?>
                        <div class="mutual-friends">
                            <div class="profile-section-title">Mutual Friends (<?php echo $mutual_friends; ?>)</div>
                            <div class="mutual-list">
                                <?php foreach ($mutual_list as $mutual): ?>
                                    <a href="profile.php?user_id=<?php echo $mutual['id']; ?>" class="mutual-item">
                                        <div class="mutual-avatar">
                                            <?php if (!empty($mutual['avatar'])): ?>
                                                <img src="<?php echo htmlspecialchars($mutual['avatar']); ?>" alt="">
                                            <?php else: ?>
                                                <?php echo strtoupper(substr($mutual['username'], 0, 1)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php echo htmlspecialchars($mutual['username']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        async function sendFriendRequest(userId) {
            const result = await ChatApp.apiRequest('/send-friend-request.php', 'POST', {
                user_id: userId,
                csrf_token: '<?php echo $csrf_token; ?>'
            });
            
            if (result.success) {
                const btn = document.getElementById('addFriendBtn');
                btn.innerHTML = '<i class="fas fa-clock"></i> Request Sent';
                btn.className = 'btn-friend pending';
                btn.onclick = null;
                ChatApp.showToast('Friend request sent!', 'success');
            } else {
                ChatApp.showToast(result.message || 'Failed to send request', 'error');
            }
        }
    </script>
    <?php echo render_sidebar_scripts(); ?>
</body>
</html>
