<?php
// Smart Checkout integrated
if (!function_exists('connectDB')) {
    require_once __DIR__ . '/../../config.php';
    require_once MODELS_PATH . 'db.php';
}
$pdo = connectDB();
$currency = '₹';
$user_role = $_SESSION['user_role'] ?? 'guest';

$service_rate = 2.00;
$points_rate = 0;
$user_balance = 0.00;
$user_points = 0;
$is_custom_rate = false;
$custom_poster_rate = 0.00;
$service_cost = 0.00; // Initialize early

try {
    $stmt_rate = $pdo->prepare("SELECT price, points_price FROM digital_service_rates WHERE service_slug = 'size_converter' AND is_active = 1");
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

$service_cost = $is_custom_rate ? $custom_poster_rate : $service_rate;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ultimate Size Converter Pro</title>
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>img/br_favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" defer></script>
    
    <style>
        /* Modern UI Resets */
        #sidebar { display: none !important; }
        .navbar { display: none !important; }
        #content { padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
        body { background-color: #0b1120; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; overflow: hidden; color: #e2e8f0; }
        
        /* Layout Structure */
        .converter-wrapper { display: flex; height: calc(100vh - 65px); width: 100vw; background: radial-gradient(circle at top right, #1e293b, #0b1120); }
        .converter-panel { width: 440px; min-width: 440px; background: rgba(15, 23, 42, 0.75); display: flex; flex-direction: column; border-right: 1px solid rgba(51, 65, 85, 0.5); z-index: 10; height: 100%; backdrop-filter: blur(20px); box-shadow: 10px 0 30px rgba(0,0,0,0.4); }
        .controls-area { flex-grow: 1; overflow-y: auto; padding: 25px; scrollbar-width: thin; scrollbar-color: #3b82f6 transparent; }
        .workspace { flex-grow: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; overflow: hidden; position: relative; padding: 30px; }
        
        /* Glassmorphism Controls */
        .control-box { background: rgba(30, 41, 59, 0.6); padding: 22px; border-radius: 20px; margin-bottom: 24px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative; overflow: hidden; }
        .control-box::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.5), transparent); }
        .control-title { font-weight: 800; font-size: 15px; color: #60a5fa; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 1px; }
        
        .form-label { font-size: 12px; font-weight: 600; color: #cbd5e1; margin-bottom: 10px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { width: 100%; padding: 14px 16px; margin-bottom: 15px; border-radius: 12px; border: 1px solid rgba(51, 65, 85, 0.8); background: rgba(15, 23, 42, 0.5); color: #f8fafc; font-size: 14px; font-family: 'Outfit'; outline: none; transition: all 0.3s ease; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15), inset 0 2px 4px rgba(0,0,0,0.1); background: rgba(15, 23, 42, 0.8); }
        .form-control option { background: #1e293b; color: #fff; }
        
        /* Modern Buttons */
        .btn-preview { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 16px; border: none; border-radius: 14px; font-weight: 800; font-size: 15px; cursor: pointer; width: 100%; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3); outline: none; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px; }
        .btn-preview:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(37, 99, 235, 0.4); }
        
        .btn-download { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 16px 30px; border: none; border-radius: 14px; font-weight: 800; font-size: 16px; cursor: pointer; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3); display: none; margin-top: 25px; align-items: center; gap: 10px; border: 1px solid rgba(255,255,255,0.1); }
        .btn-download:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 30px rgba(16, 185, 129, 0.4); }
        
        /* Workspace & Drag/Drop */
        .file-drop-zone { border: 2px dashed rgba(96, 165, 250, 0.5); border-radius: 24px; padding: 60px; text-align: center; background: rgba(30, 41, 59, 0.3); cursor: pointer; transition: all 0.3s ease; backdrop-filter: blur(10px); width: 80%; max-width: 600px; display: flex; flex-direction: column; align-items: center; gap: 15px; margin: 0 auto; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .file-drop-zone:hover { border-color: #60a5fa; background: rgba(59, 130, 246, 0.1); transform: scale(1.02); }
        .icon-circle { width: 80px; height: 80px; border-radius: 50%; background: rgba(96, 165, 250, 0.2); display: flex; align-items: center; justify-content: center; color: #60a5fa; font-size: 32px; margin-bottom: 10px; }
        
        /* Live Preview Split Pane */
        .preview-container { display: none; width: 100%; max-width: 1200px; height: 100%; flex-direction: column; align-items: center; justify-content: center; gap: 30px; animation: fadeIn 0.5s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .compare-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; width: 100%; height: 60vh; }
        .preview-card { background: rgba(30, 41, 59, 0.8); border-radius: 24px; border: 1px solid rgba(255,255,255,0.05); padding: 20px; display: flex; flex-direction: column; box-shadow: 0 20px 50px rgba(0,0,0,0.3); overflow: hidden; position: relative; }
        .preview-card.golden-border { border: 1px solid rgba(16, 185, 129, 0.5); box-shadow: 0 20px 50px rgba(16, 185, 129, 0.15); }
        .preview-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .badge { padding: 6px 12px; border-radius: 8px; font-weight: 800; font-size: 12px; letter-spacing: 0.5px; }
        .badge.original { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }
        .badge.target { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        
        .img-wrapper { flex-grow: 1; display: flex; align-items: center; justify-content: center; overflow: hidden; background: repeating-conic-gradient(#1e293b 0% 25%, transparent 0% 50%) 50% / 20px 20px; border-radius: 12px; }
        .img-wrapper img { max-width: 100%; max-height: 100%; object-fit: contain; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.5)); transition: transform 0.3s; }
        .img-wrapper img:hover { transform: scale(1.05); }
        
        .file-info-text { font-size: 13px; color: #94a3b8; margin-top: 15px; text-align: center; }
        
        /* Micro Loader */
        #loadingOverlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(11, 17, 32, 0.9); display: none; flex-direction: column; justify-content: center; align-items: center; z-index: 9999; backdrop-filter: blur(10px); }
        .loader-ring { width: 60px; height: 60px; border: 4px solid rgba(59, 130, 246, 0.2); border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s infinite linear; margin-bottom: 20px; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        
        .d-flex { display: flex; } .gap-2 { gap: 10px; } .mt-2 { margin-top: 10px; } .mb-0 { margin-bottom: 0; }
        
        @media (max-width: 1024px) {
            .converter-wrapper { flex-direction: column; height: auto; overflow-y: auto; }
            .converter-panel { width: 100%; min-width: 100%; border-right: none; border-bottom: 1px solid rgba(51, 65, 85, 0.8); }
            .compare-grid { grid-template-columns: 1fr; height: auto; }
            .preview-card { height: 400px; }
            .workspace { padding: 20px; min-height: 80vh; }
        }
    </style>
</head>
<body>

<?php $page_title = 'Ultimate Size Converter Pro'; require_once INCLUDES_PATH.'digital_header.php'; ?>

<div class="converter-wrapper">
    <!-- Sidebar Controls -->
    <div class="converter-panel">
        <div class="controls-area">
            <div class="control-box">
                <div class="control-title"><i class="fas fa-compress-arrows-alt"></i> 1. Smart Size Logic</div>
                
                <button class="btn btn-outline-danger w-100 rounded-pill mb-3 py-2 fw-bold border-opacity-25" style="border: 1px solid rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.05); color: #f87171; font-size: 13px;" onclick="resetConverter()">
                    <i class="fas fa-sync-alt me-2"></i> Reset Everything
                </button>
                
                <label class="form-label">Algorithm Strategy</label>
                <select id="actionType" class="form-control">
                    <option value="reduce">Intelligent Compression (Shrink)</option>
                    <option value="increase">Pixel Padding (Increase Memory)</option>
                </select>
                
                <label class="form-label mt-2">Target File Size (Exact)</label>
                <div class="d-flex gap-2">
                    <input type="number" id="targetSize" class="form-control" value="50" style="flex: 2; font-weight: bold;">
                    <select id="sizeUnit" class="form-control" style="flex: 1; font-weight: bold; color: #60a5fa;">
                        <option value="KB">KB</option>
                        <option value="MB">MB</option>
                    </select>
                </div>
                <small style="color: #64748b; font-size: 11px;">The AI will iteratively process the file until it matches this target closely.</small>
            </div>
            
            <div class="control-box">
                <div class="control-title"><i class="fas fa-random"></i> 2. Format Output</div>
                <label class="form-label">Export Native Formatting</label>
                <select id="outputFormat" class="form-control">
                    <option value="original">Keep Native Format</option>
                    <option value="image/jpeg">Convert to High-Res JPG</option>
                    <option value="image/png">Convert to Lossless PNG</option>
                    <option value="image/webp">Convert to Modern WebP</option>
                    <option value="image/bmp">Convert to Standard BMP</option>
                    <option value="application/pdf">Convert to PDF Document</option>
                </select>
                <small id="pdfHint" style="color: #f59e0b; font-size: 11px; display: none; margin-top:5px;"><i class="fas fa-info-circle"></i> Image will be auto-fitted inside a single PDF page.</small>
            </div>
            
            <div class="control-box" id="dimensionControlBox">
                <div class="control-title"><i class="fas fa-ruler-combined"></i> 3. Pixels & DPI Override</div>
                <div class="d-flex gap-2">
                    <div style="flex:1;">
                        <label class="form-label">Width (px)</label>
                        <input type="number" id="customW" class="form-control" placeholder="Auto">
                    </div>
                    <div style="flex:1;">
                        <label class="form-label">Height (px)</label>
                        <input type="number" id="customH" class="form-control" placeholder="Auto">
                    </div>
                </div>
                
                <label class="form-label mt-2">Force DPI (Metadata)</label>
                <select id="customDPI" class="form-control">
                    <option value="">Do Not Modify (Browser Def)</option>
                    <option value="150">150 DPI (Standard)</option>
                    <option value="300">300 DPI (High Print Quality)</option>
                    <option value="600">600 DPI (Ultra Max)</option>
                </select>
            </div>
            
            <button class="btn-preview" id="btnPreview" onclick="generatePreview()">
                <i class="fas fa-bolt"></i> Generate Live Preview
            </button>
        </div>
    </div>
    
    <!-- Workspace Area -->
    <div class="workspace">
        <!-- Initial Drop Zone -->
        <input type="file" id="fileInput" style="display:none;" accept="image/*, application/pdf" onchange="handleFileSelect(event)">
        
        <div class="file-drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
            <div class="icon-circle"><i class="fas fa-cloud-upload-alt"></i></div>
            <h2 style="font-weight: 800; font-size: 24px; color: #f8fafc; margin: 0;">Upload your file</h2>
            <p style="color: #94a3b8; font-size: 15px; margin: 0;">Drag & drop or perfectly click here to browse.<br>Supports JPG, PNG, WEBP, BMP, and PDF up to 50MB.</p>
        </div>
        
        <!-- Live Preview Content -->
        <div class="preview-container" id="previewContainer">
            <h3 style="font-weight: 800; color: #f8fafc; text-transform: uppercase; letter-spacing: 2px; margin: 0;"><i class="fas fa-eye text-blue-400"></i> Quality Assurance Preview</h3>
            
            <div class="compare-grid">
                <!-- Original Card -->
                <div class="preview-card">
                    <div class="preview-header">
                        <span style="font-weight: 800; color: #f8fafc; font-size: 16px;">Original File</span>
                        <span class="badge original" id="originalSizeBadge">0 KB</span>
                    </div>
                    <div class="img-wrapper">
                        <img id="imagePreviewOrig" src="" alt="Original Preview" onerror="this.src=''; this.alt='PDF Preview Unavailable'">
                    </div>
                    <div class="file-info-text" id="origFileText">Filename.jpg</div>
                </div>
                
                <!-- After Result Card -->
                <div class="preview-card golden-border">
                    <div class="preview-header">
                        <span style="font-weight: 800; color: #34d399; font-size: 16px;">Converted Result</span>
                        <span class="badge target" id="finalSizeBadge">Waiting...</span>
                    </div>
                    <div class="img-wrapper">
                        <img id="imagePreviewTarget" src="" alt="Target Preview" onerror="this.src=''; this.alt='PDF Output (Preview Hidden)'">
                    </div>
                    <div class="file-info-text" id="targetFileText">Target_Format.jpg</div>
                </div>
            </div>
            
            <!-- Download Action (Triggers API & Deduction) -->
            <div class="d-flex flex-column align-items-center gap-3 w-100 mt-4">
                <button class="btn-download w-75" id="btnDownload" onclick="triggerDownloadTransaction()">
                    <i class="fas fa-download"></i> Checkout & Download <?= (!isset($_SESSION['user_id']) && isset($_COOKIE['guest_service_used'])) ? '' : '('.$currency.$service_cost.')' ?>
                </button>
                <button class="btn btn-outline-info rounded-pill px-5 py-2 fw-bold" style="border: 1px solid rgba(96, 165, 250, 0.4); background: rgba(59, 130, 246, 0.1); color: #60a5fa; cursor: pointer;" onclick="resetConverter()">
                    <i class="fas fa-plus-circle me-2"></i> Upload Different File
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Global Loader -->
<div id="loadingOverlay">
    <div class="loader-ring"></div>
    <h3 id="loadingText" style="color: #f8fafc; font-weight: 600; font-family: 'Outfit';">Processing Mathematics...</h3>
    <small style="color: #94a3b8; margin-top: 5px;">This may take a few seconds.</small>
</div>

<script>
    // 0. DEFINITIONS FROM PHP (GLOBAL SCOPE)
    const APP_URL = "<?= APP_URL ?>";
    const userRole = "<?= $user_role ?>";
    const currency = "<?= $currency ?>";
    const serviceRate = <?= $service_rate ?>;
    const pointsRate = <?= $points_rate ?>;
    const userBalance = <?= $user_balance ?>;
    const userPoints = <?= $user_points ?>;
    const isGuest = (userRole === 'guest' || !userRole);
    const isCustomRate = <?= $is_custom_rate ? 'true' : 'false' ?>;
    const customRate = <?= $custom_poster_rate ?>;
    const serviceCost = <?= number_format($service_cost, 2, '.', '') ?>;

    // --- UI UTILITIES (OPTIMIZED) ---
    function showLoading(show, text = "Processing... Please wait.") {
        const el = document.getElementById('loadingOverlay');
        const txt = document.getElementById('loadingText');
        if (el) el.style.display = show ? 'flex' : 'none';
        if (txt && text) txt.innerText = text;
    }
    const hideLoader = () => showLoading(false);
    const showLoader = (text) => showLoading(true, text);

    // PERSISTENT CANVAS RESOURCES (REUSE PREVENTS MEMORY SPIKES)
    let offscreenCanvas = document.createElement('canvas');
    let offscreenCtx = offscreenCanvas.getContext('2d');

    function forceSyncAll() {
        if(typeof syncUIFromCurrentCanvas === 'function') syncUIFromCurrentCanvas();
    }

    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const previewContainer = document.getElementById('previewContainer');
    
    let currentFile = null;
    let finalBlobResult = null;
    let finalFileName = '';

    // Drag & drop logic
    dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.transform = 'scale(1.02)'; dropZone.style.borderColor = '#60a5fa'; });
    dropZone.addEventListener('dragleave', () => { dropZone.style.transform = 'scale(1)'; dropZone.style.borderColor = 'rgba(96, 165, 250, 0.5)'; });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.style.transform = 'scale(1)'; dropZone.style.borderColor = 'rgba(96, 165, 250, 0.5)';
        if(e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; handleFileSelect({target: fileInput}); }
    });

    // Formatting Bytes
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    function resetConverter() {
        // Reset states
        currentFile = null;
        finalBlobResult = null;
        fileInput.value = '';
        
        // Reset UI
        dropZone.style.display = 'flex';
        previewContainer.style.display = 'none';
        document.getElementById('btnDownload').style.display = 'none';
        
        // Clear previews
        document.getElementById('imagePreviewOrig').src = '';
        document.getElementById('imagePreviewTarget').src = '';
        
        // Reset ALL inputs to defaults
        document.getElementById('actionType').value = 'reduce';
        document.getElementById('targetSize').value = '50';
        document.getElementById('sizeUnit').value = 'KB';
        document.getElementById('outputFormat').value = 'original';
        document.getElementById('customW').value = '';
        document.getElementById('customH').value = '';
        document.getElementById('customDPI').value = '';
        document.getElementById('pdfHint').style.display = 'none';
    }

    document.getElementById('outputFormat').addEventListener('change', function() {
        if(this.value === 'application/pdf') { document.getElementById('pdfHint').style.display = 'block'; }
        else { document.getElementById('pdfHint').style.display = 'none'; }
    });

    function handleFileSelect(event) {
        const file = event.target.files[0];
        if(!file) return;

        // Security limits 50MB protection
        if(file.size > 50 * 1024 * 1024) {
            Swal.fire({ icon: 'error', title: 'File Too Large', text: 'File exceeds 50MB maximum limit for offline processing.' });
            fileInput.value = '';
            return;
        }

        currentFile = file;
        finalBlobResult = null;
        
        // Hide Dropzone, show Preview Panel
        dropZone.style.display = 'none';
        previewContainer.style.display = 'flex';
        document.getElementById('btnDownload').style.display = 'none';
        
        document.getElementById('originalSizeBadge').innerText = formatBytes(file.size);
        document.getElementById('origFileText').innerText = file.name;
        document.getElementById('finalSizeBadge').innerText = 'Press Preview...';
        document.getElementById('targetFileText').innerText = 'Pending...';

        // Load preview for image formats
        if(file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => { 
                document.getElementById('imagePreviewOrig').src = e.target.result;
                document.getElementById('imagePreviewTarget').src = ''; 
            };
            reader.readAsDataURL(file);
        } else if (file.type === 'application/pdf') {
            document.getElementById('imagePreviewOrig').src = ''; // PDF renders alt naturally
            document.getElementById('imagePreviewTarget').src = '';
            document.getElementById('outputFormat').value = 'application/pdf'; // Lock to PDF natively usually or keep it flexible if they want to try pdf->jpg (we'll limit this for this simple module since they use Document Converter for extraction).
        } else {
            Swal.fire({ icon: 'error', title: 'Unsupported Format', text: "Format unsupported. Please select an Image or PDF." }).then(() => {
                location.reload();
            });
        }
    }

    // ===============================================
    // LIVE PREVIEW PROCESSING ALGORITHM
    // ===============================================
    async function generatePreview() {
        if(!currentFile) { 
            Swal.fire({ icon: 'warning', title: 'No File', text: "Please upload a file first." });
            return; 
        }
        
        showLoader("Computing best quality...");
        
        try {
            const action = document.getElementById('actionType').value;
            const targetVal = parseFloat(document.getElementById('targetSize').value);
            const unit = document.getElementById('sizeUnit').value;
            const targetBytes = targetVal * (unit === 'MB' ? 1048576 : 1024);
            
            let outFormat = document.getElementById('outputFormat').value;
            if(outFormat === 'original') outFormat = currentFile.type;
            
            // Branch to specific manipulators
            if (currentFile.type.startsWith('image/')) {
                await computeImagePreview(action, targetBytes, outFormat);
            } else if (currentFile.type === 'application/pdf') {
                await computePDFPreview(action, targetBytes);
            }

            if(finalBlobResult) {
                document.getElementById('finalSizeBadge').innerText = formatBytes(finalBlobResult.size);
                document.getElementById('targetFileText').innerText = finalFileName;
                document.getElementById('btnDownload').style.display = 'flex';
                
                // Set the after preview visual
                if(finalBlobResult.type.startsWith('image/')) {
                    document.getElementById('imagePreviewTarget').src = URL.createObjectURL(finalBlobResult);
                } else {
                    document.getElementById('imagePreviewTarget').src = ''; // PDF alt text
                }
            }
        } catch(error) {
            console.error(error);
            Swal.fire({ icon: 'error', title: 'Processing Error', text: "Error during preview generation: " + error.message });
        } finally {
            hideLoader();
        }
    }

    // Advanced Image Byte Computation
    async function computeImagePreview(action, targetBytes, mimeType) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = async function() {
                try {
                    let oWidth = img.width; let oHeight = img.height;
                    const customW = parseInt(document.getElementById('customW').value);
                    const customH = parseInt(document.getElementById('customH').value);
                    if (customW > 10) oWidth = customW;
                    if (customH > 10) oHeight = customH;

                    // Reuse persistent resources
                    
                    const createBuffer = async (w, h, q, format) => {
                        offscreenCanvas.width = w; offscreenCanvas.height = h;
                        offscreenCtx.fillStyle = '#ffffff'; 
                        offscreenCtx.fillRect(0, 0, w, h);
                        offscreenCtx.drawImage(img, 0, 0, w, h);
                        let safeFormat = format === 'image/bmp' ? 'image/jpeg' : format; 
                        return new Promise(res => offscreenCanvas.toBlob(res, safeFormat, q));
                    };

                    let computedBlob = null;

                    // 1. PDF Output Logic
                    if (mimeType === 'application/pdf') {
                        const { jsPDF } = window.jspdf;
                        let orientation = oWidth > oHeight ? 'l' : 'p';
                        const doc = new jsPDF({ orientation: orientation, unit: 'px', format: [oWidth, oHeight] });
                        // Embed via jpeg
                        offscreenCanvas.width = oWidth; offscreenCanvas.height = oHeight; offscreenCtx.drawImage(img, 0, 0, oWidth, oHeight);
                        const b64 = offscreenCanvas.toDataURL('image/jpeg', 0.9);
                        doc.addImage(b64, 'JPEG', 0, 0, oWidth, oHeight);
                        let pdfBytes = doc.output('arraybuffer');
                        
                        if(action === 'increase' && pdfBytes.byteLength < targetBytes) {
                            const padding = new Uint8Array(targetBytes - pdfBytes.byteLength).fill(32);
                            pdfBytes = new Uint8Array([...new Uint8Array(pdfBytes), ...padding]);
                        }
                        computedBlob = new Blob([pdfBytes], { type: 'application/pdf' });
                    } 
                    // 2. Reduce Image Size Logic
                    else if (action === 'reduce') {
                        computedBlob = await createBuffer(oWidth, oHeight, 1.0, mimeType);
                        
                        // Recursive compression dropping quality (Faster steps)
                        if (computedBlob.size > targetBytes && mimeType !== 'image/png' && mimeType !== 'image/bmp') {
                            let quality = 0.85;
                            while(computedBlob.size > targetBytes && quality > 0.1) {
                                computedBlob = await createBuffer(oWidth, oHeight, quality, mimeType);
                                quality -= 0.25; // Aggressive step
                            }
                        }
                        
                        // Ratio dimensional scale down if quality drop wasn't enough
                        if (computedBlob.size > targetBytes) {
                            let attempts = 0;
                            let finalW = oWidth; let finalH = oHeight;
                            while(computedBlob.size > targetBytes && attempts < 10 && finalW > 50) {
                                let ratio = Math.sqrt(targetBytes / computedBlob.size) * 0.95;
                                finalW = Math.max(50, Math.floor(finalW * ratio));
                                finalH = Math.max(50, Math.floor(finalH * ratio));
                                computedBlob = await createBuffer(finalW, finalH, mimeType.includes('png') ? 1.0 : 0.7, mimeType);
                                attempts++;
                            }
                        }
                    } 
                    // 3. Increase Image Size Logic
                    else {
                        computedBlob = await createBuffer(oWidth, oHeight, 1.0, mimeType);
                        if(computedBlob.size < targetBytes) {
                            const diff = Math.ceil(targetBytes - computedBlob.size);
                            const padding = new Uint8Array(diff).fill(0); // Safest zero byte padding to EOF
                            computedBlob = new Blob([computedBlob, padding], { type: mimeType });
                        }
                    }

                    // Store Globals
                    finalBlobResult = computedBlob;
                    
                    const extMap = { 'image/jpeg': '.jpg', 'image/png': '.png', 'image/webp': '.webp', 'image/bmp': '.bmp', 'application/pdf': '.pdf' };
                    let ext = extMap[mimeType] || '.file';
                    finalFileName = currentFile.name.substring(0, currentFile.name.lastIndexOf('.')) + "_" + action + ext;
                    
                    resolve();
                } catch(e) { reject(e); }
            };
            img.onerror = () => reject(new Error("Broken image detected."));
            img.src = document.getElementById('imagePreviewOrig').src; // Read from DOM to skip re-reading file locally
        });
    }

    // Advanced PDF Byte Computation (Offline native padding)
    async function computePDFPreview(action, targetBytes) {
        showLoader("Parsing PDF Layout...");
        const arrayBuffer = await currentFile.arrayBuffer();
        let computedBlob = null;
        
        if (action === 'increase') {
            const currentSize = arrayBuffer.byteLength;
            if(currentSize < targetBytes) {
                const diff = Math.ceil(targetBytes - currentSize);
                const padding = new Uint8Array(diff).fill(32); 
                computedBlob = new Blob([arrayBuffer, padding], { type: 'application/pdf' });
            } else {
                computedBlob = new Blob([arrayBuffer], { type: 'application/pdf' });
            }
        } else {
            // Very Basic Offline Reduction Logic (metadata stripping using save)
            // Advanced robust layout reduction requires the 'Pro Document Converter' external API
            try {
                const pdfDoc = await PDFLib.PDFDocument.load(arrayBuffer);
                const pdfBytes = await pdfDoc.save({ useObjectStreams: false }); 
                computedBlob = new Blob([pdfBytes], { type: 'application/pdf' });
                if(computedBlob.size > targetBytes) {
                    alert("Notice: Advanced PDF size reduction is limited natively offline. It has been reduced as much as possible safely.");
                }
            } catch(e) {
                computedBlob = currentFile; 
            }
        }

        finalBlobResult = computedBlob;
        finalFileName = currentFile.name.substring(0, currentFile.name.lastIndexOf('.')) + "_" + action + ".pdf";
    }

    // Payment definitions moved to top scope

    // ===============================================
    // WALLET PAYMENT AND DOWNLOAD TRANSACTION
    // ===============================================
    async function triggerDownloadTransaction() {
        if(!finalBlobResult) return;

        // Security check for wallet deduction
        if (!isGuest && userRole !== 'admin' && userRole !== 'master_admin') {
            let actualCost = serviceRate;
            let willUsePoints = false;
            
            // Note: size_converter doesn't normally use customRate, but if Admin forces it, respect it.
            // If it's globally free
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
            // Admin logs
            await triggerWalletAPI(false);
        }

        executeDownload();
    }

    async function triggerWalletAPI(willUsePoints) {
        showLoader("Verifying Wallet Context...");
        try {
            let formData = new FormData();
            formData.append('service_slug', 'size_converter');
            formData.append('service_type', 'Ultimate Size Converter Pro');
            if (willUsePoints) formData.append('use_points', '1');

            let response = await fetch(APP_URL + 'deduct_poster_balance.php', { method: 'POST', body: formData });
            let text = await response.text();
            hideLoader();
            
            try {
                let result = JSON.parse(text);
                if (!result.success) {
                    alert("❌ Error: " + result.message);
                    throw new Error("Payment failed");
                }
                if (isGuest || result.cost <= 0) alert(result.message || "✅ Guest pass used!");
                else alert(` ✅ Downloaded!\nPaid from: ${result.deducted_type === 'points' ? result.cost + ' Pts' : currency + result.cost}`);
            } catch(e) { 
                console.error("JSON Error:", text);
                alert("❌ API Server parsing failed. Download cancelled for protection."); 
                throw e; 
            }
        } catch(e) { 
            alert("❌ Network error processing wallet. Download Cancelled."); 
            hideLoader(); 
            throw e; 
        }
    }

    function executeDownload() {
        // Output securely
        const url = URL.createObjectURL(finalBlobResult);
        const link = document.createElement('a');
        link.href = url;
        link.download = finalFileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }
</script>

</body>
</html>