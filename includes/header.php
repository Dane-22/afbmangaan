<?php
/**
 * Header Template
 * AFB Mangaan Attendance System
 */
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'AFB Mangaan Attendance System'; ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- HTML5 QR Code Scanner (for QR attendance) -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="animate__animated animate__fadeIn">
    <div class="app-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navigation -->
            <nav class="top-nav">
                <div class="nav-left">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="ph ph-list"></i>
                    </button>
                    <h1 class="page-title"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                </div>
                
                <div class="nav-right">
                    <!-- Theme Toggle -->
                    <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">
                        <i class="ph ph-sun" id="themeIcon"></i>
                    </button>
                    
                    <!-- User Dropdown -->
                    <div class="user-dropdown">
                        <button class="user-btn" id="userDropdownBtn">
                            <div class="user-avatar">
                                <i class="ph ph-user"></i>
                            </div>
                            <span class="user-name"><?php echo htmlspecialchars($currentUser['fullname'] ?? 'User'); ?></span>
                            <i class="ph ph-caret-down"></i>
                        </button>
                        <div class="dropdown-menu" id="userDropdownMenu">
                            <a href="settings.php" class="dropdown-item">
                                <i class="ph ph-gear"></i>
                                <span>Settings</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item">
                                <i class="ph ph-sign-out"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Page Content -->
            <div class="content-wrapper">
