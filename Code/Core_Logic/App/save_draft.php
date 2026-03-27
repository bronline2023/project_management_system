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
$image_data = $_POST['image_data'] ?? '';
$doc_type = $_POST['document_type'] ?? 'General Output';

if (empty($image_data)) {
    echo json_encode(['success' => false, 'error' => 'No image data']);
    exit;
}

// Strip data:image/...;base64,
if (preg_match('/^data:image\/(\w+);base64,/', $image_data, $type)) {
    $image_data = substr($image_data, strpos($image_data, ',') + 1);
    $type = strtolower($type[1]); // jpg, png, etc.
} else {
    // Fallback if no prefix but data exists
    $type = 'png';
}

$image_data = base64_decode($image_data);
if ($image_data === false) {
    echo json_encode(['success' => false, 'error' => 'Base64 decode failed']);
    exit;
}

$upload_dir = UPLOADS_PATH . 'history/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

$file_name = 'history_' . $user_id . '_' . time() . '.' . $type;
$file_path = $upload_dir . $file_name;
file_put_contents($file_path, $image_data);

$web_path = UPLOADS_DIR_RELATIVE . 'history/' . $file_name;

$pdo = connectDB();
try {
    $stmt = $pdo->prepare("INSERT INTO digital_service_history (user_id, service_slug, service_name, file_path, is_draft) VALUES (?, 'manual_export', ?, ?, 0)");
    $stmt->execute([$user_id, $doc_type, $web_path]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
