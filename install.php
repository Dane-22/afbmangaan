<?php
/**
 * AFB Mangaan Attendance System - Database Setup for phpMyAdmin
 * 
 * This script can be used in two ways:
 * 1. AUTO-SETUP: Run this file directly in browser (e.g., http://localhost/afb_mangaan_php/install.php)
 * 2. PHPMYADMIN MANUAL: Import the afb_mangaan_db.sql file via phpMyAdmin interface
 */

// Configuration - Edit these to match your MySQL setup
$dbHost = 'localhost';
$dbPort = '3306';
$dbUser = 'root';      // Change if your MySQL user is different
$dbPass = '';          // Change if your MySQL has a password
$dbName = 'afb_mangaan_db';

// SQL file path
$sqlFile = __DIR__ . '/afb_mangaan_db.sql';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AFB Mangaan - Database Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 1.8rem; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .method { 
            border: 2px solid #e5e7eb; 
            border-radius: 8px; 
            padding: 20px; 
            margin-bottom: 20px;
        }
        .method h2 { 
            color: #667eea; 
            font-size: 1.2rem; 
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn:hover { background: #5a67d8; transform: translateY(-2px); }
        .btn-success { background: #48bb78; }
        .btn-success:hover { background: #38a169; }
        .steps { 
            background: #f7fafc; 
            border-left: 4px solid #667eea; 
            padding: 15px 20px; 
            margin: 15px 0;
            border-radius: 0 8px 8px 0;
        }
        .steps ol { margin-left: 20px; }
        .steps li { margin: 8px 0; color: #4a5568; }
        .status {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .status.success { background: #c6f6d5; color: #22543d; }
        .status.error { background: #fed7d7; color: #742a2a; }
        .status.info { background: #bee3f8; color: #2a4365; }
        code {
            background: #edf2f7;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        .footer {
            background: #f7fafc;
            padding: 20px 30px;
            text-align: center;
            color: #718096;
            font-size: 0.9rem;
        }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #edf2f7; font-weight: 600; }
        .credentials {
            background: #fffaf0;
            border: 1px solid #fbd38d;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .credentials h3 { color: #c05621; margin-bottom: 15px; }
        .credential-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #fbd38d;
        }
        .credential-item:last-child { border-bottom: none; }
        .label { font-weight: 600; color: #744210; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>AFB Mangaan Attendance System</h1>
            <p>Database Installation & Setup</p>
        </div>
        
        <div class="content">
            <?php
            // Handle Auto-Setup Request
            if (isset($_POST['auto_setup'])) {
                echo '<div class="status info">Running automatic database setup...</div>';
                
                try {
                    // Connect without database
                    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;charset=utf8mb4", $dbUser, $dbPass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Create database
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    echo '<div class="status success">Database <code>' . htmlspecialchars($dbName) . '</code> created successfully!</div>';
                    
                    // Use database
                    $pdo->exec("USE `$dbName`");
                    
                    // Import SQL file
                    if (file_exists($sqlFile)) {
                        $sql = file_get_contents($sqlFile);
                        
                        // Remove CREATE DATABASE and USE statements
                        $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
                        $sql = preg_replace('/USE `.*?`;/i', '', $sql);
                        
                        // Execute SQL
                        $pdo->exec($sql);
                        echo '<div class="status success">All tables and data imported successfully!</div>';
                        
                        echo '<div class="credentials">';
                        echo '<h3>Default Login Credentials</h3>';
                        echo '<div class="credential-item"><span class="label">Username:</span> <code>admin</code></div>';
                        echo '<div class="credential-item"><span class="label">Password:</span> <code>admin123</code></div>';
                        echo '<div class="credential-item"><span class="label">Role:</span> <code>Administrator</code></div>';
                        echo '</div>';
                        
                        echo '<a href="index.php" class="btn btn-success">Go to Login Page</a>';
                    } else {
                        echo '<div class="status error">SQL file not found: ' . htmlspecialchars($sqlFile) . '</div>';
                    }
                    
                } catch (PDOException $e) {
                    echo '<div class="status error">Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    echo '<p>Please check your MySQL configuration and ensure the server is running.</p>';
                }
            } else {
                // Show installation options
                ?>
                
                <div class="method">
                    <h2>Method 1: Automatic Setup (Recommended)</h2>
                    <p>This will automatically create the database and import all tables with one click.</p>
                    <form method="POST" style="margin-top: 15px;">
                        <button type="submit" name="auto_setup" class="btn">
                            Run Automatic Setup
                        </button>
                    </form>
                </div>
                
                <div class="method">
                    <h2>Method 2: Manual Import via phpMyAdmin</h2>
                    <p>If automatic setup fails, use phpMyAdmin to manually import the database.</p>
                    
                    <div class="steps">
                        <ol>
                            <li>Open <strong>phpMyAdmin</strong> in your browser (usually at <code>http://localhost/phpmyadmin</code>)</li>
                            <li>Click <strong>"New"</strong> in the left sidebar to create a database</li>
                            <li>Enter database name: <code>afb_mangaan_db</code></li>
                            <li>Select <strong>utf8mb4_unicode_ci</strong> as collation</li>
                            <li>Click <strong>"Create"</strong></li>
                            <li>Click on the newly created database</li>
                            <li>Go to <strong>"Import"</strong> tab</li>
                            <li>Click <strong>"Choose File"</strong> and select <code>afb_mangaan_db.sql</code></li>
                            <li>Click <strong>"Go"</strong> at the bottom</li>
                        </ol>
                    </div>
                </div>
                
                <div class="method">
                    <h2>Database Schema Overview</h2>
                    <table>
                        <tr>
                            <th>Table</th>
                            <th>Description</th>
                            <th>Records</th>
                        </tr>
                        <tr>
                            <td><code>users</code></td>
                            <td>System users (admin, operator, viewer)</td>
                            <td>2 (default)</td>
                        </tr>
                        <tr>
                            <td><code>attendees</code></td>
                            <td>Church members with QR codes</td>
                            <td>5 (sample)</td>
                        </tr>
                        <tr>
                            <td><code>events</code></td>
                            <td>Church events and services</td>
                            <td>5 (sample)</td>
                        </tr>
                        <tr>
                            <td><code>attendance_logs</code></td>
                            <td>Attendance records</td>
                            <td>Empty</td>
                        </tr>
                        <tr>
                            <td><code>system_logs</code></td>
                            <td>Audit trail of user actions</td>
                            <td>4 (sample)</td>
                        </tr>
                    </table>
                </div>
                
                <div class="credentials">
                    <h3>Default Login Credentials</h3>
                    <div class="credential-item">
                        <span class="label">Admin User:</span>
                        <span>Username: <code>admin</code> / Password: <code>admin123</code></span>
                    </div>
                    <div class="credential-item">
                        <span class="label">Operator User:</span>
                        <span>Username: <code>operator</code> / Password: <code>password</code></span>
                    </div>
                </div>
                
                <?php } ?>
        </div>
        
        <div class="footer">
            <p>AFB Mangaan Attendance System v1.0.0</p>
            <p style="margin-top: 5px;">Make sure to update your <code>.env</code> file with your database credentials</p>
        </div>
    </div>
</body>
</html>
