<?php
/**
 * Reports Page - AFB Mangaan Attendance System
 */

$pageTitle = 'Reports';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/functions/report_engine.php';

// Handle date filters
$fromDate = $_GET['from_date'] ?? date('Y-m-d', strtotime('-30 days'));
$toDate = $_GET['to_date'] ?? date('Y-m-d');
$eventId = $_GET['event_id'] ?? null;
$category = $_GET['category'] ?? null;

// Get summary stats
$summary = getReportSummary($fromDate, $toDate);

// Get attendance data
$reportData = getAttendanceReport($eventId, $fromDate, $toDate, $category);

// Get events for dropdown
$events = getEvents();
$categories = ['Youth', 'Adult', 'Senior', 'Child'];

// Get top attendees
$topAttendees = getTopAttendees(10, $fromDate, $toDate);

// Get monthly comparison
$monthlyData = getMonthlyComparison(date('Y'));
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Filters -->
<div class="card animate__animated animate__fadeIn">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ph ph-funnel"></i>
            Report Filters
        </h3>
    </div>
    <div class="card-body">
        <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo $fromDate; ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo $toDate; ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Event</label>
                <select name="event_id" class="form-control form-select">
                    <option value="">All Events</option>
                    <?php foreach ($events as $e): ?>
                        <option value="<?php echo $e['id']; ?>" <?php echo $eventId == $e['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($e['event_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Category</label>
                <select name="category" class="form-control form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="ph ph-magnifying-glass"></i> Generate Report
            </button>
        </form>
    </div>
</div>

<!-- Summary Stats -->
<div class="stats-grid animate__animated animate__fadeInUp" style="margin-top: 1.5rem;">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="ph ph-calendar"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">Total Events</div>
            <div class="stat-value"><?php echo $summary['total_events']; ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="ph ph-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">Total Attendance</div>
            <div class="stat-value"><?php echo $summary['total_present']; ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="ph ph-chart-pie"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">Attendance Rate</div>
            <div class="stat-value">
                <?php 
                $rate = $summary['total_attendance'] > 0 
                    ? round(($summary['total_present'] / $summary['total_attendance']) * 100, 1) 
                    : 0;
                echo $rate . '%';
                ?>
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="ph ph-trend-up"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">Avg Per Event</div>
            <div class="stat-value">
                <?php 
                $avg = $summary['total_events'] > 0 
                    ? round($summary['total_present'] / $summary['total_events'], 1) 
                    : 0;
                echo $avg;
                ?>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
    <!-- Detailed Report Table -->
    <div class="card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="ph ph-table"></i>
                Attendance Records
                <span style="font-size: 0.875rem; font-weight: normal; color: var(--text-muted);">
                    (<?php echo count($reportData); ?> records)
                </span>
            </h3>
            <div style="display: flex; gap: 0.5rem;">
                <a href="api/export_report.php?format=csv&from_date=<?php echo $fromDate; ?>&to_date=<?php echo $toDate; ?>&event_id=<?php echo $eventId; ?>&category=<?php echo $category; ?>" class="btn btn-sm btn-secondary">
                    <i class="ph ph-file-csv"></i> CSV
                </a>
                <a href="api/export_report.php?format=pdf&from_date=<?php echo $fromDate; ?>&to_date=<?php echo $toDate; ?>&event_id=<?php echo $eventId; ?>&category=<?php echo $category; ?>" class="btn btn-sm btn-danger" target="_blank">
                    <i class="ph ph-file-pdf"></i> PDF
                </a>
                <a href="api/export_report.php?format=xlsx&from_date=<?php echo $fromDate; ?>&to_date=<?php echo $toDate; ?>&event_id=<?php echo $eventId; ?>&category=<?php echo $category; ?>" class="btn btn-sm btn-success">
                    <i class="ph ph-file-xls"></i> Excel
                </a>
            </div>
        </div>
        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
            <!-- Desktop Table View -->
            <div class="table-container desktop-only">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $displayData = array_slice($reportData, 0, 50); // Limit to 50 for display
                        foreach ($displayData as $row): 
                        ?>
                            <tr>
                                <td data-label="Employee"><?php echo htmlspecialchars($row['fullname']); ?></td>
                                <td data-label="Event"><?php echo htmlspecialchars($row['event_name']); ?></td>
                                <td data-label="Date"><?php echo date('M d, Y', strtotime($row['event_date'])); ?></td>
                                <td data-label="Status">
                                    <span class="badge badge-<?php echo $row['attendance_status'] === 'Present' ? 'success' : 'danger'; ?>">
                                        <?php echo $row['attendance_status'] ?? 'N/A'; ?>
                                    </span>
                                </td>
                                <td data-label="Method"><?php echo $row['method'] ?? '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($reportData) > 50): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted);">
                                    ... and <?php echo count($reportData) - 50; ?> more records
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Grid View -->
            <div class="mobile-grid-view mobile-only" style="padding: 0.5rem;">
                <?php 
                $displayData = array_slice($reportData, 0, 50);
                foreach ($displayData as $row): 
                    $cardBorder = $row['attendance_status'] === 'Present' ? 'border-left: 4px solid #22c55e;' : 'border-left: 4px solid #ef4444;';
                ?>
                    <div class="report-grid-card" style="<?php echo $cardBorder; ?>">
                        <div class="report-grid-header">
                            <div class="report-avatar">
                                <?php echo strtoupper(substr($row['fullname'], 0, 2)); ?>
                            </div>
                            <div class="report-grid-info">
                                <h4><?php echo htmlspecialchars($row['fullname']); ?></h4>
                                <span class="report-event"><?php echo htmlspecialchars($row['event_name']); ?></span>
                                <span class="status-badge <?php echo $row['attendance_status'] === 'Present' ? 'status-present' : 'status-absent'; ?>">
                                    <?php echo $row['attendance_status'] ?? 'N/A'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="report-grid-details">
                            <div class="report-detail-row">
                                <span class="detail-label">Date</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($row['event_date'])); ?></span>
                            </div>
                            <div class="report-detail-row">
                                <span class="detail-label">Method</span>
                                <span class="detail-value"><?php echo $row['method'] ?? '-'; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (count($reportData) > 50): ?>
                    <div style="text-align: center; padding: 1rem; color: var(--text-muted);">
                        ... and <?php echo count($reportData) - 50; ?> more records
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Top Attendees -->
    <div class="card animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="ph ph-trophy"></i>
                Top Attendees
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($topAttendees)): ?>
                <p style="text-align: center; color: var(--text-muted);">No attendance data available</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($topAttendees as $i => $attendee): ?>
                        <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: var(--bg-secondary); border-radius: var(--radius);">
                            <div style="width: 28px; height: 28px; background: <?php echo $i < 3 ? 'var(--primary)' : 'var(--bg-tertiary)'; ?>; color: <?php echo $i < 3 ? 'white' : 'var(--text-secondary)'; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 600;">
                                <?php echo $i + 1; ?>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 500; font-size: 0.875rem;"><?php echo htmlspecialchars($attendee['fullname']); ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    <?php echo $attendee['category']; ?> • <?php echo $attendee['attendance_count']; ?> attendances
                                </div>
                            </div>
                            <div style="font-weight: 600; color: var(--success);">
                                <?php echo $attendee['attendance_rate']; ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Category Breakdown -->
<div class="card animate__animated animate__fadeInUp" style="margin-top: 1.5rem; animation-delay: 0.4s;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ph ph-chart-bar"></i>
            Attendance by Category
        </h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
            <?php foreach ($summary['categories'] as $cat): ?>
                <div style="text-align: center; padding: 1.5rem; background: var(--bg-secondary); border-radius: var(--radius-md);">
                    <div style="font-size: 2rem; font-weight: 700; color: var(--primary);">
                        <?php echo $cat['count']; ?>
                    </div>
                    <div style="color: var(--text-secondary); font-size: 0.875rem;">
                        <?php echo $cat['category']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
