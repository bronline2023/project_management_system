<?php
$file = 'views/size_converter.php';
$content = file_get_contents($file);

// Replace processImage function and dropdown logic
$search_js = '/async function processImage.*?function generateFilename/is';

$replace_js = <<<'EOD'
async function processImage(action, targetBytes, mimeType, pBar) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = async function() {
                pBar.style.width = '20%';
                
                let oWidth = img.width;
                let oHeight = img.height;
                
                let canvas = document.createElement('canvas');
                let ctx = canvas.getContext('2d');
                canvas.width = oWidth;
                canvas.height = oHeight;
                ctx.drawImage(img, 0, 0);

                let finalBlob = null;
                
                // Helper to get blob
                const getBlob = async (w, h, q) => {
                    canvas.width = w; canvas.height = h;
                    ctx.drawImage(img, 0, 0, w, h);
                    return new Promise(res => canvas.toBlob(res, mimeType, q));
                };

                if(action === 'reduce') {
                    // Try Quality Compression first (Only works for JPEG/WEBP)
                    if(mimeType !== 'image/png') {
                        let minQ = 0.05, maxQ = 1.0, quality = 0.7;
                        for(let i=0; i<7; i++) {
                            let b = await getBlob(oWidth, oHeight, quality);
                            if(b.size <= targetBytes && b.size > targetBytes * 0.9) { finalBlob = b; break; }
                            if(b.size > targetBytes) maxQ = quality; else minQ = quality;
                            quality = (minQ + maxQ) / 2;
                            finalBlob = b;
                        }
                    } else {
                        // For PNG, just get the initial blob to see how big it is
                        finalBlob = await getBlob(oWidth, oHeight, 1.0);
                    }

                    // If quality wasn't enough (or it's PNG), aggressively SHRINK dimensions
                    let attempts = 0;
                    let currentW = oWidth, currentH = oHeight;
                    
                    while(finalBlob && finalBlob.size > targetBytes && attempts < 10) {
                        let ratio = Math.sqrt(targetBytes / finalBlob.size) * 0.95; 
                        currentW = Math.max(50, Math.floor(currentW * ratio));
                        currentH = Math.max(50, Math.floor(currentH * ratio));
                        
                        finalBlob = await getBlob(currentW, currentH, mimeType !== 'image/png' ? 0.7 : 1.0);
                        attempts++;
                        pBar.style.width = (40 + attempts * 5) + '%';
                    }
                    
                    // If STILL too big (extreme case), just keep shrinking by 10%
                    while(finalBlob && finalBlob.size > targetBytes && currentW > 20) {
                        currentW = Math.floor(currentW * 0.9);
                        currentH = Math.floor(currentH * 0.9);
                        finalBlob = await getBlob(currentW, currentH, 0.5);
                    }
                } 
                else {
                    // ACTION = INCREASE
                    finalBlob = await getBlob(oWidth, oHeight, 1.0);
                    pBar.style.width = '60%';

                    if(finalBlob.size < targetBytes) {
                        const diff = Math.ceil(targetBytes - finalBlob.size);
                        // Using spaces (0x20) for padding to avoid corruption in some rigid parsers
                        const padding = new Uint8Array(diff).fill(32); 
                        finalBlob = new Blob([finalBlob, padding], { type: mimeType });
                    } else {
                        // User chose "Increase" but target is actually smaller than original! So we just give original or try to shrink it?
                        // Let's just output the pristine 1.0 quality image. It will be whatever size it is.
                    }
                }

                pBar.style.width = '100%';
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

    function generateFilename
EOD;

$content = preg_replace($search_js, $replace_js, $content);

// Also fix Output format dropdown to hide properly if PDF
$search_ui = "document.getElementById('outputFormat').value = 'original'; // Force original";
$replace_ui = "document.getElementById('outputFormat').value = 'original';\n            document.getElementById('outputFormat').disabled = true;\n            document.getElementById('outputFormat').style.opacity = '0.5';";

$search_ui_img = "document.getElementById('pdfWarning').style.display = 'none';";
$replace_ui_img = "document.getElementById('pdfWarning').style.display = 'none';\n            document.getElementById('outputFormat').disabled = false;\n            document.getElementById('outputFormat').style.opacity = '1';";

$content = str_replace($search_ui, $replace_ui, $content);
$content = str_replace($search_ui_img, $replace_ui_img, $content);

file_put_contents($file, $content);
echo "Size Converter Updated.\n";

?>
