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
    
    // Get the log_time for the recorded attendance
    if ($result['success']) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT log_time FROM attendance_logs WHERE event_id = ? AND attendee_id = ? ORDER BY log_time DESC LIMIT 1");
        $stmt->execute([$event_id, $attendee_id]);
        $record = $stmt->fetch();
        if ($record) {
            $result['log_time'] = $record['log_time'];
            $result['time_formatted'] = date('h:i A', strtotime($record['log_time']));
        }
    }
    
    echo json_encode($result);

} catch (Exception $e) {
    error_log('Record attendance error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to record attendance']);
}
