<?php
/**
 * API: Search Attendees
 * AFB Mangaan Attendance System
 */

require_once __DIR__ . '/../functions/attendance_logic.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query too short']);
    exit;
}

$attendees = searchAttendees($query, 10);

echo json_encode([
    'success' => true,
    'attendees' => $attendees,
    'count' => count($attendees)
]);
