<?php
$file = 'views/document_converter.php';
$content = file_get_contents($file);

// 1. Add Universal Image Converter card
$search1 = '<div class="section-title">Image & Compression Tools</div>';
$replace1 = '<div class="section-title text-success">Universal Image Converter</div>
    <div class="tools-grid">
        <div class="tool-card" style="border: 2px solid #10b981;" onclick="openTool(\'universal_image\', \'Universal Image Converter\', \'કોઈપણ ફોટાને (JPG, PNG, WEBP) બીજા ફોર્મેટમાં ફેરવો\', \'image/*\')">
            <i class="fas fa-sync-alt tool-icon icon-green"></i>
            <div class="tool-title text-success">Multi-Format Image Converter</div>
            <div class="tool-desc">Seamless offline conversion between JPG, PNG, WEBP.</div>
        </div>
    </div>
    <div class="section-title">Image & Compression Tools</div>';

$content = str_replace($search1, $replace1, $content);

// 2. Add target format UI logic
$search2 = '<div class="config-panel" id="configPanel" style="background:#fff;">';
$replace2 = '<div id="formatPanel" style="display:none; background:#f1f5f9; padding:20px; border-radius:8px; margin:20px auto; max-width:800px; border:1px solid #cbd5e1;">
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
    <div class="config-panel" id="configPanel" style="background:#fff;">';

$content = str_replace($search2, $replace2, $content);

// 3. Inject JS UI toggles
$search3_regex = "/if \(toolId === 'compress_image'\) \{.*?\} else \{.*?\}/is";
$replace3 = <<<'EOD'
if (toolId === 'compress_image') {
            document.getElementById('configPanel').style.display = 'block';
            document.getElementById('formatPanel').style.display = 'none';
        } else if (toolId === 'universal_image') {
            document.getElementById('configPanel').style.display = 'none';
            document.getElementById('formatPanel').style.display = 'block';
        } else {
            document.getElementById('configPanel').style.display = 'none';
            document.getElementById('formatPanel').style.display = 'none';
        }
EOD;

$content = preg_replace($search3_regex, $replace3, $content);

// 4. Inject execution logic in processConversion()
$search4 = 'if (currentTool === \'compress_image\') {';
$replace4 = <<<'EOD'
if (currentTool === 'universal_image') {
            processUniversalImageConversion();
        } else if (currentTool === 'compress_image') {
EOD;

$content = str_replace($search4, $replace4, $content);

// 5. Append Universal Image Function
$search5 = '</script>';
$replace5 = <<<'EOD'
    async function processUniversalImageConversion() {
        if (!selectedFile || !selectedFile.type.startsWith('image/')) {
            alert("Please select an image.");
            return;
        }

        const targetFormat = document.getElementById('targetImageFormat').value;
        const extMap = { 'image/jpeg': '.jpg', 'image/png': '.png', 'image/webp': '.webp' };
        const ext = extMap[targetFormat] || '.jpg';
        const newFilename = selectedFile.name.substring(0, selectedFile.name.lastIndexOf('.')) + "_converted" + ext;

        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);

                const dataUrl = canvas.toDataURL(targetFormat, 1.0);
                finalizeDownload(dataUrl, newFilename);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(selectedFile);
    }
</script>
EOD;

$content = str_replace($search5, $replace5, $content);

file_put_contents($file, $content);
echo "document_converter.php updated successfully.\n";
?>
