-- Apply once to the existing MySQL database before using the Android app.
-- Every mobile submission is retained, including duplicates and conflicts.
CREATE TABLE IF NOT EXISTS mobile_attendance_actions (
    client_id CHAR(36) NOT NULL,
    church VARCHAR(150) NOT NULL,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    attendee_id INT NOT NULL,
    requested_status ENUM('Present', 'Absent') NOT NULL,
    method ENUM('Manual', 'QR Scan', 'Search') NOT NULL,
    occurred_at_utc DATETIME(6) NOT NULL,
    base_status ENUM('Present', 'Absent') NULL,
    base_log_time_utc DATETIME(6) NULL,
    result ENUM('applied', 'duplicate', 'conflict', 'rejected', 'resolved') NOT NULL,
    reason VARCHAR(255) NULL,
    reviewed_by INT NULL,
    reviewed_at DATETIME(6) NULL,
    received_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (client_id),
    KEY idx_mobile_actions_church_result (church, result, received_at),
    KEY idx_mobile_actions_event_attendee (event_id, attendee_id),
    KEY idx_mobile_actions_user_result (user_id, result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mobile_login_attempts (
    id BIGINT NOT NULL AUTO_INCREMENT,
    key_hash CHAR(64) NOT NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mobile_login_attempts (key_hash, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
