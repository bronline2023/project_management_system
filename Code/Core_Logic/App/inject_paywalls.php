<?php
$services = [
    'poster_studio', 'resume_builder', 'smart_card', 'passport_photo', 
    'document_converter', 'size_converter', 'photo_studio'
];

foreach ($services as $service) {
    $file = __DIR__ . "/../views/$service.php";
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Don't inject twice
        if (strpos($content, 'enforce_service_paywall') === false) {
            $inject = "<?php\nrequire_once __DIR__ . '/includes/service_paywall.php';\nenforce_service_paywall('$service');\n?>\n";
            
            if (strncmp(trim($content), "<?php", 5) === 0) {
                // replace first <?php
                $content = preg_replace('/<\?php/', "<?php\nrequire_once __DIR__ . '/includes/service_paywall.php';\nenforce_service_paywall('$service');\n", $content, 1);
            } else {
                $content = $inject . $content;
            }
            
            file_put_contents($file, $content);
            echo "Injected into $service.php\n";
        } else {
            echo "Already injected in $service.php\n";
        }
    } else {
        echo "File not found: $service.php\n";
    }
}
?>
