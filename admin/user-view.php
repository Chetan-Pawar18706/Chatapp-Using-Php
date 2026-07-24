<?php
/**
 * =====================================================
 * Admin User View
 * ChatApp - User Details Page
 * =====================================================
 */

define('APP_RUNNING', true);

require_once dirname(__DIR__) . '/config/database.php';
require_once 'config.php';
require_once 'auth.php';
require_once 'helpers.php';

admin_session_init();

// Check authentication
if (!admin_verify_session()) {
    header('Location: index.php');
    exit;
}

$user_id = (int)($_GET['id'] ?? 0);

if (!$user_id) {
    header('Location: users.php');
    exit;
}

// Get user data
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));

if (!$user) {
    header('Location: users.php');
    exit;
}

// Get user stats
$stats = [];

// Friend count
$friend_query = "SELECT COUNT(*) as count FROM friendships 
                 WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'";
$friend_stmt = mysqli_prepare($conn, $friend_query);
mysqli_stmt_bind_param($friend_stmt, 'ii', $user_id, $user_id);
mysqli_stmt_execute($friend_stmt);
$stats['friends'] = mysqli_fetch_assoc(mysqli_stmt_get_result($friend_stmt))['count'];

// Message count
$msg_query = "SELECT COUNT(*) as count FROM messages WHERE sender_id = ?";
$msg_stmt = mysqli_prepare($conn, $msg_query);
mysqli_stmt_bind_param($msg_stmt, 'i', $user_id);
mysqli_stmt_execute($msg_stmt);
$stats['messages'] = mysqli_fetch_assoc(mysqli_stmt_get_result($msg_stmt))['count'];

// Group count
$group_query = "SELECT COUNT(*) as count FROM group_members WHERE user_id = ?";
$group_stmt = mysqli_prepare($conn, $group_query);
mysqli_stmt_bind_param($group_stmt, 'i', $user_id);
mysqli_stmt_execute($group_stmt);
$stats['groups'] = mysqli_fetch_assoc(mysqli_stmt_get_result($group_stmt))['count'];

// Media count
$media_query = "SELECT COUNT(*) as count FROM media WHERE user_id = ?";
$media_stmt = mysqli_prepare($conn, $media_query);
mysqli_stmt_bind_param($media_stmt, 'i', $user_id);
mysqli_stmt_execute($media_stmt);
$stats['media'] = mysqli_fetch_assoc(mysqli_stmt_get_result($media_stmt))['count'];

// Recent messages
$recent_msgs_query = "SELECT m.*, u.username as receiver_name 
                      FROM messages m 
                      LEFT JOIN users u ON m.receiver_id = u.id 
                      WHERE m.sender_id = ? 
                      ORDER BY m.created_at DESC LIMIT 10";
$recent_msgs_stmt = mysqli_prepare($conn, $recent_msgs_query);
mysqli_stmt_bind_param($recent_msgs_stmt, 'i', $user_id);
mysqli_stmt_execute($recent_msgs_stmt);
$recent_messages = [];
while ($row = mysqli_fetch_assoc(mysqli_stmt_get_result($recent_msgs_stmt))) {
    $recent_messages[] = $row;
}

// Recent groups
$recent_groups_query = "SELECT g.*, gm.role 
                        FROM group_members gm 
                        JOIN groups g ON gm.group_id = g.id 
                        WHERE gm.user_id = ? 
                        ORDER BY gm.joined_at DESC LIMIT 5";
$recent_groups_stmt = mysqli_prepare($conn, $recent_groups_query);
mysqli_stmt_bind_param($recent_groups_stmt, 'i', $user_id);
mysqli_stmt_execute($recent_groups_stmt);
$recent_groups = [];
while ($row = mysqli_fetch_assoc(mysqli_stmt_get_result($recent_groups_stmt))) {
    $recent_groups[] = $row;
}

$page_title = 'User Details';
include 'includes/header.php';
?>

<!-- Back Button -->
<a href="users.php" class="btn btn-secondary mb-4">
    <i class="fas fa-arrow-left"></i> Back to Users
</a>

<div class="row">
    <!-- User Profile Card -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="card-body text-center">
                <?php echo admin_user_avatar($user, 100); ?>
                <h3 class="mt-3 mb-1"><?php echo htmlspecialchars($user['username']); ?></h3>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                
                <?php echo admin_status_badge($user['status']); ?>
                
                <div class="mt-3">
                    <small class="text-muted">
                        Member since <?php echo admin_format_date($user['created_at'], 'M d, Y'); ?>
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="admin-card">
            <div class="card-header">
                <h2>Actions</h2>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($user['status'] !== 'banned'): ?>
                    <button class="btn btn-warning" onclick="toggleUserStatus(<?php echo $user_id; ?>, 'ban')">
                        <i class="fas fa-ban"></i> Ban User
                    </button>
                    <?php else: ?>
                    <button class="btn btn-success" onclick="toggleUserStatus(<?php echo $user_id; ?>, 'unban')">
                        <i class="fas fa-check"></i> Unban User
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($user['is_active']): ?>
                    <button class="btn btn-warning" onclick="toggleUserStatus(<?php echo $user_id; ?>, 'deactivate')">
                        <i class="fas fa-user-slash"></i> Deactivate
                    </button>
                    <?php else: ?>
                    <button class="btn btn-success" onclick="toggleUserStatus(<?php echo $user_id; ?>, 'activate')">
                        <i class="fas fa-user-check"></i> Activate
                    </button>
                    <?php endif; ?>
                    
                    <button class="btn btn-danger" onclick="deleteUser(<?php echo $user_id; ?>)">
                        <i class="fas fa-trash"></i> Delete User
                    </button>
                </div>
            </div>
        </div>
        
        <!-- User Info -->
        <div class="admin-card">
            <div class="card-header">
                <h2>User Info</h2>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td class="text-muted">ID</td>
                        <td><?php echo $user['id']; ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Friend Code</td>
                        <td><code><?php echo htmlspecialchars($user['friend_code']); ?></code></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Email Verified</td>
                        <td>
                            <?php if ($user['email_verified']): ?>
                                <span class="badge bg-success">Verified</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Unverified</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Theme</td>
                        <td><?php echo ucfirst($user['theme'] ?? 'dark'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Last Password Change</td>
                        <td><?php echo $user['last_password_change'] ? admin_format_date($user['last_password_change']) : 'Never'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="col-lg-8">
        <!-- Stats -->
        <div class="stats-grid mb-4">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-user-friends"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['friends']); ?></h3>
                    <p>Friends</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon cyan">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['messages']); ?></h3>
                    <p>Messages</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-user-group"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['groups']); ?></h3>
                    <p>Groups</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon yellow">
                    <i class="fas fa-photo-film"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['media']); ?></h3>
                    <p>Media</p>
                </div>
            </div>
        </div>
        
        <!-- Bio -->
        <?php if (!empty($user['bio']) || !empty($user['about'])): ?>
        <div class="admin-card">
            <div class="card-header">
                <h2>About</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($user['bio'])): ?>
                <p><strong>Bio:</strong> <?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                <?php endif; ?>
                <?php if (!empty($user['about'])): ?>
                <p><strong>About:</strong> <?php echo nl2br(htmlspecialchars($user['about'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recent Messages -->
        <div class="admin-card">
            <div class="card-header">
                <h2>Recent Messages</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Receiver</th>
                                <th>Content</th>
                                <th>Type</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_messages as $msg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($msg['receiver_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo admin_truncate(htmlspecialchars($msg['content']), 50); ?></td>
                                <td><span class="badge bg-secondary"><?php echo ucfirst($msg['message_type']); ?></span></td>
                                <td><?php echo admin_time_ago($msg['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($recent_messages)): ?>
                            <tr>
                                <td colspan="4" class="text-center" style="padding: 20px;">No messages yet</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Recent Groups -->
        <div class="admin-card">
            <div class="card-header">
                <h2>Group Memberships</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Group</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_groups as $group): ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-info">
                                            <h4><?php echo htmlspecialchars($group['name']); ?></h4>
                                            <p><?php echo admin_truncate(htmlspecialchars($group['description'] ?? ''), 30); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo admin_role_badge($group['role']); ?></td>
                                <td><?php echo admin_time_ago($group['joined_at']); ?></td>
                                <td>
                                    <a href="group-view.php?id=<?php echo $group['id']; ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($recent_groups)): ?>
                            <tr>
                                <td colspan="4" class="text-center" style="padding: 20px;">Not in any groups</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
