<?php
/**
 * =====================================================
 * Admin Profile & Settings
 * ChatApp - Admin Profile Management (User-style tabs)
 */

define('APP_RUNNING', true);

require_once dirname(__DIR__) . '/config/database.php';
require_once 'config.php';
require_once 'auth.php';
require_once 'helpers.php';

admin_session_init();

if (!admin_verify_session()) {
    header('Location: index.php');
    exit;
}

$conn = db_connect();
$admin_id = admin_get_id();
$success = '';
$error = '';
$active_tab = $_GET['tab'] ?? 'profile';

// Get current admin data from DB
$stmt = mysqli_prepare($conn, "SELECT * FROM admin_users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$admin) {
    header('Location: index.php');
    exit;
}

// Parse admin settings
$admin_settings = json_decode($admin['settings'] ?? '{}', true) ?: [];
$privacy = $admin_settings['privacy'] ?? [];
$notifications_settings = $admin_settings['notifications'] ?? [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!admin_verify_csrf($csrf_token)) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $bio = trim($_POST['bio'] ?? '');

            if (empty($full_name) || empty($email)) {
                $error = 'Full name and email are required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address';
            } else {
                $check = mysqli_prepare($conn, "SELECT id FROM admin_users WHERE email = ? AND id != ?");
                mysqli_stmt_bind_param($check, 'si', $email, $admin_id);
                mysqli_stmt_execute($check);
                $check_result = mysqli_stmt_get_result($check);
                if (mysqli_num_rows($check_result) > 0) {
                    $error = 'Email is already in use';
                } else {
                    $stmt = mysqli_prepare($conn, "UPDATE admin_users SET full_name = ?, email = ?, bio = ?, updated_at = NOW() WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, 'sssi', $full_name, $email, $bio, $admin_id);
                    if (mysqli_stmt_execute($stmt)) {
                        $success = 'Profile updated successfully';
                        $_SESSION['admin_data']['full_name'] = $full_name;
                        $_SESSION['admin_data']['email'] = $email;
                        $admin['full_name'] = $full_name;
                        $admin['email'] = $email;
                        $admin['bio'] = $bio;
                    } else {
                        $error = 'Failed to update profile';
                    }
                    mysqli_stmt_close($stmt);
                }
                mysqli_stmt_close($check);
            }
        } elseif ($action === 'change_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = 'All password fields are required';
            } elseif (!password_verify($current_password, $admin['password'])) {
                $error = 'Current password is incorrect';
            } elseif (strlen($new_password) < 6) {
                $error = 'New password must be at least 6 characters';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New passwords do not match';
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE admin_users SET password = ?, updated_at = NOW() WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'si', $hashed, $admin_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Password changed successfully';
                } else {
                    $error = 'Failed to change password';
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($action === 'upload_avatar') {
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Please select an image to upload';
            } else {
                $file = $_FILES['avatar'];
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $max_size = ADMIN_MAX_AVATAR_SIZE;

                if (!in_array($file['type'], $allowed)) {
                    $error = 'Invalid file type. Allowed: JPG, PNG, GIF, WebP';
                } elseif ($file['size'] > $max_size) {
                    $error = 'File too large. Max size: 2MB';
                } else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
                    $upload_dir = dirname(__DIR__) . '/storage/uploads/avatars';

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $destination = $upload_dir . '/' . $filename;
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        if (!empty($admin['avatar']) && strpos($admin['avatar'], 'admin_') === 0) {
                            $old_file = $upload_dir . '/' . $admin['avatar'];
                            if (file_exists($old_file)) {
                                unlink($old_file);
                            }
                        }

                        $stmt = mysqli_prepare($conn, "UPDATE admin_users SET avatar = ?, updated_at = NOW() WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, 'si', $filename, $admin_id);
                        if (mysqli_stmt_execute($stmt)) {
                            $success = 'Avatar uploaded successfully';
                            $_SESSION['admin_data']['avatar'] = $filename;
                            $admin['avatar'] = $filename;
                        } else {
                            $error = 'Failed to save avatar';
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $error = 'Failed to upload file';
                    }
                }
            }
        } elseif ($action === 'update_appearance') {
            $theme = $_POST['theme'] ?? 'dark';
            $language = $_POST['language'] ?? 'en';

            $admin_settings['theme'] = $theme;
            $admin_settings['language'] = $language;
            $settings_json = json_encode($admin_settings);

            $stmt = mysqli_prepare($conn, "UPDATE admin_users SET settings = ?, updated_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $settings_json, $admin_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Appearance updated successfully';
                $_SESSION['admin_data']['theme'] = $theme;
            } else {
                $error = 'Failed to update appearance';
            }
            mysqli_stmt_close($stmt);
        } elseif ($action === 'update_privacy') {
            $privacy['who_can_see_profile'] = $_POST['who_can_see_profile'] ?? 'everyone';
            $privacy['show_online_status'] = isset($_POST['show_online_status']) ? 1 : 0;
            $privacy['show_last_seen'] = isset($_POST['show_last_seen']) ? 1 : 0;
            $admin_settings['privacy'] = $privacy;
            $settings_json = json_encode($admin_settings);

            $stmt = mysqli_prepare($conn, "UPDATE admin_users SET settings = ?, updated_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $settings_json, $admin_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Privacy settings updated';
            } else {
                $error = 'Failed to update privacy settings';
            }
            mysqli_stmt_close($stmt);
        } elseif ($action === 'update_notifications') {
            $notifications_settings['notify_login'] = isset($_POST['notify_login']) ? 1 : 0;
            $notifications_settings['notify_reports'] = isset($_POST['notify_reports']) ? 1 : 0;
            $notifications_settings['notify_user_activity'] = isset($_POST['notify_user_activity']) ? 1 : 0;
            $notifications_settings['email_notifications'] = isset($_POST['email_notifications']) ? 1 : 0;
            $admin_settings['notifications'] = $notifications_settings;
            $settings_json = json_encode($admin_settings);

            $stmt = mysqli_prepare($conn, "UPDATE admin_users SET settings = ?, updated_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $settings_json, $admin_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Notification settings updated';
            } else {
                $error = 'Failed to update notification settings';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Re-fetch admin data after updates
$stmt = mysqli_prepare($conn, "SELECT * FROM admin_users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
$admin_settings = json_decode($admin['settings'] ?? '{}', true) ?: [];
$privacy = $admin_settings['privacy'] ?? [];
$notifications_settings = $admin_settings['notifications'] ?? [];

$page_title = 'Profile';
include 'includes/header.php';
?>

<style>
.settings-container { max-width: 1200px; margin: 0 auto; }
.settings-layout { display: grid; grid-template-columns: 260px 1fr; gap: 30px; }
.settings-sidebar { position: sticky; top: 80px; height: fit-content; }
.settings-nav { background: var(--bg-secondary); border-radius: 16px; padding: 12px; border: 1px solid var(--border); }
.settings-nav .nav-item { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 10px; color: var(--text-secondary); text-decoration: none; transition: all 0.2s; margin-bottom: 4px; }
.settings-nav .nav-item:hover { background: var(--bg-tertiary); color: var(--text-primary); }
.settings-nav .nav-item.active { background: var(--accent); color: white; }
.settings-nav .nav-item i { width: 20px; text-align: center; font-size: 16px; }
.settings-nav .nav-divider { border: none; border-top: 1px solid var(--border); margin: 10px 0; }
.settings-nav .nav-item.danger { color: var(--danger); }
.settings-nav .nav-item.danger:hover { background: var(--danger-light); }
.settings-content { background: var(--bg-secondary); border-radius: 16px; padding: 30px; border: 1px solid var(--border); min-height: 600px; }
.settings-tab { display: none; }
.settings-tab.active { display: block; animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.tab-header { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--border); }
.tab-header h3 { margin: 0 0 8px 0; color: var(--text-primary); font-weight: 600; font-size: 22px; }
.tab-header p { margin: 0; color: var(--text-muted); }
.settings-section { margin-bottom: 35px; }
.settings-section:last-child { margin-bottom: 0; }
.settings-section h4 { margin: 0 0 8px 0; color: var(--text-primary); font-weight: 600; font-size: 16px; }
.section-desc { margin: 0 0 20px 0; color: var(--text-muted); font-size: 14px; }

/* Avatar Upload */
.avatar-upload-section { display: flex; align-items: center; gap: 24px; }
.current-avatar { width: 100px; height: 100px; border-radius: 50%; overflow: hidden; border: 3px solid var(--border); flex-shrink: 0; background: var(--accent); display: flex; align-items: center; justify-content: center; }
.current-avatar img { width: 100%; height: 100%; object-fit: cover; }
.current-avatar .avatar-initials { font-size: 2.2rem; font-weight: 700; color: white; }
.avatar-actions { display: flex; flex-direction: column; gap: 10px; }
.help-text { margin: 0; font-size: 13px; color: var(--text-muted); }

/* Form Styles */
.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-size: 14px; font-weight: 500; color: var(--text-secondary); margin-bottom: 8px; }
.form-group .form-control, .form-group .form-select { width: 100%; padding: 12px 14px; background: var(--bg-tertiary); border: 1px solid var(--border); border-radius: 10px; color: var(--text-primary); font-size: 15px; transition: all 0.2s; }
.form-group .form-control:focus, .form-group .form-select:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2); }
.form-group .form-text { font-size: 13px; color: var(--text-muted); margin-top: 6px; }

/* Toggle Group */
.toggle-group { display: flex; flex-direction: column; gap: 12px; }
.toggle-option { display: flex; align-items: center; justify-content: space-between; padding: 16px; background: var(--bg-tertiary); border-radius: 12px; border: 1px solid var(--border); }
.toggle-info { flex: 1; }
.toggle-title { display: block; font-weight: 500; color: var(--text-primary); margin-bottom: 4px; }
.toggle-desc { display: block; font-size: 13px; color: var(--text-muted); }
.toggle-switch { position: relative; width: 48px; height: 26px; flex-shrink: 0; }
.toggle-switch input { display: none; }
.toggle-slider { position: absolute; inset: 0; background: var(--bg-hover); border-radius: 26px; cursor: pointer; transition: 0.3s; }
.toggle-slider::before { content: ''; position: absolute; width: 20px; height: 20px; border-radius: 50%; background: white; top: 3px; left: 3px; transition: 0.3s; }
.toggle-switch input:checked + .toggle-slider { background: var(--accent); }
.toggle-switch input:checked + .toggle-slider::before { transform: translateX(22px); }

/* Radio Group */
.radio-group { display: flex; flex-direction: column; gap: 12px; }
.radio-option { display: flex; align-items: center; gap: 16px; padding: 16px; background: var(--bg-tertiary); border-radius: 12px; border: 2px solid var(--border); cursor: pointer; transition: all 0.2s; }
.radio-option:hover { border-color: var(--accent); }
.radio-option input { display: none; }
.radio-option input:checked + .radio-content { border-color: var(--accent); }
.radio-option:has(input:checked) { border-color: var(--accent); background: var(--accent-light); }
.radio-content { display: flex; align-items: center; gap: 12px; flex: 1; }
.radio-icon { width: 40px; height: 40px; border-radius: 10px; background: var(--bg-hover); display: flex; align-items: center; justify-content: center; color: var(--text-secondary); font-size: 16px; }
.radio-option:has(input:checked) .radio-icon { background: var(--accent); color: white; }
.radio-text { flex: 1; }
.radio-title { display: block; font-weight: 500; color: var(--text-primary); margin-bottom: 2px; }
.radio-desc { display: block; font-size: 13px; color: var(--text-muted); }
.radio-check { width: 22px; height: 22px; border-radius: 50%; border: 2px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 12px; color: transparent; transition: all 0.2s; }
.radio-option:has(input:checked) .radio-check { background: var(--accent); border-color: var(--accent); color: white; }

/* Theme Grid */
.theme-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.theme-card { position: relative; cursor: pointer; }
.theme-card input { display: none; }
.theme-preview { aspect-ratio: 16/10; border-radius: 12px; overflow: hidden; border: 3px solid var(--border); transition: all 0.3s; display: flex; }
.theme-card:hover .theme-preview { border-color: var(--accent); transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2); }
.theme-card.active .theme-preview { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3); }
.theme-check { position: absolute; top: 8px; right: 8px; width: 24px; height: 24px; border-radius: 50%; background: var(--accent); color: white; display: none; align-items: center; justify-content: center; font-size: 12px; }
.theme-card.active .theme-check { display: flex; }
.theme-name { display: block; text-align: center; margin-top: 10px; color: var(--text-primary); font-weight: 500; font-size: 14px; }
.preview-sidebar { width: 30%; padding: 8px; display: flex; flex-direction: column; gap: 6px; }
.preview-item { height: 8px; border-radius: 4px; opacity: 0.7; }
.preview-main { flex: 1; display: flex; flex-direction: column; }
.preview-header { height: 20%; padding: 6px 8px; }
.preview-content { flex: 1; padding: 8px; display: flex; flex-direction: column; justify-content: flex-end; gap: 6px; }
.preview-bubble { padding: 6px 10px; border-radius: 8px; max-width: 70%; font-size: 8px; }
.preview-bubble.sent { margin-left: auto; }
.dark-theme-preview { background: #1a1d21; }
.dark-theme-preview .preview-sidebar { background: #222529; }
.dark-theme-preview .preview-item { background: #2d3135; }
.dark-theme-preview .preview-header { background: #222529; }
.dark-theme-preview .preview-content { background: #1a1d21; }
.dark-theme-preview .preview-bubble.sent { background: #6366f1; color: white; }
.dark-theme-preview .preview-bubble.received { background: #2d3135; color: #e2e8f0; }
.light-theme-preview { background: #ffffff; }
.light-theme-preview .preview-sidebar { background: #f1f5f9; }
.light-theme-preview .preview-item { background: #e2e8f0; }
.light-theme-preview .preview-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.light-theme-preview .preview-content { background: #ffffff; }
.light-theme-preview .preview-bubble.sent { background: #6366f1; color: white; }
.light-theme-preview .preview-bubble.received { background: #f1f5f9; color: #1e293b; }
.midnight-theme-preview { background: #0f0f23; }
.midnight-theme-preview .preview-sidebar { background: #1a1a3e; }
.midnight-theme-preview .preview-item { background: #2a2a5e; }
.midnight-theme-preview .preview-header { background: #1a1a3e; }
.midnight-theme-preview .preview-content { background: #0f0f23; }
.midnight-theme-preview .preview-bubble.sent { background: #6366f1; color: white; }
.midnight-theme-preview .preview-bubble.received { background: #2a2a5e; color: #e2e8f0; }

/* Danger Zone */
.danger-section { display: flex; flex-direction: column; gap: 16px; }
.danger-card { display: flex; align-items: center; gap: 16px; padding: 20px; background: var(--bg-tertiary); border-radius: 12px; border: 1px solid var(--border); }
.danger-card.critical { border-color: rgba(239, 68, 68, 0.3); background: var(--danger-light); }
.danger-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.danger-icon.warning { background: var(--warning-light); color: var(--warning); }
.danger-icon.danger { background: var(--danger-light); color: var(--danger); }
.danger-info { flex: 1; }
.danger-info h4 { margin: 0 0 4px 0; font-size: 16px; color: var(--text-primary); }
.danger-info p { margin: 0; font-size: 14px; color: var(--text-muted); }

/* Buttons */
.btn { padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
.btn-primary { background: var(--accent); color: white; }
.btn-primary:hover { background: var(--accent-hover); transform: translateY(-1px); }
.btn-secondary { background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--border); }
.btn-secondary:hover { background: var(--bg-hover); }
.btn-danger { background: var(--danger); color: white; }
.btn-danger:hover { background: #dc2626; }
.btn-warning { background: var(--warning); color: white; }
.btn-warning:hover { background: #d97706; }
.btn-outline { background: transparent; color: var(--text-secondary); border: 1px solid var(--border); }
.btn-outline:hover { background: var(--bg-tertiary); border-color: var(--accent); color: var(--accent); }

/* Account Info Table */
.info-table { width: 100%; }
.info-table tr td { padding: 12px 0; border-bottom: 1px solid var(--border); }
.info-table tr:last-child td { border-bottom: none; }
.info-table tr td:first-child { color: var(--text-muted); width: 40%; }
.info-table tr td:last-child { color: var(--text-primary); font-weight: 500; }

@media (max-width: 768px) {
    .settings-layout { grid-template-columns: 1fr; }
    .settings-sidebar { position: static; }
    .settings-nav { display: flex; overflow-x: auto; padding: 8px; gap: 4px; }
    .settings-nav .nav-item { white-space: nowrap; padding: 10px 14px; font-size: 13px; }
    .settings-nav .nav-item span { display: none; }
    .settings-nav .nav-item i { margin: 0; }
    .settings-content { padding: 20px; }
    .theme-grid { grid-template-columns: repeat(2, 1fr); }
    .avatar-upload-section { flex-direction: column; text-align: center; }
    .danger-card { flex-direction: column; text-align: center; }
    .radio-option { flex-direction: column; text-align: center; }
    .radio-content { flex-direction: column; }
}
</style>

<div class="settings-container">
    <div class="settings-layout">
        <aside class="settings-sidebar">
            <nav class="settings-nav">
                <a href="#profile" class="nav-item <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" data-tab="profile">
                    <i class="fas fa-user-circle"></i><span>Profile</span>
                </a>
                <a href="#appearance" class="nav-item <?php echo $active_tab === 'appearance' ? 'active' : ''; ?>" data-tab="appearance">
                    <i class="fas fa-palette"></i><span>Appearance</span>
                </a>
                <a href="#security" class="nav-item <?php echo $active_tab === 'security' ? 'active' : ''; ?>" data-tab="security">
                    <i class="fas fa-shield-alt"></i><span>Security</span>
                </a>
                <a href="#privacy" class="nav-item <?php echo $active_tab === 'privacy' ? 'active' : ''; ?>" data-tab="privacy">
                    <i class="fas fa-user-lock"></i><span>Privacy</span>
                </a>
                <a href="#notifications" class="nav-item <?php echo $active_tab === 'notifications' ? 'active' : ''; ?>" data-tab="notifications">
                    <i class="fas fa-bell"></i><span>Notifications</span>
                </a>
                <hr class="nav-divider">
                <a href="#account" class="nav-item <?php echo $active_tab === 'account' ? 'active' : ''; ?>" data-tab="account">
                    <i class="fas fa-info-circle"></i><span>Account Info</span>
                </a>
            </nav>
        </aside>

        <div class="settings-content">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="margin-bottom: 20px; padding: 14px 18px; background: var(--success-light); border: 1px solid rgba(34,197,94,0.3); border-radius: 10px; color: #86efac;">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px; padding: 14px 18px; background: var(--danger-light); border: 1px solid rgba(239,68,68,0.3); border-radius: 10px; color: #fca5a5;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- PROFILE TAB -->
            <div class="settings-tab <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" id="profile">
                <div class="tab-header">
                    <h3>Profile Settings</h3>
                    <p>Manage your public profile information</p>
                </div>

                <div class="settings-section">
                    <h4>Profile Photo</h4>
                    <div class="avatar-upload-section">
                        <div class="current-avatar">
                            <?php if (!empty($admin['avatar'])): ?>
                                <img src="../storage/uploads/avatars/<?php echo htmlspecialchars($admin['avatar']); ?>" alt="Avatar">
                            <?php else: ?>
                                <span class="avatar-initials"><?php echo strtoupper(substr($admin['full_name'] ?? $admin['username'], 0, 2)); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="avatar-actions">
                            <form method="POST" enctype="multipart/form-data" id="avatarForm">
                                <?php echo admin_csrf_field(); ?>
                                <input type="hidden" name="action" value="upload_avatar">
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('avatarInput').click()">
                                    <i class="fas fa-camera"></i> Change Photo
                                </button>
                                <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display:none" onchange="document.getElementById('avatarForm').submit()">
                                <p class="help-text">JPG, PNG or GIF. Max size 2MB.</p>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="settings-section">
                    <h4>Personal Information</h4>
                    <form method="POST">
                        <?php echo admin_csrf_field(); ?>
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label for="profile_username">Username</label>
                            <input type="text" class="form-control" id="profile_username" value="<?php echo htmlspecialchars($admin['username']); ?>" disabled autocomplete="username">
                            <span class="form-text">Username cannot be changed</span>
                        </div>
                        <div class="form-group">
                            <label for="profile_fullname">Full Name</label>
                            <input type="text" class="form-control" id="profile_fullname" name="full_name" value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>" required autocomplete="name">
                        </div>
                        <div class="form-group">
                            <label for="profile_email">Email</label>
                            <input type="email" class="form-control" id="profile_email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required autocomplete="email">
                        </div>
                        <div class="form-group">
                            <label for="profile_bio">Bio</label>
                            <textarea class="form-control" id="profile_bio" name="bio" rows="3" maxlength="150" placeholder="Write something about yourself..."><?php echo htmlspecialchars($admin['bio'] ?? ''); ?></textarea>
                            <span class="form-text"><span id="bioCount"><?php echo strlen($admin['bio'] ?? ''); ?></span>/150</span>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Profile</button>
                    </form>
                </div>
            </div>

            <!-- APPEARANCE TAB -->
            <div class="settings-tab <?php echo $active_tab === 'appearance' ? 'active' : ''; ?>" id="appearance">
                <div class="tab-header">
                    <h3>Appearance</h3>
                    <p>Customize how the admin panel looks</p>
                </div>

                <form method="POST">
                    <?php echo admin_csrf_field(); ?>
                    <input type="hidden" name="action" value="update_appearance">

                    <div class="settings-section">
                        <h4>Theme</h4>
                        <p class="section-desc">Choose your preferred color theme</p>
                        <div class="theme-grid">
                            <label class="theme-card <?php echo ($admin_settings['theme'] ?? 'dark') === 'dark' ? 'active' : ''; ?>">
                                <input type="radio" name="theme" value="dark" <?php echo ($admin_settings['theme'] ?? 'dark') === 'dark' ? 'checked' : ''; ?>>
                                <div class="theme-preview dark-theme-preview">
                                    <div class="preview-sidebar"><div class="preview-item"></div><div class="preview-item"></div></div>
                                    <div class="preview-main"><div class="preview-header"></div><div class="preview-content"><div class="preview-bubble sent"></div><div class="preview-bubble received"></div></div></div>
                                </div>
                                <span class="theme-name">Dark</span>
                                <span class="theme-check"><i class="fas fa-check"></i></span>
                            </label>
                            <label class="theme-card <?php echo ($admin_settings['theme'] ?? '') === 'light' ? 'active' : ''; ?>">
                                <input type="radio" name="theme" value="light" <?php echo ($admin_settings['theme'] ?? '') === 'light' ? 'checked' : ''; ?>>
                                <div class="theme-preview light-theme-preview">
                                    <div class="preview-sidebar"><div class="preview-item"></div><div class="preview-item"></div></div>
                                    <div class="preview-main"><div class="preview-header"></div><div class="preview-content"><div class="preview-bubble sent"></div><div class="preview-bubble received"></div></div></div>
                                </div>
                                <span class="theme-name">Light</span>
                                <span class="theme-check"><i class="fas fa-check"></i></span>
                            </label>
                            <label class="theme-card <?php echo ($admin_settings['theme'] ?? '') === 'midnight' ? 'active' : ''; ?>">
                                <input type="radio" name="theme" value="midnight" <?php echo ($admin_settings['theme'] ?? '') === 'midnight' ? 'checked' : ''; ?>>
                                <div class="theme-preview midnight-theme-preview">
                                    <div class="preview-sidebar"><div class="preview-item"></div><div class="preview-item"></div></div>
                                    <div class="preview-main"><div class="preview-header"></div><div class="preview-content"><div class="preview-bubble sent"></div><div class="preview-bubble received"></div></div></div>
                                </div>
                                <span class="theme-name">Midnight</span>
                                <span class="theme-check"><i class="fas fa-check"></i></span>
                            </label>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h4>Language</h4>
                        <p class="section-desc">Select your preferred language</p>
                        <div class="form-group">
                            <label for="admin_language" class="sr-only">Language</label>
                            <select class="form-select" id="admin_language" name="language">
                                <option value="en" <?php echo ($admin_settings['language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                                <option value="es" <?php echo ($admin_settings['language'] ?? '') === 'es' ? 'selected' : ''; ?>>Español</option>
                                <option value="fr" <?php echo ($admin_settings['language'] ?? '') === 'fr' ? 'selected' : ''; ?>>Français</option>
                                <option value="de" <?php echo ($admin_settings['language'] ?? '') === 'de' ? 'selected' : ''; ?>>Deutsch</option>
                                <option value="hi" <?php echo ($admin_settings['language'] ?? '') === 'hi' ? 'selected' : ''; ?>>हिन्दी</option>
                                <option value="ar" <?php echo ($admin_settings['language'] ?? '') === 'ar' ? 'selected' : ''; ?>>العربية</option>
                                <option value="ja" <?php echo ($admin_settings['language'] ?? '') === 'ja' ? 'selected' : ''; ?>>日本語</option>
                                <option value="ko" <?php echo ($admin_settings['language'] ?? '') === 'ko' ? 'selected' : ''; ?>>한국어</option>
                                <option value="zh" <?php echo ($admin_settings['language'] ?? '') === 'zh' ? 'selected' : ''; ?>>中文</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Appearance</button>
                </form>
            </div>

            <!-- SECURITY TAB -->
            <div class="settings-tab <?php echo $active_tab === 'security' ? 'active' : ''; ?>" id="security">
                <div class="tab-header">
                    <h3>Security Settings</h3>
                    <p>Manage your password and account security</p>
                </div>

                <div class="settings-section">
                    <h4>Change Password</h4>
                    <form method="POST">
                        <?php echo admin_csrf_field(); ?>
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6" autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6" autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Update Password</button>
                    </form>
                </div>
            </div>

            <!-- PRIVACY TAB -->
            <div class="settings-tab <?php echo $active_tab === 'privacy' ? 'active' : ''; ?>" id="privacy">
                <div class="tab-header">
                    <h3>Privacy Settings</h3>
                    <p>Control your visibility and privacy</p>
                </div>

                <form method="POST">
                    <?php echo admin_csrf_field(); ?>
                    <input type="hidden" name="action" value="update_privacy">

                    <div class="settings-section">
                        <h4>Who Can See Your Profile</h4>
                        <p class="section-desc">Control who can view your admin profile</p>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="who_can_see_profile" value="everyone" <?php echo ($privacy['who_can_see_profile'] ?? 'everyone') === 'everyone' ? 'checked' : ''; ?>>
                                <div class="radio-content">
                                    <div class="radio-icon"><i class="fas fa-globe"></i></div>
                                    <div class="radio-text">
                                        <span class="radio-title">Everyone</span>
                                        <span class="radio-desc">All users can see your profile</span>
                                    </div>
                                </div>
                                <div class="radio-check"><i class="fas fa-check"></i></div>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="who_can_see_profile" value="admins" <?php echo ($privacy['who_can_see_profile'] ?? '') === 'admins' ? 'checked' : ''; ?>>
                                <div class="radio-content">
                                    <div class="radio-icon"><i class="fas fa-user-shield"></i></div>
                                    <div class="radio-text">
                                        <span class="radio-title">Admins Only</span>
                                        <span class="radio-desc">Only other admins can see your profile</span>
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

                    <div class="settings-section">
                        <h4>Additional Options</h4>
                        <div class="toggle-group">
                            <label class="toggle-option">
                                <div class="toggle-info">
                                    <span class="toggle-title">Show Online Status</span>
                                    <span class="toggle-desc">Let others see when you're online</span>
                                </div>
                                <div class="toggle-switch">
                                    <input type="checkbox" name="show_online_status" <?php echo ($privacy['show_online_status'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </div>
                            </label>
                            <label class="toggle-option">
                                <div class="toggle-info">
                                    <span class="toggle-title">Show Last Seen</span>
                                    <span class="toggle-desc">Show when you were last active</span>
                                </div>
                                <div class="toggle-switch">
                                    <input type="checkbox" name="show_last_seen" <?php echo ($privacy['show_last_seen'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Privacy Settings</button>
                </form>
            </div>

            <!-- NOTIFICATIONS TAB -->
            <div class="settings-tab <?php echo $active_tab === 'notifications' ? 'active' : ''; ?>" id="notifications">
                <div class="tab-header">
                    <h3>Notification Settings</h3>
                    <p>Choose what notifications you receive</p>
                </div>

                <form method="POST">
                    <?php echo admin_csrf_field(); ?>
                    <input type="hidden" name="action" value="update_notifications">

                    <div class="settings-section">
                        <h4>Admin Notifications</h4>
                        <div class="toggle-group">
                            <label class="toggle-option">
                                <div class="toggle-info">
                                    <span class="toggle-title">Login Notifications</span>
                                    <span class="toggle-desc">Get notified when someone logs into your account</span>
                                </div>
                                <div class="toggle-switch">
                                    <input type="checkbox" name="notify_login" <?php echo ($notifications_settings['notify_login'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </div>
                            </label>
                            <label class="toggle-option">
                                <div class="toggle-info">
                                    <span class="toggle-title">Report Notifications</span>
                                    <span class="toggle-desc">Get notified about new user reports</span>
                                </div>
                                <div class="toggle-switch">
                                    <input type="checkbox" name="notify_reports" <?php echo ($notifications_settings['notify_reports'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </div>
                            </label>
                            <label class="toggle-option">
                                <div class="toggle-info">
                                    <span class="toggle-title">User Activity Alerts</span>
                                    <span class="toggle-desc">Get notified about important user activity</span>
                                </div>
                                <div class="toggle-switch">
                                    <input type="checkbox" name="notify_user_activity" <?php echo ($notifications_settings['notify_user_activity'] ?? 1) ? 'checked' : ''; ?>>
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
                                    <span class="toggle-title">Email Notifications</span>
                                    <span class="toggle-desc">Receive important notifications via email</span>
                                </div>
                                <div class="toggle-switch">
                                    <input type="checkbox" name="email_notifications" <?php echo ($notifications_settings['email_notifications'] ?? 0) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Notification Settings</button>
                </form>
            </div>

            <!-- ACCOUNT INFO TAB -->
            <div class="settings-tab <?php echo $active_tab === 'account' ? 'active' : ''; ?>" id="account">
                <div class="tab-header">
                    <h3>Account Information</h3>
                    <p>View your account details</p>
                </div>

                <div class="settings-section">
                    <h4>Account Details</h4>
                    <table class="info-table">
                        <tr><td>Username</td><td><?php echo htmlspecialchars($admin['username']); ?></td></tr>
                        <tr><td>Email</td><td><?php echo htmlspecialchars($admin['email']); ?></td></tr>
                        <tr><td>Full Name</td><td><?php echo htmlspecialchars($admin['full_name'] ?? '-'); ?></td></tr>
                        <tr><td>Role</td><td><?php echo ADMIN_ROLES[$admin['role']] ?? ucfirst($admin['role']); ?></td></tr>
                        <tr><td>Status</td><td><?php echo $admin['is_active'] ? '<span style="color:var(--success)">Active</span>' : '<span style="color:var(--danger)">Inactive</span>'; ?></td></tr>
                        <tr><td>Member Since</td><td><?php echo admin_format_date($admin['created_at']); ?></td></tr>
                        <tr><td>Last Login</td><td><?php echo $admin['last_login'] ? admin_format_date($admin['last_login']) : 'Never'; ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.settings-nav .nav-item[data-tab]').forEach(function(item) {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        var tab = this.dataset.tab;
        document.querySelectorAll('.settings-nav .nav-item').forEach(function(n) { n.classList.remove('active'); });
        this.classList.add('active');
        document.querySelectorAll('.settings-tab').forEach(function(t) { t.classList.remove('active'); });
        document.getElementById(tab).classList.add('active');
        history.replaceState(null, null, '?tab=' + tab);
    });
});

// Theme card selection
document.querySelectorAll('.theme-card input').forEach(function(input) {
    input.addEventListener('change', function() {
        document.querySelectorAll('.theme-card').forEach(function(c) { c.classList.remove('active'); });
        this.closest('.theme-card').classList.add('active');
    });
});

// Bio char counter
var bioInput = document.querySelector('textarea[name="bio"]');
if (bioInput) {
    bioInput.addEventListener('input', function() {
        document.getElementById('bioCount').textContent = this.value.length;
    });
}
</script>

<?php include 'includes/footer.php'; ?>
