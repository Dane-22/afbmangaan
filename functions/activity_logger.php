<?php
/**
 * Activity Logger Functions
 * AFB Mangaan Attendance System
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Log system activity
 */
function logActivity($userId, $action, $details = '') {
    $pdo = getDB();
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $ipAddress, $userAgent]);
        return true;
    } catch (PDOException $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Get system logs with pagination
 */
function getSystemLogs($filters = [], $page = 1, $perPage = 50) {
    $pdo = getDB();
    
    $sql = "SELECT sl.*, u.fullname as user_name, u.username FROM system_logs sl LEFT JOIN users u ON sl.user_id = u.id WHERE 1=1";
    $params = [];
    
    if (!empty($filters['action'])) {
        $sql .= " AND sl.action = ?";
        $params[] = $filters['action'];
    }
    
    if (!empty($filters['user_id'])) {
        $sql .= " AND sl.user_id = ?";
        $params[] = $filters['user_id'];
    }
    
    if (!empty($filters['from_date'])) {
        $sql .= " AND sl.timestamp >= ?";
        $params[] = $filters['from_date'] . ' 00:00:00';
    }
    
    if (!empty($filters['to_date'])) {
        $sql .= " AND sl.timestamp <= ?";
        $params[] = $filters['to_date'] . ' 23:59:59';
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (sl.details LIKE ? OR sl.action LIKE ?)";
        $search = "%{$filters['search']}%";
        $params[] = $search;
        $params[] = $search;
    }
    
    // Get total count
    $countSql = str_replace("sl.*, u.fullname as user_name, u.username", "COUNT(*) as total", $sql);
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Add pagination
    $offset = ($page - 1) * $perPage;
    $sql .= " ORDER BY sl.timestamp DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    return [
        'logs' => $logs,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($total / $perPage)
    ];
}

/**
 * Get distinct actions for filter dropdown
 */
function getLogActions() {
    $pdo = getDB();
    
    $stmt = $pdo->query("SELECT DISTINCT action FROM system_logs ORDER BY action ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Clear old logs (keep last N days)
 */
function clearOldLogs($daysToKeep = 90) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("DELETE FROM system_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->execute([$daysToKeep]);
    
    $deleted = $stmt->rowCount();
    
    logActivity($_SESSION['user_id'] ?? null, 'LOGS_CLEARED', "Cleared {$deleted} old log entries (keeping last {$daysToKeep} days)");
    
    return ['success' => true, 'deleted' => $deleted];
}

/**
 * Get login activity summary
 */
function getLoginSummary($days = 30) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT 
        DATE(timestamp) as date,
        SUM(CASE WHEN action = 'LOGIN' THEN 1 ELSE 0 END) as logins,
        SUM(CASE WHEN action = 'LOGOUT' THEN 1 ELSE 0 END) as logouts,
        SUM(CASE WHEN action = 'LOGIN_FAILED' THEN 1 ELSE 0 END) as failed_logins
        FROM system_logs
        WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(timestamp)
        ORDER BY date DESC");
    $stmt->execute([$days]);
    
    return $stmt->fetchAll();
}

/**
 * Get recent activity for dashboard
 */
function getRecentActivity($limit = 10) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT sl.*, u.fullname as user_name FROM system_logs sl LEFT JOIN users u ON sl.user_id = u.id ORDER BY sl.timestamp DESC LIMIT ?");
    $stmt->execute([$limit]);
    
    return $stmt->fetchAll();
}
