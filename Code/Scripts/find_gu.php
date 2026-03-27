<?php
$dir = new RecursiveDirectoryIterator('views/');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/^.*\.(php|html|js)$/', RegexIterator::GET_MATCH);

$gujaratiRegex = '/[\x{0A80}-\x{0AFF}]+/u';
$found = [];

foreach($files as $file) {
    if (strpos($file[0], 'vendor') !== false || strpos($file[0], 'node_modules') !== false) continue;
    
    $content = file_get_contents($file[0]);
    if (preg_match_all($gujaratiRegex, $content, $matches)) {
        if (!empty($matches[0])) {
            $found[] = $file[0];
        }
    }
}

// Add app/ and includes/
$extraPaths = ['app/', 'includes/'];
foreach ($extraPaths as $path) {
    if (!is_dir($path)) continue;
    $dir = new RecursiveDirectoryIterator($path);
    $ite = new RecursiveIteratorIterator($dir);
    $files = new RegexIterator($ite, '/^.*\.(php|html|js)$/', RegexIterator::GET_MATCH);
    foreach($files as $file) {
        $content = file_get_contents($file[0]);
        if (preg_match_all($gujaratiRegex, $content, $matches)) {
            if (!empty($matches[0])) {
                $found[] = $file[0];
            }
        }
    }
}

// include core files
$core = ['index.php'];
foreach ($core as $file) {
    $content = file_get_contents($file);
    if (preg_match_all($gujaratiRegex, $content, $matches)) {
        if (!empty($matches[0])) {
            $found[] = $file;
        }
    }
}

echo "Files with Gujarati text:\n" . implode("\n", array_unique($found)) . "\n";
