<?php
/**
 * Async Webhook Queue Functions
 * AFB Mangaan Attendance System
 */

require_once __DIR__ . '/../config/db.php';

function queueWebhook($url, $payload) {
    $pdo = getDB();
    try {
        $stmt = $pdo->prepare("INSERT INTO webhook_queue (url, payload, status) VALUES (?, ?, 'pending')");
        return $stmt->execute([$url, json_encode($payload)]);
    } catch (PDOException $e) {
        error_log("Failed to queue webhook: " . $e->getMessage());
        return false;
    }
}
