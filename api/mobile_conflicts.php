<?php
require_once __DIR__ . '/../functions/mobile_api.php';
mobileMethod('GET');
$user = mobileUser(['admin']);
$pdo = getDB();
$pdo->exec("SET time_zone = '+00:00'");
$stmt = $pdo->prepare("SELECT m.client_id, m.event_id, e.event_name, m.attendee_id, a.fullname, m.requested_status, m.method, m.occurred_at_utc, m.reason, al.status AS current_status, al.log_time AS current_log_time_utc FROM mobile_attendance_actions m LEFT JOIN events e ON e.id = m.event_id LEFT JOIN attendees a ON a.id = m.attendee_id LEFT JOIN attendance_logs al ON al.event_id = m.event_id AND al.attendee_id = m.attendee_id WHERE m.church = ? AND m.result = 'conflict' ORDER BY m.received_at ASC LIMIT 200");
$stmt->execute([$user['church']]);
$conflicts = $stmt->fetchAll();
foreach ($conflicts as &$conflict) {
    $conflict['occurred_at_utc'] = mobileIso($conflict['occurred_at_utc']);
    $conflict['current_log_time_utc'] = mobileIso($conflict['current_log_time_utc']);
}
unset($conflict);
mobileJson(200, ['success' => true, 'conflicts' => $conflicts]);
