<?php
/** Shared church event discovery. Visibility is independent of recording eligibility. */
function mobileChurchEvents($church) {
    $stmt = getDB()->prepare('SELECT id,event_name,start_date,end_date,event_time,location,type,description,status FROM events WHERE church=? ORDER BY start_date DESC,event_time DESC,id DESC');
    $stmt->execute([$church]);
    return $stmt->fetchAll();
}
function mobileCatalogMembers($church) {
    $stmt = getDB()->prepare("SELECT id,fullname,category,qr_token FROM attendees WHERE church=? AND status='Active' ORDER BY id");
    $stmt->execute([$church]);
    return $stmt->fetchAll();
}
function mobileCatalogAttendance($church, $eventIds) {
    if (!$eventIds) return [];
    $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
    $stmt = getDB()->prepare("SELECT al.event_id,al.attendee_id,al.status,al.log_time FROM attendance_logs al JOIN events e ON e.id=al.event_id JOIN attendees a ON a.id=al.attendee_id WHERE e.church=? AND a.church=? AND e.id IN ($placeholders) ORDER BY al.event_id,al.attendee_id");
    $stmt->execute(array_merge([$church,$church], $eventIds));
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['event_id'] = (int)$row['event_id'];
        $row['attendee_id'] = (int)$row['attendee_id'];
        $row['log_time_utc'] = mobileIso($row['log_time']);
        unset($row['log_time']);
    }
    return $rows;
}
function mobileCatalogPages($payload, $input) {
    $page = filter_var($input['page'] ?? 1, FILTER_VALIDATE_INT);
    if (!$page || $page < 1) mobileJson(400, ['success'=>false,'error'=>'Invalid catalog page']);
    $snapshot = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    if (isset($input['snapshot']) && $input['snapshot'] !== $snapshot) mobileJson(409, ['success'=>false,'error'=>'Church data changed during download. Retry.']);
    $pages = max(1, (int)ceil(count($payload['events'])/100), (int)ceil(count($payload['members'])/500), (int)ceil(count($payload['attendance'])/1000));
    if ($page > $pages) mobileJson(400, ['success'=>false,'error'=>'Catalog page is out of range']);
    foreach (['events'=>100,'members'=>500,'attendance'=>1000] as $key=>$size) $payload[$key] = array_slice($payload[$key], ($page-1)*$size, $size);
    mobileJson(200, ['success'=>true,'version'=>2,'snapshot'=>$snapshot,'page'=>$page,'total_pages'=>$pages,'fetched_at_utc'=>gmdate('Y-m-d\TH:i:s\Z')] + $payload);
}
