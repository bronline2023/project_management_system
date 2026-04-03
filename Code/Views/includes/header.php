<?php
/**
 * views/includes/header.php
 * FIXED: Added Mobile Sidebar CSS & Overlay Styles
 */

// Fetch settings for SEO
$pdo_header = connectDB();
$site_settings = fetchOne($pdo_header, "SELECT app_name, seo_title, seo_description, seo_keywords, google_site_verification, seo_global_code_head, seo_global_code_body FROM settings WHERE id = 1");

$appName = $site_settings['app_name'] ?? 'B R Online Services';
$seoTitle = !empty($site_settings['seo_title']) ? $site_settings['seo_title'] : $appName;
$pageDisplayTitle = isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' . $appName : htmlspecialchars($seoTitle);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <!-- SEO Meta Tags -->
    <title><?= $pageDisplayTitle ?></title>
    <meta name="description" content="<?= htmlspecialchars($site_settings['seo_description'] ?? '') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($site_settings['seo_keywords'] ?? '') ?>">
    <?php if(!empty($site_settings['google_site_verification'])): ?>
    <meta name="google-site-verification" content="<?= htmlspecialchars($site_settings['google_site_verification']) ?>" />
    <?php endif; ?>

    <!-- Custom Global Head Code -->
    <?= $site_settings['seo_global_code_head'] ?? '' ?>

    <link rel="apple-touch-icon" sizes="114x114" href="/apple-icon-114x114.png">
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>img/br_favicon.png">

    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css"> 
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/poster_styles.css">

    <style>
        /* --- DIGITAL CLOCK --- */
        .digital-clock {
            font-family: 'Courier New', Courier, monospace;
            background: rgba(233, 236, 239, 0.7);
            padding: 4px 8px;
            border-radius: 6px;
            color: #333;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-block;
            backdrop-filter: blur(4px);
        }
        @media (max-width: 576px) {
            .digital-clock { font-size: 0.8rem; padding: 2px 6px; }
        }


        /* --- MOBILE SIDEBAR LOGIC --- */
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            min-height: 100vh;
            transition: all 0.3s;
        }

        /* Mobile specific styles */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px; /* Hide by default on mobile */
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                z-index: 9999;
                background: #fff; /* Ensure background is set */
            }
            #sidebar.active {
                margin-left: 0; /* Show when active */
                box-shadow: 2px 0 5px rgba(0,0,0,0.5);
            }
            
            /* Overlay Background */
            .overlay {
                display: none;
                position: fixed;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.7);
                z-index: 9998;
                opacity: 0;
                transition: all 0.5s ease-in-out;
                top: 0;
                left: 0;
            }
            .overlay.active {
                display: block;
                opacity: 1;
            }
            
            /* Show Hamburger on Mobile */
            #sidebarCollapse {
                display: inline-block !important;
            }
        }
    </style>
</head>
<body>
    <!-- Custom Global Body Code -->
    <?= $site_settings['seo_global_code_body'] ?? '' ?>
<div class="overlay"></div>