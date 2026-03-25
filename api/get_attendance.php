<?php
/**
 * API: Get Attendance Records
 * Gets attendance records from the MySQL database
 */

require_once __DIR__ . '/../functions/attendance_logic.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $event_id = $_GET['event_id'] ?? null;

    if (!$event_id) {
        echo json_encode(['success' => false, 'message' => 'Event ID required']);
        exit;
    }

    $attendance = getEventAttendance($event_id);
    $stats = getEventStats($event_id);

    echo json_encode([
        'success' => true,
        'attendance' => $attendance,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    error_log('Get attendance error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to get attendance']);
}
