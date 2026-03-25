<?php
/**
 * Dashboard - AFB Mangaan Attendance System
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/functions/attendance_logic.php';
require_once __DIR__ . '/functions/activity_logger.php';

// Get current church
$church = $_SESSION['church'] ?? 'AFB Mangaan';

// Get dashboard stats
$todayEvent = getTodayEvent();
$recentActivity = getRecentActivity(5);
$retentionStats = getRetentionStats(3);

// Get total members for this church
$pdo = getDB();
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendees WHERE church = ? AND status = 'Active'");
$stmt->execute([$church]);
$totalMembers = $stmt->fetch()['total'] ?? 0;
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Church Indicator -->
<div class="church-indicator animate__animated animate__fadeIn" style="background: linear-gradient(135deg, var(--primary-dark), var(--primary)); color: var(--bg-primary); padding: 0.75rem 1.5rem; border-radius: var(--radius); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; box-shadow: var(--shadow-md);">
    <i class="ph ph-buildings" style="font-size: 1.25rem;"></i>
    <div>
        <strong style="font-size: 1rem; font-family: var(--font-heading);"><?php echo htmlspecialchars($church); ?></strong>
        <br><small style="opacity: 0.9;">Current Branch</small>
    </div>
</div>

<div class="stats-grid animate__animated animate__fadeIn">
    <div class="stat-card card-entry">
        <div class="stat-icon primary">
            <i class="ph ph-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">Total Members</div>
            <div class="stat-value" id="totalMembers"><?php echo $totalMembers; ?></div>
            <div class="stat-change">Active members in <?php echo htmlspecialchars($church); ?></div>
        </div>
    </div>
    
    <div class="stat-card card-entry">
        <div class="stat-icon success">
            <i class="ph ph-calendar-check"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">Today's Event</div>
            <div class="stat-value" style="font-size: 1.25rem;">
                <?php echo $todayEvent ? htmlspecialchars($todayEvent['event_name']) : 'No Event'; ?>
            </div>
            <div class="stat-change">
                <?php echo $todayEvent ? date('g:i A', strtotime($todayEvent['event_time'])) : 'Create an event'; ?>
            </div>
        </div>
    </div>
    
    <div class="stat-card card-entry">
        <div class="stat-icon warning">
            <i class="ph ph-chart-bar"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">Consistent Members</div>
            <div class="stat-value"><?php echo $retentionStats['consistent_count']; ?></div>
            <div class="stat-change">High attendance rate</div>
        </div>
    </div>
    
    <div class="stat-card card-entry">
        <div class="stat-icon danger">
            <i class="ph ph-warning"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">At Risk Members</div>
            <div class="stat-value"><?php echo $retentionStats['at_risk_count']; ?></div>
            <div class="stat-change">Need attention</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;" class="dashboard-charts-layout">
    <!-- Attendance Trends Chart -->
    <div class="card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="ph ph-chart-line-up"></i>
                Attendance Trends
            </h3>
            <a href="reports.php" class="btn btn-sm btn-secondary">View Reports</a>
        </div>
        <div class="card-body">
            <div style="height: 300px;">
                <canvas id="attendanceTrendChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Category Distribution -->
    <div class="card animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="ph ph-chart-pie"></i>
                Categories
            </h3>
        </div>
        <div class="card-body">
            <div style="height: 250px;">
                <canvas id="categoryDistributionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;" class="dashboard-charts-layout">
    <!-- Member Retention Chart -->
    <div class="card animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="ph ph-heartbeat"></i>
                Member Retention
            </h3>
        </div>
        <div class="card-body">
            <div style="height: 200px;">
                <canvas id="retentionChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="card animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="ph ph-activity"></i>
                Recent Activity
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($recentActivity)): ?>
                <p style="text-align: center; color: var(--text-muted);">No recent activity</p>
            <?php else: ?>
                <div class="activity-list">
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="activity-item" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid var(--border-primary);">
                            <div style="width: 32px; height: 32px; background: var(--bg-tertiary); border-radius: var(--radius); display: flex; align-items: center; justify-content: center;">
                                <i class="ph ph-<?php 
                                    echo match($activity['action']) {
                                        'LOGIN' => 'sign-in',
                                        'LOGOUT' => 'sign-out',
                                        'ATTENDANCE_RECORD' => 'check-circle',
                                        'USER_CREATED' => 'user-plus',
                                        default => 'dot'
                                    };
                                ?>"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 500; font-size: 0.875rem;">
                                    <?php echo htmlspecialchars($activity['action']); ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    <?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?> • 
                                    <?php echo date('M d, g:i A', strtotime($activity['timestamp'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card animate__animated animate__fadeInUp" style="margin-top: 1.5rem; animation-delay: 0.6s;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ph ph-lightning"></i>
            Quick Actions
        </h3>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="attendance.php" class="btn btn-primary">
                <i class="ph ph-check-circle"></i>
                Take Attendance
            </a>
            <a href="members.php?action=add" class="btn btn-secondary">
                <i class="ph ph-user-plus"></i>
                Add Member
            </a>
            <a href="events.php?action=create" class="btn btn-secondary">
                <i class="ph ph-calendar-plus"></i>
                Create Event
            </a>
            <?php if ($todayEvent): ?>
                <a href="attendance.php?event_id=<?php echo $todayEvent['id']; ?>" class="btn btn-success">
                    <i class="ph ph-scanner"></i>
                    Today's Attendance
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
