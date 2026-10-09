<?php
require_once __DIR__ . '/../functions/mobile_sync_rules.php';

function expectDecision($expected, $currentStatus, $currentTime, $requestedStatus, $baseStatus, $baseTime) {
    $actual = mobileAttendanceDecision($currentStatus, $currentTime, $requestedStatus, $baseStatus, $baseTime);
    if ($actual !== $expected) throw new RuntimeException("Expected $expected, got $actual");
}

$time1 = '2026-10-09 01:00:00.000000';
$time2 = '2026-10-09 01:01:00.000000';
expectDecision('applied', null, null, 'Present', null, null);
expectDecision('duplicate', 'Present', $time1, 'Present', null, null);
expectDecision('conflict', 'Present', $time1, 'Absent', null, null);
expectDecision('applied', 'Present', $time1, 'Absent', 'Present', $time1);
expectDecision('conflict', 'Present', $time2, 'Absent', 'Present', $time1);
expectDecision('conflict', null, null, 'Present', 'Absent', $time1);
echo "Mobile sync rules passed\n";
