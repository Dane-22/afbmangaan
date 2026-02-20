<?php
/**
 * API: Export Attendance
 * AFB Mangaan Attendance System
 */

session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../functions/attendance_logic.php';

$eventId = $_GET['event_id'] ?? null;
$format = $_GET['format'] ?? 'csv';

if (!$eventId) {
    die('Event ID required');
}

$attendance = getEventAttendance($eventId);

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_' . $eventId . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    fputcsv($output, ['Member Name', 'Category', 'Contact', 'QR Token', 'Status', 'Method', 'Log Time', 'Logged By']);
    
    // Data
    foreach ($attendance as $row) {
        fputcsv($output, [
            $row['fullname'],
            $row['category'],
            $row['contact'],
            $row['qr_token'],
            $row['status'],
            $row['method'],
            $row['log_time'],
            $row['logged_by_name'] ?? 'System'
        ]);
    }
    
    fclose($output);
    exit;
}

// Default JSON response
header('Content-Type: application/json');
echo json_encode(['success' => true, 'attendance' => $attendance]);
