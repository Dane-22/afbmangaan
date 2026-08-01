<?php
require_once __DIR__ . '/config/session.php';
$_SESSION['user_id'] = 1; // Assuming 1 is admin
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['church'] = 'AFB Mangaan';
$_SESSION['login_time'] = time();
echo "Logged in as Admin. Go to <a href='event_lineup.php'>Event Lineup</a> or <a href='event_stations.php'>Event Stations</a>";
