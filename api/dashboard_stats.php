<?php
/**
 * API: Dashboard Statistics
 * AFB Mangaan Attendance System
 */

require_once __DIR__ . '/../functions/attendance_logic.php';
require_once __DIR__ . '/../functions/report_engine.php';
require_once __DIR__ . '/../functions/cache.php';
require_once __DIR__ . '/../functions/jwt_auth.php';
require_once __DIR__ . '/../functions/auth_functions.php';
require_once __DIR__ . '/../functions/api_identity.php';

header('Content-Type: application/json');

// Support both JWT (for external API) and Session (for dashboard)
$identity = apiIdentity();
$userId = $identity['id'];

$type = $_GET['type'] ?? 'all';
$church = getCurrentChurch();
$cacheKey = "dashboard_stats_{$type}_{$church}";

// Try to get from cache (5 minutes TTL)
$cachedData = cacheGet($cacheKey);
if ($cachedData !== null) {
    echo json_encode($cachedData);
    exit;
}

$response = ['success' => true];
$pdo = getDB();

switch ($type) {
    case 'trends':
        $response['trends'] = getAttendanceTrends(6);
        break;
        
    case 'categories':
        $stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM attendees WHERE status='Active' AND church = ? GROUP BY category");
        $stmt->execute([$church]);
        $response['categories'] = $stmt->fetchAll();
        break;
        
    case 'event_types':
        $stmt = $pdo->prepare("SELECT type, COUNT(*) as event_count FROM events WHERE church = ? GROUP BY type");
        $stmt->execute([$church]);
        $response['event_types'] = $stmt->fetchAll();
        break;
        
    case 'retention':
        $response = array_merge($response, getRetentionStats(3));
        break;
        
    case 'all':
    default:
        $response['trends'] = getAttendanceTrends(6);
        
        $stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM attendees WHERE status='Active' AND church = ? GROUP BY category");
        $stmt->execute([$church]);
        $response['categories'] = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendees WHERE status='Active' AND church = ?");
        $stmt->execute([$church]);
        $response['total_members'] = $stmt->fetch()['total'];
        
        $response = array_merge($response, getRetentionStats(3));
        break;
}

// Set cache for 5 minutes (300 seconds)
cacheSet($cacheKey, $response, 300);

echo json_encode($response);
