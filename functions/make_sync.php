<?php
/**
 * make.com Sync Functions
 * AFB Mangaan Attendance System
 * 
 * Functions to sync data FROM MySQL TO Google Sheets via make.com webhooks
 */

require_once __DIR__ . '/../config/webhook.php';

/**
 * Sync attendance record to Google Sheets via make.com
 * Call this after recording attendance
 * 
 * @param int $eventId Event ID
 * @param int $attendeeId Attendee ID  
 * @param string $status Attendance status (Present/Absent)
 * @param string $method Recording method (Manual/QR Scan/Search)
 * @return bool True if sync was attempted, false if webhook not configured
 */
function syncAttendanceToSheets($eventId, $attendeeId, $status, $method) {
    $webhookUrl = getWebhookConfig('make_webhook_url');
    
    // Skip if make.com webhook URL is not configured
    if (empty($webhookUrl)) {
        return false;
    }
    
    try {
        $pdo = getDB();
        
        // Get attendee details
        $stmt = $pdo->prepare("SELECT a.fullname, a.category, a.qr_token, a.contact, a.email 
                              FROM attendees a 
                              WHERE a.id = ? LIMIT 1");
        $stmt->execute([$attendeeId]);
        $attendee = $stmt->fetch();
        
        if (!$attendee) {
            error_log("[SYNC] Attendee not found: {$attendeeId}");
            return false;
        }
        
        // Get event details
        $stmt = $pdo->prepare("SELECT e.event_name, e.event_date, e.event_time, e.type, e.location 
                              FROM events e 
                              WHERE e.id = ? LIMIT 1");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        
        if (!$event) {
            error_log("[SYNC] Event not found: {$eventId}");
            return false;
        }
        
        // Prepare payload for make.com
        $payload = [
            'timestamp' => date('Y-m-d H:i:s'),
            'entity' => 'attendance',
            'action' => 'record',
            'data' => [
                // Event info
                'event_id' => $eventId,
                'event_name' => $event['event_name'],
                'event_date' => $event['event_date'],
                'event_time' => $event['event_time'],
                'event_type' => $event['type'],
                'event_location' => $event['location'],
                
                // Attendee info
                'attendee_id' => $attendeeId,
                'attendee_name' => $attendee['fullname'],
                'attendee_category' => $attendee['category'],
                'attendee_qr_token' => $attendee['qr_token'],
                'attendee_contact' => $attendee['contact'],
                'attendee_email' => $attendee['email'],
                
                // Attendance details
                'status' => $status,
                'method' => $method,
                'sync_timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        
        // Send to make.com webhook asynchronously (don't block user)
        sendAsyncWebhook($webhookUrl, $payload);
        
        if (getWebhookConfig('enable_logging', true)) {
            error_log("[SYNC] Sent attendance to make.com: Event {$eventId}, Attendee {$attendeeId}");
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("[SYNC] Error syncing attendance: " . $e->getMessage());
        return false;
    }
}

/**
 * Sync member data to Google Sheets via make.com
 * 
 * @param int $attendeeId Attendee ID
 * @param string $action Action type (create, update, delete)
 * @return bool True if sync was attempted
 */
function syncMemberToSheets($attendeeId, $action = 'update') {
    $webhookUrl = getWebhookConfig('make_webhook_url');
    
    if (empty($webhookUrl)) {
        return false;
    }
    
    try {
        $pdo = getDB();
        
        $stmt = $pdo->prepare("SELECT id, fullname, category, contact, email, qr_token, status, created_at, updated_at 
                              FROM attendees 
                              WHERE id = ? LIMIT 1");
        $stmt->execute([$attendeeId]);
        $attendee = $stmt->fetch();
        
        if (!$attendee && $action !== 'delete') {
            error_log("[SYNC] Attendee not found: {$attendeeId}");
            return false;
        }
        
        $payload = [
            'timestamp' => date('Y-m-d H:i:s'),
            'entity' => 'member',
            'action' => $action,
            'data' => $attendee ?: ['id' => $attendeeId, 'deleted_at' => date('Y-m-d H:i:s')]
        ];
        
        sendAsyncWebhook($webhookUrl, $payload);
        
        if (getWebhookConfig('enable_logging', true)) {
            error_log("[SYNC] Sent member to make.com: Attendee {$attendeeId}, Action: {$action}");
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("[SYNC] Error syncing member: " . $e->getMessage());
        return false;
    }
}

/**
 * Sync event data to Google Sheets via make.com
 * 
 * @param int $eventId Event ID
 * @param string $action Action type (create, update, delete)
 * @return bool True if sync was attempted
 */
function syncEventToSheets($eventId, $action = 'update') {
    $webhookUrl = getWebhookConfig('make_webhook_url');
    
    if (empty($webhookUrl)) {
        return false;
    }
    
    try {
        $pdo = getDB();
        
        $stmt = $pdo->prepare("SELECT e.*, u.fullname as created_by_name 
                              FROM events e 
                              LEFT JOIN users u ON e.created_by = u.id 
                              WHERE e.id = ? LIMIT 1");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        
        if (!$event && $action !== 'delete') {
            error_log("[SYNC] Event not found: {$eventId}");
            return false;
        }
        
        $payload = [
            'timestamp' => date('Y-m-d H:i:s'),
            'entity' => 'event',
            'action' => $action,
            'data' => $event ?: ['id' => $eventId, 'deleted_at' => date('Y-m-d H:i:s')]
        ];
        
        sendAsyncWebhook($webhookUrl, $payload);
        
        if (getWebhookConfig('enable_logging', true)) {
            error_log("[SYNC] Sent event to make.com: Event {$eventId}, Action: {$action}");
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("[SYNC] Error syncing event: " . $e->getMessage());
        return false;
    }
}

/**
 * Send webhook asynchronously (non-blocking)
 * Uses a short timeout to avoid delaying the user
 * 
 * @param string $url Webhook URL
 * @param array $payload Data to send
 * @return void
 */
function sendAsyncWebhook($url, $payload) {
    $ch = curl_init($url);
    
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT_MS => 1000, // 1 second timeout - quick fire and forget
        CURLOPT_CONNECTTIMEOUT_MS => 500,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    // Execute but don't wait for full response
    curl_exec($ch);
    
    if (curl_errno($ch)) {
        error_log("[SYNC] Webhook error: " . curl_error($ch));
    }
    
    curl_close($ch);
}

/**
 * Test the make.com webhook connection
 * 
 * @return array Test result with success status and message
 */
function testMakeConnection() {
    $webhookUrl = getWebhookConfig('make_webhook_url');
    
    if (empty($webhookUrl)) {
        return ['success' => false, 'message' => 'make.com webhook URL not configured in config/webhook.php'];
    }
    
    $payload = [
        'timestamp' => date('Y-m-d H:i:s'),
        'entity' => 'test',
        'action' => 'connection_test',
        'data' => [
            'message' => 'This is a test from AFB Mangaan Attendance System',
            'source' => 'PHP test function'
        ]
    ];
    
    $ch = curl_init($webhookUrl);
    
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'message' => 'Connection error: ' . $error];
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true, 
            'message' => 'Connection successful (HTTP ' . $httpCode . ')',
            'response' => $response
        ];
    }
    
    return [
        'success' => false, 
        'message' => 'HTTP error: ' . $httpCode,
        'response' => $response
    ];
}
