<?php
/** Filtered, paginated reports and attendance audit for Android. */
require_once __DIR__ . '/../functions/mobile_api.php';
mobileMethod('POST');
$input = mobileInput();
$mode = $input['mode'] ?? 'report';
if (!in_array($mode, ['report', 'audit'], true)) mobileJson(400, ['success' => false, 'error' => 'Invalid report mode']);
$user = mobileUser($mode === 'audit' ? ['admin', 'operator'] : ['admin', 'operator', 'viewer']);
set_exception_handler(function (Throwable $error) {
    error_log('Mobile report failed: ' . $error->getMessage());
    mobileJson(503, ['success' => false, 'error' => 'Report is temporarily unavailable']);
});

function reportDateInput($input, $key, $fallback) {
    $value = $input[$key] ?? $fallback;
    if (!is_string($value)) mobileJson(400, ['success' => false, 'error' => 'Invalid date']);
    if ($value === '') $value = $fallback;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) mobileJson(400, ['success' => false, 'error' => 'Enter a valid date']);
    return $value;
}
function reportQuery($sql, $params) {
    $stmt = getDB()->prepare($sql);
    foreach ($params as $i => $value) $stmt->bindValue($i + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll();
}

$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$from = reportDateInput($input, 'from_date', $today->modify('-30 days')->format('Y-m-d'));
$to = reportDateInput($input, 'to_date', $today->format('Y-m-d'));
if ($to < $from) mobileJson(400, ['success' => false, 'error' => 'End date must follow start date']);
$eventId = null;
if (isset($input['event_id']) && $input['event_id'] !== '') {
    $eventId = filter_var($input['event_id'], FILTER_VALIDATE_INT);
    if (!$eventId || $eventId < 1) mobileJson(400, ['success' => false, 'error' => 'Invalid event']);
}
$category = $input['category'] ?? '';
$search = $input['search'] ?? '';
if (!is_string($category) || !is_string($search) || strlen($category) > 100 || strlen($search) > 200) mobileJson(400, ['success' => false, 'error' => 'Invalid filter']);
$page = max(1, min(100000, (int)($input['page'] ?? 1)));
$perPage = 50;
$params = [$user['church'], $user['church'], $from, $to];
if ($mode === 'report') {
    $base = " FROM attendees a CROSS JOIN events e LEFT JOIN attendance_logs al ON al.attendee_id=a.id AND al.event_id=e.id WHERE a.church=? AND e.church=? AND e.start_date BETWEEN ? AND ? AND a.status='Active' AND e.status='Completed'";
} else {
    $base = ' FROM attendance_logs al JOIN attendees a ON a.id=al.attendee_id JOIN events e ON e.id=al.event_id WHERE a.church=? AND e.church=? AND e.start_date BETWEEN ? AND ?';
}
if ($eventId !== null) { $base .= ' AND e.id=?'; $params[] = $eventId; }
if ($category !== '') { $base .= ' AND a.category=?'; $params[] = $category; }
if ($search !== '') {
    $base .= ' AND (a.fullname LIKE ? OR a.qr_token LIKE ? OR e.event_name LIKE ?)';
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
$status = $input['status'] ?? '';
if (!is_string($status) || !in_array($status, ['', 'Present', 'Absent'], true)) mobileJson(400, ['success' => false, 'error' => 'Invalid status']);
if ($status !== '') { $base .= ' AND al.status=?'; $params[] = $status; }
$summary = reportQuery("SELECT COUNT(*) AS total_records, COUNT(DISTINCT e.id) AS total_events, COALESCE(SUM(al.status='Present'),0) AS present, COALESCE(SUM(al.status='Absent'),0) AS absent, COALESCE(SUM(al.id IS NULL),0) AS not_recorded, COUNT(DISTINCT CASE WHEN al.status='Present' THEN a.id END) AS unique_attendees" . $base, $params)[0];
$total = (int)$summary['total_records'];
$export = ($input['export'] ?? false) === true;
if ($export && $total > 50000) mobileJson(413, ['success' => false, 'error' => 'Narrow the report filters to export fewer than 50,000 rows']);
$limit = $export ? 50000 : $perPage;
$offset = $export ? 0 : ($page - 1) * $perPage;
$rows = reportQuery("SELECT al.id, a.id AS attendee_id, e.id AS event_id, a.fullname, a.category, a.contact, a.email, a.qr_token, e.event_name, e.start_date, e.type, COALESCE(al.status,'Not Recorded') AS status, al.method, al.notes, al.log_time" . $base . ' ORDER BY e.start_date DESC, e.id, a.fullname LIMIT ? OFFSET ?', array_merge($params, [$limit, $offset]));
$top = reportQuery("SELECT a.fullname, a.category, COUNT(DISTINCT e.id) AS attended" . $base . " AND al.status='Present' GROUP BY a.id, a.fullname, a.category ORDER BY attended DESC, a.fullname LIMIT 10", $params);
$monthly = reportQuery("SELECT DATE_FORMAT(e.start_date,'%Y-%m') AS month, COUNT(DISTINCT e.id) AS events, COALESCE(SUM(al.status='Present'),0) AS present_count" . $base . " GROUP BY DATE_FORMAT(e.start_date,'%Y-%m') ORDER BY month", $params);
mobileJson(200, ['success' => true, 'summary' => $summary, 'rows' => $rows, 'top_attendees' => $top, 'monthly' => $monthly, 'page' => $page, 'total_pages' => max(1, (int)ceil($total / $perPage)), 'from_date' => $from, 'to_date' => $to]);
