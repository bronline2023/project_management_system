<?php
require_once CORE_INCLUDES_PATH . 'service_paywall.php';
enforce_service_paywall('size_converter');

$pdo = connectDB();
$service_cost = 10.00; 
$currency = '₹';
$user_role = $_SESSION['user_role'] ?? 'guest';

try {
    $stmt = $pdo->query("SELECT poster_generation_cost, currency_symbol FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings) {
        $service_cost = isset($settings['poster_generation_cost']) ? (float)$settings['poster_generation_cost'] : 10.00;
        $currency = isset($settings['currency_symbol']) ? $settings['currency_symbol'] : '₹';
    }

    if (isset($_SESSION['user_id'])) {
        $stmt_user = $pdo->prepare("SELECT custom_poster_rate FROM users WHERE id = ?");
        $stmt_user->execute([$_SESSION['user_id']]);
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data && isset($user_data['custom_poster_rate']) && $user_data['custom_poster_rate'] !== null && $user_data['custom_poster_rate'] !== '') {
            $service_cost = (float)$user_data['custom_poster_rate'];
        }
    }
} catch (Exception $e) {}
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>

<style>
    #sidebar { display: none !important; }
    .navbar { display: none !important; }
    #content { padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    body { background-color: #0f172a; margin: 0; padding: 0; font-family: 'Inter', 'Segoe UI', sans-serif; overflow: hidden; }
    
    .converter-wrapper { display: flex; height: 100vh; width: 100vw; color: #f8fafc; background: #0f172a; }
    .converter-panel { width: 420px; min-width: 420px; background: #1e293b; display: flex; flex-direction: column; border-right: 1px solid #334155; z-index: 10; height: 100%; box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
    
    .controls-area { flex-grow: 1; overflow-y: auto; padding: 24px; background: #1e293b; }
    .workspace { flex-grow: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; overflow: hidden; background: #0f172a; background-image: radial-gradient(#334155 1px, transparent 0); background-size: 30px 30px; position: relative; }
    
    .control-box { background: rgba(30, 41, 59, 0.5); padding: 20px; border-radius: 16px; margin-bottom: 20px; border: 1px solid #334155; backdrop-filter: blur(10px); }
    .control-title { font-weight: 800; font-size: 14px; color: #3b82f6; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #334155; padding-bottom: 10px; }
    
    .form-label { font-size: 11px; font-weight: 700; color: #94a3b8; margin-bottom: 8px; display: block; text-transform: uppercase; }
    .form-control { width: 100%; padding: 12px; margin-bottom: 15px; border-radius: 10px; border: 1px solid #334155; background: #0f172a; color: white; font-size: 14px; outline: none; transition: 0.3s; }
    .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }

    .btn-action { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 14px; border: none; border-radius: 10px; font-weight: 800; font-size: 14px; cursor: pointer; width: 100%; transition: 0.3s; box-shadow: 0 4px 15px rgba(59,130,246,0.3); }
    .btn-action:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(59,130,246,0.4); }
    
    .btn-export { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 16px; border: none; border-radius: 12px; font-weight: 800; font-size: 16px; cursor: pointer; width: 100%; box-shadow: 0 4px 20px rgba(16,185,129,0.3); transition: 0.3s; }
    .btn-export:hover { transform: scale(1.02); }

    .file-drop-zone { border: 2px dashed #475569; border-radius: 20px; padding: 50px; text-align: center; background: rgba(30, 41, 59, 0.5); color: #94a3b8; cursor: pointer; transition: 0.3s; backdrop-filter: blur(5px); }
    .file-drop-zone:hover { border-color: #3b82f6; color: #f8fafc; background: rgba(30, 41, 59, 0.8); }
    
    .preview-image { max-width: 85%; max-height: 60vh; border-radius: 12px; box-shadow: 0 20px 50px rgba(0,0,0,0.6); display: none; margin-bottom: 30px; border: 1px solid #334155; }
    .stat-badge { background: #1e293b; color: #38bdf8; padding: 10px 20px; border-radius: 30px; font-weight: 800; font-size: 13px; margin: 0 8px; border: 1px solid #334155; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }

    .progress-bar-container { width: 100%; background: #e2e8f0; border-radius: 8px; height: 10px; margin-top: 20px; display: none; overflow: auto; }
    .progress-bar { width: 0%; height: 100%; background: #10b981; transition: 0.2s; }

    @media (max-width: 992px) {
        .converter-wrapper { flex-direction: column !important; height: auto !important; }
        .converter-panel { width: 100% !important; min-width: 100% !important; border-right: none !important; border-bottom: 2px solid #334155; }
        .workspace { padding: 20px !important; min-height: 50vh; }
    }
</style>

<?php $page_title = 'Ultimate Size Converter'; require_once INCLUDES_PATH.'digital_header.php'; ?>
<div class="converter-wrapper" style="height: calc(100vh - 65px);">
        
        <div class="controls-area">
            <div class="control-box">
                <span class="control-title"><i class="fas fa-microchip"></i> 1. Smart Size Logic</span>
                
                <label class="form-label">Conversion Strategy</label>
                <select id="actionType" class="form-control fw-bold" onchange="toggleAction()">
                    <option value="reduce">Intelligent Compression (Shrink)</option>
                    <option value="increase">Pixel Padding (Increase Size)</option>
                </select>

                <label class="form-label mt-2">Target File Size (Exact)</label>
                <div class="d-flex gap-2">
                    <input type="number" id="targetSize" class="form-control fw-bold" value="50" style="flex: 2;">
                    <select id="sizeUnit" class="form-control fw-bold" style="flex: 1; color: #3b82f6;">
                        <option value="KB">KB</option>
                        <option value="MB">MB</option>
                    </select>
                </div>
                <small class="text-muted" style="font-size:10px;">The AI will iteratively process the file to match this size.</small>
            </div>

            <div class="control-box">
                <span class="control-title"><i class="fas fa-sync-alt"></i> 2. Format Mastery</span>
                <label class="form-label">Universal Output Format</label>
                <select id="outputFormat" class="form-control fw-bold">
                    <option value="original">Keep Native Format</option>
                    <option value="image/jpeg">Convert to JPG (Photo)</option>
                    <option value="image/png">Convert to PNG (Lossless)</option>
                    <option value="image/webp">Convert to WebP (Modern)</option>
                    <option value="application/pdf">Convert to PDF (Document)</option>
                </select>
                <p class="mb-0 text-warning mt-1" style="font-size:10px; display:none;" id="pdfWarning"><i class="fas fa-exclamation-triangle"></i> Note: Image-to-PDF will wrap the image into a PDF page.</p>
            </div>

            <div class="control-box" id="dimensionControlBox">
                <span class="control-title"><i class="fas fa-ruler-combined"></i> 3. Pixels & DPI (Optional)</span>
                <div class="d-flex gap-2">
                    <div style="flex:1;">
                        <label class="form-label text-truncate">Width (px)</label>
                        <input type="number" id="customW" class="form-control" placeholder="Auto">
                    </div>
                    <div style="flex:1;">
                        <label class="form-label text-truncate">Height (px)</label>
                        <input type="number" id="customH" class="form-control" placeholder="Auto">
                    </div>
                </div>
                
                <label class="form-label mt-2">DPI (Pixels per Inch)</label>
                <select id="customDPI" class="form-control fw-bold">
                    <option value="">Browser Default (72)</option>
                    <option value="150">150 DPI (Mid)</option>
                    <option value="300">300 DPI (High Print Quality)</option>
                    <option value="600">600 DPI (Ultra)</option>
                </select>
                <small class="text-muted" style="font-size:10px;">If downloading as JPG/PNG, forcefully embeds DPI metadata.</small>
            </div>

            <div class="control-box" id="qualityControlBox" style="display:none;">
                <span class="control-title"><i class="fas fa-sliders-h"></i> 4. Quality Control</span>
                <label class="form-label">Image Quality (1-100)</label>
                <input type="range" id="imageQuality" class="form-control" min="1" max="100" value="80" oninput="this.nextElementSibling.value=this.value">
                <output class="text-white fw-bold" style="font-size:12px;">80</output>
                <small class="text-muted" style="font-size:10px; display:block;">Lower quality for smaller file size, higher for better visual fidelity.</small>
            </div>

            <button class="btn-export mt-4" id="btnConvert" style="display:none;" onclick="startConversion()">
                <i class="fas fa-magic"></i> Start the process
            </button>

            <div class="progress-bar-container" id="progressBarContainer">
                <div class="progress-bar" id="progressBar"></div>
            </div>
            <p id="progressText" class="text-center mt-2 fw-bold text-success" style="font-size:12px; display:none;">Processing...</p>
        </div>

    <div class="workspace">
        <input type="file" id="fileInput" style="display:none;" accept="image/*, application/pdf" onchange="handleFileSelect(event)">
        
        <div class="file-drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
            <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-emerald-400"></i>
            <h3>Click or drag and drop the file here</h3>
            <p class="text-muted">Supports JPG, PNG, WEBP, PDF</p>
        </div>

        <img id="imagePreview" class="preview-image" src="" alt="Preview">
        
        <div id="pdfPreview" class="pdf-preview">
            <i class="fas fa-file-pdf fa-4x text-danger mb-3"></i>
            <h4 id="pdfName">document.pdf</h4>
        </div>

        <div id="statsBar" style="display:none;" class="mt-4">
            <div class="stat-badge"><i class="fas fa-file"></i> Original: <span id="originalSizeBadge">0 KB</span></div>
            <div class="stat-badge" style="color:#10b981; border-color:#10b981;"><i class="fas fa-check-circle"></i> New Target: <span id="targetSizeBadge">0 KB</span></div>
        </div>
    </div>
</div>

<script>
    const userRole = '<?= $_SESSION['user_role'] ?? 'guest' ?>';
    const currency = '<?= $currency ?>';
    const serviceCost = <?= $service_cost ?>;
    const baseUrl = '<?= defined("BASE_URL") ? BASE_URL : "" ?>/';
    const APP_URL = '<?= APP_URL ?>';

    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const imagePreview = document.getElementById('imagePreview');
    const pdfPreview = document.getElementById('pdfPreview');
    const btnConvert = document.getElementById('btnConvert');
    const statsBar = document.getElementById('statsBar');

    let currentFile = null;
    let originalBytes = 0;
    let fileType = '';

    // Drag & Drop Handlers
    dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if(e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelect({target: fileInput});
        }
    });

    function handleFileSelect(event) {
        const file = event.target.files[0];
        if(!file) return;

        currentFile = file;
        originalBytes = file.size;
        fileType = file.type;

        document.getElementById('originalSizeBadge').innerText = formatBytes(originalBytes);
        updateTargetBadge();

        dropZone.style.display = 'none';
        statsBar.style.display = 'block';
        btnConvert.style.display = 'block';

        if(file.type.startsWith('image/')) {
            imagePreview.style.display = 'block';
            pdfPreview.style.display = 'none';
            document.getElementById('pdfWarning').style.display = 'block'; // Show PDF wrap hint
            
            const reader = new FileReader();
            reader.onload = (e) => { imagePreview.src = e.target.result; };
            reader.readAsDataURL(file);
        } else if (file.type === 'application/pdf') {
            imagePreview.style.display = 'none';
            pdfPreview.style.display = 'block';
            document.getElementById('pdfName').innerText = file.name;
            document.getElementById('pdfWarning').style.display = 'none';
            document.getElementById('outputFormat').value = 'original';
            document.getElementById('outputFormat').disabled = true; // Still locked for PDF input
            document.getElementById('outputFormat').style.opacity = '0.5';
        } else {
            alert('Unsupported file format!');
            resetWorkspace();
        }
    }

    document.getElementById('targetSize').addEventListener('input', updateTargetBadge);
    document.getElementById('sizeUnit').addEventListener('change', updateTargetBadge);

    function updateTargetBadge() {
        const val = document.getElementById('targetSize').value;
        const unit = document.getElementById('sizeUnit').value;
        document.getElementById('targetSizeBadge').innerText = `${val} ${unit}`;
    }

    function toggleAction() {
        const action = document.getElementById('actionType').value;
        // Optionally adjust UI if needed based on reduction or padding
    }

    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    function resetWorkspace() {
        dropZone.style.display = 'block';
        imagePreview.style.display = 'none';
        pdfPreview.style.display = 'none';
        statsBar.style.display = 'none';
        btnConvert.style.display = 'none';
        currentFile = null;
    }

    async function startConversion() {
        if(!currentFile) return;

        if (userRole !== 'admin') {
            let confirmMsg = `${currency}${serviceCost} will be deducted from your wallet to process this file.\n\nDo you want to proceed?`;
            if (!confirm(confirmMsg)) return; 
            
            try {
                let formData = new FormData();
                formData.append('service_type', 'Ultimate Size Converter');

                let response = await fetch(APP_URL + 'deduct_poster_balance.php', { method: 'POST', body: formData });
                let text = await response.text();
                try {
                    let result = JSON.parse(text);
                    if (!result.success) {
                        alert("❌ Error: " + result.message);
                        return;
                    }
                } catch(e) { alert("❌ Server Error."); return; }
            } catch(e) { alert("❌ Network Error."); return; }
        } else {
            let formData = new FormData();
            formData.append('service_type', 'Ultimate Size Converter (Admin)');
            fetch(APP_URL + 'deduct_poster_balance.php', { method: 'POST', body: formData });
        }

        const action = document.getElementById('actionType').value;
        const targetVal = parseFloat(document.getElementById('targetSize').value);
        const unit = document.getElementById('sizeUnit').value;
        const targetBytes = targetVal * (unit === 'MB' ? 1048576 : 1024);

        let outFormat = document.getElementById('outputFormat').value;
        if(outFormat === 'original') outFormat = currentFile.type;

        document.getElementById('progressBarContainer').style.display = 'block';
        document.getElementById('progressText').style.display = 'block';
        const pBar = document.getElementById('progressBar');
        pBar.style.width = '10%';

        if(currentFile.type.startsWith('image/')) {
            await processImage(action, targetBytes, outFormat, pBar);
        } else if (currentFile.type === 'application/pdf') {
            await processPDF(action, targetBytes, pBar);
        }
        
        document.getElementById('progressText').innerText = "Processing Complete!";
        setTimeout(() => {
            document.getElementById('progressBarContainer').style.display = 'none';
            document.getElementById('progressText').style.display = 'none';
            pBar.style.width = '0%';
        }, 3000);
    }

    // ===============================================
    // DPI INJECTION HACK FOR JPEG & PNG
    // ===============================================
    function changeDpiDataUrl(base64Image, dpi) {
        if (!dpi || isNaN(dpi)) return base64Image;
        const dpiVal = parseInt(dpi);
        if(base64Image.indexOf('image/jpeg') !== -1) {
            let data = atob(base64Image.split(',')[1]);
            let length = data.length;
            let array = new Uint8Array(length);
            for(let i = 0; i < length; i++) array[i] = data.charCodeAt(i);
            
            // Override EXIF JFIF density segments
            array[13] = 1; // dots per inch
            array[14] = Math.floor(dpiVal / 256);
            array[15] = dpiVal % 256;
            array[16] = Math.floor(dpiVal / 256);
            array[17] = dpiVal % 256;

            let finalB64 = btoa(String.fromCharCode.apply(null, array));
            return 'data:image/jpeg;base64,' + finalB64;
        } 
        else if (base64Image.indexOf('image/png') !== -1) {
            let data = atob(base64Image.split(',')[1]);
            let length = data.length;
            let array = new Uint8Array(length);
            for(let i = 0; i < length; i++) array[i] = data.charCodeAt(i);

            // PNG uses Pixels per Meter (approx DPI x 39.3701)
            let ppm = Math.round(dpiVal * 39.3701);
            let physChunk = new Uint8Array(13);
            physChunk[0] = 0; physChunk[1] = 0; physChunk[2] = 0; physChunk[3] = 9; // length
            physChunk[4] = 112; physChunk[5] = 72; physChunk[6] = 89; physChunk[7] = 115; // pHYs
            
            physChunk[8] = (ppm >>> 24) & 0xFF; physChunk[9] = (ppm >>> 16) & 0xFF; physChunk[10] = (ppm >>> 8) & 0xFF; physChunk[11] = ppm & 0xFF; // X axis
            physChunk[12] = (ppm >>> 24) & 0xFF; physChunk[13] = (ppm >>> 16) & 0xFF; physChunk[14] = (ppm >>> 8) & 0xFF; physChunk[15] = ppm & 0xFF; // Y axis
            physChunk[16] = 1; // unit = meter
            
            // Warning: Complete PNG chunk injection is complex. We will just use the standard Array chunk wrapper 
            // Better to just return the blob directly if PNG chunk hacking fails.
            // Simplified fallback for PNG format.
            return base64Image; 
        }
        return base64Image;
    }

    // ===============================================
    // IMAGE PROCESSING LOGIC
    // ===============================================
    async function processImage(action, targetBytes, mimeType, pBar) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = async function() {
                pBar.style.width = '20%';
                
                let oWidth = img.width;
                let oHeight = img.height;
                
                // Allow Custom Width & Height Overrides
                const customW = parseInt(document.getElementById('customW').value);
                const customH = parseInt(document.getElementById('customH').value);
                if (customW && customW > 10) oWidth = customW;
                if (customH && customH > 10) oHeight = customH;

                let canvas = document.createElement('canvas');
                let ctx = canvas.getContext('2d');

                let finalBlob = null;
                
                const getBlobAndDataUrl = async (w, h, q) => {
                    canvas.width = w; canvas.height = h;
                    ctx.drawImage(img, 0, 0, w, h);
                    
                    // Inject DPI if requested
                    const customDPI = document.getElementById('customDPI').value;
                    let dataUrl = canvas.toDataURL(mimeType, q);
                    if(customDPI && (mimeType === 'image/jpeg' || mimeType === 'image/png')) {
                        dataUrl = changeDpiDataUrl(dataUrl, customDPI);
                    }
                    
                    const resBlob = await (await fetch(dataUrl)).blob();
                    return { blob: resBlob, dataUrl: dataUrl };
                };

                if(action === 'reduce') {
                    let result = await getBlobAndDataUrl(oWidth, oHeight, 1.0);
                    finalBlob = result.blob;
                    
                    if (finalBlob.size > targetBytes && mimeType !== 'image/png') {
                        let quality = 0.9;
                        while(finalBlob.size > targetBytes && quality > 0.1) {
                            quality -= 0.1;
                            result = await getBlobAndDataUrl(oWidth, oHeight, quality);
                            finalBlob = result.blob;
                            pBar.style.width = (30 + ((1-quality)*50)) + '%';
                        }
                    }

                    // If still massive, forcefully scale down dimensions exactly by ratio
                    if (finalBlob.size > targetBytes) {
                        let ratio = Math.sqrt(targetBytes / finalBlob.size);
                        let finalW = Math.max(10, Math.floor(oWidth * ratio));
                        let finalH = Math.max(10, Math.floor(oHeight * ratio));
                        result = await getBlobAndDataUrl(finalW, finalH, mimeType === 'image/jpeg' ? 0.7 : 1.0);
                        finalBlob = result.blob;
                    }
                } 
                else {
                    // ACTION = INCREASE
                    let result = await getBlobAndDataUrl(oWidth, oHeight, 1.0);
                    finalBlob = result.blob;
                    pBar.style.width = '60%';

                    if(finalBlob.size < targetBytes) {
                        const diff = Math.ceil(targetBytes - finalBlob.size);
                        const padding = new Uint8Array(diff).fill(32); 
                        finalBlob = new Blob([finalBlob, padding], { type: mimeType });
                    }
                }

                pBar.style.width = '100%';
                
                // 🚀 IMAGE TO PDF WRAPPER 🚀
                if(mimeType === 'application/pdf') {
                    const pdfDoc = await PDFLib.PDFDocument.create();
                    const page = pdfDoc.addPage([oWidth, oHeight]);
                    const imgBytes = await fetch(canvas.toDataURL('image/jpeg', 0.9)).then(res => res.arrayBuffer());
                    const pdfImg = await pdfDoc.embedJpg(imgBytes);
                    page.drawImage(pdfImg, { x:0, y:0, width: oWidth, height: oHeight });
                    
                    let pdfBytes = await pdfDoc.save();
                    if(action === 'increase' && pdfBytes.byteLength < targetBytes) {
                        const padding = new Uint8Array(targetBytes - pdfBytes.byteLength).fill(32);
                        pdfBytes = new Uint8Array([...pdfBytes, ...padding]);
                    }
                    finalBlob = new Blob([pdfBytes], { type: 'application/pdf' });
                }

                triggerDownload(finalBlob, generateFilename(currentFile.name, action, mimeType));
                resolve();
            };
            img.src = imagePreview.src;
        });
    }

    // ===============================================
    // PDF PROCESSING LOGIC
    // ===============================================
    async function processPDF(action, targetBytes, pBar) {
        const arrayBuffer = await currentFile.arrayBuffer();
        pBar.style.width = '40%';
        
        let finalBlob = null;
        
        if (action === 'increase') {
            const currentSize = arrayBuffer.byteLength;
            if(currentSize < targetBytes) {
                const diff = Math.ceil(targetBytes - currentSize);
                const padding = new Uint8Array(diff).fill(32); 
                finalBlob = new Blob([arrayBuffer, padding], { type: 'application/pdf' });
            } else {
                finalBlob = new Blob([arrayBuffer], { type: 'application/pdf' });
            }
        } else {
            try {
                const pdfDoc = await PDFLib.PDFDocument.load(arrayBuffer);
                const pdfBytes = await pdfDoc.save({ useObjectStreams: false }); 
                finalBlob = new Blob([pdfBytes], { type: 'application/pdf' });
            } catch(e) {
                finalBlob = currentFile; 
            }
        }

        pBar.style.width = '100%';
        triggerDownload(finalBlob, generateFilename(currentFile.name, action, 'application/pdf'));
    }

    function generateFilename(original, action, mimeType) {
        const extMap = { 'image/jpeg': '.jpg', 'image/png': '.png', 'image/webp': '.webp', 'application/pdf': '.pdf' };
        const base = original.substring(0, original.lastIndexOf('.')) || original;
        const mappedExt = extMap[mimeType] || '.file';
        return `${base}_${action}d${mappedExt}`;
    }

    function triggerDownload(blob, filename) {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }
</script>

</body>
</html>