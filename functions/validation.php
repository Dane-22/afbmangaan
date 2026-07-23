<?php
/**
 * Validation Functions
 * AFB Mangaan Attendance System
 */

function validateInteger($value, $min = null, $max = null) {
    if (!is_numeric($value)) return false;
    $int = (int)$value;
    if ($min !== null && $int < $min) return false;
    if ($max !== null && $int > $max) return false;
    return true;
}

function validateString($value, $minLength = 0, $maxLength = 255) {
    if (!is_string($value)) return false;
    $len = strlen($value);
    return $len >= $minLength && $len <= $maxLength;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
