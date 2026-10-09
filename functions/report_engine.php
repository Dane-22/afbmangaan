<?php
/**
 * Report Engine Functions
 * AFB Mangaan Attendance System
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Get attendance report data
 */
function getAttendanceReport($eventId = null, $fromDate = null, $toDate = null, $category = null) {
    $pdo = getDB();
    
    $sql = "SELECT 
        a.id as attendee_id,
        a.fullname,
        a.category,
        a.contact,
        a.email,
        a.qr_token,
        e.id as event_id,
        e.event_name,
        e.start_date AS event_date,
        e.type as event_type,
        al.status as attendance_status,
        al.log_time,
        al.method,
        u.fullname as logged_by_name
        FROM attendees a
        CROSS JOIN events e
        LEFT JOIN attendance_logs al ON a.id = al.attendee_id AND e.id = al.event_id
        LEFT JOIN users u ON al.logged_by = u.id
        WHERE a.status = 'Active' AND e.status = 'Completed' AND a.church=? AND e.church=?";
    
    $church = $_SESSION['church'] ?? 'AFB Mangaan';
    $params = [$church, $church];
    
    if ($eventId) {
        $sql .= " AND e.id = ?";
        $params[] = $eventId;
    }
    
    if ($fromDate) {
        $sql .= " AND e.start_date >= ?";
        $params[] = $fromDate;
    }
    
    if ($toDate) {
        $sql .= " AND e.start_date <= ?";
        $params[] = $toDate;
    }
    
    if ($category) {
        $sql .= " AND a.category = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY e.start_date DESC, a.fullname ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

/**
 * Get summary statistics for reports
 */
function getReportSummary($fromDate = null, $toDate = null) {
    $pdo = getDB();
    
    $dateFilter = "WHERE e.church = ?";
    $params = [$_SESSION['church'] ?? 'AFB Mangaan'];
    
    if ($fromDate || $toDate) {
        if ($fromDate) {
            $dateFilter .= " AND e.start_date >= ?";
            $params[] = $fromDate;
        }
        if ($toDate) {
            $dateFilter .= " AND e.start_date <= ?";
            $params[] = $toDate;
        }
    }
    
    // Total events
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_events FROM events e {$dateFilter}");
    $stmt->execute($params);
    $totalEvents = $stmt->fetch()['total_events'];
    
    // Total attendance records
    $sql = "SELECT COUNT(*) as total_attendance, 
            SUM(CASE WHEN al.status = 'Present' THEN 1 ELSE 0 END) as total_present
            FROM attendance_logs al 
            JOIN events e ON al.event_id = e.id JOIN attendees a ON a.id=al.attendee_id AND a.church=e.church {$dateFilter}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $attendanceStats = $stmt->fetch();
    
    // Category breakdown
    $sql = "SELECT a.category, COUNT(*) as count 
            FROM attendance_logs al 
            JOIN attendees a ON al.attendee_id = a.id
            JOIN events e ON al.event_id = e.id 
            {$dateFilter}
            AND al.status = 'Present'
            AND a.church = e.church
            GROUP BY a.category";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll();
    
    // Event type breakdown
    $sql = "SELECT e.type, COUNT(DISTINCT e.id) as event_count, COUNT(al.id) as attendance_count
            FROM events e
            LEFT JOIN attendance_logs al ON e.id = al.event_id AND al.status = 'Present' AND EXISTS (SELECT 1 FROM attendees a WHERE a.id=al.attendee_id AND a.church=e.church)
            {$dateFilter}
            GROUP BY e.type";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $eventTypes = $stmt->fetchAll();
    
    return [
        'total_events' => $totalEvents,
        'total_attendance' => $attendanceStats['total_attendance'] ?? 0,
        'total_present' => $attendanceStats['total_present'] ?? 0,
        'categories' => $categories,
        'event_types' => $eventTypes
    ];
}

/**
 * Export data to CSV format
 */
function exportToCSV($data, $filename = 'report') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    if (!empty($data)) {
        // Headers
        fputcsv($output, array_keys($data[0]));
        
        // Data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

/**
 * Get member attendance history
 */
function getMemberAttendanceHistory($attendeeId, $limit = 50) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT 
        e.event_name,
        e.start_date AS event_date,
        e.type,
        al.status,
        al.log_time,
        al.method
        FROM attendance_logs al
        JOIN events e ON al.event_id = e.id
        JOIN attendees a ON a.id=al.attendee_id
        WHERE al.attendee_id = ? AND e.church=? AND a.church=?
        ORDER BY e.start_date DESC
        LIMIT ?");
    $church = $_SESSION['church'] ?? 'AFB Mangaan';
    $stmt->bindValue(1, (int)$attendeeId, PDO::PARAM_INT);
    $stmt->bindValue(2, $church);
    $stmt->bindValue(3, $church);
    $stmt->bindValue(4, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Get monthly attendance comparison
 */
function getMonthlyComparison($year = null) {
    $pdo = getDB();
    
    $year = $year ?: date('Y');
    
    $stmt = $pdo->prepare("SELECT 
        MONTH(e.start_date) as month,
        COUNT(DISTINCT e.id) as events,
        COUNT(CASE WHEN al.status = 'Present' THEN 1 END) as present,
        COUNT(CASE WHEN al.status = 'Absent' THEN 1 END) as absent
        FROM events e
        LEFT JOIN attendance_logs al ON e.id = al.event_id AND EXISTS (SELECT 1 FROM attendees a WHERE a.id=al.attendee_id AND a.church=e.church)
        WHERE YEAR(e.start_date) = ? AND e.church=?
        AND e.status = 'Completed'
        GROUP BY MONTH(e.start_date)
        ORDER BY month ASC");
    $stmt->execute([$year, $_SESSION['church'] ?? 'AFB Mangaan']);
    
    return $stmt->fetchAll();
}

/**
 * Get top attendees
 */
function getTopAttendees($limit = 20, $fromDate = null, $toDate = null) {
    $pdo = getDB();
    
    $sql = "SELECT 
        a.id,
        a.fullname,
        a.category,
        COUNT(CASE WHEN al.status = 'Present' THEN 1 END) as attendance_count,
        COUNT(al.id) as total_records,
        ROUND((COUNT(CASE WHEN al.status = 'Present' THEN 1 END) / COUNT(al.id)) * 100, 1) as attendance_rate
        FROM attendees a
        LEFT JOIN attendance_logs al ON a.id = al.attendee_id
        LEFT JOIN events e ON al.event_id = e.id
        WHERE a.status = 'Active' AND a.church=? AND e.church=?";
    
    $church = $_SESSION['church'] ?? 'AFB Mangaan';
    $params = [$church, $church];
    
    if ($fromDate) {
        $sql .= " AND e.start_date >= ?";
        $params[] = $fromDate;
    }
    
    if ($toDate) {
        $sql .= " AND e.start_date <= ?";
        $params[] = $toDate;
    }
    
    $sql .= " GROUP BY a.id, a.fullname, a.category
              HAVING COUNT(al.id) > 0
              ORDER BY attendance_rate DESC, attendance_count DESC
              LIMIT ?";
    $params[] = $limit;
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $index => $value) {
        $stmt->bindValue($index + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    
    return $stmt->fetchAll();
}
