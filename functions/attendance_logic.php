<?php
/**
 * Attendance Logic Functions
 * AFB Mangaan Attendance System
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/activity_logger.php';
require_once __DIR__ . '/make_sync.php';

/**
 * Record attendance for an attendee at an event
 */
function recordAttendance($eventId, $attendeeId, $status = 'Present', $method = 'Manual', $notes = '') {
    $pdo = getDB();
    
    try {
        // Check if already recorded
        $stmt = $pdo->prepare("SELECT id FROM attendance_logs WHERE event_id = ? AND attendee_id = ? LIMIT 1");
        $stmt->execute([$eventId, $attendeeId]);
        
        if ($stmt->fetch()) {
            // Update existing record
            $stmt = $pdo->prepare("UPDATE attendance_logs SET status = ?, method = ?, notes = ?, log_time = NOW(), logged_by = ? WHERE event_id = ? AND attendee_id = ?");
            $stmt->execute([$status, $method, $notes, $_SESSION['user_id'] ?? null, $eventId, $attendeeId]);
            
            logActivity($_SESSION['user_id'] ?? null, 'ATTENDANCE_UPDATE', "Updated attendance for event {$eventId}, attendee {$attendeeId} to {$status}");
        } else {
            // Insert new record
            $stmt = $pdo->prepare("INSERT INTO attendance_logs (event_id, attendee_id, status, method, notes, logged_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$eventId, $attendeeId, $status, $method, $notes, $_SESSION['user_id'] ?? null]);
            
            logActivity($_SESSION['user_id'] ?? null, 'ATTENDANCE_RECORD', "Recorded attendance for event {$eventId}, attendee {$attendeeId} as {$status}");
        }
        
        // Sync to Google Sheets via make.com (non-blocking)
        syncAttendanceToSheets($eventId, $attendeeId, $status, $method);
        
        return ['success' => true, 'message' => 'Attendance recorded successfully'];
        
    } catch (PDOException $e) {
        error_log("Attendance recording failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to record attendance'];
    }
}

/**
 * Search attendees by name or QR token (filtered by church)
 */
function searchAttendees($query, $limit = 10) {
    $pdo = getDB();
    $church = $_SESSION['church'] ?? 'AFB Mangaan';
    
    $search = "%{$query}%";
    
    $stmt = $pdo->prepare("SELECT id, fullname, category, contact, email, qr_token, qr_token as member_id FROM attendees WHERE church = ? AND status = 'Active' AND (fullname LIKE ? OR qr_token LIKE ? OR contact LIKE ?) ORDER BY fullname ASC LIMIT ?");
    $stmt->execute([$church, $search, $search, $search, $limit]);
    
    return $stmt->fetchAll();
}

/**
 * Get attendee by QR token (filtered by church)
 */
function getAttendeeByQR($qrToken) {
    $pdo = getDB();
    $church = $_SESSION['church'] ?? 'AFB Mangaan';
    
    $stmt = $pdo->prepare("SELECT id, fullname, category, contact, email, qr_token FROM attendees WHERE church = ? AND qr_token = ? AND status = 'Active' LIMIT 1");
    $stmt->execute([$church, $qrToken]);
    
    return $stmt->fetch();
}

/**
 * Get today's active event (filtered by church)
 */
function getTodayEvent() {
    $pdo = getDB();
    $church = $_SESSION['church'] ?? 'AFB Mangaan';
    
    $stmt = $pdo->prepare("SELECT id, event_name, start_date, event_time, type, status FROM events WHERE church = ? AND start_date = CURDATE() AND status IN ('Upcoming', 'Ongoing') ORDER BY event_time ASC LIMIT 1");
    $stmt->execute([$church]);
    
    return $stmt->fetch();
}

/**
 * Get all events with optional filters (filtered by church)
 */
function getEvents($filters = []) {
    $pdo = getDB();
    $church = $_SESSION['church'] ?? 'AFB Mangaan';
    
    $sql = "SELECT e.*, u.fullname as created_by_name FROM events e LEFT JOIN users u ON e.created_by = u.id WHERE e.church = ?";
    $params = [$church];
    
    if (!empty($filters['status'])) {
        $sql .= " AND e.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['type'])) {
        $sql .= " AND e.type = ?";
        $params[] = $filters['type'];
    }
    
    if (!empty($filters['from_date'])) {
        $sql .= " AND e.start_date >= ?";
        $params[] = $filters['from_date'];
    }
    
    if (!empty($filters['to_date'])) {
        $sql .= " AND e.start_date <= ?";
        $params[] = $filters['to_date'];
    }
    
    $sql .= " ORDER BY e.start_date DESC, e.event_time DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

/**
 * Get attendance for an event
 */
function getEventAttendance($eventId) {
    $pdo = getDB();
    $church = $_SESSION['church'] ?? 'AFB Mangaan';
    
    $stmt = $pdo->prepare("SELECT al.*, a.fullname, a.category, a.contact, a.qr_token, u.fullname as logged_by_name 
        FROM attendance_logs al 
        JOIN attendees a ON al.attendee_id = a.id 
        JOIN events e ON al.event_id = e.id
        LEFT JOIN users u ON al.logged_by = u.id 
        WHERE al.event_id = ? AND e.church = ? AND a.church = ?
        ORDER BY al.log_time DESC");
    $stmt->execute([$eventId, $church, $church]);
    
    return $stmt->fetchAll();
}

/**
 * Get attendance statistics for an event
 */
function getEventStats($eventId) {
    $pdo = getDB();
    $church = $_SESSION['church'] ?? 'AFB Mangaan';
    
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent
        FROM attendance_logs al
        JOIN attendees a ON al.attendee_id = a.id
        JOIN events e ON al.event_id = e.id
        WHERE al.event_id = ? AND e.church = ? AND a.church = ?");
    $stmt->execute([$eventId, $church, $church]);
    $attendance = $stmt->fetch();
    
    // Get category breakdown
    $stmt = $pdo->prepare("SELECT a.category, COUNT(*) as count 
        FROM attendance_logs al 
        JOIN attendees a ON al.attendee_id = a.id 
        JOIN events e ON al.event_id = e.id
        WHERE al.event_id = ? AND al.status = 'Present' AND e.church = ? AND a.church = ?
        GROUP BY a.category");
    $stmt->execute([$eventId, $church, $church]);
    $categories = $stmt->fetchAll();
    
    return [
        'total' => $attendance['total'] ?? 0,
        'present' => $attendance['present'] ?? 0,
        'absent' => $attendance['absent'] ?? 0,
        'categories' => $categories
    ];
}

/**
 * Get member retention statistics (filtered by church)
 */
function getRetentionStats($months = 3) {
    $pdo = getDB();
    $church = $_SESSION['church'] ?? 'AFB Mangaan';
    
    // Get consistent attendees (attended at least 70% of events in last N months)
    $stmt = $pdo->prepare("SELECT 
        a.id, 
        a.fullname,
        a.category,
        COUNT(DISTINCT e.id) as total_events,
        COUNT(DISTINCT CASE WHEN al.status = 'Present' THEN al.event_id END) as attended_events
        FROM attendees a
        CROSS JOIN events e
        LEFT JOIN attendance_logs al ON a.id = al.attendee_id AND e.id = al.event_id
        WHERE e.start_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
        AND e.status = 'Completed'
        AND a.status = 'Active'
        AND a.church = ?
        AND e.church = ?
        GROUP BY a.id, a.fullname, a.category");
    $stmt->execute([$months, $church, $church]);
    $allMembers = $stmt->fetchAll();
    
    $consistent = [];
    $atRisk = [];
    
    foreach ($allMembers as $member) {
        $rate = $member['total_events'] > 0 ? ($member['attended_events'] / $member['total_events']) : 0;
        $member['attendance_rate'] = round($rate * 100, 1);
        
        if ($rate >= 0.7) {
            $consistent[] = $member;
        } elseif ($rate <= 0.3) {
            $atRisk[] = $member;
        }
    }
    
    return [
        'consistent' => $consistent,
        'at_risk' => $atRisk,
        'consistent_count' => count($consistent),
        'at_risk_count' => count($atRisk)
    ];
}

/**
 * Get attendance trends (filtered by church)
 */
function getAttendanceTrends($months = 6) {
    $pdo = getDB();
    $church = $_SESSION['church'] ?? 'AFB Mangaan';
    
    $stmt = $pdo->prepare("SELECT 
        DATE_FORMAT(e.start_date, '%Y-%m') as month,
        COUNT(DISTINCT e.id) as events,
        COUNT(CASE WHEN al.status = 'Present' THEN 1 END) as attendance
        FROM events e
        LEFT JOIN attendance_logs al ON e.id = al.event_id
        WHERE e.start_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
        AND e.status = 'Completed'
        AND e.church = ?
        GROUP BY DATE_FORMAT(e.start_date, '%Y-%m')
        ORDER BY month ASC");
    $stmt->execute([$months, $church]);
    
    return $stmt->fetchAll();
}

/**
 * Delete attendance record
 */
function deleteAttendance($attendanceId) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("DELETE FROM attendance_logs WHERE id = ?");
    $stmt->execute([$attendanceId]);
    
    logActivity($_SESSION['user_id'] ?? null, 'ATTENDANCE_DELETE', "Deleted attendance record ID: {$attendanceId}");
    
    return ['success' => true];
}

/**
 * Generate QR token for attendee
 */
function generateQRToken($attendeeId) {
    $prefix = getenv('QR_PREFIX') ?: 'AFB';
    $token = $prefix . str_pad($attendeeId, 6, '0', STR_PAD_LEFT);
    return $token;
}
