<?php
/**
 * Webhook Queue Processor
 * AFB Mangaan Attendance System
 * 
 * Run this script as a background process or via cron.
 */

require_once __DIR__ . '/../config/db.php';

echo "Starting webhook processor...\n";

while (true) {
    $pdo = getDB();
    
    // Fetch pending jobs
    $stmt = $pdo->query("SELECT * FROM webhook_queue WHERE status = 'pending' AND attempts < 3 ORDER BY created_at ASC LIMIT 10");
    $jobs = $stmt->fetchAll();
    
    foreach ($jobs as $job) {
        echo "Processing job {$job['id']} to {$job['url']}...\n";
        
        $ch = curl_init($job['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $job['payload']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $newAttempts = $job['attempts'] + 1;
        
        if ($httpCode >= 200 && $httpCode < 300) {
            // Success
            $updateStmt = $pdo->prepare("UPDATE webhook_queue SET status = 'sent', attempts = ? WHERE id = ?");
            $updateStmt->execute([$newAttempts, $job['id']]);
            echo "Job {$job['id']} succeeded.\n";
        } else {
            // Failure
            $status = ($newAttempts >= 3) ? 'failed' : 'pending';
            $updateStmt = $pdo->prepare("UPDATE webhook_queue SET status = ?, attempts = ? WHERE id = ?");
            $updateStmt->execute([$status, $newAttempts, $job['id']]);
            echo "Job {$job['id']} failed (Code: $httpCode). Status set to $status.\n";
        }
    }
    
    // Sleep before next batch to prevent CPU burning
    sleep(5);
}
