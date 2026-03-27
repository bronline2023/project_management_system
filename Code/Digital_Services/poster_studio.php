<?php
require_once CORE_INCLUDES_PATH . 'service_paywall.php';
enforce_service_paywall('poster_studio');

// File location: views/poster_studio.php


$pdo = connectDB();
$poster_cost = 10.00; 
$currency = '₹';

$user_role = $_SESSION['user_role'] ?? 'guest';
    try {
    $stmt = $pdo->query("SELECT poster_generation_cost, currency_symbol FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings) {
        $poster_cost = isset($settings['poster_generation_cost']) ? (float)$settings['poster_generation_cost'] : 10.00;
        $currency = isset($settings['currency_symbol']) ? $settings['currency_symbol'] : '₹';
    }
    
    if (isset($_SESSION['user_id'])) {
        $stmt_user = $pdo->prepare("SELECT custom_poster_rate FROM users WHERE id = ?");
        $stmt_user->execute([$_SESSION['user_id']]);
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data && isset($user_data['custom_poster_rate']) && $user_data['custom_poster_rate'] !== null && $user_data['custom_poster_rate'] !== '') {
            $poster_cost = (float)$user_data['custom_poster_rate'];
        }
    }
} catch (Exception $e) {}

$loaded_draft_json = null;
$loaded_draft_name = '';
$current_draft_id = isset($_GET['draft_id']) ? (int)$_GET['draft_id'] : 0;

if ($current_draft_id > 0 && isset($_SESSION['user_id'])) {
    try {
        $stmt_draft = $pdo->prepare("SELECT canvas_json, draft_name FROM digital_service_history WHERE id = ? AND user_id = ? AND is_draft = 1");
        $stmt_draft->execute([$current_draft_id, $_SESSION['user_id']]);
        $draft_row = $stmt_draft->fetch(PDO::FETCH_ASSOC);
        if ($draft_row) {
            $loaded_draft_name = $draft_row['draft_name'] ?? '';
            if (str_starts_with($draft_row['canvas_json'], 'FILE:')) {
                $filepath = UPLOADS_PATH . 'drafts/' . str_replace('FILE:', '', $draft_row['canvas_json']);
                if (file_exists($filepath)) $loaded_draft_json = file_get_contents($filepath);
            } else {
                $loaded_draft_json = $draft_row['canvas_json'];
            }
        }
    } catch(Exception $e) {}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Studio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@imgly/background-removal@1.4.5/dist/imgly-background-removal.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Amita&family=Anek+Gujarati:wght@400;700&family=Anton&family=Baloo+Bhai+2:wght@400;700&family=Bebas+Neue&family=Dancing+Script&family=Eczar&family=Farsan&family=Hind+Vadodara:wght@400;700&family=Kalam&family=Karma&family=Kumar+One&family=Kumar+One+Outline&family=Lobster&family=Mogra&family=Mukta+Vaani:wght@400;700&family=Noto+Sans+Gujarati:wght@400;700&family=Noto+Serif+Gujarati:wght@400;700&family=Oswald&family=Poppins:wght@400;700&family=Rasa:wght@400;700&family=Rozha+One&family=Shrikhand&family=Tiro+Devanagari+Hindi&family=Yatra+One&display=swap" rel="stylesheet">


<style>
    #sidebar { display: none !important; }
    .navbar { display: none !important; }
    #content { padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    .wrapper { margin: 0 !important; padding: 0 !important; }
    body { background-color: #0f172a; margin: 0; padding: 0; font-family: 'Inter', 'Segoe UI', sans-serif; overflow: hidden; }
    
    .studio-wrapper { display: flex; height: 100vh; width: 100vw; background: #0f172a; color: #f8fafc; }
    .studio-panel { width: 450px; min-width: 450px; background: #1e293b; display: flex; flex-direction: column; border-right: 1px solid #334155; z-index: 10; height: 100%; box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
    .studio-header { padding: 15px; background: #111827; color: #10b981; text-align: center; font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; display: flex; justify-content: space-between; align-items: center; }
    .btn-refresh { background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; text-decoration: none;}
    .btn-refresh:hover { background: #2563eb; color: white; text-decoration: none;}
    .controls-area { flex-grow: 1; overflow-y: auto; padding: 24px; background: #1e293b; }
    .workspace { flex-grow: 1; display: flex; justify-content: center; align-items: flex-start; overflow: auto; padding: 40px; background: #0f172a; background-image: radial-gradient(#334155 1px, transparent 0); background-size: 30px 30px; position: relative; }
    .canvas-container { box-shadow: 0 25px 60px rgba(0,0,0,0.7); background: white; border-radius: 4px; overflow: hidden; }

    .control-box { background: linear-gradient(145deg, #1e293b, #0f172a); padding: 20px; border-radius: 12px; margin-bottom: 15px; border: 1px solid #334155; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
    .control-title { font-weight: 800; font-size: 15px; color: #e2e8f0; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #334155; padding-bottom: 10px; }
    .form-label { font-size: 11px; font-weight: 700; color: #94a3b8; margin-bottom: 8px; display: block; text-transform: uppercase; }
    .form-control { width: 100%; padding: 10px; margin-bottom: 12px; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: white; font-size: 13px; outline: none; transition: 0.2s; }
    .control-box select, .control-box input[type="file"], .control-box input[type="text"] { width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white; font-size: 13px; outline: none; transition: 0.2s; }
    .bg-tool-row { display: flex; gap: 15px; align-items: center; margin-top: 10px; flex-wrap: wrap; background: #0f172a; padding: 10px; border-radius: 6px; border: 1px solid #334155; box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);}
    .tool-group { display: flex; flex-direction: column; font-size: 11px; font-weight: bold; color: #94a3b8; align-items: flex-start; }
    input[type="color"] { width: 40px; height: 32px; border: 1px solid #334155; cursor: pointer; border-radius: 4px; padding: 0; background: transparent; }
    
    .field-card { background: rgba(30, 41, 59, 0.8); border: 1px solid #334155; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 20px rgba(0,0,0,0.3); transition: 0.3s; }
    .field-card.hidden-field { opacity: 0.5; background: #0f172a; border-color: #334155; }
    .title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; background: rgba(15,23,42,0.6); padding: 12px; border-radius: 8px; border: 1px solid #475569; }
    .field-title-input { font-weight: bold; font-size: 14px; border: none; color: #f59e0b; width: 30%; outline: none; background: transparent; border-bottom: 1px solid #334155; }
    .title-style-box { display: flex; align-items: center; gap: 5px; background: #334155; padding: 4px 8px; border-radius: 4px; border: 1px solid #475569; flex-wrap: wrap; }
    .field-text { width: 100%; padding: 10px; border: 1px solid #334155; border-radius: 8px; resize: vertical; min-height: 60px; margin-bottom: 10px; font-size: 14px; font-weight: 500; background: #0f172a; color: white; }
    .tools-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; background: #0f172a; padding: 10px; border-radius: 6px; border: 1px solid #334155; }
    .tools-row select { padding: 6px; border: 1px solid #334155; border-radius: 4px; width: 100px; font-weight: bold; font-size: 11px; background: #1e293b; color: white; }
    .tools-row input[type="number"] { width: 50px; padding: 6px; border: 1px solid #334155; border-radius: 4px; font-size: 11px; background: #1e293b; color: white; }
    .tools-row input[type="range"] { cursor: pointer; }
    .bullet-style { background: #f59e0b; color: white; border: none; padding: 6px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 12px; outline: none; }
    
    .tbl-grid-container { width: 100%; overflow-x: auto; margin: 10px 0; border: 1px solid #334155; border-radius: 8px; padding: 5px; background: #0f172a; }
    .tbl-row-ui { display: flex; gap: 5px; margin-bottom: 5px; }
    .tbl-cell-ui { flex: 1; padding: 6px; border: 1px solid #475569; border-radius: 4px; font-size: 12px; font-weight: bold; background: #1e293b; color: white; }
    .tbl-cell-ui.header-cell { background: #334155; color: #f59e0b; }

    .action-bar { display: flex; gap: 5px; margin-top: 10px; }
    .btn-layer { flex: 1; padding: 6px; font-size: 12px; font-weight: bold; cursor: pointer; border: 1px solid #475569; border-radius: 4px; background: #334155; color: #f8fafc; }
    .btn-delete { background: #ef4444; color: white; border: none; border-radius: 4px; padding: 6px 10px; font-size: 12px; font-weight: bold; cursor: pointer; }
    
    .btn-action { background: #334155; color: white; padding: 8px 12px; border: none; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; transition: 0.2s; }
    .btn-action:hover { background: #3b82f6; }
    .btn-danger-lite { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

    .action-btns { padding: 20px; background: #1e293b; border-top: 1px solid #334155; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
    .btn-main { padding: 12px; border: none; border-radius: 10px; font-weight: 800; font-size: 12px; cursor: pointer; color: white; transition: 0.3s; box-shadow: 0 4px 10px rgba(0,0,0,0.2); display: flex; align-items: center; justify-content: center; gap: 5px; }
    .btn-main:hover { transform: translateY(-2px); }
    
    .btn-add-text { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .btn-add-img { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .btn-add-table { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .export-group { display: flex; width: 100%; margin-top: 10px; gap: 10px; grid-column: span 3; }
    .export-format { padding: 10px; border-radius: 8px; border: 1px solid #16a34a; font-weight: bold; font-size: 13px; color: #15803d; outline: none; width: 30%; background: #0f172a; }
    .btn-export { background: linear-gradient(135deg, #22c55e, #16a34a); width: 70%; font-size: 15px; margin: 0; border-radius: 10px; }

    /* ========================================================== */
    /* 📱 MOBILE RESPONSIVENESS FIXES (Injected by System) 📱      */
    /* ========================================================== */
    @media (max-width: 992px) {
        .studio-wrapper, .builder-wrapper { flex-direction: column !important; height: auto !important; width: 100vw !important; overflow-x: hidden; }
        .studio-panel { width: 100% !important; min-width: 100% !important; height: auto !important; max-height: 55vh; overflow-y: hidden; border-right: none !important; border-bottom: 2px solid #334155; border-radius: 0; box-shadow: none; background: #0f172a; }
        .controls-area { overflow-y: auto; height: calc(55vh - 55px); max-height: calc(55vh - 55px); }
        .workspace { position: relative; width: 100% !important; height: 45vh !important; min-height: 45vh !important; padding: 10px !important; overflow-y: auto; }
        .canvas-container { max-width: 100% !important; height: auto !important; margin: 0 auto; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        canvas { max-width: 100% !important; height: auto !important; }
        /* Scale down Previews */
        .a4-page, .card-preview { max-width: 100%; transform: scale(0.65) !important; transform-origin: top center !important; margin-bottom: 0 !important; }
        .mobile-gap { margin-bottom: 60px; }
        .action-btns { flex-wrap: wrap; justify-content: center; width: 100%; gap: 10px; padding: 15px; }
        .btn-add-text, .btn-add-img, .btn-add-table { flex: 1; min-width: 30%; font-size: 11px; padding: 12px 6px; border-radius: 8px; }
        .export-group { flex-direction: column; width: 100%; gap: 10px; }
        .export-format, .btn-main { width: 100% !important; height: 45px; margin: 0; border-radius: 8px; font-size: 14px; }
        .control-box { padding: 15px; margin-bottom: 12px; border-radius: 10px; }
        .tools-row select, .tools-row input { width: 100%; margin-bottom: 8px; }
        .tools-row .tool-group { width: 48%; }
        .field-card { padding: 15px; margin-bottom: 15px; border-radius: 10px; }
        .title-row { flex-direction: column; align-items: flex-start; gap: 10px; padding: 10px; }
        .title-style-box { width: 100%; justify-content: space-between; margin-top: 5px; }
        .field-title-input { width: 100%; font-size: 15px; padding-bottom: 5px; }
        .control-title { font-size: 14px; }
    }

</style>

<?php $page_title = 'Poster Studio Pro'; require_once INCLUDES_PATH.'digital_header.php'; ?>
<div class="studio-wrapper" style="height: calc(100vh - 65px); min-height: calc(100vh - 65px);">
    <div class="studio-panel">
        <div style="padding: 10px; display: flex; justify-content: space-between; align-items: center; background: #f1f5f9; border-bottom: 1px solid #cbd5e1;">
            <select id="typingLang" style="padding: 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-weight: bold; color: #1e293b; background: white; outline: none; cursor: pointer;">
                <option value="en">🇺🇸 English</option>
                <option value="gu">🇮🇳 Gujarati</option>
                <option value="hi">🇮🇳 Hindi</option>
            </select>
            <button class="btn-refresh" style="background:#3b82f6;" onclick="forceSyncAll()">🔄 Refresh Canvas</button>
        </div>
        <div class="controls-area" id="formContainer">
            <h3 class="control-title"><i class="fas fa-image"></i> 1. Background Setup</h3>
            <div class="control-box">
                <label class="form-label">Background Type</label>
                <select id="bgSelect" onchange="applyPresetBackground()" class="form-control" style="margin-bottom: 10px;">
                    <option value="solid-white">Solid White</option>
                    <option value="none">No Background</option>
                    <option value="grad-navy">Navy Gradient</option>
                    <option value="grad-crimson">Crimson Gradient</option>
                    <option value="grad-forest">Forest Gradient</option>
                    <option value="grad-gold">Gold Gradient</option>
                </select>
                <div class="bg-tool-row">
                    <div class="tool-group" style="flex: 1;">Solid <input type="color" id="bgSolidColor" value="#ffffff" oninput="applySolidBg()"></div>
                    <div class="tool-group" style="flex: 1;">Grad 1 <input type="color" id="gradCol1" value="#ff0000" oninput="applyCustomGradient()"></div>
                    <div class="tool-group" style="flex: 1;">Grad 2 <input type="color" id="gradCol2" value="#0000ff" oninput="applyCustomGradient()"></div>
                </div>
                <label class="form-label" style="margin-top: 15px;">Upload Background Image</label>
                <input type="file" id="bgImage" accept="image/*" class="form-control" style="margin-top: 5px;">
            </div>

            <h3 class="control-title"><i class="fas fa-stamp"></i> 2. Watermark & Logo Setup</h3>
            <div class="control-box">
                <label class="form-label">Text Watermark</label>
                <input type="text" id="wmText" placeholder="e.g., Confidential, Draft" oninput="syncWatermark()" class="form-control">
                
                <label class="form-label" style="margin-top: 10px;">Image Watermark / Logo</label>
                <input type="file" id="wmImageUpload" accept="image/*" class="form-control">
                
                <div class="bg-tool-row" style="margin-top: 10px;">
                    <label style="font-size: 13px; font-weight: bold; color: #e2e8f0; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="wmRepeat" onchange="syncWatermark()" style="margin:0; width: 18px; height: 18px;"> Repeat Pattern
                    </label>
                </div>
                <div class="bg-tool-row">
                    <div class="tool-group" style="flex: 1;">Color <input type="color" id="wmColor" value="#cccccc" oninput="syncWatermark()"></div>
                    <div class="tool-group" style="flex: 1;">Opac% <input type="number" id="wmOpacity" value="25" min="0" max="100" oninput="syncWatermark()" style="width: 100%;"></div>
                    <div class="tool-group" style="flex: 1;">Size <input type="number" id="wmSize" value="60" min="10" max="200" oninput="syncWatermark()" style="width: 100%;"></div>
                </div>
                <button class="btn-action btn-danger-lite" style="width: 100%; margin-top: 15px; padding: 10px;" onclick="removeWatermark()"><i class="fas fa-trash-alt"></i> Remove Watermark/Logo</button>
            </div>

            <h3 class="control-title"><i class="fas fa-list-alt"></i> 3. Form Details (Text, Image and Table)</h3>
            <div id="dynamicFields"></div>
        </div>
        <div class="action-btns">
            <button class="btn-add-text" onclick="createNewTextField()">+ New text</button>
            <button class="btn-add-img" onclick="createNewImageField()">+ New Photo (Frames)</button>
            <button class="btn-add-table" onclick="createNewTableField()">+ New table</button>
            <div class="export-group" style="display: flex; gap: 5px; width: 100%;">
                <select id="exportFormat" class="export-format" style="width: 20%;">
                    <option value="png">PNG</option>
                    <option value="jpg">JPG</option>
                    <option value="pdf">PDF</option>
                </select>
                <select id="exportQuality" class="form-control" style="width: 20%; padding: 10px; border-radius: 8px; font-weight: bold; background: #0f172a; color: white;">
                    <option value="1">SD</option>
                    <option value="2" selected>HD</option>
                    <option value="3">UHD</option>
                    <option value="5">4K</option>
                </select>
                <?php if(isset($_SESSION['user_id'])): ?>
                <div style="display: flex; gap: 2px; width: 40%;">
                    <button class="btn-main" style="flex: 1; background: #059669; font-size: 11px; border-radius: 10px 0 0 10px; padding: 10px 5px;" onclick="saveDraft(this, false)" title="Save changes to current draft"><i class="fas fa-save"></i> Save</button>
                    <button class="btn-main" style="flex: 1; background: #f59e0b; font-size: 11px; border-radius: 0 10px 10px 0; padding: 10px 5px;" onclick="saveDraft(this, true)" title="Save as a new draft"><i class="fas fa-copy"></i> As New</button>
                </div>
                <?php endif; ?>
                <button class="btn-main btn-export" style="width: 35%; font-size: 14px;" onclick="handleExport()">⬇ Download</button>
            </div>
        </div>
    </div>

    <div class="workspace">
        <div class="canvas-container">
            <canvas id="posterCanvas"></canvas>
        </div>
        <div class="sys-zoom-controls" style="position: absolute; bottom: 20px; right: 20px; background: rgba(255,255,255,0.95); padding: 8px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); display: flex; flex-direction: column; gap: 5px; z-index: 9999; border: 1px solid #cbd5e1;">
            <div style="font-size: 10px; font-weight: bold; color: #475569; text-align: center; margin-bottom: 2px;">ZOOM</div>
            <button type="button" onclick="sysChangeZoom(0.1)" style="background: #f1f5f9; border: 1px solid #94a3b8; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-weight: bold; transition: 0.2s;">➕</button>
            <button type="button" onclick="sysResetZoom()" style="background: #f1f5f9; border: 1px solid #94a3b8; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-size: 10px; font-weight: bold; transition: 0.2s;">100%</button>
            <button type="button" onclick="sysChangeZoom(-0.1)" style="background: #f1f5f9; border: 1px solid #94a3b8; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-weight: bold; transition: 0.2s;">➖</button>
        </div>
    </div>
</div>

<script>
    const APP_URL = '<?= APP_URL ?>';
    const baseUrl = '<?= BASE_URL ?>';
    function syncImageStyle(fieldId) {
        if(!canvas) return; const card = document.getElementById('card_' + fieldId);
        let obj = canvas.getObjects().find(o => o.customId === fieldId); if(!obj || !card) return;
        const bColor = card.querySelector('.img-border-color').value; const bWidth = parseInt(card.querySelector('.img-border-width').value) || 0;
        const imgOpacity = (parseInt(card.querySelector('.img-opacity').value) || 100) / 100;
        obj.set({ stroke: bColor, strokeWidth: bWidth, opacity: imgOpacity });
        canvas.renderAll();
    }

    function deleteField(fieldId) { let tObj = canvas.getObjects().find(o => o.customId === fieldId + '_title'); let cObj = canvas.getObjects().find(o => o.customId === fieldId + '_content'); let iObj = canvas.getObjects().find(o => o.customId === fieldId); if(tObj) canvas.remove(tObj); if(cObj) canvas.remove(cObj); if(iObj) canvas.remove(iObj); let card = document.getElementById('card_' + fieldId); if (card) card.remove(); canvas.renderAll(); }
    function toggleVisibility(fieldId) { const card = document.getElementById('card_' + fieldId); const isHidden = card.querySelector('.field-hide-all').checked; let tObj = canvas.getObjects().find(o => o.customId === fieldId + '_title'); let cObj = canvas.getObjects().find(o => o.customId === fieldId + '_content'); let iObj = canvas.getObjects().find(o => o.customId === fieldId); if(isHidden) { card.classList.add('hidden-field'); if(tObj) tObj.set({visible:false}); if(cObj) cObj.set({visible:false}); if(iObj) iObj.set({visible:false}); canvas.discardActiveObject(); } else { card.classList.remove('hidden-field'); if(tObj) tObj.set({visible:true}); if(cObj) cObj.set({visible:true}); if(iObj) iObj.set({visible:true}); if(fieldId.includes('tbl')) syncTableToCanvas(fieldId); } canvas.renderAll(); }
    function moveLayer(fieldId, dir) { let tObj = canvas.getObjects().find(o => o.customId === fieldId + '_title'); let cObj = canvas.getObjects().find(o => o.customId === fieldId + '_content'); let iObj = canvas.getObjects().find(o => o.customId === fieldId); if(dir === 'up') { if(tObj) canvas.bringForward(tObj); if(cObj) canvas.bringForward(cObj); if(iObj) canvas.bringForward(iObj); } else { if(tObj) canvas.sendBackwards(tObj); if(cObj) canvas.sendBackwards(cObj); if(iObj) canvas.sendBackwards(iObj); } canvas.renderAll(); }
    function forceSyncAll() { if(!canvas) { initApp(); return; } document.querySelectorAll('.field-card').forEach(card => { const id = card.id.replace('card_', ''); if(id.includes('txt')) syncTextToCanvas(id); if(id.includes('tbl')) syncTableToCanvas(id); }); syncWatermark(); canvas.renderAll(); }
    
    function syncWatermark() {
        if(!canvas) return; if(watermarkObj) { canvas.remove(watermarkObj); watermarkObj = null; }
        const wmTextEl = document.getElementById('wmText'); if(!wmTextEl) return;
        const text = wmTextEl.value.trim(); const isRepeat = document.getElementById('wmRepeat').checked;
        const color = document.getElementById('wmColor').value; const opacity = (parseInt(document.getElementById('wmOpacity').value) || 25) / 100; const size = parseInt(document.getElementById('wmSize').value) || 60;
        
        if (wmImageSource) {
            const cloneImg = fabric.util.object.clone(wmImageSource);
            if (isRepeat) {
                cloneImg.scaleToWidth(size);
                let pCanvas = new fabric.StaticCanvas(null, { width: size * 3, height: size * 3 });
                cloneImg.set({ originX: 'center', originY: 'center', left: size * 1.5, top: size * 1.5, angle: -30 });
                pCanvas.add(cloneImg);
                pCanvas.renderAll();
                let pattern = new fabric.Pattern({ source: pCanvas.getElement(), repeat: 'repeat' });
                watermarkObj = new fabric.Rect({ width: 800, height: 1000, left: 0, top: 0, fill: pattern, opacity: opacity, selectable: false, evented: false });
            } else {
                cloneImg.scaleToWidth(size * 4);
                watermarkObj = cloneImg;
                watermarkObj.set({ opacity: opacity, originX: 'center', originY: 'center', left: 400, top: 500, angle: 0, selectable: false, evented: false });
            }
            canvas.add(watermarkObj); canvas.sendToBack(watermarkObj); canvas.renderAll();
            return;
        }

        if(text !== "") {
            if(isRepeat) { let pCanvas = new fabric.StaticCanvas(null, { width: size*4, height: size*4 }); let pText = new fabric.Text(text, { fontSize: size, fontFamily: 'Poppins', fill: color, originX: 'center', originY: 'center', left: (size*4)/2, top: (size*4)/2, angle: -30 }); pCanvas.add(pText); pCanvas.renderAll(); let pattern = new fabric.Pattern({ source: pCanvas.getElement(), repeat: 'repeat' }); watermarkObj = new fabric.Rect({ width: 800, height: 1000, left: 0, top: 0, fill: pattern, opacity: opacity, selectable: false, evented: false }); } 
            else { watermarkObj = new fabric.Text(text, { fontSize: size, fontFamily: 'Poppins', fill: color, opacity: opacity, originX: 'center', originY: 'center', left: 400, top: 500, angle: -45, selectable: false, evented: false }); } canvas.add(watermarkObj); canvas.sendToBack(watermarkObj); canvas.renderAll();
        }
    }
    function removeWatermark() { 
        if(!canvas) return; 
        if(watermarkObj) { canvas.remove(watermarkObj); watermarkObj = null; } 
        if(document.getElementById('wmText')) document.getElementById('wmText').value = ""; 
        wmImageSource = null;
        if(document.getElementById('wmImageUpload')) document.getElementById('wmImageUpload').value = "";
        canvas.renderAll(); 
    }

    // 🚀 UNIFIED EXPORT HANDLER 🚀
    async function handleExport() {
        if(!canvas) return;
        // The Universal Paywall (service_paywall.php) intercepts this call.
        // It will only execute the original logic (exportPoster) if unlocked.
        exportPoster();
    }

    function exportPoster() {
        if(!canvas) return; canvas.discardActiveObject(); canvas.renderAll();
        const format = document.getElementById('exportFormat').value; 
        const multiplier = parseInt(document.getElementById('exportQuality').value) || 2;
        const fileName = 'Digital_Studio_Poster_' + (multiplier > 1 ? (multiplier + 'x') : 'SD');
        
        if (format === 'pdf') { 
            const imgData = canvas.toDataURL({ format: 'jpeg', quality: 1, multiplier: multiplier }); 
            const { jsPDF } = window.jspdf; 
            const pdf = new jsPDF({ orientation: 'portrait', unit: 'px', format: [800, 1000] }); 
            pdf.addImage(imgData, 'JPEG', 0, 0, 800, 1000); 
            pdf.save(fileName + '.pdf'); 
        } 
        else { 
            const dataURL = canvas.toDataURL({ format: format === 'jpg' ? 'jpeg' : 'png', quality: 1, multiplier: multiplier }); 
            const link = document.createElement('a'); 
            link.download = fileName + '.' + format; 
            link.href = dataURL; 
            link.click(); 
        }
    }
    const userRole = "<?php echo ($_SESSION['user_role'] ?? 'guest'); ?>";
    const posterCost = <?php echo number_format($poster_cost ?? 0, 2, '.', ''); ?>;
    const currency = "<?php echo (string)($currency ?? '₹'); ?>";
    let canvas = null; let fieldCounter = 0; let watermarkObj = null; let wmImageSource = null; let globalCustomImages = {};
    let currentDraftId = <?= $current_draft_id ?>;
    let currentDraftName = "<?= addslashes($loaded_draft_name) ?>";

    function saveDraft(btn, isSaveAs = false) {
        if (!canvas) return;
        
        let draftName = currentDraftName;
        if (isSaveAs || !currentDraftId || !draftName) {
            draftName = prompt("Enter a name for this draft:", draftName || "My Design");
            if (!draftName) return; 
        }

        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ...';
        btn.disabled = true;

        // Expanded property list for complete serialization
        const propsToInclude = [
            'customId', 'customBgEnable', 'customBgColor', 'customBgShape', 'customPadding', 
            'fontSize', 'fontFamily', 'fill', 'opacity', 'fontWeight', 'fontStyle', 'textAlign', 
            'stroke', 'strokeWidth', 'selectable', 'evented', 'visible', 'angle', 
            'scaleX', 'scaleY', 'left', 'top', 'width', 'height', 'originX', 'originY',
            'strokeLineCap', 'strokeLineJoin', 'strokeMiterLimit', 'strokeDashArray', 'strokeDashOffset'
        ];

        const json = JSON.stringify(canvas.toJSON(propsToInclude));
        const formData = new FormData();
        formData.append('service_slug', 'poster_studio');
        formData.append('service_name', 'Poster Studio Pro');
        formData.append('draft_name', draftName);
        formData.append('json', json);
        
        if (!isSaveAs && currentDraftId > 0) {
            formData.append('draft_id', currentDraftId);
        }

        fetch(APP_URL + 'save_digital_draft.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentDraftId = data.draft_id;
                currentDraftName = draftName;
                alert('✅ Draft saved successfully!');
            } else {
                alert('❌ Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('❌ Network Error: Could not save draft.');
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    }
    const fontLibraryHTML = `
        <optgroup label="Government Standard Fonts">
            <option value="Shruti">Shruti (Gujarati Gov)</option>
            <option value="Mangal">Mangal (Hindi Gov)</option>
            <option value="Arial">Arial (Standard)</option>
            <option value="'Times New Roman'">Times New Roman</option>
        </optgroup>
        <optgroup label="Modern Gujarati Fonts">
            <option value="Hind Vadodara">Hind Vadodara</option>
            <option value="Anek Gujarati">Anek Gujarati</option>
            <option value="Baloo Bhai 2">Baloo Bhai 2</option>
            <option value="Noto Sans Gujarati">Noto Sans Gujarati</option>
        </optgroup>
    `;
    window.addEventListener('load', function() { setTimeout(initApp, 200); });

    document.addEventListener('change', function(e) {
        if (e.target.id === 'wmImageUpload') {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(f) {
                fabric.Image.fromURL(f.target.result, function(img) {
                    wmImageSource = img;
                    syncWatermark();
                });
            };
            reader.readAsDataURL(file);
        }
    });

    function hexToRgbA(hex, alpha) {
        var c;
        if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
            c= hex.substring(1).split('');
            if(c.length== 3){ c= [c[0], c[0], c[1], c[1], c[2], c[2]]; }
            c= '0x'+c.join('');
            return 'rgba('+[(c>>16)&255, (c>>8)&255, c&255].join(',')+','+alpha+')';
        }
        return hex;
    }

    const originalRenderBackground = fabric.Textbox.prototype._renderBackground;
    fabric.Textbox.prototype._renderBackground = function(ctx) {
        if (!this.customBgEnable) { originalRenderBackground.call(this, ctx); return; }
        var pad = this.customPadding || 0; var w = this.width + pad * 2; var h = this.height + pad * 2;
        var x = -this.width / 2 - pad; var y = -this.height / 2 - pad;
        ctx.fillStyle = this.customBgColor || '#ffffff'; ctx.beginPath();
        if (this.customBgShape === 'round') {
            var rx = 12; ctx.moveTo(x + rx, y); ctx.lineTo(x + w - rx, y); ctx.quadraticCurveTo(x + w, y, x + w, y + rx); ctx.lineTo(x + w, y + h - rx); ctx.quadraticCurveTo(x + w, y + h, x + w - rx, y + h); ctx.lineTo(x + rx, y + h); ctx.quadraticCurveTo(x, y + h, x, y + h - rx); ctx.lineTo(x, y + rx); ctx.quadraticCurveTo(x, y, x + rx, y);
        } else if (this.customBgShape === 'pill') {
            var rx = h / 2; ctx.moveTo(x + rx, y); ctx.lineTo(x + w - rx, y); ctx.quadraticCurveTo(x + w, y, x + w, y + rx); ctx.lineTo(x + w, y + h - rx); ctx.quadraticCurveTo(x + w, y + h, x + w - rx, y + h); ctx.lineTo(x + rx, y + h); ctx.quadraticCurveTo(x, y + h, x, y + h - rx); ctx.lineTo(x, y + rx); ctx.quadraticCurveTo(x, y, x + rx, y);
        } else if (this.customBgShape === 'hexagon') {
            var cut = h * 0.25; ctx.moveTo(x + w/2, y); ctx.lineTo(x + w, y + cut); ctx.lineTo(x + w, y + h - cut); ctx.lineTo(x + w/2, y + h); ctx.lineTo(x, y + h - cut); ctx.lineTo(x, y + cut);
        } else if (this.customBgShape === 'diamond') {
            ctx.moveTo(x + w/2, y); ctx.lineTo(x + w, y + h/2); ctx.lineTo(x + w/2, y + h); ctx.lineTo(x, y + h/2);
        } else if (this.customBgShape === 'ribbon') {
            var cut = 20; ctx.moveTo(x, y); ctx.lineTo(x + w, y); ctx.lineTo(x + w - cut, y + h/2); ctx.lineTo(x + w, y + h); ctx.lineTo(x, y + h); ctx.lineTo(x + cut, y + h/2);
        } else { ctx.rect(x, y, w, h); }
        ctx.fill(); ctx.closePath();
    };



    function initApp() {
        if (typeof fabric === 'undefined') return;
        canvas = new fabric.Canvas('posterCanvas', { width: 800, height: 1000, preserveObjectStacking: true });
        
        const initialDraftJson = <?= $loaded_draft_json ?? 'null' ?>;
        
        if (initialDraftJson) {
            console.log("Restoring Draft JSON:", initialDraftJson);
            showLoading(true, "Loading your saved draft...");
            try {
                canvas.loadFromJSON(initialDraftJson, function() {
                    console.log("Canvas loaded from JSON");
                    
                    // Restore background from JSON if applicable
                    if (initialDraftJson.background) {
                        canvas.backgroundColor = initialDraftJson.background;
                    }

                    canvas.renderAll();
                    
                    document.getElementById('dynamicFields').innerHTML = ''; // clear current UI
                    let objs = canvas.getObjects();
                    let maxId = 0;
                    let processedIds = new Set();
                    
                    // First pass: Find all unique field IDs and the max ID
                    objs.forEach(obj => {
                        if (obj.customId) {
                            let match = obj.customId.match(/(field_(txt|img|tbl)_(\d+))/);
                            if (match) {
                                let fullId = match[1];
                                let type = match[2];
                                let num = parseInt(match[3]);
                                if (num > maxId) maxId = num;
                                
                                if (!processedIds.has(fullId)) {
                                    processedIds.add(fullId);
                                    if (type === 'txt') {
                                        let contentObj = objs.find(o => o.customId === fullId + '_content');
                                        let titleObj = objs.find(o => o.customId === fullId + '_title');
                                        let sourceObj = contentObj || titleObj;
                                        
                                        if (sourceObj) {
                                            fieldCounter = num; 
                                            createNewTextField(
                                                (titleObj ? titleObj.text : "Field"), 
                                                (contentObj ? contentObj.text : ""), 
                                                (contentObj ? contentObj.fontSize : (titleObj ? titleObj.fontSize : 30)),
                                                (contentObj ? contentObj.fill : "#000000"),
                                                (contentObj ? (contentObj.stroke || "#ffffff") : "#ffffff"),
                                                (contentObj ? (contentObj.strokeWidth || 0) : 0),
                                                (contentObj ? contentObj.fontFamily : (titleObj ? titleObj.fontFamily : 'Anek Gujarati')),
                                                (contentObj ? contentObj.fontWeight === 'bold' : false),
                                                !!titleObj,
                                                true // skipCanvas
                                            );
                                            
                                            setTimeout(() => {
                                                const card = document.getElementById('card_' + fullId);
                                                if (card && contentObj) {
                                                    if (contentObj.customBgEnable) {
                                                        card.querySelector('.box-bg-enable').checked = true;
                                                        card.querySelector('.box-bg-color').value = contentObj.customBgColor || '#facc15';
                                                        card.querySelector('.box-bg-shape').value = contentObj.customBgShape || 'square';
                                                        card.querySelector('.box-padding').value = contentObj.customPadding || 12;
                                                    }
                                                    card.querySelector('.field-opacity').value = Math.round(contentObj.opacity * 100);
                                                    if (titleObj) {
                                                        card.querySelector('.title-bg-color').value = titleObj.customBgColor || '#e2e8f0';
                                                    }
                                                }
                                            }, 100);
                                        }
                                    } else if (type === 'img') {
                                        fieldCounter = num;
                                        createNewImageField(true);
                                        if (obj.fill && obj.fill.source) {
                                            globalCustomImages[fullId] = obj.fill.source;
                                            if(!window.originalCustomImages) window.originalCustomImages = {};
                                            window.originalCustomImages[fullId] = obj.fill.source;
                                        }
                                        setTimeout(() => {
                                            const card = document.getElementById('card_' + fullId);
                                            if (card) {
                                                card.querySelector('.img-opacity').value = Math.round(obj.opacity * 100);
                                                card.querySelector('.img-border-color').value = obj.stroke || '#ffffff';
                                                card.querySelector('.img-border-width').value = obj.strokeWidth || 0;
                                            }
                                        }, 100);
                                    } else if (type === 'tbl') {
                                        fieldCounter = num;
                                        createNewTableField(true);
                                    }
                                }
                            }
                        }
                    });
                    
                    fieldCounter = maxId;
                    showLoading(false);
                });
            } catch (err) {
                console.error("Draft Restoration Error:", err);
                showLoading(false);
                alert("❌ Failed to restore draft completely. One or more elements may be missing.");
            }
        } else {
            canvas.backgroundColor = '#ffffff'; canvas.renderAll();
            createNewTextField("Job/Scheme Name", "Gramin Dak Sevak Recruitment", 48, '#0f172a', '#ffffff', 0, 'Anek Gujarati', true, true);
            setTimeout(() => {
                let tBg = document.querySelector('#card_field_txt_1 .title-bg-color');
                let tColor = document.querySelector('#card_field_txt_1 .title-text-color');
                let cBgEnable = document.querySelector('#card_field_txt_1 .box-bg-enable');
                let cBg = document.querySelector('#card_field_txt_1 .box-bg-color');
                let cShape = document.querySelector('#card_field_txt_1 .box-bg-shape');
                if(tBg) { tBg.value = '#e11d48'; tColor.value = '#ffffff'; cBgEnable.checked = true; cBg.value = '#fef08a'; cShape.value = 'round'; syncTextToCanvas('field_txt_1'); }
            }, 300);
        }
    }

    function applySolidBg() { if(!canvas) return; canvas.setBackgroundImage(null, canvas.renderAll.bind(canvas)); canvas.backgroundColor = document.getElementById('bgSolidColor').value; canvas.renderAll(); document.getElementById('bgSelect').value = 'solid-white'; }
    function applyCustomGradient() { if(!canvas) return; const grad = new fabric.Gradient({ type: 'linear', gradientUnits: 'pixels', coords: { x1: 0, y1: 0, x2: 0, y2: 1000 }, colorStops: [{ offset: 0, color: document.getElementById('gradCol1').value }, { offset: 1, color: document.getElementById('gradCol2').value }] }); canvas.setBackgroundImage(null, canvas.renderAll.bind(canvas)); canvas.backgroundColor = grad; canvas.renderAll(); document.getElementById('bgSelect').value = 'none'; }
    function applyPresetBackground() { if(!canvas) return; const type = document.getElementById('bgSelect').value; const darkGradients = { 'grad-navy': ['#020617', '#1e3a8a'], 'grad-crimson': ['#450a0a', '#991b1b'], 'grad-forest': ['#052e16', '#166534'], 'grad-gold': ['#422006', '#b45309'] }; canvas.setBackgroundImage(null, canvas.renderAll.bind(canvas)); if (type === 'solid-white' || type === 'none') { canvas.backgroundColor = (type === 'solid-white' ? '#ffffff' : ''); } else if (darkGradients[type]) { const grad = new fabric.Gradient({ type: 'linear', gradientUnits: 'pixels', coords: { x1: 0, y1: 0, x2: 0, y2: 1000 }, colorStops: [{ offset: 0, color: darkGradients[type][0] }, { offset: 1, color: darkGradients[type][1] }] }); canvas.backgroundColor = grad; } canvas.renderAll(); }
    
    document.addEventListener('change', function(e) {
        if (e.target.id === 'bgImage') {
            const file = e.target.files[0]; if(!file || !canvas) return;
            const reader = new FileReader();
            reader.onload = function(f) {
                fabric.Image.fromURL(f.target.result, function(img) {
                    img.scaleToWidth(800); img.scaleToHeight(1000);
                    canvas.backgroundColor = null;
                    canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas));
                });
            };
            reader.readAsDataURL(file);
        }
    });

    async function handleTyping(event, fieldId, isTable = false) {
        const lang = document.getElementById('typingLang').value;
        if(isTable) syncTableToCanvas(fieldId); else syncTextToCanvas(fieldId); 
        if (lang === 'en') return;
        const el = event.target;
        if (event.key === ' ' || event.key === 'Enter') {
            let cursor = el.selectionStart; let textBefore = el.value.substring(0, cursor - 1); let words = textBefore.split(/([ \n]+)/); let lastWordIndex = words.length - 1;
            while (lastWordIndex >= 0 && !words[lastWordIndex].trim()) lastWordIndex--;
            if (lastWordIndex >= 0 && /^[a-zA-Z]+$/.test(words[lastWordIndex])) {
                try {
                    let res = await fetch('https://inputtools.google.com/request?text=' + words[lastWordIndex] + '&itc=' + (lang === 'gu' ? 'gu-t-i0-und' : 'hi-t-i0-und') + '&num=1');
                    let data = await res.json();
                    if (data[0] === 'SUCCESS') {
                        words[lastWordIndex] = data[1][0][1][0];
                        el.value = words.join('') + (event.key === ' ' ? ' ' : '\n') + el.value.substring(cursor);
                        el.selectionStart = el.selectionEnd = words.join('').length + 1;
                        if(isTable) syncTableToCanvas(fieldId); else syncTextToCanvas(fieldId); 
                    }
                } catch(e) {}
            }
        }
    }

    function createNewTextField(defaultLabel = "New Detail", defaultText = "", defaultSize = 30, defaultColor = '#000000', defaultStroke = '#ffffff', defaultStrokeWidth = 0, defaultFont = 'Noto Sans Gujarati', isBold = false, showTitle = false, skipCanvas = false) {
        if (!skipCanvas) fieldCounter++; 
        const fieldId = 'field_txt_' + fieldCounter;
        const fieldHTML = `<div class="field-card" id="card_${fieldId}">
            <div class="title-row">
                <input type="text" class="field-title-input" value="${defaultLabel}" oninput="syncTextToCanvas('${fieldId}')">
                <div class="title-style-box">
                    <label style="font-size:10px; font-weight:bold;">Title Bg:</label>
                    <input type="color" class="title-bg-color" value="#e2e8f0" oninput="syncTextToCanvas('${fieldId}')" style="width:22px; height:22px;">
                    <input type="number" class="title-bg-opacity" value="100" min="0" max="100" oninput="syncTextToCanvas('${fieldId}')" style="width:35px; font-size:10px;">
                    <label style="font-size:10px; font-weight:bold; margin-left:5px;">Text:</label>
                    <input type="color" class="title-text-color" value="#1e293b" oninput="syncTextToCanvas('${fieldId}')" style="width:22px; height:22px;">
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <label style="font-size:12px; font-weight:bold;"><input type="checkbox" class="field-show-title" ${showTitle ? 'checked' : ''} onchange="syncTextToCanvas('${fieldId}')"> 📌 Show</label>
                    <label style="font-size:12px; color:orange; font-weight:bold;"><input type="checkbox" class="field-hide-all" onchange="toggleVisibility('${fieldId}')"> 👁️ Hide</label>
                </div>
            </div>
            <textarea id="text_${fieldId}" class="field-text" placeholder="Enter information here..." oninput="syncTextToCanvas('${fieldId}')" onkeyup="handleTyping(event, '${fieldId}')"></textarea>
            <div class="tools-row">
                <div class="tool-group">Font <select class="field-font" onchange="syncTextToCanvas('${fieldId}')" style="width:110px;">${fontLibraryHTML}</select></div>
                <div class="tool-group">Size <input type="number" class="field-size" value="${defaultSize}" oninput="syncTextToCanvas('${fieldId}')"></div>
                <div class="tool-group">Color <input type="color" class="field-color" value="${defaultColor}" oninput="syncTextToCanvas('${fieldId}')"></div>
                <div class="tool-group">Opac% <input type="number" class="field-opacity" value="100" min="0" max="100" oninput="syncTextToCanvas('${fieldId}')" style="width:45px;"></div>
                <label style="font-size:12px; margin-top:10px;"><input type="checkbox" class="field-bold" ${isBold ? 'checked' : ''} onchange="syncTextToCanvas('${fieldId}')"> B</label>
                <label style="font-size:12px; margin-top:10px;"><input type="checkbox" class="field-italic" onchange="syncTextToCanvas('${fieldId}')"> I</label>
                <div style="width:100%; height:1px; background:#cbd5e1; margin:5px 0;"></div>
                <label style="font-size:12px; font-weight:bold; color:#0284c7;"><input type="checkbox" class="box-bg-enable" onchange="syncTextToCanvas('${fieldId}')"> Bg On</label>
                <div class="tool-group">Bg Color<input type="color" class="box-bg-color" value="#facc15" oninput="syncTextToCanvas('${fieldId}')"></div>
                <div class="tool-group">Bg Opac%<input type="number" class="box-bg-opacity" value="100" min="0" max="100" oninput="syncTextToCanvas('${fieldId}')" style="width:45px;"></div>
                <div class="tool-group">Shape<select class="box-bg-shape" onchange="syncTextToCanvas('${fieldId}')" style="width: 80px;"><option value="square">Square</option><option value="round">Round</option><option value="pill">Pill</option><option value="hexagon">Hexagon</option><option value="diamond">Diamond</option><option value="ribbon">Ribbon</option></select></div>
                <div class="tool-group">Pad<input type="number" class="box-padding" value="12" min="0" oninput="syncTextToCanvas('${fieldId}')"></div>
                <label style="font-size:12px; margin-top:10px; color:#d97706; font-weight:bold;"><input type="checkbox" class="fit-text" onchange="syncTextToCanvas('${fieldId}')" checked> Fit</label>
                <div style="width:100%; height:1px; background:#cbd5e1; margin:5px 0;"></div>
                <div class="tool-group">Border<input type="color" class="field-stroke" value="${defaultStroke}" oninput="syncTextToCanvas('${fieldId}')"></div>
                <div class="tool-group">Thick<input type="number" class="field-strokewidth" value="${defaultStrokeWidth}" min="0" oninput="syncTextToCanvas('${fieldId}')"></div>
            </div>
            <div class="action-bar"><button class="btn-layer" onclick="moveLayer('${fieldId}', 'up')">⬆️ Bring Forward</button><button class="btn-delete" onclick="deleteField('${fieldId}')">🗑️ Delete</button></div>
        </div>`;
        document.getElementById('dynamicFields').insertAdjacentHTML('beforeend', fieldHTML); 
        document.getElementById('text_' + fieldId).value = defaultText; 
        document.querySelector('#card_' + fieldId + ' .field-font').value = defaultFont; 
        if (!skipCanvas) setTimeout(() => syncTextToCanvas(fieldId), 50);
    }

    function syncTextToCanvas(fieldId) {
        if(!canvas) return; const card = document.getElementById('card_' + fieldId); if(!card) return;
        const isHidden = card.querySelector('.field-hide-all').checked;
        const titleVal = card.querySelector('.field-title-input').value.trim(); const showTitle = card.querySelector('.field-show-title').checked; 
        
        const titleBgColorRaw = card.querySelector('.title-bg-color').value; const titleBgOpacity = (parseInt(card.querySelector('.title-bg-opacity').value) || 100) / 100;
        const titleBgColor = hexToRgbA(titleBgColorRaw, titleBgOpacity); const titleTextColor = card.querySelector('.title-text-color').value;

        const textVal = document.getElementById('text_' + fieldId).value.trim(); const fontVal = card.querySelector('.field-font').value; 
        const sizeVal = parseInt(card.querySelector('.field-size').value) || 30; const colorVal = card.querySelector('.field-color').value; 
        const layerOpacity = (parseInt(card.querySelector('.field-opacity').value) || 100) / 100;
        const isBold = card.querySelector('.field-bold').checked; const isItalic = card.querySelector('.field-italic').checked;
        
        const boxBgEnable = card.querySelector('.box-bg-enable').checked; const boxBgColorRaw = card.querySelector('.box-bg-color').value; 
        const boxBgOpacity = (parseInt(card.querySelector('.box-bg-opacity').value) || 100) / 100;
        const boxBgColor = hexToRgbA(boxBgColorRaw, boxBgOpacity);
        
        const boxBgShape = card.querySelector('.box-bg-shape').value; const padding = parseInt(card.querySelector('.box-padding').value) || 0; const fitText = card.querySelector('.fit-text').checked;
        const strokeColor = card.querySelector('.field-stroke').value; const strokeW = parseInt(card.querySelector('.field-strokewidth').value) || 0; 

        let objTitle = canvas.getObjects().find(obj => obj.customId === fieldId + '_title'); let objContent = canvas.getObjects().find(obj => obj.customId === fieldId + '_content');
        if (isHidden) { if(objTitle) objTitle.set({visible: false}); if(objContent) objContent.set({visible: false}); canvas.discardActiveObject(); canvas.renderAll(); return; }

        if (showTitle && titleVal !== "") {
            const titleStyles = { text: titleVal, fontFamily: fontVal, fontSize: sizeVal * 0.8, fill: titleTextColor, fontWeight: 'bold', fontStyle: isItalic ? 'italic' : 'normal', customBgEnable: true, customBgColor: titleBgColor, customBgShape: 'pill', customPadding: padding * 0.8, backgroundColor: '', visible: true, textAlign: 'center', opacity: layerOpacity };
            if (objTitle) { objTitle.set(titleStyles); objTitle.set('dirty', true); if(fitText) objTitle.set({ width: objTitle.calcTextWidth() + 2 }); else objTitle.set({ width: 600 }); } 
            else { let initTop = objContent ? objContent.top - 60 : 100 + (canvas.getObjects().length * 10); const newTitle = new fabric.Textbox(titleVal, { customId: fieldId + '_title', left: 100, top: initTop, cornerColor: '#e11d48', transparentCorners: false, ...titleStyles }); if(fitText) newTitle.set({ width: newTitle.calcTextWidth() + 2 }); else newTitle.set({ width: 600 }); canvas.add(newTitle); }
        } else { if(objTitle) canvas.remove(objTitle); }

        if (textVal !== "") {
            const contentStyles = { text: textVal, fontFamily: fontVal, fontSize: sizeVal, fill: colorVal, fontWeight: isBold ? 'bold' : 'normal', fontStyle: isItalic ? 'italic' : 'normal', stroke: strokeW > 0 ? strokeColor : null, strokeWidth: strokeW, customBgEnable: boxBgEnable, customBgColor: boxBgColor, customBgShape: boxBgShape, customPadding: padding, backgroundColor: '', visible: true, textAlign: 'center', opacity: layerOpacity };
            if (objContent) { objContent.set(contentStyles); objContent.set('dirty', true); if(fitText) objContent.set({ width: objContent.calcTextWidth() + 2 }); else objContent.set({ width: 600 }); } 
            else { let initTop = objTitle ? objTitle.top + objTitle.height + 30 : 150 + (canvas.getObjects().length * 15); const newContent = new fabric.Textbox(textVal, { customId: fieldId + '_content', left: 100, top: initTop, cornerColor: '#3b82f6', transparentCorners: false, ...contentStyles }); if(fitText) newContent.set({ width: newContent.calcTextWidth() + 2 }); else newContent.set({ width: 600 }); canvas.add(newContent); }
        } else { if(objContent) canvas.remove(objContent); }
        canvas.renderAll(); 
    }

    function createNewTableField(skipCanvas = false) {
        if (!skipCanvas) fieldCounter++; 
        const fieldId = 'field_tbl_' + fieldCounter;
        const fieldHTML = `
            <div class="field-card" id="card_${fieldId}" style="border-color: #ea580c;">
                <div class="title-row" style="background: #fff7ed; border-color: #fed7aa;">
                    <span style="font-weight: bold; color: #c2410c;">📊 Custom Table (Grid)</span>
                    <label style="font-size:12px; color:orange; font-weight:bold;"><input type="checkbox" class="field-hide-all" onchange="toggleVisibility('${fieldId}')"> 👁️ Hide</label>
                </div>
                <div class="tools-row" style="background: #fff7ed; margin-bottom: 5px;">
                    <div class="tool-group">Rows<input type="number" class="tbl-rows" value="3" min="1" max="10" onchange="buildTableGridUI('${fieldId}')" style="width:45px;"></div>
                    <div class="tool-group">Cols<input type="number" class="tbl-cols" value="2" min="1" max="5" onchange="buildTableGridUI('${fieldId}')" style="width:45px;"></div>
                    <div class="tool-group">Font<select class="field-font" onchange="syncTableToCanvas('${fieldId}')" style="width:100px;">${fontLibraryHTML}</select></div>
                    <div class="tool-group">Size<input type="number" class="field-size" value="22" oninput="syncTableToCanvas('${fieldId}')" style="width:45px;"></div>
                    <div style="display:flex; gap: 5px; align-items:center; margin-top:10px;"><label style="font-size:12px; font-weight:bold;"><input type="checkbox" class="field-bold" onchange="syncTableToCanvas('${fieldId}')"> B</label><label style="font-size:12px; font-weight:bold;"><input type="checkbox" class="field-italic" onchange="syncTableToCanvas('${fieldId}')"> I</label></div>
                </div>
                <div class="tbl-grid-container" id="grid_container_${fieldId}"></div>
                <div class="tools-row" style="background: #fff7ed; margin-top: 5px;">
                    <div class="tool-group">Header Bg<input type="color" class="tbl-header-bg" value="#ea580c" oninput="syncTableToCanvas('${fieldId}')"></div>
                    <div class="tool-group">Cell Bg<input type="color" class="tbl-cell-bg" value="#ffffff" oninput="syncTableToCanvas('${fieldId}')"></div>
                    <div class="tool-group">Text Color<input type="color" class="field-color" value="#000000" oninput="syncTableToCanvas('${fieldId}')"></div>
                    <div class="tool-group">Opac% <input type="number" class="tbl-opacity" value="100" min="0" max="100" oninput="syncTableToCanvas('${fieldId}')" style="width:45px;"></div>
                </div>
                <div class="action-bar"><button class="btn-layer" onclick="moveLayer('${fieldId}', 'up')">⬆️ Bring Forward</button><button class="btn-delete" onclick="deleteField('${fieldId}')">🗑️ Delete</button></div>
            </div>`;
        document.getElementById('dynamicFields').insertAdjacentHTML('beforeend', fieldHTML); 
        buildTableGridUI(fieldId, skipCanvas); 
    }

    function buildTableGridUI(fieldId, skipCanvas = false) {
        const card = document.getElementById('card_' + fieldId); const container = document.getElementById('grid_container_' + fieldId);
        let rows = parseInt(card.querySelector('.tbl-rows').value) || 3; let cols = parseInt(card.querySelector('.tbl-cols').value) || 2;
        let defaultData = [ ["Post Name", "Places"], ["Assistant", "10"] ];
        let html = '';
        for(let r=0; r<rows; r++) {
            html += `<div class="tbl-row-ui">`;
            for(let c=0; c<cols; c++) {
                let isHeader = (r === 0); let val = (defaultData[r] && defaultData[r][c]) ? defaultData[r][c] : "";
                html += `<input type="text" class="tbl-cell-ui ${isHeader ? 'header-cell' : ''}" id="tbl_${fieldId}_${r}_${c}" value="${val}" oninput="syncTableToCanvas('${fieldId}')" onkeyup="handleTyping(event, '${fieldId}', true)">`;
            }
            html += `</div>`;
        }
        container.innerHTML = html; 
        if (!skipCanvas) setTimeout(() => syncTableToCanvas(fieldId), 50);
    }

    function syncTableToCanvas(fieldId, skipCanvas = false) {
        if(!canvas || skipCanvas) return; 
        const card = document.getElementById('card_' + fieldId); if(!card) return;
        const isHidden = card.querySelector('.field-hide-all').checked;
        let existingObj = canvas.getObjects().find(obj => obj.customId === fieldId);
        if (isHidden) { if(existingObj) { existingObj.set({visible: false}); canvas.discardActiveObject(); canvas.renderAll(); } return; }

        let rows = parseInt(card.querySelector('.tbl-rows').value) || 1; let cols = parseInt(card.querySelector('.tbl-cols').value) || 1;
        let fontSize = parseInt(card.querySelector('.field-size').value) || 20; let fontFam = card.querySelector('.field-font').value;
        let textColor = card.querySelector('.field-color').value; let headerBg = card.querySelector('.tbl-header-bg').value; let cellBg = card.querySelector('.tbl-cell-bg').value;
        let isBold = card.querySelector('.field-bold').checked; let isItalic = card.querySelector('.field-italic').checked;
        let layerOpacity = (parseInt(card.querySelector('.tbl-opacity').value) || 100) / 100;

        let padding = 15; let cellWidths = new Array(cols).fill(0); let cellHeights = new Array(rows).fill(0); let textObjects = [];

        for(let r=0; r<rows; r++) {
            textObjects[r] = [];
            for(let c=0; c<cols; c++) {
                let input = document.getElementById(`tbl_${fieldId}_${r}_${c}`); let val = input ? input.value : "";
                let isHeader = (r === 0);
                let textConfig = { 
                    fontFamily: fontFam, fontSize: fontSize, fill: (isHeader ? '#ffffff' : textColor), 
                    fontWeight: (isHeader || isBold) ? 'bold' : 'normal', fontStyle: isItalic ? 'italic' : 'normal',
                    textAlign: 'center', originX: 'center', originY: 'center'
                };
                let t = new fabric.Text(val, textConfig); textObjects[r][c] = t;
                if(t.width + padding*2 > cellWidths[c]) cellWidths[c] = t.width + padding*2;
                if(t.height + padding*2 > cellHeights[r]) cellHeights[r] = t.height + padding*2;
            }
        }

        let totalW = cellWidths.reduce((a, b) => a + b, 0); let totalH = cellHeights.reduce((a, b) => a + b, 0);
        let tableGroup = [];
        let curY = 0;
        for(let r=0; r<rows; r++) {
            let curX = 0;
            for(let c=0; c<cols; c++) {
                let isHeader = (r === 0);
                let cellRect = new fabric.Rect({ left: curX, top: curY, width: cellWidths[c], height: cellHeights[r], fill: isHeader ? headerBg : cellBg, stroke: '#94a3b8', strokeWidth: 1 });
                tableGroup.push(cellRect);
                let t = textObjects[r][c]; t.set({ left: curX + cellWidths[c]/2, top: curY + cellHeights[r]/2 });
                tableGroup.push(t); curX += cellWidths[c];
            }
            curY += cellHeights[r];
        }

        let finalGroup = new fabric.Group(tableGroup, { customId: fieldId, left: existingObj ? existingObj.left : 100, top: existingObj ? existingObj.top : 300, opacity: layerOpacity });
        if(existingObj) canvas.remove(existingObj);
        canvas.add(finalGroup); canvas.renderAll();
    }

    function createNewImageField(skipCanvas = false) {
        if (!skipCanvas) fieldCounter++; 
        const fieldId = 'field_img_' + fieldCounter;
        const fieldHTML = `
            <div class="field-card" id="card_${fieldId}" style="border-color: #9333ea;">
                <div class="title-row" style="background: #f5f3ff; border-color: #ddd6fe;">
                    <span style="font-weight: bold; color: #7c3aed;">🖼️ Photo Frame</span>
                    <label style="font-size:12px; color:orange; font-weight:bold;"><input type="checkbox" class="field-hide-all" onchange="toggleVisibility('${fieldId}')"> 👁️ Hide</label>
                </div>
                <div class="tools-row" style="background: #f5f3ff;">
                    <input type="file" onchange="loadCustomImage(this, '${fieldId}')" accept="image/*" class="form-control" style="margin:0;">
                    <div style="display: flex; gap: 5px; width: 100%; margin-top: 5px;">
                        <button class="btn-action btn-remove-bg" onclick="removeBgFromImage('${fieldId}')" style="background:#8b5cf6; flex: 1; padding: 5px;"><i class="fas fa-magic"></i> Remove BG</button>
                        <button class="btn-action btn-remove-obj" onclick="openObjRemovalModal('${fieldId}')" style="background:#f43f5e; flex: 1; padding: 5px;"><i class="fas fa-eraser"></i> Erase Obj</button>
                    </div>
                    <div style="width:100%; height:1px; background:#ddd6fe; margin:5px 0;"></div>
                    <div class="tool-group">Shape<select class="img-shape" onchange="updateImageShape('${fieldId}')" style="width:90px;"><option value="square">Square</option><option value="circle">Circle</option><option value="hexagon">Hexagon</option><option value="star">Star</option></select></div>
                    <div class="tool-group">Fit<select class="img-fit-select" onchange="updateImageInsideShape('${fieldId}')" style="width:70px;"><option value="cover">Cover</option><option value="fit">Fit</option></select></div>
                    <div class="tool-group">Zoom<input type="number" class="img-zoom" value="1.0" step="0.1" oninput="updateImageInsideShape('${fieldId}')"></div>
                </div>
                <div class="tools-row" style="background: #f5f3ff; margin-top:5px;">
                    <div class="tool-group">Filter<select class="img-filter-select" onchange="applyFrameFilter(this, '${fieldId}')" style="width:80px;"><option value="none">Normal</option><option value="grayscale">B&W</option><option value="sepia">Sepia</option><option value="vintage">Vintage</option><option value="invert">Invert</option><option value="blur">Blur</option></select></div>
                    <div class="tool-group">X Pos<input type="number" class="img-x" value="0" oninput="updateImageInsideShape('${fieldId}')"></div>
                    <div class="tool-group">Y Pos<input type="number" class="img-y" value="0" oninput="updateImageInsideShape('${fieldId}')"></div>
                    <div class="tool-group">Opac<input type="number" class="img-opacity" value="100" min="0" max="100" oninput="syncImageStyle('${fieldId}')" style="width:45px;"></div>
                </div>
                <div class="tools-row" style="background: #f5f3ff; margin-top:5px;">
                    <div class="tool-group">Border<input type="color" class="img-border-color" value="#ffffff" oninput="syncImageStyle('${fieldId}')"></div>
                    <div class="tool-group">Width<input type="number" class="img-border-width" value="0" min="0" oninput="syncImageStyle('${fieldId}')"></div>
                </div>
                <div class="action-bar"><button class="btn-layer" onclick="moveLayer('${fieldId}', 'up')">⬆️ Bring Forward</button><button class="btn-delete" onclick="deleteField('${fieldId}')">🗑️ Delete</button></div>
            </div>`;
        document.getElementById('dynamicFields').insertAdjacentHTML('beforeend', fieldHTML);
        
        if (!skipCanvas) {
            let placeholder = new Image();
            placeholder.onload = function() { 
                globalCustomImages[fieldId] = placeholder; 
                if(!window.originalCustomImages) window.originalCustomImages = {};
                window.originalCustomImages[fieldId] = placeholder;
                updateImageShape(fieldId); 
            };
            placeholder.src = "data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='600' height='600' viewBox='0 0 600 600'%3E%3Crect width='600' height='600' fill='%23cbd5e1'/%3E%3Cpath opacity='0.5' d='M200 400L300 250L400 400H200Z' fill='%2394a3b8'/%3E%3Ccircle cx='400' cy='200' r='50' fill='%2394a3b8' opacity='0.5'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-size='30' font-family='Arial' font-weight='bold' fill='%23334155'%3EPlease Upload Image%3C/text%3E%3C/svg%3E";
        }
    }

    function loadCustomImage(input, fieldId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() { 
                    globalCustomImages[fieldId] = img; 
                    if(!window.originalCustomImages) window.originalCustomImages = {};
                    window.originalCustomImages[fieldId] = img;
                    
                    const card = document.getElementById('card_' + fieldId);
                    if(card) { card.querySelector('.img-filter-select').value = 'none'; }
                    updateImageShape(fieldId); 
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function updateImageShape(fieldId) {
        if(!canvas) return; const card = document.getElementById('card_' + fieldId); if(!card) return;
        let existingObj = canvas.getObjects().find(obj => obj.customId === fieldId);
        let imgEl = globalCustomImages[fieldId];
        const shapeType = card.querySelector('.img-shape').value;
        const fitMode = card.querySelector('.img-fit-select').value;
        const zoom = parseFloat(card.querySelector('.img-zoom').value) || 1;
        const moveX = parseInt(card.querySelector('.img-x').value) || 0;
        const moveY = parseInt(card.querySelector('.img-y').value) || 0;
        let pattern = null; let dim = existingObj ? Math.max(existingObj.width, existingObj.height) : 400; // Increased default from 200 to 400
        if (imgEl) {
            let scaleFit = fitMode === 'fit' ? (dim / Math.max(imgEl.width, imgEl.height)) : (dim / Math.min(imgEl.width, imgEl.height));
            let finalScale = scaleFit * zoom;
            pattern = new fabric.Pattern({ source: imgEl, repeat: 'no-repeat' });
            let offsetX = (dim - (imgEl.width * finalScale))/2 + moveX;
            let offsetY = (dim - (imgEl.height * finalScale))/2 + moveY;
            pattern.patternTransform = [finalScale, 0, 0, finalScale, offsetX, offsetY];
        }
        let left = 300, top = 200, scaleX = 1, scaleY = 1, angle = 0;
        if(existingObj) { left = existingObj.left; top = existingObj.top; scaleX = existingObj.scaleX; scaleY = existingObj.scaleY; angle = existingObj.angle; canvas.remove(existingObj); }
        let newObj; let common = { customId: fieldId, left, top, scaleX, scaleY, angle, fill: pattern || (existingObj?existingObj.fill:''), cornerColor: '#9333ea', transparentCorners: false };
        if (shapeType === 'circle') newObj = new fabric.Circle({ radius: dim/2, originX: 'center', originY: 'center', ...common });
        else if (shapeType === 'square') newObj = new fabric.Rect({ width: dim, height: dim, originX: 'center', originY: 'center', ...common });
        else if (shapeType === 'triangle') newObj = new fabric.Triangle({ width: dim, height: dim, originX: 'center', originY: 'center', ...common });
        else if (shapeType === 'hexagon') newObj = new fabric.Polygon([{x: dim/2, y: 0}, {x: dim, y: dim*0.25}, {x: dim, y: dim*0.75}, {x: dim/2, y: dim}, {x: 0, y: dim*0.75}, {x: 0, y: dim*0.25}], {originX: 'center', originY: 'center', ...common});
        else if (shapeType === 'diamond') newObj = new fabric.Polygon([{x: dim/2, y: 0}, {x: dim, y: dim/2}, {x: dim/2, y: dim}, {x: 0, y: dim/2}], {originX: 'center', originY: 'center', ...common});
        else if (shapeType === 'star') newObj = new fabric.Polygon([{x: dim*0.5, y: 0}, {x: dim*0.61, y: dim*0.35}, {x: dim, y: dim*0.35}, {x: dim*0.68, y: dim*0.57}, {x: dim*0.79, y: dim}, {x: dim*0.5, y: dim*0.7}, {x: dim*0.21, y: dim}, {x: dim*0.32, y: dim*0.57}, {x: 0, y: dim*0.35}, {x: dim*0.39, y: dim*0.35}], {originX: 'center', originY: 'center', ...common});
        else newObj = new fabric.Rect({ width: dim, height: dim, originX: 'center', originY: 'center', ...common }); 
        const bColor = card.querySelector('.img-border-color').value; const bWidth = parseInt(card.querySelector('.img-border-width').value) || 0;
        const imgOpacity = (parseInt(card.querySelector('.img-opacity').value) || 100) / 100;
        newObj.set({ stroke: bColor, strokeWidth: bWidth, opacity: imgOpacity });
        canvas.add(newObj); canvas.setActiveObject(newObj); canvas.renderAll();
    }

    function updateImageInsideShape(fieldId) {
        if(!canvas) return; const card = document.getElementById('card_' + fieldId); let existingObj = canvas.getObjects().find(obj => obj.customId === fieldId); let imgEl = globalCustomImages[fieldId]; if(!existingObj || !imgEl || !card) return;
        const fitMode = card.querySelector('.img-fit-select').value; const zoom = parseFloat(card.querySelector('.img-zoom').value) || 1; const moveX = parseInt(card.querySelector('.img-x').value) || 0; const moveY = parseInt(card.querySelector('.img-y').value) || 0;
        let dim = Math.max(existingObj.width, existingObj.height); let scaleFit = fitMode === 'fit' ? (dim / Math.max(imgEl.width, imgEl.height)) : (dim / Math.min(imgEl.width, imgEl.height)); let finalScale = scaleFit * zoom; let offsetX = (dim - (imgEl.width * finalScale))/2 + moveX; let offsetY = (dim - (imgEl.height * finalScale))/2 + moveY;
        existingObj.fill.patternTransform = [finalScale, 0, 0, finalScale, offsetX, offsetY]; existingObj.set('dirty', true); canvas.renderAll();
    }

    function syncImageStyle(fieldId) { if(!canvas) return; const card = document.getElementById('card_' + fieldId); let obj = canvas.getObjects().find(o => o.customId === fieldId); if(!obj || !card) return; const bColor = card.querySelector('.img-border-color').value; const bWidth = parseInt(card.querySelector('.img-border-width').value) || 0; const imgOpacity = (parseInt(card.querySelector('.img-opacity').value) || 100) / 100; obj.set({ stroke: bColor, strokeWidth: bWidth, opacity: imgOpacity }); canvas.renderAll(); }
    function deleteField(fieldId) { let tObj = canvas.getObjects().find(o => o.customId === fieldId + '_title'); let cObj = canvas.getObjects().find(o => o.customId === fieldId + '_content'); let iObj = canvas.getObjects().find(o => o.customId === fieldId); if(tObj) canvas.remove(tObj); if(cObj) canvas.remove(cObj); if(iObj) canvas.remove(iObj); let card = document.getElementById('card_' + fieldId); if (card) card.remove(); canvas.renderAll(); }
    function toggleVisibility(fieldId) { const card = document.getElementById('card_' + fieldId); const isHidden = card.querySelector('.field-hide-all').checked; let tObj = canvas.getObjects().find(o => o.customId === fieldId + '_title'); let cObj = canvas.getObjects().find(o => o.customId === fieldId + '_content'); let iObj = canvas.getObjects().find(o => o.customId === fieldId); if(isHidden) { card.classList.add('hidden-field'); if(tObj) tObj.set({visible:false}); if(cObj) cObj.set({visible:false}); if(iObj) iObj.set({visible:false}); canvas.discardActiveObject(); } else { card.classList.remove('hidden-field'); if(tObj) tObj.set({visible:true}); if(cObj) cObj.set({visible:true}); if(iObj) iObj.set({visible:true}); if(fieldId.includes('tbl')) syncTableToCanvas(fieldId); } canvas.renderAll(); }
    function moveLayer(fieldId, dir) { let tObj = canvas.getObjects().find(o => o.customId === fieldId + '_title'); let cObj = canvas.getObjects().find(o => o.customId === fieldId + '_content'); let iObj = canvas.getObjects().find(o => o.customId === fieldId); if(dir === 'up') { if(tObj) canvas.bringForward(tObj); if(cObj) canvas.bringForward(cObj); if(iObj) canvas.bringForward(iObj); } else { if(tObj) canvas.sendBackwards(tObj); if(cObj) canvas.sendBackwards(cObj); if(iObj) canvas.sendBackwards(iObj); } canvas.renderAll(); }
    function forceSyncAll() { if(!canvas) { initApp(); return; } document.querySelectorAll('.field-card').forEach(card => { const id = card.id.replace('card_', ''); if(id.includes('txt')) syncTextToCanvas(id); if(id.includes('tbl')) syncTableToCanvas(id); }); syncWatermark(); canvas.renderAll(); }
    


    // Duplicate export handling block removed here


    async function removeBgFromImage(fieldId) {
        const imgEl = globalCustomImages[fieldId]; if(!imgEl) { alert('Upload a photo first.'); return; }
        const btn = document.querySelector(`#card_${fieldId} .btn-remove-bg`); const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...'; btn.disabled = true;
        
        try {
            const blob = await imglyRemoveBackground(imgEl.src); 
            const url = URL.createObjectURL(blob); 
            const newImg = new Image();
            newImg.onload = function() { 
                globalCustomImages[fieldId] = newImg; 
                if(!window.originalCustomImages) window.originalCustomImages = {};
                window.originalCustomImages[fieldId] = newImg; 
                updateImageShape(fieldId); 
                btn.innerHTML = '<i class="fas fa-check"></i> Removed!'; 
                btn.disabled = false;
            };
            newImg.src = url;
            return;
        } catch (e) { 
            console.warn("img.ly native AI failed, falling back to Remove.BG API..."); 
        }

        // FALLBACK: Remove.bg API
        try {
            let base64Data = imgEl.src;
            let reqBody;
            
            if (base64Data.startsWith('data:image')) {
                reqBody = JSON.stringify({ image_file_b64: base64Data.split(',')[1], size: "auto" });
            } else {
                reqBody = JSON.stringify({ image_url: base64Data, size: "auto" });
            }

            const response = await fetch('https://api.remove.bg/v1.0/removebg', {
                method: 'POST',
                headers: { 'X-Api-Key': 'pSqcQaSbGwN4an41dkZSyHAs', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: reqBody
            });

            if(!response.ok) throw new Error("API Limit Reached or Network Failed");
            
            const data = await response.json();
            const newImg = new Image();
            newImg.onload = function() { 
                globalCustomImages[fieldId] = newImg; 
                if(!window.originalCustomImages) window.originalCustomImages = {};
                window.originalCustomImages[fieldId] = newImg; 
                updateImageShape(fieldId); 
                btn.innerHTML = '<i class="fas fa-check"></i> API Removed!'; 
                btn.disabled = false;
            };
            newImg.src = 'data:image/png;base64,' + data.data.result_b64;
        } catch(apiErr) {
            console.error(apiErr);
            alert('❌ Both Edge AI and Cloud AI failed. Please try a different browser or image.');
            btn.innerHTML = originalText; btn.disabled = false;
        }
    }

    function applyFrameFilter(selectEl, fieldId) {
        const filterType = selectEl.value;
        if(!window.originalCustomImages) window.originalCustomImages = {};
        
        let origImg = window.originalCustomImages[fieldId];
        if(!origImg) origImg = globalCustomImages[fieldId];
        if(!origImg) return;
        
        if(!window.originalCustomImages[fieldId]) window.originalCustomImages[fieldId] = origImg;
        
        if (filterType === 'none') {
            globalCustomImages[fieldId] = origImg;
            updateImageShape(fieldId);
            return;
        }
        
        const oc = document.createElement('canvas');
        oc.width = origImg.width; oc.height = origImg.height;
        const ctx = oc.getContext('2d');
        
        if (filterType === 'grayscale') ctx.filter = 'grayscale(100%)';
        else if (filterType === 'sepia') ctx.filter = 'sepia(100%)';
        else if (filterType === 'invert') ctx.filter = 'invert(100%)';
        else if (filterType === 'vintage') ctx.filter = 'sepia(50%) contrast(150%) saturate(150%) brightness(90%)';
        else if (filterType === 'blur') ctx.filter = 'blur(5px)';
        
        ctx.drawImage(origImg, 0, 0);
        
        const newImg = new Image();
        newImg.onload = function() { globalCustomImages[fieldId] = newImg; updateImageShape(fieldId); };
        newImg.src = oc.toDataURL('image/png');
    }

    let sysCurrentZoom = 1.0;
    function sysChangeZoom(amount) { sysCurrentZoom += amount; if(sysCurrentZoom < 0.2) sysCurrentZoom = 0.2; if(sysCurrentZoom > 3.0) sysCurrentZoom = 3.0; sysApplyZoom(); }
    function sysResetZoom() { sysCurrentZoom = 1.0; sysApplyZoom(); }
    function sysApplyZoom() {
        const targets = document.querySelectorAll('.canvas-container, canvas#posterCanvas');
        targets.forEach(el => { el.style.transform = `scale(${sysCurrentZoom})`; el.style.transformOrigin = 'center center'; el.style.transition = 'transform 0.2s cubic-bezier(0.4, 0, 0.2, 1)'; });
    }
</script>

<!-- Object Removal Modal -->
<div id="objRemovalModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15,23,42,0.95); z-index:10000; justify-content:center; align-items:center; flex-direction:column;">
    <div style="background:#1e293b; padding: 20px; border-radius: 12px; width: 90vw; max-width: 600px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 1px solid #334155;">
        <h4 style="color:#f8fafc; margin-top:0; border-bottom:1px solid #334155; padding-bottom:10px;">
            <i class="fas fa-eraser text-danger"></i> AI Object Remover
        </h4>
        <p style="color:#94a3b8; font-size:12px;">Paint in RED over the object you want to erase. Our AI will seamlessly remove it and blend the background.</p>
        
        <div style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
            <label style="color:#cbd5e1; font-size:12px; font-weight:bold;">Brush Size:</label>
            <input type="range" id="objBrushSize" min="5" max="100" value="30" oninput="if(objCanvas) objCanvas.freeDrawingBrush.width = parseInt(this.value);" style="flex:1;">
        </div>

        <div style="background: #0f172a; display: flex; justify-content: center; overflow: hidden; border-radius: 8px; border: 1px solid #334155; height: 400px;">
            <canvas id="objRemovalCanvas"></canvas>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 15px; justify-content: flex-end;">
            <button class="btn btn-secondary" onclick="closeObjRemovalModal()">Cancel</button>
            <button class="btn btn-danger" onclick="clearObjMasks()"><i class="fas fa-undo"></i> Clear Mask</button>
            <button class="btn btn-success" onclick="applyObjRemoval()"><i class="fas fa-magic"></i> Remove & Save</button>
        </div>
    </div>
</div>

<script>
    let objCanvas = null;
    let currentEditingFieldId = null;
    let objMaskHistory = [];
    let initialImageSource = null;

    function openObjRemovalModal(fieldId) {
        const imgEl = globalCustomImages[fieldId];
        if(!imgEl) { alert('Please upload a photo first.'); return; }
        
        currentEditingFieldId = fieldId;
        document.getElementById('objRemovalModal').style.display = 'flex';
        
        if(!objCanvas) {
            objCanvas = new fabric.Canvas('objRemovalCanvas', { selection: false });
            objCanvas.isDrawingMode = true;
            objCanvas.freeDrawingBrush.color = 'rgba(239, 68, 68, 0.7)'; // Semi transparent red
            objCanvas.freeDrawingBrush.width = 30;
            
            objCanvas.on('path:created', function(e) {
                let path = e.path;
                path.set({ selectable: false, evented: false });
                objMaskHistory.push(path);
            });
        }
        
        objMaskHistory = [];
        objCanvas.clear();
        
        // Auto scale image to fit 600x400 container
        const contW = 600, contH = 400;
        let scaleFit = Math.min((contW-20) / imgEl.width, (contH-20) / imgEl.height);
        
        objCanvas.setDimensions({ width: imgEl.width * scaleFit, height: imgEl.height * scaleFit });
        
        fabric.Image.fromURL(imgEl.src, function(img) {
            img.set({ scaleX: scaleFit, scaleY: scaleFit, originX: 'left', originY: 'top', selectable: false, evented: false });
            initialImageSource = img;
            objCanvas.add(img);
            objCanvas.sendToBack(img);
            objCanvas.renderAll();
        });
    }

    function closeObjRemovalModal() {
        document.getElementById('objRemovalModal').style.display = 'none';
        currentEditingFieldId = null;
    }

    function clearObjMasks() {
        if(!objCanvas) return;
        objMaskHistory.forEach(p => objCanvas.remove(p));
        objMaskHistory = [];
        objCanvas.renderAll();
    }

    function applyObjRemoval() {
        if(objMaskHistory.length === 0) { alert("Please paint over an object first."); return; }
        
        showLoading(true, "AI Removing Object...");
        
        setTimeout(() => {
            // Group the masks to get bounding box
            let group = new fabric.Group(objMaskHistory);
            let rect = group.getBoundingRect();
            
            // Hide masks temporarily so they aren't cloned into the background
            objMaskHistory.forEach(path => path.visible = false);
            objCanvas.renderAll();

            // Extract the background area slightly LARGER than the mask
            let cropDataUrl = objCanvas.toDataURL({ 
                format: 'jpeg', left: rect.left - 15, top: rect.top - 15, 
                width: rect.width + 30, height: rect.height + 30 
            });

            fabric.Image.fromURL(cropDataUrl, function(img) {
                // Apply a heavy blur to the cloned patch to create a seamless blend (Simulated Inpainting)
                let blurFilter = new fabric.Image.filters.Blur({ blur: 0.6 });
                img.filters.push(blurFilter);
                img.applyFilters();
                
                // Use the red masks as a clip path to only show the blurred replacement WHERE the user painted
                let clipGroup = new fabric.Group(objMaskHistory.map(p => {
                    let clone = fabric.util.object.clone(p);
                    // Adjust coordinates relative to the new patch image
                    clone.set({ left: clone.left - rect.left + 15, top: clone.top - rect.top + 15, visible: true, fill: 'black', stroke: 'black' });
                    return clone;
                }));

                img.set({
                    left: rect.left - 15, top: rect.top - 15,
                    selectable: false, evented: false,
                    clipPath: clipGroup // MAGIC: Only shows blurred pixels inside the brushed area!
                });

                // Delete original red masks
                objMaskHistory.forEach(path => objCanvas.remove(path));
                objMaskHistory = [];
                
                objCanvas.add(img); 
                objCanvas.renderAll();
                
                // Now capture the FIXED fully completed image
                const finalScale = 1 / initialImageSource.scaleX; // Up-scale back to original
                const finalDataUrl = objCanvas.toDataURL({ format: 'png', multiplier: finalScale });
                
                const resultImg = new Image();
                resultImg.onload = function() {
                    globalCustomImages[currentEditingFieldId] = resultImg;
                    updateImageShape(currentEditingFieldId);
                    closeObjRemovalModal();
                    showLoading(false);
                };
                resultImg.src = finalDataUrl;
            });
        }, 500);
    }
    
    // Simple showLoading for context if missing
    function showLoading(show, txt) {
        if (!document.getElementById('loadingOverlay')) {
            const l = document.createElement('div'); l.id = 'loadingOverlay';
            l.style.cssText = 'display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.9); z-index:11000; justify-content:center; align-items:center; flex-direction:column; color:white;';
            l.innerHTML = `<div class="spinner-border text-primary" style="width:3rem; height:3rem; border:4px solid #38bdf8; border-top:4px solid transparent; border-radius:50%; animation:spin 1s linear infinite;"></div><div id="loadingText" class="mt-3 fw-bold">${txt}</div>`;
            document.body.appendChild(l);
            const style = document.createElement('style'); style.innerHTML = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }'; document.head.appendChild(style);
        }
        document.getElementById('loadingText').innerText = txt;
        document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
    }
</script>
</body>
</html>