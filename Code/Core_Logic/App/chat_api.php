<?php
/**
 * app/chat_api.php
 * Handles Live Chat, Status Updates, and Soft Delete
 */

require_once __DIR__ . '/../../../config.php'; 
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'messages.php';

$pdo = connectDB();
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Set the timezone
date_default_timezone_set('Asia/Kolkata');

$currentUserId = $_SESSION['user_id'] ?? 0;
if (!$currentUserId) { echo json_encode(['status' => 'error']); exit; }

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

try {
    // 1. FETCH CHAT & UPDATE LAST SEEN
    if ($action == 'fetch_chat' && isset($_GET['chat_with'])) {
        $chatWithId = (int)$_GET['chat_with'];
        
        // Update My Last Activity
        $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?")->execute([$currentUserId]);
        
        $messages = getMessages($currentUserId, $chatWithId);
        $status = getUserStatus($chatWithId); 
        
        // Format Dates correctly for India (IST)
        foreach($messages as &$msg) {
            $msg['formatted_time'] = date('h:i A', strtotime($msg['created_at']));
            $msg['formatted_date'] = date('d M Y', strtotime($msg['created_at']));
        }
        
        echo json_encode([
            'messages' => $messages, 
            'user_status' => $status, 
            'user_id' => $currentUserId
        ]);
        exit;
    }

    // 2. SEND MESSAGE
    if ($action == 'send_message' && isset($_POST['receiver_id'])) {
        $receiverId = $_POST['receiver_id'];
        $message = trim($_POST['message_text']);
        $replyToId = !empty($_POST['reply_to_id']) ? $_POST['reply_to_id'] : null;
        $taskId = !empty($_POST['related_task_id']) ? $_POST['related_task_id'] : null;
        $attachmentPath = null;

        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $uploadDir = UPLOADS_PATH . 'chat_attachments/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $fileName = 'chat_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $fileName)) {
                $attachmentPath = UPLOADS_DIR_RELATIVE . 'chat_attachments/' . $fileName;
            }
        }

        if (!empty($message) || !empty($attachmentPath)) {
            sendMessage($currentUserId, $receiverId, $message, $attachmentPath, $replyToId, $taskId);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
        exit;
    }

    // 3. DELETE MESSAGE (For Me Only)
    if ($action == 'delete_message' && isset($_POST['message_id'])) {
        softDeleteMessage($_POST['message_id'], $currentUserId);
        echo json_encode(['status' => 'success']);
        exit;
    }

    // 4. CLEAR CHAT
    if ($action == 'clear_chat' && isset($_POST['partner_id'])) {
        $partnerId = $_POST['partner_id'];
        $pdo->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE sender_id = ? AND receiver_id = ?")->execute([$currentUserId, $partnerId]);
        $pdo->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE receiver_id = ? AND sender_id = ?")->execute([$currentUserId, $partnerId]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    // 5. CHECK UNREAD (For Sidebar Popup)
    if ($action == 'check_unread') {
        $count = getUnreadMessageCount($currentUserId);
        echo json_encode(['unread_total' => $count]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>