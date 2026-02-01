<?php
/**
 * models/fetch_notifications.php
 * AJAX endpoint to fetch and clear unread notifications for the logged-in user.
 */

require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';
require_once MODELS_PATH . 'notifications.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$notifications = getUnreadNotifications($userId);

// Mark fetched notifications as read
foreach ($notifications as $notification) {
    markNotificationAsRead($notification['id'], $userId);
}

echo json_encode(['success' => true, 'notifications' => $notifications]);
?>