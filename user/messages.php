<?php
/**
 * user/messages.php
 * FINAL & COMPLETE: A complete redesign of the messenger page with a Telegram-like UI
 * and a fully functional chat request system that uses the corrected model functions.
 */
require_once MODELS_PATH . 'messages.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$currentUserRole = $_SESSION['user_role'] ?? 'guest';
$message = '';

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$activeChatUserId = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : null;
$users = getAllUsersWithConnectionStatus($currentUserId);
$activeChatUser = null;
$connection = null;
$chatMessages = [];

if ($activeChatUserId) {
    // Mark messages as read from the active user
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
    $stmt->execute([$activeChatUserId, $currentUserId]);

    $activeChatUser = fetchOne($pdo, "SELECT id, name, profile_picture FROM users WHERE id = ?", [$activeChatUserId]);
    $connection = checkConnectionStatus($currentUserId, $activeChatUserId);

    // Allow chat if admin OR if connection is accepted
    if ($currentUserRole === 'admin' || ($connection && $connection['status'] === 'accepted')) {
        $chatMessages = fetchAll($pdo, "SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC", [$currentUserId, $activeChatUserId, $activeChatUserId, $currentUserId]);
    }
}
?>

<div class="messenger-container card shadow-sm">
    <div class="messenger-sidebar">
        <div class="sidebar-header"><h5 class="mb-0"><i class="fas fa-comments me-2"></i>Messenger</h5></div>
        <div class="user-list">
            <?php foreach ($users as $user): 
                $profilePic = !empty($user['profile_picture']) ? BASE_URL . $user['profile_picture'] : ASSETS_URL . 'images/default-profile.png';
                $activeClass = ($activeChatUserId == $user['id']) ? 'active' : '';
            ?>
                <a href="?page=messages&chat_with=<?= $user['id'] ?>" class="user-list-item <?= $activeClass ?>">
                    <img src="<?= $profilePic ?>" alt="Avatar" class="avatar">
                    <div class="user-info">
                        <h6><?= htmlspecialchars($user['name']) ?></h6>
                        <?php if($user['status'] === 'pending' && $user['action_user_id'] != $currentUserId): ?>
                            <small class="text-success fw-bold">New Request</small>
                        <?php else: ?>
                            <small><?= htmlspecialchars($user['role_name'] ?? 'User') ?></small>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="chat-main">
        <?php if ($activeChatUser): ?>
            <div class="chat-header">
                <div class="d-flex align-items-center">
                    <?php $activeProfilePic = !empty($activeChatUser['profile_picture']) ? BASE_URL . $activeChatUser['profile_picture'] : ASSETS_URL . 'images/default-profile.png'; ?>
                    <img src="<?= $activeProfilePic ?>" alt="Avatar" class="avatar me-3">
                    <h5 class="mb-0"><?= htmlspecialchars($activeChatUser['name']) ?></h5>
                </div>
            </div>

            <div class="chat-body" id="chat-body">
                <?php if ($currentUserRole === 'admin' || ($connection && $connection['status'] === 'accepted')): ?>
                    <?php if (empty($chatMessages)): ?>
                        <div class="request-prompt"><p>No messages yet. Start the conversation!</p></div>
                    <?php endif; ?>
                    <?php foreach($chatMessages as $msg): 
                        $msgClass = ($msg['sender_id'] == $currentUserId) ? 'sent' : 'received';
                    ?>
                    <div class="message-wrapper <?= $msgClass ?>">
                        <div class="message-content">
                            <?= nl2br(htmlspecialchars($msg['message_text'])) ?>
                            <div class="message-time"><?= date('h:i A', strtotime($msg['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php elseif ($connection && $connection['status'] === 'pending' && $connection['action_user_id'] != $currentUserId): ?>
                    <div class="request-prompt">
                        <p><strong><?= htmlspecialchars($activeChatUser['name']) ?></strong> has sent you a chat request.</p>
                        <form action="index.php" method="POST" class="d-inline">
                            <input type="hidden" name="action" value="accept_request"><input type="hidden" name="page" value="messages">
                            <input type="hidden" name="user_id" value="<?= $activeChatUserId ?>">
                            <button type="submit" class="btn btn-success">Accept</button>
                        </form>
                         <form action="index.php" method="POST" class="d-inline">
                            <input type="hidden" name="action" value="reject_request"><input type="hidden" name="page" value="messages">
                            <input type="hidden" name="user_id" value="<?= $activeChatUserId ?>">
                            <button type="submit" class="btn btn-danger">Reject</button>
                        </form>
                    </div>
                <?php elseif ($connection && $connection['status'] === 'pending'): ?>
                     <div class="request-prompt"><p>Your chat request to <strong><?= htmlspecialchars($activeChatUser['name']) ?></strong> is pending.</p></div>
                <?php else: ?>
                     <div class="request-prompt">
                        <p>You are not connected with this user.</p>
                        <form action="index.php" method="POST">
                           <input type="hidden" name="action" value="send_request"><input type="hidden" name="page" value="messages">
                           <input type="hidden" name="user_id" value="<?= $activeChatUserId ?>">
                           <button type="submit" class="btn btn-primary">Send Chat Request</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($currentUserRole === 'admin' || ($connection && $connection['status'] === 'accepted')): ?>
            <div class="chat-footer">
                <form action="index.php" method="POST">
                    <input type="hidden" name="action" value="send_message">
                    <input type="hidden" name="page" value="messages">
                    <input type="hidden" name="receiver_id" value="<?= $activeChatUserId ?>">
                    <div class="input-group">
                        <input type="text" name="message_text" class="form-control" placeholder="Type a message..." required autocomplete="off">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="welcome-prompt">
                <i class="fas fa-comments fa-4x text-muted"></i>
                <h4>Welcome to the Messenger</h4>
                <p>Select a contact from the list to start a conversation.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
    const chatBody = document.getElementById('chat-body');
    if (chatBody) {
        chatBody.scrollTop = chatBody.scrollHeight;
    }
</script>