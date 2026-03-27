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

    // 1. Fix the missing </head><body> tag.
    $brokenBoilerplate = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">';
    $fixedBoilerplate = $brokenBoilerplate . "\n</head>\n<body>";
    
    // Only replace if not already replaced
    if (strpos($content, '</head>') === false) {
        $content = str_replace($brokenBoilerplate, $fixedBoilerplate, $content);
    }
    
    // 2. Make the Back button text dynamic
    // Target variants of the back button text. The actual translation script might have translated "⬅ Dashboard"
    $searchTexts = [
        '⬅ DASHBOARD',
        '⬅ Dashboard',
        '⬅ ડેશબોર્ડ',
        '<i class="fas fa-arrow-left"></i> Dashboard',
        '<i class="fas fa-arrow-left"></i> DASHBOARD'
    ];
    
    // Some buttons might be wrapped differently. Let's do a regex replacement for the content inside the anchor tag that has class="btn-back" or btn-refresh near it.
    // Actually, it's easier to find the exact line.
    $content = preg_replace(
        '/(<a[^>]*class="[^"]*btn-back[^"]*"[^>]*>).*?(<\/a>)/is',
        '$1⬅ <?= isset($_SESSION["user_id"]) ? "DASHBOARD" : "WEBSITE" ?>$2',
        $content
    );

    file_put_contents($file, $content);
    echo "Fixed $file\n";
}
