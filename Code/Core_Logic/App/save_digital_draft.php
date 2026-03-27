<?php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once MODELS_PATH . 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Login required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$service_slug = $_POST['service_slug'] ?? '';
$service_name = $_POST['service_name'] ?? '';
$json = $_POST['json'] ?? '';

if (empty($service_slug) || empty($json)) {
    echo json_encode(['success' => false, 'error' => 'Missing JSON Data / Slug']);
    exit;
}

// Bypass MySQL max_allowed_packet Error 2006 by saving large JSONs locally
if (strlen($json) > 500000) { // If larger than 500KB
    $draft_dir = UPLOADS_PATH . 'drafts';
    if (!is_dir($draft_dir)) {
        mkdir($draft_dir, 0777, true);
    }
    $filename = 'draft_' . $user_id . '_' . time() . '.json';
    file_put_contents($draft_dir . '/' . $filename, $json);
    $json = 'FILE:' . $filename;
}

$pdo = connectDB();
try {
    $stmt = $pdo->prepare("INSERT INTO digital_service_history (user_id, service_slug, service_name, canvas_json, is_draft) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([$user_id, $service_slug, $service_name, $json]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
