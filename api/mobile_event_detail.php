<?php
require_once __DIR__ . '/../functions/mobile_api.php';
require_once __DIR__ . '/../functions/mobile_event_catalog.php';
mobileMethod('POST');
$user = mobileUser();
$input = mobileInput();
$id = filter_var($input['event_id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) mobileJson(400, ['success'=>false,'error'=>'Invalid event']);
set_exception_handler(function (Throwable $error) {
    error_log('Event detail download failed: ' . $error->getMessage());
    mobileJson(503, ['success'=>false,'error'=>'Attendance detail download is temporarily unavailable']);
});
$pdo = getDB();
$pdo->exec("SET time_zone='+00:00'");
$pdo->beginTransaction();
$events = array_values(array_filter(mobileChurchEvents($user['church']), fn($event) => (int)$event['id'] === $id));
if (!$events) { $pdo->rollBack(); mobileJson(404, ['success'=>false,'error'=>'Event not found in this church']); }
$members = mobileCatalogMembers($user['church']);
$attendance = mobileCatalogAttendance($user['church'], [$id]);
$pdo->commit();
mobileCatalogPages(['events'=>$events,'members'=>$members,'attendance'=>$attendance,'downloaded_event_ids'=>[$id]], $input);
