<?php
/**
 * Structured Logging Functions
 * AFB Mangaan Attendance System
 */

function getLogDir() {
    $dir = __DIR__ . '/../logs/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function writeLog($level, $message, $context = []) {
    $logEntry = [
        'timestamp' => date('c'),
        'level' => $level,
        'message' => $message,
        'context' => $context,
        'user_id' => $_SESSION['user_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ];
    
    $file = getLogDir() . strtolower($level) . '.log';
    file_put_contents($file, json_encode($logEntry) . "\n", FILE_APPEND);
}

function logError($message, $context = []) {
    writeLog('ERROR', $message, $context);
}

function logInfo($message, $context = []) {
    writeLog('INFO', $message, $context);
}

function logWarning($message, $context = []) {
    writeLog('WARNING', $message, $context);
}
