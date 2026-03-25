<?php
/**
 * Logout Handler - AFB Mangaan Attendance System
 */

session_start();
require_once __DIR__ . '/functions/auth_functions.php';

logoutUser();

header('Location: index.php');
exit();
