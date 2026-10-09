<?php
/** Church-scoped data for the native Android dashboard and management screens. */
require_once __DIR__ . '/../functions/mobile_api.php';
mobileMethod('GET');
set_exception_handler(function (Throwable $error) {
    error_log('Mobile portal read failed: ' . $error->getMessage());
    mobileJson(503, ['success' => false, 'error' => 'Church dashboard is temporarily unavailable']);
});
$user = mobileUser(['admin', 'operator', 'viewer']);
$pdo = getDB();
$church = $user['church'];

function portalRows($sql, $params) {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function portalScalar($sql, $params) {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

$profile = portalRows('SELECT id, fullname, username, role, church FROM users WHERE id=?', [$user['id']])[0];
$canManage = in_array($user['role'], ['admin', 'operator'], true);
$members = $canManage ? portalRows('SELECT id, fullname, category, ministry, contact, email, qr_token, status FROM attendees WHERE church=? ORDER BY fullname', [$church]) : [];
$events = portalRows('SELECT id, event_name, start_date, end_date, event_time, location, type, description, status FROM events WHERE church=? ORDER BY start_date DESC, event_time DESC LIMIT 1000', [$church]);
$categories = portalRows('SELECT name FROM categories WHERE church=? ORDER BY name', [$church]);
$stats = [
    'total_members' => portalScalar("SELECT COUNT(*) FROM attendees WHERE church=? AND status='Active'", [$church]),
    'today_event' => portalRows("SELECT event_name, event_time FROM events WHERE church=? AND CURDATE() BETWEEN start_date AND COALESCE(end_date,start_date) AND status IN ('Upcoming','Ongoing') ORDER BY event_time LIMIT 1", [$church])[0] ?? null,
    'present_30_days' => portalScalar("SELECT COUNT(*) FROM attendance_logs al JOIN events e ON e.id=al.event_id JOIN attendees a ON a.id=al.attendee_id WHERE e.church=? AND a.church=? AND e.start_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND al.status='Present'", [$church, $church]),
];
$recentEvents = portalScalar('SELECT COUNT(*) FROM events WHERE church=? AND start_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH) AND start_date <= CURDATE()', [$church]);
$rates = $recentEvents > 0 ? portalRows("SELECT a.id, COUNT(DISTINCT al.event_id) AS attended FROM attendees a LEFT JOIN attendance_logs al ON al.attendee_id=a.id AND al.status='Present' AND al.event_id IN (SELECT id FROM events WHERE church=? AND start_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH) AND start_date <= CURDATE()) WHERE a.church=? AND a.status='Active' GROUP BY a.id", [$church, $church]) : [];
$stats['consistent_count'] = 0;
$stats['at_risk_count'] = 0;
foreach ($rates as $rate) {
    $attendanceRate = (int)$rate['attended'] / $recentEvents;
    if ($attendanceRate >= 0.7) $stats['consistent_count']++;
    elseif ($attendanceRate <= 0.3) $stats['at_risk_count']++;
}
$trends = portalRows("SELECT DATE_FORMAT(e.start_date, '%Y-%m') AS month, COUNT(*) AS present_count FROM attendance_logs al JOIN events e ON e.id=al.event_id JOIN attendees a ON a.id=al.attendee_id WHERE e.church=? AND a.church=? AND e.start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND al.status='Present' GROUP BY DATE_FORMAT(e.start_date, '%Y-%m') ORDER BY month", [$church, $church]);
$categoryCounts = portalRows("SELECT category, COUNT(*) AS member_count FROM attendees WHERE church=? AND status='Active' GROUP BY category ORDER BY member_count DESC", [$church]);
$audit = portalRows('SELECT al.id, al.event_id, al.attendee_id, e.event_name, e.start_date, a.fullname, a.category, al.status, al.method, al.notes, al.log_time FROM attendance_logs al JOIN events e ON e.id=al.event_id JOIN attendees a ON a.id=al.attendee_id WHERE e.church=? AND a.church=? ORDER BY al.log_time DESC LIMIT 500', [$church, $church]);
$songs = $canManage ? portalRows('SELECT s.id, s.event_id, e.event_name, s.title, s.artist, s.lyrics, s.chords, s.sort_order FROM event_songs s JOIN events e ON e.id=s.event_id WHERE e.church=? ORDER BY e.start_date DESC, s.sort_order, s.id LIMIT 1000', [$church]) : [];
$stations = $canManage ? portalRows('SELECT s.id, s.event_id, e.event_name, s.station_name FROM event_stations s JOIN events e ON e.id=s.event_id WHERE e.church=? ORDER BY e.start_date DESC, s.id LIMIT 1000', [$church]) : [];
$assignments = $canManage ? portalRows('SELECT sa.id, sa.station_id, sa.member_id, a.fullname, a.category FROM event_station_assignments sa JOIN event_stations s ON s.id=sa.station_id JOIN events e ON e.id=s.event_id JOIN attendees a ON a.id=sa.member_id WHERE e.church=? AND a.church=? ORDER BY sa.id', [$church, $church]) : [];
$logs = [];
if ($user['role'] === 'admin') {
    $logs = portalRows('SELECT sl.id, sl.timestamp, sl.action, sl.details, sl.ip_address, u.fullname AS user_name FROM system_logs sl JOIN users u ON u.id=sl.user_id WHERE u.church=? ORDER BY sl.timestamp DESC LIMIT 200', [$church]);
}
mobileJson(200, [
    'success' => true,
    'fetched_at_utc' => gmdate('Y-m-d\TH:i:s\Z'),
    'profile' => $profile,
    'stats' => $stats,
    'trends' => $trends,
    'category_counts' => $categoryCounts,
    'members' => $members,
    'events' => $events,
    'categories' => $categories,
    'audit' => $audit,
    'songs' => $songs,
    'stations' => $stations,
    'assignments' => $assignments,
    'logs' => $logs,
]);
