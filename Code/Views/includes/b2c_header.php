<?php
/**
 * views/includes/b2c_header.php
 * GLOBAL B2C HEADER: Redesigned for Premium Split-Screen Era.
 * - Sticky Glassmorphic Navigation.
 * - Scroll-to-Solid JavaScript transitions.
 * - Global 'Outfit' Font Integration.
 */
require_once __DIR__ . '/../../../config.php';
require_once MODELS_PATH . 'db.php';
$pdo = connectDB();

// Fetch menus
$stmt = $pdo->query("SELECT * FROM b2c_menus WHERE status='active' AND parent_id=0 ORDER BY display_order ASC");
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch settings for logo, header style, and SEO
$stmt_settings = $pdo->query("SELECT app_name, website_logo_url, header_style, website_logo_size, menu_color, menu_active_color, seo_title, seo_description, seo_keywords, seo_og_image, seo_global_code_head, seo_global_code_body, google_site_verification FROM settings WHERE id = 1");
$site_settings = $stmt_settings->fetch(PDO::FETCH_ASSOC);

$appName = $site_settings['app_name'] ?? APP_NAME;
$webLogo = $site_settings['website_logo_url'] ?? '';
$logoSize = $site_settings['website_logo_size'] ?: 45;

$seoTitle = !empty($site_settings['seo_title']) ? $site_settings['seo_title'] : $appName;
$seoDesc = $site_settings['seo_description'] ?? '';
$seoKeys = $site_settings['seo_keywords'] ?? '';
$googleVerify = $site_settings['google_site_verification'] ?? '';
$ogImage = !empty($site_settings['seo_og_image']) ? BASE_URL . $site_settings['seo_og_image'] : ASSETS_URL . 'img/br_logo.png';
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>img/br_favicon.png">
    
    <!-- SEO Meta Tags -->
    <title><?= htmlspecialchars($seoTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($seoDesc) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($seoKeys) ?>">
    <?php if(!empty($googleVerify)): ?>
    <meta name="google-site-verification" content="<?= htmlspecialchars($googleVerify) ?>" />
    <?php endif; ?>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $currentUrl ?>">
    <meta property="og:title" content="<?= htmlspecialchars($seoTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($seoDesc) ?>">
    <meta property="og:image" content="<?= $ogImage ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= $currentUrl ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($seoTitle) ?>">
    <meta property="twitter:description" content="<?= htmlspecialchars($seoDesc) ?>">
    <meta property="twitter:image" content="<?= $ogImage ?>">
    
    <!-- Custom Global Head Code -->
    <?= $site_settings['seo_global_code_head'] ?? '' ?>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --brand-primary: #4f46e5;
            --brand-secondary: #0ea5e9;
            --brand-indigo: #6366f1;
            --brand-blue: #3b82f6;
            --brand-dark: #0f172a;
            --brand-accent: #e11d48;
            --nav-height: 80px;
            --glass-header: rgba(255, 255, 255, 0.85);

            /* Dynamic Menu Colors from Admin */
            --menu-color: <?= htmlspecialchars($site_settings['menu_color'] ?: '#1e293b') ?>;
            --menu-active-color: <?= htmlspecialchars($site_settings['menu_active_color'] ?: '#4f46e5') ?>;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            padding-top: var(--nav-height); /* Prevent jump on scroll */
        }

        /* --- MODERN STICKY HEADER --- */
        .premium-navbar {
            height: var(--nav-height);
            background: transparent;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1100;
            display: flex;
            align-items: center;
        }

        .premium-navbar.scrolled {
            background: var(--glass-header);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            height: 70px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border-bottom: 1px solid rgba(255,255,255,0.5);
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--menu-color) !important;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }

        .scrolled .navbar-brand {
            color: var(--brand-dark) !important;
            font-size: 1.35rem;
        }

        .nav-link {
            color: var(--menu-color) !important;
            opacity: 0.85;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s;
            border-radius: 12px;
        }

        .scrolled .nav-link {
            color: var(--brand-dark) !important;
            opacity: 0.7;
        }

        .nav-link:hover {
            color: var(--brand-primary) !important;
            background: rgba(79, 70, 229, 0.05);
            transform: translateY(-2px);
            opacity: 1;
        }

        .scrolled .nav-link:hover {
            color: var(--brand-primary) !important;
            background: rgba(79, 70, 229, 0.05);
            opacity: 1;
        }

        .nav-link.active {
            position: relative;
            color: var(--menu-active-color) !important;
            background: rgba(79, 70, 229, 0.1) !important;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
            font-weight: 800 !important;
        }

        .scrolled .nav-link.active {
             background: rgba(79, 70, 229, 0.05) !important;
        }

        /* Dropdowns */
        .dropdown-menu {
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            border-radius: 18px;
            padding: 1rem;
            animation: dropdownSlide 0.3s ease-out;
            background: white;
        }

        @keyframes dropdownSlide {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dropdown-item {
            font-weight: 600;
            padding: 0.7rem 1.2rem;
            border-radius: 12px;
            transition: all 0.2s;
            color: #475569;
        }

        .dropdown-item:hover {
            background: rgba(79, 70, 229, 0.08);
            color: var(--brand-primary);
            transform: translateX(5px);
        }

        /* Action Buttons */
        .btn-nav-primary {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            color: white !important;
            border-radius: 50px;
            padding: 0.6rem 2rem !important;
            font-weight: 800;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
            border: none;
            transition: all 0.3s;
            letter-spacing: 0.5px;
        }

        .btn-nav-primary:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 30px rgba(79, 70, 229, 0.5);
            background: linear-gradient(135deg, var(--brand-secondary), var(--brand-primary));
        }

        .btn-nav-outline {
            background: rgba(0, 0, 0, 0.03);
            color: var(--menu-color) !important;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 50px;
            padding: 0.6rem 1.8rem !important;
            font-weight: 700;
            transition: all 0.3s;
        }

        .scrolled .btn-nav-outline {
            color: var(--brand-dark) !important;
            border-color: #e2e8f0;
            background: #f8fafc;
        }

        .btn-nav-outline:hover {
            background: var(--brand-primary);
            color: white !important;
            transform: translateY(-2px);
            border-color: var(--brand-primary);
        }

        .b2b-badge {
            background: linear-gradient(135deg, #e11d48, #f43f5e);
            color: white;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 5px;
            font-weight: 800;
            text-transform: uppercase;
            box-shadow: 0 2px 8px rgba(225, 29, 72, 0.4);
            vertical-align: middle;
        }

        .scrolled .btn-nav-outline:hover {
            background: var(--brand-primary);
            color: white !important;
            border-color: var(--brand-primary);
        }

        #mobile-toggle {
            color: var(--menu-color) !important;
            border: none;
            font-size: 1.5rem;
        }

        .scrolled #mobile-toggle {
            color: var(--brand-dark) !important;
        }

        /* --- MOBILE DRAWER (Improved) --- */
        @media (max-width: 991px) {
            .navbar-collapse {
                background: white;
                position: fixed;
                top: 0; left: -100%;
                width: 300px;
                height: 100vh;
                padding: 2rem;
                display: block !important;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 20px 0 50px rgba(0,0,0,0.1);
                z-index: 2000;
            }
            .navbar-collapse.show { left: 0; }
            .nav-link { color: var(--brand-dark) !important; padding: 1rem 0 !important; border-bottom: 1px solid #f1f5f9; border-radius: 0; }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Custom Global Body Code -->
    <?= $site_settings['seo_global_code_body'] ?? '' ?>

<nav class="navbar navbar-expand-lg premium-navbar transition-all" id="mainNav">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand" href="<?= BASE_URL ?>?page=b2c_home">
            <?php if(!empty($webLogo)): ?>
                <img src="<?= htmlspecialchars(BASE_URL . $webLogo) ?>" alt="Logo" style="height: <?= (int)$logoSize ?>px; object-fit: contain; margin-right: 12px;">
            <?php else: ?>
                <i class="fas fa-layer-group text-primary me-2"></i>
            <?php endif; ?>
            <span class="d-none d-sm-inline"><?= htmlspecialchars($appName) ?></span>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" id="mobile-toggle" aria-label="Toggle navigation">
            <i class="fas fa-bars-staggered"></i>
        </button>

        <div class="collapse navbar-collapse" id="b2cNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link active" href="<?= BASE_URL ?>?page=b2c_home"><i class="fas fa-home-alt me-1"></i> Home</a></li>
                
                <?php foreach($menus as $menu): ?>
                    <?php
                        $m_id = $menu['id'];
                        $sub_stmt = $pdo->prepare("SELECT * FROM b2c_menus WHERE parent_id=? AND status='active' ORDER BY display_order ASC");
                        $sub_stmt->execute([$m_id]);
                        $submenus = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php if(count($submenus) > 0): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= htmlspecialchars($menu['title']) ?></a>
                            <ul class="dropdown-menu">
                                <?php foreach($submenus as $sm): ?>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>?page=b2c_page&slug=<?= $sm['slug'] ?>"><?= htmlspecialchars($sm['title']) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $menu['is_dynamic_page'] ? BASE_URL.'?page=b2c_page&slug='.$menu['slug'] : $menu['link'] ?>"><?= htmlspecialchars($menu['title']) ?></a></li>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <li class="nav-item d-lg-none mt-3"><a class="nav-link" href="<?= BASE_URL ?>?page=appointment">Book Appointment</a></li>
            </ul>

            <!-- Auth/CTA Section -->
            <div class="d-flex align-items-center gap-2">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="<?= BASE_URL ?>?page=dashboard" class="btn btn-nav-primary px-4">
                        <i class="fas fa-gauge-high me-2"></i> Dashboard
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>?page=login" class="btn btn-nav-outline d-none d-md-inline-block">
                        B2B Portal <span class="b2b-badge">Pro</span>
                    </a>
                    <a href="<?= BASE_URL ?>?page=b2c_login" class="btn btn-nav-outline d-none d-md-inline-block">
                        User Login
                    </a>
                    <a href="<?= BASE_URL ?>?page=appointment" class="btn btn-nav-primary d-none d-lg-inline-block">
                        <i class="fas fa-calendar-check me-2"></i> Book Visit
                    </a>
                    <a href="<?= BASE_URL ?>?page=login" class="nav-link d-lg-none small"><i class="fas fa-user-tie"></i> B2B Portal</a>
                    <a href="<?= BASE_URL ?>?page=b2c_login" class="nav-link d-lg-none small"><i class="fas fa-user"></i> Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- JavaScript for Interactive Header -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const nav = document.getElementById('mainNav');
        const toggle = document.getElementById('mobile-toggle');
        const collapse = document.getElementById('b2cNav');

        // Scroll listener for transparent -> solid transition
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });

        // Check scroll position on load
        if (window.scrollY > 50) nav.classList.add('scrolled');

        // Mobile drawer toggle
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            collapse.classList.toggle('show');
        });

        // Close drawer when clicking outside
        document.addEventListener('click', () => {
            collapse.classList.remove('show');
        });
    });
</script>
