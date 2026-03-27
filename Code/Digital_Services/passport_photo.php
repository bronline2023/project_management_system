<?php
require_once CORE_INCLUDES_PATH . 'service_paywall.php';
enforce_service_paywall('passport_photo');

/**
 * views/passport_photo.php
 * ULTIMATE PRO STUDIO: Pre-configured Native AI, Perfect Borders, Drag & Drop Name, Smooth Touch
 */

$pdo = connectDB();
$card_cost = 10.00; 
$currency = '₹';
$user_role = $_SESSION['user_role'] ?? 'guest';

try {
    $stmt = $pdo->query("SELECT poster_generation_cost, currency_symbol FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings) {
        $card_cost = isset($settings['poster_generation_cost']) ? (float)$settings['poster_generation_cost'] : 10.00;
        $currency = isset($settings['currency_symbol']) ? $settings['currency_symbol'] : '₹';
    }

    if (isset($_SESSION['user_id'])) {
        $stmt_user = $pdo->prepare("SELECT custom_poster_rate FROM users WHERE id = ?");
        $stmt_user->execute([$_SESSION['user_id']]);
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data && isset($user_data['custom_poster_rate']) && $user_data['custom_poster_rate'] !== null && $user_data['custom_poster_rate'] !== '') {
            $card_cost = (float)$user_data['custom_poster_rate'];
        }
        
        $sub_stmt = $pdo->prepare("SELECT id FROM user_subscriptions WHERE user_id = ? AND status = 'active' AND end_date >= NOW()");
        $sub_stmt->execute([$_SESSION['user_id']]);
        $sub = $sub_stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}

$loaded_draft_json = null;
if (isset($_GET['draft_id']) && isset($_SESSION['user_id'])) {
    try {
        $stmt_draft = $pdo->prepare("SELECT canvas_json FROM digital_service_history WHERE id = ? AND user_id = ? AND is_draft = 1");
        $stmt_draft->execute([$_GET['draft_id'], $_SESSION['user_id']]);
        $draft_row = $stmt_draft->fetch(PDO::FETCH_ASSOC);
        if ($draft_row) {
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
<script src="https://cdn.jsdelivr.net/npm/@imgly/background-removal@1.4.5/dist/imgly-background-removal.js"></script>

<style>
    @media print {
        body * { visibility: hidden !important; }
        .workspace, .workspace canvas, .canvas-container { visibility: visible !important; }
        .workspace { position: absolute; left: 0; top: 0; width: 100vw; height: 100vh; margin: 0; padding: 0; overflow: visible !important; }
        .studio-panel, .sys-zoom-controls { display: none !important; }
    }
    
    /* Full Screen Studio Mode */
    #sidebar { display: none !important; }
    .navbar { display: none !important; }
    #content { padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    .wrapper { margin: 0 !important; padding: 0 !important; }
    body { background-color: #0f172a; overflow: auto; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
    
    .studio-wrapper { display: flex; min-height: 100vh; height: 100%; width: 100vw; background-color: #0f172a; color: #1e293b; }
    .studio-panel { width: 440px; min-width: 440px; background: #ffffff; display: flex; flex-direction: column; border-right: 2px solid #334155; z-index: 100; height: 100%; box-shadow: 5px 0 20px rgba(0,0,0,0.2); overflow-y: auto;}
    .studio-header { padding: 15px; background: #111827; color: #38bdf8; text-align: center; font-size: 16px; font-weight: bold; text-transform: uppercase; display: flex; justify-content: space-between; align-items: center; }
    .btn-back-dashboard { background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; text-decoration: none;}
    
    .controls-area { flex-grow: 1; padding: 20px; background: #f8fafc; }
    /* 🚀 ZOOM CONTROLS UI 🚀 */
    .sys-zoom-controls { position: absolute; bottom: 20px; right: 20px; background: rgba(255,255,255,0.98); padding: 12px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); display: flex; flex-direction: column; gap: 8px; z-index: 10001; border: 1px solid #e2e8f0; }
    .sys-zoom-btn { background: #f8fafc; border: 1px solid #cbd5e1; color: #1e293b; width: 40px; height: 40px; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.2s; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .sys-zoom-btn:hover { background: #3b82f6; color: white; border-color: #3b82f6; }
    .workspace { position: relative; flex-grow: 1; display: flex; justify-content: center; align-items: center; overflow: hidden; background-image: radial-gradient(#cbd5e1 1px, transparent 0); background-size: 20px 20px; z-index: 1; }
    
    .control-box { background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #cbd5e1; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .control-title { font-weight: bold; font-size: 14px; color: #0f172a; margin-bottom: 15px; display: block; border-bottom: 2px solid #38bdf8; padding-bottom: 5px; }
    
    .form-label { font-size: 12px; font-weight: bold; color: #475569; margin-bottom: 5px; display: block; }
    .form-control { width: 100%; padding: 8px; margin-bottom: 12px; border-radius: 6px; border: 1px solid #94a3b8; font-size: 13px; outline: none; }
    
    .btn-success-action { background: #10b981; color: white; padding: 10px; border: none; border-radius: 6px; font-weight: bold; font-size: 14px; cursor: pointer; width: 100%; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3); transition: background 0.3s;}
    .btn-success-action:hover { background: #059669; }
    .btn-ai-magic { background: linear-gradient(135deg, #ec4899, #8b5cf6); color: white; padding: 10px; border: none; border-radius: 6px; font-weight: bold; font-size: 14px; cursor: pointer; width: 100%; box-shadow: 0 4px 10px rgba(139, 92, 246, 0.3); transition: all 0.3s;}
    .btn-ai-magic:hover { background: linear-gradient(135deg, #db2777, #7c3aed); transform: translateY(-2px); }
    
    .btn-step-back { background: #e2e8f0; color: #334155; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: bold; font-size: 13px; cursor: pointer; width: 100%; margin-bottom: 15px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;}
    .btn-step-back:hover { background: #cbd5e1; }

    .action-btns { padding: 15px; background: #ffffff; border-top: 1px solid #cbd5e1; }
    .btn-export { background: #16a34a; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer; width: 100%; }

    .slider-container { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
    .slider-container input[type="range"] { flex-grow: 1; margin: 0 10px; }
    .slider-label { font-size: 11px; font-weight: bold; width: 85px; color: #334155; }

    .tool-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
    .tool-btn { background: #f8fafc; border: 1px solid #cbd5e1; padding: 6px; border-radius: 6px; font-size: 12px; font-weight: bold; cursor: pointer; text-align: center; color: #1e293b; transition: all 0.2s; }
    .tool-btn:hover { background: #e2e8f0; border-color: #94a3b8; }

    .check-label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: bold; color: #1e293b; cursor: pointer; margin-bottom: 10px;}
    
    #loadingOverlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15,23,42,0.95); color: white; display: none; flex-direction: column; justify-content: center; align-items: center; z-index: 1000; font-weight: bold; font-size: 16px; text-align: center; padding: 20px;}
    .spinner { border: 6px solid #f3f3f3; border-top: 6px solid #38bdf8; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin-bottom: 15px; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

    /* ========================================================== */
    /* 📱 MOBILE RESPONSIVENESS FIXES (Injected by System) 📱      */
    /* ========================================================== */
    @media (max-width: 992px) {
        .studio-wrapper, .builder-wrapper { flex-direction: column !important; height: auto !important; width: 100vw !important; overflow-x: hidden; }
        .studio-panel { width: 100% !important; min-width: 100% !important; height: auto !important; max-height: 55vh; overflow-y: auto; border-right: none !important; border-bottom: 2px solid #cbd5e1; }
        .workspace { width: 100% !important; height: 45vh !important; min-height: 45vh !important; padding: 10px !important; overflow-y: auto; }
        .canvas-container { max-width: 100% !important; height: auto !important; margin: 0 auto; }
        canvas { max-width: 100% !important; height: auto !important; }
        .a4-page, .card-preview { max-width: 100%; transform: scale(0.65) !important; transform-origin: top center !important; margin-bottom: 0 !important; }
        .mobile-gap { margin-bottom: 60px; }
        .action-btns { flex-wrap: wrap; justify-content: center; width: 100%; }
        .btn-export { width: 100%; margin-top: 10px; }
    }
</style>

<?php $page_title = 'Master Passport Studio'; require_once INCLUDES_PATH.'digital_header.php'; ?>
<div class="studio-wrapper" style="height: calc(100vh - 65px); min-height: calc(100vh - 65px);">
    <div class="studio-panel">
        
        <div class="controls-area">
            
            <div id="step1Controls">
                <div class="control-box">
                    <span class="control-title">Step 1: Select photo and size</span>

                    <label class="form-label">Upload a photo of the customer</label>
                    <input type="file" id="imageUpload" class="form-control" accept="image/*" onchange="loadImage(event)">

                    <label class="form-label mt-2">Country / Size Format</label>
                    <select class="form-control fw-bold text-primary" id="passportSize" onchange="setupCropBox()">
                        <option value="india">India Passport (35mm x 45mm)</option>
                        <option value="us">USA / Visa (2 x 2 inch)</option>
                        <option value="uk">UK Passport (35mm x 45mm)</option>
                        <option value="stamp">Stamp Size (20mm x 25mm)</option>
                    </select>

                    <div class="tool-grid mt-3">
                        <button class="tool-btn" onclick="rotateImage(-90)"><i class="fas fa-undo"></i> turn left</button>
                        <button class="tool-btn" onclick="rotateImage(90)"><i class="fas fa-redo"></i> turn right</button>
                    </div>

                    <p style="font-size:11px; color:#ef4444; font-weight:bold; margin-top:10px;">
                        <i class="fas fa-hand-pointer"></i> Set the 'red box' that appears on the screen exactly to the face.
                    </p>
                    <button class="btn-success-action mt-2" onclick="cropAndProceed()"><i class="fas fa-crop-alt"></i> Crop photo ➔</button>
                </div>
            </div>

            <div id="step2Controls" style="display:none;">
                <button class="btn-step-back" onclick="goToStep1()"><i class="fas fa-arrow-left"></i> Go Back (Change Cropping)</button>

                <div class="control-box" style="border-color:#ec4899;">
                    <span class="control-title" style="color:#ec4899;"><i class="fas fa-magic"></i> 1. AI & Background Tools</span>
                    
                    <button class="btn-ai-magic mb-2" onclick="removeBgNativeAI()">
                        <i class="fas fa-user-slash"></i> 1-Click Remove Background
                    </button>

                    <button class="btn-ai-magic mb-3" id="btnMagicClone" onclick="toggleMagicClone()" style="background: linear-gradient(135deg, #8b5cf6, #6366f1);">
                        <i class="fas fa-eraser"></i> Object Eraser (Remove Sign/Stamp)
                    </button>

                    <div id="cloneTools" style="display:none; background:#f1f5f9; padding:10px; border-radius:8px; margin-bottom:15px; border:1px solid #cbd5e1;">
                        <p style="font-size:11px; color:#1e293b; font-weight:bold; margin-bottom:8px;">
                            <i class="fas fa-info-circle"></i> 1. Select area to erase. 2. Move green box to clean area.
                        </p>
                        <button class="btn btn-success btn-sm w-100 fw-bold" onclick="applyClonePatch()">Apply Eraser</button>
                        <button class="btn btn-link btn-sm w-100 text-danger" onclick="resetAllModes()">Cancel</button>
                    </div>

                    <label class="form-label mt-2">New background color</label>
                    <div style="display:flex; gap:10px;">
                        <input type="color" id="bgColor" class="form-control" style="height:35px; padding:0; flex:1;" value="#ffffff" oninput="changeBackground()">
                        <button class="btn btn-sm btn-outline-primary fw-bold" style="height:35px;" onclick="setBg('#3b82f6')">Blue</button>
                        <button class="btn btn-sm btn-outline-danger fw-bold" style="height:35px;" onclick="setBg('#ef4444')">Red</button>
                        <button class="btn btn-sm btn-outline-dark fw-bold" style="height:35px;" onclick="setBg('#ffffff')">White</button>
                    </div>
                </div>

                <div class="control-box" style="border-color:#8b5cf6;">
                    <span class="control-title text-purple" style="color:#8b5cf6;"><i class="fas fa-eye"></i> 2. Smooth Touch & Color</span>
                    
                    <p style="font-size:11px; color:#64748b;">Press Auto Touch to clear and smooth photos without tearing them.</p>

                    <div class="slider-container">
                        <span class="slider-label">Brightness</span>
                        <input type="range" id="valBrightness" min="-0.2" max="0.3" step="0.02" value="0" oninput="applyFilters()">
                    </div>
                    <div class="slider-container">
                        <span class="slider-label">Contrast</span>
                        <input type="range" id="valContrast" min="-0.2" max="0.3" step="0.02" value="0" oninput="applyFilters()">
                    </div>
                    <div class="slider-container">
                        <span class="slider-label">Saturation</span>
                        <input type="range" id="valSaturation" min="-0.2" max="0.6" step="0.05" value="0" oninput="applyFilters()">
                    </div>

                    <div class="tool-grid mt-3 mb-0">
                        <button class="tool-btn text-success" onclick="autoBeauty()"><i class="fas fa-sparkles"></i> Auto Touch</button>
                        <button class="tool-btn text-danger" onclick="resetFilters()"><i class="fas fa-undo"></i> Reset Edit</button>
                    </div>
                </div>

                <div class="control-box" style="border-color:#3b82f6;">
                    <span class="control-title text-primary"><i class="fas fa-text-width"></i> 3. Name & Date (Drag & Drop)</span>
                    <label class="check-label text-danger">
                        <input type="checkbox" id="showNameDate" onchange="toggleNameDateText()"> 
                        Write the name and date on the photo 
                    </label>
                    
                    <div id="nameDateInputs" style="display:none; background:#f8fafc; padding:10px; border:1px solid #cbd5e1; border-radius:6px; margin-top:5px;">
                        <input type="text" id="passName" class="form-control mb-2" placeholder="Person's Name" oninput="updateTextContent()">
                        <input type="text" id="passDate" class="form-control mb-2" placeholder="DD/MM/YYYY" value="<?= date('d/m/Y') ?>" oninput="updateTextContent()">
                        
                        <label class="form-label mt-1">The background color of the bar</label>
                        <input type="color" id="textBgColor" class="form-control mb-0" style="height:35px; padding:0;" value="#ffffff" oninput="updateTextContent()">
                        
                        <p style="font-size:10px; color:#10b981; margin-top:10px; margin-bottom:0; font-weight:bold;">
                            <i class="fas fa-arrows-alt"></i> Now hold the mouse and position the bar anywhere in the photo!
                        </p>
                    </div>
                    
                    <button class="btn-success-action mt-3" onclick="proceedToGrid()"><i class="fas fa-th"></i> Set Paper Size ➔</button>
                </div>
            </div>

            <div id="step3Controls" style="display:none;">
                <button class="btn-step-back" onclick="goToStep2()"><i class="fas fa-arrow-left"></i> Go back (edit photo)</button>

                <div class="control-box" style="border-color:#10b981;">
                    <span class="control-title text-success">Step 3: Auto Paper Grid</span>
                    <p style="font-size:11px; color:#64748b;">Select the printing paper. Photos will be automatically centered with a black border.</p>
                    
                    <label class="form-label">Printing Paper Size (Page Size)</label>
                    <select class="form-control fw-bold text-dark" id="paperSize" onchange="generateGrid()">
                        <option value="4x6">4x6 Inch (Standard Lab Print)</option>
                        <option value="5x7">5x7 Inch (Medium Sheet)</option>
                        <option value="8x10">8x10 Inch (Large Photo Sheet)</option>
                        <option value="A4">A4 Size (Standard Document)</option>
                        <option value="A3">A3 Size (Poster Sheet)</option>
                    </select>

                    <label class="form-label mt-2">Gap in Pixels</label>
                    <input type="range" id="photoGap" min="10" max="60" step="5" value="30" class="w-100" oninput="generateGrid()">
                </div>
            </div>

        </div>

        <div class="action-btns" id="downloadBlock" style="display:none;">
            <div class="d-flex gap-2 mb-2" style="display: flex; gap: 10px;">
                <button class="btn-export w-100" style="flex:1" onclick="handleExport()"><i class="fas fa-download"></i> Download HD <?= (!isset($_SESSION['user_id']) && isset($_COOKIE['guest_service_used'])) ? '' : '('.$currency.$card_cost.')' ?></button>
                <?php if(isset($sub) && $sub): ?>
                <button class="btn btn-primary fw-bold text-white px-4 border-0 rounded-3 shadow-sm" onclick="handlePrint()" style="background: linear-gradient(135deg, #0ea5e9, #2563eb); border-radius: 6px; border:none; color:white; padding: 0 20px;"><i class="fas fa-print"></i></button>
                <?php else: ?>
                <button class="btn btn-secondary fw-bold px-4 border-0 rounded-3 shadow-none opacity-50" title="Subscribe to unlock priority direct printing" onclick="alert('Direct printing is reserved for premium subscribed users only. You can still download and print manually.')" style="border-radius: 6px; border:none; padding: 0 20px; opacity: 0.5;"><i class="fas fa-print"></i></button>
                <?php endif; ?>
            </div>
            <?php if(isset($_SESSION['user_id'])): ?>
            <button class="btn btn-outline-primary w-100 fw-bold py-2 shadow-sm" style="border-radius:8px;" onclick="saveDraft()"><i class="fas fa-save"></i> Save As Draft</button>
            <?php endif; ?>
            
            <div class="mt-3">
                <label class="form-label text-muted mb-1"><i class="fas fa-tachometer-alt"></i> Export DPI (Quality)</label>
                <select id="exportDPI" class="form-select form-control fw-bold border-primary shadow-sm" style="width: 100%;">
                    <option value="1">Standard (150 DPI)</option>
                    <option value="2">High (300 DPI - Recommended)</option>
                    <option value="4" selected>Studio HD (600 DPI)</option>
                </select>
            </div>
        </div>
    </div>

    <div class="workspace" id="workspaceContainer">
    <div class="sys-zoom-controls">
        <div style="font-size: 10px; font-weight: bold; color: #64748b; text-align: center; margin-bottom: 2px;">ZOOM</div>
        <button type="button" class="sys-zoom-btn" onclick="sysChangeZoom(0.1)" title="Zoom In"><i class="fas fa-plus"></i></button>
        <button type="button" class="sys-zoom-btn" onclick="sysResetZoom()" style="font-size: 11px;">100%</button>
        <button type="button" class="sys-zoom-btn" onclick="sysChangeZoom(-0.1)" title="Zoom Out"><i class="fas fa-minus"></i></button>
    </div>
        <div id="loadingOverlay">
            <div class="spinner"></div>
            <div id="loadingText" style="margin-top: 10px; line-height: 1.5;">Processing Image...</div>
        </div>
        <canvas id="mainCanvas"></canvas>
    </div>
</div>

<script>
    const userRole = "<?= $_SESSION['user_role'] ?? 'guest' ?>";
    const cardCost = <?= number_format($card_cost, 2, '.', '') ?>;
    const currency = "<?= $currency ?>";
    const baseUrl = "<?= BASE_URL ?>"; 
    const APP_URL = "<?= APP_URL ?>";

    let canvas;
    function saveDraft() {
        if (!canvas) return;
        const btn = event.target;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        btn.disabled = true;

        const json = JSON.stringify(canvas.toJSON(['customId', 'customBgEnable', 'customBgColor', 'customBgShape', 'customPadding', 'fontSize', 'fontFamily', 'fill', 'opacity', 'fontWeight', 'fontStyle', 'textAlign', 'stroke', 'strokeWidth']));
        const formData = new FormData();
        formData.append('service_slug', 'passport_photo');
        formData.append('service_name', 'Passport Photo Maker');
        formData.append('json', json);

        fetch(APP_URL + 'save_digital_draft.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('✅ Draft saved successfully! Find it in "Saved Drafts" in the sidebar.');
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
    let originalImageObj = null;
    let croppedImageObj = null;
    let originalDataUrlForAI = null; 
    let cropBox = null;
    
    // Name Date Draggable Box
    let textGroupObj = null;
    let finalHDCardDataUrl = null;

    const DPI = 300;
    const MM_TO_PX = DPI / 25.4;
    const INCH_TO_PX = DPI;

    let currentPassWidth = 35 * MM_TO_PX;  
    let currentPassHeight = 45 * MM_TO_PX; 
    let currentRotation = 0;
    
    window.onload = function() {
        canvas = new fabric.Canvas('mainCanvas', {
            width: document.getElementById('workspaceContainer').clientWidth - 40,
            height: document.getElementById('workspaceContainer').clientHeight - 40,
            selection: false,
            backgroundColor: '#e2e8f0'
        });

        // Load Draft Logic
        const draftJson = <?= $loaded_draft_json ? $loaded_draft_json : 'null' ?>;
        if (draftJson) {
            showLoading(true, "Restoring Draft...");
            try {
                canvas.loadFromJSON(draftJson, function() {
                    if (draftJson.background) {
                        canvas.backgroundColor = draftJson.background;
                    }
                    canvas.renderAll();
                    showLoading(false);
                    // Also check if we should jump to a specific step
                    if (canvas.getObjects().length > 0) {
                        currentStep = 2;
                        document.getElementById('step1Controls').style.display = 'none';
                        document.getElementById('step2Controls').style.display = 'block';
                        document.getElementById('downloadBlock').style.display = 'block';
                    }
                });
            } catch (err) {
                console.error("Passport Maker Draft Load Error:", err);
                showLoading(false);
                alert("❌ Failed to restore draft.");
            }
        }

        cropBox = new fabric.Rect({
            fill: 'transparent', stroke: '#ef4444', strokeWidth: 3, strokeDashArray: [5, 5],
            cornerColor: '#ef4444', transparentCorners: false, lockUniScaling: true, hasRotatingPoint: false,
            visible: false, cornerSize: 12
        });
        cropBox.setControlsVisibility({ mt: false, mb: false, ml: false, mr: false });
        canvas.add(cropBox);

        // Responsive resize
        window.addEventListener('resize', function() {
            const container = document.getElementById('workspaceContainer');
            if (canvas && container) {
                canvas.setWidth(container.clientWidth - 40);
                canvas.setHeight(container.clientHeight - 40);
                canvas.calcOffset();
            }
        });

        // Mouse Wheel Zoom
        canvas.on('mouse:wheel', function(opt) {
            var delta = opt.e.deltaY;
            var zoom = canvas.getZoom();
            zoom *= 0.999 ** delta;
            if (zoom > 10) zoom = 10;
            if (zoom < 0.1) zoom = 0.1;
            canvas.zoomToPoint({ x: opt.e.offsetX, y: opt.e.offsetY }, zoom);
            sysCurrentZoom = zoom;
            opt.e.preventDefault();
            opt.e.stopPropagation();
        });
    };

    // ==========================================
    // 🚀 STEP BACKWARD FUNCTIONS 🚀
    // ==========================================
    function goToStep1() {
        document.getElementById('step2Controls').style.display = 'none';
        document.getElementById('step1Controls').style.display = 'block';
        document.getElementById('downloadBlock').style.display = 'none';
        
        canvas.clear();
        canvas.backgroundColor = '#e2e8f0';
        if (originalImageObj) {
            canvas.add(originalImageObj);
            canvas.sendToBack(originalImageObj);
        }
        if (cropBox) {
            cropBox.set('visible', true);
            canvas.add(cropBox);
            canvas.bringToFront(cropBox);
            canvas.setActiveObject(cropBox);
        }
        canvas.renderAll();
    }

    function goToStep2() {
        document.getElementById('step3Controls').style.display = 'none';
        document.getElementById('step2Controls').style.display = 'block';
        document.getElementById('downloadBlock').style.display = 'none';
        
        canvas.clear();
        canvas.backgroundColor = '#e2e8f0';
        
        if (croppedImageObj) {
            canvas.add(croppedImageObj);
            changeBackground(); 
        }
        if (textGroupObj && document.getElementById('showNameDate').checked) {
            canvas.add(textGroupObj);
            canvas.bringToFront(textGroupObj);
        }
        canvas.renderAll();
    }

    // ==========================================
    // STEP 1: LOAD, ROTATE & CROP
    // ==========================================
    function loadImage(event) {
        const file = event.target.files[0];
        if (!file) return;

        showLoading(true, "Loading photo...");
        const reader = new FileReader();
        reader.onload = function(e) {
            fabric.Image.fromURL(e.target.result, function(img) {
                if (originalImageObj) canvas.remove(originalImageObj);
                if (textGroupObj) { canvas.remove(textGroupObj); textGroupObj = null; }
                
                originalImageObj = img;
                currentRotation = 0;
                document.getElementById('showNameDate').checked = false;
                document.getElementById('nameDateInputs').style.display = 'none';
                
                const scale = Math.min((canvas.width - 100) / img.width, (canvas.height - 100) / img.height);
                img.set({ originX: 'center', originY: 'center', left: canvas.width / 2, top: canvas.height / 2, scaleX: scale, scaleY: scale, selectable: false, evented: false });

                canvas.add(img); canvas.sendToBack(img);
                
                setupCropBox();
                document.getElementById('step1Controls').style.display = 'block';
                document.getElementById('step2Controls').style.display = 'none';
                document.getElementById('step3Controls').style.display = 'none';
                document.getElementById('downloadBlock').style.display = 'none';
                
                showLoading(false);
            });
        };
        reader.readAsDataURL(file);
    }

    function rotateImage(angle) {
        if (!originalImageObj) return;
        currentRotation += angle;
        originalImageObj.set({ angle: currentRotation });
        
        const scaleX = (canvas.width - 100) / originalImageObj.width;
        const scaleY = (canvas.height - 100) / originalImageObj.height;
        const scale = Math.min(scaleX, scaleY);
        
        if (currentRotation % 180 !== 0) {
            const scaleSwapped = Math.min((canvas.width - 100) / originalImageObj.height, (canvas.height - 100) / originalImageObj.width);
            originalImageObj.set({ scaleX: scaleSwapped, scaleY: scaleSwapped });
        } else {
            originalImageObj.set({ scaleX: scale, scaleY: scale });
        }
        canvas.renderAll();
    }

    function setupCropBox() {
        if (!originalImageObj) return;
        const sizeType = document.getElementById('passportSize').value;
        
        let ratio = 35 / 45; 
        if (sizeType === 'india' || sizeType === 'uk') { ratio = 35 / 45; currentPassWidth = 35 * MM_TO_PX; currentPassHeight = 45 * MM_TO_PX; }
        else if (sizeType === 'us') { ratio = 1.0; currentPassWidth = 2 * INCH_TO_PX; currentPassHeight = 2 * INCH_TO_PX; }
        else if (sizeType === 'stamp') { ratio = 20 / 25; currentPassWidth = 20 * MM_TO_PX; currentPassHeight = 25 * MM_TO_PX; }

        const boxWidth = 200;
        const boxHeight = boxWidth / ratio;

        cropBox.set({ width: boxWidth, height: boxHeight, left: canvas.width/2 - boxWidth/2, top: canvas.height/2 - boxHeight/2, scaleX: 1, scaleY: 1, visible: true });
        
        canvas.bringToFront(cropBox); canvas.setActiveObject(cropBox); canvas.renderAll();
    }

    function cropAndProceed() {
        if(!originalImageObj || !cropBox.visible) return;
        showLoading(true, "Cropping process is in progress...");

        setTimeout(() => {
            cropBox.set('visible', false); 
            canvas.renderAll();

            const cropRect = cropBox.getBoundingRect();
            originalDataUrlForAI = canvas.toDataURL({
                format: 'png', left: cropRect.left, top: cropRect.top,
                width: cropRect.width, height: cropRect.height, multiplier: 4 // HD Extraction
            });

            canvas.clear();
            document.getElementById('step1Controls').style.display = 'none';
            document.getElementById('step2Controls').style.display = 'block';

            fabric.Image.fromURL(originalDataUrlForAI, function(img) {
                const dispHeight = 350;
                const scaleDisplay = dispHeight / img.height;
                
                img.set({
                    originX: 'center', originY: 'center',
                    left: canvas.width / 2, top: canvas.height / 2,
                    scaleX: scaleDisplay, scaleY: scaleDisplay,
                    selectable: false, evented: false
                });
                
                croppedImageObj = img;
                
                // Pure Smooth Filters
                croppedImageObj.filters = [
                    new fabric.Image.filters.Brightness({ brightness: 0 }),
                    new fabric.Image.filters.Contrast({ contrast: 0 }),
                    new fabric.Image.filters.Saturation({ saturation: 0 })
                ];

                canvas.add(img);
                changeBackground(); 
                canvas.renderAll();
                showLoading(false);
            });
        }, 100);
    }

    // ==========================================
    // 🚀 STEP 2: NATIVE BROWSER AI (IMG.LY) 🚀
    // ==========================================
    async function removeBgNativeAI() {
        if(!croppedImageObj || !originalDataUrlForAI) return;

        showLoading(true, "AI is processing the background...<br><small style='color:#10b981;'>Please wait, this may take a few seconds.</small>");

        let bgRemovedDataUrl = null;

        try {
            const res = await fetch(originalDataUrlForAI);
            const blob = await res.blob();
            
            // Call imgly background removal locally
            const resultBlob = await imglyRemoveBackground(blob);
            bgRemovedDataUrl = URL.createObjectURL(resultBlob);
        } catch (error) {
            console.warn("img.ly native AI failed, falling back to Remove.BG API...");
        }

        if (!bgRemovedDataUrl) {
            try {
                let base64Data = originalDataUrlForAI;
                let reqBody = base64Data.startsWith('data:image') 
                    ? JSON.stringify({ image_file_b64: base64Data.split(',')[1], size: "auto" })
                    : JSON.stringify({ image_url: base64Data, size: "auto" });

                const response = await fetch('https://api.remove.bg/v1.0/removebg', {
                    method: 'POST',
                    headers: { 'X-Api-Key': 'pSqcQaSbGwN4an41dkZSyHAs', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: reqBody
                });

                if(!response.ok) throw new Error("API Limit Reached or Network Failed");
                
                const responseData = await response.json();
                bgRemovedDataUrl = 'data:image/png;base64,' + responseData.data.result_b64;
            } catch(apiErr) {
                console.error(apiErr);
                alert("❌ Both Edge AI and Cloud AI failed. Please try a different browser or image.");
                showLoading(false);
                return;
            }
        }

        fabric.Image.fromURL(bgRemovedDataUrl, function(newImg) {
            newImg.set({
                originX: 'center', originY: 'center',
                left: croppedImageObj.left, top: croppedImageObj.top,
                scaleX: croppedImageObj.scaleX, scaleY: croppedImageObj.scaleY,
                selectable: false, evented: false
            });

            newImg.filters = croppedImageObj.filters;
            newImg.applyFilters();

            canvas.remove(croppedImageObj);
            croppedImageObj = newImg;
            canvas.add(croppedImageObj);
            
            changeBackground(); 
            showLoading(false);
        });
    }




    function changeBackground() {
        if(!croppedImageObj) return;
        croppedImageObj.set('backgroundColor', document.getElementById('bgColor').value);
        canvas.renderAll();
    }

    canvas.on('mouse:down', function(o) {
        if(isMagicCloneMode) {
            if(sourceBox) return; // Only 1 box at a time
            let pointer = canvas.getPointer(o.e);
            cloneStartX = pointer.x; 
            cloneStartY = pointer.y;

            targetBox = new fabric.Rect({
                left: cloneStartX, top: cloneStartY, width: 0, height: 0,
                fill: 'transparent', stroke: '#ef4444', strokeWidth: 2, strokeDashArray: [5, 5],
                selectable: false, evented: false
            });
            canvas.add(targetBox);
            return;
        }

        if(currentStep !== 1) return;
        if(!cropBox) return; // Changed from cropRect to cropBox
        isDragging = true;
        let pointer = canvas.getPointer(o.e);
        startX = pointer.x;
        startY = pointer.y;
    });

    canvas.on('mouse:move', function(o) {
        if(isMagicCloneMode) {
            if(!targetBox || sourceBox) return;
            let pointer = canvas.getPointer(o.e);
            let minX = Math.min(cloneStartX, pointer.x);
            let minY = Math.min(cloneStartY, pointer.y);
            let w = Math.abs(cloneStartX - pointer.x);
            let h = Math.abs(cloneStartY - pointer.y);
            targetBox.set({ left: minX, top: minY, width: w, height: h });
            canvas.renderAll();
            return;
        }

        if(currentStep !== 1 || !isDragging || !cropBox) return; // Changed from cropRect to cropBox
        let pointer = canvas.getPointer(o.e);
        let dx = pointer.x - startX;
        let dy = pointer.y - startY;
        cropBox.set({ left: cropBox.left + dx, top: cropBox.top + dy }); // Changed from cropRect to cropBox
        startX = pointer.x;
        startY = pointer.y;
        canvas.renderAll();
    });

    canvas.on('mouse:up', function(o) {
        if(isMagicCloneMode) {
            if(!targetBox || sourceBox) return;
            if(targetBox.width < 10 || targetBox.height < 10) {
                canvas.remove(targetBox); targetBox = null; return;
            }
            // CREATE SOURCE BOX
            sourceBox = new fabric.Rect({
                left: targetBox.left, top: targetBox.top - targetBox.height - 10,
                width: targetBox.width, height: targetBox.height,
                fill: 'rgba(16, 185, 129, 0.2)', stroke: '#10b981', strokeWidth: 2,
                borderColor: '#10b981', cornerColor: '#10b981',
                hasRotatingPoint: false, lockScalingX: true, lockScalingY: true,
                selectable: true, evented: true
            });
            canvas.add(sourceBox);
            canvas.setActiveObject(sourceBox);
            sourceBox.on('moving', updateClonePatch);
            updateClonePatch();
            return;
        }

        isDragging = false;
    });

    // ==========================================
    // 11. MAGIC OBJECT ERASER (Smart Clone)
    // ==========================================
    let isMagicCloneMode = false;
    let targetBox = null;
    let sourceBox = null;
    let cloneStartX, cloneStartY;

    function toggleMagicClone() {
        if(currentStep !== 2) { alert("Please crop of the photo first."); return; }
        resetAllModes();
        isMagicCloneMode = true;
        document.getElementById('btnMagicClone').style.display = 'none';
        document.getElementById('cloneTools').style.display = 'block';
        canvas.selection = false; 
        canvas.defaultCursor = 'crosshair';
    }

    function resetAllModes() {
        isMagicCloneMode = false;
        canvas.selection = true;
        canvas.defaultCursor = 'default';
        document.getElementById('btnMagicClone').style.display = 'block';
        document.getElementById('cloneTools').style.display = 'none';
        
        if(sourceBox) { canvas.remove(sourceBox); sourceBox = null; }
        if(targetBox && targetBox.fill === 'transparent') { canvas.remove(targetBox); targetBox = null; }
        canvas.renderAll();
    }

    function updateClonePatch() {
        if(!targetBox || !sourceBox) return;
        targetBox.visible = false; sourceBox.visible = false;
        canvas.renderAll(); 
        let cropDataUrl = canvas.toDataURL({
            format: 'jpeg', quality: 1.0,
            left: sourceBox.left, top: sourceBox.top,
            width: sourceBox.width, height: sourceBox.height, multiplier: 1
        });
        targetBox.visible = true; sourceBox.visible = true;
        fabric.Image.fromURL(cropDataUrl, function(img) {
            let pattern = new fabric.Pattern({ source: img.getElement(), repeat: 'no-repeat' });
            targetBox.set('fill', pattern);
            targetBox.set('strokeWidth', 0);
            canvas.renderAll();
        });
    }

    function applyClonePatch() {
        if(!targetBox || !sourceBox) return;
        canvas.remove(sourceBox);
        targetBox.set({ selectable: false, evented: false });
        targetBox = null; sourceBox = null; 
        resetAllModes();
    }

    // ==========================================
    // 12. DRAFT & SAVING
    // ==========================================
    function saveDraft() {
        const json = JSON.stringify(canvas.toJSON());
        fetch('app/save_digital_draft.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `service_slug=passport_photo&service_name=Passport Photo Studio&json=${encodeURIComponent(json)}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) alert('Success! Your draft has been saved. You can continue later.');
            else alert('Error: ' + data.error);
        });
    }

    function setBg(color) {
        document.getElementById('bgColor').value = color;
        changeBackground();
    }

    // ==========================================
    // 🚀 STEP 2: SMOOTH COLOR FILTERS (NO BLACK SPOTS) 🚀
    // ==========================================
    function applyFilters() {
        if(!croppedImageObj) return;
        
        croppedImageObj.filters[0].brightness = parseFloat(document.getElementById('valBrightness').value);
        croppedImageObj.filters[1].contrast = parseFloat(document.getElementById('valContrast').value);
        croppedImageObj.filters[2].saturation = parseFloat(document.getElementById('valSaturation').value);
        
        croppedImageObj.applyFilters();
        canvas.renderAll();
    }

    function autoBeauty() {
        document.getElementById('valBrightness').value = 0.05;
        document.getElementById('valContrast').value = 0.05;
        document.getElementById('valSaturation').value = 0.15; 
        applyFilters();
    }

    function resetFilters() {
        document.getElementById('valBrightness').value = 0;
        document.getElementById('valContrast').value = 0;
        document.getElementById('valSaturation').value = 0;
        applyFilters();
    }

    // ==========================================
    // 🚀 STEP 2: DRAG & DROP NAME AND DATE (WITH BG) 🚀
    // ==========================================
    function toggleNameDateText() {
        const isChecked = document.getElementById('showNameDate').checked;
        document.getElementById('nameDateInputs').style.display = isChecked ? 'block' : 'none';
        
        if (isChecked) {
            if (!textGroupObj && croppedImageObj) {
                createDraggableTextBox();
            } else if (textGroupObj) {
                textGroupObj.set('visible', true);
                canvas.renderAll();
            }
        } else {
            if (textGroupObj) {
                textGroupObj.set('visible', false);
                canvas.discardActiveObject();
                canvas.renderAll();
            }
        }
    }

    function createDraggableTextBox() {
        if(!croppedImageObj) return;

        const bounds = croppedImageObj.getBoundingRect();
        const boxWidth = bounds.width;
        const boxHeight = bounds.height * 0.18; // 18% of photo height

        const bgRect = new fabric.Rect({
            width: boxWidth, height: boxHeight, fill: document.getElementById('textBgColor').value,
            originX: 'center', originY: 'center', left: 0, top: 0
        });

        const nameTxt = new fabric.Text(document.getElementById('passName').value || 'NAME', {
            fontSize: boxHeight * 0.40, fontFamily: 'Arial', fontWeight: 'bold', fill: '#000000',
            originX: 'center', originY: 'center', left: 0, top: -(boxHeight * 0.15)
        });

        const dateTxt = new fabric.Text(document.getElementById('passDate').value || 'DD/MM/YYYY', {
            fontSize: boxHeight * 0.35, fontFamily: 'Arial', fill: '#000000',
            originX: 'center', originY: 'center', left: 0, top: (boxHeight * 0.25)
        });

        textGroupObj = new fabric.Group([bgRect, nameTxt, dateTxt], {
            left: bounds.left + bounds.width/2,
            top: bounds.top + bounds.height - boxHeight/2, // Default at bottom of photo
            originX: 'center', originY: 'center',
            selectable: true, evented: true, hasControls: true, hasBorders: true,
            lockScalingFlip: true, borderColor: '#10b981', cornerColor: '#10b981'
        });

        textGroupObj.setControlsVisibility({ mt: false, mb: false, ml: false, mr: false, mtr: false });

        canvas.add(textGroupObj);
        canvas.setActiveObject(textGroupObj);
        canvas.renderAll();
    }

    function updateTextContent() {
        if (!textGroupObj) return;

        const bgRect = textGroupObj.item(0);
        const nameTxt = textGroupObj.item(1);
        const dateTxt = textGroupObj.item(2);

        bgRect.set('fill', document.getElementById('textBgColor').value);
        nameTxt.set('text', document.getElementById('passName').value || 'NAME');
        dateTxt.set('text', document.getElementById('passDate').value || 'DD/MM/YYYY');

        textGroupObj.addWithUpdate(); 
        canvas.renderAll();
    }

    // ==========================================
    // 🚀 STEP 3: AUTO GRID GENERATOR (WITH BLACK BORDERS) 🚀
    // ==========================================
    function proceedToGrid() {
        if(!croppedImageObj) return;
        showLoading(true, "Preparing printing paper...");

        // Deselect text box so selection boundaries don't show up in print
        canvas.discardActiveObject();
        canvas.renderAll();

        setTimeout(() => {
            const bounds = croppedImageObj.getBoundingRect();
            
            finalHDCardDataUrl = canvas.toDataURL({
                format: 'jpeg', quality: 1.0,
                left: bounds.left, top: bounds.top,
                width: bounds.width, height: bounds.height,
                multiplier: 4 // HD Extractor
            });

            document.getElementById('step2Controls').style.display = 'none';
            document.getElementById('step3Controls').style.display = 'block';
            document.getElementById('downloadBlock').style.display = 'block';
            
            generateGrid();
            showLoading(false);
        }, 100);
    }

    function generateGrid() {
        if(!finalHDCardDataUrl) return;

        const paperType = document.getElementById('paperSize').value;
        const gap = parseInt(document.getElementById('photoGap').value);
        
        let paperW, paperH;
        if(paperType === '4x6') { paperW = 6 * INCH_TO_PX; paperH = 4 * INCH_TO_PX; } 
        else if(paperType === '5x7') { paperW = 7 * INCH_TO_PX; paperH = 5 * INCH_TO_PX; }
        else if(paperType === '8x10') { paperW = 10 * INCH_TO_PX; paperH = 8 * INCH_TO_PX; }
        else if(paperType === 'A4') { paperW = 210 * MM_TO_PX; paperH = 297 * MM_TO_PX; }
        else if(paperType === 'A3') { paperW = 297 * MM_TO_PX; paperH = 420 * MM_TO_PX; }

        const ratio = paperW / paperH;
        const dispHeight = document.getElementById('workspaceContainer').clientHeight - 40;
        const dispWidth = dispHeight * ratio;
        
        canvas.setDimensions({ width: dispWidth, height: dispHeight });
        canvas.clear();
        canvas.backgroundColor = '#ffffff'; 

        fabric.Image.fromURL(finalHDCardDataUrl, function(hdImg) {
            
            const displayScale = dispWidth / paperW; 
            const finalImgWidthDisp = currentPassWidth * displayScale;
            
            let ratioH = hdImg.height / hdImg.width;
            const finalImgHeightDisp = finalImgWidthDisp * ratioH;
            
            const gapDisp = gap * displayScale;

            const cols = Math.floor((dispWidth + gapDisp) / (finalImgWidthDisp + gapDisp));
            const rows = Math.floor((dispHeight + gapDisp) / (finalImgHeightDisp + gapDisp));
            
            const totalContentW = (cols * finalImgWidthDisp) + ((cols - 1) * gapDisp);
            const totalContentH = (rows * finalImgHeightDisp) + ((rows - 1) * gapDisp);

            const startX = (dispWidth - totalContentW) / 2;
            const startY = (dispHeight - totalContentH) / 2;

            for(let r = 0; r < rows; r++) {
                for(let c = 0; c < cols; c++) {
                    
                    const leftPos = startX + c * (finalImgWidthDisp + gapDisp);
                    const topPos = startY + r * (finalImgHeightDisp + gapDisp);

                    hdImg.clone(function(clone) {
                        clone.set({
                            left: leftPos, top: topPos,
                            scaleX: (finalImgWidthDisp / clone.width),
                            scaleY: (finalImgHeightDisp / clone.height),
                            selectable: false
                        });
                        canvas.add(clone);

                        // Black Border around each photo
                        const borderRect = new fabric.Rect({
                            left: leftPos, top: topPos,
                            width: finalImgWidthDisp, height: finalImgHeightDisp,
                            fill: 'transparent', stroke: '#000000', strokeWidth: 2, 
                            selectable: false, evented: false
                        });
                        canvas.add(borderRect);
                    });
                }
            }
            setTimeout(() => { canvas.renderAll(); }, 200);
        });
    }

    // ==========================================
    // 🚀 EXPORT & API DEDUCTION 🚀
    // ==========================================
    async function handleExport() {
        if (userRole !== 'admin') {
            let confirmMsg = `${currency}${cardCost} will be deducted from the wallet to download the final print sheet.\nDo you want to proceed?`;
            if (!confirm(confirmMsg)) return; 
            
            try {
                let formData = new FormData();
                formData.append('service_type', 'Passport Photo Maker (AI Pro)');

                let response = await fetch(APP_URL + 'deduct_poster_balance.php', { method: 'POST', body: formData });
                let text = await response.text(); 
                
                try {
                    let result = JSON.parse(text);
                    if (result.success) {
                        processHighResDownload();
                        alert(` ✅ Downloaded!\nNew balance: ${currency}${result.remaining_balance}`);
                    } else { alert("❌ Error: " + result.message); }
                } catch (jsonError) { alert("❌ Server error."); }
            } catch (error) { alert("❌ Network error."); }
        } else {
            let formData = new FormData();
            formData.append('service_type', 'Passport Photo Maker (Admin)');
            fetch(APP_URL + 'deduct_poster_balance.php', { method: 'POST', body: formData });
            processHighResDownload(); 
        }
    }

    function processHighResDownload() {
        const paperType = document.getElementById('paperSize').value;
        let paperW, paperH;
        if(paperType === '4x6') { paperW = 6 * INCH_TO_PX; paperH = 4 * INCH_TO_PX; } 
        else if(paperType === '5x7') { paperW = 7 * INCH_TO_PX; paperH = 5 * INCH_TO_PX; }
        else if(paperType === '8x10') { paperW = 10 * INCH_TO_PX; paperH = 8 * INCH_TO_PX; }
        else if(paperType === 'A4') { paperW = 210 * MM_TO_PX; paperH = 297 * MM_TO_PX; }
        else if(paperType === 'A3') { paperW = 297 * MM_TO_PX; paperH = 420 * MM_TO_PX; }

        let dpiMultiplierSetting = parseFloat(document.getElementById('exportDPI').value || 2);
        // Calculate the multiplier needed to render the canvas at the desired print DPI
        // The canvas is currently scaled to fit the display, so we need to scale it up to the actual print size.
        // The current canvas width (canvas.width) corresponds to dispWidth, which is paperW * displayScale.
        // We want the output image to have dimensions (paperW * exportDPI) x (paperH * exportDPI).
        // The multiplier for toDataURL should be (target_pixel_width / current_canvas_width).
        // target_pixel_width = paperW * dpiMultiplierSetting (since dpiMultiplierSetting is relative to 150 DPI, and our INCH_TO_PX is 300 DPI, we need to adjust)
        // Let's assume dpiMultiplierSetting is directly the multiplier for the base 150 DPI.
        // If exportDPI is 1 (150 DPI), multiplier should be 150/300 = 0.5 relative to our INCH_TO_PX.
        // If exportDPI is 2 (300 DPI), multiplier should be 300/300 = 1 relative to our INCH_TO_PX.
        // If exportDPI is 4 (600 DPI), multiplier should be 600/300 = 2 relative to our INCH_TO_PX.
        // So, the multiplier for toDataURL should be (dpiMultiplierSetting * 150) / DPI_USED_FOR_MM_TO_PX_AND_INCH_TO_PX (which is 300).
        // This simplifies to dpiMultiplierSetting / 2.
        const finalMultiplier = (paperW / canvas.width) * (dpiMultiplierSetting / 2);
        
        let actualDpi = 300;
        if(dpiMultiplierSetting == 1) actualDpi = 150;
        if(dpiMultiplierSetting == 4) actualDpi = 600;

        let dataUrl = canvas.toDataURL({
            format: 'jpeg',
            quality: 1.0,
            multiplier: finalMultiplier
        });

        // Patch DPI in base64
        function changeDpiDataUrl(base64Image, dpi) {
            let dataArray = base64Image.split(',');
            let decoded = atob(dataArray[1]);
            let len = decoded.length;
            let view = new Uint8Array(len);
            for (let i = 0; i < len; i++) {
                view[i] = decoded.charCodeAt(i);
            }
            view[13] = 1;
            view[14] = Math.floor(dpi / 256);
            view[15] = dpi % 256;
            view[16] = Math.floor(dpi / 256);
            view[17] = dpi % 256;
            let base64 = btoa(String.fromCharCode.apply(null, view));
            return dataArray[0] + ',' + base64;
        }
        
        dataUrl = changeDpiDataUrl(dataUrl, actualDpi);
        
        const link = document.createElement('a');
        link.download = `Passport_Print_${paperType}.jpg`;
        link.href = dataUrl;
        link.click();
    }

    function handlePrint() {
        const dataUrl = canvas.toDataURL({ format: 'jpeg', quality: 1.0 });
        const printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>Print Passport</title><style>@page { margin: 0; size: auto; } body { margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #fff; } img { max-width: 100%; max-height: 100vh; padding: 10px; }</style></head><body><img src="' + dataUrl + '" onload="window.print(); window.close();" /></body></html>');
        printWindow.document.close();
    }

    function toggleMagicClone() {
        if(currentStep !== 2) return;
        isMagicCloneMode = !isMagicCloneMode;
        
        const btn = document.getElementById('btnMagicClone');
        const tools = document.getElementById('cloneTools');
        
        if (isMagicCloneMode) {
            btn.innerHTML = '<i class="fas fa-times"></i> Cancel Object Eraser';
            btn.style.background = '#ef4444';
            tools.style.display = 'block';
            canvas.defaultCursor = 'crosshair';
            canvas.discardActiveObject();
        } else {
            resetAllModes();
        }
        canvas.renderAll();
    }

    function resetAllModes() {
        isMagicCloneMode = false;
        
        const btn = document.getElementById('btnMagicClone');
        if(btn) {
            btn.innerHTML = '<i class="fas fa-eraser"></i> Object Eraser (Remove Sign/Stamp)';
            btn.style.background = 'linear-gradient(135deg, #8b5cf6, #6366f1)';
        }
        
        const tools = document.getElementById('cloneTools');
        if(tools) tools.style.display = 'none';

        if(targetBox) { canvas.remove(targetBox); targetBox = null; }
        if(sourceBox) { canvas.remove(sourceBox); sourceBox = null; }
        
        canvas.defaultCursor = 'default';
        canvas.renderAll();
    }

    function applyClonePatch() {
        if(!targetBox || !sourceBox) { alert('Please select red target box and green source box.'); return; }
        
        showLoading(true, "Applying Clean Patch...");
        setTimeout(() => {
            let sUrl = canvas.toDataURL({
                format: 'png',
                left: sourceBox.left, top: sourceBox.top, width: sourceBox.width, height: sourceBox.height,
                multiplier: 1
            });
            
            fabric.Image.fromURL(sUrl, function(patchImg) {
                // Feather the edges of the patch slightly via CSS or Fabric blur
                let blurFilter = new fabric.Image.filters.Blur({ blur: 0.1 });
                patchImg.filters.push(blurFilter);
                patchImg.applyFilters();
                
                patchImg.set({
                    left: targetBox.left, top: targetBox.top, width: targetBox.width, height: targetBox.height,
                    scaleX: 1, scaleY: 1, selectable: false, evented: false
                });
                
                canvas.add(patchImg); canvas.sendBackwards(patchImg);
                
                resetAllModes(); showLoading(false);
            });
            
        }, 300);
    }

    function showLoading(show, text = "Processing...") {
        document.getElementById('loadingText').innerHTML = text;
        document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
    }
</script>
<script>
    let sysCurrentZoom = 1.0;
    function sysChangeZoom(amount) {
        if(!canvas) return;
        sysCurrentZoom += amount;
        if(sysCurrentZoom < 0.1) sysCurrentZoom = 0.1;
        if(sysCurrentZoom > 10) sysCurrentZoom = 10;
        
        let center = canvas.getVpCenter();
        canvas.zoomToPoint({ x: center.x, y: center.y }, sysCurrentZoom);
    }
    function sysResetZoom() {
        if(!canvas) return;
        sysCurrentZoom = 1.0;
        canvas.setViewportTransform([1, 0, 0, 1, 0, 0]);
        canvas.zoomToPoint({ x: canvas.width/2, y: canvas.height/2 }, 1);
        canvas.renderAll();
    }
</script>
</body>
</html>