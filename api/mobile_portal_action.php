<?php
/** Mutations for native Android management screens. Every target is church-scoped. */
require_once __DIR__ . '/../functions/mobile_api.php';
require_once __DIR__ . '/../functions/activity_logger.php';
mobileMethod('POST');
$user = mobileUser(['admin', 'operator']);
$input = mobileInput();
$resource = (string)($input['resource'] ?? '');
$action = (string)($input['action'] ?? '');
$church = $user['church'];
$pdo = getDB();

function portalFail($message, $status = 400) {
    if (getDB()->inTransaction()) getDB()->rollBack();
    mobileJson($status, ['success' => false, 'error' => $message]);
}
function portalText($input, $key, $limit = 255, $required = false) {
    if (isset($input[$key]) && !is_scalar($input[$key])) portalFail("Invalid $key");
    $value = trim((string)($input[$key] ?? ''));
    if (($required && $value === '') || mb_strlen($value) > $limit) portalFail("Invalid $key");
    return $value;
}
function portalId($input, $key) {
    $value = filter_var($input[$key] ?? null, FILTER_VALIDATE_INT);
    if (!$value || $value < 1) portalFail("Invalid $key");
    return $value;
}
function portalOwnEvent($id, $church) {
    $stmt = getDB()->prepare('SELECT id FROM events WHERE id=? AND church=? LIMIT 1');
    $stmt->execute([$id, $church]);
    if (!$stmt->fetch()) portalFail('Event not found in this church', 404);
}
function portalOwnStation($id, $church) {
    $stmt = getDB()->prepare('SELECT s.id FROM event_stations s JOIN events e ON e.id=s.event_id WHERE s.id=? AND e.church=? LIMIT 1');
    $stmt->execute([$id, $church]);
    if (!$stmt->fetch()) portalFail('Station not found in this church', 404);
}
function portalOwnMember($id, $church) {
    $stmt = getDB()->prepare('SELECT id FROM attendees WHERE id=? AND church=? LIMIT 1');
    $stmt->execute([$id, $church]);
    if (!$stmt->fetch()) portalFail('Member not found in this church', 404);
}
function portalDate($value) {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) portalFail('Enter a valid date');
    return $value;
}

try {
    $pdo->beginTransaction();
    $logAction = null;
    $logDetails = '';
    if ($resource === 'members') {
        if ($action === 'save') {
            $name = portalText($input, 'fullname', 255, true);
            $category = portalText($input, 'category', 100, true);
            $ministry = portalText($input, 'ministry', 255);
            $contact = portalText($input, 'contact', 100);
            $email = portalText($input, 'email', 255);
            $status = portalText($input, 'status', 20);
            if (!in_array($status, ['Active', 'Inactive', 'Archived'], true)) portalFail('Invalid member status');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) portalFail('Invalid email address');
            if (!empty($input['id'])) {
                $id = portalId($input, 'id');
                portalOwnMember($id, $church);
                $stmt = $pdo->prepare('UPDATE attendees SET fullname=?, category=?, ministry=?, contact=?, email=?, status=? WHERE id=? AND church=?');
                $stmt->execute([$name, $category, $ministry ?: null, $contact, $email, $status, $id, $church]);
                $logAction = 'MEMBER_UPDATE';
            } else {
                $token = (getenv('QR_PREFIX') ?: 'AFB') . '-' . strtoupper(bin2hex(random_bytes(10)));
                $stmt = $pdo->prepare('INSERT INTO attendees (church, fullname, category, ministry, contact, email, qr_token, status) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->execute([$church, $name, $category, $ministry ?: null, $contact, $email, $token, $status]);
                $id = (int)$pdo->lastInsertId();
                $logAction = 'MEMBER_CREATE';
            }
            $logDetails = "Mobile member $id: $name";
        } elseif ($action === 'archive') {
            $id = portalId($input, 'id');
            portalOwnMember($id, $church);
            $stmt = $pdo->prepare("UPDATE attendees SET status='Archived' WHERE id=? AND church=?");
            $stmt->execute([$id, $church]);
            $logAction = 'MEMBER_ARCHIVE';
            $logDetails = "Mobile member $id";
        } elseif ($action === 'category') {
            $name = portalText($input, 'name', 100, true);
            $pdo->prepare('INSERT IGNORE INTO categories (church,name) VALUES (?,?)')->execute([$church, $name]);
            $logAction = 'CATEGORY_CREATE';
            $logDetails = "Mobile category $name";
        } else portalFail('Unknown member action');
    } elseif ($resource === 'events') {
        if ($action === 'save') {
            $name = portalText($input, 'event_name', 255, true);
            $start = portalDate(portalText($input, 'start_date', 10, true));
            $end = portalText($input, 'end_date', 10) ?: $start;
            portalDate($end);
            if ($end < $start) portalFail('End date must follow start date');
            $time = portalText($input, 'event_time', 8);
            if ($time !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time)) portalFail('Invalid event time');
            $location = portalText($input, 'location', 255);
            $type = portalText($input, 'type', 100, true);
            $description = portalText($input, 'description', 10000);
            if (!empty($input['id'])) {
                $id = portalId($input, 'id');
                portalOwnEvent($id, $church);
                $stmt = $pdo->prepare('UPDATE events SET event_name=?, start_date=?, end_date=?, event_time=?, location=?, type=?, description=? WHERE id=? AND church=?');
                $stmt->execute([$name, $start, $end, $time ?: null, $location, $type, $description, $id, $church]);
                $logAction = 'EVENT_UPDATE';
            } else {
                $recurring = !empty($input['is_recurring']);
                $weeks = $recurring ? 52 : 1;
                $stmt = $pdo->prepare('INSERT INTO events (church,event_name,start_date,end_date,event_time,location,type,description,created_by) VALUES (?,?,?,?,?,?,?,?,?)');
                for ($i = 0; $i < $weeks; $i++) {
                    $offset = $i ? "+$i weeks" : '+0 weeks';
                    $eventStart = (new DateTimeImmutable($start))->modify($offset)->format('Y-m-d');
                    $eventEnd = (new DateTimeImmutable($end))->modify($offset)->format('Y-m-d');
                    $stmt->execute([$church, $name, $eventStart, $eventEnd, $time ?: null, $location, $type, $description, $user['id']]);
                }
                $id = (int)$pdo->lastInsertId();
                $logAction = 'EVENT_CREATE';
            }
            $logDetails = "Mobile event $id: $name";
        } elseif ($action === 'status') {
            $id = portalId($input, 'id');
            $status = portalText($input, 'status', 20);
            if (!in_array($status, ['Upcoming','Ongoing','Completed','Cancelled','Archived'], true)) portalFail('Invalid event status');
            portalOwnEvent($id, $church);
            $pdo->prepare('UPDATE events SET status=? WHERE id=? AND church=?')->execute([$status, $id, $church]);
            $logAction = 'EVENT_STATUS';
            $logDetails = "Mobile event $id: $status";
        } else portalFail('Unknown event action');
    } elseif ($resource === 'songs') {
        if ($action === 'save') {
            $eventId = portalId($input, 'event_id');
            portalOwnEvent($eventId, $church);
            $title = portalText($input, 'title', 255, true);
            $artist = portalText($input, 'artist', 255);
            $lyrics = portalText($input, 'lyrics', 50000);
            $chords = portalText($input, 'chords', 50000);
            $order = max(0, (int)($input['sort_order'] ?? 0));
            if (!empty($input['id'])) {
                $id = portalId($input, 'id');
                $pdo->prepare('UPDATE event_songs SET title=?, artist=?, lyrics=?, chords=?, sort_order=? WHERE id=? AND event_id=?')->execute([$title, $artist, $lyrics, $chords, $order, $id, $eventId]);
            } else {
                $pdo->prepare('INSERT INTO event_songs (event_id,title,artist,lyrics,chords,sort_order) VALUES (?,?,?,?,?,?)')->execute([$eventId, $title, $artist, $lyrics, $chords, $order]);
            }
            $logAction = 'LINEUP_SAVE';
            $logDetails = "Mobile song: $title";
        } elseif ($action === 'delete') {
            $id = portalId($input, 'id');
            $pdo->prepare('DELETE FROM event_songs WHERE id=? AND event_id IN (SELECT id FROM events WHERE church=?)')->execute([$id, $church]);
            $logAction = 'LINEUP_DELETE';
            $logDetails = "Mobile song $id";
        } else portalFail('Unknown lineup action');
    } elseif ($resource === 'stations') {
        if ($action === 'save') {
            $eventId = portalId($input, 'event_id');
            portalOwnEvent($eventId, $church);
            $name = portalText($input, 'station_name', 150, true);
            $pdo->prepare('INSERT INTO event_stations (event_id,station_name) VALUES (?,?)')->execute([$eventId, $name]);
            $logDetails = "Mobile station: $name";
        } elseif ($action === 'delete') {
            $id = portalId($input, 'id');
            $pdo->prepare('DELETE FROM event_stations WHERE id=? AND event_id IN (SELECT id FROM events WHERE church=?)')->execute([$id, $church]);
            $logDetails = "Mobile station $id";
        } elseif ($action === 'assign') {
            $stationId = portalId($input, 'station_id');
            $memberId = portalId($input, 'member_id');
            portalOwnStation($stationId, $church);
            $stmt = $pdo->prepare("SELECT id FROM attendees WHERE id=? AND church=? AND status='Active'");
            $stmt->execute([$memberId, $church]);
            if (!$stmt->fetch()) portalFail('Member not found in this church', 404);
            $pdo->prepare('INSERT IGNORE INTO event_station_assignments (station_id,member_id) VALUES (?,?)')->execute([$stationId, $memberId]);
            $logDetails = "Mobile station $stationId, member $memberId";
        } elseif ($action === 'unassign') {
            $id = portalId($input, 'id');
            $pdo->prepare('DELETE FROM event_station_assignments WHERE id=? AND station_id IN (SELECT s.id FROM event_stations s JOIN events e ON e.id=s.event_id WHERE e.church=?)')->execute([$id, $church]);
            $logDetails = "Mobile assignment $id";
        } else portalFail('Unknown station action');
        $logAction = 'STATION_' . strtoupper($action);
    } elseif ($resource === 'logs' && $action === 'clear') {
        if ($user['role'] !== 'admin') portalFail('Only admins can clear logs', 403);
        $stmt = $pdo->prepare('DELETE FROM system_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY) AND user_id IN (SELECT id FROM users WHERE church=?)');
        $stmt->execute([$church]);
        $logAction = 'LOGS_CLEARED';
        $logDetails = 'Cleared ' . $stmt->rowCount() . ' old church logs in Android';
    } elseif ($resource === 'settings' && $action === 'password') {
        $current = (string)($input['current_password'] ?? '');
        $new = (string)($input['new_password'] ?? '');
        if (strlen($new) < 6 || strlen($new) > 200) portalFail('Password must be 6 to 200 characters');
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id=? AND church=?');
        $stmt->execute([$user['id'], $church]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($current, $row['password'])) portalFail('Current password is incorrect', 403);
        $pdo->prepare('UPDATE users SET password=? WHERE id=? AND church=?')->execute([password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]), $user['id'], $church]);
        $logAction = 'PASSWORD_CHANGE';
        $logDetails = 'Changed password in Android app';
    } else portalFail('Unknown action');
    $pdo->commit();
    if ($logAction) logActivity($user['id'], $logAction, $logDetails);
    mobileJson(200, ['success' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Mobile portal action failed: ' . $e->getMessage());
    portalFail('Could not save changes', 500);
}
