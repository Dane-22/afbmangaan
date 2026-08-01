<?php
/**
 * Event Stations Page - AFB Mangaan Attendance System
 */

$pageTitle = 'Event Stations';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/functions/attendance_logic.php';
require_once __DIR__ . '/functions/csrf.php';

$pdo = getDB();
$message = '';
$error = '';
$church = $_SESSION['church'] ?? 'AFB Mangaan';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_station') {
            $eventId = $_POST['event_id'] ?? null;
            $stationName = trim($_POST['station_name'] ?? '');
            
            try {
                $stmt = $pdo->prepare("INSERT INTO event_stations (event_id, station_name) VALUES (?, ?)");
                $stmt->execute([$eventId, $stationName]);
                $message = 'Station created successfully';
            } catch (PDOException $e) {
                $error = 'Error creating station: ' . $e->getMessage();
            }
        } elseif ($action === 'delete_station' && !empty($_POST['station_id'])) {
            try {
                $stmt = $pdo->prepare("DELETE FROM event_stations WHERE id=? AND event_id IN (SELECT id FROM events WHERE church=?)");
                $stmt->execute([$_POST['station_id'], $church]);
                $message = 'Station removed successfully';
            } catch (PDOException $e) {
                $error = 'Error deleting station';
            }
        } elseif ($action === 'assign_member') {
            $stationId = $_POST['station_id'] ?? null;
            $memberId = $_POST['member_id'] ?? null;
            
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO event_station_assignments (station_id, member_id) VALUES (?, ?)");
                $stmt->execute([$stationId, $memberId]);
                $message = 'Member assigned successfully';
            } catch (PDOException $e) {
                $error = 'Error assigning member';
            }
        } elseif ($action === 'unassign_member' && !empty($_POST['assignment_id'])) {
            try {
                // Ensure station belongs to an event from this church
                $stmt = $pdo->prepare("DELETE FROM event_station_assignments WHERE id=? AND station_id IN (SELECT id FROM event_stations WHERE event_id IN (SELECT id FROM events WHERE church=?))");
                $stmt->execute([$_POST['assignment_id'], $church]);
                $message = 'Member unassigned successfully';
            } catch (PDOException $e) {
                $error = 'Error unassigning member';
            }
        }
    }
}

// Get upcoming and ongoing events
$eventsStmt = $pdo->prepare("SELECT id, event_name, start_date, status FROM events WHERE church = ? AND status IN ('Upcoming', 'Ongoing') ORDER BY start_date ASC");
$eventsStmt->execute([$church]);
$events = $eventsStmt->fetchAll();

$selectedEventId = $_GET['event_id'] ?? ($events[0]['id'] ?? null);

// Fetch members for assignment dropdown
$members = [];
if ($selectedEventId) {
    $memStmt = $pdo->prepare("SELECT id, fullname, category, ministry FROM attendees WHERE church = ? AND status = 'Active' ORDER BY fullname ASC");
    $memStmt->execute([$church]);
    $members = $memStmt->fetchAll();
}

// Fetch stations and their assignments
$stations = [];
if ($selectedEventId) {
    $statStmt = $pdo->prepare("SELECT * FROM event_stations WHERE event_id = ? ORDER BY id ASC");
    $statStmt->execute([$selectedEventId]);
    $stations = $statStmt->fetchAll();
    
    // Fetch assignments for each station
    foreach ($stations as &$station) {
        $assStmt = $pdo->prepare("
            SELECT a.id as assignment_id, m.fullname, m.category, m.ministry 
            FROM event_station_assignments a
            JOIN attendees m ON a.member_id = m.id
            WHERE a.station_id = ?
        ");
        $assStmt->execute([$station['id']]);
        $station['assignments'] = $assStmt->fetchAll();
    }
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<?php if ($message): ?>
    <div class="alert badge-success" style="margin-bottom: 1rem; padding: 1rem;">
        <i class="ph ph-check-circle"></i> <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert badge-danger" style="margin-bottom: 1rem; padding: 1rem;">
        <i class="ph ph-warning-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="card animate__animated animate__fadeIn">
    <div class="card-header">
        <h3 class="card-title">Select Event</h3>
    </div>
    <div class="card-body">
        <form method="GET" style="display: flex; gap: 1rem; align-items: flex-end;">
            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                <label class="form-label">Event</label>
                <select name="event_id" class="form-control form-select" onchange="this.form.submit()">
                    <?php if (empty($events)): ?>
                        <option value="">No upcoming events</option>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                            <option value="<?php echo $event['id']; ?>" <?php echo $selectedEventId == $event['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($event['event_name']) . ' (' . date('M d, Y', strtotime($event['start_date'])) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($selectedEventId): ?>
    <div class="card animate__animated animate__fadeInUp" style="margin-top: 1.5rem;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title"><i class="ph ph-users-three"></i> Event Stations</h3>
            <button type="button" class="btn btn-success" onclick="openStationModal()">
                <i class="ph ph-plus"></i> Add Station
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($stations)): ?>
                <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 12px; border: 1px dashed var(--border-primary);">
                    <i class="ph ph-users-three" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem; display: block;"></i>
                    <h4 style="margin: 0 0 0.5rem 0;">No Stations Yet</h4>
                    <p style="color: var(--text-muted); margin-top: 0;">Create stations for this event to start assigning members.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($stations as $station): ?>
                        <div class="station-card animate__animated animate__fadeIn" style="border: 1px solid var(--border-primary); border-radius: 12px; overflow: hidden; background: var(--bg-card); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); transition: transform 0.2s ease, box-shadow 0.2s ease;">
                            <div style="background: linear-gradient(to right, var(--bg-secondary), var(--bg-card)); padding: 1.25rem; border-bottom: 1px solid var(--border-primary); display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="background: var(--primary); color: white; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ph ph-map-pin"></i>
                                    </div>
                                    <h4 style="margin: 0; font-size: 1.1rem; font-weight: 600;"><?php echo htmlspecialchars($station['station_name']); ?></h4>
                                </div>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this station?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="delete_station">
                                    <input type="hidden" name="station_id" value="<?php echo $station['id']; ?>">
                                    <button type="submit" class="btn btn-sm" style="background: transparent; color: var(--danger); padding: 0.25rem;" title="Delete Station"><i class="ph ph-trash" style="font-size: 1.2rem;"></i></button>
                                </form>
                            </div>
                            
                            <div style="padding: 1.25rem;">
                                <!-- Assignments List -->
                                <?php if (!empty($station['assignments'])): ?>
                                    <ul style="list-style: none; padding: 0; margin: 0 0 1.25rem 0; display: flex; flex-direction: column; gap: 0.5rem;">
                                        <?php foreach ($station['assignments'] as $assignment): ?>
                                            <li style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--bg-secondary); border-radius: 8px; transition: background 0.2s ease;">
                                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--border-primary); display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                                                        <i class="ph ph-user"></i>
                                                    </div>
                                                    <div>
                                                        <strong style="display: block; font-size: 0.95rem;"><?php echo htmlspecialchars($assignment['fullname']); ?></strong>
                                                        <small style="color: var(--text-muted); font-size: 0.8rem;"><?php echo htmlspecialchars($assignment['category'] . ($assignment['ministry'] ? ' • ' . $assignment['ministry'] : '')); ?></small>
                                                    </div>
                                                </div>
                                                <form method="POST" style="margin: 0;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                    <input type="hidden" name="action" value="unassign_member">
                                                    <input type="hidden" name="assignment_id" value="<?php echo $assignment['assignment_id']; ?>">
                                                    <button type="submit" class="btn btn-sm" style="background: transparent; padding: 0.2rem; color: var(--danger); opacity: 0.7;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'" title="Unassign">
                                                        <i class="ph ph-x-circle" style="font-size: 1.25rem;"></i>
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 1.5rem 0; color: var(--text-muted);">
                                        <i class="ph ph-user-minus" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                                        <p style="font-size: 0.875rem; margin: 0;">No members assigned</p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Assign Form -->
                                <form method="POST" style="display: flex; gap: 0.5rem;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="assign_member">
                                    <input type="hidden" name="station_id" value="<?php echo $station['id']; ?>">
                                    
                                    <select name="member_id" class="form-control form-select" required style="flex: 1; font-size: 0.875rem;">
                                        <option value="">Select Member...</option>
                                        <?php foreach ($members as $m): ?>
                                            <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['fullname']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary" style="padding: 0 0.75rem;">
                                        <i class="ph ph-plus"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Station Modal -->
<div id="stationModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <span class="close-modal" onclick="closeStationModal()">&times;</span>
        <h2 style="margin-top: 0;">Add Station</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="add_station">
            <input type="hidden" name="event_id" value="<?php echo $selectedEventId; ?>">
            
            <div class="form-group">
                <label class="form-label">Station Name *</label>
                <input type="text" name="station_name" class="form-control" placeholder="e.g., Entrance A, Camera 1, Registration..." required>
            </div>
            
            <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" class="btn btn-secondary" onclick="closeStationModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Station</button>
            </div>
        </form>
    </div>
</div>

<style>
.station-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
}
</style>

<script>
function openStationModal() {
    document.getElementById('stationModal').style.display = 'block';
}

function closeStationModal() {
    document.getElementById('stationModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('stationModal')) {
        closeStationModal();
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
