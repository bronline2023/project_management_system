<?php
$content = file_get_contents('views/photo_studio.php');
preg_match('/<script>(.*?)<\/script>/is', $content, $matches);
if (isset($matches[1])) {
    file_put_contents('app/js_test.js', $matches[1]);
    echo "Extracted JS.\n";
} else {
    echo "No JS found??\n";
}
?>
