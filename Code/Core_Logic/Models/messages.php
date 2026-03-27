<?php
/**
 * models/messages.php
 * Updated for Soft Delete, Replies, and Last Seen
 */

if (!function_exists('connectDB')) { require_once __DIR__ . '/db.php'; }

function checkDB() { global $pdo; if (!isset($pdo) || !$pdo) { $pdo = connectDB(); } }

// 1. Get User Online Status (Last Seen)
function getUserStatus($userId) {
    checkDB(); global $pdo;
    $stmt = $pdo->prepare("SELECT last_activity FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $lastActivity = $stmt->fetchColumn();
    
    if (!$lastActivity) return ['status' => 'offline', 'text' => 'Offline'];

    $timeDiff = time() - strtotime($lastActivity);
    // જો 60 સેકન્ડની અંદર એક્ટિવ હોય તો Online ગણવું
    if ($timeDiff < 60) {
        return ['status' => 'online', 'text' => 'Online'];
    } else {
        return ['status' => 'offline', 'text' => 'Last seen: ' . date('d M, h:i A', strtotime($lastActivity))];
    }
}

// 2. Send Message (With Reply & Task)
function sendMessage($senderId, $receiverId, $message, $attachmentPath = null, $replyToId = null, $taskId = null) {
    checkDB(); global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, attachment_path, reply_to_id, related_task_id, created_at, is_read) VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)");
        return $stmt->execute([$senderId, $receiverId, $message, $attachmentPath, $replyToId, $taskId]);
    } catch (PDOException $e) {
        return false;
    }
}

// 3. Get Messages (Soft Delete handled here)
function getMessages($userId, $otherUserId) {
    checkDB(); global $pdo;
    
    // Mark as read when opening chat
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")
        ->execute([$otherUserId, $userId]);

    // Fetch messages logic (Only fetch if NOT deleted by current user)
    $sql = "
        SELECT m.*, 
               r.message as reply_message, 
               u.name as reply_sender_name,
               t.id as task_id
        FROM messages m
        LEFT JOIN messages r ON m.reply_to_id = r.id
        LEFT JOIN users u ON r.sender_id = u.id
        LEFT JOIN work_assignments t ON m.related_task_id = t.id
        WHERE 
        (
            (m.sender_id = ? AND m.receiver_id = ? AND m.deleted_by_sender = 0) 
            OR 
            (m.sender_id = ? AND m.receiver_id = ? AND m.deleted_by_receiver = 0)
        )
        ORDER BY m.created_at ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 4. Soft Delete (Delete for Me Only)
function softDeleteMessage($msgId, $currentUserId) {
    checkDB(); global $pdo;
    $stmt = $pdo->prepare("SELECT sender_id, receiver_id FROM messages WHERE id = ?");
    $stmt->execute([$msgId]);
    $msg = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($msg) {
        if ($msg['sender_id'] == $currentUserId) {
            // જો હું સેન્ડર હોઉં, તો મારા માટે ડિલીટ કરો
            $pdo->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE id = ?")->execute([$msgId]);
        } elseif ($msg['receiver_id'] == $currentUserId) {
            // જો હું રીસીવર હોઉં, તો મારા માટે ડિલીટ કરો
            $pdo->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE id = ?")->execute([$msgId]);
        }
        return true;
    }
    return false;
}

// 5. Unread Counts (For Notifications)
function getUnreadMessageCount($userId) {
    if (!$userId) return 0;
    checkDB(); global $pdo;
    return fetchColumn($pdo, "SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0 AND deleted_by_receiver = 0", [$userId]);
}

/**
 * Get unread message count for a specific user from a specific sender.
 */
function getUnreadCount($currentUserId, $senderId) {
    checkDB(); global $pdo;
    return fetchColumn($pdo, "SELECT COUNT(*) FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 0 AND deleted_by_receiver = 0", [$senderId, $currentUserId]);
}

/**
 * Alias for getUnreadCount for backward compatibility.
 */
function getUnreadCountPerUser($currentUserId, $senderId) {
    return getUnreadCount($currentUserId, $senderId);
}
?>