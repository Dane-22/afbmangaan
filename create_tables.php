<?php
require_once __DIR__ . '/config/db.php';
$sql = "
CREATE TABLE IF NOT EXISTS `event_songs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `event_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `artist` VARCHAR(255) DEFAULT NULL,
  `lyrics` TEXT,
  `chords` TEXT,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `event_stations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `event_id` INT NOT NULL,
  `station_name` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `event_station_assignments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `station_id` INT NOT NULL,
  `member_id` INT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`station_id`) REFERENCES `event_stations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `attendees`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_station_member` (`station_id`, `member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
try {
    $pdo->exec($sql);
    echo "Tables created successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
