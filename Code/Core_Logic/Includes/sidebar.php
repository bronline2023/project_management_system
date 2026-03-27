<?php
/**
 * includes/sidebar.php
 * 5-Level Role Based Access Control Sidebar
 */
$user_role = $_SESSION['user_role'] ?? 'guest';
$portal_id = $_SESSION['current_portal_id'] ?? null;
?>

<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="?page=dashboard">
        <div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-laugh-wink"></i></div>
        <div class="sidebar-brand-text mx-3">BR Online <sup>Pro</sup></div>
    </a>

    <hr class="sidebar-divider my-0">
    <li class="nav-item"><a class="nav-link" href="?page=dashboard"><i class="fas fa-fw fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
    <hr class="sidebar-divider">

    <?php if($user_role === 'master_admin'): ?>
        <div class="sidebar-heading">Master Controls</div>
        <li class="nav-item"><a class="nav-link" href="?page=master_portals"><i class="fas fa-fw fa-globe"></i> <span>Manage All Portals</span></a></li>
        <li class="nav-item"><a class="nav-link" href="?page=subscription_plans"><i class="fas fa-fw fa-tags"></i> <span>Subscription Plans</span></a></li>
        <li class="nav-item"><a class="nav-link" href="?page=global_transactions"><i class="fas fa-fw fa-rupee-sign"></i> <span>Global Revenue</span></a></li>
        <hr class="sidebar-divider">
    <?php endif; ?>

    <?php if(in_array($user_role, ['master_admin', 'admin'])): ?>
        <div class="sidebar-heading">Admin Settings</div>
        <li class="nav-item"><a class="nav-link" href="?page=settings"><i class="fas fa-fw fa-cogs"></i> <span>Global Settings</span></a></li>
        <li class="nav-item"><a class="nav-link" href="?page=portal_settings"><i class="fas fa-fw fa-paint-brush"></i> <span>Portal/App Theme</span></a></li>
        <li class="nav-item"><a class="nav-link" href="?page=manage_managers"><i class="fas fa-fw fa-user-tie"></i> <span>Manage Managers</span></a></li>
        <hr class="sidebar-divider">
        
        <div class="sidebar-heading">Website Panel</div>
        <li class="nav-item"><a class="nav-link" href="?page=website_settings"><i class="fas fa-fw fa-palette"></i> <span>Website Settings</span></a></li>
        <li class="nav-item"><a class="nav-link" href="?page=manage_b2c_sliders"><i class="fas fa-fw fa-images"></i> <span>Sliders & Videos</span></a></li>
        <li class="nav-item"><a class="nav-link" href="?page=manage_custom_pages"><i class="fas fa-fw fa-file-alt"></i> <span>Custom Pages</span></a></li>
        <li class="nav-item"><a class="nav-link" href="?page=manage_b2c_menus"><i class="fas fa-fw fa-bars"></i> <span>Navigation Menus</span></a></li>
        <hr class="sidebar-divider">
    <?php endif; ?>

    <?php if(in_array($user_role, ['master_admin', 'admin', 'manager', 'district_manager'])): ?>
        <div class="sidebar-heading">Network Management</div>
        <li class="nav-item"><a class="nav-link" href="?page=manage_retailers"><i class="fas fa-fw fa-store"></i> <span>Manage Retailers</span></a></li>
        <li class="nav-item"><a class="nav-link" href="?page=commission_report"><i class="fas fa-fw fa-chart-line"></i> <span>Commission Reports</span></a></li>
        <hr class="sidebar-divider">
    <?php endif; ?>

    <div class="sidebar-heading">Digital Services</div>
    <li class="nav-item"><a class="nav-link" href="?page=photo_studio"><i class="fas fa-fw fa-camera-retro"></i> <span>Pro Photo Studio</span></a></li>
    <li class="nav-item"><a class="nav-link" href="?page=document_converter"><i class="fas fa-fw fa-file-pdf"></i> <span>Document Converter</span></a></li>
    
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Wallet & Billing</div>
    <li class="nav-item"><a class="nav-link" href="?page=wallet_recharge"><i class="fas fa-fw fa-wallet"></i> <span>Wallet Recharge</span></a></li>
    
    <?php if($user_role === 'retailer' || $user_role === 'guest'): ?>
        <li class="nav-item">
            <a class="nav-link text-warning fw-bold" href="?page=buy_subscription">
                <i class="fas fa-fw fa-crown text-warning"></i> <span>Start Your Own Portal</span>
            </a>
        </li>
    <?php endif; ?>

</ul>