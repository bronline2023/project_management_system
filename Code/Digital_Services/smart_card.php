<?php
/**
 * views/smart_card.php
 * MASTER PRO VERSION: Free Manual Selection + Forced 88x54mm HD Output + Zoom & Pan Controls
 */

require_once __DIR__ . '/../../config.php';
require_once MODELS_PATH . 'db.php';

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
    $stmt_rate = $pdo->prepare("SELECT price, points_price FROM digital_service_rates WHERE service_slug = 'smart_card' AND is_active = 1");
    $stmt_rate->execute();
    $rate_data = $stmt_rate->fetch();
    if ($rate_data) {
        $service_rate = (float)$rate_data['price'];
        $points_rate = (int)$rate_data['points_price'];
    }

    $stmt = $pdo->query("SELECT currency_symbol, remove_bg_api_key, app_name, app_logo_url FROM settings LIMIT 1");
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
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>img/br_favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.img.ly/packages/@imgly/background-removal@1.5.5/dist/index.js"></script>
</head>
<body>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>

<style>
    #sidebar { display: none !important; }
    .navbar { display: none !important; }
    #content { padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    .wrapper { margin: 0 !important; padding: 0 !important; }
    body { background-color: #0f172a; overflow: hidden; margin: 0; padding: 0; font-family: 'Inter', 'Segoe UI', sans-serif; }
    
    .studio-wrapper { display: flex; height: 100vh; width: 100vw; background-color: #0f172a; color: #f8fafc; }
    .studio-panel { width: 420px; min-width: 420px; background: #1e293b; display: flex; flex-direction: column; border-right: 1px solid #334155; z-index: 10; height: 100%; box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
    
    .controls-area { flex-grow: 1; overflow-y: auto; padding: 24px; background: #1e293b; }
    .workspace { flex-grow: 1; display: flex; justify-content: center; align-items: center; overflow: hidden; background: #0f172a; background-image: radial-gradient(#334155 1px, transparent 0); background-size: 30px 30px; position: relative; }
    
    .control-box { background: rgba(30, 41, 59, 0.5); padding: 20px; border-radius: 16px; margin-bottom: 20px; border: 1px solid #334155; backdrop-filter: blur(10px); transition: 0.3s; }
    .control-box:hover { border-color: #3b82f6; background: rgba(30, 41, 59, 0.8); }
    .control-title { font-weight: 800; font-size: 15px; color: #3b82f6; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .form-label { font-size: 11px; font-weight: 700; color: #94a3b8; margin-bottom: 8px; display: block; text-transform: uppercase; }
    .form-control { width: 100%; padding: 12px; margin-bottom: 15px; border-radius: 10px; border: 1px solid #334155; background: #0f172a; color: white; font-size: 14px; transition: 0.3s; }
    .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
    
    .btn-action { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 14px; border: none; border-radius: 10px; font-weight: 800; font-size: 14px; cursor: pointer; width: 100%; margin-bottom: 12px; transition: 0.3s; box-shadow: 0 4px 15px rgba(59,130,246,0.3); }
    .btn-action:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(59,130,246,0.4); }
    
    .btn-success-action { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 14px; border: none; border-radius: 10px; font-weight: 800; font-size: 14px; cursor: pointer; width: 100%; box-shadow: 0 4px 15px rgba(16,185,129,0.3); }
    
    .action-btns { padding: 24px; background: #1e293b; border-top: 1px solid #334155; }
    .btn-export { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 16px; border: none; border-radius: 12px; font-weight: 800; font-size: 16px; cursor: pointer; width: 100%; box-shadow: 0 4px 20px rgba(245,158,11,0.3); transition: 0.3s; }
    .btn-export:hover { transform: scale(1.02); }
    
    .scanner-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.9); z-index: 100; display: none; flex-direction: column; justify-content: center; align-items: center; color: #3b82f6; font-weight: 800; font-size: 24px; backdrop-filter: blur(5px); }
    .scanner-line { width: 80%; height: 4px; background: #3b82f6; box-shadow: 0 0 30px #3b82f6; animation: scan 2s infinite linear; margin-top: 30px; border-radius: 10px; }
    @keyframes scan { 0% { opacity: 0; transform: translateY(-150px); } 50% { opacity: 1; } 100% { opacity: 0; transform: translateY(150px); } }

    .legal-notice { font-size: 11px; color: #b91c1c; background: #fef2f2; padding: 10px; border-left: 3px solid #dc2626; border-radius: 4px; margin-top: 15px; line-height: 1.6; font-weight: 500; text-align: justify; }

    /* 🚀 ZOOM CONTROLS UI 🚀 */
    .zoom-controls { position: absolute; bottom: 20px; right: 20px; background: rgba(255,255,255,0.9); padding: 10px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); display: flex; flex-direction: column; gap: 8px; z-index: 50; border: 1px solid #cbd5e1; }
    .zoom-btn { background: #f8fafc; border: 1px solid #94a3b8; color: #0f172a; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.2s; }
    .zoom-btn:hover { background: #e2e8f0; }
    .zoom-hint { font-size: 10px; color: #475569; text-align: center; font-weight: bold; max-width: 100px;}

    /* ========================================================== */
    /* 📱 MOBILE RESPONSIVENESS FIXES (Injected by System) 📱      */
    /* ========================================================== */
    @media (max-width: 992px) {
        .studio-wrapper, .builder-wrapper { flex-direction: column !important; height: auto !important; width: 100vw !important; overflow-x: hidden; }
        .studio-panel { width: 100% !important; min-width: 100% !important; height: auto !important; max-height: 55vh; overflow-y: auto; border-right: none !important; border-bottom: 2px solid #cbd5e1; }
        .workspace { width: 100% !important; height: 45vh !important; min-height: 45vh !important; padding: 10px !important; overflow-y: auto; }
        .studio-canvas-layout { max-width: 100% !important; height: auto !important; margin: 0 auto; }
        canvas { max-width: 100% !important; height: auto !important; }
        /* Scale down Previews */
        .a4-page, .card-preview { max-width: 100%; transform: scale(0.65) !important; transform-origin: top center !important; margin-bottom: 0 !important; }
        .mobile-gap { margin-bottom: 60px; }
        .action-btns { flex-wrap: wrap; justify-content: center; width: 100%; }
        .btn-export { width: 100%; margin-top: 10px; }
    }

    /* 🚀 NEW CATEGORY SELECTION UI 🚀 */
    /* 🚀 PREMIUM CATEGORY SELECTION UI 🚀 */
    .category-selection-overlay {
        position: fixed; top: 65px; left: 0; width: 100%; height: calc(100vh - 65px);
        background: radial-gradient(circle at center, rgba(30, 41, 59, 1) 0%, rgba(15, 23, 42, 1) 100%); 
        z-index: 999; overflow-y: auto; padding: 40px 20px;
        display: flex; flex-direction: column; align-items: center;
        backdrop-filter: blur(25px);
    }
    .category-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 30px; max-width: 1400px; width: 100%; margin-top: 40px;
        padding-bottom: 80px;
    }
    .category-card {
        background: rgba(30, 41, 59, 0.4); border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 28px; padding: 40px 25px; cursor: pointer; transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        text-align: center; backdrop-filter: blur(20px); position: relative; overflow: hidden;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    .category-card:hover {
        transform: translateY(-15px) scale(1.03); background: rgba(59, 130, 246, 0.12);
        border-color: rgba(59, 130, 246, 0.6); box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.6);
    }
    .category-card::after {
        content: ''; position: absolute; inset: 0; border-radius: 28px;
        padding: 2px; background: linear-gradient(135deg, rgba(59, 130, 246, 0.6), transparent, rgba(16, 185, 129, 0.6));
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor; mask-composite: exclude;
        opacity: 0; transition: 0.6s;
    }
    .category-card:hover::after { opacity: 1; }
    
    .category-logo {
        height: 100px; width: 100%; margin-bottom: 25px; object-fit: contain;
        filter: drop-shadow(0 15px 20px rgba(0,0,0,0.4)); transition: 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .category-card:hover .category-logo { transform: scale(1.2) rotate(2deg); filter: drop-shadow(0 20px 40px rgba(59,130,246,0.5)); }
    
    .category-title { font-size: 22px; font-weight: 900; color: #f8fafc; margin-bottom: 12px; letter-spacing: -0.8px; }
    .category-desc { font-size: 14px; color: #94a3b8; line-height: 1.6; font-weight: 500; opacity: 0.8; }
    
    .category-badge {
        position: absolute; top: 25px; right: 25px; font-size: 11px; font-weight: 900;
        background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; 
        padding: 6px 14px; border-radius: 40px; text-transform: uppercase;
        box-shadow: 0 6px 15px rgba(59,130,246,0.4); letter-spacing: 1.5px;
    }
    .selection-header { text-align: center; margin-bottom: 20px; max-width: 900px; }
    .selection-header h1 { 
        font-size: 52px; font-weight: 1000; margin-bottom: 20px;
        background: linear-gradient(to right, #fff 20%, #60a5fa 50%, #fff 80%);
        background-size: 200% auto; -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        animation: glow 4s linear infinite; letter-spacing: -2px;
        text-shadow: 0 10px 30px rgba(59, 130, 246, 0.2);
    }
    @keyframes glow { to { background-position: 200% center; } }
    .selection-header p { font-size: 20px; color: #cbd5e1; font-weight: 600; text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
    
    .sidebar-logo { height: 35px; width: auto; object-fit: contain; border-radius: 4px; }
    .btn-back-selection {
        font-size: 11px; color: #3b82f6; cursor: pointer; font-weight: 800;
        text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 6px;
        transition: 0.3s; margin-top: 8px; padding: 4px 10px; border-radius: 8px; background: rgba(59,130,246,0.1); width: fit-content;
    }
    .btn-back-selection:hover { background: rgba(59,130,246,0.2); transform: translateX(-4px); color: #60a5fa; }
</style>

<?php $page_title = 'Smart PVC Studio'; require_once INCLUDES_PATH.'digital_header.php'; ?>
<div class="studio-wrapper" style="height: calc(100vh - 65px); min-height: calc(100vh - 65px);">
    <div class="studio-panel">
        
        <div class="controls-area">
            
            <div id="step1Controls">
                <div class="control-box">
                    <span class="control-title"><i class="fas fa-file-invoice"></i> 1. Document Intel</span>
                    <label class="form-label">Selected Card Format</label>
                    <div id="selectedFormatDisplay" class="p-3 mb-3 rounded border border-primary bg-primary bg-opacity-10 d-flex align-items-center gap-3" style="background: rgba(59, 130, 246, 0.05) !important; border: 1px solid rgba(59, 130, 246, 0.3) !important;">
                        <img id="selectedLogo" src="<?= BASE_URL ?>assets/img/logos/aadhaar.png" class="sidebar-logo">
                        <div class="flex-grow-1">
                            <div id="selectedTitle" class="fw-bold text-white small">Aadhaar Card (New 2026)</div>
                            <div class="btn-back-selection" onclick="showCategorySelection()">
                                <i class="fas fa-arrow-left"></i> Change Format
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="cardType" value="aadhaar_2026">

                    <label class="form-label mt-2">Upload Original PDF/Image</label>
                    <input type="file" id="pdfFile" class="form-control" accept="application/pdf, image/*">

                    <div id="passwordBlock" style="display:none; transition: 0.3s;">
                        <label class="form-label text-warning">PDF Password</label>
                        <input type="password" id="pdfPassword" class="form-control border-warning" placeholder="e.g. RAHU1995">
                    </div>

                    <button class="btn-action mt-2" onclick="loadDocument()"><i class="fas fa-microchip me-2"></i> LOAD & AI SCAN</button>
                </div>
                
                <div class="control-box" id="cropAdjustBox" style="display:none; border-color:#f59e0b;">
                    <span class="control-title text-warning"><i class="fas fa-expand-arrows-alt"></i> Fine Tune Borders</span>
                    <p style="font-size:11px; color:#94a3b8; line-height: 1.6;">
                        AI has auto-detected borders. If not perfect:<br>
                        • <b>Zoom:</b> Mouse Wheel<br>
                        • <b>Move View:</b> Alt + Drag Mouse<br>
                        • <b>Adjust:</b> Drag Red/Blue Box corners
                    </p>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-warning flex-grow-1" onclick="autoDetectLayout()"><i class="fas fa-search"></i> Re-Scan Layout</button>
                        <button class="btn-success-action flex-grow-1" style="padding: 8px;" onclick="proceedToStep2()"><i class="fas fa-check-circle me-1"></i> Confirm ➔</button>
                    </div>
                </div>
            </div>

            <div id="step2Controls" style="display:none;">
                <div class="control-box" style="border-color:#3b82f6;">
                    <span class="control-title text-primary">Step 2: Printing Details (Optional)</span>
                    <p style="font-size:11px; color:#64748b;">Only mobile number can be added for customer contact. Changing any other government details or changing the photo is illegal.</p>
                    
                    <button class="btn-action mt-2" onclick="addMobileNumber()"><i class="fas fa-phone-alt"></i> Add mobile number</button>
                    <button class="btn-action mt-2 btn-danger-lite" onclick="removeBgFromSelected()"><i class="fas fa-magic"></i> AI Remove Background from Photo</button>

                    <div id="textEditControls" style="display:none; background:#f8fafc; padding:10px; border-radius:6px; border:1px solid #e2e8f0; margin-top:10px;">
                        <label class="form-label">Color</label>
                        <input type="color" id="textColor" class="form-control" style="height:30px; padding:0;" value="#000000" oninput="updateSelectedText()">
                        <button class="btn btn-sm btn-outline-danger w-100 mt-2" onclick="deleteSelected()"><i class="fas fa-trash"></i> Delete the number</button>
                    </div>
                </div>

                <div class="control-box">
                    <label class="form-label">Card Download Format (Layout)</label>
                    <select class="form-control fw-bold text-success" id="exportFormat">
                        <option value="separate">Different (Front & Back JPG)</option>
                        <option value="4x6">4x6 size photo (both sides in same paper – for print)</option>
                        <option value="A4">A4 paper (both sides in same paper – for print)</option>
                    </select>

                    <div class="form-check mt-3 mb-2 px-0 bg-light p-2 rounded border">
                        <label class="form-check-label d-flex align-items-center cursor-pointer" style="cursor:pointer;">
                            <input type="checkbox" id="addAadharText" class="form-check-input me-2 ms-1" style="transform: scale(1.3);">
                            <span class="fw-bold text-danger" style="font-size:13px;">Add Aadhaar New Format Text (Apply Consent Text)</span>
                        </label>
                        <p class="text-muted mb-0 ms-4 mt-1" style="font-size:10px;">If there is an old Aadhaar then as per the new format "Aadhaar is proof of identity..." will be printed.</p>
                    </div>
                </div>
            </div>
            
            <canvas id="pdfRenderCanvas" style="display:none;"></canvas>

            <div class="legal-notice">
                <b>Special Legal Note:</b> This software only fetches the original details of the government PDF. 
                According to Indian government law, it is an offense to change the photo or tamper with the original text in any government document. This system does not allow any editing in the original PDF.
            </div>
        </div>

        <div class="action-btns" id="downloadBlock" style="display:none;">
            <?php if(isset($_SESSION['user_id'])): ?>
            <button class="btn-main" style="width: 100%; background: linear-gradient(135deg, #3b82f6, #2563eb); font-size: 14px; border-radius: 10px; margin-bottom: 10px; padding: 12px; color: white; border: none; font-weight: bold; cursor: pointer;" onclick="saveDraft(this)"><i class="fas fa-save"></i> Save Draft</button>
            <?php endif; ?>
            <button class="btn-export" onclick="handleExport()"><i class="fas fa-download"></i> Save and download <?= (!isset($_SESSION['user_id']) && isset($_COOKIE['guest_service_used'])) ? '' : '('.$currency.$service_rate.')' ?></button>
        </div>
    </div>

    <div class="workspace" id="workspaceContainer">
    <div class="sys-zoom-controls" style="position: absolute; bottom: 20px; right: 20px; background: rgba(255,255,255,0.95); padding: 8px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); display: flex; flex-direction: column; gap: 5px; z-index: 9999; border: 1px solid #cbd5e1;">
        <div style="font-size: 10px; font-weight: bold; color: #475569; text-align: center; margin-bottom: 2px;">ZOOM</div>
        <button type="button" onclick="sysChangeZoom(0.1)" style="background: #f1f5f9; border: 1px solid #94a3b8; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-weight: bold; transition: 0.2s;">➕</button>
        <button type="button" onclick="sysResetZoom()" style="background: #f1f5f9; border: 1px solid #94a3b8; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-size: 10px; font-weight: bold; transition: 0.2s;">100%</button>
        <button type="button" onclick="sysChangeZoom(-0.1)" style="background: #f1f5f9; border: 1px solid #94a3b8; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-weight: bold; transition: 0.2s;">➖</button>
    </div>
    <div class="studio-canvas-layout" style="margin: 0 auto; overflow: visible;">
        <canvas id="mainCanvas"></canvas>
    </div>
</div>

</div>

<!-- 🚀 CATEGORY SELECTION OVERLAY 🚀 -->
<div id="categorySelection" class="category-selection-overlay">
    <div class="selection-header">
        <div class="d-flex justify-content-center gap-3 mb-4">
            <a href="<?= $back_url ?>" class="btn btn-outline-light rounded-pill px-4 btn-sm fw-bold border-opacity-25" style="border-color: rgba(255,255,255,0.2); background: rgba(255,255,255,0.05);">
                <i class="fas fa-arrow-left me-2"></i> Dashboard
            </a>
            <button class="btn btn-outline-danger rounded-pill px-4 btn-sm fw-bold border-opacity-25" onclick="document.getElementById('categorySelection').style.display='none'" id="closeSelectBtn" style="display:none; border-color: rgba(239,68,68,0.2); background: rgba(239,68,68,0.05);">
                <i class="fas fa-times me-2"></i> Close
            </button>
        </div>
        <h1 class="text-white">Select Smart Card Format</h1>
        <p class="text-muted">Choose the type of government document you want to convert into a PVC smart card.</p>
    </div>

    <div class="category-grid">
        <div class="category-card" onclick="selectCategory('aadhaar_2026', 'Aadhaar Card (New 2026)', '<?= BASE_URL ?>assets/img/logos/aadhaar.png')">
            <div class="category-badge">Recommended</div>
            <img src="<?= BASE_URL ?>assets/img/logos/aadhaar.png" class="category-logo" alt="Aadhaar">
            <div class="category-title">Aadhaar (2026)</div>
            <div class="category-desc">Latest e-Aadhaar PDF format with automated AI cropping.</div>
        </div>

        <div class="category-card" onclick="selectCategory('pan_new', 'e-PAN Card', '<?= BASE_URL ?>assets/img/logos/pan.png')">
            <img src="<?= BASE_URL ?>assets/img/logos/pan.png" class="category-logo" alt="e-PAN">
            <div class="category-title">e-PAN Card</div>
            <div class="category-desc">New NSDL/UTI e-PAN PDF format segmentation.</div>
        </div>

        <div class="category-card" onclick="selectCategory('voter_color', 'Voter ID Card', '<?= BASE_URL ?>assets/img/logos/voter_id.png')">
            <img src="<?= BASE_URL ?>assets/img/logos/voter_id.png" class="category-logo" alt="Voter ID">
            <div class="category-title">Voter ID</div>
            <div class="category-desc">Modern Smart Voter ID (EPIC) PDF format.</div>
        </div>

        <div class="category-card" onclick="selectCategory('ayushman_v2', 'Ayushman Card', '<?= BASE_URL ?>assets/img/logos/ayushman.png')">
            <img src="<?= BASE_URL ?>assets/img/logos/ayushman.png" class="category-logo" alt="Ayushman">
            <div class="category-title">Ayushman Bharat</div>
            <div class="category-desc">PMJAY Gold Card v2 conversion for PVC printing.</div>
        </div>

        <div class="category-card" onclick="selectCategory('janaadhar', 'Jan Aadhaar Card', '<?= BASE_URL ?>assets/img/logos/janaadhar.png')">
            <div class="category-badge">Rajasthan</div>
            <img src="<?= BASE_URL ?>assets/img/logos/janaadhar.png" class="category-logo" alt="Jan Aadhaar">
            <div class="category-title">Jan Aadhaar</div>
            <div class="category-desc">Rajasthan state identity card PDF cropping.</div>
        </div>

        <div class="category-card" onclick="selectCategory('abha', 'ABHA Health Card', '<?= BASE_URL ?>assets/img/logos/abha.png')">
            <img src="<?= BASE_URL ?>assets/img/logos/abha.png" class="category-logo" alt="ABHA">
            <div class="category-title">ABHA Card</div>
            <div class="category-desc">Ayushman Bharat Health Account identity card.</div>
        </div>

        <div class="category-card" onclick="selectCategory('uan_epfo', 'UAN / EPFO Card', '<?= BASE_URL ?>assets/img/logos/uan.png')">
            <img src="<?= BASE_URL ?>assets/img/logos/uan.png" class="category-logo" alt="UAN">
            <div class="category-title">UAN Card</div>
            <div class="category-desc">EPFO Universal Account Number card cropping.</div>
        </div>

        <div class="category-card" onclick="selectCategory('ration_card', 'Ration Card', '<?= BASE_URL ?>assets/img/logos/ration.png')">
            <img src="<?= BASE_URL ?>assets/img/logos/ration.png" class="category-logo" alt="Ration Card">
            <div class="category-title">Ration Card</div>
            <div class="category-desc">Universal layout for National Food Security cards.</div>
        </div>

        <div class="category-card" onclick="selectCategory('driving_license', 'Driving License', '<?= BASE_URL ?>assets/img/logos/dl_emblem.png')">
            <img src="<?= BASE_URL ?>assets/img/logos/dl_emblem.png" class="category-logo" alt="Driving License">
            <div class="category-title">Driving License</div>
            <div class="category-desc">High-quality DL scan or digital document conversion.</div>
        </div>

        <div class="category-card" onclick="selectCategory('eshram_pro', 'e-Shram HD', '<?= BASE_URL ?>assets/img/logos/eshram.png')">
            <img src="<?= BASE_URL ?>assets/img/logos/eshram.png" class="category-logo" alt="e-Shram">
            <div class="category-title">e-Shram</div>
            <div class="category-desc">Official e-Shram worker card with enterprise HD quality.</div>
        </div>

        <div class="category-card" onclick="selectCategory('other', 'Manual Custom', '<?= BASE_URL ?>assets/img/logos/manual.png')">
            <img src="<?= BASE_URL ?>assets/img/logos/manual.png" class="category-logo" style="filter: brightness(0.8);" alt="Manual">
            <div class="category-title">Manual / Other</div>
            <div class="category-desc">For any other document types with manual crop adjustment.</div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.9); z-index:9999; justify-content:center; align-items:center; flex-direction:column; color:white;">
    <div class="spinner-border text-primary" style="width:3rem; height:3rem; border: 4px solid #38bdf8; border-top: 4px solid transparent; border-radius: 50%; animation: spin 1s linear infinite;" role="status"></div>
    <div id="loadingText" class="mt-3 fw-bold" style="font-size:18px;">Processing...</div>
</div>

<style>
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<script>
    const APP_URL = '<?= APP_URL ?>';
    const baseUrl = '<?= BASE_URL ?>';
    const userRole = '<?= $_SESSION['user_role'] ?? 'guest' ?>';
    const currency = '<?= $currency ?>';
    const serviceRate = <?= $service_rate ?>;
    const pointsRate = <?= $points_rate ?>;
    const userBalance = <?= $user_balance ?>;
    const userPoints = <?= $user_points ?>;
    const isCustomRate = <?= $is_custom_rate ? 'true' : 'false' ?>;
    const customRate = <?= $custom_poster_rate ?>;
    const cardCost = serviceRate;
    
    // Standard ID Card: 85.6mm x 54mm (CR80) or 88mm x 54mm depending on the config
    const CARD_ASPECT_RATIO = 1040 / 638; 
    const EXPORT_WIDTH = 1040; 
    const EXPORT_HEIGHT = 638;

    let canvas = null;
    let frontCropBox = null, backCropBox = null;
    let loadedImageObj = null;
    let frontCardObj = null, backCardObj = null;

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

    // Cross-Domain Worker Fix for local/remote environments
    const pdfWorkerUrl = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
    const workerBlob = new Blob([`importScripts('${pdfWorkerUrl}');`], { type: 'text/javascript' });
    pdfjsLib.GlobalWorkerOptions.workerPort = new Worker(URL.createObjectURL(workerBlob));

    // Category Selection Logic
    function selectCategory(type, title, logoUrl) {
        document.getElementById('cardType').value = type;
        document.getElementById('selectedTitle').innerText = title;
        document.getElementById('selectedLogo').src = logoUrl;
        document.getElementById('categorySelection').style.display = 'none';
        
        // If an image was already loaded, re-detect layout
        if (loadedImageObj) {
            autoDetectLayout();
        }
    }

    function showCategorySelection() {
        const el = document.getElementById('categorySelection');
        if (el) {
            el.style.display = 'flex';
            el.scrollTop = 0; // 🚀 Force scroll to top
            
            // Show close button if we already have a file or it's a re-selection
            if(document.getElementById('cardType').value) {
                document.getElementById('closeSelectBtn').style.display = 'inline-block';
            }
        }
    }

    function updateSelectedText() {
        const obj = canvas.getActiveObject();
        if (obj && obj.type === 'text') {
            obj.set({ fill: document.getElementById('textColor').value }); canvas.renderAll();
        }
    }

    async function removeBgFromSelected() {
        const obj = canvas.getActiveObject();
        if (!obj || obj.type !== 'image') { 
            Swal.fire({ icon: 'warning', title: 'Oops...', text: 'Please select the photo on the card first.' });
            return; 
        }
        
        showLoading(true, "AI Removing Background...");
        try {
            // We need to extract the image from the Fabric object
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = obj.width; tempCanvas.height = obj.height;
            const ctx = tempCanvas.getContext('2d');
            ctx.drawImage(obj._element, 0, 0);
            const blob = await new Promise(resolve => tempCanvas.toBlob(resolve, 'image/png'));
            
            const resultBlob = await removeBackgroundAI(blob);
            if (resultBlob) {
                const url = URL.createObjectURL(resultBlob);
                fabric.Image.fromURL(url, function(newImg) {
                    newImg.set({
                        left: obj.left, top: obj.top,
                        scaleX: obj.scaleX, scaleY: obj.scaleY,
                        angle: obj.angle,
                        customId: obj.customId
                    });
                    canvas.remove(obj);
                    canvas.add(newImg);
                    canvas.setActiveObject(newImg);
                    canvas.renderAll();
                    showLoading(false);
                });
            } else {
                showLoading(false);
                Swal.fire({ icon: 'error', title: 'AI Failed', text: 'AI failed to process. Try again or use a high-contrast image.' });
            }
        } catch (err) {
            console.error(err);
            showLoading(false);
            Swal.fire({ icon: 'error', title: 'AI Error', text: err.message });
        }
    }

    async function removeBackgroundAI(blob) {
        // Dual fallback system: WASM then Remove.bg API
        try {
            if (typeof imglyRemoveBackground === 'function') {
                return await imglyRemoveBackground(blob);
            }
        } catch (e) { console.warn("Local AI failed, trying API...", e); }

        try {
            const formData = new FormData();
            formData.append('image_file', blob);
            formData.append('size', 'auto');
            const response = await fetch('https://api.remove.bg/v1.0/removebg', {
                method: 'POST',
                headers: { 'X-Api-Key': '<?= isset($settings['remove_bg_api_key']) ? $settings['remove_bg_api_key'] : '' ?>' },
                body: formData
            });
            if (response.ok) return await response.blob();
        } catch (e) { console.error("API AI failed", e); }
        return null;
    }

    // 💾 DRAFT ENGINE 💾
    function saveDraft(btn) {
        if (!canvas) return;
        const originalHtml = btn.innerHTML; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...'; btn.disabled = true;

        const json = JSON.stringify(canvas.toJSON(['customId', 'fontSize', 'fontFamily', 'fill', 'opacity', 'fontWeight', 'stroke', 'strokeWidth']));
        const formData = new FormData();
        formData.append('service_slug', 'smart_card');
        formData.append('service_name', 'Smart PVC Studio');
        formData.append('json', json);

        fetch(APP_URL + 'save_digital_draft.php', { method: 'POST', body: formData })
        .then(res => res.json()).then(data => {
            if (data.success) alert('✅ Draft saved successfully!');
            else alert('❌ Error: ' + (data.error || 'Unknown error'));
        })
        .catch(err => alert('❌ Network Error.'))
        .finally(() => { btn.innerHTML = originalHtml; btn.disabled = false; });
    }

    // Load Draft Data (Logic moved inside window.load below)
    const draftJson = <?= $loaded_draft_json ? $loaded_draft_json : 'null' ?>;

    window.addEventListener('load', function() {
        // 🚀 CRITICAL FIX: Initialize Canvas First
        canvas = new fabric.Canvas('mainCanvas', {
            width: 1200, height: 800,
            backgroundColor: '#0f172a',
            preserveObjectStacking: true,
            selectionColor: 'rgba(59, 130, 246, 0.1)',
            selectionBorderColor: '#3b82f6',
            selectionLineWidth: 1
        });
        frontCropBox = new fabric.Rect({
            fill: 'rgba(239, 68, 68, 0.2)', stroke: '#ef4444', strokeWidth: 3,
            cornerColor: '#ef4444', cornerSize: 12, transparentCorners: false,
            left: 50, top: 100, width: 350, height: 350 / CARD_ASPECT_RATIO,
            visible: false, objectCaching: false, hasRotatingPoint: false,
            strokeDashArray: [5, 5]
        });
        
        backCropBox = new fabric.Rect({
            fill: 'rgba(59, 130, 246, 0.2)', stroke: '#3b82f6', strokeWidth: 3,
            cornerColor: '#3b82f6', cornerSize: 12, transparentCorners: false,
            left: 450, top: 100, width: 350, height: 350 / CARD_ASPECT_RATIO,
            visible: false, objectCaching: false, hasRotatingPoint: false,
            strokeDashArray: [5, 5]
        });

        // Enforce aspect ratio and modern styling
        const lockSettings = {
            lockUniScaling: true, uniformScaling: true,
            cornerColor: '#3b82f6', cornerSize: 10, transparentCorners: false,
            borderColor: '#3b82f6', cornerStrokeColor: '#ffffff',
            hasRotatingPoint: false
        };
        
        frontCropBox.set(lockSettings);
        frontCropBox.set({ 
            fill: 'rgba(59, 130, 246, 0.1)', 
            stroke: '#3b82f6', 
            strokeDashArray: [5, 5],
            label: 'FRONT'
        });
        
        backCropBox.set(lockSettings);
        backCropBox.set({ 
            fill: 'rgba(16, 185, 129, 0.1)', 
            stroke: '#10b981', 
            strokeDashArray: [5, 5],
            label: 'BACK'
        });

        // Aspect ratio forcing on scale
        // Aspect ratio forcing on scale
        const forceRatio = (e) => {
            const obj = e.target;
            if (obj === frontCropBox || obj === backCropBox) {
                const ratio = CARD_ASPECT_RATIO;
                if (Math.abs(obj.width / obj.height - ratio) > 0.01) {
                    obj.set({ height: obj.width / ratio });
                }
            }
        };
        canvas.on('object:scaling', forceRatio);

        canvas.add(frontCropBox, backCropBox);
        setupZoomAndPan();

        // 🚀 Restore Draft IF Exists
        if (draftJson) {
            showLoading(true, "Restoring Draft...");
            canvas.loadFromJSON(draftJson, function() {
                canvas.renderAll();
                showLoading(false);
                if (canvas.getObjects().length > 0) {
                    document.getElementById('step1Controls').style.display = 'none';
                    document.getElementById('step2Controls').style.display = 'block';
                    document.getElementById('downloadBlock').style.display = 'block';
                    
                    frontCardObj = canvas.getObjects().find(o => o.customId === 'front_card');
                    backCardObj = canvas.getObjects().find(o => o.customId === 'back_card');
                }
            });
        }

        // 🚀 Initialize Event Listeners
        canvas.on('selection:created', onObjectSelected);
        canvas.on('selection:updated', onObjectSelected);
        canvas.on('selection:cleared', function() { document.getElementById('textEditControls').style.display = 'none'; });
    });

    function onObjectSelected(opt) {
        if (opt.target && opt.target.type === 'text') {
            document.getElementById('textEditControls').style.display = 'block';
            document.getElementById('textColor').value = opt.target.fill;
        } else {
            document.getElementById('textEditControls').style.display = 'none';
        }
    }

    function addMobileNumber() {
        const text = new fabric.IText('Mo. +91 ', {
            left: canvas.width/2 - 100, top: 150, 
            fontSize: 22, fontFamily: 'Arial', fontWeight: 'bold', 
            fill: '#000000', backgroundColor: '#ffffff',
            padding: 5, cornerSize: 8,
            selectable: true, customId: 'mobile_num'
        });
        canvas.add(text); canvas.setActiveObject(text); canvas.renderAll();
    }

    function showLoading(show, txt = "Processing...") {
        document.getElementById('loadingText').innerText = txt;
        document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
    }

    async function loadDocument() {
        const fileInput = document.getElementById('pdfFile');
        if(!fileInput.files || fileInput.files.length === 0) { alert('Please upload a PDF or Image.'); return; }
        
        const file = fileInput.files[0];
        const isPdf = file.type === 'application/pdf';
        
        showLoading(true, "AI Scanning Document...");
        document.getElementById('passwordBlock').style.display = isPdf ? 'block' : 'none';

        if(isPdf) {
            const password = document.getElementById('pdfPassword').value;
            try {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer, password: password });
                    
                    loadingTask.promise.then(async function(pdf) {
                        try {
                            const page = await pdf.getPage(1);
                            const viewport = page.getViewport({ scale: 3.0 }); 
                            
                            const renderCanvas = document.getElementById('pdfRenderCanvas');
                            renderCanvas.width = viewport.width;
                            renderCanvas.height = viewport.height;
                            
                            const renderContext = renderCanvas.getContext('2d');
                            await page.render({ canvasContext: renderContext, viewport: viewport }).promise;
                            
                            if(typeof sysResetZoom === 'function') sysResetZoom();
                            const dataUrl = renderCanvas.toDataURL('image/jpeg', 1.0);
                            showLoading(false);
                            loadIntoWorkspace(dataUrl);
                        } catch(pageErr) {
                            showLoading(false);
                            console.error(pageErr);
                            alert("Failed to render PDF page. " + pageErr.message);
                        }
                    }).catch(function(error) {
                        showLoading(false);
                        console.error("PDF Load Error:", error);
                        if(error.name === 'PasswordException') {
                            document.getElementById('passwordBlock').style.display = 'block';
                            alert('This PDF is password protected. Please enter the password and try again.');
                        } else {
                            alert('Could not render PDF. ' + error.message);
                        }
                    });
                };
                reader.onerror = function(err) { showLoading(false); alert("File reading error."); };
                reader.readAsArrayBuffer(file);
            } catch(e) { 
                showLoading(false); 
                console.error(e);
                alert("Critical failure during PDF extraction.");
            }
        } else {
            const reader = new FileReader();
            reader.onload = function(e) {
                showLoading(false);
                loadIntoWorkspace(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    }

    function loadIntoWorkspace(dataUrl) {
        fabric.Image.fromURL(dataUrl, function(img) {
            if (loadedImageObj) canvas.remove(loadedImageObj);
            loadedImageObj = img;
            
            const scaleX = (canvas.width - 60) / img.width;
            const scaleY = (canvas.height - 60) / img.height;
            const scale = Math.min(scaleX, scaleY);

            img.set({ 
                originX: 'center', originY: 'center', 
                left: canvas.width / 2, top: canvas.height / 2, 
                scaleX: scale, scaleY: scale, 
                selectable: false, evented: false 
            });

            canvas.add(img); canvas.sendToBack(img);
            frontCropBox.set('visible', true); backCropBox.set('visible', true);
            
            autoDetectLayout();
            
            // Bring crops to front
            canvas.bringToFront(frontCropBox);
            canvas.bringToFront(backCropBox);
            
            document.getElementById('cropAdjustBox').style.display = 'block';
        }, { crossOrigin: 'anonymous' });
    }

    // Trigger auto-detect logic
    function triggerAutoDetect() {
        if (frontCropBox && frontCropBox.visible) {
            autoDetectLayout();
        }
    }

    // ==========================================
    // 🚀 ZOOM & PAN (DRAG) ENGINE 🚀
    // ==========================================
    function autoDetectLayout() {
        if (!loadedImageObj) return;
        const type = document.getElementById('cardType').value;
        const iw = loadedImageObj.width * loadedImageObj.scaleX;
        const ih = loadedImageObj.height * loadedImageObj.scaleY;
        const left = loadedImageObj.left - iw/2;
        const top = loadedImageObj.top - ih/2;

        const cardW = iw * 0.42; 
        const cardH = cardW / CARD_ASPECT_RATIO;

        if (type === 'aadhaar_2026') {
            // New 2026 e-Aadhaar format: Usually side-by-side at the bottom with specific offsets
            frontCropBox.set({ left: left + iw * 0.045, top: top + ih * 0.70, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
            backCropBox.set({ left: left + iw * 0.515, top: top + ih * 0.70, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
        } else if (type === 'aadhaar_2024') {
            // Standard 2024 e-Aadhaar layout
            frontCropBox.set({ left: left + iw * 0.05, top: top + ih * 0.655, width: iw * 0.43, height: (iw * 0.43) / CARD_ASPECT_RATIO, visible: true });
            backCropBox.set({ left: left + iw * 0.52, top: top + ih * 0.655, width: iw * 0.43, height: (iw * 0.43) / CARD_ASPECT_RATIO, visible: true });
        } else if (type === 'aadhaar_pvc_official') {
            // Official PVC Scan: Cards are usually side-by-side center
            frontCropBox.set({ left: left + iw * 0.05, top: top + ih * 0.25, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
            backCropBox.set({ left: left + iw * 0.51, top: top + ih * 0.25, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
        } else if (type === 'aadhaar_pvc_stacked') {
            // Official PVC Scan: Stacked vertically
            frontCropBox.set({ left: left + iw * 0.28, top: top + ih * 0.1, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
            backCropBox.set({ left: left + iw * 0.28, top: top + ih * 0.5, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
        } else if (type.startsWith('aadhaar')) {
            // Fallback for older Aadhaar
            frontCropBox.set({ left: left + iw * 0.05, top: top + ih * 0.65, width: cardW, height: cardH, visible: true });
            backCropBox.set({ left: left + iw * 0.53, top: top + ih * 0.65, width: cardW, height: cardH, visible: true });
        } else if (type === 'voter_color') {
            // Voter ID New Format: Front and Back are usually side-by-side in the top half
            frontCropBox.set({ left: left + iw * 0.04, top: top + ih * 0.05, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
            backCropBox.set({ left: left + iw * 0.52, top: top + ih * 0.05, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
        } else if (type === 'pan_new') {
            // PAN Card: Usually at the bottom center
            frontCropBox.set({ left: left + iw * 0.28, top: top + ih * 0.75, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
            backCropBox.set({ left: left + iw * 0.28, top: top + ih * 0.85, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
        } else if (type === 'janaadhar') {
            // Jan Aadhaar: Side by side center
            frontCropBox.set({ left: left + iw * 0.05, top: top + ih * 0.25, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
            backCropBox.set({ left: left + iw * 0.51, top: top + ih * 0.25, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
        } else if (type === 'abha') {
            // ABHA: Side by side bottom
            frontCropBox.set({ left: left + iw * 0.05, top: top + ih * 0.60, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
            backCropBox.set({ left: left + iw * 0.51, top: top + ih * 0.60, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
        } else if (type === 'uan_epfo') {
            // UAN: Side by side center
            frontCropBox.set({ left: left + iw * 0.05, top: top + ih * 0.35, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
            backCropBox.set({ left: left + iw * 0.51, top: top + ih * 0.35, width: iw * 0.44, height: (iw * 0.44) / CARD_ASPECT_RATIO, visible: true });
        } else if (type === 'ration_card') {
            // Ration Card: Large front and back
            frontCropBox.set({ left: left + iw * 0.05, top: top + ih * 0.1, width: iw * 0.9, height: (iw * 0.9) / CARD_ASPECT_RATIO, visible: true });
            backCropBox.set({ left: left + iw * 0.05, top: top + ih * 0.5, width: iw * 0.9, height: (iw * 0.9) / CARD_ASPECT_RATIO, visible: true });
        } else {
            // Default: Side-by-side center
            frontCropBox.set({ left: left + iw * 0.05, top: top + ih * 0.3, width: cardW, height: cardH, visible: true });
            backCropBox.set({ left: left + iw * 0.53, top: top + ih * 0.3, width: cardW, height: cardH, visible: true });
        }
        
        frontCropBox.setCoords();
        backCropBox.setCoords();
        
        // 🚀 SMART SNAP: Attempt to find actual edges if possible
        smartSnap(frontCropBox);
        smartSnap(backCropBox);
        
        canvas.renderAll();
    }

    function smartSnap(box) {
        if (!loadedImageObj) return;
        // Simple logic: If we're on a PDF, we might be able to find the card edges 
        // by looking for the transition from white to neutral or dashed lines.
        // For now, we'll just ensure they stay within the image bounds.
        const iw = loadedImageObj.width * loadedImageObj.scaleX;
        const ih = loadedImageObj.height * loadedImageObj.scaleY;
        const left = loadedImageObj.left - iw/2;
        const top = loadedImageObj.top - ih/2;

        if (box.left < left) box.set('left', left + 10);
        if (box.top < top) box.set('top', top + 10);
        if (box.left + box.width > left + iw) box.set('left', left + iw - box.width - 10);
        if (box.top + box.height > top + ih) box.set('top', top + ih - box.height - 10);
        
        box.setCoords();
    }

    function setupZoomAndPan() {
        canvas.on('mouse:wheel', function(opt) {
            var delta = opt.e.deltaY;
            var zoom = canvas.getZoom();
            zoom *= 0.999 ** delta;
            if (zoom > 10) zoom = 10;
            if (zoom < 0.2) zoom = 0.2;
            canvas.zoomToPoint({ x: opt.e.offsetX, y: opt.e.offsetY }, zoom);
            opt.e.preventDefault();
            opt.e.stopPropagation();
        });

        canvas.on('mouse:down', function(opt) {
            var evt = opt.e;
            if (evt.altKey === true || !canvas.getActiveObject()) {
                this.isDragging = true;
                this.selection = false;
                this.lastPosX = evt.clientX;
                this.lastPosY = evt.clientY;
            }
        });

        canvas.on('mouse:move', function(opt) {
            if (this.isDragging) {
                var e = opt.e;
                var vpt = this.viewportTransform;
                vpt[4] += e.clientX - this.lastPosX;
                vpt[5] += e.clientY - this.lastPosY;
                this.requestRenderAll();
                this.lastPosX = e.clientX;
                this.lastPosY = e.clientY;
            }
        });

        canvas.on('mouse:up', function(opt) {
            this.setViewportTransform(this.viewportTransform);
            this.isDragging = false;
            this.selection = true;
        });
    }

    async function proceedToStep2() {
        if (!loadedImageObj) return;
        showLoading(true, "Segmenting Cards...");
        
        // 🚀 FIX: Hide crop boxes BEFORE capturing to dataURL to avoid "Color remains in front" issue
        frontCropBox.set('visible', false);
        backCropBox.set('visible', false);
        canvas.renderAll();

        const fBox = frontCropBox.getBoundingRect();
        const bBox = backCropBox.getBoundingRect();

        // High-res crop using multiplier
        const frontDataUrl = canvas.toDataURL({
            left: fBox.left, top: fBox.top, width: fBox.width, height: fBox.height,
            format: 'jpeg', quality: 1, multiplier: 3
        });

        const backDataUrl = canvas.toDataURL({
            left: bBox.left, top: bBox.top, width: bBox.width, height: bBox.height,
            format: 'jpeg', quality: 1, multiplier: 3
        });

        canvas.remove(loadedImageObj);

        fabric.Image.fromURL(frontDataUrl, function(img) {
            frontCardObj = img;
            img.set({ left: 50, top: 100, selectable: true, customId: 'front_card' });
            img.scaleToWidth(400);
            canvas.add(img);
            
            fabric.Image.fromURL(backDataUrl, function(img2) {
                backCardObj = img2;
                img2.set({ left: 500, top: 100, selectable: true, customId: 'back_card' });
                img2.scaleToWidth(400);
                canvas.add(img2);
                
                document.getElementById('step1Controls').style.display = 'none';
                document.getElementById('step2Controls').style.display = 'block';
                document.getElementById('downloadBlock').style.display = 'block';
                
                // 🚀 FIX: If disclaimer was already checked, show it now
                if (document.getElementById('addAadharText').checked) {
                    document.getElementById('addAadharText').dispatchEvent(new Event('change'));
                }

                showLoading(false);
                canvas.renderAll();
            }, { crossOrigin: 'anonymous' });
        }, { crossOrigin: 'anonymous' });
    }

    // ==========================================
    // 🚀 EXPORT: FORCED EXACT 1040x638 SIZE 🚀
    // ==========================================
    // Removed duplicate definitions

    // (Cleaned up late declaration)

    async function triggerDownloadTransaction(finalBlob, finalName) {
        if (!finalBlob) { alert('Processing failed. Please try again.'); return; }

        if (!isGuest && userRole !== 'admin' && userRole !== 'master_admin') {
            let actualCost = serviceRate;
            let willUsePoints = false;
            
            if (actualCost <= 0) {
                 willUsePoints = false;
            } 
            else if (userBalance >= actualCost) {
                let confirmMsg = `${currency}${actualCost} will be deducted from your wallet to download the final result.\nDo you want to proceed?`;
                if (!confirm(confirmMsg)) return;
            } 
            else if (pointsRate > 0 && userPoints >= pointsRate) {
                let confirmMsg = `You don't have enough Wallet Balance, but you have ${userPoints} Points.\n${pointsRate} Points will be deducted to download this result.\nDo you want to proceed?`;
                if (!confirm(confirmMsg)) return;
                willUsePoints = true;
            }
            else {
                alert(`❌ Insufficient Funds.\nYou need ${currency}${actualCost} or ${pointsRate} Points to download this file.`);
                return;
            }

            await triggerWalletAPI(willUsePoints);
        } else if (isGuest) {
            if (!confirm("Confirm using your single free daily guest pass to download this file?")) return;
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
            formData.append('service_slug', 'smart_card');
            formData.append('service_type', 'Smart Card Pro');
            if (willUsePoints) formData.append('use_points', '1');

            let response = await fetch(APP_URL + 'deduct_poster_balance.php', { method: 'POST', body: formData });
            let text = await response.text();
            showLoading(false);
            
            try {
                let result = JSON.parse(text);
                if (!result.success) {
                    alert("❌ Error: " + result.message);
                    throw new Error("Payment failed");
                }
                if (isGuest || result.cost <= 0) {
                    Swal.fire({ icon: 'success', title: 'Success', text: result.message || "✅ Guest pass used!" });
                } else {
                    Swal.fire({ 
                        icon: 'success', 
                        title: 'Success!', 
                        html: `Paid from: <b>${result.deducted_type === 'points' ? result.cost + ' Pts' : currency + result.cost}</b>` 
                    });
                }
            } catch(e) { 
                console.error("JSON Error:", text);
                Swal.fire({ icon: 'error', title: 'Parse Error', text: "❌ API Server parsing failed. Check internet." });
                throw e; 
            }
        } catch(e) { 
            Swal.fire({ icon: 'error', title: 'Network Error', text: "❌ Network error processing wallet." });
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

    async function handleExport() {
        if (!frontCardObj || !backCardObj) return;
        
        if (userRole !== 'admin' && userRole !== 'master_admin') {
            let actualCost = serviceRate;
            let willUsePoints = false;
            
            if (actualCost <= 0) {
                 willUsePoints = false;
            } 
            else if (userBalance >= actualCost) {
                const result = await Swal.fire({
                    title: 'Confirm Purchase',
                    text: `${currency}${actualCost} will be deducted from your wallet to download the final cards.`,
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
                    text: `You don't have enough Wallet Balance, but you have ${userPoints} Points. ${pointsRate} Points will be deducted to run this task.`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Use Points',
                    cancelButtonText: 'Cancel'
                });
                if (!result.isConfirmed) return;
                willUsePoints = true;
            }
            else {
                Swal.fire({ icon: 'error', title: 'Insufficient Funds', text: `You need ${currency}${actualCost} or ${pointsRate} Points to download this file.` });
                return;
            }
            
            try {
                let formData = new FormData();
                formData.append('service_slug', 'smart_card');
                formData.append('service_type', 'Smart Card PVC');
                if (willUsePoints) formData.append('use_points', '1');

                let response = await fetch(APP_URL + 'deduct_poster_balance.php', { method: 'POST', body: formData });
                let text = await response.text(); 
                
                try {
                    let result = JSON.parse(text);
                    if (result.success) {
                        processFinalDownload();
                        Swal.fire({ 
                            icon: 'success', 
                            title: 'Downloaded!', 
                            html: `Successfully processed.<br>Paid from: <b>${result.deducted_type === 'points' ? result.cost + ' Pts' : currency + result.cost}</b>`
                        });
                    } else { 
                        Swal.fire({ icon: 'error', title: 'Transaction Failed', text: result.message });
                    }
                } catch (jsonError) { 
                    Swal.fire({ icon: 'error', title: 'Parse Error', text: 'Server response parsing failed.' });
                }
            } catch (error) { 
                Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not connect to server.' });
            }
        } else {
            let formData = new FormData();
            formData.append('service_slug', 'smart_card');
            formData.append('service_type', 'Smart Card PVC (Admin)');
            fetch(APP_URL + 'deduct_poster_balance.php', { method: 'POST', body: formData });
            processFinalDownload(); 
        }
    }

    function processFinalDownload() {
        canvas.discardActiveObject(); 
        canvas.renderAll();
        const layoutFormat = document.getElementById('exportFormat').value;
        const addAadharText = document.getElementById('addAadharText') ? document.getElementById('addAadharText').checked : false;

        const frontBounds = frontCardObj.getBoundingRect();
        const frontDataUrl = canvas.toDataURL({ format: 'jpeg', quality: 1, left: frontBounds.left, top: frontBounds.top, width: frontBounds.width, height: frontBounds.height, multiplier: 3 });
        
        setTimeout(() => {
            const backBounds = backCardObj.getBoundingRect();
            const backDataUrl = canvas.toDataURL({ format: 'jpeg', quality: 1, left: backBounds.left, top: backBounds.top, width: backBounds.width, height: backBounds.height, multiplier: 3 });
            
            if(layoutFormat === 'separate') {
                resizeAndDownload(frontDataUrl, "Front_SmartCard", addAadharText, false);
                setTimeout(() => { resizeAndDownload(backDataUrl, "Back_SmartCard", false, false); }, 500);
            } else if (layoutFormat === '4x6') {
                processFusedLayout(frontDataUrl, backDataUrl, addAadharText, '4x6');
            } else if (layoutFormat === 'A4') {
                processFusedLayout(frontDataUrl, backDataUrl, addAadharText, 'A4');
            }
        }, 500);
    }

    // 🚀 THE CORE FORCED CONVERTER ENGINE 🚀
    function resizeAndDownload(dataUrl, filename, applyNewAadharText, returnBlobOnly = false) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = function() {
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = EXPORT_WIDTH; 
                tempCanvas.height = EXPORT_HEIGHT;
                const ctx = tempCanvas.getContext('2d');
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = "high";
                ctx.drawImage(img, 0, 0, EXPORT_WIDTH, EXPORT_HEIGHT);
                
                if(applyNewAadharText) {
                    // 🚀 NOTE: Text is now added to the canvas for preview, 
                    // so we don't need to manually draw it here if we're capturing the canvas.
                    // However, for high-res consistency, we'll keep it as a fallback 
                    // or just ensure the canvas objects are captured correctly.
                }

                if(returnBlobOnly) {
                    resolve(tempCanvas);
                } else {
                    const finalData = tempCanvas.toDataURL('image/jpeg', 1.0);
                    const link = document.createElement('a');
                    link.download = filename + '.jpg';
                    link.href = finalData;
                    link.click();
                    
                    let formData = new FormData();
                    formData.append('image_data', finalData);
                    formData.append('document_type', 'Smart PVC Card');
                    fetch(APP_URL + 'save_draft.php', { method: 'POST', body: formData }).catch(e => {});

                    resolve(true);
                }
            };
            img.src = dataUrl;
        });
    }

    // 🚀 PRINT LAYOUT GENERATOR (4x6 or A4) 🚀
    async function processFusedLayout(frontDataUrl, backDataUrl, addAadharText, layout) {
        const frontCanvas = await resizeAndDownload(frontDataUrl, "", addAadharText, true);
        const backCanvas = await resizeAndDownload(backDataUrl, "", false, true);

        const printCanvas = document.createElement('canvas');
        const pCtx = printCanvas.getContext('2d');
        
        let sheetWidth, sheetHeight;
        if(layout === '4x6') {
            sheetWidth = 1200; // 4 inches * 300 DPI
            sheetHeight = 1800; // 6 inches * 300 DPI
        } else {
            sheetWidth = 2480; // A4 Width at 300 DPI
            sheetHeight = 3508; // A4 Height at 300 DPI
        }

        printCanvas.width = sheetWidth;
        printCanvas.height = sheetHeight;
        
        pCtx.fillStyle = "#ffffff";
        pCtx.fillRect(0, 0, sheetWidth, sheetHeight);
        pCtx.imageSmoothingEnabled = true;
        pCtx.imageSmoothingQuality = "high";

        const cardW = 1040;
        const cardH = 638;
        
        const topMargin = (layout === '4x6') ? 150 : 200;
        const xPos = (sheetWidth - cardW) / 2;
        
        pCtx.drawImage(frontCanvas, xPos, topMargin, cardW, cardH);
        
        pCtx.setLineDash([15, 15]);
        pCtx.strokeStyle = "#94a3b8";
        pCtx.lineWidth = 3;
        pCtx.beginPath();
        pCtx.moveTo(xPos - 50, topMargin + cardH + 50);
        pCtx.lineTo(xPos + cardW + 50, topMargin + cardH + 50);
        pCtx.stroke();
        
        pCtx.drawImage(backCanvas, xPos, topMargin + cardH + 100, cardW, cardH);

        pCtx.setLineDash([]);
        pCtx.strokeStyle = "#cbd5e1";
        pCtx.lineWidth = 2;
        pCtx.strokeRect(xPos, topMargin, cardW, cardH);
        pCtx.strokeRect(xPos, topMargin + cardH + 100, cardW, cardH);

        const finalData = printCanvas.toDataURL('image/jpeg', 1.0);
        const link = document.createElement('a');
        link.download = `SmartCard_Print_${layout}.jpg`;
        link.href = finalData;
        link.click();

        let formData = new FormData();
        formData.append('image_data', finalData);
        formData.append('document_type', 'Smart PVC Layout');
        fetch(APP_URL + 'save_draft.php', { method: 'POST', body: formData }).catch(e => {});
    }

    document.getElementById('addAadharText').addEventListener('change', function() {
        if (this.checked) {
            if (!frontCardObj) return;
            // 🚀 Position ON the card (bottom area) with background for visibility
            const disclaimer1 = new fabric.IText('Aadhaar is proof of identity, not of citizenship or date of birth.', {
                originX: 'center', left: frontCardObj.left + frontCardObj.getScaledWidth() / 2, 
                top: frontCardObj.top + frontCardObj.getScaledHeight() * 0.78,
                fontSize: 14, fontFamily: 'Arial', fontWeight: 'bold', fill: '#000000', 
                backgroundColor: 'rgba(255,255,255,0.9)', padding: 3,
                customId: 'aadhar_disclaimer_1'
            });
            const disclaimer2 = new fabric.IText('It should be used with verification (online authentication, or scanning of QR code / offline XML).', {
                originX: 'center', left: frontCardObj.left + frontCardObj.getScaledWidth() / 2, 
                top: frontCardObj.top + frontCardObj.getScaledHeight() * 0.88,
                fontSize: 11, fontFamily: 'Arial', fill: '#334155', 
                backgroundColor: 'rgba(255,255,255,0.9)', padding: 2,
                customId: 'aadhar_disclaimer_2'
            });
            canvas.add(disclaimer1, disclaimer2);
            canvas.bringToFront(disclaimer1);
            canvas.bringToFront(disclaimer2);
            canvas.renderAll();
        } else {
            const objs = canvas.getObjects().filter(o => o.customId === 'aadhar_disclaimer_1' || o.customId === 'aadhar_disclaimer_2');
            objs.forEach(o => canvas.remove(o));
            canvas.renderAll();
        }
    });

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
        const targets = document.querySelectorAll('.studio-canvas-layout, .a4-page, .card-preview, canvas#mainCanvas');
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