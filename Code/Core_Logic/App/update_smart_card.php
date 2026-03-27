<?php
$file = 'views/smart_card.php';
$content = file_get_contents($file);

// Replace 1: UI controls
$search1 = '<div class="control-box">
                    <label class="form-label">Forced HD format (1040 x 638 px)</label>
                    <select class="form-control fw-bold text-success" id="exportFormat">
                        <option value="png">High Quality PNG (Best for PVC)</option>
                        <option value="jpeg">High Quality JPG</option>
                    </select>
                </div>';

$replace1 = '<div class="control-box">
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
                </div>';

// Using regex to normalize line endings for search 1 just in case
$content = preg_replace('/<div class="control-box">\s*<label class="form-label">Forced HD format.*?<\/select>\s*<\/div>/is', $replace1, $content);

// Replace 2: Export logic
$search2_regex = '/function processFinalDownload\(\) \{.*?img\.src = dataUrl;\s*\}/is';

$replace2 = <<<'EOD'
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
                    ctx.fillStyle = "#ffffff";
                    ctx.fillRect(10, EXPORT_HEIGHT - 120, EXPORT_WIDTH - 20, 110);
                    ctx.fillStyle = "#000000";
                    ctx.font = "bold 28px Arial";
                    ctx.textAlign = "center";
                    ctx.fillText("Aadhaar is proof of identity, not of citizenship or date of birth.", EXPORT_WIDTH/2, EXPORT_HEIGHT - 60);
                    ctx.fillStyle = "#dc2626";
                    ctx.font = "24px Arial";
                    ctx.fillText("It should be verified online / offline before accepting it.", EXPORT_WIDTH/2, EXPORT_HEIGHT - 25);
                }

                if(returnBlobOnly) {
                    resolve(tempCanvas);
                } else {
                    const finalData = tempCanvas.toDataURL('image/jpeg', 1.0);
                    const link = document.createElement('a');
                    link.download = filename + '.jpg';
                    link.href = finalData;
                    link.click();
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
    }
EOD;

$content = preg_replace($search2_regex, $replace2, $content);

file_put_contents($file, $content);
echo "Replaced successfully\n";
?>
