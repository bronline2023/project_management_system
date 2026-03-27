<?php
require_once CORE_INCLUDES_PATH . 'service_paywall.php';
enforce_service_paywall('photo_studio');

/**

/**
 * views/photo_studio.php
 * THE ULTIMATE MASTER STUDIO: Fixed White Box Bug in Object Remover, Perfect Crop, All Tools
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
    <script src="https://cdn.img.ly/packages/@imgly/background-removal@1.5.5/dist/index.js"></script>
</head>
<body>

<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Vadodara:wght@400;700&family=Mukta+Vaani:wght@400;700&family=Noto+Sans+Gujarati:wght@400;700&family=Rasa:wght@400;700&display=swap" rel="stylesheet">

<style>
    /* =========================================
       1. GLOBAL UI & LAYOUT
       ========================================= */
    #sidebar { display: none !important; }
    .navbar { display: none !important; }
    #content { padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    .wrapper { margin: 0 !important; padding: 0 !important; }
    
    body { 
        background-color: #0f172a; 
        overflow: auto; 
        margin: 0; 
        padding: 0; 
        font-family: 'Segoe UI', Tahoma, sans-serif; 
        color: #f8fafc;
    }
    
    .studio-wrapper { 
        display: flex; 
        flex-direction: column; 
        min-height: calc(100vh - 65px); height: calc(100vh - 65px); 
        width: 100vw; 
        background-color: #0f172a; 
    }
    
    /* =========================================
       2. HEADER NAVIGATION
       ========================================= */
    .studio-header { 
        height: 65px; 
        background: #1e293b; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 0 25px; 
        border-bottom: 1px solid #334155; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.4); 
        z-index: 100;
    }
    
    .header-title { 
        font-size: 20px; 
        font-weight: bold; 
        color: #38bdf8; 
        display: flex; 
        align-items: center; 
        gap: 12px;
    }
    
    .btn-back { 
        background: #ef4444; 
        color: white; 
        border: none; 
        padding: 8px 18px; 
        border-radius: 8px; 
        font-weight: bold; 
        text-decoration: none; 
        transition: 0.3s;
    }
    .btn-back:hover { background: #dc2626; box-shadow: 0 0 10px rgba(239, 68, 68, 0.5); }
    
    .top-actions { display: flex; gap: 15px; align-items: center; }
    
    .balance-badge {
        font-size: 14px; 
        font-weight: bold; 
        background: #334155; 
        padding: 8px 18px; 
        border-radius: 20px; 
        color: #cbd5e1;
        border: 1px solid #475569;
    }
    
    .btn-download { 
        background: linear-gradient(135deg, #10b981, #059669); 
        color: white; 
        border: none; 
        padding: 10px 25px; 
        border-radius: 8px; 
        font-weight: bold; 
        cursor: pointer; 
        transition: 0.3s;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 15px;
    }
    .btn-download:hover { 
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(16, 185, 129, 0.4);
    }

    /* =========================================
       3. MAIN BODY & SIDEBARS
       ========================================= */
    .studio-body { 
        background-color: #0f172a; 
        display: flex;
        flex-grow: 1;
        overflow: hidden;
        margin: 0; 
        padding: 0; 
        font-family: 'Segoe UI', Tahoma, sans-serif; 
        color: #f8fafc;
    }
    
    /* Left Sidebar (Tools) */
    .tools-sidebar { 
        width: 100px; 
        background: #1e293b; 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        padding-top: 20px; 
        border-right: 1px solid #334155; 
        z-index: 50; 
        overflow-y: auto;
    }
    
    .tool-icon-btn { 
        width: 80px; 
        height: 70px; 
        border-radius: 12px; 
        display: flex; 
        flex-direction: column; 
        justify-content: center; 
        align-items: center; 
        color: #94a3b8; 
        background: transparent; 
        border: 2px solid transparent; 
        cursor: pointer; 
        margin-bottom: 10px; 
        transition: all 0.3s ease; 
        font-size: 12px;
        padding: 5px;
    }
    
    .tool-icon-btn i { font-size: 24px; margin-bottom: 6px; }
    .tool-icon-btn:hover, .tool-icon-btn.active { 
        background: #0f172a; 
        color: #38bdf8; 
        font-weight: bold; 
        border-color: #38bdf8; 
        box-shadow: 0 4px 15px rgba(56, 189, 248, 0.2);
    }
    
    .tool-icon-btn.danger:hover { background: #ef4444; color: white; border-color: #dc2626;}
    .tool-icon-btn.magic { color: #ec4899; }
    .tool-icon-btn.magic:hover, .tool-icon-btn.magic.active { background: #ec4899; color: white; border-color: #db2777;}
    .tool-icon-btn.ai-gold { color: #f59e0b; }
    .tool-icon-btn.ai-gold:hover, .tool-icon-btn.ai-gold.active { background: #f59e0b; color: #0f172a; border-color: #d97706;}
    .tool-icon-btn.collage { color: #10b981; }
    .tool-icon-btn.collage:hover, .tool-icon-btn.collage.active { background: #10b981; color: #0f172a; border-color: #059669;}

    /* =========================================
       4. PROPERTIES PANELS
       ========================================= */
    .props-sidebar { 
        width: 360px; 
        background: #0f172a; 
        border-right: 1px solid #334155; 
        padding: 25px 20px; 
        overflow-y: auto; 
        display: none; 
    }
    
    .props-title { 
        font-size: 18px; 
        font-weight: bold; 
        color: #f8fafc; 
        border-bottom: 2px solid #38bdf8; 
        padding-bottom: 12px; 
        margin-bottom: 25px; 
        display: flex; 
        align-items: center; 
        gap: 12px;
    }
    
    .form-label { font-size: 13px; font-weight: bold; color: #94a3b8; margin-bottom: 8px; display: block; }
    
    .form-control, .form-select { 
        width: 100%; 
        padding: 12px; 
        background: #1e293b; 
        border: 1px solid #475569; 
        color: white; 
        border-radius: 8px; 
        margin-bottom: 20px; 
        outline: none; 
        font-size: 14px;
        transition: 0.3s;
    }
    .form-control:focus, .form-select:focus { border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);}
    
    /* Sliders */
    .slider-container { 
        margin-bottom: 20px; 
        background: #1e293b; 
        padding: 15px; 
        border-radius: 10px; 
        border: 1px solid #334155;
    }
    .slider-container input[type="range"] { width: 100%; cursor: pointer; margin-top: 8px; height: 6px; border-radius: 5px;}
    .slider-val { font-size: 12px; float: right; color: #38bdf8; font-weight: bold; background: #0f172a; padding: 3px 8px; border-radius: 4px;}

    /* Buttons inside panels */
    .action-btn { 
        width: 100%; 
        padding: 14px; 
        background: #334155; 
        border: 1px solid #475569; 
        color: white; 
        border-radius: 8px; 
        cursor: pointer; 
        font-weight: bold; 
        margin-bottom: 15px; 
        transition: all 0.3s ease;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        font-size: 15px;
    }
    .action-btn:hover { background: #475569; border-color: #94a3b8; }
    .action-btn.success-btn { background: #10b981; border-color: #10b981; color: white; }
    .action-btn.success-btn:hover { background: #059669; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);}
    .action-btn.danger { background: #ef4444; border-color: #ef4444; color: white; }
    .action-btn.danger:hover { background: #dc2626; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);}
    
    .action-btn.ai-btn { background: linear-gradient(135deg, #ec4899, #8b5cf6); border: none; box-shadow: 0 4px 15px rgba(139,92,246,0.4); }
    .action-btn.ai-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(139,92,246,0.6); }
    
    .action-btn.gold-btn { background: linear-gradient(135deg, #f59e0b, #ea580c); border: none; box-shadow: 0 4px 15px rgba(245,158,11,0.4); }
    .action-btn.gold-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(245,158,11,0.6);}

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
    
    /* Text Format Buttons */
    .format-btn { width: 50px; height: 50px; border-radius: 8px; border: 1px solid #475569; background: #1e293b; color: #cbd5e1; cursor: pointer; transition: 0.2s; display: flex; justify-content: center; align-items: center; font-size: 18px;}
    .format-btn:hover { background: #334155; border-color: #94a3b8; color: white;}
    .format-btn.active { background: #38bdf8; color: #0f172a; border-color: #38bdf8;}

    /* =========================================
       5. CANVAS WORKSPACE
       ========================================= */
    .workspace { 
        flex-grow: 1; 
        display: flex; 
        justify-content: center; 
        align-items: flex-start; 
        background-color: #1e293b;
        background-image: linear-gradient(#334155 1px, transparent 1px), linear-gradient(90deg, #334155 1px, transparent 1px);
        background-size: 30px 30px; 
        position: relative; 
        overflow: auto; /* Allow scrolling if canvas is huge */
        padding: 40px 40px 40px 40px; padding-top: 40px;
    }
    
    .canvas-container { box-shadow: 0 15px 50px rgba(0,0,0,0.8); border: 1px solid #475569;}

    #fileUploadInput { display: none; }
</style>

<input type="file" id="fileUploadInput" accept="image/*" onchange="addUploadedImage(event)">

<?php $page_title = 'Ultimate Photo Studio Pro'; require_once INCLUDES_PATH.'digital_header.php'; ?>

<div class="studio-wrapper">

    <div class="studio-body">
        <div class="tools-sidebar">
            <button class="tool-icon-btn" style="background:#10b981; color:#fff; border-color:#059669;" onclick="handleExport()" title="Download HD">
                <i class="fas fa-download"></i><span style="font-weight:bold;">Export</span>
            </button>
            <?php if(isset($_SESSION['user_id'])): ?>
            <button class="tool-icon-btn" style="background:#f59e0b; color:#fff; border-color:#d97706;" onclick="saveDraft()" title="Save As Draft">
                <i class="fas fa-save"></i><span style="font-weight:bold;">Draft</span>
            </button>
            <?php endif; ?>
            <button class="tool-icon-btn" onclick="document.getElementById('fileUploadInput').click()" title="Upload Image">
                <i class="fas fa-upload"></i><span>Upload</span>
            </button>
            <button class="tool-icon-btn" onclick="clearCanvas()" title="Clear Canvas">
                <i class="fas fa-trash-alt"></i><span>Clear</span>
            </button>
            <button class="tool-icon-btn ai-gold" onclick="openPanel('panel-ai-pro', event)" title="AI Pro Tools">
                <i class="fas fa-bolt"></i><span>AI Pro</span>
            </button>
            <button class="tool-icon-btn magic" onclick="openPanel('panel-bg-remove', event)" title="AI Background Remover">
                <i class="fas fa-user-astronaut"></i><span>Remove BG</span>
            </button>
            <button class="tool-icon-btn" onclick="openPanel('panel-crop', event)" title="Crop Image">
                <i class="fas fa-crop-alt"></i><span>Crop</span>
            </button>
            <button class="tool-icon-btn" onclick="openPanel('panel-filter', event)" title="Filters & Image Adjustments">
                <i class="fas fa-sliders-h"></i><span>Filters</span>
            </button>
            <button class="tool-icon-btn" onclick="openPanel('panel-shadow', event)" title="Borders & Drop Shadow">
                <i class="fas fa-clone"></i><span>Shadow</span>
            </button>
            <button class="tool-icon-btn" onclick="openPanel('panel-text', event)" title="Add Typography">
                <i class="fas fa-font"></i><span>Text</span>
            </button>
            <button class="tool-icon-btn" onclick="openPanel('panel-draw', event)" title="Freehand Draw">
                <i class="fas fa-paint-brush"></i><span>Draw</span>
            </button>
            <button class="tool-icon-btn" onclick="openPanel('panel-shape', event)" title="Add Shapes">
                <i class="fas fa-shapes"></i><span>Shapes</span>
            </button>
            <button class="tool-icon-btn collage" onclick="openPanel('panel-collage', event)" title="Collage Maker">
                <i class="fas fa-th-large"></i><span>Collage</span>
            </button>
            <button class="tool-icon-btn" onclick="openPanel('panel-bg', event)" title="Canvas Size & BG">
                <i class="fas fa-desktop"></i><span>Canvas</span>
            </button>
            
            <div style="flex-grow:1;"></div>
            
            <button class="tool-icon-btn danger" onclick="deleteSelected()" title="Delete Selected Item">
                <i class="fas fa-trash"></i><span>Delete</span>
            </button>
        </div>

        <div class="props-sidebar" id="panel-bg" style="display:block;">
            <div class="props-title"><i class="fas fa-desktop"></i> Canvas Setup</div>
            <p style="font-size:13px; color:#94a3b8; margin-bottom:20px;">Set the size of the poster you want to make.</p>
            
            <div class="grid-2">
                <div>
                    <label class="form-label">Width (px)</label>
                    <input type="number" id="resizeW" class="form-control" value="1080">
                </div>
                <div>
                    <label class="form-label">Height (px)</label>
                    <input type="number" id="resizeH" class="form-control" value="1080">
                </div>
            </div>
            <button class="action-btn success-btn mb-4" onclick="applyResize()">
                <i class="fas fa-expand"></i> Apply Canvas Size
            </button>

            <label class="form-label border-top pt-4 border-secondary mt-4">Background Color</label>
            <input type="color" id="bgColorPicker" class="form-control" style="height:50px; padding:0; cursor:pointer;" value="#ffffff" onchange="changeCanvasBg()">
            
            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button class="action-btn mb-0" style="background: #ffffff; color: #000;" onclick="setBgColorDirect('#ffffff')">White</button>
                <button class="action-btn mb-0" style="background: #000000; color: #fff;" onclick="setBgColorDirect('#000000')">Black</button>
                <button class="action-btn mb-0" style="background: transparent; border: 1px dashed #94a3b8;" onclick="setBgColorDirect('transparent')">PNG</button>
            </div>
        </div>

        <div class="props-sidebar" id="panel-ai-pro">
            <div class="props-title" style="color:#f59e0b;"><i class="fas fa-bolt"></i> AI Pro Tools</div>
            <p style="font-size:13px; color:#ef4444; font-weight:bold; margin-bottom:20px;">* Please select a photo.</p>

            <div class="slider-container" style="border-color:#f59e0b;">
                <h6 style="color:#f59e0b; font-size:15px; font-weight:bold; margin-bottom:8px;"><i class="fas fa-eye"></i> AI Enhancer (HD)</h6>
                <p style="font-size:12px; color:#94a3b8; margin-bottom:15px;">Make blurry photos clear and sharp.</p>
                <button class="action-btn gold-btn mb-0" onclick="applyAIEnhance()"><i class="fas fa-magic"></i> Enhance Photo</button>
            </div>

            <div class="slider-container" style="border-color:#8b5cf6;">
                <h6 style="color:#8b5cf6; font-size:15px; font-weight:bold; margin-bottom:8px;"><i class="fas fa-palette"></i> AI Colorize</h6>
                <p style="font-size:12px; color:#94a3b8; margin-bottom:15px;">Automatic color fill in old black and white photos.</p>
                <button class="action-btn ai-btn mb-0" onclick="applyAIColorize()"><i class="fas fa-tint"></i> Colorize Photo</button>
            </div>

            <div class="slider-container" style="border-color:#ef4444;">
                <h6 style="color:#ef4444; font-size:15px; font-weight:bold; margin-bottom:8px;"><i class="fas fa-eraser"></i> Object Remover</h6>
                <p style="font-size:12px; color:#94a3b8; margin-bottom:15px;">Move the brush over additional objects, the system will mix them with the background.</p>
                
                <button class="action-btn danger mb-0" id="btnEraserMask" onclick="toggleEraserMask()"><i class="fas fa-paint-brush"></i> 1. Start Masking</button>
                <button class="action-btn success-btn mt-3 mb-0" id="btnApplyErase" onclick="applyObjectRemoval()" style="display:none; animation: pulse 1.5s infinite;"><i class="fas fa-check"></i> 2. Remove Selected Object</button>
                
                <div class="mt-4">
                    <label class="form-label">Mask Brush Size</label>
                    <input type="range" id="eraserSize" min="5" max="100" value="20" oninput="updateEraserSize()">
                </div>
            </div>

            <button class="action-btn mb-4 mt-2" style="background:transparent; border: 2px dashed #94a3b8; color:#cbd5e1;" onclick="resetAIFilters()"><i class="fas fa-undo"></i> Reset AI Effects</button>
        </div>

        <div class="props-sidebar" id="panel-crop">
            <div class="props-title"><i class="fas fa-crop-alt"></i> Exact Crop Image</div>
            <p style="font-size:13px; color:#94a3b8; margin-bottom:20px;">Select the photo and set the crop box and press 'Apply'.</p>
            
            <button class="action-btn success-btn" id="btnStartCrop" onclick="startCrop()">
                <i class="fas fa-crop"></i> Start Cropping
            </button>
            
            <div id="cropOptions" style="display:none; margin-top:25px;">
                <label class="form-label">Aspect Ratio Guidelines</label>
                <div class="grid-2">
                    <button class="action-btn mb-0" onclick="setCropRatio('free')">Free Style</button>
                    <button class="action-btn mb-0" onclick="setCropRatio('1:1')">1:1 Square</button>
                    <button class="action-btn mb-0" onclick="setCropRatio('3:4')">3:4 Portrait</button>
                    <button class="action-btn mb-0" onclick="setCropRatio('16:9')">16:9 Landscape</button>
                </div>
                
                <div style="margin-top: 25px; display: flex; gap: 15px;">
                    <button class="action-btn success-btn mb-0" onclick="applyCrop()"><i class="fas fa-check"></i> Apply Crop</button>
                    <button class="action-btn danger mb-0" onclick="cancelCrop()"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </div>
        </div>

        <div class="props-sidebar" id="panel-shadow">
            <div class="props-title"><i class="fas fa-clone"></i> Borders & Shadow</div>
            <p style="font-size:12px; color:#ef4444; font-weight:bold; margin-bottom:15px;">* Select photo, text or shape.</p>
            
            <label class="form-label text-warning"><i class="fas fa-moon"></i> Drop Shadow</label>
            <div class="slider-container">
                <label class="form-label">Shadow Blur <span class="slider-val" id="valSBlur">0</span></label>
                <input type="range" id="shadowBlur" min="0" max="100" step="1" value="0" oninput="updateObjectShadowBorder()">
            </div>
            <div class="grid-2">
                <div class="slider-container mb-0"><label class="form-label">Offset X <span id="valOX">0</span></label><input type="range" id="shadowOffsetX" min="-50" max="50" step="1" value="0" oninput="updateObjectShadowBorder()"></div>
                <div class="slider-container mb-0"><label class="form-label">Offset Y <span id="valOY">0</span></label><input type="range" id="shadowOffsetY" min="-50" max="50" step="1" value="0" oninput="updateObjectShadowBorder()"></div>
            </div>
            <label class="form-label mt-3">Shadow Color</label>
            <input type="color" id="shadowColor" class="form-control" style="height:40px; padding:0;" value="#000000" onchange="updateObjectShadowBorder()">

            <hr style="border-color:#334155; margin:25px 0;">

            <label class="form-label text-success"><i class="fas fa-square"></i> Image/Shape Border</label>
            <div class="slider-container">
                <label class="form-label">Border Width <span class="slider-val" id="valBWidth">0</span></label>
                <input type="range" id="objBorderWidth" min="0" max="50" step="1" value="0" oninput="updateObjectShadowBorder()">
            </div>
            <label class="form-label mt-3">Border Color</label>
            <input type="color" id="objBorderColor" class="form-control" style="height:40px; padding:0;" value="#ffffff" onchange="updateObjectShadowBorder()">
        </div>

        <div class="props-sidebar" id="panel-bg-remove">
            <div class="props-title" style="color:#ec4899;"><i class="fas fa-user-astronaut"></i> AI Background Remove</div>
            <p style="font-size:14px; color:#94a3b8; margin-bottom:25px; line-height: 1.6;">Remove the background of any photo and make it transparent (PNG) in just 1 second using Remove.bg API.</p>
            
            <button class="action-btn ai-btn" style="padding: 18px; font-size: 16px;" onclick="removeBackgroundAI()">
                <i class="fas fa-cut"></i> Remove Background
            </button>
            <input type="hidden" id="removeBgApiKey" value="pSqcQaSbGwN4an41dkZSyHAs">
        </div>

        <div class="props-sidebar" id="panel-filter">
            <div class="props-title"><i class="fas fa-sliders-h"></i> Filters & Adjustments</div>
            
            <div class="slider-container">
                <label class="form-label">Opacity <span class="slider-val" id="valOpacity">100%</span></label>
                <input type="range" id="objOpacity" min="0" max="1" step="0.05" value="1" oninput="updateOpacity()">
            </div>
            
            <label class="form-label mt-2">Layer Position & Flip</label>
            <div class="grid-2">
                <button class="action-btn mb-0" onclick="bringForward()"><i class="fas fa-angle-up"></i> Forward</button>
                <button class="action-btn mb-0" onclick="sendBackward()"><i class="fas fa-angle-down"></i> Backward</button>
                <button class="action-btn mb-0" onclick="flipObject('x')"><i class="fas fa-arrows-alt-h"></i> Flip X</button>
                <button class="action-btn mb-0" onclick="flipObject('y')"><i class="fas fa-arrows-alt-v"></i> Flip Y</button>
            </div>

            <label class="form-label border-top pt-4 border-secondary mt-4">Color Adjustments</label>
            <div class="slider-container"><label class="form-label">Brightness <span class="slider-val" id="valB">0</span></label><input type="range" id="filterBrightness" min="-1" max="1" step="0.05" value="0" oninput="applyFilters()"></div>
            <div class="slider-container"><label class="form-label">Contrast <span class="slider-val" id="valC">0</span></label><input type="range" id="filterContrast" min="-1" max="1" step="0.05" value="0" oninput="applyFilters()"></div>
            <div class="slider-container"><label class="form-label">Saturation <span class="slider-val" id="valS">0</span></label><input type="range" id="filterSaturation" min="-1" max="1" step="0.05" value="0" oninput="applyFilters()"></div>
            <div class="slider-container"><label class="form-label">Noise <span class="slider-val" id="valN">0</span></label><input type="range" id="filterNoise" min="0" max="1000" step="10" value="0" oninput="applyFilters()"></div>
            <div class="slider-container"><label class="form-label">Pixelate <span class="slider-val" id="valP">1</span></label><input type="range" id="filterPixelate" min="1" max="20" step="1" value="1" oninput="applyFilters()"></div>
            <div class="slider-container"><label class="form-label">Blur <span class="slider-val" id="valBlur">0</span></label><input type="range" id="filterBlur" min="0" max="1" step="0.05" value="0" oninput="applyFilters()"></div>
            
            <label class="form-label mt-4">Color Overlays</label>
            <div class="grid-2">
                <button class="action-btn mb-0" onclick="applySpecialFilter('grayscale')">Grayscale</button>
                <button class="action-btn mb-0" onclick="applySpecialFilter('sepia')">Sepia</button>
                <button class="action-btn mb-0" onclick="applySpecialFilter('invert')">Invert</button>
                <button class="action-btn mb-0" onclick="applySpecialFilter('vintage')">Vintage</button>
            </div>
            
            <button class="action-btn danger mt-4" onclick="resetImageFilters()"><i class="fas fa-undo"></i> Reset All Filters</button>
        </div>

        <div class="props-sidebar" id="panel-text">
            <div class="props-title"><i class="fas fa-font"></i> Typography Tools</div>
            
            <button class="action-btn" style="background:#38bdf8; color:#0f172a; padding: 15px; font-size: 16px;" onclick="addText()">
                <i class="fas fa-plus"></i> Add New Text
            </button>
            
            <hr style="border-color:#334155; margin:20px 0;">
            
            <label class="form-label">Font Family (Gujarati / English)</label>
            <select id="fontFamily" class="form-select" onchange="updateTextProps()">
                <option value="Arial">Arial (Default English)</option>
                <option value="'Noto Sans Gujarati', sans-serif">Noto Sans Gujarati (Gujarati 1)</option>
                <option value="'Hind Vadodara', sans-serif">Hind Vadodara (Gujarati 2)</option>
                <option value="'Mukta Vaani', sans-serif">Mukta Vaani (Gujarati 3)</option>
                <option value="'Rasa', serif">Rasa (Gujarati 4)</option>
                <option value="Impact">Impact (Bold Poster)</option>
                <option value="Times New Roman">Times New Roman</option>
                <option value="Courier New">Courier New</option>
            </select>

            <div class="slider-container">
                <label class="form-label">Font Size <span class="slider-val" id="valFontSize">50</span></label>
                <input type="range" id="fontSizeSlider" min="10" max="500" step="1" value="50" oninput="updateTextProps()">
            </div>
            
            <div class="grid-2">
                <div>
                    <label class="form-label">Text Color</label>
                    <input type="color" id="textColorPicker" class="form-control mb-0" style="height:45px; padding:0; cursor:pointer;" value="#000000" onchange="updateTextProps()">
                </div>
                <div>
                    <label class="form-label">Text Background</label>
                    <input type="color" id="textBgColorPicker" class="form-control mb-0" style="height:45px; padding:0; cursor:pointer;" value="#ffffff" onchange="updateTextProps()">
                    <button class="action-btn mt-2" style="padding:5px; font-size:11px; background:#475569;" onclick="clearTextBg()">Remove BG</button>
                </div>
            </div>
            
            <label class="form-label border-top pt-4 border-secondary mt-3">Text Stroke (Border)</label>
            <div class="grid-2 align-items-center">
                <input type="color" id="textStrokeColor" class="form-control mb-0" style="height:45px; padding:0; cursor:pointer;" value="#ffffff" onchange="updateTextProps()">
                <div class="slider-container mb-0 border-0 p-0 bg-transparent">
                    <label class="form-label">Thickness</label>
                    <input type="range" id="textStrokeWidth" min="0" max="20" step="0.5" value="0" oninput="updateTextProps()">
                </div>
            </div>

            <label class="form-label border-top pt-4 border-secondary mt-4">Formatting & Alignment</label>
            <div style="display:flex; gap:12px; margin-bottom:20px; flex-wrap: wrap;">
                <button class="format-btn" id="btnBold" onclick="toggleTextFormat('bold')"><i class="fas fa-bold"></i></button>
                <button class="format-btn" id="btnItalic" onclick="toggleTextFormat('italic')"><i class="fas fa-italic"></i></button>
                <button class="format-btn" id="btnUnderline" onclick="toggleTextFormat('underline')"><i class="fas fa-underline"></i></button>
                <button class="format-btn" id="btnLinethrough" onclick="toggleTextFormat('linethrough')"><i class="fas fa-strikethrough"></i></button>
                
                <div style="width: 2px; height: 45px; background: #475569; margin: 0 5px;"></div>
                
                <button class="format-btn" id="btnAlignLeft" onclick="setTextAlign('left')"><i class="fas fa-align-left"></i></button>
                <button class="format-btn" id="btnAlignCenter" onclick="setTextAlign('center')"><i class="fas fa-align-center"></i></button>
                <button class="format-btn" id="btnAlignRight" onclick="setTextAlign('right')"><i class="fas fa-align-right"></i></button>
            </div>
        </div>
        
        <div class="props-sidebar" id="panel-draw">
            <div class="props-title"><i class="fas fa-paint-brush"></i> Draw Freehand</div>
            
            <button class="action-btn" id="btnDrawMode" style="background:#10b981; color:#fff; margin-bottom:25px; padding: 18px; font-size: 16px;" onclick="toggleDrawMode()">
                <i class="fas fa-pen"></i> Start Drawing Mode
            </button>
            
            <div class="slider-container">
                <label class="form-label">Brush Color</label>
                <input type="color" id="brushColorPicker" class="form-control mb-0" style="height:50px; padding:0; cursor:pointer;" value="#ef4444" onchange="updateBrush()">
            </div>
            
            <div class="slider-container mt-4">
                <label class="form-label">Brush Size Thickness</label>
                <input type="range" id="brushSize" min="1" max="150" value="10" oninput="updateBrush()">
            </div>
        </div>

        <div class="props-sidebar" id="panel-shape">
            <div class="props-title"><i class="fas fa-shapes"></i> Add Elements</div>
            
            <div class="slider-container mb-5">
                <label class="form-label">Shape Color Fill</label>
                <input type="color" id="shapeColorPicker" class="form-control mb-0" style="height:50px; padding:0; cursor:pointer;" value="#3b82f6">
            </div>
            
            <label class="form-label">Click to Add Shape</label>
            <div class="grid-2">
                <button class="action-btn mb-0" onclick="addShape('rect')"><i class="fas fa-square"></i> Square</button>
                <button class="action-btn mb-0" onclick="addShape('circle')"><i class="fas fa-circle"></i> Circle</button>
                <button class="action-btn mb-0" onclick="addShape('triangle')"><i class="fas fa-caret-up"></i> Triangle</button>
                <button class="action-btn mb-0" onclick="addShape('star')"><i class="fas fa-star"></i> Star</button>
                <button class="action-btn mb-0" onclick="addShape('line')" style="grid-column: span 2;"><i class="fas fa-minus"></i> Add Straight Line</button>
            </div>
        </div>

        <div class="props-sidebar" id="panel-collage">
            <div class="props-title" style="color:#10b981;"><i class="fas fa-th-large"></i> Collage Maker</div>
            <p style="font-size:13px; color:#94a3b8; margin-bottom:20px;">Add a grid to the canvas and arrange different photos in it.</p>
            
            <button class="action-btn success-btn mb-4" style="padding: 15px;" onclick="document.getElementById('fileUploadInput').click()">
                <i class="fas fa-images"></i> 1. Add Multiple Photos
            </button>
            
            <label class="form-label border-top pt-4 border-secondary">Add Grids (Frames)</label>
            <div class="grid-2">
                <button class="action-btn mb-0" onclick="addCollageGrid(2, 'vertical')"><i class="fas fa-columns"></i> 2 Split (Vert)</button>
                <button class="action-btn mb-0" onclick="addCollageGrid(2, 'horizontal')"><i class="fas fa-window-minimize"></i> 2 Split (Horz)</button>
                <button class="action-btn mb-0" onclick="addCollageGrid(3, 'vertical')"><i class="fas fa-th-list"></i> 3 Split</button>
                <button class="action-btn mb-0" onclick="addCollageGrid(4, 'grid')"><i class="fas fa-th-large"></i> 4 Grid Square</button>
            </div>
            
            <label class="form-label border-top pt-4 border-secondary mt-4">Grid Settings</label>
            <div class="slider-container">
                <label class="form-label">Grid Border Thickness</label>
                <input type="range" id="gridBorder" min="0" max="50" value="10" oninput="updateGridBorders()">
            </div>
            <div class="slider-container">
                <label class="form-label">Border Color</label>
                <input type="color" id="gridBorderColor" class="form-control mb-0" style="height:40px; padding:0;" value="#ffffff" onchange="updateGridBorders()">
            </div>
        </div>

        <div class="workspace" id="workspaceContainer">
    <div class="sys-zoom-controls" style="position: absolute; bottom: 20px; right: 20px; background: rgba(255,255,255,0.95); padding: 8px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); display: flex; flex-direction: column; gap: 5px; z-index: 9999; border: 1px solid #cbd5e1;">
        <div style="font-size: 10px; font-weight: bold; color: #475569; text-align: center; margin-bottom: 2px;">ZOOM</div>
        <button type="button" onclick="sysChangeZoom(0.1)" style="background: #f1f5f9; border: 1px solid #94a3b8; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-weight: bold; transition: 0.2s;">➕</button>
        <button type="button" onclick="sysResetZoom()" style="background: #f1f5f9; border: 1px solid #94a3b8; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-size: 10px; font-weight: bold; transition: 0.2s;">100%</button>
        <button type="button" onclick="sysChangeZoom(-0.1)" style="background: #f1f5f9; border: 1px solid #94a3b8; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-weight: bold; transition: 0.2s;">➖</button>
    </div> <!-- sys-zoom-controls -->
    
    <div class="canvas-container" style="margin: 0 auto; overflow: visible;">
        <canvas id="mainCanvas"></canvas>
    </div>
</div> <!-- workspaceContainer -->

</div> <!-- studio-body -->
</div> <!-- studio-wrapper -->

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.9); z-index:9999; justify-content:center; align-items:center; flex-direction:column; color:white;">
    <div class="spinner-border text-primary" style="width:3rem; height:3rem; border: 4px solid #38bdf8; border-top: 4px solid transparent; border-radius: 50%; animation: spin 1s linear infinite;" role="status"></div>
    <div id="loadingText" class="mt-3 fw-bold" style="font-size:18px;">Processing...</div>
</div>

<style>
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
@keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); box-shadow: 0 0 15px rgba(16,185,129,0.5); } 100% { transform: scale(1); } }
</style>

<input type="file" id="fileUploadInput" accept="image/png, image/jpeg, image/jpg, image/webp">

<script>
    const userRole = '<?= $_SESSION['user_role'] ?? 'guest' ?>';
    const currency = '<?= $currency ?>';
    const cardCost = <?= $card_cost ?>;
    const baseUrl = "<?= BASE_URL ?>";
    const APP_URL = "<?= APP_URL ?>";
    
    function saveDraft() {
        if (!canvas) return;
        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Saving...</span>';
        btn.disabled = true;

        const json = JSON.stringify(canvas.toJSON(['customId', 'customBgEnable', 'customBgColor', 'customBgShape', 'customPadding', 'fontSize', 'fontFamily', 'fill', 'opacity', 'fontWeight', 'fontStyle', 'textAlign', 'stroke', 'strokeWidth']));
        const formData = new FormData();
        formData.append('service_slug', 'photo_studio');
        formData.append('service_name', 'Ultimate Photo Studio Pro');
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

    const canvas = new fabric.Canvas('mainCanvas', {
        preserveObjectStacking: true,
        selection: true,
        backgroundColor: '#ffffff'
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
            console.error("Photo Studio Draft Load Error:", err);
            showLoading(false);
            alert("❌ Failed to restore draft completely.");
        }
    }

    let LOGICAL_W = 1080;
    let LOGICAL_H = 1080;
    let cropZone = null;
    let isMasking = false;
    let maskPathHistory = [];

    window.addEventListener('resize', fitCanvasToScreen);

    function fitCanvasToScreen() {
        const container = document.getElementById('workspaceContainer');
        if(!container) return;
        
        const pW = container.clientWidth - 80;
        const pH = container.clientHeight - 80;
        
        const scaleX = pW / LOGICAL_W;
        const scaleY = pH / LOGICAL_H;
        let scale = Math.min(scaleX, scaleY, 1);
        
        canvas.setDimensions({ width: LOGICAL_W * scale, height: LOGICAL_H * scale });
        canvas.setZoom(scale);
        canvas.renderAll();
    }

    // Initialize size
    fitCanvasToScreen();
    canvas.backgroundColor = '#ffffff';

    // ==========================================
    // 1. UPLOAD IMAGE
    // ==========================================
    document.getElementById('fileUploadInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if(!file) return;
        const reader = new FileReader();
        reader.onload = function(f) {
            showLoading(true, "Loading Image...");
            fabric.Image.fromURL(f.target.result, function(img) {
                const sX = (LOGICAL_W * 0.8) / img.width;
                const sY = (LOGICAL_H * 0.8) / img.height;
                const s = Math.min(sX, sY, 1);
                
                img.set({
                    left: LOGICAL_W / 2, top: LOGICAL_H / 2, originX: 'center', originY: 'center',
                    scaleX: s, scaleY: s,
                    borderColor: '#38bdf8', cornerColor: '#38bdf8', transparentCorners: false
                });
                canvas.add(img); canvas.setActiveObject(img); canvas.renderAll();
                showLoading(false);
            });
        };
        reader.readAsDataURL(file);
        e.target.value = "";
    });

    // ==========================================
    // 2. PANEL TOGGLES
    // ==========================================
    function openPanel(panelId, event) {
        if(isMasking) toggleEraserMask();
        if(cropZone) cancelCrop();
        
        document.querySelectorAll('.props-sidebar').forEach(p => p.style.display = 'none');
        document.querySelectorAll('.tool-icon-btn').forEach(btn => btn.classList.remove('active'));
        
        document.getElementById(panelId).style.display = 'block';
        if(event && event.currentTarget) event.currentTarget.classList.add('active');
        
        fitCanvasToScreen();
    }

    // ==========================================
    // 3. CANVAS SETUP
    // ==========================================
    function applyResize() {
        LOGICAL_W = parseInt(document.getElementById('resizeW').value) || 1080;
        LOGICAL_H = parseInt(document.getElementById('resizeH').value) || 1080;
        fitCanvasToScreen();
    }
    function changeCanvasBg() {
        let currentBg = document.getElementById('bgColorPicker').value;
        canvas.backgroundColor = currentBg; canvas.renderAll();
    }
    function setBgColorDirect(color) {
        if(color === 'transparent') { canvas.backgroundColor = null; document.getElementById('bgColorPicker').value = '#cccccc'; } 
        else { canvas.backgroundColor = color; document.getElementById('bgColorPicker').value = color; }
        canvas.renderAll();
    }
    function clearCanvas() {
        if(confirm("Clear all items from the canvas?")) {
            canvas.clear(); 
            canvas.backgroundColor = document.getElementById('bgColorPicker').value;
            canvas.renderAll();
        }
    }
    function deleteSelected() {
        const activeObjects = canvas.getActiveObjects();
        if (activeObjects.length) {
            canvas.discardActiveObject();
            activeObjects.forEach(obj => canvas.remove(obj));
            canvas.renderAll();
        }
    }

    canvas.on('selection:created', syncAllPanels);
    canvas.on('selection:updated', syncAllPanels);
    function syncAllPanels() {
        syncTextPanel();
        syncEffectsPanel();
    }

    canvas.on('path:created', function(e) {
        if(isMasking) {
            let path = e.path;
            path.set({ selectable: false, evented: false, objectCaching: false });
            maskPathHistory.push(path);
        }
    });

    function bringForward() { const obj = canvas.getActiveObject(); if(obj) { canvas.bringForward(obj); canvas.renderAll(); } }
    function sendBackward() { const obj = canvas.getActiveObject(); if(obj) { canvas.sendBackwards(obj); canvas.renderAll(); } }
    function flipObject(dir) { const obj = canvas.getActiveObject(); if(obj) { if(dir==='x') obj.set('flipX', !obj.flipX); else obj.set('flipY', !obj.flipY); canvas.renderAll(); }}

    // ==========================================
    // 4. SMART EXACT CROP
    // ==========================================
    let currentCropRatio = null;
    function setCropRatio(ratioType) {
        currentCropRatio = ratioType;
        if(cropZone) {
            let newW = cropZone.width, newH = cropZone.height;
            if(ratioType === '1:1') newW = newH = Math.min(newW, newH);
            else if(ratioType === '3:4') { newH = Math.max(newW, newH); newW = newH * (3/4); }
            else if(ratioType === '16:9') { newW = Math.max(newW, newH); newH = newW * (9/16); }
            
            cropZone.set({ width: newW, height: newH, scaleX: 1, scaleY: 1 });
            
            if(ratioType !== 'free') cropZone.setControlsVisibility({ mt: false, mb: false, ml: false, mr: false });
            else cropZone.setControlsVisibility({ mt: true, mb: true, ml: true, mr: true });
            
            canvas.renderAll();
        }
    }
    
    function startCrop() {
        const obj = canvas.getActiveObject();
        if(!obj) { alert("Select a photo to crop."); return; }
        if(obj.type !== 'image') { alert("Only photos can be cropped."); return; }
        
        document.getElementById('btnStartCrop').style.display = 'none';
        document.getElementById('cropOptions').style.display = 'block';

        let cWidth = LOGICAL_W * 0.5, cHeight = LOGICAL_H * 0.5;

        cropZone = new fabric.Rect({
            fill: 'rgba(255,255,255,0.4)', stroke: '#38bdf8', strokeDashArray: [5, 5], strokeWidth: 3,
            width: cWidth, height: cHeight, originX: 'center', originY: 'center',
            left: LOGICAL_W / 2, top: LOGICAL_H / 2, transparentCorners: false, cornerColor: '#38bdf8', cornerSize: 15
        });
        
        canvas.add(cropZone); canvas.setActiveObject(cropZone); setCropRatio('free');
    }
    
    function applyCrop() {
        if(!cropZone) return;
        showLoading(true, "Cropping Exact Pixels...");
        setTimeout(() => {
            let lWidth = cropZone.width * cropZone.scaleX;
            let lHeight = cropZone.height * cropZone.scaleY;
            let lLeft = cropZone.left - lWidth / 2;
            let lTop = cropZone.top - lHeight / 2;

            canvas.discardActiveObject(); cropZone.visible = false; canvas.renderAll();

            // Extract pixels directly from logic
            let croppedDataUrl = canvas.toDataURL({ 
                format: 'png', 
                left: lLeft, top: lTop, width: lWidth, height: lHeight, 
                multiplier: 1 / canvas.getZoom()
            });

            // Rebuild canvas to crop size
            canvas.clear(); 
            LOGICAL_W = Math.round(lWidth); LOGICAL_H = Math.round(lHeight);

            document.getElementById('resizeW').value = LOGICAL_W; 

            document.getElementById('resizeH').value = LOGICAL_H;
            
            let currentBg = document.getElementById('bgColorPicker').value;
            canvas.backgroundColor = currentBg === 'transparent' ? null : currentBg;

            fabric.Image.fromURL(croppedDataUrl, function(img) {
                img.set({ left: LOGICAL_W / 2, top: LOGICAL_H / 2, originX: 'center', originY: 'center' });
                canvas.add(img); canvas.setActiveObject(img);
                
                fitCanvasToScreen(); cancelCrop(); showLoading(false);
            });
        }, 200);
    }
    
    function cancelCrop() {
        if(cropZone) { 
            canvas.remove(cropZone); 
            cropZone = null; 
        }
        document.getElementById('btnStartCrop').style.display = 'block'; 
        document.getElementById('cropOptions').style.display = 'none';
    }

    // ==========================================
    // 7. SHADOW & BORDERS (NEW)
    // ==========================================
    function updateObjectShadowBorder() {
        const obj = canvas.getActiveObject(); 
        if(!obj) return;

        // Shadow
        const sBlur = parseInt(document.getElementById('shadowBlur').value); 
        const sOffsetX = parseInt(document.getElementById('shadowOffsetX').value); 
        const sOffsetY = parseInt(document.getElementById('shadowOffsetY').value); 
        const sColor = document.getElementById('shadowColor').value;
        
        document.getElementById('valSBlur').innerText = sBlur;
        document.getElementById('valOX').innerText = sOffsetX;
        document.getElementById('valOY').innerText = sOffsetY;

        if (sBlur > 0 || sOffsetX !== 0 || sOffsetY !== 0) {
            obj.set('shadow', new fabric.Shadow({ color: sColor, blur: sBlur, offsetX: sOffsetX, offsetY: sOffsetY }));
        } else {
            obj.set('shadow', null);
        }

        // Image/Shape Border
        const bWidth = parseInt(document.getElementById('objBorderWidth').value);
        const bColor = document.getElementById('objBorderColor').value;
        
        document.getElementById('valBWidth').innerText = bWidth;

        if(obj.type === 'image' || obj.type === 'rect' || obj.type === 'circle' || obj.type === 'triangle') {
            obj.set({ stroke: bColor, strokeWidth: bWidth });
        }

        canvas.renderAll();
    }

    function syncEffectsPanel() {
        const obj = canvas.getActiveObject();
        if(obj) {
            document.getElementById('objOpacity').value = obj.opacity !== undefined ? obj.opacity : 1;
            document.getElementById('valOpacity').innerText = Math.round((obj.opacity || 1) * 100) + '%';
            
            if(obj.shadow) {
                document.getElementById('shadowBlur').value = obj.shadow.blur || 0; 
                document.getElementById('shadowOffsetX').value = obj.shadow.offsetX || 0; 
                document.getElementById('shadowOffsetY').value = obj.shadow.offsetY || 0; 
                document.getElementById('shadowColor').value = obj.shadow.color || '#000000';
            } else {
                document.getElementById('shadowBlur').value = 0; document.getElementById('shadowOffsetX').value = 0; document.getElementById('shadowOffsetY').value = 0;
            }

            if(obj.strokeWidth !== undefined) {
                document.getElementById('objBorderWidth').value = obj.strokeWidth;
                document.getElementById('objBorderColor').value = obj.stroke || '#ffffff';
                document.getElementById('valBWidth').innerText = obj.strokeWidth;
            }
        }
    }

    // ==========================================
    // 8. FILTERS & ADJUSTMENTS
    // ==========================================
    function updateOpacity() {
        const obj = canvas.getActiveObject(); if(!obj) return;
        const opacity = parseFloat(document.getElementById('objOpacity').value);
        obj.set('opacity', opacity); document.getElementById('valOpacity').innerText = Math.round(opacity * 100) + '%';
        canvas.renderAll();
    }

    function applyFilters() {
        const obj = canvas.getActiveObject(); 
        if(!obj || obj.type !== 'image') return;
        
        const b = parseFloat(document.getElementById('filterBrightness').value); 
        const c = parseFloat(document.getElementById('filterContrast').value); 
        const s = parseFloat(document.getElementById('filterSaturation').value);
        const n = parseInt(document.getElementById('filterNoise').value);
        const p = parseInt(document.getElementById('filterPixelate').value);
        const blur = parseFloat(document.getElementById('filterBlur').value);
        
        document.getElementById('valB').innerText = b; 
        document.getElementById('valC').innerText = c; 
        document.getElementById('valS').innerText = s; 
        document.getElementById('valN').innerText = n; 
        document.getElementById('valP').innerText = p; 
        document.getElementById('valBlur').innerText = blur; 

        // Preserve AI filters, replace basics
        obj.filters = obj.filters.filter(f => 
            !(f instanceof fabric.Image.filters.Brightness) && 
            !(f instanceof fabric.Image.filters.Contrast) && 
            !(f instanceof fabric.Image.filters.Saturation) &&
            !(f instanceof fabric.Image.filters.Noise) &&
            !(f instanceof fabric.Image.filters.Pixelate) &&
            !(f instanceof fabric.Image.filters.Blur)
        );
        
        if (b !== 0) obj.filters.push(new fabric.Image.filters.Brightness({ brightness: b }));
        if (c !== 0) obj.filters.push(new fabric.Image.filters.Contrast({ contrast: c }));
        if (s !== 0) obj.filters.push(new fabric.Image.filters.Saturation({ saturation: s }));
        if (n !== 0) obj.filters.push(new fabric.Image.filters.Noise({ noise: n }));
        if (p !== 1) obj.filters.push(new fabric.Image.filters.Pixelate({ blocksize: p }));
        if (blur !== 0) obj.filters.push(new fabric.Image.filters.Blur({ blur: blur }));
        
        obj.applyFilters(); canvas.renderAll();
    }

    function applySpecialFilter(type) {
        const obj = canvas.getActiveObject(); if(!obj || obj.type !== 'image') return;
        let filter; 
        if(type === 'grayscale') filter = new fabric.Image.filters.Grayscale(); 
        if(type === 'sepia') filter = new fabric.Image.filters.Sepia(); 
        if(type === 'invert') filter = new fabric.Image.filters.Invert(); 
        if(type === 'vintage') filter = new fabric.Image.filters.Vintage();
        
        if(filter) { obj.filters.push(filter); obj.applyFilters(); canvas.renderAll(); }
    }

    function resetImageFilters() {
        const obj = canvas.getActiveObject(); 
        if(!obj || obj.type !== 'image') return;
        
        document.getElementById('filterBrightness').value = 0; document.getElementById('filterContrast').value = 0; document.getElementById('filterSaturation').value = 0; 
        document.getElementById('filterNoise').value = 0; document.getElementById('filterPixelate').value = 1; document.getElementById('filterBlur').value = 0; 
        
        document.getElementById('valB').innerText = 0; document.getElementById('valC').innerText = 0; document.getElementById('valS').innerText = 0; 
        document.getElementById('valN').innerText = 0; document.getElementById('valP').innerText = 1; document.getElementById('valBlur').innerText = 0; 
        
        obj.filters = []; obj.applyFilters(); canvas.renderAll();
    }

    // ==========================================
    // 9. AI PRO (ENHANCE, COLORIZE, OBJECT REMOVER)
    // ==========================================
    function applyAIEnhance() {
        const obj = canvas.getActiveObject();
        if(!obj || obj.type !== 'image') { alert("Select photo first."); return; }
        
        showLoading(true, "AI Enhancing Image (HD)...");
        setTimeout(() => {
            if(!obj.originalFilters) obj.originalFilters = [...obj.filters];
            
            let sharpenMatrix = [  0, -1,  0, -1,  5, -1, 0, -1,  0 ];
            let filter = new fabric.Image.filters.Convolute({ matrix: sharpenMatrix });
            let contrast = new fabric.Image.filters.Contrast({ contrast: 0.15 });
            
            obj.filters.push(filter, contrast); obj.applyFilters(); canvas.renderAll(); showLoading(false);
        }, 500);
    }

    function applyAIColorize() {
        const obj = canvas.getActiveObject();
        if(!obj || obj.type !== 'image') { alert("Select photo first."); return; }
        
        showLoading(true, "AI Colorizing...");
        setTimeout(() => {
            if(!obj.originalFilters) obj.originalFilters = [...obj.filters];
            let saturation = new fabric.Image.filters.Saturation({ saturation: 0.85 });
            let brightness = new fabric.Image.filters.Brightness({ brightness: 0.05 });
            
            obj.filters.push(saturation, brightness); obj.applyFilters(); canvas.renderAll(); showLoading(false);
        }, 500);
    }

    function resetAIFilters() {
        const obj = canvas.getActiveObject();
        if(!obj || obj.type !== 'image') return;
        if(obj.originalFilters) {
            obj.filters = [...obj.originalFilters]; obj.applyFilters(); canvas.renderAll();
        }
    }

    // --- ADVANCED INPAINTING (OBJECT REMOVER - FIXED WHITE BOX) ---
    function toggleEraserMask() {
        isMasking = !isMasking;
        const btn = document.getElementById('btnEraserMask');
        const btnApply = document.getElementById('btnApplyErase');
        
        if (isMasking) {
            btn.innerHTML = '<i class="fas fa-times"></i> Cancel Masking';
            btn.style.background = '#ef4444';
            btnApply.style.display = 'flex'; 
            
            canvas.isDrawingMode = true;
            canvas.freeDrawingBrush.color = 'rgba(239, 68, 68, 0.7)'; // Mask Color
            updateEraserSize();
        } else {
            btn.innerHTML = '<i class="fas fa-paint-brush"></i> 1. Start Masking';
            btn.style.background = '#dc2626';
            btnApply.style.display = 'none';
            canvas.isDrawingMode = false;
            
            // Remove drawn masks if cancelled
            maskPathHistory.forEach(path => canvas.remove(path));
            maskPathHistory = [];
            canvas.renderAll();
        }
    }
    
    function updateEraserSize() {
        if(canvas.isDrawingMode && isMasking) {
            canvas.freeDrawingBrush.width = parseInt(document.getElementById('eraserSize').value) || 20;
        }
    }

    function applyObjectRemoval() {
        if(maskPathHistory.length === 0) { alert("Move the red brush over the object to be removed first."); return; }
        
        showLoading(true, "AI Inpainting (Removing Object)...");
        
        setTimeout(() => {
            // Group the masks to get bounding box
            let group = new fabric.Group(maskPathHistory);
            let rect = group.getBoundingRect();
            
            // Hide masks temporarily so they aren't cloned
            maskPathHistory.forEach(path => path.visible = false);
            canvas.renderAll();

            // Extract the background area slightly LARGER than the mask (for cloning surrounding pixels)
            let cropDataUrl = canvas.toDataURL({ 
                format: 'jpeg', left: rect.left - 15, top: rect.top - 15, 
                width: rect.width + 30, height: rect.height + 30, multiplier: 1 
            });

            fabric.Image.fromURL(cropDataUrl, function(img) {
                // Apply a heavy blur to the cloned patch to create a seamless blend (Simulated Inpainting)
                let blurFilter = new fabric.Image.filters.Blur({ blur: 0.6 });
                img.filters.push(blurFilter);
                img.applyFilters();
                
                // Use the red masks as a clip path to only show the blurred replacement WHERE the user painted
                let clipGroup = new fabric.Group(maskPathHistory.map(p => {
                    let clone = fabric.util.object.clone(p);
                    // Adjust coordinates relative to the new patch image
                    clone.set({ left: clone.left - rect.left + 15, top: clone.top - rect.top + 15, visible: true, fill: 'black', stroke: 'black' });
                    return clone;
                }));

                img.set({
                    left: rect.left - 15, top: rect.top - 15,
                    selectable: true, evented: true,
                    clipPath: clipGroup // MAGIC: Only shows blurred pixels inside the brushed area!
                });

                // Delete original red masks
                maskPathHistory.forEach(path => canvas.remove(path));
                maskPathHistory = [];
                
                canvas.add(img); canvas.renderAll();
                toggleEraserMask(); // Turn off mask mode
                showLoading(false);
            });
        }, 500);
    }

    // ==========================================
    // 10. AI REMOVE BG API
    // ==========================================
    async function removeBackgroundAI() {
        const activeObj = canvas.getActiveObject();
        if(!activeObj || activeObj.type !== 'image') { alert("Please select a photo first."); return; }
        
        const apiKey = document.getElementById('removeBgApiKey').value;
        if(!apiKey || apiKey.trim() === '') { alert("API Key is missing."); return; }

        showLoading(true, "AI body scanning removing background...");

        try {
            const imgDataUrl = activeObj.toDataURL({ format: 'png', multiplier: 1 }); 
            const base64Image = imgDataUrl.split(',')[1];
            
            const response = await fetch('https://api.remove.bg/v1.0/removebg', { 
                method: 'POST', headers: { 'X-Api-Key': apiKey, 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ image_file_b64: base64Image, size: 'auto' }) 
            });
            
            if (!response.ok) throw new Error("API Error");
            
            const blob = await response.blob(); 
            const transparentImageUrl = URL.createObjectURL(blob);
            
            fabric.Image.fromURL(transparentImageUrl, function(newImg) {
                newImg.set({ 
                    left: activeObj.left, top: activeObj.top, scaleX: activeObj.scaleX, scaleY: activeObj.scaleY, 
                    angle: activeObj.angle, originX: activeObj.originX, originY: activeObj.originY, 
                    cornerColor: '#38bdf8', borderColor: '#38bdf8', transparentCorners: false 
                });
                
                canvas.remove(activeObj); canvas.add(newImg); canvas.setActiveObject(newImg); 
                showLoading(false);
            });
        } catch (error) { alert("❌ Failed to remove background. API key limit may have been exceeded."); showLoading(false); }
    }

    // ==========================================
    // 11. TYPOGRAPHY ENGINE
    // ==========================================
    function addText() {
        const color = document.getElementById('textColorPicker').value;
        const font = document.getElementById('fontFamily').value.replace(/['"]/g, '');
        const size = parseInt(document.getElementById('fontSizeSlider').value) || 50;
        
        const text = new fabric.IText('Type here / Text', {
            left: LOGICAL_W / 2, top: LOGICAL_H / 2, originX: 'center', originY: 'center',
            fontFamily: font, fill: color, fontSize: size, fontWeight: 'bold',
            borderColor: '#38bdf8', cornerColor: '#38bdf8', transparentCorners: false, padding: 10,
            stroke: document.getElementById('textStrokeColor').value, strokeWidth: parseFloat(document.getElementById('textStrokeWidth').value)
        });
        
        canvas.add(text); canvas.setActiveObject(text); text.enterEditing(); text.selectAll(); syncTextPanel();
    }

    function updateTextProps() {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'i-text') {
            let newSize = parseInt(document.getElementById('fontSizeSlider').value);
            obj.set({
                fill: document.getElementById('textColorPicker').value,
                backgroundColor: document.getElementById('textBgColorPicker').value,
                fontFamily: document.getElementById('fontFamily').value.replace(/['"]/g, ''),
                fontSize: newSize,
                stroke: document.getElementById('textStrokeColor').value,
                strokeWidth: parseFloat(document.getElementById('textStrokeWidth').value),
                scaleX: 1, scaleY: 1
            });
            document.getElementById('valFontSize').innerText = newSize; canvas.renderAll();
        }
    }

    function clearTextBg() {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'i-text') { obj.set('backgroundColor', ''); canvas.renderAll(); }
    }

    function syncTextPanel() {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'i-text') {
            let currentFont = obj.fontFamily;
            if(currentFont.includes('Gujarati')) currentFont = "'Noto Sans Gujarati', sans-serif";
            else if(currentFont.includes('Hind')) currentFont = "'Hind Vadodara', sans-serif";
            else if(currentFont.includes('Mukta')) currentFont = "'Mukta Vaani', sans-serif";
            else if(currentFont.includes('Rasa')) currentFont = "'Rasa', serif";
            
            try { document.getElementById('fontFamily').value = currentFont; } catch(e){}
            
            let safeSize = Math.round(obj.fontSize * obj.scaleX);
            document.getElementById('fontSizeSlider').value = safeSize; document.getElementById('valFontSize').innerText = safeSize;
            
            document.getElementById('textColorPicker').value = obj.fill || '#000000'; 
            document.getElementById('textBgColorPicker').value = obj.backgroundColor || '#ffffff';
            document.getElementById('textStrokeColor').value = obj.stroke || '#ffffff'; 
            document.getElementById('textStrokeWidth').value = obj.strokeWidth || 0;

            document.getElementById('btnBold').classList.toggle('active', obj.fontWeight === 'bold'); 
            document.getElementById('btnItalic').classList.toggle('active', obj.fontStyle === 'italic'); 
            document.getElementById('btnUnderline').classList.toggle('active', obj.underline); 
            document.getElementById('btnLinethrough').classList.toggle('active', obj.linethrough);

            document.getElementById('btnAlignLeft').classList.toggle('active', obj.textAlign === 'left'); 
            document.getElementById('btnAlignCenter').classList.toggle('active', obj.textAlign === 'center'); 
            document.getElementById('btnAlignRight').classList.toggle('active', obj.textAlign === 'right');
        }
    }

    function toggleTextFormat(format) {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'i-text') {
            if(format === 'bold') { obj.set('fontWeight', obj.fontWeight === 'bold' ? 'normal' : 'bold'); }
            if(format === 'italic') { obj.set('fontStyle', obj.fontStyle === 'italic' ? 'normal' : 'italic'); }
            if(format === 'underline') { obj.set('underline', !obj.underline); }
            if(format === 'linethrough') { obj.set('linethrough', !obj.linethrough); }
            canvas.renderAll(); syncTextPanel();
        }
    }

    function setTextAlign(alignment) {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'i-text') { obj.set('textAlign', alignment); canvas.renderAll(); syncTextPanel(); }
    }

    // ==========================================
    // 12. DRAWING & SHAPES
    // ==========================================
    function toggleDrawMode() {
        if (typeof isMasking !== 'undefined' && isMasking) toggleEraserMask();
        canvas.isDrawingMode = !canvas.isDrawingMode;
        const btn = document.getElementById('btnDrawMode');
        if (canvas.isDrawingMode) {
            btn.innerHTML = '<i class="fas fa-times"></i> Stop Drawing';
            btn.style.background = '#ef4444';
            updateBrush();
        } else {
            btn.innerHTML = '<i class="fas fa-pen"></i> Start Drawing Mode';
            btn.style.background = '#10b981';
        }
    }
    function updateBrush() {
        if(canvas.isDrawingMode && (!window.isMasking || window.isMasking===false)) {
            let color = document.getElementById('brushColorPicker').value;
            let width = parseInt(document.getElementById('brushSize').value) || 10;
            if(canvas.freeDrawingBrush) {
                canvas.freeDrawingBrush.color = color;
                canvas.freeDrawingBrush.width = width;
            }
        }
    }
    
    function updateEraserSize() {
        if(canvas.isDrawingMode && isMasking) {
            canvas.freeDrawingBrush.width = parseInt(document.getElementById('eraserSize').value) || 20;
        }
    }

    function applyObjectRemoval() {
        if(maskPathHistory.length === 0) { alert("Move the red brush over the object to be removed first."); return; }
        
        showLoading(true, "AI Inpainting (Removing Object)...");
        
        setTimeout(() => {
            // Group the masks to get bounding box
            let group = new fabric.Group(maskPathHistory);
            let rect = group.getBoundingRect();
            
            // Hide masks temporarily so they aren't cloned
            maskPathHistory.forEach(path => path.visible = false);
            canvas.renderAll();

            // Extract the background area slightly LARGER than the mask (for cloning surrounding pixels)
            let cropDataUrl = canvas.toDataURL({ 
                format: 'jpeg', left: rect.left - 15, top: rect.top - 15, 
                width: rect.width + 30, height: rect.height + 30, multiplier: 1 
            });

            fabric.Image.fromURL(cropDataUrl, function(img) {
                // Apply a heavy blur to the cloned patch to create a seamless blend (Simulated Inpainting)
                let blurFilter = new fabric.Image.filters.Blur({ blur: 0.6 });
                img.filters.push(blurFilter);
                img.applyFilters();
                
                // Use the red masks as a clip path to only show the blurred replacement WHERE the user painted
                let clipGroup = new fabric.Group(maskPathHistory.map(p => {
                    let clone = fabric.util.object.clone(p);
                    // Adjust coordinates relative to the new patch image
                    clone.set({ left: clone.left - rect.left + 15, top: clone.top - rect.top + 15, visible: true, fill: 'black', stroke: 'black' });
                    return clone;
                }));

                img.set({
                    left: rect.left - 15, top: rect.top - 15,
                    selectable: true, evented: true,
                    clipPath: clipGroup // MAGIC: Only shows blurred pixels inside the brushed area!
                });

                // Delete original red masks
                maskPathHistory.forEach(path => canvas.remove(path));
                maskPathHistory = [];
                
                canvas.add(img); canvas.renderAll();
                toggleEraserMask(); // Turn off mask mode
                showLoading(false);
            });
        }, 500);
    }

    // ==========================================
    // 10. AI STUDIO FEATURES
    // ==========================================
    function applyAIEnhance() {
        let activeObj = canvas.getActiveObject();
        if(!activeObj || activeObj.type !== 'image') { alert("Please select a photo first."); return; }
        
        let filter = new fabric.Image.filters.Brightness({ brightness: 0.1 });
        let contrast = new fabric.Image.filters.Contrast({ contrast: 0.2 });
        let sharpen = new fabric.Image.filters.Convolute({
            matrix: [ 0, -0.5,  0,
                     -0.5,  3, -0.5,
                      0, -0.5,  0 ]
        });
        
        activeObj.filters.push(filter, contrast, sharpen);
        activeObj.applyFilters();
        canvas.renderAll();
    }

    function applyAIColorize() {
        let activeObj = canvas.getActiveObject();
        if(!activeObj || activeObj.type !== 'image') { alert("Please select a photo first."); return; }
        
        let sepia = new fabric.Image.filters.Sepia();
        let brightness = new fabric.Image.filters.Brightness({ brightness: 0.05 });
        activeObj.filters.push(sepia, brightness);
        activeObj.applyFilters();
        canvas.renderAll();
    }

    // ==========================================
    // 11. TYPOGRAPHY ENGINE
    // ==========================================
    function addText() {
        const color = document.getElementById('textColorPicker').value;
        const font = document.getElementById('fontFamily').value.replace(/['"]/g, '');
        const size = parseInt(document.getElementById('fontSizeSlider').value) || 50;
        
        const text = new fabric.IText('Type here / Text', {
            left: LOGICAL_W / 2, top: LOGICAL_H / 2, originX: 'center', originY: 'center',
            fontFamily: font, fill: color, fontSize: size, fontWeight: 'bold',
            borderColor: '#38bdf8', cornerColor: '#38bdf8', transparentCorners: false, padding: 10,
            stroke: document.getElementById('textStrokeColor').value, strokeWidth: parseFloat(document.getElementById('textStrokeWidth').value)
        });
        
        canvas.add(text); canvas.setActiveObject(text); text.enterEditing(); text.selectAll(); syncTextPanel();
    }

    function updateTextProps() {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'i-text') {
            let newSize = parseInt(document.getElementById('fontSizeSlider').value);
            obj.set({
                fill: document.getElementById('textColorPicker').value,
                backgroundColor: document.getElementById('textBgColorPicker').value,
                fontFamily: document.getElementById('fontFamily').value.replace(/['"]/g, ''),
                fontSize: newSize,
                stroke: document.getElementById('textStrokeColor').value,
                strokeWidth: parseFloat(document.getElementById('textStrokeWidth').value),
                scaleX: 1, scaleY: 1
            });
            document.getElementById('valFontSize').innerText = newSize; canvas.renderAll();
        }
    }

    function clearTextBg() {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'i-text') { obj.set('backgroundColor', ''); canvas.renderAll(); }
    }

    function syncTextPanel() {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'i-text') {
            let currentFont = obj.fontFamily;
            if(currentFont.includes('Gujarati')) currentFont = "'Noto Sans Gujarati', sans-serif";
            else if(currentFont.includes('Hind')) currentFont = "'Hind Vadodara', sans-serif";
            else if(currentFont.includes('Mukta')) currentFont = "'Mukta Vaani', sans-serif";
            else if(currentFont.includes('Rasa')) currentFont = "'Rasa', serif";
            
            try { document.getElementById('fontFamily').value = currentFont; } catch(e){}
            
            let safeSize = Math.round(obj.fontSize * obj.scaleX);
            document.getElementById('fontSizeSlider').value = safeSize; document.getElementById('valFontSize').innerText = safeSize;
            
            document.getElementById('textColorPicker').value = obj.fill || '#000000'; 
            document.getElementById('textBgColorPicker').value = obj.backgroundColor || '#ffffff';
            document.getElementById('textStrokeColor').value = obj.stroke || '#ffffff'; 
            document.getElementById('textStrokeWidth').value = obj.strokeWidth || 0;

            document.getElementById('btnBold').classList.toggle('active', obj.fontWeight === 'bold'); 
            document.getElementById('btnItalic').classList.toggle('active', obj.fontStyle === 'italic'); 
            document.getElementById('btnUnderline').classList.toggle('active', obj.underline); 
            document.getElementById('btnLinethrough').classList.toggle('active', obj.linethrough);

            document.getElementById('btnAlignLeft').classList.toggle('active', obj.textAlign === 'left'); 
            document.getElementById('btnAlignCenter').classList.toggle('active', obj.textAlign === 'center'); 
            document.getElementById('btnAlignRight').classList.toggle('active', obj.textAlign === 'right');
        }
    }

    function toggleTextFormat(format) {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'i-text') {
            if(format === 'bold') { obj.set('fontWeight', obj.fontWeight === 'bold' ? 'normal' : 'bold'); }
            if(format === 'italic') { obj.set('fontStyle', obj.fontStyle === 'italic' ? 'normal' : 'italic'); }
            if(format === 'underline') { obj.set('underline', !obj.underline); }
            if(format === 'linethrough') { obj.set('linethrough', !obj.linethrough); }
            canvas.renderAll(); syncTextPanel();
        }
    }

    function setTextAlign(alignment) {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'i-text') { obj.set('textAlign', alignment); canvas.renderAll(); syncTextPanel(); }
    }

    // ==========================================
    // 12. DRAWING, SHAPES & UPLOADS
    // ==========================================
    function addUploadedImage(e) {
        const file = e.target.files[0];
        if(!file) return;
        const reader = new FileReader();
        reader.onload = function(f) {
            fabric.Image.fromURL(f.target.result, function(img) {
                let scale = 1;
                if(img.width > LOGICAL_W * 0.8) scale = (LOGICAL_W * 0.8) / img.width;
                if(img.height * scale > LOGICAL_H * 0.8) scale = (LOGICAL_H * 0.8) / img.height;
                
                img.set({
                    left: LOGICAL_W / 2, top: LOGICAL_H / 2,
                    originX: 'center', originY: 'center',
                    scaleX: scale, scaleY: scale
                });
                
                if (canvas.getObjects('image').length === 0 && !canvas.backgroundColor) {
                    canvas.backgroundColor = '#ffffff'; 
                    document.getElementById('bgColorPicker').value = '#ffffff';
                }

                canvas.add(img); canvas.setActiveObject(img); canvas.renderAll();
                document.getElementById('fileUploadInput').value = '';
            });
        };
        reader.readAsDataURL(file);
    }
    
    async function removeBackgroundAI() {
        let activeObj = canvas.getActiveObject();
        if(!activeObj || activeObj.type !== 'image') {
            alert("Please select an image on the canvas first.");
            return;
        }

        const btn = document.querySelector('.ai-btn') || document.createElement('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;

        showLoading(true, "AI is processing the background...<br><small style='color:#10b981;'>Please wait, this may take a few seconds.</small>");

        let bgRemovedDataUrl = null;
        const imgDataUrl = activeObj.toDataURL({ format: 'png', multiplier: 1 });

        try {
            const blob = await fetch(imgDataUrl).then(res => res.blob());
            // Attempt edge WebAssembly background removal
            const resultBlob = await (window.imglyBackgroundRemoval ? window.imglyBackgroundRemoval.removeBackground(blob) : window.imglyRemoveBackground(blob));
            bgRemovedDataUrl = URL.createObjectURL(resultBlob);
        } catch (error) {
            console.warn("img.ly native AI failed, falling back to API...", error);
        }

        if(!bgRemovedDataUrl) {
            try {
                const base64Data = imgDataUrl.split(',')[1];
                let apiKey = 'pSqcQaSbGwN4an41dkZSyHAs';
                try {
                    let inputKey = document.getElementById('removeBgApiKey');
                    if (inputKey && inputKey.value) apiKey = inputKey.value;
                    
                    const keyRes = await fetch(APP_URL + 'get_remove_bg_key.php');
                    const keyData = await keyRes.json();
                    if(keyData.api_key) apiKey = keyData.api_key;
                } catch(e) {}

                const reqBody = JSON.stringify({ image_file_b64: base64Data, size: "auto" });

                const response = await fetch('https://api.remove.bg/v1.0/removebg', {
                    method: 'POST',
                    headers: { 'X-Api-Key': apiKey, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: reqBody
                });

                if(!response.ok) throw new Error("API Limit Reached or Network error");
                
                const data = await response.json();
                bgRemovedDataUrl = 'data:image/png;base64,' + data.data.result_b64;
            } catch(apiErr) {
                console.error(apiErr);
                alert("❌ Both Edge AI and Cloud AI failed. Please try a different browser or image.");
                showLoading(false);
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }
        }

        fabric.Image.fromURL(bgRemovedDataUrl, function(newImg) {
            newImg.set({
                originX: 'center', originY: 'center',
                left: activeObj.left, top: activeObj.top,
                scaleX: activeObj.scaleX, scaleY: activeObj.scaleY,
                angle: activeObj.angle,
                cornerColor: '#38bdf8', borderColor: '#38bdf8', transparentCorners: false
            });

            newImg.filters = activeObj.filters || [];
            newImg.applyFilters();

            canvas.remove(activeObj);
            canvas.add(newImg);
            canvas.setActiveObject(newImg);
            canvas.renderAll();
            
            showLoading(false);
            btn.innerHTML = '<i class="fas fa-check"></i> Removed!';
            btn.disabled = false;
            
            setTimeout(() => { btn.innerHTML = originalText; }, 2000);
        });
    }


    function toggleDrawMode() {
        canvas.isDrawingMode = !canvas.isDrawingMode; const btn = document.getElementById('btnDrawMode');
        if (canvas.isDrawingMode) { btn.innerHTML = '<i class="fas fa-times"></i> Stop Drawing'; btn.style.background = '#ef4444'; updateBrush(); } 
        else { btn.innerHTML = '<i class="fas fa-pen"></i> Start Drawing Mode'; btn.style.background = '#10b981'; }
    }
    
    function updateBrush() { if (canvas.isDrawingMode && !isMasking) { canvas.freeDrawingBrush.color = document.getElementById('brushColorPicker').value; canvas.freeDrawingBrush.width = parseInt(document.getElementById('brushSize').value) || 5; } }

    function addShape(type) {
        const color = document.getElementById('shapeColorPicker').value;
        const props = { left: LOGICAL_W/2, top: LOGICAL_H/2, fill: color, originX: 'center', originY: 'center', borderColor: '#38bdf8', cornerColor: '#38bdf8', transparentCorners: false };
        let shape;
        if(type === 'rect') shape = new fabric.Rect({ ...props, width: 150, height: 150 });
        if(type === 'circle') shape = new fabric.Circle({ ...props, radius: 75 });
        if(type === 'triangle') shape = new fabric.Triangle({ ...props, width: 150, height: 150 });
        if(type === 'star') { 
            const points = [{x: 75, y: 0}, {x: 90, y: 52}, {x: 150, y: 52}, {x: 102, y: 85}, {x: 120, y: 142}, {x: 75, y: 109}, {x: 30, y: 142}, {x: 48, y: 85}, {x: 0, y: 52}, {x: 60, y: 52}]; 
            shape = new fabric.Polygon(points, { ...props }); 
        }
        if(type === 'line') { shape = new fabric.Line([0, 0, 300, 0], { left: LOGICAL_W/2, top: LOGICAL_H/2, stroke: color, strokeWidth: 10, originX: 'center', originY: 'center', borderColor: '#38bdf8', cornerColor: '#38bdf8', transparentCorners: false }); }
        
        if(shape) { canvas.add(shape); canvas.setActiveObject(shape); }
    }

    function addCollageGrid(num, type) {
        let w = LOGICAL_W, h = LOGICAL_H;
        let borderW = parseInt(document.getElementById('gridBorder').value) || 10;
        let borderC = document.getElementById('gridBorderColor').value || '#ffffff';
        
        canvas.getObjects().forEach(obj => { if(obj.isGrid) canvas.remove(obj); });
        
        let rects = [];
        if(num === 2 && type === 'vertical') {
            rects.push({l:0, t:0, w:w/2, h:h}); rects.push({l:w/2, t:0, w:w/2, h:h});
        } else if(num === 2 && type === 'horizontal') {
            rects.push({l:0, t:0, w:w, h:h/2}); rects.push({l:0, t:h/2, w:w, h:h/2});
        } else if(num === 3 && type === 'vertical') {
            rects.push({l:0, t:0, w:w/3, h:h}); rects.push({l:w/3, t:0, w:w/3, h:h}); rects.push({l:(w/3)*2, t:0, w:w/3, h:h});
        } else if(num === 4 && type === 'grid') {
            rects.push({l:0, t:0, w:w/2, h:h/2}); rects.push({l:w/2, t:0, w:w/2, h:h/2});
            rects.push({l:0, t:h/2, w:w/2, h:h/2}); rects.push({l:w/2, t:h/2, w:w/2, h:h/2});
        }
        
        rects.forEach(r => {
            let rect = new fabric.Rect({
                left: r.l, top: r.t, width: r.w, height: r.h,
                fill: 'transparent', stroke: borderC, strokeWidth: borderW,
                selectable: false, evented: false, isGrid: true
            });
            canvas.add(rect);
        });
        canvas.renderAll();
    }
    
    function updateGridBorders() {
        let borderW = parseInt(document.getElementById('gridBorder').value) || 10;
        let borderC = document.getElementById('gridBorderColor').value || '#ffffff';
        canvas.getObjects().forEach(obj => { 
            if(obj.isGrid) { obj.set({strokeWidth: borderW, stroke: borderC}); } 
        });
        canvas.renderAll();
    }

    // ==========================================
    // 13. EXPORT HD IMAGE
    // ==========================================
    async function handleExport() {
        if(cropZone) cancelCrop();
        if(isMasking) toggleEraserMask();
        
        // Hide Grid Lines
        canvas.getObjects().forEach(obj => { if(obj.isGrid) obj.visible = false; });
        canvas.discardActiveObject(); canvas.renderAll();

        if (userRole !== 'admin') {
            let confirmMsg = `${currency}${cardCost} will be deducted from wallet to download HD photo.\nDo you want to proceed?`;
            if (!confirm(confirmMsg)) {
                canvas.getObjects().forEach(obj => { if(obj.isGrid) obj.visible = true; }); canvas.renderAll(); return; 
            }
            try {
                let formData = new FormData(); formData.append('service_type', 'Pro Photo Studio Export');
                let response = await fetch(APP_URL + 'deduct_poster_balance.php', { method: 'POST', body: formData });
                let text = await response.text(); let result = JSON.parse(text);
                if (!result.success) { alert("❌ Error: " + result.message); return; }
            } catch (error) { alert("❌ Network error."); return; }
        }

        showLoading(true, "Saving High Quality Masterpiece...");

        setTimeout(() => {
            const currentZoom = canvas.getZoom();
            canvas.setZoom(1); canvas.setWidth(LOGICAL_W); canvas.setHeight(LOGICAL_H);

            const dataUrl = canvas.toDataURL({ format: canvas.backgroundColor ? 'jpeg' : 'png', quality: 1.0, multiplier: 2 });
            
            canvas.setZoom(currentZoom); fitCanvasToScreen();
            canvas.getObjects().forEach(obj => { if(obj.isGrid) obj.visible = true; }); canvas.renderAll();

            const link = document.createElement('a'); link.download = `Studio_Masterpiece_${Date.now()}.${canvas.backgroundColor ? 'jpg' : 'png'}`; link.href = dataUrl; link.click();
            showLoading(false);
        }, 800);
    }
</script>

<script>
    let sysCurrentZoom = 1.0;
    function sysChangeZoom(amount) {
        sysCurrentZoom += amount;
        if(sysCurrentZoom < 0.2) sysCurrentZoom = 0.2;
        if(sysCurrentZoom > 3.0) sysCurrentZoom = 3.0;
        sysApplyZoom();
    }
    function sysResetZoom() {
        sysCurrentZoom = 1.0;
        sysApplyZoom();
    }
    function sysApplyZoom() {
        const targets = document.querySelectorAll('.canvas-container, .a4-page, .card-preview, canvas#mainCanvas');
        targets.forEach(el => {
            el.style.transform = `scale(${sysCurrentZoom})`;
            el.style.transformOrigin = 'top center';
            el.style.transition = 'transform 0.2s ease';
            el.style.marginBottom = '50px'; // Prevent cutoffs when scaled up
        });
    }
</script>
</body>
</html>