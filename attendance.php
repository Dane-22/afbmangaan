<?php
/**
 * Attendance Page - AFB Mangaan Attendance System
 */

$pageTitle = 'Attendance';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/functions/attendance_logic.php';

// Get selected event
$eventId = $_GET['event_id'] ?? null;
$selectedEvent = null;
$attendance = [];

if ($eventId) {
    $selectedEvent = getEvents(['status' => null])[0] ?? null;
    foreach (getEvents() as $e) {
        if ($e['id'] == $eventId) {
            $selectedEvent = $e;
            break;
        }
    }
    
    if ($selectedEvent) {
        $attendance = getEventAttendance($eventId);
    }
}

// Get all events for dropdown
$events = getEvents(['status' => null]);
$todayEvent = getTodayEvent();
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="card animate__animated animate__fadeIn">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ph ph-check-circle"></i>
            Record Attendance
        </h3>
    </div>
    <div class="card-body">
        <!-- Event Selection -->
        <div class="form-group">
            <label class="form-label">Select Event</label>
            <select id="eventSelect" class="form-control form-select" onchange="changeEvent(this.value)">
                <option value="">-- Select an event --</option>
                <?php foreach ($events as $event): ?>
                    <option value="<?php echo $event['id']; ?>" <?php echo ($eventId == $event['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($event['event_name']); ?> - 
                        <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                        (<?php echo $event['status']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($todayEvent && !$eventId): ?>
                <p style="margin-top: 0.5rem; font-size: 0.875rem;">
                    <i class="ph ph-info"></i>
                    Today's event: <a href="?event_id=<?php echo $todayEvent['id']; ?>"><?php echo htmlspecialchars($todayEvent['event_name']); ?></a>
                </p>
            <?php endif; ?>
        </div>
        
        <?php if ($selectedEvent): ?>
            <input type="hidden" id="eventId" value="<?php echo $selectedEvent['id']; ?>">
            
            <!-- Attendance Method Tabs -->
            <div style="display: flex; gap: 0.5rem; margin: 1.5rem 0; border-bottom: 1px solid var(--border-primary);">
                <button type="button" class="btn btn-sm" id="tabSearch" onclick="switchTab('search')" style="border-radius: 0; border-bottom: 2px solid var(--primary);">
                    <i class="ph ph-magnifying-glass"></i> Search
                </button>
                <button type="button" class="btn btn-sm btn-secondary" id="tabQR" onclick="switchTab('qr')">
                    <i class="ph ph-qr-code"></i> QR Scanner
                </button>
            </div>
            
            <!-- Search Tab -->
            <div id="searchPanel">
                <div class="form-group" style="position: relative;">
                    <label class="form-label">Search Member</label>
                    <div class="search-box">
                        <i class="ph ph-magnifying-glass"></i>
                        <input type="text" id="attendeeSearch" class="form-control" placeholder="Type name or QR code..." autocomplete="off">
                    </div>
                    <div id="searchResults" class="search-results"></div>
                    <input type="hidden" id="selectedAttendeeId">
                </div>
                
                <div style="display: flex; gap: 0.75rem;">
                    <button type="button" class="btn btn-success" onclick="submitAttendance('Present')">
                        <i class="ph ph-check"></i> Mark Present
                    </button>
                    <button type="button" class="btn btn-danger" onclick="submitAttendance('Absent')">
                        <i class="ph ph-x"></i> Mark Absent
                    </button>
                </div>
            </div>
            
            <!-- QR Scanner Tab -->
            <div id="qrPanel" style="display: none;">
                <div class="qr-scanner-container">
                    <div class="qr-scanner-frame" id="qrScannerFrame">
                        <div id="qrScanner" style="width: 100%; height: 300px;"></div>
                        <div class="qr-scan-line"></div>
                        <div class="qr-corner top-left"></div>
                        <div class="qr-corner top-right"></div>
                        <div class="qr-corner bottom-left"></div>
                        <div class="qr-corner bottom-right"></div>
                    </div>
                    <div style="display: flex; gap: 0.5rem; justify-content: center; margin-top: 1rem;">
                        <button type="button" class="btn btn-primary" id="startQRScan">
                            <i class="ph ph-play"></i> Start Scanner
                        </button>
                        <button type="button" class="btn btn-secondary" id="stopQRScan">
                            <i class="ph ph-stop"></i> Stop
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                <i class="ph ph-calendar" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                <p>Select an event to start recording attendance</p>
                <a href="events.php?action=create" class="btn btn-primary" style="margin-top: 1rem;">
                    <i class="ph ph-plus"></i> Create New Event
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($selectedEvent): ?>
    <!-- Attendance List -->
    <div class="card animate__animated animate__fadeInUp" style="margin-top: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="ph ph-list"></i>
                Attendance Records
                <span style="font-size: 0.875rem; font-weight: normal; color: var(--text-muted);">
                    (<?php echo count($attendance); ?> recorded)
                </span>
            </h3>
            <div style="display: flex; gap: 0.5rem;">
                <a href="api/export_attendance.php?event_id=<?php echo $eventId; ?>&format=csv" class="btn btn-sm btn-secondary">
                    <i class="ph ph-download"></i> CSV
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-container" id="attendanceList">
                <table class="data-table" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Method</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendance)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    No attendance records yet
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendance as $record): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['fullname']); ?></strong>
                                        <br><small><?php echo $record['qr_token']; ?></small>
                                    </td>
                                    <td><?php echo $record['category']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $record['status'] === 'Present' ? 'success' : 'danger'; ?>">
                                            <?php echo $record['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $record['method']; ?></td>
                                    <td><?php echo date('g:i A', strtotime($record['log_time'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="AttendanceAjax.delete(<?php echo $record['id']; ?>)">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function changeEvent(eventId) {
    if (eventId) {
        window.location.href = '?event_id=' + eventId;
    }
}

function switchTab(tab) {
    const searchPanel = document.getElementById('searchPanel');
    const qrPanel = document.getElementById('qrPanel');
    const tabSearch = document.getElementById('tabSearch');
    const tabQR = document.getElementById('tabQR');
    
    if (tab === 'search') {
        searchPanel.style.display = 'block';
        qrPanel.style.display = 'none';
        tabSearch.classList.remove('btn-secondary');
        tabSearch.style.borderBottom = '2px solid var(--primary)';
        tabQR.classList.add('btn-secondary');
        tabQR.style.borderBottom = 'none';
        AttendanceAjax.stopQR();
    } else {
        searchPanel.style.display = 'none';
        qrPanel.style.display = 'block';
        tabQR.classList.remove('btn-secondary');
        tabQR.style.borderBottom = '2px solid var(--primary)';
        tabSearch.classList.add('btn-secondary');
        tabSearch.style.borderBottom = 'none';
    }
}

function submitAttendance(status) {
    const eventId = document.getElementById('eventId')?.value;
    const attendeeId = document.getElementById('selectedAttendeeId')?.value;
    const attendeeName = document.getElementById('attendeeSearch')?.value;
    
    if (!eventId) {
        showToast('Please select an event first', 'warning');
        return;
    }
    
    if (!attendeeId) {
        showToast('Please search and select a member', 'warning');
        return;
    }
    
    AttendanceAjax.record(eventId, attendeeId, status, 'Manual')
        .then(() => {
            document.getElementById('attendeeSearch').value = '';
            document.getElementById('selectedAttendeeId').value = '';
        });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
