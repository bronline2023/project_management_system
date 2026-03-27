<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/db.php';

$pdo = connectDB();

$sql_history = "CREATE TABLE IF NOT EXISTS digital_service_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_slug VARCHAR(50) NOT NULL,
    service_name VARCHAR(255) NOT NULL,
    amount_deducted DECIMAL(10,2) DEFAULT 0,
    points_deducted INT DEFAULT 0,
    file_path VARCHAR(255),
    canvas_json LONGTEXT,
    is_draft TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    $pdo->exec($sql_history);
    echo "Table 'digital_service_history' created successfully.\n";
} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>
