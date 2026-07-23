<?php
/**
 * Caching Functions
 * AFB Mangaan Attendance System
 * 
 * Simple file-based caching fallback since Redis is not configured.
 */

function getCacheDir() {
    $dir = __DIR__ . '/../cache/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function cacheGet($key, $default = null) {
    $file = getCacheDir() . md5($key) . '.json';
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        
        // Check TTL
        if (isset($data['expires_at']) && time() < $data['expires_at']) {
            return $data['value'];
        }
        
        // Expired
        unlink($file);
    }
    
    return $default;
}

function cacheSet($key, $value, $ttl = 3600) {
    $file = getCacheDir() . md5($key) . '.json';
    
    $data = [
        'value' => $value,
        'expires_at' => time() + $ttl
    ];
    
    return file_put_contents($file, json_encode($data)) !== false;
}

function cacheDelete($key) {
    $file = getCacheDir() . md5($key) . '.json';
    if (file_exists($file)) {
        return unlink($file);
    }
    return true;
}

function cacheClear() {
    $dir = getCacheDir();
    $files = glob($dir . '*.json');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    return true;
}
