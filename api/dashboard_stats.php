<?php
/**
 * API: Dashboard Statistics
 * AFB Mangaan Attendance System
 */

require_once __DIR__ . '/../functions/attendance_logic.php';
require_once __DIR__ . '/../functions/report_engine.php';

header('Content-Type: application/json');

$type = $_GET['type'] ?? 'all';

$response = ['success' => true];

switch ($type) {
    case 'trends':
        $response['trends'] = getAttendanceTrends(6);
        break;
        
    case 'categories':
        $pdo = getDB();
        $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM attendees WHERE status='Active' GROUP BY category");
        $response['categories'] = $stmt->fetchAll();
        break;
        
    case 'event_types':
        $response['event_types'] = (new class {
            public function get() {
                $pdo = getDB();
                $stmt = $pdo->query("SELECT type, COUNT(*) as event_count FROM events GROUP BY type");
                return $stmt->fetchAll();
            }
        })->get();
        break;
        
    case 'retention':
        $response = array_merge($response, getRetentionStats(3));
        break;
        
    case 'all':
    default:
        $response['trends'] = getAttendanceTrends(6);
        $pdo = getDB();
        $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM attendees WHERE status='Active' GROUP BY category");
        $response['categories'] = $stmt->fetchAll();
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM attendees WHERE status='Active'");
        $response['total_members'] = $stmt->fetch()['total'];
        $response = array_merge($response, getRetentionStats(3));
        break;
}

echo json_encode($response);
