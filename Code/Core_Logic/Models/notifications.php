<?php
/**
 * models/notifications.php
 * Handles notification-related database operations.
 * FINAL & COMPLETE: This model is fully functional.
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

/**
 * Adds a new notification for a specific user.
 *
 * @param int    $userId  The ID of the user to notify.
 * @param string $message The notification message.
 * @param string $link    (Optional) The link to navigate to when the notification is clicked.
 * @return bool True on success, false on failure.
 */
function addNotification($userId, $message, $link = '#') {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $message, $link]);
    } catch (PDOException $e) {
        // It's often better to log this error than to display it to the user.
        error_log("Notification Error: " . $e->getMessage());
        return false;
    }
}


/**
 * Fetches all unread notifications for a given user.
 *
 * @param int $userId The ID of the user.
 * @return array An array of unread notifications.
 */
function getUnreadNotifications($userId) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Marks a specific notification as read.
 *
 * @param int $notificationId The ID of the notification.
 * @return bool True on success, false on failure.
 */
function markNotificationAsRead($notificationId) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    return $stmt->execute([$notificationId]);
}

/**
 * Marks all unread notifications for a user as read.
 *
 * @param int $userId The ID of the user.
 * @return bool True on success, false on failure.
 */
function markAllNotificationsAsRead($userId) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    return $stmt->execute([$userId]);
}

?>