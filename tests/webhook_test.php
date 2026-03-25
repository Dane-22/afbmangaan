<?php
/**
 * Webhook Test Script
 * AFB Mangaan Attendance System
 * 
 * This script tests the webhook endpoint with sample payloads.
 * Run this from command line or browser to verify the integration.
 */

// Configuration - set your webhook secret here
$webhookSecret = 'change-this-to-a-random-secret-key'; // Match this with config/webhook.php
$webhookUrl = 'http://localhost/afb_mangaan/api/webhook_import.php'; // Adjust if needed

echo "=== AFB Mangaan Webhook Test Script ===\n\n";

// Test 1: Create Attendee
echo "Test 1: Create Attendee\n";
echo "------------------------\n";
$payload = [
    'entity' => 'attendee',
    'action' => 'create',
    'data' => [
        'fullname' => 'Test Member ' . date('His'),
        'category' => 'Adult',
        'contact' => '09123456789',
        'email' => 'test@example.com',
        'qr_token' => null, // Will auto-generate
        'status' => 'Active'
    ]
];

$result = sendWebhook($webhookUrl, $webhookSecret, $payload);
echo "Result: " . ($result['success'] ? "✓ SUCCESS" : "✗ FAILED") . "\n";
echo "Message: " . $result['message'] . "\n";
if (isset($result['id'])) {
    echo "Created ID: " . $result['id'] . "\n";
}
echo "\n";

// Test 2: Create Event
echo "Test 2: Create Event\n";
echo "---------------------\n";
$payload = [
    'entity' => 'event',
    'action' => 'create',
    'data' => [
        'event_name' => 'Test Event ' . date('Y-m-d H:i'),
        'event_date' => date('Y-m-d', strtotime('+7 days')),
        'event_time' => '09:00:00',
        'location' => 'Test Location',
        'type' => 'Sunday Service',
        'status' => 'Upcoming',
        'description' => 'Created via webhook test'
    ]
];

$result = sendWebhook($webhookUrl, $webhookSecret, $payload);
echo "Result: " . ($result['success'] ? "✓ SUCCESS" : "✗ FAILED") . "\n";
echo "Message: " . $result['message'] . "\n";
if (isset($result['id'])) {
    echo "Created ID: " . $result['id'] . "\n";
}
echo "\n";

// Test 3: Invalid Secret
echo "Test 3: Invalid Secret (Should Fail)\n";
echo "-------------------------------------\n";
$result = sendWebhook($webhookUrl, 'wrong-secret', $payload);
echo "Result: " . ($result['success'] ? "✗ UNEXPECTED SUCCESS" : "✓ EXPECTED FAILURE") . "\n";
echo "Message: " . $result['message'] . "\n\n";

// Test 4: Missing Required Field
echo "Test 4: Missing Required Field (Should Fail)\n";
echo "-----------------------------------------------\n";
$payload = [
    'entity' => 'attendee',
    'action' => 'create',
    'data' => [
        'category' => 'Adult'
        // Missing fullname
    ]
];

$result = sendWebhook($webhookUrl, $webhookSecret, $payload);
echo "Result: " . ($result['success'] ? "✗ UNEXPECTED SUCCESS" : "✓ EXPECTED FAILURE") . "\n";
echo "Message: " . $result['message'] . "\n\n";

// Test 5: Unknown Entity
echo "Test 5: Unknown Entity (Should Fail)\n";
echo "-------------------------------------\n";
$payload = [
    'entity' => 'unknown',
    'action' => 'create',
    'data' => []
];

$result = sendWebhook($webhookUrl, $webhookSecret, $payload);
echo "Result: " . ($result['success'] ? "✗ UNEXPECTED SUCCESS" : "✓ EXPECTED FAILURE") . "\n";
echo "Message: " . $result['message'] . "\n\n";

echo "=== Tests Complete ===\n";

/**
 * Send webhook request
 */
function sendWebhook($url, $secret, $payload) {
    $ch = curl_init($url);
    
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Webhook-Secret: ' . $secret
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'message' => 'CURL Error: ' . $error];
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        return ['success' => false, 'message' => 'Invalid response: ' . $response];
    }
    
    return $data;
}
