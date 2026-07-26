<?php
/**
 * =====================================================
 * Reset Password Page
 * ChatApp - Password Reset Form
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

session_initialize();

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    header('Location: forgot-password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $result = reset_password($token, $password, $confirm_password);
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ChatApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-logo">
                    <div class="logo-icon"><i class="fas fa-lock"></i></div>
                    <h1>Reset Password</h1>
                    <p>Enter your new password</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
                    <div class="auth-footer"><p><a href="../login.php"><i class="fas fa-sign-in-alt me-1"></i> Go to Login</a></p></div>
                <?php else: ?>
                <form method="POST" class="auth-form">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password" required minlength="8">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required minlength="8">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-auth btn-primary-custom">
                        <i class="fas fa-save"></i> Reset Password
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p><a href="../login.php"><i class="fas fa-arrow-left me-1"></i> Back to Login</a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
</body>
</html>
