<?php
require_once __DIR__ . '/../functions/mobile_api.php';
mobileMethod('POST');
mobileSecretReady();
$input = mobileInput();
$username = trim((string)($input['username'] ?? ''));
$password = (string)($input['password'] ?? '');
$church = (string)($input['church'] ?? '');
if ($username === '' || $password === '' || $church === '') {
    mobileJson(400, ['success' => false, 'error' => 'Username, password, and church are required']);
}

$pdo = getDB();
$pdo->exec('DELETE FROM mobile_login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
$key = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower($username) . '|' . $church);
$limit = $pdo->prepare('SELECT COUNT(*) FROM mobile_login_attempts WHERE key_hash = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
$limit->execute([$key]);
if ((int)$limit->fetchColumn() >= 10) {
    mobileJson(429, ['success' => false, 'error' => 'Too many sign-in attempts. Try again later']);
}
$pdo->prepare('INSERT INTO mobile_login_attempts (key_hash) VALUES (?)')->execute([$key]);

$stmt = $pdo->prepare("SELECT id, fullname, role, church, password, must_change_password FROM users WHERE username = ? AND church = ? AND status = 'Active' LIMIT 1");
$stmt->execute([$username, $church]);
$user = $stmt->fetch();
if (!$user || !password_verify($password, $user['password'])) {
    mobileJson(401, ['success' => false, 'error' => 'Invalid sign-in details']);
}
if (!in_array($user['role'], ['admin', 'operator'], true)) {
    mobileJson(403, ['success' => false, 'error' => 'Mobile attendance requires an admin or operator account']);
}
if ((int)$user['must_change_password'] === 1) {
    mobileJson(403, ['success' => false, 'error' => 'Change your password on the web dashboard first']);
}
mobileJson(200, [
    'success' => true,
    'token' => generateJwt((int)$user['id']),
    'expires_in' => 3600,
    'user' => ['id' => (int)$user['id'], 'fullname' => $user['fullname'], 'role' => $user['role'], 'church' => $user['church']]
]);
