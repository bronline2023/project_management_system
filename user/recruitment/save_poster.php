<?php
/**
 * user/recruitment/save_poster.php
 * Handles the AJAX request from generate_poster.php to save the image.
 */

// This needs to be a standalone script, so it requires its own config.
require_once dirname(dirname(__DIR__)) . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['imageData']) || empty($data['imageData'])) {
    echo json_encode(['success' => false, 'message' => 'No image data.']);
    exit;
}

$imageData = str_replace('data:image/jpeg;base64,', '', $data['imageData']);
$imageData = base64_decode($imageData);

if ($imageData === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to decode image data.']);
    exit;
}

$uploadDir = ROOT_PATH . 'post/generated_posters/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
        exit;
    }
}

$fileName = 'poster_' . uniqid() . '.jpg';
$filePath = $uploadDir . $fileName;

if (file_put_contents($filePath, $imageData)) {
    $imageUrl = BASE_URL . 'post/generated_posters/' . $fileName;
    echo json_encode(['success' => true, 'imageUrl' => $imageUrl]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save image.']);
}
?>