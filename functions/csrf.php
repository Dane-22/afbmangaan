<?php
/**
 * CSRF Protection Functions
 * AFB Mangaan Attendance System
 */

/**
 * Generate CSRF token for forms
 */
function generateCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from POST request
 */
function verifyCsrfToken($token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check kung parehong string at may laman ang session token
    if (!is_string($token) || empty($token) || empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Timing-attack safe comparison
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token and regenerate for one-time use
 */
function generateCsrfTokenOneTime(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    
    return $token;
}

/**
 * Validate CSRF token from POST data
 * Dies with error if validation fails
 */
function requireCsrfToken(): void {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($token)) {
        http_response_code(403);
        die('CSRF validation failed. Please refresh the page and try again.');
    }
}