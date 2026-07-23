<?php
/**
 * Session Configuration
 * AFB Mangaan Attendance System
 * 
 * Centralized session security settings
 */

// Detect if using HTTPS
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           ($_SERVER['SERVER_PORT'] == 443);

// Configure session security settings
ini_set('session.cookie_secure', $isHttps ? '1' : '0'); // HTTPS only if available
ini_set('session.cookie_httponly', '1'); // No JavaScript access to session cookie
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
ini_set('session.use_strict_mode', '1'); // Reject uninitialized session IDs
ini_set('session.use_only_cookies', '1'); // Prevent session ID in URL

// Session lifetime (1 hour)
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);

// Session name (optional - helps identify session cookies)
ini_set('session.name', 'AFBMANGAAN_SESSION');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
