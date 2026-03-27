<?php
ini_set('display_errors', 1);
require_once 'config.php';
require_once MODELS_PATH . 'db.php';
try {
    $pdo = connectDB();
    $stmt = $pdo->query('SELECT * FROM digital_service_rates');
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo "DB Error: " . $e->getMessage();
}
