<?php
/**
 * admin/messages.php
 * FINAL VERSION: Auto-refresh + Tab Notification
 */

require_once __DIR__ . '/../../../config.php';
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'messages.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$chatWithId = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : 0;
$chatUser = null;

$chatPartners = fetchAll($pdo, "
    SELECT u.id, u.name, u.profile_picture, u.role_id, r.role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id != ?
    ORDER BY u.name ASC
", [$currentUserId]);

if ($chatWithId) {
    $chatUser = fetchOne($pdo, "SELECT * FROM users WHERE id = ?", [$chatWithId]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Messages</title>
    <style>
        body { background-color: #e5ddd5; overflow-x: hidden; }
        .chat-container { display: flex; height: 80vh; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 5px; }
        .users-list { width: 30%; border-right: 1px solid #ddd; background: #fff; display: flex; flex-direction: column; }
        .users-scroll { flex: 1; overflow-y: auto; }
        .chat-area { width: 70%; display: flex; flex-direction: column; background: #efeae2; }
        .users-header, .chat-header { padding: 10px 15px; background: #f0f2f5; border-bottom: 1px solid #ddd; }
        .user-item { display: flex; align-items: center; padding: 10px; cursor: pointer; border-bottom: 1px solid #f0f0f0; text-decoration: none; color: inherit; }
        .user-item:hover, .user-item.active { background: #f0f2f5; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 10px; }
        .unread-badge { background: #25d366; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; margin-left: auto; }
        
        .messages-box { flex: 1; overflow-y: auto; padding: 15px; display: flex; flex-direction: column; gap: 8px; }
        .message { max-width: 75%; padding: 8px 10px; border-radius: 7px; font-size: 14px; position: relative; word-wrap: break-word; display: flex; flex-direction: column; }
        .message.sent { align-self: flex-end; background: #d9fdd3; }
        .message.received { align-self: flex-start; background: #ffffff; }
        .msg-meta { display: flex; justify-content: flex-end; align-items: center; gap: 4px; margin-top: 2px; font-size: 10px; color: #777; }
        .msg-attachment { width: 150px; height: 150px; object-fit: cover; border-radius: 5px; margin-bottom: 5px; cursor: pointer; border: 1px solid #ccc; }
        .delete-msg { color: #dc3545; cursor: pointer; font-size: 12px; margin-left: 5px; opacity: 0.6; }

        .input-area { padding: 8px; background: #f0f2f5; display: flex; align-items: center; gap: 8px; }
        .input-area input { flex: 1; padding: 10px; border-radius: 20px; border: none; outline: none; font-size: 15px; min-width: 0; }
        .btn-icon { background: none; border: none; font-size: 20px; color: #54656f; cursor: pointer; padding: 0 5px; }
        .btn-send { background: #00a884; color: white; border: none; padding: 10px 12px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; }
        
        @media (max-width: 768px) {
            .chat-container { height: 85vh; border-radius: 0; margin-top: 0; }
            .users-list { width: 100%; display: <?= $chatWithId ? 'none' : 'flex' ?>; }
            .chat-area { width: 100%; display: <?= $chatWithId ? 'flex' : 'none' ?>; }
            .btn-back { display: block !important; margin-right: 10px; font-size: 18px; border: none; background: none; }
        }
        .btn-back { display: none; }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="chat-container">
        
        <div class="users-list">
            <div class="users-header">Admin Messenger</div>
            <div class="users-scroll">
                <?php foreach ($chatPartners as $user): 
                    $unread = getUnreadCount($currentUserId, $user['id']);
                ?>
                <a href="index.php?page=messages&chat_with=<?= $user['id'] ?>" 
                   class="user-item <?= $chatWithId == $user['id'] ? 'active' : '' ?>"
                   onclick="hideBadge('badge-<?= $user['id'] ?>')">
                    
                    <img src="<?= !empty($user['profile_picture']) ? UPLOADS_URL . $user['profile_picture'] : ASSETS_URL . 'images/default_avatar.png' ?>" class="user-avatar">
                    <div style="flex:1;">
                        <h6 class="m-0"><?= htmlspecialchars($user['name']) ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($user['role_name']) ?></small>
                    </div>
                    <?php if ($unread > 0): ?>
                        <span id="badge-<?= $user['id'] ?>" class="unread-badge"><?= $unread ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="chat-area">
            <?php if ($chatWithId && $chatUser): ?>
                
                <div class="chat-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <button class="btn-back" onclick="window.location.href='index.php?page=messages'"><i class="fas fa-arrow-left"></i></button>
                            <img src="<?= !empty($chatUser['profile_picture']) ? UPLOADS_URL . $chatUser['profile_picture'] : ASSETS_URL . 'images/default_avatar.png' ?>" class="user-avatar" style="width: 35px; height: 35px;">
                            <div>
                                <h6 class="m-0"><?= htmlspecialchars($chatUser['name']) ?></h6>
                                <span class="online-status" id="onlineIndicator">● Online</span>
                            </div>
                        </div>
                        <button onclick="clearChat()" class="btn btn-sm btn-outline-danger" title="Clear Chat">
                            <i class="fas fa-trash-alt"></i> Clear Chat
                        </button>
                    </div>
                </div>

                <div class="messages-box" id="msgBox">
                    <div class="text-center mt-5 text-muted">Loading...</div>
                </div>

                <form id="chatForm" class="input-area" enctype="multipart/form-data">
                    <input type="hidden" name="receiver_id" value="<?= $chatWithId ?>">
                    <input type="file" id="fileInput" name="attachment" style="display: none;" onchange="previewFile()">
                    <button type="button" class="btn-icon" onclick="document.getElementById('fileInput').click()"><i class="fas fa-paperclip"></i></button>
                    <input type="text" id="message_text" name="message_text" placeholder="Type a message..." autocomplete="off">
                    <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i></button>
                </form>
                <div id="filePreview" class="px-3 pb-2 small text-success fw-bold" style="display:none; background:#f0f2f5;"></div>

            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center h-100 flex-column text-muted">
                    <i class="fab fa-whatsapp fa-3x mb-3 text-success"></i>
                    <h5>Select a user to chat</h5>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const chatWithId = <?= $chatWithId ?>;
    const currentUserId = <?= $currentUserId ?>;
    const msgBox = document.getElementById('msgBox');
    const onlineIndicator = document.getElementById('onlineIndicator');
    const APP_URL = '<?= APP_URL ?>';
    const API_URL = APP_URL + 'chat_api.php';
    let lastMessageCount = -1;

    function hideBadge(badgeId) {
        const badge = document.getElementById(badgeId);
        if (badge) badge.style.display = 'none';
    }

    function clearChat() {
        if(!confirm('Delete ALL messages?')) return;
        let formData = new FormData();
        formData.append('partner_id', chatWithId);
        fetch(`${API_URL}?action=clear_chat`, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { if(data.status === 'success') msgBox.innerHTML = '<div class="text-center text-muted mt-3 small">No messages yet.</div>'; });
    }

    function deleteMessage(msgId) {
        if(!confirm('Delete this message?')) return;
        let formData = new FormData();
        formData.append('message_id', msgId);
        fetch(`${API_URL}?action=delete_message`, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { if(data.status === 'success') {
            const el = document.getElementById('msg-' + msgId);
            if(el) el.remove();
        }});
    }

    function previewFile() {
        const file = document.getElementById('fileInput').files[0];
        const preview = document.getElementById('filePreview');
        if (file) {
            preview.style.display = 'block';
            preview.innerHTML = `<i class="fas fa-image"></i> ${file.name}`;
        } else {
            preview.style.display = 'none';
        }
    }

    function fetchMessages() {
        if (!chatWithId) return;
        
        // Anti-cache timestamp
        const timestamp = new Date().getTime();
        
        fetch(`${API_URL}?action=fetch_chat&chat_with=${chatWithId}&_=${timestamp}`)
            .then(res => res.json())
            .then(data => {
                if(onlineIndicator) onlineIndicator.style.display = (data.is_online) ? 'inline' : 'none';
                let html = '';
                let newMessagesCount = 0;

                if (data.messages && data.messages.length > 0) {
                    newMessagesCount = data.messages.length;
                    
                    data.messages.forEach(msg => {
                        let isMe = (msg.sender_id == currentUserId);
                        let type = isMe ? 'sent' : 'received';
                        let time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        let attachmentHtml = msg.attachment_path ? `<a href="${msg.attachment_path}" target="_blank"><img src="${msg.attachment_path}" class="msg-attachment"></a>` : '';
                        
                        let ticks = '';
                        if (isMe) {
                            ticks = (msg.is_read == 1) 
                                ? '<i class="fas fa-check-double text-primary" style="font-size:10px;"></i>' 
                                : '<i class="fas fa-check" style="font-size:10px;"></i>';
                        }
                        
                        let deleteIcon = isMe ? `<i class="fas fa-trash-alt delete-msg" onclick="deleteMessage(${msg.id})" title="Delete"></i>` : '';

                        html += `<div class="message ${type}" id="msg-${msg.id}">
                                    ${attachmentHtml}
                                    <div>${msg.message || ''}</div>
                                    <div class="msg-meta">${time} ${ticks} ${deleteIcon}</div>
                                 </div>`;
                    });
                } else { 
                    html = '<div class="text-center text-muted mt-3 small">No messages yet.</div>'; 
                }
                
                // NOTIFICATION LOGIC
                if (lastMessageCount !== -1 && newMessagesCount > lastMessageCount) {
                    document.title = "(1) New Message!";
                } else if (newMessagesCount === lastMessageCount) {
                     document.title = "Admin Messages";
                }
                
                lastMessageCount = newMessagesCount;

                if (msgBox.innerHTML !== html) {
                    let shouldScroll = (msgBox.scrollTop + msgBox.clientHeight >= msgBox.scrollHeight - 150) || msgBox.innerHTML.includes('Loading...');
                    msgBox.innerHTML = html;
                    if(shouldScroll) msgBox.scrollTop = msgBox.scrollHeight;
                }
            })
            .catch(err => console.error(err));
    }

    if (document.getElementById('chatForm')) {
        document.getElementById('chatForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            let text = document.getElementById('message_text').value;
            let file = document.getElementById('fileInput').files[0];
            if (!text && !file) return;

            document.getElementById('message_text').value = '';
            document.getElementById('fileInput').value = '';
            document.getElementById('filePreview').style.display = 'none';

            fetch(`${API_URL}?action=send_message`, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => { if(data.status === 'success') fetchMessages(); });
        });
    }

    if (chatWithId) { 
        fetchMessages(); 
        setInterval(fetchMessages, 2000); 
    }
</script>

</body>
</html>