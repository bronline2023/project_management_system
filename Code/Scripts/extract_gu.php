<?php
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

$gujaratiRegex = '/[\x{0A80}-\x{0AFF}][\x{0A80}-\x{0AFF}\s\dA-Za-z\.,:\-\(\)\!\'\"\?]*[\x{0A80}-\x{0AFF}]|[\x{0A80}-\x{0AFF}]/u';
$foundStrings = [];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    if (preg_match_all('/([^\r\n>]*[\x{0A80}-\x{0AFF}]+[^\r\n<]*)/u', $content, $matches)) {
        foreach ($matches[1] as $match) {
            $cleaned = trim(strip_tags($match));
            if (!empty($cleaned) && preg_match('/[\x{0A80}-\x{0AFF}]/u', $cleaned)) {
                $foundStrings[$cleaned] = true;
            }
        }
    }
}

$unique = array_keys($foundStrings);
file_put_contents('gujarati_strings.json', json_encode($unique, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Found " . count($unique) . " unique Gujarati lines. Saved to gujarati_strings.json\n";
