<?php
/**
 * admin/messages.php
 *
 * This file provides a messaging interface for admin to chat with other users.
 * It displays a list of contacts and a chat conversation area.
 *
 * It ensures that only authenticated admin users can access this page.
 */

// Include the configuration file for database connection and session management.
// This MUST be the first thing, before any output.
require_once ROOT_PATH . 'config.php';
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php'; // For isLoggedIn()

// Restrict access to authenticated admin users.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';
$selectedContactId = $_GET['chat_with'] ?? null;
$conversationMessages = [];
$selectedContactName = 'Select a Contact';

// --- FIX: Handle Sending Message and redirection BEFORE any HTML output ---
if (isset($_POST['send_message'])) {
    $receiverId = $_POST['receiver_id'] ?? null;
    $messageText = trim($_POST['message_text'] ?? '');

    if (empty($messageText)) {
        $_SESSION['status_message'] = '<div class="alert alert-danger" role="alert">Message cannot be empty.</div>';
    } elseif (empty($receiverId)) {
        $_SESSION['status_message'] = '<div class="alert alert-danger" role="alert">Please select a recipient.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, sent_at) VALUES (:sender_id, :receiver_id, :message_text, NOW())");
            $stmt->bindParam(':sender_id', $currentUserId, PDO::PARAM_INT);
            $stmt->bindParam(':receiver_id', $receiverId, PDO::PARAM_INT);
            $stmt->bindParam(':message_text', $messageText);
            $stmt->execute();
            $_SESSION['status_message'] = '<div class="alert alert-success" role="alert">Message sent successfully!</div>';
        } catch (PDOException $e) {
            error_log("Error sending message: " . $e->getMessage());
            $_SESSION['status_message'] = '<div class="alert alert-danger" role="alert">Error sending message: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
    // Redirect to the same page with the selected contact to refresh conversation
    header('Location: ' . BASE_URL . '?page=messages&chat_with=' . $receiverId);
    exit;
}

// Check for a status message in the session (e.g., from a redirect after sending a message)
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

// Mark all unread messages for the current user as read when the messages page is accessed
try {
    $stmt = $pdo->prepare("UPDATE messages SET read_status = 1, read_at = NOW() WHERE receiver_id = :userId AND read_status = 0");
    $stmt->bindParam(':userId', $currentUserId, PDO::PARAM_INT);
    $stmt->execute();
} catch (PDOException $e) {
    error_log("Error marking messages as read: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error marking messages as read.</div>';
}

// Fetch all other users to display as contacts
$contacts = [];
try {
    $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id != :currentUserId ORDER BY name ASC");
    $stmt->bindParam(':currentUserId', $currentUserId, PDO::PARAM_INT);
    $stmt->execute();
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching contacts: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading contacts.</div>';
}

// Fetch conversation messages if a contact is selected
if ($selectedContactId) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = :id");
        $stmt->bindParam(':id', $selectedContactId, PDO::PARAM_INT);
        $stmt->execute();
        $contactInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($contactInfo) {
            $selectedContactName = htmlspecialchars($contactInfo['name']);
        }

        $sql = "
            SELECT m.*, s.name as sender_name, r.name as receiver_name
            FROM messages m
            JOIN users s ON m.sender_id = s.id
            JOIN users r ON m.receiver_id = r.id
            WHERE (m.sender_id = :currentUserId AND m.receiver_id = :selectedContactId_1)
               OR (m.sender_id = :selectedContactId_2 AND m.receiver_id = :currentUserId_2)
            ORDER BY m.sent_at ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':currentUserId', $currentUserId, PDO::PARAM_INT);
        $stmt->bindParam(':selectedContactId_1', $selectedContactId, PDO::PARAM_INT);
        $stmt->bindParam(':selectedContactId_2', $selectedContactId, PDO::PARAM_INT);
        $stmt->bindParam(':currentUserId_2', $currentUserId, PDO::PARAM_INT);
        $stmt->execute();
        $conversationMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mark messages from the selected contact to current user as read
        $stmt = $pdo->prepare("UPDATE messages SET read_status = 1, read_at = NOW() WHERE receiver_id = :userId AND sender_id = :senderId AND read_status = 0");
        $stmt->bindParam(':userId', $currentUserId, PDO::PARAM_INT);
        $stmt->bindParam(':senderId', $selectedContactId, PDO::PARAM_INT);
        $stmt->execute();

    } catch (PDOException $e) {
        error_log("Error fetching conversation messages: " . $e->getMessage());
        $message .= '<div class="alert alert-danger" role="alert">Error loading conversation: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Now include the HTML and sidebar
include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Messenger</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; ?>
            <script>
                setupAutoHideAlerts();
            </script>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Contacts</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php if (!empty($contacts)): ?>
                                <?php foreach ($contacts as $contact): ?>
                                    <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= ($selectedContactId == $contact['id']) ? 'active' : '' ?>">
                                        <a href="<?= BASE_URL ?>?page=messages&chat_with=<?= htmlspecialchars($contact['id']) ?>" class="text-decoration-none d-block w-100 py-2 px-3">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-circle fa-2x me-3 text-muted"></i>
                                                <div>
                                                    <h6 class="mb-0 text-truncate"><?= htmlspecialchars($contact['name']) ?></h6>
                                                    <small class="text-muted"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $contact['role']))) ?></small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-center text-muted">No other users found.</p>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-8 mb-4">
                <div class="card shadow-sm rounded-3 h-100 d-flex flex-column">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Conversation with: <?= $selectedContactName ?></h5>
                    </div>
                    <div class="card-body chat-messages flex-grow-1 overflow-auto p-3" id="chatMessages">
                        <?php if ($selectedContactId): ?>
                            <?php if (!empty($conversationMessages)): ?>
                                <?php foreach ($conversationMessages as $msg): ?>
                                    <div class="message-bubble d-flex <?= ($msg['sender_id'] == $currentUserId) ? 'justify-content-end' : 'justify-content-start' ?> mb-2">
                                        <div class="card p-2 rounded-3 shadow-sm <?= ($msg['sender_id'] == $currentUserId) ? 'bg-info text-white' : 'bg-light' ?>" style="max-width: 75%;">
                                            <small class="text-muted d-block mb-1 <?= ($msg['sender_id'] == $currentUserId) ? 'text-white-50' : '' ?>">
                                                <?= htmlspecialchars($msg['sender_name']) ?> at <?= date('Y-m-d H:i', strtotime($msg['sent_at'])) ?>
                                            </small>
                                            <p class="mb-0"><?= htmlspecialchars($msg['message_text']) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-center text-muted">No messages in this conversation yet. Say hello!</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-center text-muted">Please select a contact to start chatting.</p>
                        <?php endif; ?>
                    </div>
                    <?php if ($selectedContactId): ?>
                        <div class="card-footer bg-light p-3">
                            <form action="<?= BASE_URL ?>?page=messages&chat_with=<?= htmlspecialchars($selectedContactId) ?>" method="POST" class="d-flex">
                                <input type="hidden" name="page" value="messages">
                                <input type="hidden" name="receiver_id" value="<?= htmlspecialchars($selectedContactId) ?>">
                                <textarea class="form-control rounded-pill me-2" name="message_text" rows="1" placeholder="Type your message..." required></textarea>
                                <button type="submit" name="send_message" class="btn btn-primary rounded-pill px-4">
                                    <i class="fas fa-paper-plane"></i> Send
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
<?php ob_end_flush(); ?>