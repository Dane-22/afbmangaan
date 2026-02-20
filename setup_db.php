<?php
/**
 * Database Setup Script - Run once to create tables
 */

require_once __DIR__ . '/config/db.php';

$sqlFile = __DIR__ . '/afb_mangaan_db.sql';

if (!file_exists($sqlFile)) {
    die("SQL file not found: $sqlFile");
}

$sql = file_get_contents($sqlFile);

// Remove CREATE DATABASE and USE statements since we're already connected
$sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
$sql = preg_replace('/USE `.*?`;/i', '', $sql);

try {
    $pdo->exec($sql);
    echo "✅ Database tables created successfully!<br><br>";
    echo "Default login:<br>";
    echo "Username: <strong>admin</strong><br>";
    echo "Password: <strong>admin123</strong><br><br>";
    echo "<a href='index.php'>Go to Login</a>";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
