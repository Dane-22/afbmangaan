<?php
/**
 * Settings Page - AFB Mangaan Attendance System
 */

$pageTitle = 'Settings';
require_once __DIR__ . '/includes/auth_check.php';

$message = '';
$error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $result = updatePassword($_SESSION['user_id'], $currentPassword, $newPassword);
        if ($result['success']) {
            $message = 'Password updated successfully';
        } else {
            $error = $result['message'];
        }
    }
}

$currentUser = getCurrentUser();
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<?php if ($message): ?>
    <div class="alert badge-success" style="margin-bottom: 1rem; padding: 1rem;">
        <i class="ph ph-check-circle"></i> <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert badge-danger" style="margin-bottom: 1rem; padding: 1rem;">
        <i class="ph ph-warning-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;" class="settings-layout">
    <!-- Profile Info -->
    <div class="card animate__animated animate__fadeIn">
        <div class="card-header">
            <h3 class="card-title">
                <i class="ph ph-user"></i>
                Profile Information
            </h3>
        </div>
        <div class="card-body">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary), var(--primary-light)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem; color: white;">
                    <i class="ph ph-user"></i>
                </div>
                <h4><?php echo htmlspecialchars($currentUser['fullname']); ?></h4>
                <span class="badge badge-info"><?php echo ucfirst($currentUser['role']); ?></span>
            </div>
            
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 0.5rem; color: var(--text-muted);">Username</td>
                    <td style="padding: 0.5rem; text-align: right;"><?php echo htmlspecialchars($currentUser['username']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; color: var(--text-muted);">Role</td>
                    <td style="padding: 0.5rem; text-align: right;"><?php echo ucfirst($currentUser['role']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; color: var(--text-muted);">User ID</td>
                    <td style="padding: 0.5rem; text-align: right;">#<?php echo $currentUser['id']; ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="card animate__animated animate__fadeIn" style="animation-delay: 0.1s;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="ph ph-lock-key"></i>
                Change Password
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="change_password" value="1">
                
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="ph ph-floppy-disk"></i> Update Password
                </button>
            </form>
        </div>
    </div>
</div>

<!-- System Information -->
<div class="card animate__animated animate__fadeInUp" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ph ph-info"></i>
            System Information
        </h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius);">
                <div style="font-size: 0.875rem; color: var(--text-muted);">System Version</div>
                <div style="font-weight: 600;">v1.0.0</div>
            </div>
            <div style="padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius);">
                <div style="font-size: 0.875rem; color: var(--text-muted);">PHP Version</div>
                <div style="font-weight: 600;"><?php echo phpversion(); ?></div>
            </div>
            <div style="padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius);">
                <div style="font-size: 0.875rem; color: var(--text-muted);">Session Timeout</div>
                <div style="font-weight: 600;">1 Hour</div>
            </div>
            <div style="padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius);">
                <div style="font-size: 0.875rem; color: var(--text-muted);">Theme</div>
                <div style="font-weight: 600;">Light / Dark Mode</div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
