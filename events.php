<?php
/**
 * Events Page - AFB Mangaan Attendance System
 */

$pageTitle = 'Events';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/functions/attendance_logic.php';

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo = getDB();
    
    if ($action === 'create' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $eventName = $_POST['event_name'] ?? '';
        $eventDate = $_POST['event_date'] ?? '';
        $eventTime = $_POST['event_time'] ?? '';
        $location = $_POST['location'] ?? '';
        $type = $_POST['type'] ?? 'Sunday Service';
        $description = $_POST['description'] ?? '';
        
        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE events SET event_name=?, event_date=?, event_time=?, location=?, type=?, description=? WHERE id=?");
                $stmt->execute([$eventName, $eventDate, $eventTime, $location, $type, $description, $id]);
                $message = 'Event updated successfully';
                logActivity($_SESSION['user_id'], 'EVENT_UPDATE', "Updated event: {$eventName}");
            } else {
                $stmt = $pdo->prepare("INSERT INTO events (event_name, event_date, event_time, location, type, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$eventName, $eventDate, $eventTime, $location, $type, $description, $_SESSION['user_id']]);
                $message = 'Event created successfully';
                logActivity($_SESSION['user_id'], 'EVENT_CREATE', "Created event: {$eventName}");
            }
        } catch (PDOException $e) {
            $error = 'Error saving event: ' . $e->getMessage();
        }
    } elseif ($action === 'status' && isset($_POST['id'], $_POST['status'])) {
        try {
            $stmt = $pdo->prepare("UPDATE events SET status=? WHERE id=?");
            $stmt->execute([$_POST['status'], $_POST['id']]);
            $message = 'Event status updated';
            logActivity($_SESSION['user_id'], 'EVENT_STATUS', "Updated event {$_POST['id']} status to {$_POST['status']}");
        } catch (PDOException $e) {
            $error = 'Error updating status';
        }
    }
}

// Get events
$filters = [];
if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
if (!empty($_GET['type'])) $filters['type'] = $_GET['type'];
$events = getEvents($filters);

$eventTypes = ['Sunday Service', 'Midweek Service', 'Special Event', 'Meeting', 'Other'];
$statuses = ['Upcoming', 'Ongoing', 'Completed', 'Cancelled'];

// Edit mode
$editMode = false;
$editEvent = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    foreach ($events as $e) {
        if ($e['id'] == $_GET['edit']) {
            $editMode = true;
            $editEvent = $e;
            break;
        }
    }
}
$createMode = isset($_GET['action']) && $_GET['action'] === 'create';
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

<!-- Filters -->
<div class="card animate__animated animate__fadeIn">
    <div class="card-body">
        <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control form-select">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo ($_GET['status'] ?? '') === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Type</label>
                <select name="type" class="form-control form-select">
                    <option value="">All Types</option>
                    <?php foreach ($eventTypes as $t): ?>
                        <option value="<?php echo $t; ?>" <?php echo ($_GET['type'] ?? '') === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="ph ph-funnel"></i> Filter
            </button>
            
            <a href="?action=create" class="btn btn-success">
                <i class="ph ph-plus"></i> Create Event
            </a>
        </form>
    </div>
</div>

<?php if ($createMode || $editMode): ?>
    <div class="card animate__animated animate__fadeIn" style="margin-top: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="ph ph-calendar<?php echo $editMode ? '' : '-plus'; ?>"></i>
                <?php echo $editMode ? 'Edit Event' : 'Create New Event'; ?>
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo $editMode ? 'edit' : 'create'; ?>">
                <?php if ($editMode): ?>
                    <input type="hidden" name="id" value="<?php echo $editEvent['id']; ?>">
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Event Name *</label>
                        <input type="text" name="event_name" class="form-control" required
                               value="<?php echo $editMode ? htmlspecialchars($editEvent['event_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date *</label>
                        <input type="date" name="event_date" class="form-control" required
                               value="<?php echo $editMode ? $editEvent['event_date'] : date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Time</label>
                        <input type="time" name="event_time" class="form-control"
                               value="<?php echo $editMode ? $editEvent['event_time'] : '09:00'; ?>">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="e.g., Main Sanctuary"
                               value="<?php echo $editMode ? htmlspecialchars($editEvent['location']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Event Type *</label>
                        <select name="type" class="form-control form-select" required>
                            <?php foreach ($eventTypes as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo ($editMode && $editEvent['type'] === $t) ? 'selected' : ''; ?>><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 1rem;">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo $editMode ? htmlspecialchars($editEvent['description']) : ''; ?></textarea>
                </div>
                
                <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-floppy-disk"></i> Save Event
                    </button>
                    <a href="events.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Events List -->
<div class="card animate__animated animate__fadeInUp" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ph ph-calendar"></i>
            Events
            <span style="font-size: 0.875rem; font-weight: normal; color: var(--text-muted);">
                (<?php echo count($events); ?> found)
            </span>
        </h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date & Time</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <?php 
                        $statusClass = match($event['status']) {
                            'Upcoming' => 'info',
                            'Ongoing' => 'success',
                            'Completed' => 'secondary',
                            'Cancelled' => 'danger',
                            default => 'secondary'
                        };
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($event['event_name']); ?></strong>
                                <?php if ($event['description']): ?>
                                    <br><small><?php echo htmlspecialchars(substr($event['description'], 0, 50)) . '...'; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                                <?php if ($event['event_time']): ?>
                                    <br><small><?php echo date('g:i A', strtotime($event['event_time'])); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($event['location'] ?? '-'); ?></td>
                            <td><?php echo $event['type']; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $statusClass; ?>">
                                    <?php echo $event['status']; ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="attendance.php?event_id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary" title="Take Attendance">
                                        <i class="ph ph-check-circle"></i>
                                    </a>
                                    <a href="?edit=<?php echo $event['id']; ?>" class="btn btn-sm btn-secondary" title="Edit">
                                        <i class="ph ph-pencil"></i>
                                    </a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="form-control form-select" style="width: auto; font-size: 0.75rem; padding: 0.25rem 1.5rem 0.25rem 0.5rem;">
                                            <?php foreach ($statuses as $s): ?>
                                                <option value="<?php echo $s; ?>" <?php echo $event['status'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
