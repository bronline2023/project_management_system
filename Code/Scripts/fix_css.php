<?php
$files = [
    'views/poster_studio.php',
    'views/resume_builder.php',
    'views/smart_card.php',
    'views/passport_photo.php',
    'views/photo_studio.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    // 1. Revert global sloppy replace
    $content = str_replace('align-items: flex-start; padding-top: 30px;', 'align-items: center;', $content);
    $content = str_replace('align-items: flex-start; padding-top: 40px;', 'align-items: center;', $content);

    // 2. ONLY replace for .workspace and .preview-panel correctly.
    // They usually look like:
    // .workspace { position: relative; flex-grow: 1; display: flex; justify-content: center; align-items: center; overflow: auto; padding: 20px; background-image: radial-gradient(#64748b 1px, transparent 0); background-size: 20px 20px; }
    
    // We can use preg_replace specifically for the workspace block.
    $content = preg_replace(
        '/(\.workspace\s*\{[^}]*?)align-items:\s*center;([^}]*?padding:\s*)(\d+)px;/', 
        '$1align-items: flex-start;$2$3px $3px $3px $3px; padding-top: 40px;', 
        $content
    );

    // Some might just have padding: 0.
    $content = preg_replace(
        '/(\.workspace\s*\{[^}]*?)align-items:\s*center;([^}]*?padding:\s*0;)/', 
        '$1align-items: flex-start;$2 padding-top: 40px;', 
        $content
    );

    $content = preg_replace(
        '/(\.preview-panel\s*\{[^}]*?)align-items:\s*center;([^}]*?padding:\s*)(\d+)px;/', 
        '$1align-items: flex-start;$2$3px $3px $3px $3px; padding-top: 40px;', 
        $content
    );

    file_put_contents($file, $content);
    echo "Fixed CSS scoping in $file\n";
}
