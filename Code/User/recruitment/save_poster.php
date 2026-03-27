<?php
/**
 * user/recruitment/save_poster.php
 * 100% FIXED: Returns Full Absolute URL for <input type="url"> compatibility
 */

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(dirname(dirname(__DIR__))) . '/config.php';
require_once MODELS_PATH . 'db.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }
$currentUserId = $_SESSION['user_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

if (!$currentUserId) {
    echo json_encode(['success' => false, 'message' => 'Session Expired. Please login again.']);
    exit;
}

$pdo = connectDB();

// --- 💳 B2C WALLET DEDUCTION LOGIC ---
if (!IS_TENANT) {
    $service_cost = 10.00; // Fixed cost in points/₹
    $balance = (float)fetchColumn($pdo, "SELECT balance FROM users WHERE id = ?", [$currentUserId]);
    
    if ($balance < $service_cost) {
        echo json_encode(['success' => false, 'message' => "Insufficient Wallet Balance! You need at least ₹$service_cost to generate a poster."]);
        exit;
    }
    
    // Deduct balance and add transaction log
    $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")->execute([$service_cost, $currentUserId]);
    $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, 'debit', ?, 'Generated Custom Poster (Digital Service)')")->execute([$currentUserId, $service_cost]);
}
// -------------------------------------

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (empty($data['imageData'])) {
    echo json_encode(['success' => false, 'message' => 'Image Data is missing.']);
    exit;
}

// Decode Base64 image data
$imageData = preg_replace('#^data:image/\w+;base64,#i', '', $data['imageData']);
$decodedData = base64_decode($imageData);

if ($decodedData === false) {
    echo json_encode(['success' => false, 'message' => 'Base64 Decode Failed.']);
    exit;
}

// Correct folder path
$uploadDir = UPLOADS_PATH . 'generated_posters/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

$fileName = 'poster_' . time() . '_' . rand(1000, 9999) . '.jpg';
$filePath = $uploadDir . $fileName;

if (file_put_contents($filePath, $decodedData)) {
    // Generate 100% full (Absolute) link for browser
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    
    // Set root path
    $basePath = preg_replace('/user\/recruitment\/save_poster\.php$/i', '', $scriptName);
    $basePath = rtrim($basePath, '/') . '/';
    
    // Final full link (https://bronline.net/uploads/...)
    $fullUrl = $protocol . $host . $basePath . UPLOADS_DIR_RELATIVE . 'generated_posters/' . $fileName;
    
    echo json_encode(['success' => true, 'imageUrl' => $fullUrl]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save image. Check folder permissions.']);
}
exit;
?>