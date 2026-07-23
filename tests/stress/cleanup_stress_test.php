<?php
// cleanup_stress_test.php
require_once __DIR__ . '/includes/db_connect.php';

echo "Cleaning up stress testing data...\n";

try {
    $pdo->beginTransaction();
    
    // Delete any attendance records created in the last 10 minutes
    // (Assuming the stress test takes less than 10 mins to run)
    $stmt = $pdo->prepare("DELETE FROM attendance WHERE created_at >= NOW() - INTERVAL 10 MINUTE");
    $stmt->execute();
    $deletedAttendance = $stmt->rowCount();
    echo "Deleted {$deletedAttendance} attendance records.\n";
    
    $pdo->commit();
    echo "Cleanup complete!\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error during cleanup: " . $e->getMessage() . "\n";
}
