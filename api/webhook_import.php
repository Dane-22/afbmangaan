<?php
/**
 * Webhook endpoint for make.com integration
 * Receives data from Google Sheets via make.com and imports into MySQL
 * AFB Mangaan Attendance System
 */

header('Content-Type: application/json');

// Prevent direct access errors
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

// Define app root if not already defined
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Load environment variables
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/webhook.php';

// Security: Validate webhook secret
$webhookSecret = getWebhookConfig('secret', '');
$receivedSecret = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';

if (empty($webhookSecret) || $receivedSecret !== $webhookSecret) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid webhook secret']);
    exit;
}

// Get JSON payload from make.com
$input = file_get_contents('php://input');
$payload = json_decode($input, true);

if (!$payload || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload: ' . json_last_error_msg()]);
    exit;
}

// Process based on entity type
$entity = $payload['entity'] ?? ''; // 'attendee', 'event', etc.
$action = $payload['action'] ?? ''; // 'create', 'update', 'delete'
$importType = $payload['import_type'] ?? 'single'; // 'single', 'bulk', 'sync', 'backup'
$data = $payload['data'] ?? [];

// Log incoming webhook for debugging
error_log("[WEBHOOK] Received: entity={$entity}, action={$action}, import_type={$importType}");

$result = ['success' => false, 'message' => 'Unknown error'];

// Handle bulk import differently
if ($importType === 'bulk' && is_array($data)) {
    $results = [];
    foreach ($data as $index => $item) {
        switch ($entity) {
            case 'attendee':
                $results[] = processAttendeeImport($item, $action);
                break;
            case 'event':
                $results[] = processEventImport($item, $action);
                break;
            default:
                $results[] = ['success' => false, 'message' => "Unknown entity: {$entity}"];
        }
    }
    $successCount = count(array_filter($results, fn($r) => $r['success'] ?? false));
    $result = [
        'success' => true,
        'message' => "Bulk import completed: {$successCount}/" . count($results) . " items processed",
        'import_type' => 'bulk',
        'results' => $results
    ];
} else {
    // Single item import
    switch ($entity) {
        case 'attendee':
            $result = processAttendeeImport($data, $action);
            break;
        case 'event':
            $result = processEventImport($data, $action);
            break;
        default:
            $result = ['success' => false, 'message' => "Unknown entity type: {$entity}"];
    }
}

// Add import_type to response
$result['import_type'] = $importType;

// Log result
error_log("[WEBHOOK] Result: " . json_encode($result));

http_response_code($result['success'] ? 200 : 400);
echo json_encode($result);
exit;

/**
 * Process attendee import from Google Sheets
 */
function processAttendeeImport($data, $action) {
    $pdo = getDB();
    
    try {
        // Validate required fields
        if (empty($data['fullname'])) {
            return ['success' => false, 'message' => 'Fullname is required'];
        }
        
        // Sanitize inputs
        $fullname = trim($data['fullname']);
        $category = in_array($data['category'] ?? '', ['Youth', 'Adult', 'Senior', 'Child']) 
            ? $data['category'] 
            : 'Adult';
        $contact = isset($data['contact']) ? trim($data['contact']) : null;
        $email = isset($data['email']) ? trim($data['email']) : null;
        $qrToken = isset($data['qr_token']) ? trim($data['qr_token']) : null;
        $status = ($data['status'] ?? '') === 'Archived' ? 'Archived' : 'Active';
        
        if ($action === 'create') {
            // Check if attendee with same name already exists
            $stmt = $pdo->prepare("SELECT id FROM attendees WHERE fullname = ? LIMIT 1");
            $stmt->execute([$fullname]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                return ['success' => false, 'message' => "Attendee '{$fullname}' already exists with ID: {$existing['id']}"];
            }
            
            // Check if QR token is already used
            if ($qrToken) {
                $stmt = $pdo->prepare("SELECT id FROM attendees WHERE qr_token = ? LIMIT 1");
                $stmt->execute([$qrToken]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => "QR token '{$qrToken}' is already in use"];
                }
            }
            
            // Insert new attendee
            $stmt = $pdo->prepare("INSERT INTO attendees (fullname, category, contact, email, qr_token, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fullname, $category, $contact, $email, $qrToken, $status]);
            $newId = $pdo->lastInsertId();
            
            // Auto-generate QR token if not provided
            if (!$qrToken) {
                $prefix = getenv('QR_PREFIX') ?: 'AFB';
                $autoToken = $prefix . str_pad($newId, 6, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("UPDATE attendees SET qr_token = ? WHERE id = ?");
                $stmt->execute([$autoToken, $newId]);
            }
            
            // Log the import if enabled
            if (getWebhookConfig('enable_logging', true)) {
                error_log("[WEBHOOK] Created attendee ID {$newId}: {$fullname}");
            }
            
            return ['success' => true, 'message' => "Attendee '{$fullname}' created successfully", 'id' => $newId];
            
        } elseif ($action === 'update') {
            if (empty($data['id'])) {
                return ['success' => false, 'message' => 'Attendee ID is required for update'];
            }
            
            $id = (int)$data['id'];
            
            // Check if attendee exists
            $stmt = $pdo->prepare("SELECT id FROM attendees WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => "Attendee with ID {$id} not found"];
            }
            
            // Update attendee
            $stmt = $pdo->prepare("UPDATE attendees SET fullname = ?, category = ?, contact = ?, email = ?, qr_token = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$fullname, $category, $contact, $email, $qrToken, $status, $id]);
            
            // Log the import if enabled
            if (getWebhookConfig('enable_logging', true)) {
                error_log("[WEBHOOK] Updated attendee ID {$id}: {$fullname}");
            }
            
            return ['success' => true, 'message' => "Attendee '{$fullname}' updated successfully", 'id' => $id];
            
        } elseif ($action === 'delete') {
            if (empty($data['id'])) {
                return ['success' => false, 'message' => 'Attendee ID is required for delete'];
            }
            
            $id = (int)$data['id'];
            
            $stmt = $pdo->prepare("DELETE FROM attendees WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log the import if enabled
            if (getWebhookConfig('enable_logging', true)) {
                error_log("[WEBHOOK] Deleted attendee ID {$id}");
            }
            
            return ['success' => true, 'message' => "Attendee with ID {$id} deleted successfully"];
        }
        
        return ['success' => false, 'message' => "Unknown action: {$action}"];
        
    } catch (PDOException $e) {
        error_log("[WEBHOOK] Attendee import error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Process event import from Google Sheets
 */
function processEventImport($data, $action) {
    $pdo = getDB();
    
    try {
        // Validate required fields
        if (empty($data['event_name'])) {
            return ['success' => false, 'message' => 'Event name is required'];
        }
        if (empty($data['event_date'])) {
            return ['success' => false, 'message' => 'Event date is required'];
        }
        
        // Sanitize inputs
        $eventName = trim($data['event_name']);
        $eventDate = $data['event_date'];
        $eventTime = $data['event_time'] ?? null;
        $location = isset($data['location']) ? trim($data['location']) : null;
        $type = in_array($data['type'] ?? '', ['Sunday Service', 'Midweek Service', 'Special Event', 'Meeting', 'Other']) 
            ? $data['type'] 
            : 'Sunday Service';
        $status = in_array($data['status'] ?? '', ['Upcoming', 'Ongoing', 'Completed', 'Cancelled']) 
            ? $data['status'] 
            : 'Upcoming';
        $description = isset($data['description']) ? trim($data['description']) : null;
        $createdBy = !empty($data['created_by']) ? (int)$data['created_by'] : null;
        
        if ($action === 'create') {
            // Check if event already exists for same date and name
            $stmt = $pdo->prepare("SELECT id FROM events WHERE event_name = ? AND event_date = ? LIMIT 1");
            $stmt->execute([$eventName, $eventDate]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                return ['success' => false, 'message' => "Event '{$eventName}' on {$eventDate} already exists"];
            }
            
            // Insert new event
            $stmt = $pdo->prepare("INSERT INTO events (event_name, event_date, event_time, location, type, status, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$eventName, $eventDate, $eventTime, $location, $type, $status, $description, $createdBy]);
            $newId = $pdo->lastInsertId();
            
            // Log the import if enabled
            if (getWebhookConfig('enable_logging', true)) {
                error_log("[WEBHOOK] Created event ID {$newId}: {$eventName}");
            }
            
            return ['success' => true, 'message' => "Event '{$eventName}' created successfully", 'id' => $newId];
            
        } elseif ($action === 'update') {
            if (empty($data['id'])) {
                return ['success' => false, 'message' => 'Event ID is required for update'];
            }
            
            $id = (int)$data['id'];
            
            // Check if event exists
            $stmt = $pdo->prepare("SELECT id FROM events WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => "Event with ID {$id} not found"];
            }
            
            // Update event
            $stmt = $pdo->prepare("UPDATE events SET event_name = ?, event_date = ?, event_time = ?, location = ?, type = ?, status = ?, description = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$eventName, $eventDate, $eventTime, $location, $type, $status, $description, $id]);
            
            // Log the import if enabled
            if (getWebhookConfig('enable_logging', true)) {
                error_log("[WEBHOOK] Updated event ID {$id}: {$eventName}");
            }
            
            return ['success' => true, 'message' => "Event '{$eventName}' updated successfully", 'id' => $id];
            
        } elseif ($action === 'delete') {
            if (empty($data['id'])) {
                return ['success' => false, 'message' => 'Event ID is required for delete'];
            }
            
            $id = (int)$data['id'];
            
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log the import if enabled
            if (getWebhookConfig('enable_logging', true)) {
                error_log("[WEBHOOK] Deleted event ID {$id}");
            }
            
            return ['success' => true, 'message' => "Event with ID {$id} deleted successfully"];
        }
        
        return ['success' => false, 'message' => "Unknown action: {$action}"];
        
    } catch (PDOException $e) {
        error_log("[WEBHOOK] Event import error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}
