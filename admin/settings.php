<?php
/**
 * =====================================================
 * Admin Settings
 * ChatApp - System Settings
 * =====================================================
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

admin_require_permission('manage_settings');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!admin_verify_csrf($csrf_token)) {
        $error = 'Invalid security token';
    } else {
        $settings = $_POST['settings'] ?? [];
        foreach ($settings as $key => $value) {
            $stmt = mysqli_prepare($conn, "UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = ?");
            mysqli_stmt_bind_param($stmt, 'sis', $value, admin_get_id(), $key);
            mysqli_stmt_execute($stmt);
        }
        $success = 'Settings updated successfully';
    }
}

// Get current settings
$settings = [];
$result = mysqli_query($conn, "SELECT * FROM system_settings ORDER BY setting_key");
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_key']] = $row;
}

$page_title = 'Settings';
include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?php echo admin_csrf_field(); ?>
            
            <div class="admin-card">
                <div class="card-header"><h2>General Settings</h2></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Site Name</label>
                        <input type="text" class="form-control" name="settings[site_name]" value="<?php echo htmlspecialchars($settings['site_name']['setting_value'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Site Description</label>
                        <textarea class="form-control" name="settings[site_description]" rows="3"><?php echo htmlspecialchars($settings['site_description']['setting_value'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-header"><h2>Registration & Security</h2></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Maintenance Mode</label>
                        <select class="form-select" name="settings[maintenance_mode]">
                            <option value="0" <?php echo ($settings['maintenance_mode']['setting_value'] ?? '') === '0' ? 'selected' : ''; ?>>Disabled</option>
                            <option value="1" <?php echo ($settings['maintenance_mode']['setting_value'] ?? '') === '1' ? 'selected' : ''; ?>>Enabled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Registration Enabled</label>
                        <select class="form-select" name="settings[registration_enabled]">
                            <option value="1" <?php echo ($settings['registration_enabled']['setting_value'] ?? '') === '1' ? 'selected' : ''; ?>>Enabled</option>
                            <option value="0" <?php echo ($settings['registration_enabled']['setting_value'] ?? '') === '0' ? 'selected' : ''; ?>>Disabled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max Upload Size (bytes)</label>
                        <input type="number" class="form-control" name="settings[max_upload_size]" value="<?php echo htmlspecialchars($settings['max_upload_size']['setting_value'] ?? '20971520'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Session Lifetime (seconds)</label>
                        <input type="number" class="form-control" name="settings[session_lifetime]" value="<?php echo htmlspecialchars($settings['session_lifetime']['setting_value'] ?? '86400'); ?>">
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
                <a href="settings.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="card-header"><h2>System Info</h2></div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr><td class="text-muted">PHP Version</td><td><?php echo phpversion(); ?></td></tr>
                    <tr><td class="text-muted">MySQL Version</td><td><?php echo mysqli_get_server_info($conn); ?></td></tr>
                    <tr><td class="text-muted">Server Software</td><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td></tr>
                    <tr><td class="text-muted">Max Upload</td><td><?php echo ini_get('upload_max_filesize'); ?></td></tr>
                    <tr><td class="text-muted">Memory Limit</td><td><?php echo ini_get('memory_limit'); ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
