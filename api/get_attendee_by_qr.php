<?php
/**
 * API: Get Attendee by QR Token
 * AFB Mangaan Attendance System
 */

require_once __DIR__ . '/../functions/attendance_logic.php';

header('Content-Type: application/json');

$token = $_GET['token'] ?? '';

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'QR token required']);
    exit;
}

$attendee = getAttendeeByQR($token);

if ($attendee) {
    echo json_encode(['success' => true, 'attendee' => $attendee]);
} else {
    echo json_encode(['success' => false, 'message' => 'Attendee not found']);
}
