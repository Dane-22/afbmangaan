<?php
// Runs only against the disposable database created by GitHub Actions.
if (getenv('CI') !== 'true' || getenv('DB_NAME') !== 'afb_mobile_ci') {
    fwrite(STDERR, "Use the isolated afb_mobile_ci database in CI only.\n");
    exit(1);
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../functions/jwt_auth.php';
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
if (($argv[1] ?? '') === 'seed') {
    // Extract table definitions only. Never import existing users or attendance.
    foreach (['afb_mangaan_db (3).sql', 'schema_update.sql', 'mobile_schema.sql'] as $file) {
        preg_match_all('/CREATE TABLE IF NOT EXISTS.*?;\s*/s', file_get_contents(__DIR__ . '/../' . $file), $matches);
        foreach ($matches[0] as $sql) $pdo->exec($sql);
    }
    $pdo->exec("ALTER TABLE events MODIFY status ENUM('Upcoming','Ongoing','Completed','Cancelled','Archived') DEFAULT 'Upcoming'");
    $stmt = $pdo->prepare('INSERT INTO users (id,church,username,password,fullname,role,status,must_change_password) VALUES (?,?,?,?,?,?,?,0)');
    foreach ([[1,'AFB Mangaan','admin'],[2,'AFB Mangaan','viewer'],[3,'AFB Lettac Sur','admin'],[4,'AFB Mangaan','operator']] as [$id,$church,$role]) {
        $stmt->execute([$id,$church,'ci'.$id,password_hash('CI-only-password', PASSWORD_BCRYPT),'CI User '.$id,$role,'Active']);
    }
    $pdo->exec("INSERT INTO attendees (id,church,fullname,category,qr_token,status) VALUES (1,'AFB Mangaan','CI Member A','WMO','CI-A','Active'),(2,'AFB Lettac Sur','CI Member B','WMO','CI-B','Active')");
    $pdo->exec("INSERT INTO events (id,church,event_name,start_date,end_date,status) VALUES (1,'AFB Mangaan','CI Event A',CURDATE(),CURDATE(),'Completed'),(2,'AFB Lettac Sur','CI Event B',CURDATE(),CURDATE(),'Completed')");
    $pdo->exec("INSERT INTO attendance_logs (attendee_id,event_id,status,method,logged_by) VALUES (1,1,'Present','Manual',1),(2,2,'Present','Manual',3)");
    $pdo->exec("INSERT INTO categories (church,name) VALUES ('AFB Mangaan','WMO'),('AFB Lettac Sur','WMO')");
    echo "CI fixture ready\n";
    exit;
}
function request($endpoint, $user, $body = null, $expected = 200) {
    $headers = "Content-Type: application/json\r\n";
    if ($user !== null) $headers .= 'Authorization: Bearer ' . generateJwt($user) . "\r\n";
    $context = stream_context_create(['http' => ['method' => $body === null ? 'GET' : 'POST', 'header' => $headers, 'content' => $body === null ? '' : json_encode($body), 'ignore_errors' => true, 'timeout' => 10]]);
    $raw = file_get_contents('http://127.0.0.1:8099/api/' . $endpoint . '.php', false, $context);
    preg_match('/\s(\d{3})\s/', $http_response_header[0] ?? '', $status);
    check((int)($status[1] ?? 0) === $expected, "$endpoint: expected $expected, got " . ($status[1] ?? 'none') . ': ' . $raw);
    $json = json_decode($raw, true);
    check(is_array($json), "$endpoint did not return JSON: $raw");
    return $json;
}
request('mobile_portal', null, null, 401);
$portal = request('mobile_portal', 1);
check(count($portal['members']) === 1 && (int)$portal['members'][0]['id'] === 1, 'Member church isolation failed');
check(count($portal['events']) === 1 && (int)$portal['events'][0]['id'] === 1, 'Event church isolation failed');
$viewer = request('mobile_portal', 2);
check($viewer['members'] === [] && $viewer['logs'] === [], 'Viewer received management data');
$save = ['resource'=>'members','action'=>'save','fullname'=>'Created Member','category'=>'WMO','status'=>'Active'];
request('mobile_portal_action', 2, $save, 403);
request('mobile_portal_action', 1, $save + ['id'=>2], 404);
request('mobile_portal_action', 4, $save);
request('mobile_portal_action', 1, ['resource'=>'events','action'=>'status','id'=>2,'status'=>'Cancelled'], 404);
request('mobile_portal_action', 1, ['resource'=>'events','action'=>'status','id'=>1,'status'=>'Archived']);
request('mobile_portal_action', 1, ['resource'=>'events','action'=>'status','id'=>1,'status'=>'Completed']);
$report = request('mobile_report', 2, ['mode'=>'report']);
check((int)$report['summary']['present'] === 1 && count($report['rows']) === 2, 'Report summary or scope failed');
foreach ($report['rows'] as $row) check((int)$row['event_id'] === 1 && $row['fullname'] !== 'CI Member B', 'Cross-church report leak');
request('mobile_report', 2, ['mode'=>'audit'], 403);
$audit = request('mobile_report', 4, ['mode'=>'audit','export'=>true]);
check(count($audit['rows']) === 1, 'Audit export scope failed');
request('mobile_report', 1, ['from_date'=>'invalid'], 400);
$roomA = request('chat', 1, ['action'=>'create_room','name'=>'CI Church A'])['room']['id'];
$roomB = request('chat', 3, ['action'=>'create_room','name'=>'CI Church B'])['room']['id'];
request('chat', 1, ['action'=>'get_messages','room_id'=>$roomB], 404);
request('chat', 1, ['action'=>'send_message','room_id'=>$roomB,'message'=>'Rejected'], 404);
request('chat', 1, ['action'=>'send_message','room_id'=>$roomA,'message'=>'CI test message']);
$messages = request('chat', 2, ['action'=>'get_messages','room_id'=>$roomA]);
check(count($messages['messages']) === 2, 'Same church chat failed');
request('ai_assistant', 2, ['query'=>'record attendance CI Member A'], 403);
request('ai_assistant', 1, ['query'=>['invalid']], 400);
request('ai_assistant', 1, ['query'=>'open dashboard']);
// Shared report engine also uses start_date and integer LIMIT bindings.
$_SESSION = ['church'=>'AFB Mangaan'];
require_once __DIR__ . '/../functions/report_engine.php';
check(count(getAttendanceReport()) === 2, 'Web report scope failed');
check(count(getMemberAttendanceHistory(1)) === 1, 'Web member history failed');
check(count(getMemberAttendanceHistory(2)) === 0, 'Web history church isolation failed');
check(count(getTopAttendees()) === 1, 'Web top attendee query failed');
getReportSummary();
getMonthlyComparison();
// Historical metadata must remain discoverable; its detail download is explicit.
$stmt = $pdo->prepare('INSERT INTO events (id,church,event_name,start_date,end_date,status) VALUES (?,\'AFB Mangaan\',?,\'2026-02-05\',\'2026-02-26\',?)');
foreach ([60=>'Upcoming',61=>'Ongoing',62=>'Completed',63=>'Cancelled',64=>'Archived'] as $id=>$status) $stmt->execute([$id,'Historical '.$status,$status]);
$pdo->exec("INSERT INTO attendance_logs (attendee_id,event_id,status,method,logged_by) VALUES (1,61,'Present','Manual',1)");
$catalog = request('mobile_catalog_v2', 1, ['page'=>1]);
$portal = request('mobile_portal', 1);
check(array_column($catalog['events'],'id') === array_column($portal['events'],'id'), 'Catalog and portal discovery differ');
check(count($catalog['events']) === 6, 'Historical event metadata was excluded');
check(!in_array(61, $catalog['downloaded_event_ids']), 'Old attendance should require an explicit download');
$detail = request('mobile_event_detail', 1, ['event_id'=>61]);
check(count($detail['attendance']) === 1 && $detail['attendance'][0]['status'] === 'Present', 'Historical attendance falsely missing');
request('mobile_event_detail', 1, ['event_id'=>2], 404);
request('mobile_catalog_v2', 2, ['page'=>1], 403);
request('mobile_event_detail', 2, ['event_id'=>61], 403);
// Long-running events are downloaded automatically even if their start is old.
$pdo->exec("UPDATE events SET end_date=CURDATE() WHERE id=61");
$catalog = request('mobile_catalog_v2', 1, ['page'=>1]);
check(in_array(61, $catalog['downloaded_event_ids']), 'Long-running event details were excluded');
check(count($catalog['attendance']) === 2, 'Automatic attendance snapshot incomplete');
for ($id=100; $id<205; $id++) $stmt->execute([$id,'Pagination '.$id,'Upcoming']);
$page1 = request('mobile_catalog_v2', 1, ['page'=>1]);
$page2 = request('mobile_catalog_v2', 1, ['page'=>2,'snapshot'=>$page1['snapshot']]);
check(count($page1['events']) === 100 && count($page2['events']) === 11, 'Event pagination omitted metadata');
$pdo->exec("UPDATE events SET event_name='Changed during download' WHERE id=100");
request('mobile_catalog_v2', 1, ['page'=>2,'snapshot'=>$page1['snapshot']], 409);
request('mobile_catalog_v2', 1, ['page'=>999], 400);
echo "Mobile portal integration checks passed\n";
