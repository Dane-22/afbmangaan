<?php
/**
 * Authentication Check
 * Include this at the top of all admin pages
 */

session_start();

require_once __DIR__ . '/../functions/auth_functions.php';
require_once __DIR__ . '/../functions/activity_logger.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Check session timeout (1 hour)
if (isset($_SESSION['login_time'])) {
    $timeout = 3600; // 1 hour
    if (time() - $_SESSION['login_time'] > $timeout) {
        logoutUser();
        header('Location: index.php?error=timeout');
        exit();
    }
    $_SESSION['login_time'] = time(); // Reset timer
}

// Get current user info
$currentUser = getCurrentUser();
