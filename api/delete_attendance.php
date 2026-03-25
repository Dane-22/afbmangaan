<?php
/**
 * API: Delete Attendance Record
 * Deletes attendance record from the MySQL database
 */

require_once __DIR__ . '/../functions/attendance_logic.php';

header('Content-Type: application/json');

// Handle DELETE method or POST with _method=DELETE
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && ($_POST['_method'] ?? '') === 'DELETE') {
    $method = 'DELETE';
}

if ($method !== 'DELETE' && $method !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $id = $_GET['id'] ?? $_POST['id'] ?? null;

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Attendance ID required']);
        exit;
    }

    $result = deleteAttendance($id);
    echo json_encode($result);

} catch (Exception $e) {
    error_log('Delete attendance error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete attendance']);
}
