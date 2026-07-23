<?php
/**
 * JWT Authentication Functions
 * AFB Mangaan Attendance System
 */

require_once __DIR__ . '/../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

function getJwtSecret() {
    $secret = getenv('JWT_SECRET');
    if (!$secret) {
        $secret = 'development_secret_key_change_in_production_123!';
    }
    return $secret;
}

function generateJwt($userId) {
    $payload = [
        'user_id' => $userId,
        'iat' => time(),
        'exp' => time() + 3600 // 1 hour expiration
    ];
    return JWT::encode($payload, getJwtSecret(), 'HS256');
}

function verifyJwt($token) {
    try {
        $decoded = JWT::decode($token, new Key(getJwtSecret(), 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        return false;
    }
}

function requireJwtAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        $decoded = verifyJwt($token);
        
        if ($decoded) {
            return $decoded['user_id'];
        }
    }
    
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Invalid or missing JWT']);
    exit;
}
