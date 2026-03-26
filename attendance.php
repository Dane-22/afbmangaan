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
$attendanceMap = [];

if ($eventId) {
    $selectedEvent = getEvents(['status' => null])[0] ?? null;
    foreach (getEvents() as $e) {
        if ($e['id'] == $eventId) {
            $selectedEvent = $e;
            break;
        }
    }
    
    if ($selectedEvent) {
        // Get existing attendance records
        $attendance = getEventAttendance($eventId);
        
        // Create attendance map for quick lookup [attendee_id => status]
        foreach ($attendance as $record) {
            $attendanceMap[$record['attendee_id']] = $record;
        }
        
        // Get all active members for this church
        $pdo = getDB();
        $church = $_SESSION['church'] ?? 'AFB Mangaan';
        $stmt = $pdo->prepare("SELECT id, fullname, category, qr_token FROM attendees WHERE church = ? AND status = 'Active' ORDER BY fullname ASC");
        $stmt->execute([$church]);
        $members = $stmt->fetchAll();
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
                        <?php echo date('M d, Y', strtotime($event['start_date'] ?? $event['event_date'] ?? 'today')); ?>
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
            
            <!-- QR Scanner Toggle -->
            <div style="display: flex; gap: 0.5rem; margin: 1.5rem 0;">
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleQR()">
                    <i class="ph ph-qr-code"></i> QR Scanner
                </button>
            </div>
            
            <!-- QR Scanner Panel -->
            <div id="qrPanel" style="display: none; margin-bottom: 1.5rem;">
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

<?php if ($selectedEvent && !empty($members)): ?>
    <!-- Members List with Attendance Buttons -->
    <div class="card animate__animated animate__fadeInUp" style="margin-top: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="ph ph-users"></i>
                Members List
                <span style="font-size: 0.875rem; font-weight: normal; color: var(--text-muted);">
                    (<?php echo count($members); ?> members)
                </span>
            </h3>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-sm btn-success" onclick="markAllPresent()">
                    <i class="ph ph-check"></i> Mark All Present
                </button>
                <a href="api/export_attendance.php?event_id=<?php echo $eventId; ?>&format=csv" class="btn btn-sm btn-secondary">
                    <i class="ph ph-download"></i> Export
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Desktop Table View -->
            <div class="table-container desktop-only">
                <table class="data-table" id="membersTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Member</th>
                            <th style="width: 240px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $index => $member): 
                            $record = $attendanceMap[$member['id']] ?? null;
                            $status = $record ? $record['status'] : null;
                            $timeIn = $record && $record['log_time'] ? date('h:i A', strtotime($record['log_time'])) : null;
                            $rowClass = '';
                            if ($status === 'Present') $rowClass = 'style="background: rgba(34, 197, 94, 0.05);"';
                            elseif ($status === 'Absent') $rowClass = 'style="background: rgba(239, 68, 68, 0.05);"';
                        ?>
                            <tr id="member-row-<?php echo $member['id']; ?>" <?php echo $rowClass; ?>>
                                <td data-label="#"><?php echo $index + 1; ?></td>
                                <td data-label="Member">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div class="member-avatar" style="width: 36px; height: 36px; border-radius: 8px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.75rem; text-transform: uppercase;">
                                            <?php echo strtoupper(substr($member['fullname'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <strong style="font-size: 0.9rem;"><?php echo htmlspecialchars($member['fullname']); ?></strong>
                                            <br><small style="color: var(--text-muted); font-size: 0.75rem;"><?php echo $member['qr_token']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Actions">
                                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                        <?php if ($status !== 'Absent'): ?>
                                            <button class="btn btn-sm" onclick="quickMark(<?php echo $member['id']; ?>, 'Absent')" id="btn-absent-<?php echo $member['id']; ?>" style="background: #dc2626; color: white; border: none; padding: 0.4rem 0.75rem; font-size: 0.75rem; display: flex; align-items: center; gap: 0.25rem;">
                                                <i class="ph ph-x-circle"></i> Absent
                                            </button>
                                        <?php else: ?>
                                            <span class="status-badge status-absent" style="padding: 0.4rem 0.75rem; font-size: 0.75rem; display: flex; align-items: center; gap: 0.25rem;">
                                                <i class="ph ph-x-circle"></i> Absent
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($status === 'Present'): ?>
                                            <span class="status-badge status-present" style="padding: 0.4rem 0.75rem; font-size: 0.75rem; display: flex; align-items: center; gap: 0.25rem; flex-direction: column; line-height: 1.2;">
                                                <span style="display: flex; align-items: center; gap: 0.25rem;"><i class="ph ph-check-circle"></i> Present</span>
                                                <?php if ($timeIn): ?>
                                                    <small style="font-size: 0.65rem; opacity: 0.9;"><?php echo $timeIn; ?></small>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <button class="btn btn-sm" onclick="quickMark(<?php echo $member['id']; ?>, 'Present')" id="btn-present-<?php echo $member['id']; ?>" style="background: #16a34a; color: white; border: none; padding: 0.4rem 0.75rem; font-size: 0.75rem; display: flex; align-items: center; gap: 0.25rem;">
                                                <i class="ph ph-clock-in"></i> Time In
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Grid View -->
            <div class="mobile-grid-view mobile-only">
                <?php foreach ($members as $index => $member): 
                    $record = $attendanceMap[$member['id']] ?? null;
                    $status = $record ? $record['status'] : null;
                    $timeIn = $record && $record['log_time'] ? date('h:i A', strtotime($record['log_time'])) : null;
                    $cardBorder = '';
                    if ($status === 'Present') $cardBorder = 'border-left: 4px solid #16a34a;';
                    elseif ($status === 'Absent') $cardBorder = 'border-left: 4px solid #dc2626;';
                ?>
                    <div class="member-grid-card" id="member-card-<?php echo $member['id']; ?>" style="<?php echo $cardBorder; ?>">
                        <div class="member-grid-header">
                            <div class="member-avatar-large">
                                <?php echo strtoupper(substr($member['fullname'], 0, 2)); ?>
                            </div>
                            <div class="member-grid-info">
                                <h4><?php echo htmlspecialchars($member['fullname']); ?></h4>
                                <span class="member-code"><?php echo $member['qr_token']; ?></span>
                                <?php if ($status): ?>
                                    <span class="status-badge <?php echo $status === 'Present' ? 'status-present' : 'status-absent'; ?>">
                                        <?php echo $status; ?>
                                        <?php if ($status === 'Present' && $timeIn): ?>
                                            <small style="display: block; font-size: 0.7rem; opacity: 0.9; margin-top: 1px;"><?php echo $timeIn; ?></small>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="member-grid-actions">
                            <?php if ($status !== 'Absent'): ?>
                                <button class="btn btn-absent" onclick="quickMark(<?php echo $member['id']; ?>, 'Absent')" id="btn-absent-card-<?php echo $member['id']; ?>">
                                    <i class="ph ph-x-circle"></i> Absent
                                </button>
                            <?php else: ?>
                                <span class="btn btn-absent" style="opacity: 1; cursor: default;">
                                    <i class="ph ph-x-circle"></i> Absent
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($status === 'Present'): ?>
                                <span class="btn btn-present" style="opacity: 1; cursor: default; flex-direction: column; line-height: 1.2;">
                                    <span><i class="ph ph-check-circle"></i> Present</span>
                                    <?php if ($timeIn): ?>
                                        <small style="font-size: 0.7rem; opacity: 0.9;"><?php echo $timeIn; ?></small>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <button class="btn btn-present" onclick="quickMark(<?php echo $member['id']; ?>, 'Present')" id="btn-present-card-<?php echo $member['id']; ?>">
                                    <i class="ph ph-clock-in"></i> Time In
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php elseif ($selectedEvent): ?>
    <div class="card animate__animated animate__fadeInUp" style="margin-top: 1.5rem;">
        <div class="card-body" style="text-align: center; padding: 3rem;">
            <i class="ph ph-users" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--text-muted);"></i>
            <p>No active members found</p>
            <a href="members.php?action=add" class="btn btn-primary" style="margin-top: 1rem;">
                <i class="ph ph-plus"></i> Add Member
            </a>
        </div>
    </div>
<?php endif; ?>

<script>
function changeEvent(eventId) {
    if (eventId) {
        window.location.href = '?event_id=' + eventId;
    }
}

function toggleQR() {
    const qrPanel = document.getElementById('qrPanel');
    qrPanel.style.display = qrPanel.style.display === 'none' ? 'block' : 'none';
}

// Simple toast notification function
function showToast(message, type = 'info') {
    // Remove existing toasts
    const existing = document.querySelector('.attendance-toast');
    if (existing) existing.remove();
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = 'attendance-toast';
    
    // Colors based on type
    const colors = {
        success: '#16a34a',
        error: '#dc2626',
        warning: '#f59e0b',
        info: '#3b82f6'
    };
    const color = colors[type] || colors.info;
    
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${color};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 200px;
        animation: slideIn 0.3s ease;
    `;
    
    // Add icon based on type
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    toast.innerHTML = `<span>${icons[type] || 'ℹ'}</span> ${message}`;
    
    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    if (!document.querySelector('#toast-styles')) {
        style.id = 'toast-styles';
        document.head.appendChild(style);
    }
    
    document.body.appendChild(toast);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function quickMark(attendeeId, status) {
    console.log(`quickMark called - attendeeId: ${attendeeId}, status: ${status}`);
    
    const eventId = document.getElementById('eventId')?.value;
    console.log('eventId:', eventId);
    
    if (!eventId) {
        showToast('Please select an event first', 'warning');
        return;
    }
    
    // Find and disable all buttons for this attendee (both desktop and mobile)
    const selector = `[id^="btn-absent-${attendeeId}"], [id^="btn-present-${attendeeId}"], [id^="btn-absent-card-${attendeeId}"], [id^="btn-present-card-${attendeeId}"]`;
    console.log('Button selector:', selector);
    
    const buttons = document.querySelectorAll(selector);
    console.log(`Found ${buttons.length} buttons to disable:`, buttons);
    
    buttons.forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.6';
        btn.style.cursor = 'not-allowed';
    });
    
    const body = `event_id=${eventId}&attendee_id=${attendeeId}&status=${status}&method=Manual`;
    console.log('Request body:', body);
    
    fetch('/afb_mangaan_php/api/record_attendance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('API Response:', data);
        if (data.success) {
            showToast(`Marked ${status}`, 'success');
            console.log('Calling updateRowStatus with:', attendeeId, status, data.time_formatted);
            updateRowStatus(attendeeId, status, data.time_formatted);
        } else {
            showToast(data.message || 'Failed to record attendance', 'error');
            // Re-enable buttons on error
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '';
                btn.style.cursor = '';
            });
        }
    })
    .catch(error => {
        console.error('Attendance error:', error);
        showToast('An error occurred. Please try again.', 'error');
        // Re-enable buttons on error
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '';
            btn.style.cursor = '';
        });
    });
}

function updateRowStatus(attendeeId, status, timeFormatted) {
    console.log('updateRowStatus called:', { attendeeId, status, timeFormatted });
    
    const row = document.getElementById('member-row-' + attendeeId);
    console.log('Found row:', row);
    
    if (!row) {
        console.error('Row not found for attendee:', attendeeId);
    } else {
        // Update row background
        if (status === 'Present') {
            row.style.background = 'rgba(34, 197, 94, 0.05)';
        } else {
            row.style.background = 'rgba(239, 68, 68, 0.05)';
        }
        
        // Find action cell - try multiple selectors for robustness
        let actionCell = row.querySelector('td[data-label="Actions"] > div');
        if (!actionCell) {
            const tds = row.querySelectorAll('td');
            if (tds.length >= 3) {
                actionCell = tds[tds.length - 1].querySelector('div');
            }
        }
        console.log('Found actionCell:', actionCell);
        
        if (actionCell) {
            try {
                if (status === 'Present') {
                    const timeHtml = timeFormatted ? `<small style="font-size: 0.65rem; opacity: 0.9;">${timeFormatted}</small>` : '';
                    actionCell.innerHTML = `
                        <button class="btn btn-sm" onclick="quickMark(${attendeeId}, 'Absent')" id="btn-absent-${attendeeId}" style="background: #dc2626; color: white; border: none; padding: 0.4rem 0.75rem; font-size: 0.75rem; display: flex; align-items: center; gap: 0.25rem;">
                            <i class="ph ph-x-circle"></i> Absent
                        </button>
                        <span class="status-badge status-present" style="padding: 0.4rem 0.75rem; font-size: 0.75rem; display: flex; align-items: center; gap: 0.25rem; flex-direction: column; line-height: 1.2;">
                            <span style="display: flex; align-items: center; gap: 0.25rem;"><i class="ph ph-check-circle"></i> Present</span>
                            ${timeHtml}
                        </span>
                    `;
                } else {
                    actionCell.innerHTML = `
                        <span class="status-badge status-absent" style="padding: 0.4rem 0.75rem; font-size: 0.75rem; display: flex; align-items: center; gap: 0.25rem;">
                            <i class="ph ph-x-circle"></i> Absent
                        </span>
                        <button class="btn btn-sm" onclick="quickMark(${attendeeId}, 'Present')" id="btn-present-${attendeeId}" style="background: #16a34a; color: white; border: none; padding: 0.4rem 0.75rem; font-size: 0.75rem; display: flex; align-items: center; gap: 0.25rem;">
                            <i class="ph ph-clock-in"></i> Time In
                        </button>
                    `;
                }
                console.log('Desktop action cell updated successfully');
            } catch (e) {
                console.error('Error updating action cell:', e);
            }
        } else {
            console.error('Action cell not found in row');
        }
    }
    
    // Update mobile card view
    const card = document.getElementById('member-card-' + attendeeId);
    console.log('Found mobile card:', card);
    
    if (card) {
        try {
            const cardActions = card.querySelector('.member-grid-actions');
            if (cardActions) {
                if (status === 'Present') {
                    const timeHtml = timeFormatted ? `<small style="font-size: 0.7rem; opacity: 0.9;">${timeFormatted}</small>` : '';
                    cardActions.innerHTML = `
                        <button class="btn btn-absent" onclick="quickMark(${attendeeId}, 'Absent')" id="btn-absent-card-${attendeeId}">
                            <i class="ph ph-x-circle"></i> Absent
                        </button>
                        <span class="btn btn-present" style="opacity: 1; cursor: default; flex-direction: column; line-height: 1.2;">
                            <span><i class="ph ph-check-circle"></i> Present</span>
                            ${timeHtml}
                        </span>
                    `;
                    card.style.borderLeft = '4px solid #16a34a';
                } else {
                    cardActions.innerHTML = `
                        <span class="btn btn-absent" style="opacity: 1; cursor: default;">
                            <i class="ph ph-x-circle"></i> Absent
                        </span>
                        <button class="btn btn-present" onclick="quickMark(${attendeeId}, 'Present')" id="btn-present-card-${attendeeId}">
                            <i class="ph ph-clock-in"></i> Time In
                        </button>
                    `;
                    card.style.borderLeft = '4px solid #dc2626';
                }
                console.log('Mobile card updated successfully');
            }
            
            // Update or add status badge in card header
            const cardInfo = card.querySelector('.member-grid-info');
            if (cardInfo) {
                let statusBadge = cardInfo.querySelector('.status-badge');
                if (!statusBadge) {
                    statusBadge = document.createElement('span');
                    statusBadge.className = 'status-badge';
                    cardInfo.appendChild(statusBadge);
                }
                const timeHtml = (status === 'Present' && timeFormatted) ? `<small style="display: block; font-size: 0.7rem; opacity: 0.9; margin-top: 1px;">${timeFormatted}</small>` : '';
                statusBadge.className = `status-badge ${status === 'Present' ? 'status-present' : 'status-absent'}`;
                statusBadge.innerHTML = `${status}${timeHtml}`;
            }
        } catch (e) {
            console.error('Error updating mobile card:', e);
        }
    }
    
    // Force a reflow to ensure changes are rendered
    document.body.offsetHeight;
    console.log('UI update complete');
}

function markAllPresent() {
    const eventId = document.getElementById('eventId')?.value;
    
    if (!eventId) {
        showToast('Please select an event first', 'warning');
        return;
    }
    
    if (!confirm('Mark all members as Present?')) return;
    
    const rows = document.querySelectorAll('#membersTable tbody tr');
    let processed = 0;
    let total = 0;
    
    rows.forEach(row => {
        const statusBadge = row.querySelector('[id^="status-"]');
        const status = statusBadge?.textContent?.trim();
        
        if (status !== 'Present') {
            total++;
            const attendeeId = row.id.replace('member-row-', '');
            
            fetch('/afb_mangaan_php/api/record_attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `event_id=${eventId}&attendee_id=${attendeeId}&status=Present&method=Manual`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateRowStatus(attendeeId, 'Present', data.time_formatted);
                    processed++;
                    if (processed === total) {
                        showToast(`Marked ${processed} members as Present`, 'success');
                    }
                }
            });
        }
    });
    
    if (total === 0) {
        showToast('All members are already marked Present', 'info');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
