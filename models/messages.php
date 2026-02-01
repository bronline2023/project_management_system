<?php
/**
 * models/messages.php
 * Handles all database interactions related to messaging and connections.
 * FINAL & COMPLETE: Added the missing getUnreadMessageCount() function and fixed all queries.
 */

if (!defined('ROOT_PATH')) {
    // This check ensures config is loaded if the file is accessed directly.
    require_once __DIR__ . '/../config.php';
}
if (!function_exists('connectDB')) {
    require_once __DIR__ . '/db.php';
}

/**
 * Gets the count of unread messages for a specific user.
 * This function is now defined here to be used in the sidebar.
 *
 * @param int $userId The ID of the user.
 * @return int The count of unread messages.
 */
function getUnreadMessageCount($userId) {
    $pdo = connectDB();
    $sql = "SELECT COUNT(id) FROM messages WHERE receiver_id = ? AND is_read = 0";
    return (int)fetchColumn($pdo, $sql, [$userId]);
}

/**
 * Fetches all users the current user can potentially chat with, along with their connection status.
 *
 * @param int $currentUserId The ID of the currently logged-in user.
 * @return array A list of users.
 */
function getAllUsersWithConnectionStatus($currentUserId) {
    $pdo = connectDB();
    $sql = "
        SELECT u.id, u.name, u.profile_picture, r.role_name, 
               c.status, c.action_user_id
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        LEFT JOIN chat_connections c ON 
            (c.user_one_id = u.id AND c.user_two_id = :currentUserId1) OR 
            (c.user_two_id = u.id AND c.user_one_id = :currentUserId2)
        WHERE u.id != :currentUserId3
        ORDER BY u.name ASC
    ";
    // Fixed parameter binding with unique placeholders
    return fetchAll($pdo, $sql, [
        ':currentUserId1' => $currentUserId,
        ':currentUserId2' => $currentUserId,
        ':currentUserId3' => $currentUserId
    ]);
}

/**
 * Checks the connection status between two users.
 *
 * @param int $user1_id The ID of the first user.
 * @param int $user2_id The ID of the second user.
 * @return array|false The connection record or false if no connection exists.
 */
function checkConnectionStatus($user1_id, $user2_id) {
    $pdo = connectDB();
    $sql = "SELECT * FROM chat_connections 
            WHERE (user_one_id = ? AND user_two_id = ?) 
               OR (user_one_id = ? AND user_two_id = ?)";
    // Correctly passing 4 parameters for 4 placeholders
    $params = [min($user1_id, $user2_id), max($user1_id, $user2_id), min($user2_id, $user1_id), max($user2_id, $user1_id)];
    return fetchOne($pdo, $sql, $params);
}

/**
 * Sends a chat request from one user to another.
 *
 * @param int $senderId The user sending the request.
 * @param int $receiverId The user receiving the request.
 * @return bool True on success, false on failure.
 */
function sendChatRequest($senderId, $receiverId) {
    $pdo = connectDB();
    // Prevent sending a request if a connection already exists
    if (checkConnectionStatus($senderId, $receiverId)) {
        return false;
    }
    $sql = "INSERT INTO chat_connections (user_one_id, user_two_id, status, action_user_id) VALUES (?, ?, 'pending', ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([min($senderId, $receiverId), max($senderId, $receiverId), $senderId]);
}

/**
 * Accepts a chat request.
 *
 * @param int $receiverId The user who is accepting the request.
 * @param int $senderId The user who sent the request.
 * @return bool True on success, false on failure.
 */
function acceptChatRequest($receiverId, $senderId) {
    $pdo = connectDB();
    $sql = "UPDATE chat_connections 
            SET status = 'accepted', action_user_id = ? 
            WHERE (user_one_id = ? AND user_two_id = ?) OR (user_one_id = ? AND user_two_id = ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$receiverId, $senderId, $receiverId, $receiverId, $senderId]);
}

/**
 * Rejects or cancels a chat request by deleting the connection record.
 *
 * @param int $actionUserId The user performing the action.
 * @param int $otherUserId The other user in the connection.
 * @return bool True on success, false on failure.
 */
function rejectOrCancelRequest($actionUserId, $otherUserId) {
    $pdo = connectDB();
    $sql = "DELETE FROM chat_connections 
            WHERE (user_one_id = ? AND user_two_id = ?) OR (user_one_id = ? AND user_two_id = ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$actionUserId, $otherUserId, $otherUserId, $actionUserId]);
}

/**
 * Sends a message from one user to another.
 *
 * @param int $senderId The ID of the message sender.
 * @param int $receiverId The ID of the message receiver.
 * @param string $messageText The content of the message.
 * @return bool True on success, false on failure.
 */
function sendMessage($senderId, $receiverId, $messageText) {
    $pdo = connectDB();
    $sql = "INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$senderId, $receiverId, $messageText]);
}