<?php
/**
 * user/messages.php
 * FIXED: Mobile Send Button Visibility & Layout
 */

require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'messages.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$chatWithId = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : 0;
$chatUser = null;

// Fetch Users List
$chatPartners = fetchAll($pdo, "
    SELECT DISTINCT u.id, u.name, u.profile_picture, u.role_id, r.role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id != ? AND (r.role_name = 'Admin' OR u.id IN (SELECT sender_id FROM messages WHERE receiver_id = ?) OR u.id IN (SELECT receiver_id FROM messages WHERE sender_id = ?))
", [$currentUserId, $currentUserId, $currentUserId]);

if ($chatWithId) {
    $chatUser = fetchOne($pdo, "SELECT * FROM users WHERE id = ?", [$chatWithId]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        /* --- CORE STYLES --- */
        body { background-color: #e5ddd5; overflow-x: hidden; }
        
        /* Container Height Adjustment */
        .chat-container { 
            display: flex; 
            height: 80vh; /* Reduced slightly to fit mobile screens better */
            background: #fff; 
            border-radius: 10px; 
            overflow: hidden; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            margin-top: 5px; 
        }
        
        /* SIDEBAR & CHAT AREA (Default Desktop) */
        .users-list { width: 30%; border-right: 1px solid #ddd; background: #fff; display: flex; flex-direction: column; }
        .users-scroll { flex: 1; overflow-y: auto; }
        
        .chat-area { width: 70%; display: flex; flex-direction: column; background: #efeae2; }
        
        .users-header, .chat-header { padding: 10px 15px; background: #f0f2f5; border-bottom: 1px solid #ddd; }
        .user-item { display: flex; align-items: center; padding: 10px; cursor: pointer; border-bottom: 1px solid #f0f0f0; text-decoration: none; color: inherit; }
        .user-item:hover, .user-item.active { background: #f0f2f5; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 10px; }
        .unread-badge { background: #25d366; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; margin-left: auto; }

        /* MESSAGES */
        .messages-box { flex: 1; overflow-y: auto; padding: 15px; display: flex; flex-direction: column; gap: 8px; }
        .message { max-width: 75%; padding: 8px 10px; border-radius: 7px; font-size: 14px; position: relative; word-wrap: break-word; }
        .message.sent { align-self: flex-end; background: #d9fdd3; }
        .message.received { align-self: flex-start; background: #ffffff; }
        .msg-time { font-size: 9px; color: #999; text-align: right; display: flex; justify-content: flex-end; gap: 3px; margin-top: 2px; }
        .msg-attachment { width: 150px; height: 150px; object-fit: cover; border-radius: 5px; margin-bottom: 5px; cursor: pointer; border: 1px solid #ccc; }
        .delete-msg { display: none; margin-left: 5px; color: #dc3545; cursor: pointer; font-size: 11px; }
        .message:hover .delete-msg { display: inline-block; }

        /* INPUT AREA (FIXED FOR MOBILE) */
        .input-area { 
            padding: 8px; 
            background: #f0f2f5; 
            display: flex; 
            align-items: center; 
            gap: 8px; /* Space between elements */
        }
        
        .input-area input { 
            flex: 1; /* Takes available space */
            padding: 10px; 
            border-radius: 20px; 
            border: none; 
            outline: none; 
            font-size: 15px;
            min-width: 0; /* Prevents overflow in flexbox */
        }
        
        .btn-icon { background: none; border: none; font-size: 20px; color: #54656f; cursor: pointer; padding: 0 5px; }
        
        .btn-send { 
            background: #00a884; 
            color: white; 
            border: none; 
            padding: 10px 12px; 
            border-radius: 50%; 
            cursor: pointer; 
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 40px; /* Fixed width prevents shrinking */
            height: 40px;
        }

        /* MOBILE RESPONSIVE FIX */
        @media (max-width: 768px) {
            .chat-container { 
                height: 85vh; /* Increase height on mobile */
                border-radius: 0; 
                margin-top: 0;
            }
            
            /* Toggle Views */
            .users-list { width: 100%; display: <?= $chatWithId ? 'none' : 'flex' ?>; }
            .chat-area { width: 100%; display: <?= $chatWithId ? 'flex' : 'none' ?>; }
            
            .btn-back { display: block !important; margin-right: 10px; font-size: 18px; border: none; background: none; }
            
            /* Compact Message Box on Mobile */
            .messages-box { padding: 10px; }
            .message { font-size: 13.5px; padding: 6px 10px; }
            
            /* Fix Input Area on Small Screens */
            .input-area { padding: 5px; }
            .input-area input { padding: 8px 12px; }
            .btn-icon { font-size: 18px; }
            .btn-send { min-width: 38px; height: 38px; padding: 8px; }
        }
        .btn-back { display: none; }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="chat-container">
        
        <div class="users-list">
            <div class="users-header">Chats</div>
            <div class="users-scroll">
                <?php foreach ($chatPartners as $user): 
                    $unread = getUnreadCount($currentUserId, $user['id']);
                ?>
                <a href="index.php?page=messages&chat_with=<?= $user['id'] ?>" 
                   class="user-item <?= $chatWithId == $user['id'] ? 'active' : '' ?>"
                   onclick="hideBadge('badge-<?= $user['id'] ?>')">
                    
                    <img src="<?= !empty($user['profile_picture']) ? $user['profile_picture'] : 'assets/img/default_avatar.png' ?>" class="user-avatar">
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
                    <div class="d-flex align-items-center">
                        <button class="btn-back" onclick="window.location.href='index.php?page=messages'"><i class="fas fa-arrow-left"></i></button>
                        
                        <img src="<?= !empty($chatUser['profile_picture']) ? $chatUser['profile_picture'] : 'assets/img/default_avatar.png' ?>" class="user-avatar" style="width: 35px; height: 35px;">
                        <div>
                            <h6 class="m-0"><?= htmlspecialchars($chatUser['name']) ?></h6>
                            <span class="online-status" id="onlineIndicator">● Online</span>
                        </div>
                    </div>
                    
                    <button onclick="clearChat()" class="btn btn-sm text-danger" title="Clear Chat">
                        <i class="fas fa-trash-alt"></i>
                    </button>
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
                    <h5>Select a contact</h5>
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
    const API_URL = 'app/chat_api.php'; 

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
        if(!confirm('Delete message?')) return;
        let formData = new FormData();
        formData.append('message_id', msgId);
        fetch(`${API_URL}?action=delete_message`, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { if(data.status === 'success') document.getElementById('msg-' + msgId).remove(); });
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
        fetch(`${API_URL}?action=fetch_chat&chat_with=${chatWithId}`)
            .then(res => res.json())
            .then(data => {
                onlineIndicator.style.display = (data.is_online) ? 'inline' : 'none';
                let html = '';
                if (data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        let isMe = (msg.sender_id == currentUserId);
                        let type = isMe ? 'sent' : 'received';
                        let time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        let attachmentHtml = msg.attachment_path ? `<a href="${msg.attachment_path}" target="_blank"><img src="${msg.attachment_path}" class="msg-attachment"></a>` : '';
                        let ticks = isMe ? (msg.is_read == 1 ? '<i class="fas fa-check-double text-info"></i>' : '<i class="fas fa-check"></i>') : '';
                        let deleteIcon = isMe ? `<i class="fas fa-trash-alt delete-msg" onclick="deleteMessage(${msg.id})"></i>` : '';

                        html += `<div class="message ${type}" id="msg-${msg.id}">${attachmentHtml}<div>${msg.message || ''}</div><div class="msg-time">${time} ${ticks} ${deleteIcon}</div></div>`;
                    });
                } else { html = '<div class="text-center text-muted mt-3 small">No messages yet.</div>'; }
                if (msgBox.innerHTML !== html) {
                    let shouldScroll = (msgBox.scrollTop + msgBox.clientHeight >= msgBox.scrollHeight - 50);
                    msgBox.innerHTML = html;
                    if(shouldScroll || msgBox.innerHTML.length < 200) msgBox.scrollTop = msgBox.scrollHeight;
                }
            });
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
            fetch(`${API_URL}?action=send_message`, { method: 'POST', body: formData }).then(res => res.json()).then(data => fetchMessages());
        });
    }

    if (chatWithId) { fetchMessages(); setInterval(fetchMessages, 2000); setTimeout(() => { msgBox.scrollTop = msgBox.scrollHeight; }, 500); }
</script>

</body>
</html>