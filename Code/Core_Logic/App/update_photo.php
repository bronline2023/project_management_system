<?php
$file = 'views/photo_studio.php';
$content = file_get_contents($file);

// Replace .studio-body CSS rule
$search = '/\.studio-body\s*\{[^\}]*\}/i';
$replace = ".studio-body { 
        background-color: #0f172a; 
        display: flex;
        flex-grow: 1;
        overflow: hidden;
        margin: 0; 
        padding: 0; 
        font-family: 'Segoe UI', Tahoma, sans-serif; 
        color: #f8fafc;
    }";

$content = preg_replace($search, $replace, $content, 1);
file_put_contents($file, $content);

echo "Updated photo_studio flex container.\n";
?>
