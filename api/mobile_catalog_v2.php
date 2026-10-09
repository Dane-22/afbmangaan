<?php
require_once __DIR__ . '/../functions/mobile_api.php';
require_once __DIR__ . '/../functions/mobile_event_catalog.php';
mobileMethod('POST');
$user = mobileUser();
$input = mobileInput();
set_exception_handler(function (Throwable $error) {
    error_log('Catalog download failed: ' . $error->getMessage());
    mobileJson(503, ['success'=>false,'error'=>'Event download is temporarily unavailable']);
});
$pdo = getDB();
$pdo->exec("SET time_zone='+00:00'");
$pdo->beginTransaction();
$events = mobileChurchEvents($user['church']);
$cutoff = (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->modify('-30 days')->format('Y-m-d');
$ids = [];
foreach ($events as $event) {
    if (!in_array($event['status'], ['Cancelled','Archived'], true) && ($event['end_date'] ?: $event['start_date']) >= $cutoff) $ids[] = (int)$event['id'];
}
$members = mobileCatalogMembers($user['church']);
$attendance = mobileCatalogAttendance($user['church'], $ids);
$stmt = $pdo->prepare("SELECT client_id,result,reason FROM mobile_attendance_actions WHERE user_id=? AND result='resolved' ORDER BY reviewed_at DESC LIMIT 500");
$stmt->execute([$user['id']]);
$reviews = $stmt->fetchAll();
$pdo->commit();
mobileCatalogPages(['events'=>$events,'members'=>$members,'attendance'=>$attendance,'downloaded_event_ids'=>$ids,'resolved_actions'=>$reviews], $input);
