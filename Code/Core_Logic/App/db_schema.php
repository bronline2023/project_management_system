<?php
require_once __DIR__ . '/../../../config.php';
require_once MODELS_PATH . 'db.php';

try {
    $pdo = connectDB();
    echo "=== USERS ===\n";
    $stmt = $pdo->query("DESCRIBE users");
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
    echo "\n=== DIGITAL_SERVICE_RATES ===\n";
    $stmt = $pdo->query("DESCRIBE digital_service_rates");
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
