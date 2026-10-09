<?php
/** Shared authentication and JSON helpers for the Android API. */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/jwt_auth.php';

function mobileJson($status, $data) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function mobileInput() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        mobileJson(400, ['success' => false, 'error' => 'Expected a JSON object']);
    }
    return $input;
}

function mobileMethod($method) {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        mobileJson(405, ['success' => false, 'error' => 'Method not allowed']);
    }
}

function mobileSecretReady() {
    $secret = getenv('JWT_SECRET');
    if (!$secret || strlen($secret) < 32) {
        error_log('Mobile API requires a JWT_SECRET of at least 32 characters');
        mobileJson(503, ['success' => false, 'error' => 'Mobile authentication is not configured']);
    }
}

function mobileUser($roles = ['admin', 'operator']) {
    mobileSecretReady();
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$header && function_exists('getallheaders')) {
        foreach (getallheaders() as $key => $value) {
            if (strcasecmp($key, 'Authorization') === 0) $header = $value;
        }
    }
    if (!preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
        mobileJson(401, ['success' => false, 'error' => 'Sign in to sync']);
    }
    $claims = verifyJwt($matches[1]);
    if (!$claims || empty($claims['user_id'])) {
        mobileJson(401, ['success' => false, 'error' => 'Session expired. Sign in to sync']);
    }
    $stmt = getDB()->prepare("SELECT id, fullname, role, church FROM users WHERE id = ? AND status = 'Active' LIMIT 1");
    $stmt->execute([(int)$claims['user_id']]);
    $user = $stmt->fetch();
    if (!$user || !in_array($user['role'], $roles, true)) {
        mobileJson(403, ['success' => false, 'error' => 'Access denied']);
    }
    return $user;
}

function mobileIso($mysqlUtc) {
    if ($mysqlUtc === null) return null;
    return (new DateTimeImmutable($mysqlUtc, new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z');
}

function mobileUtc($value) {
    if (!is_string($value) || !preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(?:\.\d{1,6})?Z$/', $value)) return null;
    try {
        return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'));
    } catch (Exception $e) {
        return null;
    }
}

function mobileDbTime(DateTimeImmutable $time) {
    return $time->format('Y-m-d H:i:s.u');
}
