<?php
require_once __DIR__ . '/../functions/mobile_api.php';
require_once __DIR__ . '/../functions/make_sync.php';
mobileMethod('POST');
$user = mobileUser(['admin']);
$input = mobileInput();
$id = (string)($input['client_id'] ?? '');
$decision = (string)($input['decision'] ?? '');
$expectedStatus = $input['expected_status'] ?? null;
$expectedTime = $input['expected_log_time_utc'] ?? null;
if (!preg_match('/^[0-9a-f-]{36}$/i', $id) || !in_array($decision, ['apply', 'keep'], true)) {
    mobileJson(400, ['success' => false, 'error' => 'Invalid decision']);
}
if ($expectedStatus !== null && !in_array($expectedStatus, ['Present', 'Absent'], true)) mobileJson(400, ['success' => false, 'error' => 'Invalid expected status']);
if ($expectedTime !== null && !mobileUtc($expectedTime)) mobileJson(400, ['success' => false, 'error' => 'Invalid expected timestamp']);
$pdo = getDB();
$pdo->exec("SET time_zone = '+00:00'");
try {
    $pdo->beginTransaction();
    $lookup = $pdo->prepare('SELECT event_id FROM mobile_attendance_actions WHERE client_id = ? AND church = ?');
    $lookup->execute([$id, $user['church']]);
    $eventId = $lookup->fetchColumn();
    if (!$eventId) {
        $pdo->rollBack();
        mobileJson(404, ['success' => false, 'error' => 'Open conflict not found']);
    }
    $lockEvent = $pdo->prepare('SELECT id FROM events WHERE id = ? FOR UPDATE');
    $lockEvent->execute([$eventId]);
    $stmt = $pdo->prepare("SELECT m.*, e.status AS event_status, e.church AS event_church, a.status AS member_status, a.church AS member_church FROM mobile_attendance_actions m LEFT JOIN events e ON e.id = m.event_id LEFT JOIN attendees a ON a.id = m.attendee_id WHERE m.client_id = ? AND m.church = ? FOR UPDATE");
    $stmt->execute([$id, $user['church']]);
    $action = $stmt->fetch();
    if (!$action || $action['result'] !== 'conflict') {
        $pdo->rollBack();
        mobileJson(404, ['success' => false, 'error' => 'Open conflict not found']);
    }
    if ($decision === 'apply') {
        if ($action['event_church'] !== $user['church'] || $action['member_church'] !== $user['church'] || $action['event_status'] === 'Cancelled' || $action['member_status'] !== 'Active') {
            $pdo->rollBack();
            mobileJson(409, ['success' => false, 'error' => 'This record cannot be applied. Keep the server result or correct the event/member first']);
        }
        $current = $pdo->prepare('SELECT status, log_time FROM attendance_logs WHERE event_id = ? AND attendee_id = ? FOR UPDATE');
        $current->execute([$action['event_id'], $action['attendee_id']]);
        $currentRow = $current->fetch();
        if (($currentRow['status'] ?? null) !== $expectedStatus || mobileIso($currentRow['log_time'] ?? null) !== $expectedTime) {
            $pdo->rollBack();
            mobileJson(409, ['success' => false, 'error' => 'Server attendance changed. Refresh conflicts before deciding']);
        }
        $write = $pdo->prepare('INSERT INTO attendance_logs (event_id, attendee_id, status, method, log_time, logged_by) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), method = VALUES(method), log_time = VALUES(log_time), logged_by = VALUES(logged_by)');
        $write->execute([$action['event_id'], $action['attendee_id'], $action['requested_status'], $action['method'], $action['occurred_at_utc'], $action['user_id']]);
    }
    $update = $pdo->prepare("UPDATE mobile_attendance_actions SET result = 'resolved', reason = ?, reviewed_by = ?, reviewed_at = UTC_TIMESTAMP(6) WHERE client_id = ?");
    $update->execute([$decision === 'apply' ? 'Admin applied mobile result' : 'Admin kept server result', $user['id'], $id]);
    $pdo->commit();
    if ($decision === 'apply') {
        try { syncAttendanceToSheets($action['event_id'], $action['attendee_id'], $action['requested_status'], $action['method']); }
        catch (Throwable $e) { error_log('Mobile resolution webhook error: ' . $e->getMessage()); }
    }
    mobileJson(200, ['success' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Mobile conflict resolution error: ' . $e->getMessage());
    mobileJson(500, ['success' => false, 'error' => 'Could not resolve conflict']);
}
