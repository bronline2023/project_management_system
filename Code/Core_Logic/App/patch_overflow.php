<?php
$files = [
    'views/smart_card.php',
    'views/passport_photo.php',
    'views/poster_studio.php',
    'views/resume_builder.php',
    'views/document_converter.php',
    'views/size_converter.php',
    'views/photo_studio.php'
];

foreach ($files as $f) {
    $path = 'c:/xampp/htdocs/project_management_system/' . $f;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        // Remove 'overflow: hidden;' from body
        $content = preg_replace('/body\s*\{[^}]*overflow\s*:\s*hidden\s*;[^}]*\}/i', 
            preg_replace('/overflow\s*:\s*hidden\s*;/i', 'overflow: auto;', 
            preg_match('/body\s*\{[^}]*overflow\s*:\s*hidden\s*;[^}]*\}/i', $content, $m) ? $m[0] : ''), 
        $content);

        // Adjust 100vh wrappers to allow scrolling
        // .studio-wrapper, .builder-wrapper, .converter-wrapper
        $content = preg_replace('/height\s*:\s*100vh\s*;/i', 'min-height: 100vh; height: 100%;', $content);

        // Some specific blocks
        $content = str_replace('overflow: hidden', 'overflow: auto', $content);

        file_put_contents($path, $content);
        echo "Patched overflow in $f\n";
    }
}
?>
