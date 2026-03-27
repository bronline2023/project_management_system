<?php
// Auto Translator for Gujarati -> English

$json = file_get_contents('gujarati_strings.json');
$strings = json_decode($json, true);

$translations = [];

foreach ($strings as $index => $text) {
    if (empty(trim($text))) continue;
    
    // Quick delay to avoid rate limiting
    usleep(100000); 
    
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=gu&tl=en&dt=t&q=" . urlencode($text);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data[0]) && is_array($data[0])) {
            $translated = '';
            foreach ($data[0] as $chunk) {
                if (isset($chunk[0])) {
                    $translated .= $chunk[0];
                }
            }
            $translations[$text] = $translated;
            echo "Translated: $translated \n";
        }
    }
}

file_put_contents('translations_map.json', json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\nFinished generating translation map.\n";

// NOW APPLY TRANSLATIONS TO FILES
$files = [
    'views/admin_wallet_requests.php',
    'views/buy_subscription.php',
    'views/create_user_direct.php',
    'views/document_converter.php',
    'views/includes/footer.php',
    'views/includes/manage_b2b_users.php',
    'views/includes/sidebar.php',
    'views/manage_service_rates.php',
    'views/manage_users.php',
    'views/master_portals.php',
    'views/passport_photo.php',
    'views/photo_studio.php',
    'views/poster_studio.php',
    'views/process_recharge.php',
    'views/resume_builder.php',
    'views/size_converter.php',
    'views/smart_card.php',
    'views/wallet_recharge.php',
    'app/actions.php',
    'app/chat_api.php',
    'app/clean_blocks.php',
    'app/create_b2b_user.php',
    'app/deduct_poster_balance.php',
    'app/js_test.js',
    'app/manual_recharge.php',
    'app/process_subscription.php',
    'app/update_docs.php',
    'app/update_smart_card.php',
    'app/upload_handler.php',
    'index.php'
];

// Sort by length descending, so longer strings get replaced first to avoid partial replacements
uksort($translations, function($a, $b) {
    return mb_strlen($b) - mb_strlen($a);
});

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    $original = $content;
    
    foreach ($translations as $gu => $en) {
        // Simple string replace for now.
        $content = str_replace($gu, $en, $content);
    }
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Updated $file with English translations.\n";
    }
}
