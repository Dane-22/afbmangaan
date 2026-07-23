<?php
/**
 * Rate Limiter Functions
 * AFB Mangaan Attendance System
 */

require_once __DIR__ . '/../config/db.php';

function checkRateLimit($identifier, $limit = 100, $window = 3600) {
    $pdo = getDB();
    $key = md5($identifier);
    
    // Clean old entries
    $pdo->prepare("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)")->execute([$window]);
    
    // Check current count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->execute([$key, $window]);
    $count = $stmt->fetch()['count'];
    
    if ($count >= $limit) {
        return false;
    }
    
    // Log this request
    $pdo->prepare("INSERT INTO rate_limits (identifier, created_at) VALUES (?, NOW())")->execute([$key]);
    return true;
}
