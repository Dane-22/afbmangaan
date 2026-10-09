<?php
require_once __DIR__ . '/../functions/mobile_api.php';
require_once __DIR__ . '/../functions/make_sync.php';
require_once __DIR__ . '/../functions/mobile_sync_rules.php';
mobileMethod('POST');
$user = mobileUser();
$input = mobileInput();
$actions = $input['actions'] ?? null;
if (!is_array($actions) || count($actions) > 50) {
    mobileJson(400, ['success' => false, 'error' => 'Send up to 50 actions']);
}

$pdo = getDB();
$pdo->exec("SET time_zone = '+00:00'");
$results = [];
foreach ($actions as $action) {
    if (!is_array($action)) {
        mobileJson(400, ['success' => false, 'error' => 'Invalid action']);
    }
    $id = (string)($action['client_id'] ?? '');
    $eventId = filter_var($action['event_id'] ?? null, FILTER_VALIDATE_INT);
    $attendeeId = filter_var($action['attendee_id'] ?? null, FILTER_VALIDATE_INT);
    $status = (string)($action['status'] ?? '');
    $method = (string)($action['method'] ?? '');
    $occurred = mobileUtc($action['occurred_at_utc'] ?? null);
    $baseStatus = $action['base_status'] ?? null;
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $id)
        || !$eventId || !$attendeeId || $eventId < 1 || $attendeeId < 1
        || !in_array($status, ['Present', 'Absent'], true)
        || !in_array($method, ['Manual', 'QR Scan', 'Search'], true)
        || !$occurred || ($baseStatus !== null && !in_array($baseStatus, ['Present', 'Absent'], true))) {
        mobileJson(400, ['success' => false, 'error' => 'Invalid attendance action']);
    }
    $baseTime = null;
    if (array_key_exists('base_log_time_utc', $action) && $action['base_log_time_utc'] !== null) {
        $baseTime = mobileUtc($action['base_log_time_utc']);
        if (!$baseTime) mobileJson(400, ['success' => false, 'error' => 'Invalid base timestamp']);
    }
    try {
        $pdo->beginTransaction();
        // Locking the event serializes inserts for the same event and attendee.
        $eventStmt = $pdo->prepare('SELECT id, church, status, start_date, end_date FROM events WHERE id = ? FOR UPDATE');
        $eventStmt->execute([$eventId]);
        $event = $eventStmt->fetch();
        $oldStmt = $pdo->prepare('SELECT client_id, user_id, event_id, attendee_id, requested_status, method, occurred_at_utc, result, reason FROM mobile_attendance_actions WHERE client_id = ?');
        $oldStmt->execute([$id]);
        $old = $oldStmt->fetch();
        if ($old) {
            $pdo->commit();
            if ((int)$old['user_id'] !== (int)$user['id'] || (int)$old['event_id'] !== $eventId || (int)$old['attendee_id'] !== $attendeeId || $old['requested_status'] !== $status || $old['method'] !== $method || $old['occurred_at_utc'] !== mobileDbTime($occurred)) {
                $results[] = ['client_id' => $id, 'result' => 'rejected', 'reason' => 'Action ID was reused with different data'];
            } else {
                $results[] = ['client_id' => $id, 'result' => $old['result'], 'reason' => $old['reason']];
            }
            continue;
        }

        $memberStmt = $pdo->prepare('SELECT id, church, status FROM attendees WHERE id = ?');
        $memberStmt->execute([$attendeeId]);
        $member = $memberStmt->fetch();
        $result = 'applied';
        $reason = null;
        if ($occurred > (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('+10 minutes')) {
            $result = 'rejected';
            $reason = 'Device time is in the future';
        } elseif (!$event || $event['church'] !== $user['church'] || !$member || $member['church'] !== $user['church']) {
            $result = 'rejected';
            $reason = 'Event or member is unavailable for this church';
        } elseif ($event['status'] === 'Cancelled' || $member['status'] !== 'Active') {
            $result = 'conflict';
            $reason = 'Event was cancelled or member was archived';
        } else {
            $eventDay = $occurred->setTimezone(new DateTimeZone('Asia/Manila'))->format('Y-m-d');
            if ($eventDay < $event['start_date'] || $eventDay > ($event['end_date'] ?: $event['start_date'])) {
                $result = 'conflict';
                $reason = 'Recorded time is outside the event dates';
            }
        }

        $current = null;
        if ($result === 'applied') {
            $currentStmt = $pdo->prepare('SELECT id, status, log_time FROM attendance_logs WHERE event_id = ? AND attendee_id = ? FOR UPDATE');
            $currentStmt->execute([$eventId, $attendeeId]);
            $current = $currentStmt->fetch();
            $currentTime = $current ? mobileDbTime(new DateTimeImmutable($current['log_time'], new DateTimeZone('UTC'))) : null;
            $result = mobileAttendanceDecision($current['status'] ?? null, $currentTime, $status, $baseStatus, $baseTime ? mobileDbTime($baseTime) : null);
            if ($result === 'duplicate') $reason = 'Already marked ' . $status;
            if ($result === 'conflict') $reason = 'Attendance status changed on another device';
        }
        if ($result === 'applied') {
            $insert = $pdo->prepare('INSERT INTO attendance_logs (event_id, attendee_id, status, method, log_time, logged_by) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), method = VALUES(method), log_time = VALUES(log_time), logged_by = VALUES(logged_by)');
            $insert->execute([$eventId, $attendeeId, $status, $method, mobileDbTime($occurred), $user['id']]);
        }
        $save = $pdo->prepare('INSERT INTO mobile_attendance_actions (client_id, church, user_id, event_id, attendee_id, requested_status, method, occurred_at_utc, base_status, base_log_time_utc, result, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $save->execute([$id, $user['church'], $user['id'], $eventId, $attendeeId, $status, $method, mobileDbTime($occurred), $baseStatus, $baseTime ? mobileDbTime($baseTime) : null, $result, $reason]);
        $pdo->commit();
        if ($result === 'applied') {
            try { syncAttendanceToSheets($eventId, $attendeeId, $status, $method); }
            catch (Throwable $e) { error_log('Mobile attendance webhook error: ' . $e->getMessage()); }
        }
        $results[] = ['client_id' => $id, 'result' => $result, 'reason' => $reason];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Mobile sync error: ' . $e->getMessage());
        mobileJson(500, ['success' => false, 'error' => 'Sync failed. Your device will retry']);
    }
}
mobileJson(200, ['success' => true, 'results' => $results]);
