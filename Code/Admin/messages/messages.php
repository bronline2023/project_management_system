<?php
/**
 * Code/Admin/messages/messages.php
 * PREMIUM WHATSAPP-STYLE MESSENGER (Admin Side)
 * Features: Emojis, Stickers, Voice Notes, IST Timezone, Real-time Status
 */

require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'messages.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$chatWithId = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : 0;
$chatUser = null;

// Fetch all available chat partners
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Messages</title>
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow: hidden; margin: 0; }
        .chat-container { display: flex; height: calc(100vh - 160px); min-height: 500px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin: 0; border: 1px solid #d1d7db; position: relative; overflow: hidden; }

        
        /* Sidebar */
        .users-list { width: 30%; min-width: 320px; border-right: 1px solid #d1d7db; background: #fff; display: flex; flex-direction: column; height: 100%; transition: 0.3s; }
        .users-header { padding: 15px; background: #f0f2f5; border-bottom: 1px solid #d1d7db; display: flex; align-items: center; justify-content: space-between; height: 60px; flex-shrink: 0; }
        .users-scroll { flex: 1; overflow-y: auto; background: #fff; }
        .user-item { display: flex; align-items: center; padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #f0f2f5; transition: 0.2s; text-decoration: none; color: inherit; }
        .user-item:hover { background: #f5f6f6; }
        .user-item.active { background: #ebebeb; }
        .user-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; margin-right: 15px; border: 1px solid #eee; flex-shrink: 0; }
        .unread-badge { background: #25d366; color: white; font-size: 11px; padding: 2px 7px; border-radius: 12px; font-weight: bold; margin-left: auto; }
        
        /* Chat Area */
        .chat-area { width: 70%; display: flex; flex-direction: column; background: #e5ddd5; position: relative; height: 100%; }
        .chat-area::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); opacity: 0.08; pointer-events: none; z-index: 1; }
        
        .chat-header { padding: 10px 15px; background: #f0f2f5; border-bottom: 1px solid #d1d7db; display: flex; align-items: center; height: 60px; z-index: 10; position: relative; }
        .messages-box { flex: 1; overflow-y: auto; padding: 20px 5%; display: flex; flex-direction: column; gap: 4px; z-index: 5; scroll-behavior: smooth; position: relative; }
        
        /* Bubbles */
        .message { max-width: 65%; padding: 6px 10px 8px 10px; border-radius: 8px; font-size: 14.5px; position: relative; box-shadow: 0 1px 0.5px rgba(0,0,0,0.13); margin-bottom: 2px; line-height: 1.4; word-wrap: break-word; display: flex; flex-direction: column; z-index: 10; }
        .message.sent { align-self: flex-end; background: #d9fdd3; border-top-right-radius: 0; }
        .message.received { align-self: flex-start; background: #ffffff; border-top-left-radius: 0; }
        .msg-meta { display: flex; justify-content: flex-end; align-items: center; gap: 4px; margin-top: -2px; font-size: 10.5px; color: #667781; }
        .msg-attachment { width: 100%; max-width: 250px; border-radius: 6px; margin-bottom: 5px; cursor: pointer; border: 1px solid #f0f2f5; }
        
        /* Input Area */
        .input-area { padding: 10px 15px; background: #f0f2f5; display: flex; align-items: center; gap: 10px; z-index: 20; position: relative; }
        .input-area input { flex: 1; padding: 11px 15px; border-radius: 8px; border: none; outline: none; font-size: 15px; background: #fff; color: #111b21; }
        .btn-icon { background: none; border: none; font-size: 22px; color: #54656f; cursor: pointer; padding: 5px; border-radius: 50%; transition: 0.1s; }
        .btn-icon:hover { background: rgba(0,0,0,0.05); }
        .btn-send { background: none; color: #54656f; border: none; font-size: 24px; cursor: pointer; }
        .btn-send.active { color: #00a884; }

        /* Panels */
        .extra-panel { position: absolute; bottom: 75px; left: 15px; background: #fff; width: 320px; height: 350px; box-shadow: 0 -2px 15px rgba(0,0,0,0.15); border-radius: 10px; z-index: 100; display: none; flex-direction: column; overflow: hidden; border: 1px solid #ddd; }
        .panel-tabs { display: flex; background: #f0f2f5; border-bottom: 1px solid #ddd; }
        .panel-tab { flex: 1; padding: 10px; text-align: center; cursor: pointer; font-size: 18px; }
        .panel-tab.active { border-bottom: 3px solid #00a884; background: #fff; }
        .panel-content { flex: 1; overflow-y: auto; padding: 12px; display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; font-size: 26px; }
        .sticker-item { width: 100%; height: auto; cursor: pointer; transition: 0.2s; border-radius: 4px; }
        .sticker-item:hover { transform: scale(1.15); background: #eee; }
        
        /* Voice Recorder */
        .recording-ui { flex: 1; display: none; align-items: center; justify-content: space-between; background: #f0f2f5; padding: 5px 15px; color: #dc3545; font-weight: bold; border-radius: 10px; }
        .rec-dot { width: 10px; height: 10px; background: #dc3545; border-radius: 50%; animation: blink 1s infinite; margin-right: 10px; }
        @keyframes blink { 0% {opacity: 1;} 50% {opacity: 0.3;} 100% {opacity: 1;} }

        /* Mobile Viewport Fix - FULL SCREEN TAKEOVER */
        @media (max-width: 768px) {
            .chat-container { 
                position: fixed; top: 0; left: 0; width: 100vw; height: 100dvh; 
                z-index: 10000; border: none; border-radius: 0; margin: 0;
                background: #fff;
            }
            .users-list { width: 100%; min-width: 100%; display: <?= $chatWithId ? 'none' : 'flex' ?>; height: 100%; }
            .chat-area { width: 100%; display: <?= $chatWithId ? 'flex' : 'none' ?>; height: 100%; }
            .btn-back { display: block !important; }
            .extra-panel { width: 95%; left: 2.5%; bottom: 85px; }
            .messages-box { padding: 10px 10px 20px 10px; }
            .message { max-width: 92%; }
            .chat-header { height: 60px; padding: 5px 15px; }
            .users-header { height: 60px; padding: 5px 15px; }
            .input-area { padding: 8px 10px; gap: 5px; }
        }

        .user-avatar { transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; border: 2px solid transparent; }
        .user-avatar:hover { transform: scale(1.8); z-index: 1000; position: relative; box-shadow: 0 8px 25px rgba(0,0,0,0.4); border-color: #00a884; }
        
        .btn-back { display: none; font-size: 18px; border: none; background: none; margin-right: 10px; cursor: pointer; }
        .online-dot { color: #00a884; font-size: 11px; margin-right: 4px; }
    </style>
</head>
<body>

<div class="chat-container">
    
    <!-- Sidebar -->
    <div class="users-list">
        <div class="users-header">
            <h5 class="m-0">Admin Messaging</h5>
            <div class="d-flex gap-2">
                <a href="index.php?page=dashboard" class="btn-icon"><i class="fas fa-home"></i></a>
            </div>
        </div>
        <div class="users-scroll">
            <?php foreach ($chatPartners as $user): 
                $pic = !empty($user['profile_picture']) ? $user['profile_picture'] : '';
                $pic_url = ASSETS_URL . 'images/default_avatar.png';
                if (!empty($pic)) {
                    if (strpos($pic, '/') !== false) $pic_url = UPLOADS_URL . $pic;
                    else $pic_url = UPLOADS_URL . 'profile_pictures/' . $pic;
                }
            ?>
                <a href="index.php?page=messages&chat_with=<?= $user['id'] ?>" class="user-item <?= $chatWithId == $user['id'] ? 'active' : '' ?>">
                    <img src="<?= $pic_url ?>" class="user-avatar">
                    <div style="flex:1; overflow:hidden;">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-truncate"><?= htmlspecialchars($user['name']) ?></strong>
                        </div>
                        <small class="text-muted d-block text-truncate"><?= htmlspecialchars($user['role_name']) ?></small>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Chat View -->
    <div class="chat-area">
        <?php if ($chatWithId && $chatUser): ?>
            
            <div class="chat-header">
                <button class="btn-back" onclick="window.location.href='index.php?page=messages'"><i class="fas fa-arrow-left"></i></button>
                <?php 
                    $cpic = !empty($chatUser['profile_picture']) ? $chatUser['profile_picture'] : '';
                    $cpic_url = ASSETS_URL . 'images/default_avatar.png';
                    if (!empty($cpic)) {
                        if (strpos($cpic, '/') !== false) $cpic_url = UPLOADS_URL . $cpic;
                        else $cpic_url = UPLOADS_URL . 'profile_pictures/' . $cpic;
                    }
                ?>
                <img src="<?= $cpic_url ?>" class="user-avatar" style="width: 38px; height: 38px;">
                <div>
                    <h6 class="m-0"><?= htmlspecialchars($chatUser['name']) ?></h6>
                    <span id="onlineIndicator" style="font-size: 11px; color: #667781;">Checking...</span>
                </div>
                <div class="ms-auto d-flex items-center">
                    <button class="btn-icon text-danger" onclick="clearChat()"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>

            <div class="messages-box" id="msgBox">
                <div class="text-center mt-5 text-muted"><i class="fas fa-spinner fa-spin"></i> Loading conversation...</div>
            </div>

            <!-- Floating Panels -->
            <div id="extraPanel" class="extra-panel">
                <div class="panel-tabs">
                    <div class="panel-tab active" onclick="switchPanel('emoji')">😊</div>
                    <div class="panel-tab" onclick="switchPanel('sticker')">🎭</div>
                </div>
                <div id="panelContent" class="panel-content"></div>
            </div>

            <div id="recordingUI" class="recording-ui">
                <div class="d-flex align-items-center"><div class="rec-dot"></div> Recording... <span id="recTimer" class="ms-2">00:00</span></div>
                <div class="d-flex gap-2">
                    <button class="btn-icon text-danger" onclick="cancelRecording()"><i class="fas fa-trash"></i></button>
                    <button class="btn-icon text-success" onclick="stopAndSendVoice()"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>

            <form id="chatForm" class="input-area" enctype="multipart/form-data" onsubmit="return false;">
                <input type="hidden" name="receiver_id" value="<?= $chatWithId ?>">
                <input type="file" id="fileInput" name="attachment" style="display: none;" onchange="previewFile()">
                
                <div class="d-flex align-items-center">
                    <button type="button" class="btn-icon" onclick="toggleExtraPanel()"><i class="far fa-smile"></i></button>
                    <button type="button" class="btn-icon" onclick="document.getElementById('fileInput').click()"><i class="fas fa-paperclip"></i></button>
                </div>
                
                <input type="text" id="message_text" name="message_text" placeholder="Type a message" autocomplete="off" oninput="toggleSendBtn()">
                
                <div id="inputBtns">
                    <button type="button" id="micBtn" class="btn-icon" onclick="startRecording()"><i class="fas fa-microphone"></i></button>
                    <button type="button" id="sendBtn" class="btn-send" style="display:none;" onclick="handleMsgSubmit()"><i class="fas fa-paper-plane"></i></button>
                </div>
            </form>
            <div id="filePreview" class="px-3 pb-2 small text-success fw-bold" style="display:none; background:#f0f2f5;"></div>

        <?php else: ?>
            <div class="d-flex align-items-center justify-content-center h-100 flex-column text-muted" style="z-index: 10; position: relative;">
                <i class="fab fa-whatsapp fa-4x mb-3 text-success" style="opacity: 0.5;"></i>
                <h5>Select a user to chat</h5>
                <p class="small">Messages are encrypted with IST Timeline</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const chatWithId = <?= $chatWithId ?>;
    const currentUserId = <?= (int)$currentUserId ?>;
    const msgBox = document.getElementById('msgBox');
    const APP_URL = '<?= APP_URL ?>';
    const API_URL = APP_URL + 'chat_api.php';
    const emojis = ["😊","😂","🤣","❤️","😍","👍","🙏","🔥","✨","✅","❌","📍","📞","🏠","🕐","⭐","💯","🚀","😎","🥺","🤔","🙌","🎉","🎁","🎂","🌹","🥂","💪","🌈","🎈"];
    const stickers = [
        "https://cdn-icons-png.flaticon.com/512/3248/3248235.png",
        "https://cdn-icons-png.flaticon.com/512/3248/3248243.png",
        "https://cdn-icons-png.flaticon.com/512/3249/3249821.png",
        "https://cdn-icons-png.flaticon.com/512/3247/3247447.png",
        "https://cdn-icons-png.flaticon.com/512/3247/3247310.png",
        "https://cdn-icons-png.flaticon.com/512/2584/2584606.png",
        "https://cdn-icons-png.flaticon.com/512/2584/2584617.png",
        "https://cdn-icons-png.flaticon.com/512/2584/2584656.png"
    ];

    let mediaRecorder;
    let audioChunks = [];
    let recInterval;

    function toggleSendBtn() {
        const val = document.getElementById('message_text').value.trim();
        const file = document.getElementById('fileInput').files[0];
        const mic = document.getElementById('micBtn');
        const send = document.getElementById('sendBtn');
        if(val || file) {
            mic.style.display = 'none';
            send.style.display = 'block';
            send.classList.add('active');
        } else {
            mic.style.display = 'block';
            send.style.display = 'none';
        }
    }

    function toggleExtraPanel() {
        const p = document.getElementById('extraPanel');
        p.style.display = (p.style.display === 'flex') ? 'none' : 'flex';
        if(p.style.display === 'flex') switchPanel('emoji');
    }

    function switchPanel(type) {
        const content = document.getElementById('panelContent');
        const tabs = document.querySelectorAll('.panel-tab');
        tabs.forEach(t => t.classList.remove('active'));
        if(type === 'emoji') {
            tabs[0].classList.add('active');
            content.style.gridTemplateColumns = 'repeat(6, 1fr)';
            content.innerHTML = emojis.map(e => `<div onclick="addEmoji('${e}')" style="cursor:pointer; text-align:center;">${e}</div>`).join('');
        } else {
            tabs[1].classList.add('active');
            content.style.gridTemplateColumns = 'repeat(3, 1fr)';
            content.innerHTML = stickers.map(s => `<img src="${s}" class="sticker-item" onclick="sendSticker('${s}')">`).join('');
        }
    }

    function addEmoji(e) {
        document.getElementById('message_text').value += e;
        toggleSendBtn();
    }

    function sendSticker(url) {
        let formData = new FormData();
        formData.append('receiver_id', chatWithId);
        formData.append('message_text', `<img src="${url}" style="width:100px;">`);
        fetch(`${API_URL}?action=send_message`, { method: 'POST', body: formData })
        .then(() => { toggleExtraPanel(); fetchMessages(true); });
    }

    function startRecording() {
        navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.start();
            audioChunks = [];
            document.getElementById('chatForm').style.display = 'none';
            document.getElementById('recordingUI').style.display = 'flex';
            
            let sec = 0;
            recInterval = setInterval(() => {
                sec++;
                let m = Math.floor(sec/60).toString().padStart(2, '0');
                let s = (sec%60).toString().padStart(2, '0');
                document.getElementById('recTimer').innerText = `${m}:${s}`;
            }, 1000);

            mediaRecorder.addEventListener("dataavailable", e => audioChunks.push(e.data));
        });
    }

    function stopAndSendVoice() {
        if(!mediaRecorder) return;
        mediaRecorder.stop();
        clearInterval(recInterval);
        mediaRecorder.addEventListener("stop", () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            let formData = new FormData();
            formData.append('receiver_id', chatWithId);
            formData.append('attachment', audioBlob, `voice_${Date.now()}.webm`);
            formData.append('message_text', '<i class="fas fa-microphone"></i> Voice Message');

            fetch(`${API_URL}?action=send_message`, { method: 'POST', body: formData })
            .then(() => cancelRecording());
        });
    }

    function cancelRecording() {
        if(mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
        clearInterval(recInterval);
        document.getElementById('chatForm').style.display = 'flex';
        document.getElementById('recordingUI').style.display = 'none';
        document.getElementById('recTimer').innerText = '00:00';
    }

    function handleMsgSubmit() {
        const text = document.getElementById('message_text').value.trim();
        const file = document.getElementById('fileInput').files[0];
        if(!text && !file) return;

        let formData = new FormData(document.getElementById('chatForm'));
        document.getElementById('message_text').value = '';
        document.getElementById('fileInput').value = '';
        document.getElementById('filePreview').style.display = 'none';
        toggleSendBtn();

        fetch(`${API_URL}?action=send_message`, { method: 'POST', body: formData })
        .then(() => fetchMessages(true));
    }

    function clearChat() {
        if(!confirm('Clear ALL messages?')) return;
        let formData = new FormData();
        formData.append('partner_id', chatWithId);
        fetch(`${API_URL}?action=clear_chat`, { method: 'POST', body: formData })
        .then(() => fetchMessages(true));
    }

    // Enter Key
    document.getElementById('message_text')?.addEventListener('keypress', (e) => {
        if(e.key === 'Enter') handleMsgSubmit();
    });

    function previewFile() {
        const file = document.getElementById('fileInput').files[0];
        const preview = document.getElementById('filePreview');
        if (file) {
            preview.style.display = 'block';
            preview.innerHTML = `<i class="fas fa-file-image"></i> ${file.name}`;
            toggleSendBtn();
        } else {
            preview.style.display = 'none';
        }
    }

    function fetchMessages(forceScroll = false) {
        if (!chatWithId) return;
        fetch(`${API_URL}?action=fetch_chat&chat_with=${chatWithId}&t=${Date.now()}`)
            .then(res => res.json())
            .then(data => {
                const indicator = document.getElementById('onlineIndicator');
                if(indicator) {
                    if(data.user_status.status === 'online') {
                        indicator.innerHTML = '<span class="online-dot">●</span> Online';
                        indicator.style.color = '#00a884';
                    } else {
                        indicator.innerHTML = data.user_status.text;
                        indicator.style.color = '#667781';
                    }
                }

                let html = '';
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        let isMe = (msg.sender_id == currentUserId);
                        let type = isMe ? 'sent' : 'received';
                        let attachmentHtml = '';
                        if(msg.attachment_path) {
                            if(msg.attachment_path.endsWith('.webm')) {
                                attachmentHtml = `<audio controls src="${msg.attachment_path}" style="height:35px; width:200px; margin-bottom:5px;"></audio>`;
                            } else {
                                attachmentHtml = `<a href="${msg.attachment_path}" target="_blank"><img src="${msg.attachment_path}" class="msg-attachment"></a>`;
                            }
                        }
                        let ticks = isMe ? (msg.is_read == 1 ? '<i class="fas fa-check-double" style="color:#53bdeb;"></i>' : '<i class="fas fa-check"></i>') : '';
                        
                        html += `<div class="message ${type}">
                                    ${attachmentHtml}
                                    <div style="font-size:14.5px;">${msg.message || ''}</div>
                                    <div class="msg-meta">${msg.formatted_time} <span class="ms-1">${ticks}</span></div>
                                 </div>`;
                    });
                } else {
                    html = '<div class="text-center text-muted mt-5 small">No messages yet.</div>';
                }

                if(msgBox.innerHTML !== html) {
                    const nearBottom = msgBox.scrollTop + msgBox.clientHeight >= msgBox.scrollHeight - 200;
                    msgBox.innerHTML = html;
                    if(forceScroll || nearBottom) msgBox.scrollTop = msgBox.scrollHeight;
                }
            });
    }

    if (chatWithId) {
        fetchMessages(true);
        setInterval(() => fetchMessages(), 3000);
    }
</script>

</body>
</html>