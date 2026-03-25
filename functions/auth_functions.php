<?php
/**
 * Authentication Functions
 * AFB Mangaan Attendance System
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/activity_logger.php';

/**
 * Login user and create session
 */
function loginUser($username, $password, $church = 'AFB Mangaan') {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT id, username, fullname, role, status, password, church FROM users WHERE username = ? AND church = ? AND status = 'Active' LIMIT 1");
    $stmt->execute([$username, $church]);
    $user = $stmt->fetch();
    
    if ($user && md5($password) === $user['password']) {
        // Create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['church'] = $user['church'];
        $_SESSION['login_time'] = time();
        
        // Log the login
        logActivity($user['id'], 'LOGIN', "User {$user['username']} logged in successfully ({$user['church']})");
        
        return ['success' => true, 'user' => $user];
    }
    
    return ['success' => false, 'message' => 'Invalid username, password, or church'];
}

/**
 * Logout user and destroy session
 */
function logoutUser() {
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'LOGOUT', "User {$_SESSION['username']} logged out");
    }
    
    session_destroy();
    $_SESSION = [];
    
    return ['success' => true];
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has required role
 */
function hasRole($requiredRoles) {
    if (!isLoggedIn()) return false;
    
    if (!is_array($requiredRoles)) {
        $requiredRoles = [$requiredRoles];
    }
    
    return in_array($_SESSION['role'], $requiredRoles);
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'fullname' => $_SESSION['fullname'],
        'role' => $_SESSION['role'],
        'church' => $_SESSION['church'] ?? 'AFB Mangaan'
    ];
}

/**
 * Get current church
 */
function getCurrentChurch() {
    return $_SESSION['church'] ?? 'AFB Mangaan';
}

/**
 * Update user password
 */
function updatePassword($userId, $currentPassword, $newPassword) {
    $pdo = getDB();
    
    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || md5($currentPassword) !== $user['password']) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    // Update password
    $newHash = md5($newPassword);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$newHash, $userId]);
    
    logActivity($userId, 'PASSWORD_CHANGE', "User changed their password");
    
    return ['success' => true, 'message' => 'Password updated successfully'];
}

/**
 * Create new user (admin only)
 */
function createUser($username, $password, $fullname, $role = 'operator') {
    $pdo = getDB();
    
    // Check if username exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    
    $hashedPassword = md5($password);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $hashedPassword, $fullname, $role]);
    
    $newUserId = $pdo->lastInsertId();
    
    logActivity($_SESSION['user_id'] ?? null, 'USER_CREATED', "Created user: {$username} (ID: {$newUserId})");
    
    return ['success' => true, 'user_id' => $newUserId];
}

/**
 * Update user status
 */
function updateUserStatus($userId, $status) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$status, $userId]);
    
    logActivity($_SESSION['user_id'] ?? null, 'USER_STATUS_UPDATE', "Updated user ID {$userId} status to {$status}");
    
    return ['success' => true];
}

/**
 * Get all users
 */
function getAllUsers($status = null) {
    $pdo = getDB();
    
    $sql = "SELECT id, username, fullname, role, status, created_at FROM users";
    $params = [];
    
    if ($status) {
        $sql .= " WHERE status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

/**
 * Redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

/**
 * Redirect if not authorized
 */
function requireRole($roles) {
    requireLogin();
    
    if (!hasRole($roles)) {
        header('Location: dashboard.php?error=unauthorized');
        exit();
    }
}
