<?php
require_once __DIR__ . '/../functions/mobile_api.php';
mobileMethod('GET');
$user = mobileUser();
$pdo = getDB();
$pdo->exec("SET time_zone = '+00:00'");
$events = $pdo->prepare("SELECT id, event_name, start_date, end_date, event_time, location, status FROM events WHERE church = ? AND status IN ('Upcoming', 'Ongoing', 'Completed') AND start_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ORDER BY start_date DESC, event_time DESC");
$events->execute([$user['church']]);
$members = $pdo->prepare("SELECT id, fullname, category, qr_token FROM attendees WHERE church = ? AND status = 'Active' ORDER BY fullname");
$members->execute([$user['church']]);
$attendance = $pdo->prepare("SELECT al.event_id, al.attendee_id, al.status, al.log_time FROM attendance_logs al JOIN events e ON e.id = al.event_id JOIN attendees a ON a.id = al.attendee_id WHERE e.church = ? AND a.church = ? AND e.start_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$attendance->execute([$user['church'], $user['church']]);
$records = $attendance->fetchAll();
$reviews = $pdo->prepare("SELECT client_id, result, reason FROM mobile_attendance_actions WHERE user_id = ? AND result = 'resolved' ORDER BY reviewed_at DESC LIMIT 500");
$reviews->execute([$user['id']]);
foreach ($records as &$record) {
    $record['event_id'] = (int)$record['event_id'];
    $record['attendee_id'] = (int)$record['attendee_id'];
    $record['log_time_utc'] = mobileIso($record['log_time']);
    unset($record['log_time']);
}
unset($record);
mobileJson(200, [
    'success' => true,
    'fetched_at_utc' => gmdate('Y-m-d\TH:i:s\Z'),
    'events' => $events->fetchAll(),
    'members' => $members->fetchAll(),
    'attendance' => $records,
    'resolved_actions' => $reviews->fetchAll()
]);
