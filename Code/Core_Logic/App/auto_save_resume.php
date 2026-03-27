<?php
session_start();
require_once __DIR__ . '/../Config/init.php';
require_once MODELS_PATH . 'db.php';

$pdo = connectDB();

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$draftJson = json_encode($data);
$filepath = null;

if (strlen($draftJson) > 10000) {
    $filename = 'draft_' . $userId . '_' . time() . '.json';
    $filepath = UPLOADS_PATH . 'drafts/' . $filename;
    
    if (file_put_contents($filepath, $draftJson)) {
        $draftJson = 'FILE:' . $filename;
    }
}

try {
    $stmt = $pdo->prepare("REPLACE INTO digital_service_history 
        (user_id, service_type, canvas_json, is_draft, created_at) 
        VALUES (?, ?, ?, 1, NOW())");
    $stmt->execute([$userId, 'Resume Builder', $draftJson]);
    
    echo json_encode(['success' => true, 'draft_id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
