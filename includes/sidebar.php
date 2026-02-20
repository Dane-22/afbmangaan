<?php
/**
 * Sidebar Navigation
 * AFB Mangaan Attendance System
 */

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Define menu items
$menuItems = [
    ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ph-squares-four', 'url' => 'dashboard.php', 'roles' => ['admin', 'operator', 'viewer']],
    ['id' => 'attendance', 'label' => 'Attendance', 'icon' => 'ph-check-circle', 'url' => 'attendance.php', 'roles' => ['admin', 'operator']],
    ['id' => 'members', 'label' => 'Members', 'icon' => 'ph-users', 'url' => 'members.php', 'roles' => ['admin', 'operator']],
    ['id' => 'events', 'label' => 'Events', 'icon' => 'ph-calendar', 'url' => 'events.php', 'roles' => ['admin', 'operator']],
    ['id' => 'logs', 'label' => 'System Logs', 'icon' => 'ph-scroll', 'url' => 'logs.php', 'roles' => ['admin']],
    ['id' => 'settings', 'label' => 'Settings', 'icon' => 'ph-gear', 'url' => 'settings.php', 'roles' => ['admin', 'operator']],
];
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon">
                <i class="ph ph-church"></i>
            </div>
            <div class="logo-text">
                <span class="logo-title">AFB Mangaan</span>
                <span class="logo-subtitle">Attendance System</span>
            </div>
        </div>
        <button class="sidebar-close" id="sidebarClose">
            <i class="ph ph-x"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php foreach ($menuItems as $item): ?>
                <?php if (in_array($currentUser['role'], $item['roles'])): ?>
                    <li class="nav-item">
                        <a href="<?php echo $item['url']; ?>" 
                           class="nav-link <?php echo ($currentPage === $item['id']) ? 'active' : ''; ?>">
                            <i class="ph <?php echo $item['icon']; ?>"></i>
                            <span><?php echo $item['label']; ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="version-info">
            <span>v1.0.0</span>
        </div>
    </div>
</aside>

<!-- Sidebar Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
