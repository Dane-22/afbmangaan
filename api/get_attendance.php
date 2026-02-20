<?php
/**
 * API: Get Attendance
 * AFB Mangaan Attendance System
 */

require_once __DIR__ . '/../functions/attendance_logic.php';

header('Content-Type: application/json');

$eventId = $_GET['event_id'] ?? null;

if (!$eventId) {
    echo json_encode(['success' => false, 'message' => 'Event ID required']);
    exit;
}

$attendance = getEventAttendance($eventId);

// Get stats
$stats = getEventStats($eventId);

echo json_encode([
    'success' => true,
    'attendance' => $attendance,
    'stats' => $stats
]);
