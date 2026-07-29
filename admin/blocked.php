<?php
/**
 * =====================================================
 * Admin Blocked Users
 * ChatApp - Blocked Users Management
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

$conn = db_connect();

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = ADMIN_PER_PAGE;

$count_query = "SELECT COUNT(*) as total FROM block_list";
$total = mysqli_fetch_assoc(mysqli_query($conn, $count_query))['total'];
$total_pages = ceil($total / $per_page);
$offset = ($page - 1) * $per_page;

$query = "SELECT bl.*, 
          u.username as blocked_username, u.email as blocked_email, u.avatar as blocked_avatar,
          blocker.username as blocker_name
          FROM block_list bl
          LEFT JOIN users u ON bl.blocked_user_id = u.id
          LEFT JOIN users blocker ON bl.user_id = blocker.id
          ORDER BY bl.created_at DESC
          LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ii', $per_page, $offset);
mysqli_stmt_execute($stmt);
$blocks = [];
while ($row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    $blocks[] = $row;
}

$page_title = 'Blocked Users';
include 'includes/header.php';
?>

<div class="admin-card">
    <div class="card-header">
        <h2>Blocked Users (<?php echo number_format($total); ?>)</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Blocked User</th>
                        <th>Blocked By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blocks as $block): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <?php echo admin_user_avatar(['avatar' => $block['blocked_avatar'], 'username' => $block['blocked_username']], 32); ?>
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($block['blocked_username'] ?? 'Unknown'); ?></h4>
                                    <p><?php echo htmlspecialchars($block['blocked_email'] ?? ''); ?></p>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($block['blocker_name'] ?? 'Unknown'); ?></td>
                        <td><?php echo admin_time_ago($block['created_at']); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="user-view.php?id=<?php echo $block['blocked_user_id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
                                <button class="btn btn-sm btn-danger" onclick="adminAjax('blocked.php', 'POST', {action: 'unblock', block_id: <?php echo $block['id']; ?>}).then(r => { if(r.success) location.reload(); })"><i class="fas fa-unlock"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($blocks)): ?>
                    <tr><td colspan="4"><div class="empty-state"><i class="fas fa-ban"></i><h3>No blocked users</h3></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer">
        <?php echo admin_pagination($total, $per_page, $page, 'blocked.php?page='); ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
