<?php
$files = [
    'views/poster_studio.php',
    'views/resume_builder.php',
    'views/smart_card.php',
    'views/passport_photo.php',
    'views/photo_studio.php',
    'views/document_converter.php',
    'views/size_converter.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    $content = preg_replace('/(⬅\s*Dashboard)/i', '⬅ <?= isset($_SESSION[\'user_id\']) ? \'DASHBOARD\' : \'WEBSITE\' ?>', $content);
    file_put_contents($file, $content);
    echo "Fixed $file\n";
}
