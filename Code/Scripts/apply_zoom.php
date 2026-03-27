<?php
$files = [
    'views/poster_studio.php',
    'views/resume_builder.php',
    'views/smart_card.php',
    'views/passport_photo.php',
    'views/photo_studio.php'
];

$zoomUI = <<<HTML
    <div class="sys-zoom-controls" style="position: absolute; bottom: 20px; right: 20px; background: rgba(255,255,255,0.95); padding: 8px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); display: flex; flex-direction: column; gap: 5px; z-index: 9999; border: 1px solid #cbd5e1;">
        <div style="font-size: 10px; font-weight: bold; color: #475569; text-align: center; margin-bottom: 2px;">ZOOM</div>
        <button type="button" onclick="sysChangeZoom(0.1)" style="background: #f1f5f9; border: 1px solid #94a3b8; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-weight: bold; transition: 0.2s;">➕</button>
        <button type="button" onclick="sysResetZoom()" style="background: #f1f5f9; border: 1px solid #94a3b8; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-size: 10px; font-weight: bold; transition: 0.2s;">100%</button>
        <button type="button" onclick="sysChangeZoom(-0.1)" style="background: #f1f5f9; border: 1px solid #94a3b8; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-weight: bold; transition: 0.2s;">➖</button>
    </div>
HTML;

$zoomScript = <<<JS
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
            el.style.transform = `scale(\${sysCurrentZoom})`;
            el.style.transformOrigin = 'top center';
            el.style.transition = 'transform 0.2s ease';
            el.style.marginBottom = '50px'; // Prevent cutoffs when scaled up
        });
    }
</script>
JS;

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    // 1. Fix CSS overflow by changing align-items
    $content = preg_replace('/(class="workspace"[^>]*>)/i', '$1' . "\n" . $zoomUI, $content, 1);
    $content = preg_replace('/(class="preview-panel"[^>]*>)/i', '$1' . "\n" . $zoomUI, $content, 1);
    
    // Fix align-items: center -> flex-start + padding
    $content = str_replace('align-items: center;', 'align-items: flex-start; padding-top: 30px;', $content);
    $content = preg_replace('/(\.workspace\s*\{[^}]*)align-items:\s*center;/i', '$1align-items: flex-start; padding-top: 40px;', $content);
    $content = preg_replace('/(\.preview-panel\s*\{[^}]*)align-items:\s*flex-start;/i', '$1align-items: flex-start; padding-top: 40px;', $content);

    // Make workspace relative so zoom controls float correctly inside it
    if (strpos($content, '.workspace {') !== false && strpos($content, 'position: relative') === false) {
        $content = str_replace('.workspace {', '.workspace { position: relative;', $content);
    }
    if (strpos($content, '.preview-panel {') !== false && strpos($content, 'position: relative') === false) {
        $content = str_replace('.preview-panel {', '.preview-panel { position: relative;', $content);
    }

    // 2. Inject Javascript before </body>
    if (strpos($content, 'function sysApplyZoom()') === false) {
        $content = str_replace('</body>', $zoomScript . "\n</body>", $content);
    }

    file_put_contents($file, $content);
    echo "Updated $file\n";
}
