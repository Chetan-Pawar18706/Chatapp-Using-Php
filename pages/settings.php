<?php
/**
 * =====================================================
 * Settings Page - Complete
 * ChatApp - Professional Settings Interface
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

// Get blocked users
$blocked_query = "SELECT u.id, u.username, u.avatar, bl.created_at as blocked_at 
                  FROM block_list bl 
                  JOIN users u ON bl.blocked_user_id = u.id 
                  WHERE bl.user_id = ? 
                  ORDER BY bl.created_at DESC";
$blocked_stmt = mysqli_prepare($conn, $blocked_query);
mysqli_stmt_bind_param($blocked_stmt, 'i', $user_id);
mysqli_stmt_execute($blocked_stmt);
$blocked_result = mysqli_stmt_get_result($blocked_stmt);
$blocked_users = [];
while ($row = mysqli_fetch_assoc($blocked_result)) {
    $blocked_users[] = $row;
}

// Parse settings JSON
$settings = json_decode($user['settings'] ?? '{}', true) ?: [];
$privacy = $settings['privacy'] ?? [];
$notifications = $settings['notifications'] ?? [];

$csrf_token = session_generate_csrf();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($user['theme'] ?? 'dark'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Settings - ChatApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/settings-complete.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
</head>
<body>
    <?php echo render_sidebar('settings', $user, $user_id); ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-wrapper">
        <?php echo render_top_navbar('Settings', $user, $user_id); ?>

        <main class="main-content">
        <div class="settings-container">
            <!-- Settings Header -->
            <div class="settings-header">
                <h2><i class="fas fa-cog"></i> Settings</h2>
            </div>

            <div class="settings-layout">
                <!-- Sidebar Navigation -->
                <aside class="settings-sidebar">
                    <nav class="settings-nav">
                        <a href="#profile" class="nav-item active" data-tab="profile">
                            <i class="fas fa-user-circle"></i>
                            <span>Profile</span>
                        </a>
                        <a href="#appearance" class="nav-item" data-tab="appearance">
                            <i class="fas fa-palette"></i>
                            <span>Appearance</span>
                        </a>
                        <a href="#account" class="nav-item" data-tab="account">
                            <i class="fas fa-shield-alt"></i>
                            <span>Security</span>
                        </a>
                        <a href="#privacy" class="nav-item" data-tab="privacy">
                            <i class="fas fa-user-lock"></i>
                            <span>Privacy</span>
                        </a>
                        <a href="#notifications" class="nav-item" data-tab="notifications">
                            <i class="fas fa-bell"></i>
                            <span>Notifications</span>
                        </a>
                        <a href="#blocked" class="nav-item" data-tab="blocked">
                            <i class="fas fa-ban"></i>
                            <span>Blocked Users</span>
                        </a>
                        <a href="#data" class="nav-item" data-tab="data">
                            <i class="fas fa-database"></i>
                            <span>Data & Export</span>
                        </a>
                        <hr class="nav-divider">
                        <a href="#danger" class="nav-item danger" data-tab="danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Danger Zone</span>
                        </a>
                    </nav>
                </aside>

                <!-- Main Settings Content -->
                <div class="settings-content">
                    
                    <!-- ==================== PROFILE TAB ==================== -->
                    <div class="settings-tab active" id="profile">
                        <div class="tab-header">
                            <h3>Profile Settings</h3>
                            <p>Manage your public profile information</p>
                        </div>

                        <!-- Profile Photo -->
                        <div class="settings-section">
                            <h4>Profile Photo</h4>
                            <div class="avatar-upload-section">
                                <div class="current-avatar">
                                    <?php if ($user['avatar']): ?>
                                        <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar">
                                    <?php else: ?>
                                        <div class="avatar-initials large">
                                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="avatar-actions">
                                    <button class="btn btn-primary" onclick="document.getElementById('avatarInput').click()">
                                        <i class="fas fa-camera"></i> Change Photo
                                    </button>
                                    <button class="btn btn-outline-danger" id="removeAvatarBtn">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                    <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                                    <p class="help-text">JPG, PNG or GIF. Max size 5MB.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Username & Bio -->
                        <div class="settings-section">
                            <h4>Personal Information</h4>
                            <form id="profileForm">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                
                                <div class="form-group">
                                    <label for="profileUsername">Username</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-at"></i>
                                        <input type="text" id="profileUsername" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="profileBio">Bio</label>
                                    <textarea id="profileBio" name="bio" rows="3" maxlength="150" placeholder="Write something about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                    <div class="char-counter"><span id="bioCount"><?php echo strlen($user['bio'] ?? ''); ?></span>/150</div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Profile
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- ==================== APPEARANCE TAB ==================== -->
                    <div class="settings-tab" id="appearance">
                        <div class="tab-header">
                            <h3>Appearance</h3>
                            <p>Customize how ChatApp looks and feels</p>
                        </div>

                        <!-- Theme Selection -->
                        <div class="settings-section">
                            <h4>Theme</h4>
                            <p class="section-desc">Choose your preferred color theme</p>
                            
                            <div class="theme-grid">
                                <label class="theme-card <?php echo ($user['theme'] ?? 'dark') === 'dark' ? 'active' : ''; ?>">
                                    <input type="radio" name="theme" value="dark" <?php echo ($user['theme'] ?? 'dark') === 'dark' ? 'checked' : ''; ?>>
                                    <div class="theme-preview dark-theme-preview">
                                        <div class="preview-sidebar">
                                            <div class="preview-item"></div>
                                            <div class="preview-item"></div>
                                            <div class="preview-item"></div>
                                        </div>
                                        <div class="preview-main">
                                            <div class="preview-header"></div>
                                            <div class="preview-content">
                                                <div class="preview-bubble sent"></div>
                                                <div class="preview-bubble received"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="theme-name">Dark</span>
                                    <span class="theme-check"><i class="fas fa-check"></i></span>
                                </label>
                                
                                <label class="theme-card <?php echo ($user['theme'] ?? '') === 'light' ? 'active' : ''; ?>">
                                    <input type="radio" name="theme" value="light" <?php echo ($user['theme'] ?? '') === 'light' ? 'checked' : ''; ?>>
                                    <div class="theme-preview light-theme-preview">
                                        <div class="preview-sidebar">
                                            <div class="preview-item"></div>
                                            <div class="preview-item"></div>
                                            <div class="preview-item"></div>
                                        </div>
                                        <div class="preview-main">
                                            <div class="preview-header"></div>
                                            <div class="preview-content">
                                                <div class="preview-bubble sent"></div>
                                                <div class="preview-bubble received"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="theme-name">Light</span>
                                    <span class="theme-check"><i class="fas fa-check"></i></span>
                                </label>
                                
                                <label class="theme-card <?php echo ($user['theme'] ?? '') === 'midnight' ? 'active' : ''; ?>">
                                    <input type="radio" name="theme" value="midnight" <?php echo ($user['theme'] ?? '') === 'midnight' ? 'checked' : ''; ?>>
                                    <div class="theme-preview midnight-theme-preview">
                                        <div class="preview-sidebar">
                                            <div class="preview-item"></div>
                                            <div class="preview-item"></div>
                                            <div class="preview-item"></div>
                                        </div>
                                        <div class="preview-main">
                                            <div class="preview-header"></div>
                                            <div class="preview-content">
                                                <div class="preview-bubble sent"></div>
                                                <div class="preview-bubble received"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="theme-name">Midnight</span>
                                    <span class="theme-check"><i class="fas fa-check"></i></span>
                                </label>
                                
                                <label class="theme-card <?php echo ($user['theme'] ?? '') === 'ocean' ? 'active' : ''; ?>">
                                    <input type="radio" name="theme" value="ocean" <?php echo ($user['theme'] ?? '') === 'ocean' ? 'checked' : ''; ?>>
                                    <div class="theme-preview ocean-theme-preview">
                                        <div class="preview-sidebar">
                                            <div class="preview-item"></div>
                                            <div class="preview-item"></div>
                                            <div class="preview-item"></div>
                                        </div>
                                        <div class="preview-main">
                                            <div class="preview-header"></div>
                                            <div class="preview-content">
                                                <div class="preview-bubble sent"></div>
                                                <div class="preview-bubble received"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="theme-name">Ocean</span>
                                    <span class="theme-check"><i class="fas fa-check"></i></span>
                                </label>
                            </div>
                        </div>

                        <!-- Language -->
                        <div class="settings-section">
                            <h4>Language</h4>
                            <p class="section-desc">Select your preferred language</p>
                            
                            <div class="language-selector">
                                <select id="language" name="language" class="form-select">
                                    <option value="en" <?php echo ($user['language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="es" <?php echo ($user['language'] ?? '') === 'es' ? 'selected' : ''; ?>>Español (Spanish)</option>
                                    <option value="fr" <?php echo ($user['language'] ?? '') === 'fr' ? 'selected' : ''; ?>>Français (French)</option>
                                    <option value="de" <?php echo ($user['language'] ?? '') === 'de' ? 'selected' : ''; ?>>Deutsch (German)</option>
                                    <option value="pt" <?php echo ($user['language'] ?? '') === 'pt' ? 'selected' : ''; ?>>Português (Portuguese)</option>
                                    <option value="it" <?php echo ($user['language'] ?? '') === 'it' ? 'selected' : ''; ?>>Italiano (Italian)</option>
                                    <option value="nl" <?php echo ($user['language'] ?? '') === 'nl' ? 'selected' : ''; ?>>Nederlands (Dutch)</option>
                                    <option value="ru" <?php echo ($user['language'] ?? '') === 'ru' ? 'selected' : ''; ?>>Русский (Russian)</option>
                                    <option value="ja" <?php echo ($user['language'] ?? '') === 'ja' ? 'selected' : ''; ?>>日本語 (Japanese)</option>
                                    <option value="ko" <?php echo ($user['language'] ?? '') === 'ko' ? 'selected' : ''; ?>>한국어 (Korean)</option>
                                    <option value="zh" <?php echo ($user['language'] ?? '') === 'zh' ? 'selected' : ''; ?>>中文 (Chinese)</option>
                                    <option value="ar" <?php echo ($user['language'] ?? '') === 'ar' ? 'selected' : ''; ?>>العربية (Arabic)</option>
                                    <option value="hi" <?php echo ($user['language'] ?? '') === 'hi' ? 'selected' : ''; ?>>हिन्दी (Hindi)</option>
                                </select>
                                <i class="fas fa-globe language-icon"></i>
                            </div>
                        </div>

                        <!-- Chat Theme -->
                        <div class="settings-section">
                            <h4>Chat Style</h4>
                            <p class="section-desc">Choose how messages appear</p>
                            
                            <div class="chat-style-options">
                                <label class="style-option">
                                    <input type="radio" name="chat_style" value="bubbles" <?php echo ($user['chat_style'] ?? 'bubbles') === 'bubbles' ? 'checked' : ''; ?>>
                                    <div class="style-preview">
                                        <div class="bubble-preview">
                                            <div class="msg sent">Hello!</div>
                                            <div class="msg received">Hi there!</div>
                                        </div>
                                    </div>
                                    <span>Bubbles</span>
                                </label>
                                <label class="style-option">
                                    <input type="radio" name="chat_style" value="flat">
                                    <div class="style-preview">
                                        <div class="flat-preview">
                                            <div class="msg sent">Hello!</div>
                                            <div class="msg received">Hi there!</div>
                                        </div>
                                    </div>
                                    <span>Flat</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== ACCOUNT TAB ==================== -->
                    <div class="settings-tab" id="account">
                        <div class="tab-header">
                            <h3>Security Settings</h3>
                            <p>Manage your password and account security</p>
                        </div>

                        <!-- Email -->
                        <div class="settings-section">
                            <h4>Email Address</h4>
                            <form id="accountForm">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-envelope"></i>
                                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Email
                                </button>
                            </form>
                        </div>

                        <!-- Change Password -->
                        <div class="settings-section">
                            <h4>Change Password</h4>
                            <form id="passwordForm">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                
                                <div class="form-group">
                                    <label for="currentPassword">Current Password</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-lock"></i>
                                        <input type="password" id="currentPassword" name="current_password" required>
                                        <button type="button" class="toggle-password" onclick="togglePassword('currentPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="newPassword">New Password</label>
                                        <div class="input-with-icon">
                                            <i class="fas fa-key"></i>
                                            <input type="password" id="newPassword" name="new_password" required>
                                            <button type="button" class="toggle-password" onclick="togglePassword('newPassword')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength" id="passwordStrength">
                                            <div class="strength-bar"></div>
                                            <span class="strength-text">Enter a password</span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirmPassword">Confirm Password</label>
                                        <div class="input-with-icon">
                                            <i class="fas fa-key"></i>
                                            <input type="password" id="confirmPassword" name="confirm_password" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Update Password
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- ==================== PRIVACY TAB ==================== -->
                    <div class="settings-tab" id="privacy">
                        <div class="tab-header">
                            <h3>Privacy Settings</h3>
                            <p>Control who can see and interact with you</p>
                        </div>

                        <form id="privacyForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <!-- Who Can Message -->
                            <div class="settings-section">
                                <h4>Who Can Send You Messages</h4>
                                <p class="section-desc">Choose who can start a conversation with you</p>
                                
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="who_can_message" value="everyone" <?php echo ($privacy['who_can_message'] ?? 'everyone') === 'everyone' ? 'checked' : ''; ?>>
                                        <div class="radio-content">
                                            <div class="radio-icon"><i class="fas fa-globe"></i></div>
                                            <div class="radio-text">
                                                <span class="radio-title">Everyone</span>
                                                <span class="radio-desc">Any user can send you messages</span>
                                            </div>
                                        </div>
                                        <div class="radio-check"><i class="fas fa-check"></i></div>
                                    </label>
                                    
                                    <label class="radio-option">
                                        <input type="radio" name="who_can_message" value="friends" <?php echo ($privacy['who_can_message'] ?? '') === 'friends' ? 'checked' : ''; ?>>
                                        <div class="radio-content">
                                            <div class="radio-icon"><i class="fas fa-user-friends"></i></div>
                                            <div class="radio-text">
                                                <span class="radio-title">Friends Only</span>
                                                <span class="radio-desc">Only your friends can message you</span>
                                            </div>
                                        </div>
                                        <div class="radio-check"><i class="fas fa-check"></i></div>
                                    </label>
                                    
                                    <label class="radio-option">
                                        <input type="radio" name="who_can_message" value="nobody" <?php echo ($privacy['who_can_message'] ?? '') === 'nobody' ? 'checked' : ''; ?>>
                                        <div class="radio-content">
                                            <div class="radio-icon"><i class="fas fa-ban"></i></div>
                                            <div class="radio-text">
                                                <span class="radio-title">Nobody</span>
                                                <span class="radio-desc">Disable incoming messages</span>
                                            </div>
                                        </div>
                                        <div class="radio-check"><i class="fas fa-check"></i></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Who Can See Status -->
                            <div class="settings-section">
                                <h4>Who Can See Your Online Status</h4>
                                <p class="section-desc">Control visibility of your online/offline status</p>
                                
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="who_can_see_status" value="everyone" <?php echo ($privacy['who_can_see_status'] ?? 'everyone') === 'everyone' ? 'checked' : ''; ?>>
                                        <div class="radio-content">
                                            <div class="radio-icon"><i class="fas fa-globe"></i></div>
                                            <div class="radio-text">
                                                <span class="radio-title">Everyone</span>
                                                <span class="radio-desc">All users can see when you're online</span>
                                            </div>
                                        </div>
                                        <div class="radio-check"><i class="fas fa-check"></i></div>
                                    </label>
                                    
                                    <label class="radio-option">
                                        <input type="radio" name="who_can_see_status" value="friends" <?php echo ($privacy['who_can_see_status'] ?? '') === 'friends' ? 'checked' : ''; ?>>
                                        <div class="radio-content">
                                            <div class="radio-icon"><i class="fas fa-user-friends"></i></div>
                                            <div class="radio-text">
                                                <span class="radio-title">Friends Only</span>
                                                <span class="radio-desc">Only friends can see your status</span>
                                            </div>
                                        </div>
                                        <div class="radio-check"><i class="fas fa-check"></i></div>
                                    </label>
                                    
                                    <label class="radio-option">
                                        <input type="radio" name="who_can_see_status" value="nobody" <?php echo ($privacy['who_can_see_status'] ?? '') === 'nobody' ? 'checked' : ''; ?>>
                                        <div class="radio-content">
                                            <div class="radio-icon"><i class="fas fa-eye-slash"></i></div>
                                            <div class="radio-text">
                                                <span class="radio-title">Nobody</span>
                                                <span class="radio-desc">Always appear offline</span>
                                            </div>
                                        </div>
                                        <div class="radio-check"><i class="fas fa-check"></i></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Who Can See Profile -->
                            <div class="settings-section">
                                <h4>Who Can See Your Profile</h4>
                                <p class="section-desc">Control who can view your profile information</p>
                                
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="who_can_see_profile" value="everyone" <?php echo ($privacy['who_can_see_profile'] ?? 'everyone') === 'everyone' ? 'checked' : ''; ?>>
                                        <div class="radio-content">
                                            <div class="radio-icon"><i class="fas fa-globe"></i></div>
                                            <div class="radio-text">
                                                <span class="radio-title">Everyone</span>
                                                <span class="radio-desc">Any user can view your profile</span>
                                            </div>
                                        </div>
                                        <div class="radio-check"><i class="fas fa-check"></i></div>
                                    </label>
                                    
                                    <label class="radio-option">
                                        <input type="radio" name="who_can_see_profile" value="friends" <?php echo ($privacy['who_can_see_profile'] ?? '') === 'friends' ? 'checked' : ''; ?>>
                                        <div class="radio-content">
                                            <div class="radio-icon"><i class="fas fa-user-friends"></i></div>
                                            <div class="radio-text">
                                                <span class="radio-title">Friends Only</span>
                                                <span class="radio-desc">Only friends can view your profile</span>
                                            </div>
                                        </div>
                                        <div class="radio-check"><i class="fas fa-check"></i></div>
                                    </label>
                                    
                                    <label class="radio-option">
                                        <input type="radio" name="who_can_see_profile" value="nobody" <?php echo ($privacy['who_can_see_profile'] ?? '') === 'nobody' ? 'checked' : ''; ?>>
                                        <div class="radio-content">
                                            <div class="radio-icon"><i class="fas fa-eye-slash"></i></div>
                                            <div class="radio-text">
                                                <span class="radio-title">Nobody</span>
                                                <span class="radio-desc">Hide your profile from everyone</span>
                                            </div>
                                        </div>
                                        <div class="radio-check"><i class="fas fa-check"></i></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Additional Privacy Options -->
                            <div class="settings-section">
                                <h4>Additional Options</h4>
                                
                                <div class="toggle-group">
                                    <label class="toggle-option">
                                        <div class="toggle-info">
                                            <span class="toggle-title">Show Read Receipts</span>
                                            <span class="toggle-desc">Let others know when you've read their messages</span>
                                        </div>
                                        <div class="toggle-switch">
                                            <input type="checkbox" name="show_read_receipts" id="showReadReceipts" <?php echo ($privacy['show_read_receipts'] ?? 1) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </div>
                                    </label>
                                    
                                    <label class="toggle-option">
                                        <div class="toggle-info">
                                            <span class="toggle-title">Show Typing Indicator</span>
                                            <span class="toggle-desc">Let others see when you're typing</span>
                                        </div>
                                        <div class="toggle-switch">
                                            <input type="checkbox" name="show_typing" id="showTyping" <?php echo ($privacy['show_typing'] ?? 1) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </div>
                                    </label>
                                    
                                    <label class="toggle-option">
                                        <div class="toggle-info">
                                            <span class="toggle-title">Allow Friend Requests</span>
                                            <span class="toggle-desc">Allow other users to send you friend requests</span>
                                        </div>
                                        <div class="toggle-switch">
                                            <input type="checkbox" name="allow_friend_requests" id="allowFriendRequests" <?php echo ($privacy['allow_friend_requests'] ?? 1) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Privacy Settings
                            </button>
                        </form>
                    </div>

                    <!-- ==================== NOTIFICATIONS TAB ==================== -->
                    <div class="settings-tab" id="notifications">
                        <div class="tab-header">
                            <h3>Notification Settings</h3>
                            <p>Choose what notifications you receive</p>
                        </div>

                        <form id="notificationsForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="settings-section">
                                <h4>Push Notifications</h4>
                                
                                <div class="toggle-group">
                                    <label class="toggle-option">
                                        <div class="toggle-info">
                                            <span class="toggle-title">Message Notifications</span>
                                            <span class="toggle-desc">Get notified when you receive a new message</span>
                                        </div>
                                        <div class="toggle-switch">
                                            <input type="checkbox" name="notify_messages" <?php echo ($notifications['notify_messages'] ?? 1) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </div>
                                    </label>
                                    
                                    <label class="toggle-option">
                                        <div class="toggle-info">
                                            <span class="toggle-title">Friend Request Notifications</span>
                                            <span class="toggle-desc">Get notified about friend requests</span>
                                        </div>
                                        <div class="toggle-switch">
                                            <input type="checkbox" name="notify_friend_requests" <?php echo ($notifications['notify_friend_requests'] ?? 1) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </div>
                                    </label>
                                    
                                    <label class="toggle-option">
                                        <div class="toggle-info">
                                            <span class="toggle-title">Group Notifications</span>
                                            <span class="toggle-desc">Get notified about group activity</span>
                                        </div>
                                        <div class="toggle-switch">
                                            <input type="checkbox" name="notify_groups" <?php echo ($notifications['notify_groups'] ?? 1) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </div>
                                    </label>
                                    
                                    <label class="toggle-option">
                                        <div class="toggle-info">
                                            <span class="toggle-title">Sound Notifications</span>
                                            <span class="toggle-desc">Play a sound for new notifications</span>
                                        </div>
                                        <div class="toggle-switch">
                                            <input type="checkbox" name="notify_sound" <?php echo ($notifications['notify_sound'] ?? 1) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="settings-section">
                                <h4>Email Notifications</h4>
                                
                                <div class="toggle-group">
                                    <label class="toggle-option">
                                        <div class="toggle-info">
                                            <span class="toggle-title">Email on New Message</span>
                                            <span class="toggle-desc">Receive email when you get a new message</span>
                                        </div>
                                        <div class="toggle-switch">
                                            <input type="checkbox" name="email_messages" <?php echo ($notifications['email_messages'] ?? 0) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </div>
                                    </label>
                                    
                                    <label class="toggle-option">
                                        <div class="toggle-info">
                                            <span class="toggle-title">Security Alerts</span>
                                            <span class="toggle-desc">Get email for security-related events</span>
                                        </div>
                                        <div class="toggle-switch">
                                            <input type="checkbox" name="email_security" <?php echo ($notifications['email_security'] ?? 1) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Notification Settings
                            </button>
                        </form>
                    </div>

                    <!-- ==================== BLOCKED USERS TAB ==================== -->
                    <div class="settings-tab" id="blocked">
                        <div class="tab-header">
                            <h3>Blocked Users</h3>
                            <p>Manage users you've blocked</p>
                        </div>

                        <div class="settings-section">
                            <?php if (empty($blocked_users)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-ban"></i>
                                    <h4>No Blocked Users</h4>
                                    <p>When you block someone, they won't be able to message you or see your profile.</p>
                                </div>
                            <?php else: ?>
                                <p class="section-desc">You have <?php echo count($blocked_users); ?> blocked user(s)</p>
                                
                                <div class="blocked-users-list" id="blockedList">
                                    <?php foreach ($blocked_users as $blocked): ?>
                                        <div class="blocked-user-item" data-user-id="<?php echo $blocked['id']; ?>">
                                            <div class="user-info">
                                                <div class="avatar">
                                                    <?php if ($blocked['avatar']): ?>
                                                        <img src="<?php echo htmlspecialchars($blocked['avatar']); ?>" alt="">
                                                    <?php else: ?>
                                                        <div class="avatar-initials">
                                                            <?php echo strtoupper(substr($blocked['username'], 0, 2)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="user-details">
                                                    <span class="user-name"><?php echo htmlspecialchars($blocked['username']); ?></span>
                                                    <span class="blocked-date">Blocked <?php echo date('M d, Y', strtotime($blocked['blocked_at'])); ?></span>
                                                </div>
                                            </div>
                                            <button class="btn btn-outline unblock-btn" data-user-id="<?php echo $blocked['id']; ?>">
                                                <i class="fas fa-unlock"></i> Unblock
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ==================== DATA & EXPORT TAB ==================== -->
                    <div class="settings-tab" id="data">
                        <div class="tab-header">
                            <h3>Data & Export</h3>
                            <p>Download or manage your data</p>
                        </div>

                        <div class="settings-section">
                            <h4>Export Your Data</h4>
                            <p class="section-desc">Download a copy of all your data including messages, profile, and media.</p>
                            
                            <div class="export-options">
                                <div class="export-card">
                                    <div class="export-icon">
                                        <i class="fas fa-file-archive"></i>
                                    </div>
                                    <div class="export-info">
                                        <h5>Full Export</h5>
                                        <p>Download all your data as a ZIP file</p>
                                    </div>
                                    <button class="btn btn-primary" id="exportAllBtn">
                                        <i class="fas fa-download"></i> Export All
                                    </button>
                                </div>
                                
                                <div class="export-card">
                                    <div class="export-icon messages">
                                        <i class="fas fa-comments"></i>
                                    </div>
                                    <div class="export-info">
                                        <h5>Messages Only</h5>
                                        <p>Download your messages as JSON</p>
                                    </div>
                                    <button class="btn btn-outline" id="exportMessagesBtn">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                </div>
                                
                                <div class="export-card">
                                    <div class="export-icon media">
                                        <i class="fas fa-photo-film"></i>
                                    </div>
                                    <div class="export-info">
                                        <h5>Media Files</h5>
                                        <p>Download all uploaded media files</p>
                                    </div>
                                    <button class="btn btn-outline" id="exportMediaBtn">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                </div>
                            </div>
                            
                            <div class="export-status" id="exportStatus" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                                <span>Preparing your export...</span>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== DANGER ZONE TAB ==================== -->
                    <div class="settings-tab" id="danger">
                        <div class="tab-header danger">
                            <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                            <p>Irreversible actions - proceed with caution</p>
                        </div>

                        <div class="danger-section">
                            <!-- Deactivate Account -->
                            <div class="danger-card">
                                <div class="danger-icon warning">
                                    <i class="fas fa-pause-circle"></i>
                                </div>
                                <div class="danger-info">
                                    <h4>Deactivate Account</h4>
                                    <p>Temporarily disable your account. You can reactivate within 30 days.</p>
                                </div>
                                <button class="btn btn-warning" onclick="showDeactivateModal()">
                                    <i class="fas fa-pause"></i> Deactivate
                                </button>
                            </div>

                            <!-- Delete Account -->
                            <div class="danger-card critical">
                                <div class="danger-icon danger">
                                    <i class="fas fa-trash-alt"></i>
                                </div>
                                <div class="danger-info">
                                    <h4>Delete Account</h4>
                                    <p>Permanently delete your account and all data. This cannot be undone.</p>
                                </div>
                                <button class="btn btn-danger" onclick="showDeleteModal()">
                                    <i class="fas fa-trash"></i> Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </main>
    </div>

    <!-- Deactivate Modal -->
    <div class="modal" id="deactivateModal">
        <div class="modal-overlay" onclick="closeModal('deactivateModal')"></div>
        <div class="modal-content">
            <div class="modal-header warning">
                <h3><i class="fas fa-pause-circle"></i> Deactivate Account</h3>
                <button class="modal-close" onclick="closeModal('deactivateModal')">×</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to deactivate your account?</p>
                <ul class="warning-list">
                    <li>Your profile will be hidden</li>
                    <li>You won't be able to log in</li>
                    <li>Your messages will remain visible</li>
                    <li>Account can be reactivated within 30 days</li>
                </ul>
                <div class="form-group">
                    <label>Type your username to confirm:</label>
                    <input type="text" id="deactivateConfirm" placeholder="<?php echo htmlspecialchars($user['username']); ?>" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('deactivateModal')">Cancel</button>
                <button class="btn btn-warning" id="deactivateBtn" onclick="deactivateAccount()" disabled>
                    <i class="fas fa-pause"></i> Deactivate
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-overlay" onclick="closeModal('deleteModal')"></div>
        <div class="modal-content">
            <div class="modal-header danger">
                <h3><i class="fas fa-trash-alt"></i> Delete Account</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">×</button>
            </div>
            <div class="modal-body">
                <p class="text-danger"><strong>This action cannot be undone!</strong></p>
                <p>Deleting your account will permanently remove:</p>
                <ul class="warning-list">
                    <li>Your profile and all personal data</li>
                    <li>All your messages</li>
                    <li>Your friend connections</li>
                    <li>Your group memberships</li>
                    <li>All uploaded media</li>
                </ul>
                <div class="form-group">
                    <label>Type "DELETE" to confirm:</label>
                    <input type="text" id="deleteConfirm" placeholder="DELETE" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                <button class="btn btn-danger" id="deleteBtn" onclick="deleteAccount()" disabled>
                    <i class="fas fa-trash"></i> Delete Permanently
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/settings-complete.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <?php echo render_sidebar_scripts(); ?>
</body>
</html>
