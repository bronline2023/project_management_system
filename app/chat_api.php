<?php
/**
 * app/chat_api.php
 * FINAL COMPLETE VERSION
 * Handles:
 * 1. Fetch Chat & Online Status
 * 2. Send Message (Text + Attachment)
 * 3. Delete Single Message
 * 4. Clear Entire Chat
 * 5. Get Total Unread Count (For Sidebar & Browser Title)
 */

// 1. Load Configuration & Models
require_once __DIR__ . '/../config.php'; 
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'messages.php';

// 2. Connect DB & Start Session
$pdo = connectDB();
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// 3. Auth Check
$currentUserId = $_SESSION['user_id'] ?? 0;
if (!$currentUserId) {
    echo json_encode(['status' => 'error', 'msg' => 'Not logged in']);
    exit;
}

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

try {

    // --- ACTION 1: GET TOTAL UNREAD COUNT (For Sidebar Badge) ---
    if ($action == 'get_total_unread') {
        $count = getUnreadMessageCount($currentUserId);
        echo json_encode(['status' => 'success', 'count' => $count]);
        exit;
    }

    // --- ACTION 2: FETCH CHAT MESSAGES ---
    if ($action == 'fetch_chat' && isset($_GET['chat_with'])) {
        $chatWithId = (int)$_GET['chat_with'];
        
        // Update My Online Status (Heartbeat)
        $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?")->execute([$currentUserId]);
        
        $messages = getMessages($currentUserId, $chatWithId);
        $isOnline = isUserOnline($chatWithId);
        
        echo json_encode(['messages' => $messages, 'is_online' => $isOnline, 'user_id' => $currentUserId]);
        exit;
    }

    // --- ACTION 3: SEND MESSAGE ---
    if ($action == 'send_message' && isset($_POST['receiver_id'])) {
        $receiverId = $_POST['receiver_id'];
        $message = trim($_POST['message_text']);
        $attachmentPath = null;

        // Handle File Upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $uploadDir = ROOT_PATH . 'uploads/chat_attachments/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
            
            $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $fileName = 'chat_' . time() . '_' . uniqid() . '.' . $ext;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $fileName)) {
                $attachmentPath = 'uploads/chat_attachments/' . $fileName;
            }
        }

        if (!empty($message) || !empty($attachmentPath)) {
            $result = sendMessage($currentUserId, $receiverId, $message, $attachmentPath);
            echo json_encode(['status' => $result ? 'success' : 'error']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Empty message']);
        }
        exit;
    }

    // --- ACTION 4: DELETE SINGLE MESSAGE ---
    if ($action == 'delete_message' && isset($_POST['message_id'])) {
        $msgId = $_POST['message_id'];
        // Security: Only delete if I am the sender OR receiver
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ? AND (sender_id = ? OR receiver_id = ?)");
        $stmt->execute([$msgId, $currentUserId, $currentUserId]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    // --- ACTION 5: CLEAR ENTIRE CHAT ---
    if ($action == 'clear_chat' && isset($_POST['partner_id'])) {
        $partnerId = $_POST['partner_id'];
        // Delete all messages between me and partner
        $stmt = $pdo->prepare("DELETE FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
        $stmt->execute([$currentUserId, $partnerId, $partnerId, $currentUserId]);
        echo json_encode(['status' => 'success']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>