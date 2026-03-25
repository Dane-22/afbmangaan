<?php
/**
 * Sidebar Navigation
 * AFB Mangaan Attendance System
 */

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Get current church
$sidebarChurch = $_SESSION['church'] ?? 'AFB Mangaan';

// Define menu items
$menuItems = [
    ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ph-squares-four', 'url' => 'dashboard.php', 'roles' => ['admin', 'operator', 'viewer']],
    ['id' => 'attendance', 'label' => 'Attendance', 'icon' => 'ph-check-circle', 'url' => 'attendance.php', 'roles' => ['admin', 'operator']],
    ['id' => 'attendance_audit', 'label' => 'Attendance Audit', 'icon' => 'ph-magnifying-glass', 'url' => 'attendance_audit.php', 'roles' => ['admin', 'operator']],
    ['id' => 'members', 'label' => 'Members', 'icon' => 'ph-users', 'url' => 'members.php', 'roles' => ['admin', 'operator']],
    ['id' => 'events', 'label' => 'Events', 'icon' => 'ph-calendar', 'url' => 'events.php', 'roles' => ['admin', 'operator']],
    ['id' => 'logs', 'label' => 'System Logs', 'icon' => 'ph-scroll', 'url' => 'logs.php', 'roles' => ['admin']],
    ['id' => 'settings', 'label' => 'Settings', 'icon' => 'ph-gear', 'url' => 'settings.php', 'roles' => ['admin', 'operator']],
];
?>

<aside class="sidebar" id="sidebar" style="background-color: transparent; color: var(--text-inverse); transition: background-color 0.3s ease;">
    <div class="sidebar-header" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
        <div class="logo">
            <div class="logo-icon" style="background: transparent;">
                <?php if ($sidebarChurch === 'AFB Mangaan'): ?>
                    <!-- AFB Mangaan Logos -->
                    <img src="assets/img/logo-white.png" alt="AFB Mangaan Logo" class="logo-img-dark" style="width: 40px; height: 40px; object-fit: contain;">
                    <img src="assets/img/logo-black.png" alt="AFB Mangaan Logo" class="logo-img-light" style="width: 40px; height: 40px; object-fit: contain; display: none;">
                <?php else: ?>
                    <!-- AFB Lettac Sur Logo -->
                    <img src="assets/img/lettacsur-logo.png" alt="AFB Lettac Sur Logo" style="width: 40px; height: 40px; object-fit: contain;" onerror="this.src='assets/img/logo-white.png'">
                <?php endif; ?>
            </div>
            <div class="logo-text">
                <span class="logo-title" style="color: var(--text-inverse);"><?php echo htmlspecialchars($sidebarChurch); ?></span>
                <span class="logo-subtitle" style="color: var(--text-inverse); opacity: 0.7;">Attendance System</span>
            </div>
        </div>
        <button class="sidebar-close" id="sidebarClose" style="color: var(--text-inverse);">
            <i class="ph ph-x"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php foreach ($menuItems as $item): ?>
                <?php if (in_array($currentUser['role'], $item['roles'])): ?>
                    <li class="nav-item">
                        <a href="<?php echo $item['url']; ?>" 
                           class="nav-link <?php echo ($currentPage === $item['id']) ? 'active' : ''; ?>"
                           style="color: rgba(255,255,255,0.7); transition: all 0.2s ease; border-left: 3px solid transparent;">
                            <i class="ph <?php echo $item['icon']; ?>"></i>
                            <span><?php echo $item['label']; ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>
    
    <div class="sidebar-footer" style="border-top: 1px solid rgba(255,255,255,0.1);">
        <div class="version-info" style="color: var(--text-inverse); opacity: 0.5;">
            <span>v1.0.0</span>
        </div>
    </div>
</aside>

<style>
/* Light mode sidebar - white background */
[data-theme="light"] .sidebar {
    background-color: #ffffff !important;
    color: #1a1a1a !important;
}

[data-theme="light"] .sidebar-header {
    border-bottom-color: rgba(0,0,0,0.1) !important;
}

[data-theme="light"] .logo-title,
[data-theme="light"] .logo-subtitle {
    color: #1a1a1a !important;
}

[data-theme="light"] .sidebar-close {
    color: #1a1a1a !important;
}

[data-theme="light"] .nav-link {
    color: rgba(0,0,0,0.7) !important;
}

[data-theme="light"] .nav-link:hover,
[data-theme="light"] .nav-link.active {
    color: #1a1a1a !important;
    background-color: rgba(0,0,0,0.05) !important;
    border-left-color: var(--primary) !important;
}

[data-theme="light"] .sidebar-footer {
    border-top-color: rgba(0,0,0,0.1) !important;
}

[data-theme="light"] .version-info {
    color: #7a7a7a !important;
}

[data-theme="light"] .logo-img-dark {
    display: none !important;
}

[data-theme="light"] .logo-img-light {
    display: block !important;
}

/* Dark mode sidebar - dark background */
[data-theme="dark"] .sidebar {
    background-color: #0a0a0a !important;
    color: #f5f3ef !important;
}

[data-theme="dark"] .logo-title,
[data-theme="dark"] .logo-subtitle {
    color: #f5f3ef !important;
}

[data-theme="dark"] .sidebar-close {
    color: #f5f3ef !important;
}

[data-theme="dark"] .nav-link {
    color: rgba(255,255,255,0.7) !important;
}

[data-theme="dark"] .nav-link:hover,
[data-theme="dark"] .nav-link.active {
    color: #f5f3ef !important;
    background-color: rgba(255,255,255,0.05) !important;
}

[data-theme="dark"] .logo-img-dark {
    display: block !important;
}

[data-theme="dark"] .logo-img-light {
    display: none !important;
}

/* Navigation link hover/active states */
.nav-link:hover {
    border-left-color: var(--primary) !important;
}

.nav-link.active {
    border-left-color: var(--primary) !important;
}
</style>

<!-- Sidebar Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
