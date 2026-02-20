<?php
/**
 * API: Record Attendance
 * AFB Mangaan Attendance System
 */

session_start();
require_once __DIR__ . '/../functions/attendance_logic.php';

header('Content-Type: application/json');

$eventId = $_POST['event_id'] ?? null;
$attendeeId = $_POST['attendee_id'] ?? null;
$status = $_POST['status'] ?? 'Present';
$method = $_POST['method'] ?? 'Manual';

if (!$eventId || !$attendeeId) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$result = recordAttendance($eventId, $attendeeId, $status, $method);

echo json_encode($result);
