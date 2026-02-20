<?php
/**
 * API: Export Members
 * AFB Mangaan Attendance System
 */

session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="members_' . date('Y-m-d') . '.csv"');

$pdo = getDB();
$stmt = $pdo->query("SELECT fullname, category, contact, email, qr_token, status, created_at FROM attendees ORDER BY fullname");
$members = $stmt->fetchAll();

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Headers
fputcsv($output, ['Full Name', 'Category', 'Contact', 'Email', 'QR Token', 'Status', 'Created Date']);

// Data
foreach ($members as $row) {
    fputcsv($output, [
        $row['fullname'],
        $row['category'],
        $row['contact'],
        $row['email'],
        $row['qr_token'],
        $row['status'],
        $row['created_at']
    ]);
}

fclose($output);
exit;
