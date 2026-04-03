<?php
// Smart Checkout integrated
/**
 * views/poster_studio.php
 */

$pdo = connectDB();
$currency = '₹';
$user_role = $_SESSION['user_role'] ?? 'guest';

$service_rate = 2.00;
$points_rate = 0;
$user_balance = 0.00;
$user_points = 0;
$is_custom_rate = false;
$custom_poster_rate = 0.00;

try {
    $stmt_rate = $pdo->prepare("SELECT price, points_price FROM digital_service_rates WHERE service_slug = 'poster_studio' AND is_active = 1");
    $stmt_rate->execute();
    $rate_data = $stmt_rate->fetch();
    if ($rate_data) {
        $service_rate = (float)$rate_data['price'];
        $points_rate = (int)$rate_data['points_price'];
    }

    $stmt = $pdo->query("SELECT currency_symbol FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings && isset($settings['currency_symbol'])) { $currency = $settings['currency_symbol']; }

    if (isset($_SESSION['user_id'])) {
        $stmt_user = $pdo->prepare("SELECT balance, poster_points, custom_poster_rate FROM users WHERE id = ?");
        $stmt_user->execute([$_SESSION['user_id']]);
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            $user_balance = (float)$user_data['balance'];
            $user_points = (int)$user_data['poster_points'];
            if ($user_data['custom_poster_rate'] !== null && $user_data['custom_poster_rate'] !== '') {
                $custom_poster_rate = (float)$user_data['custom_poster_rate'];
                $is_custom_rate = true;
            }
        }
    }
} catch (Exception $e) {}

// Fetch Draft if ID is provided
$loaded_draft_json = 'null';
$loaded_draft_name = '';
$loaded_draft_id = 0;

if (isset($_GET['draft_id']) && isset($_SESSION['user_id'])) {
    $draft_id = (int)$_GET['draft_id'];
    $stmt = $pdo->prepare("SELECT draft_name, canvas_json FROM digital_service_history WHERE id = ? AND user_id = ? AND is_draft = 1");
    $stmt->execute([$draft_id, $_SESSION['user_id']]);
    $draft = $stmt->fetch();
    if ($draft) {
        $loaded_draft_name = $draft['draft_name'] ?? '';
        $loaded_draft_id = $draft_id;
        if (str_starts_with($draft['canvas_json'], 'FILE:')) {
            $filepath = UPLOADS_PATH . 'drafts/' . str_replace('FILE:', '', $draft['canvas_json']);
            if (file_exists($filepath)) $loaded_draft_json = file_get_contents($filepath);
        } else {
            $loaded_draft_json = $draft['canvas_json'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poster Studio Pro | Premium Design Suite</title>
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>img/br_favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Anek+Gujarati:wght@400;700&family=Noto+Sans+Gujarati:wght@400;700&family=Hind+Vadodara:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@imgly/background-removal@latest/dist/index.js"></script>
    <!-- Cache-Busting Build: <?= APP_VERSION ?> -->
</head>

<style>
    #sidebar { display: none !important; }
    .navbar { display: none !important; }
    #content { padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    .wrapper { margin: 0 !important; padding: 0 !important; }
    body { background-color: #0f172a; margin: 0; padding: 0; font-family: 'Inter', 'Segoe UI', sans-serif; overflow: hidden; }
    
    .studio-wrapper { display: flex; height: 100vh; width: 100vw; background: #0f172a; color: #f8fafc; }
    
    /* Left Panel: Project & Tools */
    .studio-panel { width: 350px; min-width: 350px; background: #1e293b; display: flex; flex-direction: column; border-right: 1px solid #334155; z-index: 10; height: 100%; box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
    
    /* Right Panel: Live Editor */
    .live-editor-bar { width: 400px; min-width: 400px; background: #111827; display: flex; flex-direction: column; border-left: 1px solid #334155; z-index: 10; height: 100%; box-shadow: -10px 0 30px rgba(0,0,0,0.5); }
    
    .studio-header { padding: 15px; background: #111827; color: #10b981; text-align: center; font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
    .btn-refresh { background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; text-decoration: none;}
    .btn-refresh:hover { background: #2563eb; color: white; text-decoration: none;}
    
    .controls-area { flex-grow: 1; overflow-y: auto; padding: 20px; background: #1e293b; }
    .live-editor-area { flex-grow: 1; overflow-y: auto; padding: 20px; background: #111827; }
    
    .workspace { flex-grow: 1; display: flex; justify-content: center; align-items: center; overflow: auto; padding: 20px; background: #0f172a; background-image: radial-gradient(#334155 1px, transparent 0); background-size: 30px 30px; position: relative; }
    .studio-canvas-layout { box-shadow: 0 25px 60px rgba(0,0,0,0.7); background: white; border-radius: 4px; overflow: hidden; }

    .control-box { background: linear-gradient(145deg, #1e293b, #0f172a); padding: 15px; border-radius: 12px; margin-bottom: 15px; border: 1px solid #334155; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
    .control-title { font-weight: 800; font-size: 14px; color: #e2e8f0; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #334155; padding-bottom: 8px; }
    .form-label { font-size: 10px; font-weight: 700; color: #94a3b8; margin-bottom: 6px; display: block; text-transform: uppercase; }
    .form-control { width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: white; font-size: 12px; outline: none; transition: 0.2s; }
    
    .bg-tool-row { display: flex; gap: 10px; align-items: center; margin-top: 5px; flex-wrap: wrap; background: #0f172a; padding: 8px; border-radius: 6px; border: 1px solid #334155; }
    .tool-group { display: flex; flex-direction: column; font-size: 10px; font-weight: bold; color: #94a3b8; align-items: flex-start; }
    input[type="color"] { width: 35px; height: 28px; border: 1px solid #334155; cursor: pointer; border-radius: 4px; padding: 0; background: transparent; }
    
    .field-card { background: rgba(30, 41, 59, 0.9); border: 1px solid #475569; border-radius: 12px; padding: 15px; margin-bottom: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.3); transition: 0.3s; }
    .field-card.hidden-field { opacity: 0.4; filter: grayscale(1); }
    .title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; background: rgba(15,23,42,0.8); padding: 10px; border-radius: 8px; border: 1px solid #334155; }
    .field-title-input { font-weight: bold; font-size: 13px; border: none; color: #f59e0b; width: 40%; outline: none; background: transparent; }
    .title-style-box { display: flex; align-items: center; gap: 4px; background: #1e293b; padding: 4px 6px; border-radius: 4px; border: 1px solid #334155; }
    
    .field-text { width: 100%; padding: 8px; border: 1px solid #334155; border-radius: 8px; resize: vertical; min-height: 50px; margin-bottom: 10px; font-size: 13px; background: #0f172a; color: white; }
    .tools-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; background: #0f172a; padding: 8px; border-radius: 6px; border: 1px solid #334155; }
    .tools-row select { padding: 4px; border: 1px solid #334155; border-radius: 4px; font-size: 10px; background: #1e293b; color: white; }
    .tools-row input[type="number"] { width: 40px; padding: 4px; border: 1px solid #334155; border-radius: 4px; font-size: 10px; background: #1e293b; color: white; }
    
    .tbl-grid-container { width: 100%; overflow-x: auto; margin: 10px 0; border: 1px solid #334155; border-radius: 8px; padding: 5px; background: #0f172a; }
    .tbl-row-ui { display: flex; gap: 4px; margin-bottom: 4px; }
    .tbl-cell-ui { flex: 1; padding: 6px; border: 1px solid #475569; border-radius: 4px; font-size: 11px; background: #1e293b; color: white; }

    .tool-group { display: flex; align-items: center; gap: 4px; background: rgba(15,23,42,0.5); padding: 2px 6px; border-radius: 4px; border: 1px solid #334155; font-size: 9px; color: #cbd5e1; }
    .btn-layer { padding: 5px 10px; border: none; border-radius: 6px; font-weight: bold; font-size: 10px; cursor: pointer; color: white; background: #475569; transition: 0.2s; flex: 1; display: flex; align-items: center; justify-content: center; gap: 4px; }
    .btn-layer:hover { background: #64748b; }

    .action-btns { padding: 15px; background: #0f172a; border-top: 1px solid #334155; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
    .btn-main { padding: 10px; border: none; border-radius: 8px; font-weight: 800; font-size: 11px; cursor: pointer; color: white; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 5px; }
    .btn-main:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
    
    .btn-add-text { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .btn-add-img { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .btn-add-table { background: linear-gradient(135deg, #f59e0b, #d97706); }
    
    .export-group { display: flex; width: 100%; margin-top: 8px; gap: 8px; grid-column: span 3; }
    .btn-export { background: linear-gradient(135deg, #22c55e, #16a34a); flex: 1; font-size: 14px; }

    @media (max-width: 1200px) {
        .live-editor-bar { width: 300px; min-width: 300px; }
        .studio-panel { width: 280px; min-width: 280px; }
    }
    </style>
</head>
<body>
<?php $page_title = 'Poster Studio Pro'; require_once INCLUDES_PATH.'digital_header.php'; ?>
<div class="studio-wrapper" style="height: calc(100vh - 65px); min-height: calc(100vh - 65px);">
    <!-- Left Sidebar: Tools & Background -->
    <div class="studio-panel">
        <div class="studio-header">
            <span>Studio Tools</span>
            <div style="display: flex; gap: 5px;">
                <button class="btn-refresh" style="background:#64748b;" onclick="undo()" title="Undo (Ctrl+Z)"><i class="fas fa-undo"></i></button>
                <button class="btn-refresh" style="background:#64748b;" onclick="redo()" title="Redo (Ctrl+Y)"><i class="fas fa-redo"></i></button>
            </div>
        </div>
        
        <div class="controls-area">
            <h3 class="control-title"><i class="fas fa-layer-group"></i> 1. Layout & Design</h3>
            <div class="control-box">
                <label class="form-label text-warning mb-2"><i class="fas fa-language me-1"></i> Typing Language (Space/Enter applies Transliteration)</label>
                <select id="typingLang" class="form-control mb-3" style="border-color: #f59e0b; font-weight: bold; padding: 12px; font-size: 14px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <option value="en">English (No Translation)</option>
                    <option value="gu">Gujarati (ગુજરાતી)</option>
                    <option value="hi">Hindi (हिंदी)</option>
                </select>
                
                <label class="form-label" style="color:#10b981; font-weight:bold;">Project / Draft Name</label>
                <input type="text" id="draftNameInput" class="form-control" placeholder="Poster_Name..." value="<?= htmlspecialchars($loaded_draft_name ?? '') ?>">
                
                <label class="form-label">Background Type</label>
                <select id="bgSelect" onchange="applyPresetBackground()" class="form-control">
                    <option value="solid-white">Solid White</option>
                    <option value="none">No Background</option>
                    <option value="grad-navy">Navy Gradient</option>
                    <option value="grad-crimson">Crimson Gradient</option>
                    <option value="grad-forest">Forest Gradient</option>
                    <option value="grad-gold">Gold Gradient</option>
                </select>
                <div class="bg-tool-row">
                    <div class="tool-group">Solid <input type="color" id="bgSolidColor" value="#ffffff" oninput="applySolidBg()"></div>
                    <div class="tool-group">Grad 1 <input type="color" id="gradCol1" value="#ff0000" oninput="applyCustomGradient()"></div>
                    <div class="tool-group">Grad 2 <input type="color" id="gradCol2" value="#0000ff" oninput="applyCustomGradient()"></div>
                </div>
                <label class="form-label" style="margin-top: 10px;">Upload Background Image</label>
                <input type="file" id="bgImage" accept="image/*" class="form-control" onchange="applyBgImage(this)">
            </div>

            <h3 class="control-title"><i class="fas fa-stamp"></i> 2. Watermark / Logo</h3>
            <div class="control-box">
                <input type="text" id="wmText" placeholder="Text Watermark..." oninput="syncWatermark()" class="form-control">
                <input type="file" id="wmImageUpload" accept="image/*" class="form-control">
                <div style="display:flex; justify-content:space-between; margin-top:10px;">
                    <label style="font-size:11px; font-weight:bold;"><input type="checkbox" id="wmRepeat" onchange="syncWatermark()"> Tile Pattern</label>
                    <button class="btn-refresh" style="background:#ef4444; padding:2px 8px;" onclick="removeWatermark()"><i class="fas fa-trash"></i></button>
                </div>
                <div class="bg-tool-row" style="margin-top:5px;">
                    <div class="tool-group">Color <input type="color" id="wmColor" value="#cccccc" oninput="syncWatermark()"></div>
                    <div class="tool-group">Op% <input type="number" id="wmOpacity" value="25" min="0" max="100" oninput="syncWatermark()" style="width:35px;"></div>
                    <div class="tool-group">Size <input type="number" id="wmSize" value="60" min="10" max="200" oninput="syncWatermark()" style="width:35px;"></div>
                </div>
            </div>
            
            <h3 class="control-title"><i class="fas fa-plus-circle"></i> 3. Add Elements</h3>
            <div style="display: grid; grid-template-columns: 1fr; gap: 10px;">
                <button class="btn-main btn-add-text" onclick="createNewTextField()"><i class="fas fa-font"></i> Add New Text</button>
                <button class="btn-main btn-add-img" onclick="createNewImageField()"><i class="fas fa-image"></i> Add Photo Frame</button>
                <button class="btn-main btn-add-table" onclick="createNewTableField()"><i class="fas fa-table"></i> Add Custom Table</button>
            </div>
        </div>

        <div class="action-btns">
            <div class="export-group">
                <select id="exportFormat" class="form-control" style="width: 70px; margin-bottom: 0;">
                    <option value="png">PNG</option>
                    <option value="jpg">JPG</option>
                    <option value="pdf">PDF</option>
                </select>
                <select id="exportQuality" class="form-control" style="width: 60px; margin-bottom: 0;">
                    <option value="1">SD</option>
                    <option value="2" selected>HD</option>
                    <option value="3">4K</option>
                </select>
                <button class="btn-main btn-export" onclick="handleExport()"><i class="fas fa-download"></i> Download (<?= $currency . number_format($service_rate, 2) ?>)</button>
            </div>
            <?php if(isset($_SESSION['user_id'])): ?>
            <button class="btn-main" style="background:#059669; grid-column: span 2;" onclick="saveDraft(this, false)"><i class="fas fa-save"></i> Save Draft</button>
            <button class="btn-main" style="background:#f59e0b;" onclick="saveDraft(this, true)"><i class="fas fa-copy"></i> Copy</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Middle: Workspace -->
    <div class="workspace">
        <div class="studio-canvas-layout">
            <canvas id="posterCanvas"></canvas>
        </div>
        <div class="sys-zoom-controls" style="position: absolute; bottom: 20px; right: 20px; background: rgba(255,255,255,0.95); padding: 8px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); display: flex; flex-direction: column; gap: 5px; z-index: 9999; border: 1px solid #cbd5e1;">
            <button type="button" onclick="sysChangeZoom(0.1)" style="border:none; background:none; cursor:pointer;">➕</button>
            <button type="button" onclick="sysResetZoom()" style="border:none; background:none; cursor:pointer; font-size:10px; font-weight:bold;">100%</button>
            <button type="button" onclick="sysChangeZoom(-0.1)" style="border:none; background:none; cursor:pointer;">➖</button>
        </div>
    </div>

    <!-- Right Sidebar: Live Editor -->
    <div class="live-editor-bar" id="liveEditorSidebar">
        <div class="studio-header">
            <span><i class="fas fa-magic"></i> Live Editor Bar</span>
            <button class="btn-refresh" style="background:#3b82f6;" onclick="forceSyncAll()">🔄 Sync</button>
        </div>
        <div class="live-editor-area" id="dynamicFields">
            <div style="text-align:center; color:#94a3b8; margin-top:50px;">
                <i class="fas fa-mouse-pointer" style="font-size:30px; margin-bottom:15px;"></i>
                <p>Select an element or add a new one to edit properties here.</p>
            </div>
        </div>
    </div>
</div>

<script>
    // 0. DEFINITIONS FROM PHP (GLOBAL SCOPE)
    const APP_URL = "<?= APP_URL ?>";
    const baseUrl = '<?= BASE_URL ?>';
    const currency = '<?= $currency ?>';
    const userRole = '<?= $user_role ?>';
    const serviceRate = <?= $service_rate ?>;
    const pointsRate = <?= $points_rate ?>;
    const userBalance = <?= $user_balance ?>;
    const userPoints = <?= $user_points ?>;
    const isCustomRate = <?= $is_custom_rate ? 'true' : 'false' ?>;
    const customRate = <?= $custom_poster_rate ?>;
    const posterCost = serviceRate;
    const isGuest = (userRole === 'guest' || !userRole);

    // --- UI UTILITIES ---
    function showLoading(show, text = "Processing... Please wait.") {
        const el = document.getElementById('loadingOverlay');
        const txt = document.getElementById('loadingText');
        if (el) el.style.display = show ? 'flex' : 'none';
        if (txt && text) txt.innerText = text;
    }
    const hideLoader = () => showLoading(false);
    const showLoader = (text) => showLoading(true, text);

    function forceSyncAll() {
        if(typeof syncUIFromCurrentCanvas === 'function') syncUIFromCurrentCanvas();
    }

    let canvas = null; 
    let fieldCounter = 0; 
    let watermarkObj = null; 
    let wmImageSource = null;
    let globalCustomImages = {};
    let currentDraftId = <?= (int)($loaded_draft_id ?? 0) ?>;
    let currentDraftName = <?= json_encode($loaded_draft_name ?? '') ?>;
    let undoStack = [];
    let redoStack = [];
    let isStackLoading = false;

    // --- FABRIC EXTENSIONS ---
    fabric.Textbox.prototype._renderBackground = function(ctx) {
        if (!this.customBgEnable || !this.customBgColor) return;
        ctx.save();
        ctx.fillStyle = this.customBgColor;
        const p = this.customPadding || 0;
        const w = this.width + p * 2;
        const h = this.height + p * 2;
        const x = -this.width / 2 - p;
        const y = -this.height / 2 - p;
        
        if (this.customBgShape === 'round') {
            const r = 15;
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.lineTo(x + w - r, y);
            ctx.quadraticCurveTo(x + w, y, x + w, y + r);
            ctx.lineTo(x + w, y + h - r);
            ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
            ctx.lineTo(x + r, y + h);
            ctx.quadraticCurveTo(x, y + h, x, y + h - r);
            ctx.lineTo(x, y + r);
            ctx.quadraticCurveTo(x, y, x + r, y);
            ctx.closePath();
            ctx.fill();
        } else if (this.customBgShape === 'pill') {
            const r = h / 2;
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.lineTo(x + w - r, y);
            ctx.quadraticCurveTo(x + w, y, x + w, y + r);
            ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
            ctx.lineTo(x + r, y + h);
            ctx.quadraticCurveTo(x, y + h, x, y + h - r);
            ctx.quadraticCurveTo(x, y, x + r, y);
            ctx.closePath();
            ctx.fill();
        } else {
            ctx.fillRect(x, y, w, h);
        }
        ctx.restore();
    };

    // --- CORE INITIALIZATION ---
    window.addEventListener('load', function() { setTimeout(initApp, 200); });

    function initApp() {
        try {
            // Renamed to avoid double wrapping conflict with Fabric's 'canvas-container'
            canvas = new fabric.Canvas('posterCanvas', { width: 800, height: 1000, preserveObjectStacking: true });
            const initialDraftJson = <?= json_encode($loaded_draft_json) ?>;
            
            if (initialDraftJson && initialDraftJson !== 'null') {
                let parsedJson = (typeof initialDraftJson === 'string') ? JSON.parse(initialDraftJson) : initialDraftJson;
                showLoading(true, "Restoring your draft...");
                canvas.loadFromJSON(parsedJson, function() {
                    if (parsedJson.background) canvas.backgroundColor = parsedJson.background;
                    canvas.renderAll();
                    syncUIFromCurrentCanvas();
                    showLoading(false);
                    startHistoryTracking();
                });
            } else {
                canvas.backgroundColor = '#ffffff'; 
                canvas.renderAll();
                // Add default text field
                createNewTextField("Job Name", "Digital Poster Design", 48, '#0f172a', '#ffffff', 0, 'Noto Sans Gujarati', true, true);
                startHistoryTracking();
            }
            
            // Selection events to highlight cards
            canvas.on('selection:created', (e) => { if(e.selected.length) focusCard(e.selected[0]); });
            canvas.on('selection:updated', (e) => { if(e.selected.length) focusCard(e.selected[0]); });
        } catch(e) { 
            console.error("App Init Error:", e); 
            showLoading(false);
        }
    }

    function syncUIFromCurrentCanvas() {
        if (!canvas) return;
        const objs = canvas.getObjects();
        let maxId = 0;
        let processedIds = new Set();
        document.getElementById('dynamicFields').innerHTML = '';        objs.forEach(obj => {
            if (obj.customId) {
                let parts = obj.customId.split('_');
                if (parts.length >= 3) {
                    let type = parts[1];
                    let idNum = parseInt(parts[2]);
                    if (idNum > maxId) maxId = idNum;
                    
                    let fullId = parts[0] + '_' + parts[1] + '_' + parts[2];
                    if (!processedIds.has(fullId)) {
                        processedIds.add(fullId);
                        if (type === 'txt') {
                            let contentObj = objs.find(o => o.customId === fullId + '_content');
                            let titleObj = objs.find(o => o.customId === fullId + '_title');
                            if (contentObj || titleObj) {
                                createNewTextField((titleObj?titleObj.text:"Field"), (contentObj?contentObj.text:""), (contentObj?contentObj.fontSize:(titleObj?titleObj.fontSize:30)), (contentObj?contentObj.fill:"#000000"), (contentObj?(contentObj.stroke||"#ffffff"):"#ffffff"), (contentObj?(contentObj.strokeWidth||0):0), (contentObj?(contentObj.fontFamily||'Anek Gujarati'):'Anek Gujarati'), (contentObj?contentObj.fontWeight==='bold':false), !!titleObj, true, fullId);
                                setTimeout(() => {
                                    const card = document.getElementById('card_' + fullId);
                                    if (card && contentObj) {
                                        if (contentObj.customBgEnable) {
                                            card.querySelector('.box-bg-enable').checked = true;
                                            card.querySelector('.box-bg-color').value = contentObj.customBgColor || '#facc15';
                                            card.querySelector('.box-bg-shape').value = contentObj.customBgShape || 'square';
                                            card.querySelector('.box-padding').value = contentObj.customPadding || 12;
                                        }
                                        card.querySelector('.field-opacity').value = Math.round((contentObj.opacity || 1) * 100);
                                        card.querySelector('.field-align').value = contentObj.textAlign || 'center';
                                        card.querySelector('.field-spacing').value = contentObj.charSpacing || 0;
                                        card.querySelector('.field-lineheight').value = contentObj.lineHeight || 1.16;
                                        card.querySelector('.field-italic').checked = (contentObj.fontStyle === 'italic');
                                        card.querySelector('.field-underline').checked = !!contentObj.underline;
                                        card.querySelector('.field-uppercase').checked = !!contentObj.customIsUppercase;
                                        
                                        if (contentObj.shadow) {
                                            card.querySelector('.field-shadow-enable').checked = true;
                                            card.querySelector('.field-shadow-color').value = contentObj.shadow.color || '#000000';
                                            card.querySelector('.field-shadow-blur').value = contentObj.shadow.blur || 5;
                                        }
                                        if (titleObj) card.querySelector('.title-bg-color').value = titleObj.customBgColor || '#e2e8f0';
                                    }
                                }, 50);
                            }
                        } else if (type === 'img') {
                            createNewImageField(true, fullId);
                            if (obj.fill && obj.fill.source) globalCustomImages[fullId] = obj.fill.source;
                            setTimeout(() => {
                                const card = document.getElementById('card_' + fullId);
                                if (card) {
                                    card.querySelector('.img-opacity').value = Math.round((obj.opacity || 1) * 100);
                                    card.querySelector('.img-border-color').value = obj.stroke || '#ffffff';
                                    card.querySelector('.img-border-width').value = obj.strokeWidth || 0;
                                }
                            }, 50);
                        } else if (type === 'tbl') {
                            createNewTableField(true, fullId, obj.tblRows || 3, obj.tblCols || 2, obj.tblData || null, obj);
                        }
                    }
                }
            }
        });
        fieldCounter = maxId;
    }

    function focusCard(obj) {
        if (!obj || !obj.customId) return;
        let parts = obj.customId.split('_');
        if (parts.length < 3) return;
        let baseId = parts[0] + '_' + parts[1] + '_' + parts[2];
        const card = document.getElementById('card_' + baseId);
        if (card) {
            document.querySelectorAll('.field-card').forEach(c => c.style.borderColor = '#475569');
            card.style.borderColor = '#10b981';
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    // --- TEXT FIELDS ---
    function createNewTextField(defaultLabel="Label", defaultText="", defaultSize=30, defaultColor='#000000', defaultStroke='#ffffff', defaultStrokeW=0, defaultFont='Anek Gujarati', isBold=false, showTitle=false, skipCanvas=false, forcedId=null) {
        if (!skipCanvas && !forcedId) fieldCounter++; 
        const fieldId = forcedId || ('field_txt_' + fieldCounter);
        const html = `<div class="field-card" id="card_${fieldId}">
            <div class="title-row">
                <input type="text" class="field-title-input" value="${defaultLabel}" oninput="syncTextToCanvas('${fieldId}')">
                <div class="title-style-box">
                    <input type="color" class="title-bg-color" value="#e2e8f0" oninput="syncTextToCanvas('${fieldId}')" style="width:18px;">
                    <input type="color" class="title-text-color" value="#1e293b" oninput="syncTextToCanvas('${fieldId}')" style="width:18px;">
                    <label style="font-size:9px;"><input type="checkbox" class="field-show-title" ${showTitle?'checked':''} onchange="syncTextToCanvas('${fieldId}')"> Titles</label>
                </div>
                <button class="btn-refresh" style="background:#ef4444; padding:2px 6px;" onclick="deleteField('${fieldId}')"><i class="fas fa-trash"></i></button>
            </div>
            <textarea id="text_${fieldId}" class="field-text" oninput="syncTextToCanvas('${fieldId}')" onkeyup="handleTyping(event, '${fieldId}')">${defaultText}</textarea>
            
            <div class="tools-row">
                <select class="field-font" onchange="syncTextToCanvas('${fieldId}')" style="width:105px;">
                    <optgroup label="Gujarati">
                        <option value="Anek Gujarati">Anek Gujarati</option>
                        <option value="Noto Sans Gujarati">Noto Sans Guj</option>
                        <option value="Hind Vadodara">Hind Vadodara</option>
                    </optgroup>
                    <optgroup label="English/Modern">
                        <option value="Poppins">Poppins</option>
                        <option value="Inter">Inter</option>
                        <option value="Arial">Arial</option>
                    </optgroup>
                </select>
                <input type="number" class="field-size" value="${defaultSize}" oninput="syncTextToCanvas('${fieldId}')" style="width:40px;">
                <input type="color" class="field-color" value="${defaultColor}" oninput="syncTextToCanvas('${fieldId}')">
                <input type="number" class="field-opacity" value="100" min="0" max="100" oninput="syncTextToCanvas('${fieldId}')" style="width:40px;">
                <label style="font-size:11px; font-weight:900;"><input type="checkbox" class="field-bold" ${isBold?'checked':''} onchange="syncTextToCanvas('${fieldId}')"> B</label>
                <label style="font-size:11px; font-style:italic;"><input type="checkbox" class="field-italic" onchange="syncTextToCanvas('${fieldId}')"> I</label>
                <label style="font-size:11px; text-decoration:underline;"><input type="checkbox" class="field-underline" onchange="syncTextToCanvas('${fieldId}')"> U</label>
                <label style="font-size:11px; text-transform:uppercase;"><input type="checkbox" class="field-uppercase" onchange="syncTextToCanvas('${fieldId}')"> AA</label>
                <select class="field-align" onchange="syncTextToCanvas('${fieldId}')" style="width:45px;"><option value="left">L</option><option value="center" selected>C</option><option value="right">R</option></select>
                
                <div style="width:100%; border-top:1px solid #334155; margin:5px 0;"></div>
                
                <!-- Advanced Controls Group -->
                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:10px; width:100%;">
                    <div>
                        <span class="form-label" style="color:#fbbf24;">Effects</span>
                        <div style="display:flex; flex-wrap:wrap; gap:5px;">
                            <div class="tool-group">Stroke <input type="color" class="field-stroke" value="${defaultStroke}" oninput="syncTextToCanvas('${fieldId}')"></div>
                            <div class="tool-group">W <input type="number" class="field-stroke-w" value="${defaultStrokeW}" min="0" max="10" oninput="syncTextToCanvas('${fieldId}')" style="width:30px;"></div>
                            <div class="tool-group">Space <input type="number" class="field-spacing" value="0" step="10" oninput="syncTextToCanvas('${fieldId}')" style="width:35px;"></div>
                            <div class="tool-group">H <input type="number" class="field-lineheight" value="1.16" step="0.1" oninput="syncTextToCanvas('${fieldId}')" style="width:35px;"></div>
                        </div>
                    </div>
                    <div>
                        <span class="form-label" style="color:#10b981;">Shadow</span>
                        <div style="display:flex; flex-wrap:wrap; gap:5px;">
                            <input type="checkbox" class="field-shadow-enable" onchange="syncTextToCanvas('${fieldId}')">
                            <input type="color" class="field-shadow-color" value="#000000" oninput="syncTextToCanvas('${fieldId}')">
                            <input type="number" class="field-shadow-blur" value="5" min="0" max="50" oninput="syncTextToCanvas('${fieldId}')" style="width:30px;">
                        </div>
                    </div>
                </div>

                <div style="width:100%; border-top:1px solid #334155; margin:5px 0;"></div>
                <div style="display:flex; align-items:center; gap:8px; width:100%;">
                    <label style="font-size:10px;"><input type="checkbox" class="box-bg-enable" onchange="syncTextToCanvas('${fieldId}')"> 🔳 Box</label>
                    <input type="color" class="box-bg-color" value="#facc15" oninput="syncTextToCanvas('${fieldId}')">
                    <select class="box-bg-shape" onchange="syncTextToCanvas('${fieldId}')" style="width:65px;"><option value="square">Rect</option><option value="round">Round</option><option value="pill">Pill</option></select>
                    <input type="number" class="box-padding" value="12" oninput="syncTextToCanvas('${fieldId}')" style="width:35px;">
                    <label style="font-size:11px; color:orange;"><input type="checkbox" class="field-hide-all" onchange="toggleVisibility('${fieldId}')"> 👁️</label>
                </div>
            </div>
            <div style="display:flex; gap:5px; margin-top:8px;">
                <button class="btn-layer" onclick="moveLayer('${fieldId}', 'up')"><i class="fas fa-arrow-up"></i> Top</button>
                <button class="btn-layer" onclick="moveLayer('${fieldId}', 'down')"><i class="fas fa-arrow-down"></i> Back</button>
            </div>
        </div>`;
        document.getElementById('dynamicFields').insertAdjacentHTML('beforeend', html);
        if (!skipCanvas) syncTextToCanvas(fieldId);
    }

    function syncTextToCanvas(fieldId) {
        if(!canvas) return; const card = document.getElementById('card_' + fieldId); if(!card) return;
        const textVal = document.getElementById('text_' + fieldId).value; 
        const fontVal = card.querySelector('.field-font').value;
        const sizeVal = parseInt(card.querySelector('.field-size').value); 
        const colorVal = card.querySelector('.field-color').value;
        const opacity = (parseInt(card.querySelector('.field-opacity').value) || 100)/100;
        const isBold = card.querySelector('.field-bold').checked;
        const isItalic = card.querySelector('.field-italic').checked;
        const isUnderline = card.querySelector('.field-underline').checked;
        const isUppercase = card.querySelector('.field-uppercase').checked;
        const textAlign = card.querySelector('.field-align').value;
        
        let finalVal = textVal;
        if (isUppercase) finalVal = finalVal.toUpperCase();
        
        // Advanced
        const strokeVal = card.querySelector('.field-stroke').value;
        const strokeW = parseFloat(card.querySelector('.field-stroke-w').value) || 0;
        const spacing = parseInt(card.querySelector('.field-spacing').value) || 0;
        const lineHeight = parseFloat(card.querySelector('.field-lineheight').value) || 1.16;
        
        // Shadow
        const shadowEnable = card.querySelector('.field-shadow-enable').checked;
        let shadowObj = null;
        if(shadowEnable) {
            shadowObj = new fabric.Shadow({ color: card.querySelector('.field-shadow-color').value, blur: parseInt(card.querySelector('.field-shadow-blur').value) || 5, offsetX: 3, offsetY: 3 });
        }

        // Box Bg
        const bgEnable = card.querySelector('.box-bg-enable').checked; 
        const bgColor = card.querySelector('.box-bg-color').value;
        const bgShape = card.querySelector('.box-bg-shape').value; 
        const padding = parseInt(card.querySelector('.box-padding').value);

        let objTitle = canvas.getObjects().find(o => o.customId === fieldId + '_title');
        let objContent = canvas.getObjects().find(o => o.customId === fieldId + '_content');

        const titleVal = card.querySelector('.field-title-input').value; 
        const showTitle = card.querySelector('.field-show-title').checked;
        if (showTitle && titleVal) {
            const tStyles = { text: titleVal, fontFamily: fontVal, fontSize: sizeVal*0.7, fill: card.querySelector('.title-text-color').value, fontWeight: 'bold', customBgEnable: true, customBgColor: card.querySelector('.title-bg-color').value, customBgShape: 'pill', customPadding: 10, textAlign: 'center', opacity: opacity, visible: true };
            if (objTitle) objTitle.set(tStyles); 
            else { objTitle = new fabric.Textbox(titleVal, { customId: fieldId + '_title', left: 100, top: 100, ...tStyles }); canvas.add(objTitle); }
            objTitle.set({ width: objTitle.calcTextWidth() + 5 });
        } else if(objTitle) canvas.remove(objTitle);

        if (finalVal) {
            const cStyles = { 
                text: finalVal, fontFamily: fontVal, fontSize: sizeVal, fill: colorVal, 
                fontWeight: isBold?'bold':'normal', fontStyle: isItalic?'italic':'normal', underline: isUnderline, 
                textAlign: textAlign, customIsUppercase: isUppercase,
                stroke: strokeVal, strokeWidth: strokeW, charSpacing: spacing, lineHeight: lineHeight,
                shadow: shadowObj,
                customBgEnable: bgEnable, customBgColor: bgColor, customBgShape: bgShape, 
                customPadding: padding, opacity: opacity, visible: true 
            };
            if (objContent) objContent.set(cStyles);
            else { objContent = new fabric.Textbox(textVal, { customId: fieldId + '_content', left: 100, top: 160, ...cStyles }); canvas.add(objContent); }
            objContent.set({ width: objContent.calcTextWidth() + 10 });
        } else if(objContent) canvas.remove(objContent);
        canvas.renderAll();
    }

    // --- IMAGE FIELDS ---
    function createNewImageField(skipCanvas=false, forcedId=null) {
        if (!skipCanvas && !forcedId) fieldCounter++; 
        const fieldId = forcedId || ('field_img_' + fieldCounter);
        const html = `<div class="field-card" id="card_${fieldId}" style="border-color:#8b5cf6;">
            <div class="title-row" style="background:#f5f3ff;">
                <span style="font-size:12px; font-weight:bold; color:#7c3aed;">🖼️ Photo Frame</span>
                <button class="btn-refresh" style="background:#ef4444; padding:2px 6px;" onclick="deleteField('${fieldId}')"><i class="fas fa-trash"></i></button>
            </div>
            <input type="file" onchange="loadCustomImage(this, '${fieldId}')" class="form-control" style="font-size:10px;">
            <div class="tools-row" style="margin-top:5px; flex-wrap:wrap; gap:5px;">
                <select class="img-shape" onchange="updateImageShape('${fieldId}')" style="width:65px;"><option value="square">Rect</option><option value="circle">Circle</option><option value="hexagon">Hex</option></select>
                <select class="img-filter" onchange="applyFrameFilter(this, '${fieldId}')" style="width:65px; font-size:10px;">
                    <option value="none">Normal</option>
                    <option value="grayscale">B&W</option>
                    <option value="sepia">Sepia</option>
                    <option value="vintage">Vintage</option>
                    <option value="invert">Invert</option>
                    <option value="blur">Blur</option>
                </select>
                <div class="tool-group">Opac <input type="number" class="img-opacity" value="100" oninput="syncImageStyle('${fieldId}')" style="width:35px;"></div>
                <div class="tool-group">Stroke <input type="color" class="img-border-color" value="#ffffff" oninput="syncImageStyle('${fieldId}')"></div>
                <div class="tool-group">W <input type="number" class="img-border-width" value="0" oninput="syncImageStyle('${fieldId}')" style="width:30px;"></div>
                <label style="font-size:11px; color:orange; align-self:center;"><input type="checkbox" class="field-hide-all" onchange="toggleVisibility('${fieldId}')"> 👁️ Hide</label>
            </div>
             <div style="display:flex; gap:5px; margin-top:8px;">
                <button class="btn-layer" onclick="moveLayer('${fieldId}', 'up')"><i class="fas fa-arrow-up"></i> Front</button>
                <button class="btn-layer" style="background:#8b5cf6;" onclick="removeBgFromImage('${fieldId}')">BG Remove</button>
            </div>
        </div>`;
        document.getElementById('dynamicFields').insertAdjacentHTML('beforeend', html);
        if (!skipCanvas) {
            let p = new Image(); p.src="data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Crect width='200' height='200' fill='%23ccc'/%3E%3C/svg%3E";
            p.onload = () => { globalCustomImages[fieldId] = p; updateImageShape(fieldId); };
        }
    }

    function loadCustomImage(input, fieldId) {
        if (input.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => { globalCustomImages[fieldId] = img; updateImageShape(fieldId); };
                img.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function updateImageShape(fieldId) {
        if(!canvas) return; const card = document.getElementById('card_' + fieldId); if(!card) return;
        let obj = canvas.getObjects().find(o => o.customId === fieldId);
        let img = globalCustomImages[fieldId];
        let shape = card.querySelector('.img-shape').value;
        let dim = 300;
        let pattern = new fabric.Pattern({ source: img, repeat: 'no-repeat' });
        let scale = dim / Math.min(img.width, img.height);
        pattern.patternTransform = [scale, 0, 0, scale, (dim-img.width*scale)/2, (dim-img.height*scale)/2];
        
        if (obj) canvas.remove(obj);
        let props = { customId: fieldId, left: 100, top: 200, fill: pattern, originX: 'center', originY: 'center', cornerColor: '#8b5cf6' };
        if (shape === 'circle') obj = new fabric.Circle({ radius: dim/2, ...props });
        else obj = new fabric.Rect({ width: dim, height: dim, ...props });
        
        canvas.add(obj); canvas.renderAll();
        syncImageStyle(fieldId);
    }

    function syncImageStyle(fieldId) {
        const card = document.getElementById('card_' + fieldId);
        const obj = canvas.getObjects().find(o => o.customId === fieldId);
        if (!obj || !card) return;
        obj.set({ stroke: card.querySelector('.img-border-color').value, strokeWidth: parseInt(card.querySelector('.img-border-width').value), opacity: parseInt(card.querySelector('.img-opacity').value)/100 });
        canvas.renderAll();
    }

    // IMAGE EFFECTS & BG REMOVAL
    async function removeBgFromImage(fieldId) {
        const imgEl = globalCustomImages[fieldId]; if(!imgEl) { alert('Upload a photo first.'); return; }
        const btn = document.querySelector(`#card_${fieldId} .btn-layer[style*="background:#8b5cf6"]`); 
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Wait...'; btn.disabled = true;
        
        try {
            const blob = await imglyRemoveBackground(imgEl.src); 
            const url = URL.createObjectURL(blob); 
            const newImg = new Image();
            newImg.onload = function() { 
                globalCustomImages[fieldId] = newImg; 
                if(!window.originalCustomImages) window.originalCustomImages = {};
                window.originalCustomImages[fieldId] = newImg; 
                updateImageShape(fieldId); 
                btn.innerHTML = '✅ Removed!'; btn.disabled = false;
            };
            newImg.src = url; return;
        } catch (e) { console.warn("img.ly AI failed, fallback to API..."); }

        // FALLBACK: Remove.bg API
        try {
            let base64Data = imgEl.src;
            let reqBody = base64Data.startsWith('data:image') 
                ? JSON.stringify({ image_file_b64: base64Data.split(',')[1], size: "auto" })
                : JSON.stringify({ image_url: base64Data, size: "auto" });

            const response = await fetch('https://api.remove.bg/v1.0/removebg', {
                method: 'POST',
                headers: { 'X-Api-Key': 'pSqcQaSbGwN4an41dkZSyHAs', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: reqBody
            });

            if(!response.ok) throw new Error("API Limit Reached");
            
            const data = await response.json();
            const newImg = new Image();
            newImg.onload = function() { 
                globalCustomImages[fieldId] = newImg; 
                if(!window.originalCustomImages) window.originalCustomImages = {};
                window.originalCustomImages[fieldId] = newImg; 
                updateImageShape(fieldId); 
                btn.innerHTML = '✅ API Removed!'; btn.disabled = false;
            };
            newImg.src = 'data:image/png;base64,' + data.data.result_b64;
        } catch(err) {
            console.error(err);
            alert('❌ Both Edge AI and Cloud API failed or Limit Reached.');
            btn.innerHTML = originalText; btn.disabled = false;
        }
    }

    function applyFrameFilter(selectEl, fieldId) {
        if(!window.originalCustomImages) window.originalCustomImages = {};
        
        // Cache original image instance if not done yet
        let origImg = window.originalCustomImages[fieldId] || globalCustomImages[fieldId];
        if(!origImg) return;
        if(!window.originalCustomImages[fieldId]) window.originalCustomImages[fieldId] = origImg;
        
        const filterType = selectEl.value;
        if (filterType === 'none') {
            globalCustomImages[fieldId] = origImg;
            updateImageShape(fieldId);
            return;
        }
        
        let cv = document.createElement('canvas');
        cv.width = origImg.width; cv.height = origImg.height;
        let ctx = cv.getContext('2d');
        
        if (filterType === 'grayscale') ctx.filter = 'grayscale(100%)';
        else if (filterType === 'sepia') ctx.filter = 'sepia(100%)';
        else if (filterType === 'invert') ctx.filter = 'invert(100%)';
        else if (filterType === 'vintage') ctx.filter = 'sepia(50%) contrast(150%) saturate(150%) brightness(90%)';
        else if (filterType === 'blur') ctx.filter = 'blur(5px)';
        
        ctx.drawImage(origImg, 0, 0);
        
        let newImg = new Image();
        newImg.onload = function() {
            globalCustomImages[fieldId] = newImg;
            updateImageShape(fieldId);
        };
        newImg.src = cv.toDataURL('image/png');
    }

    // --- TABLE FIELDS ---
    function createNewTableField(skipCanvas=false, forcedId=null, rows=3, cols=2, data=null, designProps=null) {
        if (!skipCanvas && !forcedId) fieldCounter++; 
        const fieldId = forcedId || ('field_tbl_' + fieldCounter);
        const html = `<div class="field-card" id="card_${fieldId}" style="border-color:#f59e0b;">
            <div class="title-row" style="background:#fff7ed;">
                <span style="font-size:12px; font-weight:bold; color:#c2410c;">📊 Table Grid</span>
                <button class="btn-refresh" style="background:#ef4444; padding:2px 6px;" onclick="deleteField('${fieldId}')"><i class="fas fa-trash"></i></button>
            </div>
            <div class="tools-row" style="flex-wrap: wrap; gap: 8px; padding-bottom:5px;">
                <div style="display:flex; align-items:center; gap:3px;" title="Rows and Columns">
                    <span style="font-size:11px">⊞</span>
                    <input type="number" class="tbl-rows" value="${rows}" onchange="buildTableGridUI('${fieldId}')" style="width:35px; padding:2px;"> 
                    <span style="font-size:11px">x</span> 
                    <input type="number" class="tbl-cols" value="${cols}" onchange="buildTableGridUI('${fieldId}')" style="width:35px; padding:2px;">
                </div>
                
                <div style="display:flex; align-items:center; gap:3px;" title="Font Size">
                    <span style="font-size:11px;">A<sup>T</sup></span>
                    <input type="number" class="field-size" value="22" oninput="syncTableToCanvas('${fieldId}')" style="width:40px; padding:2px;">
                </div>
                
                <div style="display:flex; align-items:center; gap:3px;" title="Header Background & Text Color">
                    <span style="font-size:11px;">H-Bg/Tx</span>
                    <input type="color" class="tbl-header-bg" value="#f59e0b" oninput="syncTableToCanvas('${fieldId}')" style="width:25px;height:25px;padding:0;">
                    <input type="color" class="tbl-header-color" value="#ffffff" oninput="syncTableToCanvas('${fieldId}')" style="width:25px;height:25px;padding:0;">
                </div>
                
                <div style="display:flex; align-items:center; gap:3px;" title="Data Background & Text Color">
                    <span style="font-size:11px;">D-Bg/Tx</span>
                    <input type="color" class="tbl-bg" value="#ffffff" oninput="syncTableToCanvas('${fieldId}')" style="width:25px;height:25px;padding:0;">
                    <input type="color" class="tbl-color" value="#000000" oninput="syncTableToCanvas('${fieldId}')" style="width:25px;height:25px;padding:0;">
                </div>

                <div style="display:flex; align-items:center; gap:3px;" title="Border Width & Color">
                    <span style="font-size:11px;">Brd/Col</span>
                    <input type="number" class="tbl-border-w" value="1" oninput="syncTableToCanvas('${fieldId}')" style="width:35px; padding:2px;" min="0" max="10">
                    <input type="color" class="tbl-border-c" value="#9ca3af" oninput="syncTableToCanvas('${fieldId}')" style="width:25px;height:25px;padding:0;">
                </div>
                
                <label style="font-size:11px; color:orange; margin-left:auto; display:flex; align-items:center;"><input type="checkbox" class="field-hide-all" onchange="toggleVisibility('${fieldId}')" style="margin-right:2px;"> View</label>
            </div>
            <div class="tbl-grid-container" id="grid_${fieldId}"></div>
        </div>`;
        document.getElementById('dynamicFields').insertAdjacentHTML('beforeend', html);
        if (data) document.getElementById('card_' + fieldId).dataset.init = JSON.stringify(data);
        if (designProps) document.getElementById('card_' + fieldId).dataset.design = JSON.stringify(designProps);
        buildTableGridUI(fieldId, skipCanvas);
    }

    function buildTableGridUI(fieldId, skipCanvas=false) {
        const card = document.getElementById('card_' + fieldId);
        const container = document.getElementById('grid_' + fieldId);
        
        if (card.dataset.design) {
            let dp = JSON.parse(card.dataset.design);
            if (dp.tblFontSize) card.querySelector('.field-size').value = dp.tblFontSize;
            if (dp.tblHeaderBg) card.querySelector('.tbl-header-bg').value = dp.tblHeaderBg;
            if (dp.tblHeaderColor) card.querySelector('.tbl-header-color').value = dp.tblHeaderColor;
            if (dp.tblRowBg) card.querySelector('.tbl-bg').value = dp.tblRowBg;
            if (dp.tblRowColor) card.querySelector('.tbl-color').value = dp.tblRowColor;
            if (dp.tblBorderW !== undefined) card.querySelector('.tbl-border-w').value = dp.tblBorderW;
            if (dp.tblBorderColor) card.querySelector('.tbl-border-c').value = dp.tblBorderColor;
            delete card.dataset.design;
        }

        const rLen = parseInt(card.querySelector('.tbl-rows').value);
        const cLen = parseInt(card.querySelector('.tbl-cols').value);
        let currentData = [];
        if (card.dataset.init) { currentData = JSON.parse(card.dataset.init); delete card.dataset.init; }
        else { container.querySelectorAll('.tbl-cell-ui').forEach(input => { let p = input.id.split('_'); let r=p[p.length-2], c=p[p.length-1]; if(!currentData[r]) currentData[r]=[]; currentData[r][c]=input.value; }); }

        let html = '';
        for(let r=0; r<rLen; r++) {
            html += '<div class="tbl-row-ui">';
            for(let c=0; c<cLen; c++) {
                let val = (currentData[r] && typeof currentData[r][c] !== 'undefined') ? currentData[r][c] : (r===0?'Head':'Data');
                let safeVal = String(val).replace(/"/g, '&quot;');
                html += `<input type="text" class="tbl-cell-ui ${r===0?'header-cell':''}" id="cell_${fieldId}_${r}_${c}" value="${safeVal}" oninput="syncTableToCanvas('${fieldId}')" onkeyup="handleTyping(event, '${fieldId}', true)">`;
            }
            html += '</div>';
        }
        container.innerHTML = html;
        if (!skipCanvas) syncTableToCanvas(fieldId);
    }

    function syncTableToCanvas(fieldId) {
        const card = document.getElementById('card_' + fieldId); if(!card) return;
        const rows = parseInt(card.querySelector('.tbl-rows').value);
        const cols = parseInt(card.querySelector('.tbl-cols').value);
        const fontSize = parseInt(card.querySelector('.field-size').value);
        const headerBg = card.querySelector('.tbl-header-bg').value;
        const headerColor = card.querySelector('.tbl-header-color') ? card.querySelector('.tbl-header-color').value : '#ffffff';
        const rowBg = card.querySelector('.tbl-bg') ? card.querySelector('.tbl-bg').value : '#ffffff';
        const rowColor = card.querySelector('.tbl-color') ? card.querySelector('.tbl-color').value : '#000000';
        const borderW = card.querySelector('.tbl-border-w') ? parseInt(card.querySelector('.tbl-border-w').value) : 1;
        const borderColor = card.querySelector('.tbl-border-c') ? card.querySelector('.tbl-border-c').value : '#9ca3af';

        let tableGroup = [];
        let rawData = [];
        let padding = 15;

        // Pass 1: find max width per column
        let colWidths = [];
        for (let c = 0; c < cols; c++) {
            colWidths[c] = 50; // minimum width
            for (let r = 0; r < rows; r++) {
                let el = document.getElementById(`cell_${fieldId}_${r}_${c}`);
                let val = el ? el.value : '';
                let tempT = new fabric.Text(val, { fontSize: fontSize, fontFamily: 'Anek Gujarati' });
                if ((tempT.width + padding*2) > colWidths[c]) {
                    colWidths[c] = tempT.width + padding*2;
                }
            }
        }

        let curY = 0;
        // Pass 2: draw cells with uniform width and height
        for(let r=0; r<rows; r++) {
            let curX = 0; rawData[r] = [];
            let rowHeight = fontSize + padding*2;
            for (let c=0; c<cols; c++) {
                let el = document.getElementById(`cell_${fieldId}_${r}_${c}`);
                let val = el ? el.value : '';
                rawData[r][c] = val;
                
                let rect = new fabric.Rect({ 
                    left: curX, top: curY, 
                    width: colWidths[c], height: rowHeight, 
                    fill: (r===0?headerBg:rowBg), 
                    stroke: borderColor, strokeWidth: borderW 
                });
                let t = new fabric.Text(val, { 
                    fontSize: fontSize, fill: (r===0?headerColor:rowColor), 
                    fontFamily: 'Anek Gujarati', originX: 'center', originY: 'center', 
                    left: curX + (colWidths[c]/2), top: curY + (rowHeight/2) 
                });
                
                tableGroup.push(rect, t);
                curX += colWidths[c];
            }
            curY += rowHeight;
        }
        let grp = new fabric.Group(tableGroup, { 
            customId: fieldId, left: 100, top: 400, 
            tblRows: rows, tblCols: cols, tblData: rawData,
            tblFontSize: fontSize, tblHeaderBg: headerBg, tblHeaderColor: headerColor,
            tblRowBg: rowBg, tblRowColor: rowColor, tblBorderW: borderW, tblBorderColor: borderColor
        });
        let old = canvas.getObjects().find(o => o.customId === fieldId);
        if (old) { grp.set({ left: old.left, top: old.top }); canvas.remove(old); }
        canvas.add(grp); canvas.renderAll();
    }

    // --- SHARED UTILS ---
    function deleteField(id) { 
        if (!confirm("Really delete?")) return;
        canvas.getObjects().filter(o => o.customId && o.customId.startsWith(id)).forEach(o => canvas.remove(o));
        const card = document.getElementById('card_' + id); if (card) card.remove();
        canvas.renderAll();
    }
    function toggleVisibility(id) {
        const card = document.getElementById('card_' + id); const isHidden = card.querySelector('.field-hide-all').checked;
        canvas.getObjects().filter(o => o.customId && o.customId.startsWith(id)).forEach(o => o.set({ visible: !isHidden }));
        card.classList.toggle('hidden-field', isHidden); canvas.renderAll();
    }
    function moveLayer(id, dir) {
        canvas.getObjects().filter(o => o.customId && o.customId.startsWith(id)).forEach(o => { if(dir==='up') canvas.bringForward(o); else canvas.sendBackwards(o); });
        canvas.renderAll();
    }
    function forceSyncAll() { syncUIFromCurrentCanvas(); syncWatermark(); canvas.renderAll(); }

    function syncWatermark() {
        if (!canvas) return;
        canvas.getObjects().filter(o => o.isWatermark).forEach(o => canvas.remove(o));
        const text = document.getElementById('wmText').value;
        const color = document.getElementById('wmColor').value;
        const op = (parseInt(document.getElementById('wmOpacity').value)||25)/100;
        const size = parseInt(document.getElementById('wmSize').value)||60;
        if (text) {
            const wm = new fabric.Text(text, { left: 400, top: 500, originX: 'center', originY: 'center', fontSize: size*2, fill: color, opacity: op, angle: -45, selectable: false, isWatermark: true });
            canvas.add(wm); canvas.sendToBack(wm);
        }
        canvas.renderAll();
    }
    function removeWatermark() { document.getElementById('wmText').value=''; syncWatermark(); }

    // Removed duplicate definitions

    // 🚀 EXPORT & API DEDUCTION 🚀
    // ==========================================
    async function handleExport() {
        if (!canvas) return;
        
        // 1. Prepare Canvas
        canvas.discardActiveObject();
        sysResetZoom();
        
        // 2. Determine format and quality
        const format = document.getElementById('exportFormat').value;
        const multiplier = parseInt(document.getElementById('exportQuality').value) || 2;
        const finalName = `Project_${Date.now()}.${format}`;

        showLoading(true, "Processing Final Output...");
        
        try {
            let finalBlob = null;
            if (format === 'pdf') {
                const imgData = canvas.toDataURL({ format: 'png', multiplier: multiplier });
                const { jsPDF } = window.jspdf;
                let orientation = canvas.width > canvas.height ? 'landscape' : 'portrait';
                const doc = new jsPDF({ 
                    orientation: orientation, 
                    unit: 'px', 
                    format: [canvas.width * multiplier, canvas.height * multiplier] 
                });
                doc.addImage(imgData, 'PNG', 0, 0, canvas.width * multiplier, canvas.height * multiplier);
                finalBlob = doc.output('blob');
            } else {
                const dataUrl = canvas.toDataURL({ 
                    format: format === 'jpg' ? 'jpeg' : 'png', 
                    quality: 1.0, 
                    multiplier: multiplier 
                });
                // Convert dataUrl to Blob
                const res = await fetch(dataUrl);
                finalBlob = await res.blob();
            }

            showLoading(false);
            if (finalBlob) {
                await triggerDownloadTransaction(finalBlob, finalName);
            }
        } catch (e) {
            console.error("Export Error:", e);
            Swal.fire({ icon: 'error', title: 'Export Failed', text: 'Canvas Might contain external images blocking export (CORS).' });
            showLoading(false);
        }
    }

    async function triggerDownloadTransaction(finalBlob, finalName) {
        if (!finalBlob) { 
            Swal.fire({ icon: 'error', title: 'Error', text: 'Processing failed. Please try again.' });
            return; 
        }

        if (!isGuest && userRole !== 'admin' && userRole !== 'master_admin') {
            let actualCost = serviceRate;
            let willUsePoints = false;
            
            if (actualCost <= 0) {
                 willUsePoints = false;
            } 
            else if (userBalance >= actualCost) {
                const result = await Swal.fire({
                    title: 'Confirm Purchase',
                    text: `${currency}${actualCost} will be deducted from your wallet to download the final result.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Deduct & Download',
                    cancelButtonText: 'No, Cancel'
                });
                if (!result.isConfirmed) return;
            } 
            else if (pointsRate > 0 && userPoints >= pointsRate) {
                const result = await Swal.fire({
                    title: 'Use Points?',
                    text: `You don't have enough Wallet Balance, but you have ${userPoints} Points. ${pointsRate} Points will be deducted to download this result.`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Use Points',
                    cancelButtonText: 'Cancel'
                });
                if (!result.isConfirmed) return;
                willUsePoints = true;
            }
            else {
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Insufficient Funds', 
                    text: `You need ${currency}${actualCost} or ${pointsRate} Points to download this file.`
                });
                return;
            }

            await triggerWalletAPI(willUsePoints);
        } else if (isGuest) {
            const result = await Swal.fire({
                title: 'Confirm Guest Download',
                text: "Confirm using your single free daily guest pass to download this file?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Use Pass',
                cancelButtonText: 'Cancel'
            });
            if (!result.isConfirmed) return;
            await triggerWalletAPI(false);
        } else {
            // Admin
            await triggerWalletAPI(false);
        }

        executeDownload(finalBlob, finalName);
    }

    async function triggerWalletAPI(willUsePoints) {
        showLoading(true, "Verifying Wallet Transaction...");
        try {
            let formData = new FormData();
            formData.append('service_slug', 'poster_studio');
            formData.append('service_type', 'Poster Studio Pro');
            if (willUsePoints) formData.append('use_points', '1');

            let response = await fetch(APP_URL + 'deduct_poster_balance.php', { method: 'POST', body: formData });
            let text = await response.text();
            showLoading(false);
            
            try {
                let result = JSON.parse(text);
                if (!result.success) {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.message });
                    throw new Error("Payment failed");
                }
                if (isGuest || result.cost <= 0) {
                    Swal.fire({ icon: 'success', title: 'Success', text: result.message || "✅ Guest pass used!" });
                } else {
                    Swal.fire({ 
                        icon: 'success', 
                        title: 'Downloaded!', 
                        html: `Paid from: <b>${result.deducted_type === 'points' ? result.cost + ' Pts' : currency + result.cost}</b>` 
                    });
                }
                
                // Update UI balance if applicable
                if (result.remaining_balance !== undefined) {
                    const balanceEls = document.querySelectorAll('.header-wallet-amount');
                    balanceEls.forEach(el => el.innerText = currency + result.remaining_balance);
                }
            } catch(e) { 
                console.error("JSON Error:", text);
                Swal.fire({ icon: 'error', title: 'Parse Error', text: 'API Server parsing failed. Check internet.' }); 
                throw e; 
            }
        } catch(e) { 
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Network error processing wallet.' }); 
            showLoading(false); 
            throw e; 
        }
    }

    function executeDownload(blob, name) {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = name;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
    }

    function saveDraft(btn, isSaveAs=false) {
        let nameField = document.getElementById('draftNameInput');
        let name = nameField ? nameField.value.trim() : '';

        if (!name) {
            let d = new Date();
            let ds = d.getFullYear() + "-" + String(d.getMonth()+1).padStart(2, '0') + "-" + String(d.getDate()).padStart(2, '0');
            let ts = String(d.getHours()).padStart(2, '0') + "-" + String(d.getMinutes()).padStart(2, '0') + "-" + String(d.getSeconds()).padStart(2, '0');
            name = "Poster_" + ds + "_" + ts;
            if (nameField) nameField.value = name;
        }

        if (isSaveAs) {
            name = prompt("Save As new draft name:", name + "_Copy");
            if (!name) return;
            if (nameField) nameField.value = name;
        }

        const props = ['customId', 'customBgEnable', 'customBgColor', 'customBgShape', 'customPadding', 'customIsUppercase', 'tblRows', 'tblCols', 'tblData', 'tblFontSize', 'tblHeaderBg', 'tblHeaderColor', 'tblRowBg', 'tblRowColor', 'tblBorderW', 'tblBorderColor', 'shadow', 'stroke', 'strokeWidth', 'charSpacing', 'lineHeight', 'textAlign'];
        const json = JSON.stringify(canvas.toJSON(props));
        const fd = new FormData(); fd.append('service_slug', 'poster_studio'); fd.append('draft_name', name); fd.append('json', json);
        if (!isSaveAs && currentDraftId) fd.append('draft_id', currentDraftId);
        btn.disabled = true;
        fetch(APP_URL + 'save_digital_draft.php', { method:'POST', body:fd })
        .then(r => r.json()).then(d => { if(d.success) { currentDraftId=d.draft_id; alert("Saved!"); } btn.disabled=false; });
    }

    let historyTimeout = null;
    function startHistoryTracking() {
        canvas.on('object:modified', saveHistory);
        canvas.on('object:added', saveHistory);
        canvas.on('object:removed', saveHistory);
        saveHistory();
    }
    function saveHistory() { 
        if(isStackLoading) return; 
        
        // Debounce: Wait 300ms before saving heavy JSON
        if(historyTimeout) clearTimeout(historyTimeout);
        historyTimeout = setTimeout(() => {
            const props = ['customId', 'customBgEnable', 'customBgColor', 'customBgShape', 'customPadding', 'customIsUppercase', 'tblRows', 'tblCols', 'tblData', 'tblFontSize', 'tblHeaderBg', 'tblHeaderColor', 'tblRowBg', 'tblRowColor', 'tblBorderW', 'tblBorderColor', 'shadow', 'stroke', 'strokeWidth', 'charSpacing', 'lineHeight', 'textAlign'];
            const json = JSON.stringify(canvas.toJSON(props));
            if (undoStack.length > 0 && undoStack[undoStack.length - 1] === json) return;
            
            undoStack.push(json); 
            if(undoStack.length > 30) undoStack.shift();
            redoStack = []; // Clear redo on new action
        }, 300);
    }
    function undo() { if(undoStack.length<=1) return; isStackLoading=true; redoStack.push(undoStack.pop()); canvas.loadFromJSON(undoStack[undoStack.length-1], () => { canvas.renderAll(); isStackLoading=false; }); }
    function redo() { if(!redoStack.length) return; isStackLoading=true; undoStack.push(redoStack.pop()); canvas.loadFromJSON(undoStack[undoStack.length-1], () => { canvas.renderAll(); isStackLoading=false; }); }

    function showLoading(show, txt) { let l = document.getElementById('sys-loader'); if(!l){ l=document.createElement('div'); l.id='sys-loader'; l.style='position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:10000;display:flex;align-items:center;justify-content:center;color:#fff;'; document.body.appendChild(l); } l.style.display=show?'flex':'none'; l.innerText=txt; }
    
    // Zoom Utils
    let sysCurrentZoom = 1.0;
    function sysChangeZoom(a) { 
        sysCurrentZoom += a; 
        if(sysCurrentZoom < 0.2) sysCurrentZoom = 0.2;
        if(sysCurrentZoom > 5.0) sysCurrentZoom = 5.0;
        sysApplyZoom();
    }
    function sysResetZoom() { 
        sysCurrentZoom = 1.0; 
        sysApplyZoom();
    }
    function sysApplyZoom() {
        // Optimized: Use Fabric.js native zoom for perfect cursor precision
        canvas.setZoom(sysCurrentZoom);
        canvas.renderAll();
    }

    // Helpers
    async function handleTyping(event, fieldId, isTable = false) {
        const langEl = document.getElementById('typingLang');
        const lang = langEl ? langEl.value : 'en';
        
        const el = event.target;
        if (event.key === ' ' || event.key === 'Enter') {
            if (lang === 'en') {
                if(isTable) syncTableToCanvas(fieldId); else syncTextToCanvas(fieldId);
                return;
            }
            
            let cursor = el.selectionStart; 
            let textBefore = el.value.substring(0, cursor - 1); 
            let words = textBefore.split(/([ \n]+)/); 
            let lastWordIndex = words.length - 1;
            
            while (lastWordIndex >= 0 && !words[lastWordIndex].trim()) lastWordIndex--;
            
            if (lastWordIndex >= 0 && /^[a-zA-Z]+$/.test(words[lastWordIndex])) {
                try {
                    let res = await fetch('https://inputtools.google.com/request?text=' + words[lastWordIndex] + '&itc=' + (lang === 'gu' ? 'gu-t-i0-und' : 'hi-t-i0-und') + '&num=1');
                    let data = await res.json();
                    if (data[0] === 'SUCCESS') {
                        words[lastWordIndex] = data[1][0][1][0];
                        el.value = words.join('') + (event.key === ' ' ? ' ' : '\n') + el.value.substring(cursor);
                        el.selectionStart = el.selectionEnd = words.join('').length + 1;
                    }
                } catch(e) { console.warn("Transliteration Error:", e); }
            }
            if(isTable) syncTableToCanvas(fieldId); else syncTextToCanvas(fieldId);
        }
    }
    function applySolidBg() { 
        document.getElementById('bgSelect').value = 'none'; // Deselect preset
        if(canvas.backgroundImage) canvas.setBackgroundImage(null, canvas.renderAll.bind(canvas)); // clear image
        canvas.backgroundColor = document.getElementById('bgSolidColor').value; 
        canvas.renderAll(); 
    }
    
    function applyCustomGradient() { 
        document.getElementById('bgSelect').value = 'none';
        if(canvas.backgroundImage) canvas.setBackgroundImage(null, canvas.renderAll.bind(canvas));
        const g = new fabric.Gradient({ type:'linear', coords:{x1:0,y1:0,x2:0,y2:1000}, colorStops:[{offset:0,color:document.getElementById('gradCol1').value},{offset:1,color:document.getElementById('gradCol2').value}] }); 
        canvas.backgroundColor=g; 
        canvas.renderAll(); 
    }
    
    function applyPresetBackground() { 
        const v = document.getElementById('bgSelect').value; 
        if(canvas.backgroundImage) canvas.setBackgroundImage(null, canvas.renderAll.bind(canvas)); // Clear custom image
        if(v==='solid-white') canvas.backgroundColor='#ffffff'; 
        else if(v.startsWith('grad')) { 
            const g = new fabric.Gradient({ type:'linear', coords:{x1:0,y1:0,x2:0,y2:1000}, colorStops:[{offset:0,color:'#1e3a8a'},{offset:1,color:'#020617'}] }); 
            canvas.backgroundColor=g; 
        } 
        else if(v==='none') canvas.backgroundColor=''; 
        canvas.renderAll(); 
    }

    function applyBgImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(f) {
                fabric.Image.fromURL(f.target.result, function(img) {
                    canvas.backgroundColor = ''; // clear colors
                    document.getElementById('bgSelect').value = 'none';
                    canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), {
                        scaleX: canvas.width / img.width,
                        scaleY: canvas.height / img.height
                    });
                });
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
</body>
</html>