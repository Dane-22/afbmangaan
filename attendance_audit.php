<?php
/**
 * Attendance Audit Page - AFB Mangaan Attendance System
 * Review daily attendance records by selecting a date
 */

$pageTitle = 'Attendance Audit';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/functions/attendance_logic.php';

// Get selected date (default to today)
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$searchQuery = $_GET['search'] ?? '';
$selectedEventId = $_GET['event_id'] ?? 'all';

// Get view mode
$viewMode = $_GET['view'] ?? 'day'; // day, week, month

// Get events and attendance based on view mode
$pdo = getDB();
$events = [];
$attendanceData = [];
$totalRecords = 0;
$presentCount = 0;
$absentCount = 0;
$church = $_SESSION['church'] ?? 'AFB Mangaan';

if ($viewMode === 'week') {
    // Get all events for the current week
    $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($selectedDate)));
    $weekEnd = date('Y-m-d', strtotime('sunday this week', strtotime($selectedDate)));
    
    $stmt = $pdo->prepare("SELECT * FROM events WHERE church = ? AND start_date BETWEEN ? AND ? ORDER BY start_date, event_time ASC");
    $stmt->execute([$church, $weekStart, $weekEnd]);
    $events = $stmt->fetchAll();
    
    // Get attendance for all events in the week
    foreach ($events as $event) {
        $attendance = getEventAttendance($event['id']);
        foreach ($attendance as $record) {
            $record['event_name'] = $event['event_name'];
            $record['event_date'] = $event['start_date'];
            $attendanceData[] = $record;
            $totalRecords++;
            if ($record['status'] === 'Present') {
                $presentCount++;
            } elseif ($record['status'] === 'Absent') {
                $absentCount++;
            }
        }
    }
} elseif ($viewMode === 'month') {
    // Get all events for the selected month
    $monthStart = date('Y-m-01', strtotime($selectedDate));
    $monthEnd = date('Y-m-t', strtotime($selectedDate));
    
    $stmt = $pdo->prepare("SELECT * FROM events WHERE church = ? AND start_date BETWEEN ? AND ? ORDER BY start_date, event_time ASC");
    $stmt->execute([$church, $monthStart, $monthEnd]);
    $events = $stmt->fetchAll();
    
    // Get attendance for all events in the month
    foreach ($events as $event) {
        $attendance = getEventAttendance($event['id']);
        foreach ($attendance as $record) {
            $record['event_name'] = $event['event_name'];
            $record['event_date'] = $event['start_date'];
            $attendanceData[] = $record;
            $totalRecords++;
            if ($record['status'] === 'Present') {
                $presentCount++;
            } elseif ($record['status'] === 'Absent') {
                $absentCount++;
            }
        }
    }
} else {
    // Day view - Get events for selected date only
    $stmt = $pdo->prepare("SELECT * FROM events WHERE church = ? AND start_date = ? ORDER BY event_time ASC");
    $stmt->execute([$church, $selectedDate]);
    $events = $stmt->fetchAll();
    
    // Get attendance data for selected event or all events on this date
    if ($selectedEventId === 'all') {
        foreach ($events as $event) {
            $attendance = getEventAttendance($event['id']);
            foreach ($attendance as $record) {
                $record['event_name'] = $event['event_name'];
                $record['event_date'] = $event['start_date'] ?? $event['event_date'] ?? $selectedDate;
                $attendanceData[] = $record;
                $totalRecords++;
                if ($record['status'] === 'Present') {
                    $presentCount++;
                } elseif ($record['status'] === 'Absent') {
                    $absentCount++;
                }
            }
        }
    } elseif ($selectedEventId) {
        $attendance = getEventAttendance($selectedEventId);
        // Get event details
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$selectedEventId]);
        $event = $stmt->fetch();
        foreach ($attendance as $record) {
            $record['event_name'] = $event['event_name'] ?? 'Unknown';
            $record['event_date'] = $event['start_date'] ?? $event['event_date'] ?? $selectedDate;
            $attendanceData[] = $record;
            $totalRecords++;
            if ($record['status'] === 'Present') {
                $presentCount++;
            } elseif ($record['status'] === 'Absent') {
                $absentCount++;
            }
        }
    }
}

// Get ALL events for dropdown filter (last 3 months) - filtered by church
$stmt = $pdo->prepare("SELECT * FROM events WHERE church = ? AND start_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH) ORDER BY start_date DESC, event_time ASC");
$stmt->execute([$church]);
$allEvents = $stmt->fetchAll();

// Filter by search query
if ($searchQuery) {
    $attendanceData = array_filter($attendanceData, function($record) use ($searchQuery) {
        return stripos($record['fullname'], $searchQuery) !== false ||
               stripos($record['qr_token'], $searchQuery) !== false ||
               stripos($record['category'], $searchQuery) !== false;
    });
}

// Get calendar data for the month - filtered by church
$year = date('Y', strtotime($selectedDate));
$month = date('m', strtotime($selectedDate));
$firstDay = date('Y-m-01', strtotime($selectedDate));
$daysInMonth = date('t', strtotime($selectedDate));
$startWeekday = date('w', strtotime($firstDay));

// Get dates with attendance records - filtered by church
$stmt = $pdo->prepare("SELECT DISTINCT DATE(e.start_date) as date FROM events e 
                       JOIN attendance_logs al ON e.id = al.event_id 
                       WHERE e.church = ? AND MONTH(e.start_date) = ? AND YEAR(e.start_date) = ?");
$stmt->execute([$church, $month, $year]);
$datesWithRecords = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<style>
.calendar-container {
    width: 100%;
}
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 4px;
    width: 100%;
}
.calendar-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
    font-weight: 600;
    min-width: 0;
    overflow: hidden;
}
.calendar-day:hover {
    background: var(--bg-secondary);
}
.calendar-day.selected {
    background: #f59e0b;
    color: white;
}
.calendar-day.has-records {
    border: 2px solid #22c55e;
}
.calendar-day.today {
    background: rgba(245, 158, 11, 0.3);
}
.calendar-day.other-month {
    opacity: 0.4;
    color: var(--text-muted);
}
.day-number {
    font-weight: 600;
}
.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 4px;
    text-align: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 6px;
}
.stat-card {
    background: var(--bg-secondary);
    border-radius: 12px;
    padding: 1rem;
    border: 1px solid var(--border-primary);
}
.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1;
}
.stat-label {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}
</style>

<div class="page-header" style="margin-bottom: 1.5rem;">
    <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Attendance Audit</h1>
    <p style="color: var(--text-muted);">Review daily attendance records by selecting a date</p>
</div>

<!-- Search Bar -->
<div class="card animate__animated animate__fadeIn" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="date" value="<?php echo $selectedDate; ?>">
            <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <div class="search-box">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" name="search" class="form-control" placeholder="Search employees, codes, or branches..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
            </div>
            <div class="form-group" style="min-width: 200px; margin-bottom: 0;">
                <select name="event_id" class="form-control form-select" onchange="this.form.submit()">
                    <option value="all">All Events</option>
                    <?php foreach ($allEvents as $event): ?>
                        <option value="<?php echo $event['id']; ?>" <?php echo ($selectedEventId == $event['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($event['event_name']); ?> - <?php echo date('M d, Y g:i A', strtotime($event['start_date'] . ' ' . $event['event_time'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="ph ph-magnifying-glass"></i> Search
            </button>
        </form>
    </div>
</div>

<!-- Quick Filter Buttons -->
<div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
    <a href="?date=<?php echo date('Y-m-d', strtotime('monday this week')); ?>&event_id=<?php echo $selectedEventId; ?>&view=week" class="btn btn-sm btn-secondary <?php echo (isset($_GET['view']) && $_GET['view'] === 'week') ? 'active' : ''; ?>">
        <i class="ph ph-calendar-blank"></i> This Week
    </a>
    <a href="?date=<?php echo date('Y-m-01'); ?>&event_id=<?php echo $selectedEventId; ?>&view=month" class="btn btn-sm btn-secondary <?php echo (isset($_GET['view']) && $_GET['view'] === 'month') ? 'active' : ''; ?>">
        <i class="ph ph-calendar"></i> This Month
    </a>
    <a href="?date=<?php echo date('Y-m-d'); ?>&event_id=<?php echo $selectedEventId; ?>" class="btn btn-sm <?php echo (!isset($_GET['view']) && $selectedDate === date('Y-m-d')) ? 'active' : ''; ?>" style="background: #f59e0b; color: white;">
        <i class="ph ph-calendar-check"></i> Today
    </a>
    <button type="button" class="btn btn-sm btn-primary" onclick="window.print()">
        <i class="ph ph-printer"></i> Generate Report
    </button>
</div>

<div style="display: grid; grid-template-columns: 320px 1fr; gap: 1.5rem;" class="audit-layout">
    <!-- Calendar Panel -->
    <div class="animate__animated animate__fadeIn">
        <!-- Calendar -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <button class="btn btn-sm btn-icon" onclick="changeMonth(-1)">
                    <i class="ph ph-caret-left"></i>
                </button>
                <h3 style="font-size: 1rem; margin: 0;"><?php echo date('F Y', strtotime($selectedDate)); ?></h3>
                <button class="btn btn-sm btn-icon" onclick="changeMonth(1)">
                    <i class="ph ph-caret-right"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="calendar-container">
                    <div class="calendar-header">
                        <div>S</div>
                        <div>M</div>
                        <div>T</div>
                        <div>W</div>
                        <div>T</div>
                        <div>F</div>
                        <div>S</div>
                    </div>
                    <div class="calendar-grid">
                        <?php
                        // Fixed 6 rows = 42 cells for consistent calendar
                        $totalGridCells = 42;
                        
                        // Previous month days
                        $prevMonthDays = date('t', strtotime($firstDay . ' -1 month'));
                        for ($i = $startWeekday - 1; $i >= 0; $i--) {
                            $prevDay = $prevMonthDays - $i;
                            echo '<div class="calendar-day other-month"><span class="day-number">' . $prevDay . '</span></div>';
                        }
                        
                        // Current month days
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $dateStr = sprintf('%s-%s-%02d', $year, $month, $day);
                            $classes = ['calendar-day'];
                            $hasRecords = in_array($dateStr, $datesWithRecords);
                            
                            if ($dateStr === $selectedDate) {
                                $classes[] = 'selected';
                            }
                            if ($hasRecords) {
                                $classes[] = 'has-records';
                            }
                            if ($dateStr === date('Y-m-d')) {
                                $classes[] = 'today';
                            }
                            
                            $classStr = implode(' ', $classes);
                            echo '<div class="' . $classStr . '" onclick="selectDate(\'' . $dateStr . '\')">';
                            echo '<span class="day-number">' . $day . '</span>';
                            echo '</div>';
                        }
                        
                        // Next month days to fill remaining cells
                        $totalCells = $startWeekday + $daysInMonth;
                        $remainingCells = $totalGridCells - $totalCells;
                        for ($i = 1; $i <= $remainingCells; $i++) {
                            echo '<div class="calendar-day other-month"><span class="day-number">' . $i . '</span></div>';
                        }
                        ?>
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1rem; font-size: 0.875rem; justify-content: center;">
                    <div style="display: flex; align-items: center; gap: 0.25rem;">
                        <div style="width: 12px; height: 12px; background: #f59e0b; border-radius: 3px;"></div>
                        <span>Selected</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.25rem;">
                        <div style="width: 12px; height: 12px; border: 2px solid #22c55e; border-radius: 3px;"></div>
                        <span>Has Records</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.25rem;">
                        <div style="width: 12px; height: 12px; background: rgba(245, 158, 11, 0.2); border-radius: 3px;"></div>
                        <span>Today</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Records Panel -->
    <div class="animate__animated animate__fadeIn">
        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1rem;" class="stats-cards-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalRecords; ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #22c55e;"><?php echo $presentCount; ?></div>
                <div class="stat-label">Currently Present</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #3b82f6;">2</div>
                <div class="stat-label">Completed Shifts</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ef4444;"><?php echo $absentCount; ?></div>
                <div class="stat-label">Absent</div>
            </div>
        </div>

        <!-- Attendance List -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">
                    <i class="ph ph-calendar-check" style="color: #f59e0b;"></i>
                    <?php 
                    if ($viewMode === 'week') {
                        $weekStart = date('M d', strtotime('monday this week', strtotime($selectedDate)));
                        $weekEnd = date('M d, Y', strtotime('sunday this week', strtotime($selectedDate)));
                        echo "Week of $weekStart - $weekEnd";
                    } elseif ($viewMode === 'month') {
                        echo date('F Y', strtotime($selectedDate));
                    } else {
                        echo date('F d, Y (l)', strtotime($selectedDate));
                    }
                    ?>
                    <?php if ($selectedEventId !== 'all'): ?>
                        <span style="font-size: 0.75rem; color: var(--text-muted); margin-left: 0.5rem;">
                            (<?php echo htmlspecialchars($allEvents[array_search($selectedEventId, array_column($allEvents, 'id'))]['event_name'] ?? 'Selected Event'); ?>)
                        </span>
                    <?php endif; ?>
                </h3>
                <span style="font-size: 0.875rem; color: var(--text-muted);">
                    <?php echo count($attendanceData); ?> records found
                </span>
            </div>
            <div class="card-body" style="padding: 0;">
                <!-- Desktop Table View -->
                <div class="table-container desktop-only">
                    <table class="data-table" style="font-size: 0.95rem;">
                        <thead>
                            <tr>
                                <th>EMPLOYEE</th>
                                <th>CODE</th>
                                <th>BRANCH</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($attendanceData)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                        <i class="ph ph-calendar-blank" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                                        No attendance records for this date
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($attendanceData as $record): 
                                    $branch = $record['category'] ?? 'Main Branch';
                                ?>
                                    <tr>
                                        <td data-label="Employee">
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 600;">
                                                    <?php echo strtoupper(substr($record['fullname'], 0, 2)); ?>
                                                </div>
                                                <div>
                                                    <strong style="font-size: 0.95rem; color: var(--text-primary);"><?php echo htmlspecialchars($record['fullname']); ?></strong>
                                                    <br><span style="color: var(--text-secondary); font-size: 0.85rem; font-weight: 500;">Worker</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="Code"><code style="font-size: 0.85rem;"><?php echo $record['qr_token']; ?></code></td>
                                        <td data-label="Branch"><?php echo $branch; ?></td>
                                        <td data-label="Status">
                                            <span class="badge badge-<?php echo $record['status'] === 'Present' ? 'success' : 'danger'; ?>" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                                                <?php echo $record['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Grid View -->
                <div class="mobile-grid-view mobile-only" style="padding: 1rem;">
                    <?php if (empty($attendanceData)): ?>
                        <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            <i class="ph ph-calendar-blank" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                            No attendance records for this date
                        </div>
                    <?php else: ?>
                        <?php foreach ($attendanceData as $record): 
                            $branch = $record['category'] ?? 'Main Branch';
                            $cardBorder = '';
                            if ($record['status'] === 'Present') $cardBorder = 'border-left: 4px solid #16a34a;';
                            elseif ($record['status'] === 'Absent') $cardBorder = 'border-left: 4px solid #dc2626;';
                        ?>
                            <div class="audit-grid-card" style="<?php echo $cardBorder; ?>">
                                <div class="audit-grid-header">
                                    <div class="audit-avatar">
                                        <?php echo strtoupper(substr($record['fullname'], 0, 2)); ?>
                                    </div>
                                    <div class="audit-grid-info">
                                        <h4><?php echo htmlspecialchars($record['fullname']); ?></h4>
                                        <span class="audit-code"><?php echo $record['qr_token']; ?></span>
                                        <span class="status-badge <?php echo $record['status'] === 'Present' ? 'status-present' : 'status-absent'; ?>">
                                            <?php echo $record['status']; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="audit-grid-details">
                                    <div class="audit-detail-row">
                                        <span class="detail-label">Branch</span>
                                        <span class="detail-value"><?php echo $branch; ?></span>
                                    </div>
                                    <div class="audit-detail-row">
                                        <span class="detail-label">Event</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($record['event_name'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectDate(date) {
    const eventId = document.querySelector('select[name="event_id"]')?.value || 'all';
    window.location.href = '?date=' + date + '&event_id=' + eventId;
}

function changeMonth(direction) {
    const params = new URLSearchParams(window.location.search);
    const currentDate = params.get('date') || '<?php echo date('Y-m-d'); ?>';
    const eventId = params.get('event_id') || 'all';
    const view = params.get('view') || '';
    const date = new Date(currentDate);
    date.setMonth(date.getMonth() + direction);
    const newDate = date.toISOString().split('T')[0];
    let url = '?date=' + newDate + '&event_id=' + eventId;
    if (view) url += '&view=' + view;
    window.location.href = url;
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
