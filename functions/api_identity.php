<?php
/** JSON authentication for shared web and native portal endpoints. */
function apiAccessError($status, $message) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['success' => false, 'error' => $message, 'message' => $message]);
    exit;
}

function apiIdentity($roles = ['admin', 'operator', 'viewer'], $allowBearer = true) {
    global $pdo;
    $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$authorization && function_exists('getallheaders')) {
        foreach (getallheaders() as $key => $value) if (strcasecmp($key, 'Authorization') === 0) $authorization = $value;
    }
    if ($authorization !== '') {
        if (!$allowBearer) apiAccessError(401, 'Use the mobile attendance sync API');
        require_once __DIR__ . '/mobile_api.php';
        require_once __DIR__ . '/auth_functions.php';
        $user = mobileUser($roles);
        // Existing query functions read this request-local identity. No cookie session is created.
        $_SESSION = ['user_id' => (int)$user['id'], 'username' => $user['username'] ?? '', 'fullname' => $user['fullname'], 'role' => $user['role'], 'church' => $user['church']];
        $GLOBALS['api_bearer_identity'] = true;
    } else {
        require_once __DIR__ . '/../config/session.php';
        require_once __DIR__ . '/auth_functions.php';
        if (!isLoggedIn()) apiAccessError(401, 'Sign in to continue');
        if (isset($_SESSION['login_time']) && time() - $_SESSION['login_time'] > 3600) {
            logoutUser();
            apiAccessError(401, 'Session expired. Sign in again');
        }
        $stmt = getDB()->prepare('SELECT id, username, fullname, role, church, status FROM users WHERE id=? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user || ($user['status'] ?? '') !== 'Active' || !in_array($user['role'], $roles, true)) apiAccessError(403, 'Access denied');
        $_SESSION['role'] = $user['role'];
        $_SESSION['church'] = $user['church'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['login_time'] = time();
        $GLOBALS['api_bearer_identity'] = false;
    }
    header('Cache-Control: no-store');
    return $user;
}

function apiWriteAccess() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') apiAccessError(405, 'Method not allowed');
    if (!empty($GLOBALS['api_bearer_identity'])) return;
    require_once __DIR__ . '/csrf.php';
    if (!verifyCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '')) apiAccessError(403, 'Security validation failed. Refresh the page');
}
