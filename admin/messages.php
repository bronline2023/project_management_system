<?php
/**
 * admin/messages.php
 * FINAL & COMPLETE: A complete redesign of the messenger page with a Telegram-like UI
 * and a fully functional chat request system where admin has special privileges.
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
    $activeChatUser = fetchOne($pdo, "SELECT id, name, profile_picture FROM users WHERE id = ?", [$activeChatUserId]);
    $connection = checkConnectionStatus($currentUserId, $activeChatUserId);

    if ($connection && $connection['status'] === 'accepted') {
        $chatMessages = fetchAll($pdo, "SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC", [$currentUserId, $activeChatUserId, $activeChatUserId, $currentUserId]);
    }
}
?>

<div class="messenger-container card shadow-sm">
    <div class="messenger-sidebar">
        <div class="sidebar-header"><h5 class="mb-0">Messenger</h5></div>
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
                            <small class="text-success">New Request</small>
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
                <?php if ($connection && $connection['status'] === 'accepted'): ?>
                <form action="index.php" method="POST" onsubmit="return confirm('Are you sure you want to clear this chat history?');">
                    <input type="hidden" name="action" value="clear_chat">
                    <input type="hidden" name="page" value="messages">
                    <input type="hidden" name="receiver_id" value="<?= $activeChatUserId ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Clear Chat"><i class="fas fa-trash"></i></button>
                </form>
                <?php endif; ?>
            </div>

            <div class="chat-body">
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
                <?php else: ?>
                     <div class="request-prompt"><p>You are not connected with this user.</p></div>
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