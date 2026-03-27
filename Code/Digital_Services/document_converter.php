<?php
require_once CORE_INCLUDES_PATH . 'service_paywall.php';
enforce_service_paywall('document_converter');

/**
 * views/document_converter.php
 * MASTER STUDIO VERSION: Ultimate Magic Clone Patch (Zero Background Issues) + ConvertAPI
 */



$pdo = connectDB();
$card_cost = 5.00; 
$currency = '₹';
$user_role = $_SESSION['user_role'] ?? 'guest';

try {
    $stmt = $pdo->query("SELECT poster_generation_cost, currency_symbol FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings) {
        $card_cost = isset($settings['poster_generation_cost']) ? (float)$settings['poster_generation_cost'] : 5.00;
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
    <link href="https://fonts.googleapis.com/css2?family=Hind+Vadodara:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>

<style>
    /* UI Design */
    #sidebar { display: none !important; }
    .navbar { display: none !important; }
    #content { padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    .wrapper { margin: 0 !important; padding: 0 !important; }
    body { background-color: #0f172a; font-family: 'Inter', 'Segoe UI', sans-serif; overflow: hidden;}
    
    .header-bar { background: #1e293b; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 20px rgba(0,0,0,0.5); position: sticky; top: 0; z-index: 100; border-bottom: 1px solid #334155;}
    .btn-back { background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; text-decoration: none; transition: 0.3s; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);}
    .btn-back:hover { background: #dc2626; transform: translateY(-2px); }
    
    .main-container { padding: 40px; max-width: 1500px; margin: 0 auto; height: calc(100vh - 80px); overflow-y: auto; }
    
    .section-title { font-size: 16px; font-weight: 800; color: #3b82f6; margin-bottom: 25px; margin-top: 30px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 1px;}
    .section-title::after { content: ""; flex: 1; height: 1px; background: #334155; }
    
    .tools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 24px; margin-bottom: 40px; }
    
    .tool-card { background: #1e293b; border-radius: 16px; padding: 30px 20px; text-align: center; cursor: pointer; border: 1px solid #334155; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; overflow: hidden;}
    .tool-card:hover { transform: translateY(-10px); box-shadow: 0 15px 30px rgba(0,0,0,0.4); border-color: #3b82f6; }
    .tool-card::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(59,130,246,0.1), transparent); opacity: 0; transition: 0.3s; }
    .tool-card:hover::before { opacity: 1; }
    
    .tool-icon { font-size: 40px; margin-bottom: 20px; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2)); }
    .tool-title { font-size: 16px; font-weight: 800; color: #f8fafc; margin-bottom: 10px; }
    .tool-desc { font-size: 12px; color: #94a3b8; line-height: 1.5; }

    .icon-red { color: #ef4444; } .icon-blue { color: #3b82f6; } .icon-green { color: #10b981; } .icon-orange { color: #f59e0b; } .icon-purple { color: #8b5cf6; } .icon-dark { color: #334155; }

    #workspaceArea { display: none; background: white; border-radius: 12px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; min-height: 60vh;}
    
    .upload-box { border: 3px dashed #cbd5e1; border-radius: 12px; padding: 50px 20px; background: #f8fafc; cursor: pointer; transition: 0.3s; margin: 20px auto; max-width: 600px;}
    .upload-box:hover { border-color: #3b82f6; background: #eff6ff; }
    .upload-box i { font-size: 50px; color: #94a3b8; margin-bottom: 15px; }
    .upload-box h3 { color: #334155; font-size: 20px; font-weight: bold;}
    
    .btn-convert { background: #16a34a; color: white; padding: 15px 40px; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; margin-top: 20px; box-shadow: 0 4px 10px rgba(22, 163, 74, 0.3); transition: 0.3s;}
    .btn-convert:hover { background: #15803d; transform: translateY(-2px);}
    
    /* 🚀 PDF EDITOR SPECIFIC 🚀 */
    .pdf-toolbar { display: none; background: #1e293b; padding: 15px; border-radius: 8px; margin: 15px auto; max-width: 1000px; justify-content: center; gap: 10px; align-items: center; flex-wrap: wrap;}
    .pdf-tool-btn { background: #334155; color: white; border: 1px solid #475569; padding: 10px 15px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 13px;}
    .pdf-tool-btn:hover, .pdf-tool-btn.active { background: #38bdf8; border-color: #38bdf8; color: #0f172a;}
    .pdf-tool-btn.danger { background: #ef4444; border-color: #ef4444; }
    .pdf-tool-btn.success { background: #10b981; border-color: #10b981; color: white !important;}
    .pdf-tool-btn.success:hover { background: #059669; }
    .pdf-tool-btn.magic { background: linear-gradient(135deg, #8b5cf6, #ec4899); border: none; box-shadow: 0 0 10px rgba(139,92,246,0.5); animation: pulse 2s infinite;}
    
    @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }

    .pdf-canvas-wrapper { display: none; max-width: 100%; overflow: auto; background: #e2e8f0; padding: 20px; border-radius: 8px; margin: 0 auto; border: 2px solid #cbd5e1; height: 65vh; position: relative;}

    .instruction-bar { background: #fef3c7; color: #065f46; padding: 12px; border-radius: 6px; font-weight: bold; font-size: 15px; margin-bottom: 15px; border: 1px solid #a7f3d0; display: none; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05);}

    /* Compressor */
    .config-panel { background: #f1f5f9; padding: 20px; border-radius: 8px; margin: 20px auto; max-width: 800px; text-align: left; display: none; border: 1px solid #cbd5e1;}
    .form-control:focus { border-color: #3b82f6; }
    .preview-box { background: white; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; height: 100%;}
    .preview-box img { max-width: 100%; max-height: 250px; object-fit: contain; margin-top: 10px; border-radius: 4px;}
    
    #loadingOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.9); color: white; display: none; flex-direction: column; justify-content: center; align-items: center; z-index: 4000; text-align: center; padding: 20px;}
    .spinner { border: 5px solid #f3f3f3; border-top: 5px solid #38bdf8; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin-bottom: 15px; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<?php $page_title = 'Pro Document Converter'; require_once INCLUDES_PATH.'digital_header.php'; ?>

<div class="main-container" id="gridArea">
    
    <div class="section-title text-purple">Advanced PDF Tools</div>
    <div class="tools-grid">
        <div class="tool-card" style="border: 2px solid #8b5cf6;" onclick="openTool('edit_pdf', 'Studio PDF Editor', 'Accurately edit any document by matching the original background', 'application/pdf')">
            <i class="fas fa-magic tool-icon icon-purple"></i>
            <div class="tool-title text-purple">Studio PDF Editor</div>
            <div class="tool-desc">100% seamless text replacement using Smart Clone algorithm.</div>
        </div>
    </div>

    <div class="section-title">Office & PDF Converters</div>
    <div class="tools-grid">
        <div class="tool-card" onclick="openTool('word_to_pdf', 'Word to PDF', 'Convert Word (DOCX) files to PDF', '.doc,.docx')">
            <i class="fas fa-file-word tool-icon icon-blue"></i>
            <div class="tool-title">Word to PDF</div>
        </div>
        <div class="tool-card" onclick="openTool('pdf_to_word', 'PDF to Word', 'Create editable Word files from PDF', 'application/pdf')">
            <i class="fas fa-file-pdf tool-icon icon-red"></i>
            <div class="tool-title">PDF to Word</div>
        </div>
        <div class="tool-card" onclick="openTool('excel_to_pdf', 'Excel to PDF', 'Convert Excel (XLSX) files to PDF', '.xls,.xlsx')">
            <i class="fas fa-file-excel tool-icon icon-green"></i>
            <div class="tool-title">Excel to PDF</div>
        </div>
        <div class="tool-card" onclick="openTool('pdf_to_excel', 'PDF to Excel', 'Convert PDF data to Excel (XLSX)', 'application/pdf')">
            <i class="fas fa-file-excel tool-icon icon-green"></i>
            <div class="tool-title">PDF to Excel</div>
        </div>
        <div class="tool-card" onclick="openTool('ppt_to_pdf', 'PPT to PDF', 'Convert Powerpoint files to PDF', '.ppt,.pptx')">
            <i class="fas fa-file-powerpoint tool-icon icon-orange"></i>
            <div class="tool-title">PPT to PDF</div>
        </div>
    </div>

    <div class="section-title text-success">Universal Image Converter</div>
    <div class="tools-grid">
        <div class="tool-card" style="border: 2px solid #10b981;" onclick="openTool('universal_image', 'Universal Image Converter', 'Convert any photo (JPG, PNG, WEBP) to another format', 'image/*')">
            <i class="fas fa-sync-alt tool-icon icon-green"></i>
            <div class="tool-title text-success">Multi-Format Image Converter</div>
            <div class="tool-desc">Seamless offline conversion between JPG, PNG, WEBP.</div>
        </div>
    </div>
    <div class="section-title">Image & Compression Tools</div>
    <div class="tools-grid">
        <div class="tool-card" onclick="openTool('jpg_to_pdf', 'JPG to PDF', 'Create PDF from images (JPG/PNG)', 'image/*')">
            <i class="fas fa-images tool-icon icon-orange"></i>
            <div class="tool-title">JPG to PDF</div>
        </div>
        <div class="tool-card" onclick="openTool('pdf_to_jpg', 'PDF to JPG', 'Extract high-quality images (JPG) from PDF', 'application/pdf')">
            <i class="fas fa-file-pdf tool-icon icon-red"></i>
            <div class="tool-title">PDF to JPG</div>
        </div>
        <div class="tool-card" onclick="openTool('compress_image', 'Compress Image (Size)', 'Reduce photo KB size and check quality', 'image/*')">
            <i class="fas fa-compress-arrows-alt tool-icon icon-purple"></i>
            <div class="tool-title">Compress Size (KB)</div>
        </div>
    </div>
</div>

<div class="main-container" id="workspaceArea">
    <button class="btn-back" style="float: left;" onclick="closeTool()"><i class="fas fa-arrow-left"></i> Go back to Tools</button>
    <div style="clear: both;"></div>

    <h2 id="wsTitle" style="color: #0f172a; font-weight: bold; margin-top: 20px;">Tool Name</h2>
    <p id="wsDesc" style="color: #64748b; font-size: 16px;">Description</p>

    <input type="file" id="fileInput" style="display: none;" onchange="handleFileSelect(event)">
    <div class="upload-box" id="uploadBox" onclick="document.getElementById('fileInput').click()">
        <i class="fas fa-cloud-upload-alt"></i>
        <h3>Click here to Select File</h3>
        <p id="fileNameDisplay" style="color: #38bdf8; font-weight: bold; margin-top: 10px;"></p>
    </div>

    <div class="instruction-bar" id="instructionBar"></div>

    <div class="pdf-toolbar" id="pdfToolbar" style="background: #0f172a; border: 1px solid #334155; margin-bottom: 10px;">
        
        <button class="pdf-tool-btn magic" id="btnMagicClone" onclick="toggleMagicClone()" title="Erase anything flawlessly">
            <i class="fas fa-stamp"></i> 1. Smart Clone
        </button>
        
        <button class="pdf-tool-btn success" id="btnApplyClone" onclick="applyClonePatch()" style="display:none; animation: pulse 1.5s infinite;">
            <i class="fas fa-check-circle"></i> Apply Clone
        </button>

        <div style="width: 1px; height: 30px; background: #334155; margin: 0 10px;"></div>
        
        <button class="pdf-tool-btn" onclick="addPdfText()" title="Add custom text"><i class="fas fa-font"></i> Add Text</button>
        
        <select id="pdfFontStyle" class="pdf-tool-btn" style="width: 150px; background: #1e293b; outline:none;" onchange="updateSelectedObject('fontFamily', this.value)">
            <option value="Arial">Arial (Standard)</option>
            <option value="Shruti">Shruti (Gujarati Gov)</option>
            <option value="Mangal">Mangal (Hindi Gov)</option>
            <option value="'Times New Roman'">Times New Roman</option>
            <option value="Courier">Courier (Typing)</option>
            <option value="'Hind Vadodara'">Hind Vadodara</option>
        </select>

        <select id="pdfFontSize" class="pdf-tool-btn" style="width: 70px; background: #1e293b;" onchange="updateSelectedObject('fontSize', parseInt(this.value))">
            <option value="12">12</option>
            <option value="14">14</option>
            <option value="16">16</option>
            <option value="18" selected>18</option>
            <option value="20">20</option>
            <option value="24">24</option>
            <option value="32">32</option>
        </select>

        <div style="display:flex; align-items:center; gap:5px; background:#1e293b; padding:5px 12px; border-radius:6px; border: 1px solid #475569;">
            <input type="color" id="pdfColorPicker" value="#000000" style="height:25px; width:30px; cursor:pointer; padding:0; border:none; border-radius:4px; background: transparent;" onchange="syncColorPicker()">
        </div>

        <button class="pdf-tool-btn danger" onclick="deletePdfObject()"><i class="fas fa-trash"></i> Delete</button>
        <?php if(isset($_SESSION['user_id'])): ?>
        <button class="pdf-tool-btn" onclick="saveDraft()"><i class="fas fa-save"></i> Save Draft</button>
        <?php endif; ?>
    </div>

    <div class="pdf-canvas-wrapper" id="pdfCanvasWrapper">
        <canvas id="pdfEditorCanvas"></canvas>
    </div>

    <div id="formatPanel" style="display:none; background:#f1f5f9; padding:20px; border-radius:8px; margin:20px auto; max-width:800px; border:1px solid #cbd5e1;">
        <h5 style="border-bottom: 2px solid #cbd5e1; padding-bottom: 10px; margin-bottom: 15px;"><i class="fas fa-exchange-alt text-success"></i> Target Format Setting</h5>
        <div class="row align-items-center">
            <div class="col-md-8">
                <select id="targetImageFormat" class="form-control fw-bold">
                    <option value="image/jpeg">Convert to JPG / JPEG</option>
                    <option value="image/png">Convert to PNG</option>
                    <option value="image/webp">Convert to WEBP</option>
                </select>
            </div>
            <div class="col-md-4 text-end">
                <span class="text-muted" style="font-size:11px;">100% Offline Processing</span>
            </div>
        </div>
    </div>
    <div class="config-panel" id="configPanel" style="background:#fff;">
        <h5 style="border-bottom: 2px solid #cbd5e1; padding-bottom: 10px; margin-bottom: 15px;"><i class="fas fa-sliders-h text-primary"></i> Target Size Setting (KB)</h5>
        <div class="row align-items-center mb-3">
            <div class="col-md-5">
                <div class="input-group">
                    <input type="number" id="targetKB" class="form-control" placeholder="e.g. 50" value="50">
                    <span class="input-group-text bg-light fw-bold">KB</span>
                </div>
            </div>
            <div class="col-md-7 text-end">
                <button class="btn btn-info fw-bold w-100 text-white" style="background:#0ea5e9; border:none;" onclick="generateLivePreview()"><i class="fas fa-eye"></i> Check (Live Preview)</button>
            </div>
        </div>
        <div class="row mt-4" id="previewArea" style="display:none;">
            <div class="col-md-6 mb-3">
                <div class="preview-box border-secondary"><img id="origImgPreview" src=""><div id="origSizeTxt" class="badge bg-secondary mt-2">0 KB</div></div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="preview-box border-success" style="border-width: 2px;"><img id="compImgPreview" src=""><div id="compSizeTxt" class="badge bg-success mt-2">0 KB</div></div>
            </div>
        </div>
    </div>

    <div id="apiNoticePanel" style="display:none; background:#f0fdf4; border:1px solid #bbf7d0; padding:15px; border-radius:8px; max-width:600px; margin: 20px auto; color:#166534; text-align:center; font-size:13px;">
        <h6 class="fw-bold mb-1"><i class="fas fa-check-circle text-success"></i> Secure cloud conversion is enabled</h6>
        <p class="mb-0 text-muted">This file will be converted to 100% original format by high-speed API.</p>
        <input type="hidden" id="convertApiKey" value="uBHuW1yh3D6KFOHGmjEvs6Px7HhpzexU">
    </div>

    <button class="btn-convert" id="btnConvert" style="display: none;" onclick="processConversion()">
        <i class="fas fa-download"></i> <span id="btnText">Convert & Download <?= (!isset($_SESSION['user_id']) && isset($_COOKIE['guest_service_used'])) ? '' : '('.$currency.$card_cost.')' ?></span>
    </button>
</div>

<div id="loadingOverlay">
    <div class="spinner"></div>
    <h3 id="loadingText" style="line-height:1.5;">Processing... Please wait.</h3>
</div>

<script>
    const userRole = "<?= $_SESSION['user_role'] ?? 'guest' ?>";
    const cardCost = <?= number_format($card_cost, 2, '.', '') ?>;
    const currency = "<?= $currency ?>";
    const baseUrl = "<?= BASE_URL ?>"; 
    const APP_URL = "<?= APP_URL ?>";

    window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

    let currentTool = '';
    let selectedFile = null;
    let finalCompressedDataUrl = null; 

    // 🚀 STUDIO EDITOR VARIABLES 🚀
    let pdfEditorCanvas = null;
    let pdfDocDimensions = { width: 0, height: 0 };
    
    // Clone Tools
    let isMagicCloneMode = false;
    let targetBox = null;
    let sourceBox = null;
    let cloneStartX = 0;
    let cloneStartY = 0;

    const API_TOOLS = ['pdf_to_word', 'word_to_pdf', 'pdf_to_excel', 'excel_to_pdf', 'pdf_to_ppt', 'ppt_to_pdf'];

    function openTool(toolId, title, desc, acceptType) {
        currentTool = toolId;
        selectedFile = null;
        finalCompressedDataUrl = null;

        document.getElementById('gridArea').style.display = 'none';
        document.getElementById('workspaceArea').style.display = 'block';
        document.getElementById('wsTitle').innerText = title;
        document.getElementById('wsDesc').innerText = desc;
        
        const fileInput = document.getElementById('fileInput');
        fileInput.value = ''; fileInput.accept = acceptType;
        
        document.getElementById('fileNameDisplay').innerText = '';
        document.getElementById('btnConvert').style.display = 'none';
        document.getElementById('configPanel').style.display = 'none';
        document.getElementById('apiNoticePanel').style.display = 'none';
        document.getElementById('previewArea').style.display = 'none';
        document.getElementById('pdfToolbar').style.display = 'none';
        document.getElementById('pdfCanvasWrapper').style.display = 'none';
        document.getElementById('instructionBar').style.display = 'none';
        document.getElementById('uploadBox').style.display = 'block';

        if (['compress_image', 'universal_image', 'jpg_to_pdf', 'pdf_to_jpg'].includes(toolId)) document.getElementById('configPanel').style.display = 'block';
        if (API_TOOLS.includes(toolId)) document.getElementById('apiNoticePanel').style.display = 'block';
        if (toolId === 'universal_image') document.getElementById('formatPanel').style.display = 'block';
        else document.getElementById('formatPanel').style.display = 'none';

        if(toolId !== 'compress_image') document.getElementById('previewArea').style.display = 'none';
    }

    function closeTool() {
        document.getElementById('workspaceArea').style.display = 'none';
        document.getElementById('gridArea').style.display = 'block';
        if(pdfEditorCanvas) { pdfEditorCanvas.dispose(); pdfEditorCanvas = null; }
    }

    function handleFileSelect(event) {
        if (event.target.files.length > 0) {
            selectedFile = event.target.files[0];
            
            if(['pdf_to_jpg', 'pdf_to_word', 'pdf_to_excel', 'pdf_to_ppt', 'edit_pdf'].includes(currentTool) && selectedFile.type !== 'application/pdf') {
                alert('Please select PDF file only.'); return;
            }

            document.getElementById('fileNameDisplay').innerText = `Selected: ${selectedFile.name}`;
            
            if (currentTool === 'edit_pdf') {
                document.getElementById('uploadBox').style.display = 'none';
                initPdfEditor();
            } else if (currentTool !== 'compress_image') {
                document.getElementById('btnConvert').style.display = 'inline-block';
            }
        }
    }

    function showLoading(show, text = 'Processing...') {
        document.getElementById('loadingText').innerHTML = text;
        document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
    }

    function showInstruction(text, type='info') {
        const bar = document.getElementById('instructionBar');
        bar.style.display = 'block';
        bar.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i> <span>${text}</span>`;
        if(type === 'error') { bar.style.backgroundColor = '#fef2f2'; bar.style.color = '#dc2626'; bar.style.borderColor = '#fecaca'; }
        else { bar.style.backgroundColor = '#fef3c7'; bar.style.color = '#065f46'; bar.style.borderColor = '#a7f3d0'; }
    }

    // =====================================
    // 🚀 LIVE STUDIO PDF EDITOR 🚀
    // =====================================
    async function initPdfEditor() {
        showLoading(true, "Loading High-Res Document...");
        document.getElementById('pdfToolbar').style.display = 'flex';
        document.getElementById('pdfCanvasWrapper').style.display = 'block';
        document.getElementById('btnConvert').style.display = 'inline-block';
        document.getElementById('btnText').innerText = `Save Edited PDF (${currency}${cardCost})`;

        try {
            const fileUrl = URL.createObjectURL(selectedFile);
            const pdf = await pdfjsLib.getDocument(fileUrl).promise;
            const page = await pdf.getPage(1); 
            
            const scale = 2.5; // High Res for precise cloning
            const viewport = page.getViewport({ scale: scale });

            const tempCanvas = document.createElement('canvas');
            const context = tempCanvas.getContext('2d');
            tempCanvas.height = viewport.height;
            tempCanvas.width = viewport.width;

            await page.render({ canvasContext: context, viewport: viewport }).promise;

            if(pdfEditorCanvas) pdfEditorCanvas.dispose();
            
            pdfEditorCanvas = new fabric.Canvas('pdfEditorCanvas', {
                width: viewport.width,
                height: viewport.height,
                isDrawingMode: false,
                selection: true
            });

            pdfDocDimensions.width = viewport.width;
            pdfDocDimensions.height = viewport.height;

            fabric.Image.fromURL(tempCanvas.toDataURL('image/jpeg', 1.0), function(img) {
                img.set({ selectable: false, evented: false });
                pdfEditorCanvas.setBackgroundImage(img, pdfEditorCanvas.renderAll.bind(pdfEditorCanvas));
                
                setupStudioEvents(); 
                showLoading(false);
            });

        } catch (error) {
            alert("Error loading PDF: " + error.message);
            showLoading(false);
        }
    }

    // 🚀 1. THE MAGIC CLONE ALGORITHM (Photoshop Rubber Stamp) 🚀
    function toggleMagicClone() {
        resetAllModes();
        isMagicCloneMode = true;
        document.getElementById('btnMagicClone').classList.add('active');
        pdfEditorCanvas.selection = false; 
        pdfEditorCanvas.defaultCursor = 'crosshair';
        showInstruction('<b>Step 1:</b> Draw a red box over the text you want to delete with the mouse.');
    }

    function setupStudioEvents() {
        pdfEditorCanvas.on('mouse:down', function(o) {
            if(!isMagicCloneMode || targetBox) return; // Only 1 box at a time
            let pointer = pdfEditorCanvas.getPointer(o.e);
            cloneStartX = pointer.x; 
            cloneStartY = pointer.y;

            targetBox = new fabric.Rect({
                left: cloneStartX, top: cloneStartY, width: 0, height: 0,
                fill: 'transparent', stroke: '#ef4444', strokeWidth: 2, strokeDashArray: [5, 5],
                selectable: false, evented: false
            });
            pdfEditorCanvas.add(targetBox);
        });

        pdfEditorCanvas.on('mouse:move', function(o) {
            if(!isMagicCloneMode || !targetBox || sourceBox) return;
            let pointer = pdfEditorCanvas.getPointer(o.e);
            
            let minX = Math.min(cloneStartX, pointer.x);
            let minY = Math.min(cloneStartY, pointer.y);
            let w = Math.abs(cloneStartX - pointer.x);
            let h = Math.abs(cloneStartY - pointer.y);

            targetBox.set({ left: minX, top: minY, width: w, height: h });
            pdfEditorCanvas.renderAll();
        });

        pdfEditorCanvas.on('mouse:up', function(o) {
            if(!isMagicCloneMode || !targetBox || sourceBox) return;
            
            if(targetBox.width < 10 || targetBox.height < 10) {
                pdfEditorCanvas.remove(targetBox); targetBox = null; return; // Ignore accidental clicks
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
            pdfEditorCanvas.add(sourceBox);
            pdfEditorCanvas.setActiveObject(sourceBox);

            showInstruction("<b>Step 2:</b> Now this <b>Green box</b> Hold and place on the surrounding blank background. So the red box will be covered by the original design! Then press 'Apply'.");
            document.getElementById('btnApplyClone').style.display = 'inline-block';
            document.getElementById('btnMagicClone').style.display = 'none';

            sourceBox.on('moving', updateClonePatch);
            updateClonePatch(); // Run once immediately
        });
    }

    function updateClonePatch() {
        if(!targetBox || !sourceBox) return;
        
        // Temporarily hide both boxes to capture the clean background underneath the green box
        targetBox.visible = false; 
        sourceBox.visible = false;
        pdfEditorCanvas.renderAll(); 

        let cropDataUrl = pdfEditorCanvas.toDataURL({
            format: 'jpeg', quality: 1.0,
            left: sourceBox.left, top: sourceBox.top,
            width: sourceBox.width, height: sourceBox.height, multiplier: 1
        });

        targetBox.visible = true; 
        sourceBox.visible = true;

        fabric.Image.fromURL(cropDataUrl, function(img) {
            // Fill the target box perfectly with the image from the source box
            let pattern = new fabric.Pattern({ source: img.getElement(), repeat: 'no-repeat' });
            targetBox.set('fill', pattern);
            targetBox.set('strokeWidth', 0); // Hide red stroke to see seamless blend
            pdfEditorCanvas.renderAll();
        });
    }

    function applyClonePatch() {
        if(!targetBox || !sourceBox) return;
        
        // Remove the green helper box
        pdfEditorCanvas.remove(sourceBox);
        
        // Lock the newly patched background area
        targetBox.set({ selectable: false, evented: false, isClonePatch: true });
        
        // Add an editable Text Box right over the hidden text
        const color = document.getElementById('pdfColorPicker').value;
        const iText = new fabric.IText('Type here', {
            left: targetBox.left + 5, top: targetBox.top + (targetBox.height/2) - 10,
            fontFamily: 'Arial', fill: color, fontSize: Math.max(16, targetBox.height * 0.7),
            borderColor: '#38bdf8', cornerColor: '#38bdf8', transparentCorners: false,
            fontWeight: 'bold'
        });
        pdfEditorCanvas.add(iText);
        pdfEditorCanvas.setActiveObject(iText);
        iText.enterEditing(); 
        iText.selectAll();

        // Cleanup
        targetBox = null; sourceBox = null; 
        resetAllModes();
        showInstruction(" ✅ Accurate editing! Now you can type directly.");
    }

    // 🚀 2. STANDARD TOOLS 🚀
    function addPdfText() {
        resetAllModes();
        const color = document.getElementById('pdfColorPicker').value;
        const text = new fabric.IText('new text', {
            left: pdfDocDimensions.width / 2 - 50, top: pdfDocDimensions.height / 3,
            fontFamily: 'Arial', fill: color, fontSize: 24, fontWeight: 'bold',
            borderColor: '#38bdf8', cornerColor: '#38bdf8', transparentCorners: false
        });
        pdfEditorCanvas.add(text);
        pdfEditorCanvas.setActiveObject(text);
    }

    function togglePdfDraw() {
        let wasDrawing = pdfEditorCanvas.isDrawingMode;
        resetAllModes();
        
        if(!wasDrawing) {
            pdfEditorCanvas.isDrawingMode = true;
            document.getElementById('btnDraw').classList.add('active');
            pdfEditorCanvas.freeDrawingBrush.color = document.getElementById('pdfColorPicker').value;
            pdfEditorCanvas.freeDrawingBrush.width = 3; 
            showInstruction('Brush is on. Can sign PDF with mouse.');
        }
    }

    function syncColorPicker() {
        const color = document.getElementById('pdfColorPicker').value;
        const activeObj = pdfEditorCanvas.getActiveObject();
        if (activeObj && activeObj.type === 'i-text') {
            activeObj.set('fill', color);
            pdfEditorCanvas.renderAll();
        }
        if(pdfEditorCanvas.isDrawingMode) {
            pdfEditorCanvas.freeDrawingBrush.color = color;
        }
    }

    function deletePdfObject() {
        const activeObj = pdfEditorCanvas.getActiveObject();
        if (activeObj) { pdfEditorCanvas.remove(activeObj); }
    }

    function updateSelectedObject(property, value) {
        if (!pdfEditorCanvas) return;
        const activeObj = pdfEditorCanvas.getActiveObject();
        if (activeObj && activeObj.type === 'i-text') {
            activeObj.set(property, value);
            pdfEditorCanvas.renderAll();
        }
    }

    function resetAllModes() {
        isMagicCloneMode = false;
        pdfEditorCanvas.isDrawingMode = false;
        pdfEditorCanvas.selection = true;
        pdfEditorCanvas.defaultCursor = 'default';
        
        document.getElementById('btnMagicClone').classList.remove('active');
        document.getElementById('btnMagicClone').style.display = 'inline-block';
        document.getElementById('btnApplyClone').style.display = 'none';
        document.getElementById('btnDraw').classList.remove('active');
        document.getElementById('instructionBar').style.display = 'none';
        
        if(sourceBox) { pdfEditorCanvas.remove(sourceBox); sourceBox = null; }
        if(targetBox && targetBox.fill === 'transparent') { pdfEditorCanvas.remove(targetBox); targetBox = null; }
    }

    async function exportEditedPdf() {
        showLoading(true, "Saving High Quality PDF...");
        
        // Ensure no selection borders are visible in the final export
        pdfEditorCanvas.discardActiveObject(); 
        pdfEditorCanvas.renderAll();

        setTimeout(() => {
            const dataUrl = pdfEditorCanvas.toDataURL({ format: 'jpeg', quality: 1.0 });
            const { jsPDF } = window.jspdf;
            const orientation = pdfDocDimensions.width > pdfDocDimensions.height ? 'l' : 'p';
            const doc = new jsPDF({ orientation: orientation, unit: 'px', format: [pdfDocDimensions.width, pdfDocDimensions.height] });
            doc.addImage(dataUrl, 'JPEG', 0, 0, pdfDocDimensions.width, pdfDocDimensions.height);
            doc.save(`Edited_${selectedFile.name}`);
            
            showLoading(false); alert("The edited PDF has been successfully downloaded!");
            
            // Auto save to drafts
            let formData = new FormData();
            formData.append('image_data', dataUrl);
            formData.append('document_type', 'PDF Editor Draft');
            fetch(APP_URL + 'save_draft.php', { method: 'POST', body: formData }).catch(e => {});

        }, 500);
    }

    function saveDraft() {
        if (!pdfEditorCanvas) return;
        showLoading(true, "Saving to My Designs...");
        pdfEditorCanvas.discardActiveObject(); 
        pdfEditorCanvas.renderAll();

        setTimeout(() => {
            const dataUrl = pdfEditorCanvas.toDataURL({ format: 'jpeg', quality: 0.8 });
            let formData = new FormData();
            formData.append('image_data', dataUrl);
            formData.append('document_type', 'PDF Editor Draft');
            
            fetch(baseUrl + '/app/save_draft.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    showLoading(false);
                    if(data.success) { alert("✅ Saved to 'My Designs' successfully!"); }
                    else { alert("❌ Failed to save draft."); }
                }).catch(e => { showLoading(false); alert("❌ Network error saving draft."); });
        }, 500);
    }

    // =====================================
    // 🚀 COMPRESSOR & OTHER TOOLS 🚀
    // =====================================
    async function generateLivePreview() {
        if (!selectedFile) return;
        const targetKB = parseInt(document.getElementById('targetKB').value);
        if (!targetKB || targetKB <= 0) return;

        showLoading(true, "Calculating...");
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = async function() {
                document.getElementById('origImgPreview').src = img.src;
                document.getElementById('origSizeTxt').innerText = (selectedFile.size / 1024).toFixed(2) + " KB";
                const targetBytes = targetKB * 1024;
                const canvas = document.createElement('canvas'); const ctx = canvas.getContext('2d');
                canvas.width = img.width; canvas.height = img.height; ctx.drawImage(img, 0, 0);

                let bestDataUrl = canvas.toDataURL('image/jpeg', 0.9);
                let bestSize = Math.round((bestDataUrl.length * 3) / 4);
                let minQ = 0.01, maxQ = 1.0;
                for (let i = 0; i < 7; i++) {
                    let midQ = (minQ + maxQ) / 2; let tempUrl = canvas.toDataURL('image/jpeg', midQ); let tempSize = Math.round((tempUrl.length * 3) / 4);
                    if (tempSize <= targetBytes) { bestDataUrl = tempUrl; bestSize = tempSize; minQ = midQ; } else { maxQ = midQ; }
                }

                if (bestSize > targetBytes * 1.05) { 
                    let scaleFactor = Math.sqrt(targetBytes / bestSize) * 0.95; 
                    canvas.width = Math.floor(img.width * scaleFactor); canvas.height = Math.floor(img.height * scaleFactor);
                    ctx.imageSmoothingEnabled = true; ctx.imageSmoothingQuality = "high"; ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    minQ = 0.01; maxQ = 0.9;
                    for (let i = 0; i < 5; i++) {
                        let midQ = (minQ + maxQ) / 2; let tempUrl = canvas.toDataURL('image/jpeg', midQ); let tempSize = Math.round((tempUrl.length * 3) / 4);
                        if (tempSize <= targetBytes) { bestDataUrl = tempUrl; bestSize = tempSize; minQ = midQ; } else { maxQ = midQ; }
                    }
                }

                document.getElementById('compImgPreview').src = bestDataUrl;
                document.getElementById('compSizeTxt').innerText = (bestSize / 1024).toFixed(2) + " KB";
                document.getElementById('previewArea').style.display = 'flex';
                finalCompressedDataUrl = bestDataUrl; document.getElementById('btnConvert').style.display = 'inline-block';
                showLoading(false);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(selectedFile);
    }

    async function processConversion() {
        if (!selectedFile) return;

        if (userRole !== 'admin') {
            let confirmMsg = `${currency}${cardCost} will be deducted from the wallet for this task.\nDo you want to download?`;
            if (!confirm(confirmMsg)) return; 
            try {
                let formData = new FormData(); formData.append('service_type', `Document Tool (${currentTool})`);
                let response = await fetch(baseUrl + 'app/deduct_poster_balance.php', { method: 'POST', body: formData });
                let text = await response.text(); let result = JSON.parse(text);
                if (!result.success) { alert("❌ Error: " + result.message); return; }
            } catch (error) { return; }
        }

        if (currentTool === 'edit_pdf') { await exportEditedPdf(); }
        else if (currentTool === 'universal_image') {
            processUniversalImageConversion();
        } else if (currentTool === 'compress_image') {
            const link = document.createElement('a'); link.download = `Compressed_${selectedFile.name}`; link.href = finalCompressedDataUrl; link.click();
            alert("The photo has been downloaded!");
        }
        else if (currentTool === 'jpg_to_pdf') { await convertImageToPdf(); } 
        else if (currentTool === 'pdf_to_jpg') { await convertPdfToImage(); } 
        else if (API_TOOLS.includes(currentTool)) { await processOfficeConversionAPI(); }
    }

    function base64ToBlob(base64, mimeType = 'application/octet-stream') {
        const byteChars = atob(base64); const byteArrays = [];
        for (let offset = 0; offset < byteChars.length; offset += 512) {
            const slice = byteChars.slice(offset, offset + 512); const byteNumbers = new Array(slice.length);
            for (let i = 0; i < slice.length; i++) { byteNumbers[i] = slice.charCodeAt(i); }
            byteArrays.push(new Uint8Array(byteNumbers));
        }
        return new Blob(byteArrays, { type: mimeType });
    }

    async function processOfficeConversionAPI() {
        const apiKey = document.getElementById('convertApiKey').value.trim();
        const ext = selectedFile.name.split('.').pop().toLowerCase();
        let fromFormat = '', toFormat = '';
        const toolMap = {
            'pdf_to_word': { from: 'pdf', to: 'docx' }, 'word_to_pdf': { from: ext, to: 'pdf' },
            'pdf_to_excel': { from: 'pdf', to: 'xlsx' }, 'excel_to_pdf': { from: ext, to: 'pdf' },
            'pdf_to_ppt': { from: 'pdf', to: 'pptx' }, 'ppt_to_pdf': { from: ext, to: 'pdf' }
        };
        if(toolMap[currentTool]) { fromFormat = toolMap[currentTool].from; toFormat = toolMap[currentTool].to; }

        showLoading(true, "Converting file on cloud server...<br><small>(This may take 10-30 seconds)</small>");

        try {
            let formData = new FormData(); formData.append('File', selectedFile); formData.append('StoreFile', 'true');
            let response = await fetch(`https://v2.convertapi.com/convert/${fromFormat}/to/${toFormat}?Secret=${apiKey}`, { method: 'POST', body: formData });
            let result = await response.json();

            if (response.ok && result.Files && result.Files.length > 0) {
                let fileData = result.Files[0].FileData; let fileName = result.Files[0].FileName;
                if (fileData) { saveAs(base64ToBlob(fileData), fileName); } 
                else if (result.Files[0].Url) { let link = document.createElement('a'); link.href = result.Files[0].Url; link.download = fileName; link.click(); }
                showLoading(false); alert("CONVERTED AND DOWNLOADED!");
            } else { throw new Error(result.Message || "Unknown Error from Server"); }
        } catch (error) { alert("❌ Error: File could not be converted."); showLoading(false); }
    }

    async function compressImageToBytes(imgOrCanvas, targetBytes, mimeType = 'image/jpeg') {
        const isCanvas = imgOrCanvas instanceof HTMLCanvasElement;
        let canvas = isCanvas ? imgOrCanvas : document.createElement('canvas');
        if (!isCanvas) {
            canvas.width = imgOrCanvas.width; canvas.height = imgOrCanvas.height;
            canvas.getContext('2d').drawImage(imgOrCanvas, 0, 0);
        }
        const ctx = canvas.getContext('2d');
        const origW = canvas.width, origH = canvas.height;

        const getBlob = async (w, h, q) => {
            let tempC = document.createElement('canvas'); tempC.width = w; tempC.height = h;
            tempC.getContext('2d').drawImage(canvas, 0, 0, origW, origH, 0, 0, w, h);
            return new Promise(res => tempC.toBlob(res, mimeType, q));
        };

        if(mimeType !== 'image/png') {
            let minQ = 0.05, maxQ = 1.0, quality = 0.7; let finalBlob = null;
            for(let i=0; i<7; i++) {
                let b = await getBlob(origW, origH, quality);
                if(b.size <= targetBytes && b.size > targetBytes * 0.9) { finalBlob = b; break; }
                if(b.size > targetBytes) maxQ = quality; else minQ = quality;
                quality = (minQ + maxQ) / 2; finalBlob = b;
            }
            if(finalBlob && finalBlob.size <= targetBytes * 1.05) return finalBlob;
        }

        let attempts = 0; let currentW = origW, currentH = origH;
        let finalBlob = await getBlob(currentW, currentH, 0.8);
        while(finalBlob && finalBlob.size > targetBytes && attempts < 10 && currentW > 50) {
            let ratio = Math.sqrt(targetBytes / finalBlob.size) * 0.95; 
            currentW = Math.max(50, Math.floor(currentW * ratio)); currentH = Math.max(50, Math.floor(currentH * ratio));
            finalBlob = await getBlob(currentW, currentH, mimeType !== 'image/png' ? 0.7 : 1.0);
            attempts++;
        }
        return finalBlob;
    }

    async function convertImageToPdf() {
        showLoading(true, "Converting to PDF...");
        const targetKB = parseInt(document.getElementById('targetKB').value);
        const targetBytes = (targetKB && targetKB > 0) ? targetKB * 1024 : 0;
        
        const img = new Image();
        img.onload = async function() {
            let processedUrl = img.src;
            if (targetBytes > 0) {
                const finalBlob = await compressImageToBytes(img, targetBytes * 0.95, 'image/jpeg');
                processedUrl = URL.createObjectURL(finalBlob);
            }
            
            const pImg = new Image();
            pImg.onload = function() {
                const { jsPDF } = window.jspdf;
                let orientation = pImg.width > pImg.height ? 'l' : 'p';
                const doc = new jsPDF({ orientation: orientation, unit: 'px', format: [pImg.width, pImg.height] });
                doc.addImage(pImg.src, 'JPEG', 0, 0, pImg.width, pImg.height); 
                doc.save(selectedFile.name.split('.')[0] + '_converted.pdf');
                showLoading(false); alert("The PDF has been downloaded!");
            };
            pImg.src = processedUrl;
        };
        img.src = URL.createObjectURL(selectedFile);
    }

    async function convertPdfToImage() {
        showLoading(true, "Preparing PDF...");
        const targetKB = parseInt(document.getElementById('targetKB').value);
        const targetBytes = (targetKB && targetKB > 0) ? targetKB * 1024 : 0;

        const fileReader = new FileReader();
        fileReader.onload = async function() {
            try {
                const typedarray = new Uint8Array(this.result);
                const pdf = await pdfjsLib.getDocument({ data: typedarray }).promise;
                const zip = new JSZip();
                
                for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                    showLoading(true, `Extracting Page ${pageNum} of ${pdf.numPages}...`);
                    const page = await pdf.getPage(pageNum);
                    const viewport = page.getViewport({ scale: 3.0 });
                    const canvas = document.createElement('canvas'); const context = canvas.getContext('2d');
                    canvas.height = viewport.height; canvas.width = viewport.width;
                    await page.render({ canvasContext: context, viewport: viewport }).promise;
                    
                    if (targetBytes > 0) {
                        const blob = await compressImageToBytes(canvas, targetBytes, 'image/jpeg');
                        const arrayBuffer = await blob.arrayBuffer();
                        const base64Data = btoa(new Uint8Array(arrayBuffer).reduce((data, byte) => data + String.fromCharCode(byte), ''));
                        zip.file(`Page_${pageNum}.jpg`, base64Data, {base64: true});
                    } else {
                        const imgData = canvas.toDataURL('image/jpeg', 0.95);
                        const base64Data = imgData.replace(/^data:image\/(png|jpeg);base64,/, "");
                        zip.file(`Page_${pageNum}.jpg`, base64Data, {base64: true});
                    }
                }
                showLoading(true, "Zipping all images...");
                const zipContent = await zip.generateAsync({type:"blob"});
                saveAs(zipContent, selectedFile.name.split('.')[0] + "_Images.zip");
                showLoading(false); alert(" ✅ All images have been downloaded in Zip file!");
            } catch (error) { alert("Error reading PDF: " + error.message); showLoading(false); }
        };
        fileReader.readAsArrayBuffer(selectedFile);
    }

    async function processUniversalImageConversion() {
        if (!selectedFile || !selectedFile.type.startsWith('image/')) { alert("Please select an image."); return; }

        const targetFormat = document.getElementById('targetImageFormat').value;
        const targetKB = parseInt(document.getElementById('targetKB').value);
        const targetBytes = (targetKB && targetKB > 0) ? targetKB * 1024 : 0;
        const extMap = { 'image/jpeg': '.jpg', 'image/png': '.png', 'image/webp': '.webp' };
        const ext = extMap[targetFormat] || '.jpg';
        const newFilename = selectedFile.name.substring(0, selectedFile.name.lastIndexOf('.')) + "_converted" + ext;

        showLoading(true, "Converting Image...");
        const img = new Image();
        img.onload = async function() {
            let finalBlob;
            if (targetBytes > 0) {
                finalBlob = await compressImageToBytes(img, targetBytes, targetFormat);
            } else {
                const canvas = document.createElement('canvas'); canvas.width = img.width; canvas.height = img.height;
                const ctx = canvas.getContext('2d'); ctx.drawImage(img, 0, 0);
                finalBlob = await new Promise(resolve => canvas.toBlob(resolve, targetFormat, 0.9));
            }
            saveAs(finalBlob, newFilename);
            showLoading(false);
        };
        img.src = URL.createObjectURL(selectedFile);
    }
</script>
</body>
</html>