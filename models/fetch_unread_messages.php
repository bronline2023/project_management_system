<?php
/**
 * models/fetch_unread_messages.php
 *
 * This endpoint fetches the number of unread messages for the logged-in user.
 * It's designed to be called via AJAX (fetch API) from the client-side.
 */

// Include necessary configuration and database functions
require_once __DIR__ . '/../config.php';

// CORRECTED: db.php must be included BEFORE auth.php because auth.php depends on it.
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';

header('Content-Type: application/json'); // Set header to indicate JSON response

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$currentUserId = $_SESSION['user_id'] ?? null;

if (!$currentUserId) {
    echo json_encode(['success' => false, 'message' => 'User ID not found in session.']);
    exit;
}

try {
    $pdo = connectDB();
    $unreadCount = getUnreadMessageCount($pdo, $currentUserId);
    echo json_encode(['success' => true, 'unread_count' => $unreadCount]);
} catch (Exception $e) {
    error_log("Error in fetch_unread_messages.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error fetching unread messages.']);
}
?>