<?php
/**
 * AFB Mangaan Attendance System
 * Change Password Page (change_password.php)
 */

session_start();

require_once __DIR__ . '/functions/auth_functions.php';
require_once __DIR__ . '/functions/activity_logger.php';
require_once __DIR__ . '/functions/csrf.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Redirect if password change is not required
if (!($_SESSION['must_change_password'] ?? false)) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed. Please refresh the page and try again.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All fields are required';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters long';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New password and confirmation do not match';
        } elseif ($currentPassword === $newPassword) {
            $error = 'New password must be different from current password';
        } else {
            // Verify current password
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user && md5($currentPassword) === $user['password']) {
                // Update password
                $newHash = md5($newPassword);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newHash, $_SESSION['user_id']]);
                
                // Log the password change
                logActivity($_SESSION['user_id'], 'PASSWORD_CHANGE', "User {$_SESSION['username']} changed their password");
                
                // Update session
                $_SESSION['must_change_password'] = false;
                
                $success = 'Password changed successfully. Redirecting to dashboard...';
                
                // Redirect after 2 seconds
                header('refresh:2;url=dashboard.php');
            } else {
                $error = 'Current password is incorrect';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - AFB Mangaan Attendance System</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Cinzel:wght@600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><i class="ph ph-shield-check"></i> Change Password</h1>
                <p>You must change your password to continue</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="ph ph-warning-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="ph ph-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php else: ?>
                <form method="POST" class="login-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <div class="input-wrapper">
                            <i class="ph ph-lock-key" aria-hidden="true"></i>
                            <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Enter current password" required aria-required="true" maxlength="255">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="input-wrapper">
                            <i class="ph ph-lock-key-open" aria-hidden="true"></i>
                            <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter new password (min 6 characters)" required aria-required="true" minlength="6" maxlength="255">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="input-wrapper">
                            <i class="ph ph-lock-key-open" aria-hidden="true"></i>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm new password" required aria-required="true" minlength="6" maxlength="255">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="ph ph-check"></i>
                        Change Password
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="login-footer">
                <p style="margin-top: 0.5rem; font-size: 0.75rem;">v1.0.0</p>
            </div>
        </div>
    </div>
    
    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const html = document.documentElement;
        
        // Check saved theme or default to dark
        const savedTheme = localStorage.getItem('theme') || 'dark';
        html.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
        
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const currentTheme = html.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme);
            });
        }
        
        function updateThemeIcon(theme) {
            if (themeIcon) {
                themeIcon.className = theme === 'dark' ? 'ph ph-moon' : 'ph ph-sun';
            }
        }
    </script>
</body>
</html>
