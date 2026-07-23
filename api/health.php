<?php
/**
 * System Health Check Endpoint
 * AFB Mangaan Attendance System
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/webhook.php';

header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'services' => []
];

// Check database connectivity
try {
    $pdo = getDB();
    $pdo->query("SELECT 1");
    $health['services']['database'] = 'ok';
} catch (Exception $e) {
    $health['services']['database'] = 'error';
    $health['status'] = 'degraded';
}

// Check webhook configuration
$webhookUrl = getWebhookConfig('make_webhook_url');
if ($webhookUrl) {
    $health['services']['webhook'] = 'configured';
} else {
    $health['services']['webhook'] = 'not_configured';
    // Not strictly an error if webhooks are optional, so we don't degrade the status.
}

echo json_encode($health);
