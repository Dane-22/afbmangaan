<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = getDB();
    
    // Clear existing users to prevent conflicts
    $pdo->query("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->query("TRUNCATE TABLE users");
    $pdo->query("SET FOREIGN_KEY_CHECKS = 1");
    
    $stmt = $pdo->prepare("INSERT INTO users (church, username, password, fullname, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    
    // Admin 1
    $stmt->execute([
        'AFB Mangaan',
        'admin',
        '$2y$10$pCHnPcELGAog/L/m35uT8.YIPwkYFuTLpIz5iNc/k7h53qPmzF.Fm', // admin123
        'System Admin',
        'admin',
        'Active'
    ]);
    
    // Operator 1
    $stmt->execute([
        'AFB Mangaan',
        'operator',
        '$2y$10$el4MBjzlFrv4qeg84lelu.VyToq63kMRilDwAM4vYt129l.Ptllp.', // password
        'Mangaan Operator',
        'operator',
        'Active'
    ]);

    // Admin 2
    $stmt->execute([
        'AFB Lettac Sur',
        'admin',
        '$2y$10$pCHnPcELGAog/L/m35uT8.YIPwkYFuTLpIz5iNc/k7h53qPmzF.Fm', // admin123
        'System Admin',
        'admin',
        'Active'
    ]);
    
    // Operator 2
    $stmt->execute([
        'AFB Lettac Sur',
        'operator',
        '$2y$10$el4MBjzlFrv4qeg84lelu.VyToq63kMRilDwAM4vYt129l.Ptllp.', // password
        'Lettac Sur Operator',
        'operator',
        'Active'
    ]);

    echo "Default users seeded successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
