<?php
/**
 * Logout Handler - AFB Mangaan Attendance System
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/functions/auth_functions.php';

// Prevent browser caching of authenticated state
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

logoutUser();

header('Location: index.php');
exit();
