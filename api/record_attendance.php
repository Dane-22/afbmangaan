<?php
/**
 * API: Record Attendance
 * Records attendance to the MySQL database
 */

require_once __DIR__ . '/../functions/attendance_logic.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $event_id = $_POST['event_id'] ?? null;
    $attendee_id = $_POST['attendee_id'] ?? null;
    $status = $_POST['status'] ?? 'Present';
    $method = $_POST['method'] ?? 'Manual';
    $notes = $_POST['notes'] ?? '';

    if (!$event_id || !$attendee_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters: event_id and attendee_id']);
        exit;
    }

    $result = recordAttendance($event_id, $attendee_id, $status, $method, $notes);
    echo json_encode($result);

} catch (Exception $e) {
    error_log('Record attendance error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to record attendance']);
}
