<?php
$services = [
    'poster_studio', 'resume_builder', 'smart_card', 'passport_photo', 
    'document_converter', 'photo_studio'
];

foreach ($services as $service) {
    $file = __DIR__ . "/../views/$service.php";
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        $replaced = str_replace("if (userRole !== 'admin') {", "if (false && userRole !== 'admin') {", $content);
        
        if ($replaced !== $content) {
            file_put_contents($file, $replaced);
            echo "Bypassed JS deduction in $service.php\n";
        } else {
            echo "No changes needed in $service.php\n";
        }
    }
}
?>
