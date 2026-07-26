<?php
/**
 * Real-Time Group Chat API Endpoint
 * AFB Mangaan Attendance & Administration System
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth_check.php';

// Verify authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$pdo = getDB();
$userId = $_SESSION['user_id'] ?? ($_SESSION['id'] ?? 1);
$userName = $_SESSION['fullname'] ?? ($_SESSION['name'] ?? 'Admin User');
$userRole = $_SESSION['role'] ?? 'Admin';
$church = $_SESSION['church'] ?? 'AFB Mangaan';

// Ensure Chat Tables Exist
ensureChatTablesExist($pdo);

// Read Input
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? $_POST;
$action = $_GET['action'] ?? ($input['action'] ?? 'get_rooms');

try {
    switch ($action) {
        case 'get_rooms':
            // Fetch all active chat rooms for church
            $stmt = $pdo->prepare("
                SELECT r.id, r.name, r.type, r.created_at,
                       (SELECT message FROM chat_messages WHERE room_id = r.id ORDER BY id DESC LIMIT 1) as last_message,
                       (SELECT created_at FROM chat_messages WHERE room_id = r.id ORDER BY id DESC LIMIT 1) as last_message_time,
                       (SELECT sender_name FROM chat_messages WHERE room_id = r.id ORDER BY id DESC LIMIT 1) as last_sender
                FROM chat_rooms r
                WHERE r.church = ? OR r.church IS NULL
                ORDER BY last_message_time DESC, r.id ASC
            ");
            $stmt->execute([$church]);
            $rooms = $stmt->fetchAll();

            // If no rooms exist, seed default General Chat
            if (empty($rooms)) {
                $seedStmt = $pdo->prepare("INSERT INTO chat_rooms (name, type, created_by, church) VALUES (?, 'group', ?, ?)");
                $seedStmt->execute(['General Church Chat', $userId, $church]);
                $newRoomId = $pdo->lastInsertId();

                // Welcome message
                $msgStmt = $pdo->prepare("INSERT INTO chat_messages (room_id, sender_id, sender_name, message) VALUES (?, 0, 'System', ?)");
                $msgStmt->execute([$newRoomId, "Welcome to the AFB Mangaan General Group Chat! Use this space for team communication and announcements."]);

                $rooms = [[
                    'id' => $newRoomId,
                    'name' => 'General Church Chat',
                    'type' => 'group',
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_message' => 'Welcome to the AFB Mangaan General Group Chat!',
                    'last_message_time' => date('Y-m-d H:i:s'),
                    'last_sender' => 'System'
                ]];
            }

            echo json_encode(['success' => true, 'rooms' => $rooms, 'current_user_id' => $userId]);
            break;

        case 'create_room':
            $roomName = trim($input['name'] ?? '');
            if (empty($roomName)) {
                echo json_encode(['success' => false, 'message' => 'Room name is required.']);
                exit();
            }

            $stmt = $pdo->prepare("INSERT INTO chat_rooms (name, type, created_by, church) VALUES (?, 'group', ?, ?)");
            $stmt->execute([$roomName, $userId, $church]);
            $roomId = $pdo->lastInsertId();

            $msgStmt = $pdo->prepare("INSERT INTO chat_messages (room_id, sender_id, sender_name, message) VALUES (?, 0, 'System', ?)");
            $msgStmt->execute([$roomId, "Group room '{$roomName}' created by {$userName}."]);

            echo json_encode([
                'success' => true,
                'room' => [
                    'id' => $roomId,
                    'name' => $roomName,
                    'type' => 'group',
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_message' => "Group room '{$roomName}' created by {$userName}.",
                    'last_message_time' => date('Y-m-d H:i:s'),
                    'last_sender' => 'System'
                ]
            ]);
            break;

        case 'get_messages':
            $roomId = (int)($_GET['room_id'] ?? ($input['room_id'] ?? 0));
            if ($roomId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid room ID.']);
                exit();
            }

            $stmt = $pdo->prepare("
                SELECT m.id, m.room_id, m.sender_id, m.sender_name, m.message, m.reply_to_id, m.created_at,
                       rm.message as reply_message, rm.sender_name as reply_sender
                FROM chat_messages m
                LEFT JOIN chat_messages rm ON m.reply_to_id = rm.id
                WHERE m.room_id = ?
                ORDER BY m.id ASC
                LIMIT 100
            ");
            $stmt->execute([$roomId]);
            $messages = $stmt->fetchAll();

            // Fetch reactions for these messages
            $messageIds = array_column($messages, 'id');
            $reactionsMap = [];

            if (!empty($messageIds)) {
                $inClause = implode(',', array_map('intval', $messageIds));
                $rxStmt = $pdo->query("
                    SELECT r.message_id, r.emoji, r.user_id, r.user_name
                    FROM chat_reactions r
                    WHERE r.message_id IN ({$inClause})
                ");
                $reactions = $rxStmt->fetchAll();

                foreach ($reactions as $rx) {
                    $mId = $rx['message_id'];
                    if (!isset($reactionsMap[$mId])) {
                        $reactionsMap[$mId] = [];
                    }
                    $reactionsMap[$mId][] = [
                        'emoji' => $rx['emoji'],
                        'user_id' => $rx['user_id'],
                        'user_name' => $rx['user_name']
                    ];
                }
            }

            // Group reactions by emoji per message
            foreach ($messages as &$msg) {
                $rawRx = $reactionsMap[$msg['id']] ?? [];
                $grouped = [];
                foreach ($rawRx as $item) {
                    $e = $item['emoji'];
                    if (!isset($grouped[$e])) {
                        $grouped[$e] = ['emoji' => $e, 'count' => 0, 'users' => [], 'user_reacted' => false];
                    }
                    $grouped[$e]['count']++;
                    $grouped[$e]['users'][] = $item['user_name'];
                    if ($item['user_id'] == $userId) {
                        $grouped[$e]['user_reacted'] = true;
                    }
                }
                $msg['reactions'] = array_values($grouped);
                $msg['is_mine'] = ($msg['sender_id'] == $userId);
            }

            echo json_encode([
                'success' => true,
                'room_id' => $roomId,
                'current_user_id' => $userId,
                'messages' => $messages
            ]);
            break;

        case 'send_message':
            $roomId = (int)($input['room_id'] ?? 0);
            $message = trim($input['message'] ?? '');
            $replyToId = !empty($input['reply_to_id']) ? (int)$input['reply_to_id'] : null;

            if ($roomId <= 0 || empty($message)) {
                echo json_encode(['success' => false, 'message' => 'Room ID and message content are required.']);
                exit();
            }

            $stmt = $pdo->prepare("
                INSERT INTO chat_messages (room_id, sender_id, sender_name, message, reply_to_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$roomId, $userId, $userName, $message, $replyToId]);
            $msgId = $pdo->lastInsertId();

            // Fetch newly inserted message details
            $fetchStmt = $pdo->prepare("
                SELECT m.id, m.room_id, m.sender_id, m.sender_name, m.message, m.reply_to_id, m.created_at,
                       rm.message as reply_message, rm.sender_name as reply_sender
                FROM chat_messages m
                LEFT JOIN chat_messages rm ON m.reply_to_id = rm.id
                WHERE m.id = ?
            ");
            $fetchStmt->execute([$msgId]);
            $newMessage = $fetchStmt->fetch();
            $newMessage['reactions'] = [];
            $newMessage['is_mine'] = true;

            echo json_encode([
                'success' => true,
                'message' => $newMessage
            ]);
            break;

        case 'add_reaction':
            $messageId = (int)($input['message_id'] ?? 0);
            $emoji = trim($input['emoji'] ?? '👍');

            if ($messageId <= 0 || empty($emoji)) {
                echo json_encode(['success' => false, 'message' => 'Message ID and emoji are required.']);
                exit();
            }

            // Check if user already reacted with this emoji
            $checkStmt = $pdo->prepare("SELECT id FROM chat_reactions WHERE message_id = ? AND user_id = ? AND emoji = ?");
            $checkStmt->execute([$messageId, $userId, $emoji]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                // Remove reaction (toggle off)
                $delStmt = $pdo->prepare("DELETE FROM chat_reactions WHERE id = ?");
                $delStmt->execute([$existing['id']]);
                $status = 'removed';
            } else {
                // Insert reaction
                $insStmt = $pdo->prepare("INSERT INTO chat_reactions (message_id, user_id, user_name, emoji) VALUES (?, ?, ?, ?)");
                $insStmt->execute([$messageId, $userId, $userName, $emoji]);
                $status = 'added';
            }

            echo json_encode(['success' => true, 'status' => $status, 'message_id' => $messageId, 'emoji' => $emoji]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid chat action.']);
            break;
    }
} catch (Exception $e) {
    error_log("Chat API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Chat server error: ' . $e->getMessage()]);
}

/**
 * Auto-create database tables for chat if they don't exist
 */
function ensureChatTablesExist($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS chat_rooms (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                type ENUM('group', 'direct') DEFAULT 'group',
                created_by INT NOT NULL DEFAULT 1,
                church VARCHAR(150) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS chat_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                room_id INT NOT NULL,
                sender_id INT NOT NULL,
                sender_name VARCHAR(150) NOT NULL,
                message TEXT NOT NULL,
                reply_to_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_room (room_id),
                INDEX idx_reply (reply_to_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS chat_reactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                message_id INT NOT NULL,
                user_id INT NOT NULL,
                user_name VARCHAR(150) NOT NULL,
                emoji VARCHAR(20) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_emoji (message_id, user_id, emoji),
                INDEX idx_msg (message_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    } catch (Exception $ex) {
        error_log("Table creation error in chat.php: " . $ex->getMessage());
    }
}
