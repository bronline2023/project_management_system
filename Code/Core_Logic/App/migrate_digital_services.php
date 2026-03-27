<?php
require_once __DIR__ . '/../../../config.php';
require_once MODELS_PATH . 'db.php';

$pdo = connectDB();

$sql = "
CREATE TABLE IF NOT EXISTS digital_service_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_slug VARCHAR(100) NOT NULL UNIQUE,
    service_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1
);

INSERT IGNORE INTO digital_service_rates (service_slug, service_name, price) VALUES 
('poster_studio', 'Poster Design Studio', 10),
('resume_builder', 'Resume Builder', 20),
('smart_card', 'Smart Card Creator', 15),
('passport_photo', 'Passport Photo Maker', 5),
('document_converter', 'Document Converter', 5),
('size_converter', 'Size Converter', 2),
('photo_studio', 'Photo Studio Pro', 25);

CREATE TABLE IF NOT EXISTS b2c_subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    validity_days INT NOT NULL DEFAULT 30,
    allowed_services JSON,
    is_active TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS user_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('active','expired','cancelled') DEFAULT 'active'
);
";

try {
    $pdo->exec($sql);
    echo "Successfully created digital service tables and inserted default rates.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
