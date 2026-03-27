<?php
// b2c_header.php
require_once __DIR__ . '/../../../config.php';
require_once MODELS_PATH . 'db.php';
$pdo = connectDB();

// Fetch menus
$stmt = $pdo->query("SELECT * FROM b2c_menus WHERE status='active' AND parent_id=0 ORDER BY display_order ASC");
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch settings for logo and header style
$stmt_settings = $pdo->query("SELECT app_name, website_logo_url, header_style, website_logo_size, menu_color, menu_active_color FROM settings WHERE id = 1");
$site_settings = $stmt_settings->fetch(PDO::FETCH_ASSOC);

$appName = $site_settings['app_name'] ?? APP_NAME;
$webLogo = $site_settings['website_logo_url'] ?? '';
$headerStyle = $site_settings['header_style'] ?? 'style1';
$logoSize = $site_settings['website_logo_size'] ?: 40;
$menuColor = $site_settings['menu_color'] ?? '';
$menuActiveColor = $site_settings['menu_active_color'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName) ?> - Digital Online Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f39c12;
            --dark-bg: #1e293b;
            --nav-text: #ffffff;
        }
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; }
        
        /* Base Navbar Styles */
        .custom-navbar { padding: 15px 0; transition: all 0.3s; z-index: 1040; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .nav-link { font-weight: 500; font-size: 1.05rem; margin: 0 4px; border-radius: 8px; transition: all 0.2s ease-in-out; }
        .dropdown-menu { border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border-radius: 12px; overflow: hidden; }
        .dropdown-item { padding: 10px 20px; font-weight: 500; }
        .dropdown-item:hover { background-color: rgba(14,165,233,0.1); color: var(--primary-color); }
        .brand-logo-img { max-height: 40px; object-fit: contain; }
        .navbar-brand { font-weight: 800; font-size: 1.5rem; letter-spacing: -0.5px; }
        
        /* Buttons */
        .btn-login { font-weight: 600; padding: 8px 25px; border-radius: 50px; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; margin-right: 10px; transition: all 0.2s;}
        .btn-signup { font-weight: 700; padding: 8px 25px; border-radius: 50px; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; transition: all 0.2s;}
        .btn-login:hover, .btn-signup:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }

        /* Style 1: Modern Centered */
        .header-style1 { background-color: var(--dark-bg); }
        .header-style1 .nav-link { color: rgba(255,255,255,0.7) !important; }
        .header-style1 .nav-link:hover { color: #ffffff !important; }
        .header-style1 .navbar-brand { color: #ffffff !important; }
        .header-style1 .btn-login { border: 2px solid rgba(255,255,255,0.4); color: white; }
        .header-style1 .btn-login:hover { background: white; color: var(--dark-bg); }
        .header-style1 .btn-signup { background: var(--secondary-color); color: white; border: 2px solid var(--secondary-color); }
        
        /* Style 2: Classic Left */
        .header-style2 { background-color: #ffffff; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-bottom: none; }
        .header-style2 .nav-link { color: #475569 !important; }
        .header-style2 .nav-link:hover { color: var(--primary-color) !important; background: rgba(74, 144, 226, 0.05); }
        .header-style2 .navbar-brand { color: var(--dark-bg) !important; }
        .header-style2 .btn-login, .header-style2 .btn-signup { border: 2px solid var(--primary-color); color: var(--primary-color); background: transparent; }
        .header-style2 .btn-login:hover, .header-style2 .btn-signup:hover { background: var(--primary-color); color: white; }
        .header-style2 .navbar-toggler { border-color: #ddd; color: #333; }
        
        /* Style 3: Minimal Dark */
        .header-style3 { background-color: #000000; border-bottom: 1px solid #222; }
        .header-style3 .nav-link { color: #a1a1aa !important; }
        .header-style3 .nav-link:hover { color: #ffffff !important; }
        .header-style3 .navbar-brand { color: #ffffff !important; }
        .header-style3 .btn-login { color: #ffffff; background: transparent; border: 1px solid #333; }
        .header-style3 .btn-login:hover { border-color: #fff; }
        .header-style3 .btn-signup { background: #ffffff; color: #000; border: none; }
        
        /* Style 4: Vibrant Gradient */
        .header-style4 { background: linear-gradient(135deg, #0ea5e9, #4f46e5); border-bottom: none; }
        .header-style4 .nav-link { color: rgba(255,255,255,0.9) !important; }
        .header-style4 .nav-link:hover { color: #ffffff !important; background: rgba(255,255,255,0.1); }
        .header-style4 .navbar-brand { color: #ffffff !important; }
        .header-style4 .btn-login { color: white; border: 2px solid rgba(255,255,255,0.5); }
        .header-style4 .btn-login:hover { background: white; color: #4f46e5; }
        .header-style4 .btn-signup { background: #f59e0b; color: white; border: none; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4); }

        /* Dynamic Custom Color Overrides */
        <?php if(!empty($menuColor)): ?>
            .custom-navbar .nav-link { color: <?= htmlspecialchars($menuColor) ?> !important; opacity: 0.9; }
            .custom-navbar .nav-link:hover { color: <?= htmlspecialchars($menuColor) ?> !important; opacity: 1; filter: brightness(1.2); }
        <?php endif; ?>
        <?php if(!empty($menuActiveColor)): ?>
            .custom-navbar .nav-link.active, .custom-navbar .nav-link:hover, .custom-navbar .nav-item:hover > .nav-link { 
                color: <?= htmlspecialchars($menuActiveColor) ?> !important; 
                opacity: 1; 
                font-weight: bold;
            }
        <?php endif; ?>

    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg sticky-top custom-navbar header-<?= htmlspecialchars($headerStyle) ?>" style="display: flex !important;">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>?page=b2c_home" style="position:relative; z-index:1050; height: 50px; min-width: 200px;">
            <?php if(!empty($webLogo)): ?>
                <!-- Visible hanging logo escaping layout flow -->
                <img src="<?= htmlspecialchars(BASE_URL . $webLogo) ?>" alt="<?= htmlspecialchars($appName) ?> Logo" class="brand-logo-img" style="position:absolute; left:0; top:50%; transform:translateY(-50%); max-height: <?= (int)$logoSize ?>px; max-width: 280px; object-fit: contain;">
            <?php else: ?>
                <i class="fas fa-layer-group me-2"></i> <?= htmlspecialchars($appName) ?>
            <?php endif; ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#b2cNav" style="<?= $headerStyle=='style2'?'color:black;':'color:white; filter:invert(1);' ?>">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="b2cNav">
            <!-- Nav Links Container (Alignment logic depending on style) -->
            <ul class="navbar-nav <?= ($headerStyle == 'style1') ? 'mx-auto' : 'me-auto ms-4' ?>">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>?page=b2c_home">Home</a></li>
                <?php foreach($menus as $menu): ?>
                    <?php
                        $m_id = $menu['id'];
                        $sub_stmt = $pdo->prepare("SELECT * FROM b2c_menus WHERE parent_id=? AND status='active' ORDER BY display_order ASC");
                        $sub_stmt->execute([$m_id]);
                        $submenus = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php if(count($submenus) > 0): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><?= htmlspecialchars($menu['title']) ?></a>
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
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>?page=buy_subscription">Subscriptions</a></li>
            </ul>
            
            <!-- Auth Buttons -->
            <ul class="navbar-nav mt-3 mt-lg-0">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link btn-signup text-white px-4" href="<?= BASE_URL ?>?page=dashboard" style="background: linear-gradient(to right, #10b981, #059669); border:none;">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link btn-login text-center" href="<?= BASE_URL ?>?page=b2c_login">User Login</a></li>
                    <li class="nav-item"><a class="nav-link btn-signup text-center" href="<?= BASE_URL ?>?page=b2c_register">Sign Up Free</a></li>
                    <li class="nav-item ms-lg-2"><a class="nav-link btn-login text-center border-0 bg-transparent text-secondary" style="font-size: 0.8rem; padding-left:0; padding-right:0;" href="<?= BASE_URL ?>?page=login"><i class="fas fa-user-tie me-1"></i> B2B Portal</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
/* Adjust digital tools workspace to fit perfectly under navbar */
.studio-wrapper { min-height: calc(100vh - 75px) !important; height: calc(100vh - 75px) !important; }
</style>
