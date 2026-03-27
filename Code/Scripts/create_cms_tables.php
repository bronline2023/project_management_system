<?php
require_once '../../config.php';
require_once 'models/db.php';
$pdo = connectDB();

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS `custom_pages` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `slug` varchar(255) NOT NULL,
      `title` varchar(255) NOT NULL,
      `content` longtext DEFAULT NULL,
      `status` enum('active','inactive') DEFAULT 'active',
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);
    echo "Table custom_pages created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>
