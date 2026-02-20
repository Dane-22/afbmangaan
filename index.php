<?php
/**
 * AFB Mangaan Attendance System
 * Login Page (index.php)
 */

session_start();

require_once __DIR__ . '/functions/auth_functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = loginUser($username, $password);
    
    if ($result['success']) {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AFB Mangaan Attendance System</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 1rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            background-color: var(--bg-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        
        .login-header {
            background-color: var(--bg-sidebar);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }
        
        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .login-subtitle {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        
        .input-group .form-control {
            padding-left: 2.75rem;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.875rem;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .alert {
            padding: 0.875rem 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .login-footer {
            text-align: center;
            padding: 0 2rem 2rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .theme-toggle-login {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: var(--radius);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-page">
        <button class="theme-toggle-login" id="themeToggle">
            <i class="ph ph-moon" id="themeIcon"></i>
        </button>
        
        <div class="login-container animate__animated animate__fadeInUp">
            <div class="login-header">
                <div class="login-logo">
                    <i class="ph ph-church"></i>
                </div>
                <h1 class="login-title">AFB Mangaan</h1>
                <p class="login-subtitle">Attendance & Analytics System</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-error animate__animated animate__shakeX">
                        <i class="ph ph-warning-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <i class="ph ph-user"></i>
                            <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <i class="ph ph-lock-key"></i>
                            <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="ph ph-sign-in"></i>
                        Sign In
                    </button>
                </form>
            </div>
            
            <div class="login-footer">
                <p>Default login: admin / admin123</p>
                <p style="margin-top: 0.5rem; font-size: 0.75rem;">v1.0.0</p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/theme_handler.js"></script>
</body>
</html>
