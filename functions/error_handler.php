<?php
/**
 * Error Handler Functions
 * AFB Mangaan Attendance System
 */

function sendErrorResponse($message, $code = 400, $details = null) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'code' => $code,
        'details' => $details
    ]);
    exit;
}

function sendSuccessResponse($data = null) {
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    exit;
}
