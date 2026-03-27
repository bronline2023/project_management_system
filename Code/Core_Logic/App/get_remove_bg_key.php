<?php
require_once __DIR__ . '/../../../config.php';
require_once MODELS_PATH . 'db.php';

header('Content-Type: application/json');

$pdo = connectDB();

try {
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'remove_bg_api_key'");
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($setting) {
        echo json_encode(['api_key' => $setting['setting_value']]);
    }
    else {
        echo json_encode(['api_key' => '']);
    }
}
catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>