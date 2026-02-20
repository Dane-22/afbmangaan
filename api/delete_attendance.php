<?php
/**
 * API: Delete Attendance
 * AFB Mangaan Attendance System
 */

session_start();
require_once __DIR__ . '/../functions/attendance_logic.php';

header('Content-Type: application/json');

$id = $_POST['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Attendance ID required']);
    exit;
}

$result = deleteAttendance($id);

echo json_encode($result);
