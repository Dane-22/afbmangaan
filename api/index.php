<?php
/**
 * Vercel Serverless Entry Point
 * Routes requests to appropriate pages
 */

// Get the request path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Route mapping
$routes = [
    '' => __DIR__ . '/../index.php',
    'login' => __DIR__ . '/../index.php',
    'dashboard' => __DIR__ . '/../dashboard.php',
    'attendance' => __DIR__ . '/../attendance.php',
    'members' => __DIR__ . '/../members.php',
    'events' => __DIR__ . '/../events.php',
    'logs' => __DIR__ . '/../logs.php',
    'settings' => __DIR__ . '/../settings.php',
    'reports' => __DIR__ . '/../reports.php',
    'logout' => __DIR__ . '/../logout.php',
    'setup_db' => __DIR__ . '/../setup_db.php',
];

// API routes
if (strpos($path, 'api/') === 0) {
    $apiFile = __DIR__ . '/../api/' . substr($path, 4) . '.php';
    if (file_exists($apiFile)) {
        require $apiFile;
        exit;
    }
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found']);
    exit;
}

// Static assets
if (strpos($path, 'assets/') === 0 || strpos($path, 'css/') === 0 || strpos($path, 'js/') === 0) {
    $file = __DIR__ . '/../' . $path;
    if (file_exists($file)) {
        // Set content type
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $contentTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
        ];
        if (isset($contentTypes[$ext])) {
            header('Content-Type: ' . $contentTypes[$ext]);
        }
        readfile($file);
        exit;
    }
}

// Route to page
if (isset($routes[$path])) {
    require $routes[$path];
} else {
    // Try to match page directly
    $pageFile = __DIR__ . '/../' . $path . '.php';
    if (file_exists($pageFile)) {
        require $pageFile;
    } else {
        http_response_code(404);
        echo "Page not found: $path";
    }
}
