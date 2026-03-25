<?php
/**
 * AFB Mangaan Attendance System
 * Login Page (login.php)
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
    $church = $_POST['church'] ?? 'AFB Mangaan';
    
    $result = loginUser($username, $password, $church);
    
    if ($result['success']) {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AFB Mangaan Attendance System</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <style>
        /* CSS Variables - Matching index.php theme */
        :root {
            --bg: #faf9f6;
            --bg-secondary: #f5f3ef;
            --bg-tertiary: #ebe8e2;
            --text: #1a1a1a;
            --text-secondary: #4a4a4a;
            --text-muted: #7a7a7a;
            --accent: #c9a227;
            --accent-light: #e0b83d;
            --accent-dark: #a88420;
            --divine-glow: rgba(201, 162, 39, 0.15);
            --shadow-soft: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 8px 30px rgba(0, 0, 0, 0.12);
            --shadow-strong: 0 12px 40px rgba(0, 0, 0, 0.18);
            --radius: 12px;
            --radius-lg: 20px;
            --transition: 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="dark"] {
            --bg: #0d0d0d;
            --bg-secondary: #161616;
            --bg-tertiary: #1f1f1f;
            --text: #f5f3ef;
            --text-secondary: #b8b4ab;
            --text-muted: #7a7569;
            --accent: #e8d068;
            --accent-light: #f0e08a;
            --accent-dark: #c9a227;
            --divine-glow: rgba(232, 208, 104, 0.1);
            --shadow-soft: 0 4px 20px rgba(0, 0, 0, 0.4);
            --shadow-medium: 0 8px 30px rgba(0, 0, 0, 0.5);
            --shadow-strong: 0 12px 40px rgba(0, 0, 0, 0.6);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        *, *::before, *::after {
            transition: background-color var(--transition),
                        color var(--transition),
                        border-color var(--transition),
                        box-shadow var(--transition);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        h1, h2, h3 {
            font-family: 'Cinzel', Georgia, serif;
            font-weight: 500;
            letter-spacing: 0.02em;
        }

        /* Login Page */
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--bg-tertiary) 0%, var(--bg-secondary) 50%, var(--bg) 100%);
            padding: 1rem;
            position: relative;
            overflow: hidden;
        }

        .login-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(circle at 20% 80%, var(--divine-glow) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, var(--divine-glow) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            background-color: var(--bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-strong);
            overflow: hidden;
            position: relative;
            z-index: 1;
            border: 1px solid var(--bg-tertiary);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--accent-dark) 0%, var(--accent) 100%);
            color: var(--bg);
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 40px;
            background: var(--accent);
            border-radius: 50%;
            z-index: 2;
        }
        
        .login-logo {
            width: 64px;
            height: 64px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
            color: var(--bg);
            backdrop-filter: blur(10px);
        }
        
        .login-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .login-subtitle {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 2.5rem 2rem 2rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--text);
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent);
            font-size: 1.25rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            border: 2px solid var(--bg-tertiary);
            border-radius: var(--radius);
            background: var(--bg-secondary);
            color: var(--text);
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--divine-glow);
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23c9a227' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.25rem;
            padding-right: 2.75rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border-radius: var(--radius);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-dark) 0%, var(--accent) 100%);
            color: var(--bg);
            box-shadow: 0 4px 15px rgba(201, 162, 39, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(201, 162, 39, 0.4);
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            font-size: 1rem;
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
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .login-footer {
            text-align: center;
            padding: 0 2rem 2rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .login-footer p {
            margin-bottom: 0.5rem;
        }
        
        .theme-toggle-login {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--bg-secondary);
            color: var(--text);
            border: 2px solid var(--accent);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            z-index: 10;
            transition: all 0.3s ease;
        }

        .theme-toggle-login:hover {
            background: var(--accent);
            color: var(--bg);
            transform: rotate(15deg) scale(1.1);
            box-shadow: 0 0 20px var(--divine-glow);
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shakeX {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease-out;
        }

        .animate-shakeX {
            animation: shakeX 0.5s ease-in-out;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
            }

            .login-header {
                padding: 2rem 1.5rem;
            }

            .login-body {
                padding: 2rem 1.5rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <button class="theme-toggle-login" id="themeToggle">
            <i class="ph ph-moon" id="themeIcon"></i>
        </button>
        
        <div class="login-container animate-fadeInUp">
            <div class="login-header">
                <div class="login-logo">
                    <i class="ph ph-church"></i>
                </div>
                <h1 class="login-title">AFB Mangaan</h1>
                <p class="login-subtitle">Attendance & Analytics System</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-error animate-shakeX">
                        <i class="ph ph-warning-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Church</label>
                        <div class="input-group">
                            <i class="ph ph-buildings"></i>
                            <select name="church" class="form-control form-select" required style="padding-left: 2.75rem;">
                                <option value="AFB Mangaan">AFB Mangaan</option>
                                <option value="AFB Lettac Sur">AFB Lettac Sur</option>
                            </select>
                        </div>
                    </div>
                    
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
    
    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const html = document.documentElement;
        
        // Check saved theme or default to dark
        const savedTheme = localStorage.getItem('theme') || 'dark';
        html.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
        
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });
        
        function updateThemeIcon(theme) {
            themeIcon.className = theme === 'dark' ? 'ph ph-moon' : 'ph ph-sun';
        }
    </script>
</body>
</html>
