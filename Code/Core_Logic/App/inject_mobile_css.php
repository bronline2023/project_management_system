<?php
$files = [
    'poster_studio.php',
    'resume_builder.php',
    'smart_card.php',
    'passport_photo.php'
];

$responsive_css = "
    /* ========================================================== */
    /* 📱 MOBILE RESPONSIVENESS FIXES (Injected by System) 📱      */
    /* ========================================================== */
    @media (max-width: 992px) {
        .studio-wrapper, .builder-wrapper { flex-direction: column !important; height: auto !important; width: 100vw !important; overflow-x: hidden; }
        .editor-panel { width: 100% !important; min-width: 100% !important; height: auto !important; max-height: 55vh; overflow-y: auto; border-right: none !important; border-bottom: 2px solid #cbd5e1; }
        .preview-panel { width: 100% !important; height: 45vh !important; min-height: 45vh !important; padding: 10px !important; overflow-y: auto; }
        .canvas-container { max-width: 100% !important; height: auto !important; margin: 0 auto; }
        canvas { max-width: 100% !important; height: auto !important; }
        /* Scale down Previews */
        .a4-page, .card-preview { max-width: 100%; transform: scale(0.65) !important; transform-origin: top center !important; margin-bottom: 0 !important; }
        .mobile-gap { margin-bottom: 60px; }
        .action-btns { flex-wrap: wrap; justify-content: center; width: 100%; }
        .btn-export { width: 100%; margin-top: 10px; }
    }
";

foreach ($files as $f) {
    $path = __DIR__ . "/../views/" . $f;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        if (strpos($content, 'MOBILE RESPONSIVENESS FIXES') === false) {
            $content = str_replace('</style>', $responsive_css . "\n</style>", $content);
            file_put_contents($path, $content);
            echo "Injected mobile CSS into $f\n";
        } else {
            echo "Mobile CSS already exists in $f\n";
        }
    }
}
?>
