<?php
/**
 * models/messages.php
 * FINAL FIXED VERSION
 */

function checkDB() {
    global $pdo;
    if (!$pdo) { $pdo = connectDB(); }
}

// 1. Send Message
function sendMessage($senderId, $receiverId, $message, $attachmentPath = null) {
    checkDB(); global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, attachment_path, created_at, is_read) VALUES (?, ?, ?, ?, NOW(), 0)");
        return $stmt->execute([$senderId, $receiverId, $message, $attachmentPath]);
    } catch (PDOException $e) {
        error_log("Chat Error: " . $e->getMessage());
        return false;
    }
}

// 2. Get Messages
function getMessages($userId, $otherUserId) {
    checkDB(); global $pdo;
    
    // Mark as Read
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")
        ->execute([$otherUserId, $userId]);

    $stmt = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
    $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 3. Online Status
function isUserOnline($userId) {
    checkDB(); global $pdo;
    $user = fetchOne($pdo, "SELECT last_activity FROM users WHERE id = ?", [$userId]);
    if ($user && $user['last_activity']) {
        return (time() - strtotime($user['last_activity'])) < 120; // 2 mins
    }
    return false;
}

// 4. Unread Counts
function getUnreadMessageCount($userId) {
    if (!$userId) return 0;
    checkDB(); global $pdo;
    return fetchColumn($pdo, "SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0", [$userId]);
}

function getUnreadCount($currentUserId, $senderId) {
    checkDB(); global $pdo;
    return fetchColumn($pdo, "SELECT COUNT(*) FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 0", [$senderId, $currentUserId]);
}
?>