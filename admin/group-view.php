<?php
/**
 * =====================================================
 * Admin Group View
 * ChatApp - Group Details Page
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

$group_id = (int)($_GET['id'] ?? 0);

if (!$group_id) {
    header('Location: groups.php');
    exit;
}

// Get group data
$group_query = "SELECT g.*, u.username as creator_name FROM groups g LEFT JOIN users u ON g.created_by = u.id WHERE g.id = ?";
$group_stmt = mysqli_prepare($conn, $group_query);
mysqli_stmt_bind_param($group_stmt, 'i', $group_id);
mysqli_stmt_execute($group_stmt);
$group = mysqli_fetch_assoc(mysqli_stmt_get_result($group_stmt));

if (!$group) {
    header('Location: groups.php');
    exit;
}

// Get members
$members_query = "SELECT gm.*, u.username, u.email, u.avatar, u.is_online 
                  FROM group_members gm 
                  JOIN users u ON gm.user_id = u.id 
                  WHERE gm.group_id = ? 
                  ORDER BY gm.role ASC, gm.joined_at ASC";
$members_stmt = mysqli_prepare($conn, $members_query);
mysqli_stmt_bind_param($members_stmt, 'i', $group_id);
mysqli_stmt_execute($members_stmt);
$members = [];
while ($row = mysqli_fetch_assoc(mysqli_stmt_get_result($members_stmt))) {
    $members[] = $row;
}

// Get recent messages
$messages_query = "SELECT gm.*, u.username 
                   FROM group_messages gm 
                   JOIN users u ON gm.user_id = u.id 
                   WHERE gm.group_id = ? 
                   ORDER BY gm.created_at DESC LIMIT 20";
$messages_stmt = mysqli_prepare($conn, $messages_query);
mysqli_stmt_bind_param($messages_stmt, 'i', $group_id);
mysqli_stmt_execute($messages_stmt);
$messages = [];
while ($row = mysqli_fetch_assoc(mysqli_stmt_get_result($messages_stmt))) {
    $messages[] = $row;
}

$page_title = 'Group Details';
include 'includes/header.php';
?>

<a href="groups.php" class="btn btn-secondary mb-4"><i class="fas fa-arrow-left"></i> Back to Groups</a>

<div class="row">
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="card-body text-center">
                <div class="stat-icon blue mx-auto mb-3" style="width: 80px; height: 80px; font-size: 32px;">
                    <i class="fas fa-user-group"></i>
                </div>
                <h3 class="mb-1"><?php echo htmlspecialchars($group['name']); ?></h3>
                <p class="text-muted"><?php echo htmlspecialchars($group['description'] ?? 'No description'); ?></p>
                <?php echo admin_status_badge($group['status']); ?>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="card-header"><h2>Info</h2></div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr><td class="text-muted">ID</td><td><?php echo $group['id']; ?></td></tr>
                    <tr><td class="text-muted">Created by</td><td><?php echo htmlspecialchars($group['creator_name'] ?? 'Unknown'); ?></td></tr>
                    <tr><td class="text-muted">Members</td><td><?php echo count($members); ?></td></tr>
                    <tr><td class="text-muted">Created</td><td><?php echo admin_format_date($group['created_at']); ?></td></tr>
                </table>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="card-header"><h2>Actions</h2></div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-danger" onclick="deleteGroup(<?php echo $group_id; ?>)">
                        <i class="fas fa-trash"></i> Delete Group
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="card-header"><h2>Members (<?php echo count($members); ?>)</h2></div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr><th>User</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <?php echo admin_user_avatar($member, 32); ?>
                                        <div class="user-info">
                                            <h4><?php echo htmlspecialchars($member['username']); ?></h4>
                                            <p><?php echo htmlspecialchars($member['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo admin_role_badge($member['role']); ?></td>
                                <td><?php echo admin_time_ago($member['joined_at']); ?></td>
                                <td><a href="user-view.php?id=<?php echo $member['user_id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="card-header"><h2>Recent Messages</h2></div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr><th>User</th><th>Message</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $msg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($msg['username']); ?></td>
                                <td><?php echo admin_truncate(htmlspecialchars($msg['message']), 60); ?></td>
                                <td><?php echo admin_time_ago($msg['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($messages)): ?>
                            <tr><td colspan="3" class="text-center" style="padding: 20px;">No messages yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
