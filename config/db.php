<?php
/**
 * AFB Mangaan Attendance System
 * Database Connection Configuration (PDO)
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Load environment variables
function loadEnv($path) {
    if (!file_exists($path)) {
        die("Environment file not found. Please create .env file.");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        if (!empty($key)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Load .env file
loadEnv(APP_ROOT . '/.env');

// Database configuration
$dbConfig = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'name' => getenv('DB_NAME') ?: 'afb_mangaan_db',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4'
];

// Create PDO connection
try {
    // First try to connect without database to check if we can create it
    $dsnCheck = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset={$dbConfig['charset']}";
    $pdoCheck = new PDO($dsnCheck, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Create database if it doesn't exist
    $pdoCheck->exec("CREATE DATABASE IF NOT EXISTS {$dbConfig['name']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdoCheck = null; // Close connection
    
    // Now connect to the actual database
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$dbConfig['charset']} COLLATE utf8mb4_unicode_ci"
    ];
    
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed: " . htmlspecialchars($e->getMessage()) . "<br><br>Please check your .env file or ensure MySQL is running.");
}

// Helper function to get PDO instance
function getDB() {
    global $pdo;
    return $pdo;
}
