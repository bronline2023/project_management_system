<?php
/**
 * user/messages.php
 * FINAL FIX: Full Screen Mobile View (Overlay Mode) & Timezone Display
 */

require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'messages.php';
date_default_timezone_set('Asia/Kolkata');
$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$chatWithId = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : 0;
$chatUser = null;

// Chat Partners List
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
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
    <style>
        /* --- GLOBAL LAYOUT --- */
        
        /* Desktop Wrapper */
        .chat-wrapper {
            display: flex;
            height: calc(100vh - 100px); /* Desktop height */
            width: 100%;
            max-width: 1400px;
            margin: 10px auto;
            background: #fff;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        
        /* --- SIDEBAR (Users) --- */
        .users-sidebar {
            width: 320px;
            border-right: 1px solid #ddd;
            display: flex;
            flex-direction: column;
            background: #fff;
            height: 100%;
        }
        .users-header { padding: 15px; background: #f8f9fa; border-bottom: 1px solid #ddd; }
        .users-list { flex: 1; overflow-y: auto; }
        .user-item { display: flex; align-items: center; padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0; text-decoration: none; color: #333; transition: 0.2s; }
        .user-item:hover, .user-item.active { background: #e8f0fe; }
        .user-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; margin-right: 12px; border: 1px solid #eee; flex-shrink: 0; }
        .unread-badge { background: #25d366; color: white; font-size: 11px; padding: 3px 7px; border-radius: 12px; font-weight: bold; margin-left: auto; }

        /* --- CHAT AREA --- */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
            background-color: #efeae2;
            background-image: url('assets/img/chat-bg-doodle.png');
            overflow: hidden; 
        }

        /* Header */
        .chat-header {
            padding: 10px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 60px;
            flex-shrink: 0;
            z-index: 10;
        }

        /* Messages Body */
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 15px 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            scroll-behavior: smooth;
            padding-bottom: 20px;
        }

        /* Input Area */
        .chat-input-area {
            padding: 10px;
            background: #ffffff;
            border-top: 1px solid #ddd;
            flex-shrink: 0;
            display: flex;
            align-items: flex-end;
            gap: 10px;
            position: relative;
            z-index: 20;
            min-height: 60px;
        }

        /* Input Styling */
        .input-wrapper {
            flex: 1;
            background: #f0f2f5;
            border-radius: 20px;
            padding: 8px 15px;
            display: flex;
            flex-direction: column;
            border: 1px solid #ddd;
            justify-content: center;
        }
        .input-wrapper:focus-within { background: #fff; border-color: #25d366; }
        .input-wrapper textarea {
            width: 100%; border: none; outline: none; resize: none;
            max-height: 100px; font-family: inherit; font-size: 15px;
            background: transparent; min-height: 24px;
        }

        /* Message Bubbles */
        .message {
            max-width: 75%;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 14.5px;
            position: relative;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            word-wrap: break-word;
        }
        .message.sent { align-self: flex-end; background: #d9fdd3; border-top-right-radius: 0; }
        .message.received { align-self: flex-start; background: #ffffff; border-top-left-radius: 0; }
        .msg-time { font-size: 10px; color: #888; align-self: flex-end; margin-top: 3px; display: flex; gap: 4px; }

        /* Emoji Popover */
        #emoji-popover {
            display: none; position: absolute; bottom: 75px; left: 10px;
            z-index: 200; box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            border-radius: 10px; overflow: hidden; height: 300px; width: 300px; background: white;
        }
        
        /* Previews */
        .preview-box {
            display: flex; justify-content: space-between; align-items: center; 
            background: #fff; padding: 5px 10px; border-radius: 5px; margin-bottom: 5px; 
            font-size: 12px; border-left: 3px solid #00a884;
        }

        /* --- MOBILE FULL SCREEN FIX (CRITICAL UPDATE) --- */
        @media (max-width: 768px) {
            
            /* FORCE FULL SCREEN OVERLAY */
            .chat-wrapper {
                position: fixed; /* Takes it out of dashboard flow */
                top: 0;
                left: 0;
                width: 100%;
                height: 100%; /* Full Height */
                margin: 0 !important;
                border-radius: 0;
                z-index: 9999; /* Shows above everything */
                max-width: none;
                background: #fff;
            }

            /* HIDE SIDEBAR IF CHATTING */
            .users-sidebar { 
                width: 100%; 
                display: <?= $chatWithId ? 'none' : 'flex' ?>; 
            }

            /* SHOW CHAT MAIN IF CHATTING */
            .chat-main { 
                width: 100%; 
                display: <?= $chatWithId ? 'flex' : 'none' ?>; 
            }

            .message { max-width: 85%; }
            .chat-input-area { padding: 8px; }
            
            /* Add padding to top of list if using sticky header */
            .users-list { padding-bottom: 60px; }
        }
    </style>
</head>
<body>

<div class="chat-wrapper">
    
    <div class="users-sidebar">
        <div class="users-header d-flex justify-content-between align-items-center">
            <h5 class="m-0">Chats</h5>
            <a href="index.php?page=dashboard" class="btn btn-sm btn-light d-md-none"><i class="fas fa-home"></i></a>
        </div>
        <div class="users-list">
            <?php foreach ($chatPartners as $user): 
                $unread = function_exists('getUnreadCount') ? getUnreadCount($currentUserId, $user['id']) : 0;
            ?>
            <a href="index.php?page=messages&chat_with=<?= $user['id'] ?>" class="user-item <?= $chatWithId == $user['id'] ? 'active' : '' ?>">
                <img src="<?= !empty($user['profile_picture']) ? UPLOADS_URL . $user['profile_picture'] : ASSETS_URL . 'images/default_avatar.png' ?>" class="user-avatar">
                <div class="w-100 overflow-hidden">
                    <div class="d-flex justify-content-between">
                        <strong class="text-truncate"><?= htmlspecialchars($user['name']) ?></strong>
                        <?php if ($unread > 0): ?>
                            <span class="unread-badge"><?= $unread ?></span>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted d-block text-truncate"><?= htmlspecialchars($user['role_name']) ?></small>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="chat-main">
        <?php if ($chatWithId && $chatUser): ?>
            
            <div class="chat-header">
                <div class="d-flex align-items-center">
                    <a href="index.php?page=messages" class="btn btn-sm btn-light rounded-circle me-2 d-md-none"><i class="fas fa-arrow-left"></i></a>
                    <img src="<?= !empty($chatUser['profile_picture']) ? UPLOADS_URL . $chatUser['profile_picture'] : ASSETS_URL . 'images/default_avatar.png' ?>" class="user-avatar" style="width: 40px; height: 40px;">
                    <div class="ms-2">
                        <h6 class="m-0 text-dark"><?= htmlspecialchars($chatUser['name']) ?></h6>
                        <div class="small text-muted" id="liveStatus">Checking...</div>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item text-danger" href="#" onclick="clearChat()"><i class="fas fa-trash-alt me-2"></i> Clear Chat</a></li>
                        <li><a class="dropdown-item" href="#" onclick="location.reload()"><i class="fas fa-sync me-2"></i> Refresh</a></li>
                    </ul>
                </div>
            </div>

            <div class="messages-container" id="msgBox">
                <div class="text-center mt-5"><div class="spinner-border text-secondary" role="status"></div></div>
            </div>

            <form id="chatForm" class="chat-input-area" enctype="multipart/form-data">
                <input type="hidden" name="receiver_id" value="<?= $chatWithId ?>">
                <input type="hidden" name="reply_to_id" id="replyToId">
                
                <div id="emoji-popover"><emoji-picker></emoji-picker></div>
                
                <input type="file" id="fileInput" name="attachment" style="display:none;" onchange="showFilePreview()">
                
                <div class="d-flex gap-1 mb-1">
                    <button type="button" class="btn btn-light rounded-circle text-secondary p-2" onclick="toggleEmoji()" title="Emoji"><i class="far fa-smile"></i></button>
                    <button type="button" class="btn btn-light rounded-circle text-secondary p-2" onclick="document.getElementById('fileInput').click()" title="Attachment"><i class="fas fa-paperclip"></i></button>
                </div>

                <div class="input-wrapper">
                    <div id="replyPreview" class="preview-box" style="display:none;">
                        <div>
                            <span class="text-success fw-bold">Replying...</span><br>
                            <span class="text-muted text-truncate" id="replyTextDisplay" style="max-width: 200px;"></span>
                        </div>
                        <i class="fas fa-times cursor-pointer text-muted" onclick="cancelReply()"></i>
                    </div>

                    <div id="filePreview" class="preview-box" style="display:none; border-left-color: #0d6efd;">
                        <span id="fileNameDisplay" class="text-primary fw-bold"></span>
                        <i class="fas fa-times cursor-pointer text-muted" onclick="clearFile()"></i>
                    </div>

                    <textarea id="message_text" name="message_text" rows="1" placeholder="Type a message..." oninput="autoResize(this)"></textarea>
                </div>

                <button type="submit" class="btn btn-success rounded-circle shadow-sm" style="width:45px; height:45px; flex-shrink:0;"><i class="fas fa-paper-plane"></i></button>
            </form>
            
        <?php else: ?>
            <div class="d-flex align-items-center justify-content-center h-100 flex-column text-muted">
                <i class="fas fa-comments fa-4x mb-3 opacity-25"></i>
                <h5>Select a chat to start messaging</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const chatWithId = <?= $chatWithId ?>;
    const currentUserId = <?= $currentUserId ?>;
    const APP_URL = '<?= APP_URL ?>';
    const API_URL = APP_URL + 'chat_api.php';
    let lastMsgCount = 0;
    const msgBox = document.getElementById('msgBox');

    function autoResize(el) {
        el.style.height = 'auto';
        el.style.height = (el.scrollHeight) + 'px';
        if(el.value === '') el.style.height = 'auto';
    }

    function scrollToBottom() {
        if(msgBox) msgBox.scrollTop = msgBox.scrollHeight;
    }

    if(document.getElementById('chatForm')) {
        document.getElementById('chatForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const text = document.getElementById('message_text').value.trim();
            const file = document.getElementById('fileInput').files[0];
            if (!text && !file) return;

            const formData = new FormData(this);
            document.getElementById('message_text').value = '';
            document.getElementById('message_text').style.height = 'auto';
            clearFile();
            cancelReply();
            document.getElementById('emoji-popover').style.display = 'none';

            fetch(`${API_URL}?action=send_message`, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => { if(data.status === 'success') fetchMessages(true); });
        });
    }

    function fetchMessages(forceScroll = false) {
        if (!chatWithId) return;

        fetch(`${API_URL}?action=fetch_chat&chat_with=${chatWithId}&t=${Date.now()}`)
        .then(res => res.json())
        .then(data => {
            // Live Status Update
            const statusDiv = document.getElementById('liveStatus');
            if(data.user_status.status === 'online') {
                statusDiv.innerHTML = '<span class="text-success fw-bold">● Online</span>';
            } else {
                statusDiv.innerText = data.user_status.text;
            }

            // Messages Render
            if (!data.messages || data.messages.length === 0) {
                msgBox.innerHTML = '<div class="text-center mt-5 text-muted small">No conversation yet.</div>';
                return;
            }

            if (data.messages.length !== lastMsgCount) {
                let html = '';
                data.messages.forEach(msg => {
                    const isMe = (msg.sender_id == currentUserId);
                    const type = isMe ? 'sent' : 'received';
                    
                    let replyHtml = msg.reply_to_id ? `<div class="bg-light p-1 mb-1 rounded small border-start border-success border-3">Replying: <i class="text-muted">${msg.reply_message ? msg.reply_message.substring(0,30) : '...'}</i></div>` : '';
                    let attachHtml = msg.attachment_path ? `<div class="mb-1"><a href="${msg.attachment_path}" target="_blank"><img src="${msg.attachment_path}" style="max-width:150px; border-radius:8px;"></a></div>` : '';
                    let ticks = isMe ? (msg.is_read == 1 ? '<i class="fas fa-check-double text-primary"></i>' : '<i class="fas fa-check"></i>') : '';

                    html += `
                        <div class="message ${type}" id="msg-${msg.id}">
                            ${replyHtml} ${attachHtml}
                            <div>${msg.message || ''}</div>
                            <div class="msg-time">
                                ${msg.formatted_time} ${ticks}
                                <div class="dropdown ms-1">
                                    <i class="fas fa-chevron-down text-muted" style="cursor:pointer; font-size:10px;" data-bs-toggle="dropdown"></i>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li><a class="dropdown-item small" href="#" onclick="setReply(${msg.id}, '${escapeHtml(msg.message)}')"><i class="fas fa-reply"></i> Reply</a></li>
                                        <li><a class="dropdown-item small text-danger" href="#" onclick="deleteMessage(${msg.id})"><i class="fas fa-trash"></i> Delete For Me</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    `;
                });
                msgBox.innerHTML = html;
                
                const isNearBottom = msgBox.scrollHeight - msgBox.scrollTop - msgBox.clientHeight < 150;
                if (forceScroll || isNearBottom) {
                    scrollToBottom();
                }
                lastMsgCount = data.messages.length;
            }
        });
    }

    function deleteMessage(id) {
        if(confirm('Delete this message for yourself?')) {
            fetch(`${API_URL}?action=delete_message`, { 
                method: 'POST', 
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}, 
                body: `message_id=${id}` 
            }).then(() => { document.getElementById('msg-'+id).remove(); });
        }
    }
    
    function clearChat() {
        if(confirm('Clear chat history?')) {
            fetch(`${API_URL}?action=clear_chat`, { 
                method: 'POST', 
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}, 
                body: `partner_id=${chatWithId}` 
            }).then(() => location.reload());
        }
    }

    function setReply(id, text) {
        document.getElementById('replyToId').value = id;
        document.getElementById('replyPreview').style.display = 'flex';
        document.getElementById('replyTextDisplay').innerText = text || 'Attachment';
    }

    function cancelReply() {
        document.getElementById('replyToId').value = '';
        document.getElementById('replyPreview').style.display = 'none';
    }

    function showFilePreview() {
        const f = document.getElementById('fileInput').files[0];
        if(f) {
            document.getElementById('filePreview').style.display = 'block';
            document.getElementById('fileNameDisplay').innerText = "📎 " + f.name;
        }
    }

    function clearFile() {
        document.getElementById('fileInput').value = '';
        document.getElementById('filePreview').style.display = 'none';
    }

    function toggleEmoji() {
        const p = document.getElementById('emoji-popover');
        p.style.display = (p.style.display === 'block') ? 'none' : 'block';
    }

    document.querySelector('emoji-picker').addEventListener('emoji-click', e => {
        document.getElementById('message_text').value += e.detail.unicode;
        toggleEmoji();
    });

    function escapeHtml(text) {
        if(!text) return '';
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }

    if(chatWithId) {
        fetchMessages(true);
        setInterval(() => fetchMessages(), 2000);
    }
</script>

</body>
</html>