<?php
/** Decide whether a mobile status can replace the downloaded attendance version. */
function mobileAttendanceDecision($currentStatus, $currentTimeUtc, $requestedStatus, $baseStatus, $baseTimeUtc) {
    if ($currentStatus === null) {
        return $baseStatus === null ? 'applied' : 'conflict';
    }
    if ($currentStatus === $requestedStatus) return 'duplicate';
    if ($baseStatus === $currentStatus && $baseTimeUtc !== null && $baseTimeUtc === $currentTimeUtc) {
        return 'applied';
    }
    return 'conflict';
}
