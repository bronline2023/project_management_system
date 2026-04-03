<?php
/**
 * views/includes/sidebar.php
 * FINAL B2B SAAS VERSION: Old 260+ Lines Code Intact + NEW Master Admin, District Manager, Retailer Roles
 */

// Ensure models/messages.php is loaded
require_once MODELS_PATH . 'messages.php';

$current_page = basename($_GET['page'] ?? 'dashboard');
$user_role = $_SESSION['user_role'] ?? 'guest';
$user_permissions = $_SESSION['user_permissions'] ?? [];

// Fetch Settings
$pdo = connectDB();
$settings = fetchOne($pdo, "SELECT app_name, app_logo_url FROM settings LIMIT 1");
$current_user_id = $_SESSION['user_id'] ?? 0;

$header_user = fetchOne($pdo, "SELECT balance, poster_points, role FROM users WHERE id = ?", [$current_user_id]);
$is_admin_check = in_array($header_user['role'] ?? $user_role, ['admin', 'master_admin']);

$header_balance_display = $is_admin_check ? "Unlimited" : "₹" . number_format((float)($header_user['balance'] ?? 0), 2);
$header_points_display = $is_admin_check ? "Unlimited" : number_format((int)($header_user['poster_points'] ?? 0)) . " Pts";

// Determine Panel Heading based on SaaS Roles
$panel_heading = 'Admin Panel';
switch ($user_role) {
    case 'master_admin': $panel_heading = 'Master Admin Panel'; break;
    case 'district_manager': $panel_heading = 'District Manager Panel'; break;
    case 'retailer': $panel_heading = 'Retailer Panel'; break;
    case 'admin': $panel_heading = 'Admin Panel'; break;
    case 'hr': $panel_heading = 'HR Panel'; break;
    case 'accountant': $panel_heading = 'Accountant Panel'; break;
    case 'deo': case 'freelancer': case 'data_entry_operator': case 'user': case 'coordinator': $panel_heading = 'My Work Panel'; break;
    case 'manager': $panel_heading = 'Manager Panel'; break;
    default: $panel_heading = 'User Panel'; break;
}

// Calculate Unread Messages safely
$unreadCount = 0;
if ($current_user_id > 0 && function_exists('getUnreadMessageCount')) {
    $unreadCount = getUnreadMessageCount($current_user_id);
}

// 🚀 Count pending recharge request for admin and master admin 🚀
$pendingRechargeCount = 0;
if (in_array($user_role, ['admin', 'master_admin'])) {
    try {
        $pendingRechargeCount = fetchColumn($pdo, "SELECT COUNT(*) FROM wallet_recharge_requests WHERE status = 'pending'") ?: 0;
    } catch (Exception $e) {
        $pendingRechargeCount = 0;
    }
}
?>

<nav id="sidebar">
    <div class="sidebar-header position-relative">
        <div id="dismiss" class="btn btn-outline-danger btn-sm position-absolute top-0 end-0 m-2 d-md-none" style="z-index: 1100; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; padding: 0;">
            <i class="fas fa-times"></i>
        </div>


        <?php if (!empty($settings['app_logo_url'])): ?>
            <img src="<?= htmlspecialchars($settings['app_logo_url']) ?>" alt="Logo" class="sidebar-logo">
        <?php endif; ?>
        <h3 class="app-name"><?= htmlspecialchars($settings['app_name'] ?? 'Portal') ?></h3>
    </div>

    <div class="profile-section text-center p-3">
        <div class="profile-pic mb-2">
            <?php if (!empty($_SESSION['user_profile_picture'])): ?>
                <img src="<?= UPLOADS_URL . htmlspecialchars($_SESSION['user_profile_picture']) ?>" alt="Profile" class="rounded-circle" style="width:60px; height:60px; object-fit:cover;">
            <?php else: ?>
                <img src="<?= ASSETS_URL . 'images/default_avatar.png' ?>" alt="Profile" class="rounded-circle" style="width:60px; height:60px; object-fit:cover;">
            <?php endif; ?>
        </div>
        <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Guest') ?></strong><br>
        <span class="text-warning small fw-bold text-uppercase"><?= htmlspecialchars(str_replace('_', ' ', $user_role)) ?></span>
    </div>

    <ul class="list-unstyled components">
        <p class="menu-heading px-3 text-uppercase small font-weight-bold text-muted">Main Menu</p>
        
        <?php if (in_array($user_role, ['admin', 'master_admin'])): ?>
            <li class="<?= $current_page == 'master_dashboard' ? 'active' : '' ?>">
                <a href="?page=master_dashboard" class="text-info fw-bold">
                    <i class="fas fa-chart-pie"></i> Master Dashboard
                </a>
            </li>
            <li class="<?= $current_page == 'dashboard' ? 'active' : '' ?>">
                <a href="?page=dashboard" class="fw-bold">
                    <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                </a>
            </li>
        <?php else: ?>
            <li class="<?= in_array($current_page, ['dashboard', 'user_dashboard', 'retailer_dashboard', 'worker_dashboard', 'district_manager_dashboard', 'super_admin_dashboard', 'hr_dashboard', 'accountant_dashboard', 'deo_dashboard', 'freelancer_dashboard']) ? 'active' : '' ?>">
                <a href="?page=dashboard"><i class="fas fa-home"></i> My Dashboard</a>
            </li>
        <?php endif; ?>

        <?php if ($current_user_id > 0 && (in_array('messages', $user_permissions) || in_array($user_role, ['admin', 'master_admin']))): ?>
            <li class="<?= $current_page == 'messages' ? 'active' : '' ?>">
                <a href="?page=messages" class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-comments"></i> Messages</span>
                    <?php if ($unreadCount > 0): ?>
                    <span id="sidebar-badge" class="badge bg-danger rounded-pill"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>

        <?php if(in_array($user_role, ['master_admin', 'admin', 'district_manager'])): ?>
            <p class="menu-heading px-3 mt-3 text-uppercase small font-weight-bold text-danger"><?= $panel_heading ?></p>

            <?php if($user_role === 'master_admin'): ?>
                <li class="<?= $current_page == 'manage_b2b_users' ? 'active' : '' ?>"><a href="?page=manage_b2b_users"><i class="fas fa-user-plus"></i> Users & Subscriptions</a></li>
                <li class="<?= $current_page == 'master_portals' ? 'active' : '' ?>"><a href="?page=master_portals"><i class="fas fa-globe"></i> Manage All Portals</a></li>
                <li class="<?= $current_page == 'subscription_plans' ? 'active' : '' ?>"><a href="?page=subscription_plans"><i class="fas fa-tags"></i> B2B Subscription Plans</a></li>
            <?php endif; ?>
            
            <?php if(in_array($user_role, ['master_admin', 'admin'])): ?>
                <p class="menu-heading px-3 mt-3 text-uppercase small font-weight-bold" style="color: #10b981;">Website Panel</p>
                <li class="<?= $current_page == 'website_settings' ? 'active' : '' ?>"><a href="?page=website_settings"><i class="fas fa-palette"></i> Website Settings</a></li>
                <li class="<?= $current_page == 'manage_b2c_sliders' ? 'active' : '' ?>"><a href="?page=manage_b2c_sliders"><i class="fas fa-images"></i> Sliders & Videos</a></li>
                <li class="<?= $current_page == 'manage_custom_pages' ? 'active' : '' ?>"><a href="?page=manage_custom_pages"><i class="fas fa-file-alt"></i> Custom Pages</a></li>
                <li class="<?= $current_page == 'manage_b2c_menus' ? 'active' : '' ?>"><a href="?page=manage_b2c_menus"><i class="fas fa-sitemap"></i> Navigation Menus</a></li>
                
                <p class="menu-heading px-3 mt-3 text-uppercase small font-weight-bold text-danger">Subscriptions & Rates</p>
                <li class="<?= $current_page == 'manage_b2c_subscriptions' ? 'active' : '' ?>"><a href="?page=manage_b2c_subscriptions"><i class="fas fa-box-open"></i> B2C Subscriptions</a></li>
                <li class="<?= $current_page == 'manage_service_rates' ? 'active' : '' ?>"><a href="?page=manage_service_rates"><i class="fas fa-rupee-sign"></i> Digital Service Rates</a></li>
            <?php endif; ?>

            <?php if($user_role === 'master_admin'): ?>
                <li class="<?= $current_page == 'global_transactions' ? 'active' : '' ?>"><a href="?page=global_transactions"><i class="fas fa-chart-line"></i> System Revenue</a></li>
            <?php endif; ?>

            <?php if($user_role === 'district_manager'): ?>
                <li class="<?= $current_page == 'manage_retailers' ? 'active' : '' ?>"><a href="?page=manage_retailers"><i class="fas fa-store"></i> My Retailers</a></li>
                <li class="<?= $current_page == 'commission_report' ? 'active' : '' ?>"><a href="?page=commission_report"><i class="fas fa-chart-line"></i> My Commission</a></li>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (count(array_intersect(['users', 'manage_roles', 'all_tasks', 'clients', 'expenses', 'appointments', 'categories', 'manage_recruitment_posts', 'reports', 'settings'], $user_permissions)) > 0 || in_array($user_role, ['admin', 'master_admin'])): ?>
        <p class="menu-heading px-3 mt-3 text-uppercase small font-weight-bold text-muted"><?= $user_role !== 'master_admin' ? $panel_heading : 'Office Panel' ?></p>
        <?php endif; ?>

        <?php if (in_array('users', $user_permissions) || in_array('manage_roles', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?>
        <li><a href="#userManagementSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-users-cog"></i> User Management</a>
            <ul class="collapse list-unstyled" id="userManagementSubmenu">
                <?php if (in_array('users', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=users">Manage Users</a></li><?php endif; ?>
                <?php if (in_array('manage_roles', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=manage_roles">Manage Roles</a></li><?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <?php if (in_array($user_role, ['admin', 'master_admin'])): ?>
            <li class="<?= $current_page == 'manage_notices' ? 'active' : '' ?>">
                <a href="?page=manage_notices" class="text-info">
                    <i class="fas fa-bullhorn"></i> Manage Notices
                </a>
            </li>
        <?php endif; ?>

        <?php if (in_array('daily_work_entry', $user_permissions) || in_array('view_daily_reports', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?>
        <li><a href="#dailyWorkSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle" style="color: #6366f1;"><i class="fas fa-calendar-check text-primary"></i> Official Work Tracker</a>
            <ul class="collapse list-unstyled" id="dailyWorkSubmenu">
                <?php if (in_array('daily_work_entry', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=daily_work_entry"><i class="fas fa-plus-circle"></i> Daily Work Entry</a></li><?php endif; ?>
                <?php if (in_array('view_daily_reports', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=my_daily_entries"><i class="fas fa-list-alt"></i> Daily Work Report</a></li><?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <?php if (in_array('clients', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=clients"><i class="fas fa-user-tie"></i> Client Management</a></li><?php endif; ?>
        <?php if (in_array('customers', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=customers"><i class="fas fa-user-friends"></i> Customer Management</a></li><?php endif; ?>
        <?php if (in_array($user_role, ['admin', 'master_admin']) || in_array('appointments', $user_permissions)): ?><li><a href="?page=appointments"><i class="fas fa-calendar-check"></i> Appointments</a></li><?php endif; ?>
        <?php if (in_array('categories', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=categories"><i class="fas fa-folder-tree"></i> Categories</a></li><?php endif; ?>

        <?php if (in_array('expenses', $user_permissions) || in_array('manage_withdrawals', $user_permissions) || in_array('manage_salaries', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?>
        <li><a href="#financialSubmenu" data-bs-toggle="collapse" aria-expanded="<?= ($current_page == 'admin_wallet_requests') ? 'true' : 'false' ?>" class="dropdown-toggle"><i class="fas fa-wallet"></i> Financial</a>
            <ul class="collapse list-unstyled <?= ($current_page == 'admin_wallet_requests') ? 'show' : '' ?>" id="financialSubmenu">
                <?php if (in_array('expenses', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=expenses">Office Expenses</a></li><?php endif; ?>
                <?php if (in_array('manage_withdrawals', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=manage_withdrawals">Withdrawals</a></li><?php endif; ?>
                <?php if (in_array('manage_salaries', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=manage_salaries">Salaries</a></li><?php endif; ?>
                
                <?php if (in_array($user_role, ['admin', 'master_admin'])): ?>
                    <li class="<?= ($current_page == 'admin_wallet_requests') ? 'active' : '' ?>">
                        <a href="?page=admin_wallet_requests" class="text-warning fw-bold d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-money-check-alt"></i> Wallet Requests</span>
                            <?php if ($pendingRechargeCount > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?= $pendingRechargeCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <?php if (in_array('hr_management', $user_permissions) || in_array('manage_attendance', $user_permissions) || in_array('hr_settings', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?>
        <li><a href="#hrSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-briefcase"></i> HR Management</a>
            <ul class="collapse list-unstyled" id="hrSubmenu">
                <?php if (in_array('hr_management', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=hr_management">HR Management</a></li><?php endif; ?>
                <?php if (in_array('manage_attendance', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=manage_attendance">Manage Attendance</a></li><?php endif; ?>
                <?php if (in_array('hr_settings', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=hr_settings">HR Settings</a></li><?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <?php if (in_array('manage_recruitment_posts', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=manage_recruitment_posts"><i class="fas fa-bullhorn"></i> Recruitment</a></li><?php endif; ?>
        <?php if (in_array('reports', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=reports"><i class="fas fa-chart-line"></i> Reports</a></li><?php endif; ?>
        <?php if (in_array('settings', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=settings"><i class="fas fa-cogs"></i> System Settings</a></li><?php endif; ?>
        
        <?php if (!in_array($user_role, ['admin', 'master_admin']) && (in_array('my_tasks', $user_permissions) || in_array('my_freelancer_tasks', $user_permissions) || in_array('my_appointments', $user_permissions) || in_array('add_recruitment_post', $user_permissions) || in_array('my_withdrawals', $user_permissions) || in_array('bank_details', $user_permissions))): ?>
        <p class="menu-heading px-3 mt-3 text-uppercase small font-weight-bold text-muted">My Work</p>
        <?php endif; ?>
        
        <?php if ($user_role === 'freelancer' || $user_role === 'data_entry_operator'): ?>
            <?php if (in_array('my_freelancer_tasks', $user_permissions)): ?><li><a href="?page=my_freelancer_tasks"><i class="fas fa-clipboard-list"></i> My Assigned Tasks</a></li><?php endif; ?>
        <?php else: ?>
            <?php if (in_array('my_tasks', $user_permissions)): ?><li><a href="?page=my_tasks"><i class="fas fa-clipboard-list"></i> My Assigned Tasks</a></li><?php endif; ?>
        <?php endif; ?>

        <?php if (in_array('my_appointments', $user_permissions) && !in_array($user_role, ['admin', 'master_admin'])): ?><li><a href="?page=my_appointments"><i class="fas fa-calendar-alt"></i> My Appointments</a></li><?php endif; ?>
        
        <?php if (!in_array($user_role, ['admin', 'master_admin']) && (in_array('add_recruitment_post', $user_permissions) || in_array('my_recruitment_posts', $user_permissions) || in_array('generate_poster', $user_permissions))): ?>
        <li><a href="#myRecruitmentSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-bullhorn"></i> My Recruitment</a>
             <ul class="collapse list-unstyled" id="myRecruitmentSubmenu">
                <?php if (in_array('add_recruitment_post', $user_permissions)): ?><li><a href="?page=add_recruitment_post">Add New Post</a></li><?php endif; ?>
                <?php if (in_array('my_recruitment_posts', $user_permissions)): ?><li><a href="?page=my_recruitment_posts">My Posts</a></li><?php endif; ?>
                <?php if (in_array('generate_poster', $user_permissions)): ?><li><a href="?page=generate_poster">Generate Poster</a></li><?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>
        
        <?php 
        $isAdmin = in_array($user_role, ['master_admin', 'admin']);
        $digital_pages = ['poster_studio', 'resume_builder', 'smart_card', 'passport_photo', 'document_converter', 'size_converter', 'photo_studio'];
        $hasAnyDigital = count(array_intersect($digital_pages, $user_permissions)) > 0;
        
        if ($isAdmin || $hasAnyDigital): 
        ?>
        <li>
            <a href="?page=digital_services" class="<?= ($current_page == 'digital_services') ? 'active' : '' ?>" style="background:#0f172a; color:#38bdf8;">
                <i class="fas fa-laptop-code"></i> Digital Services
            </a>
        </li>
        <?php endif; ?>

        <?php if (!in_array($user_role, ['admin', 'master_admin']) && in_array('my_withdrawals', $user_permissions)): ?><li><a href="?page=my_withdrawals"><i class="fas fa-hand-holding-usd"></i> My Withdrawals</a></li><?php endif; ?>
        <?php if (!in_array($user_role, ['admin', 'master_admin']) && in_array('bank_details', $user_permissions)): ?><li><a href="?page=bank_details"><i class="fas fa-university"></i> Bank Details</a></li><?php endif; ?>
        
        <?php if (in_array($user_role, ['retailer', 'district_manager', 'freelancer', 'deo', 'data_entry_operator']) || in_array('digital_studio', $user_permissions)): ?>
            <li class="<?= ($current_page == 'wallet_recharge') ? 'active' : '' ?>">
                <a href="?page=wallet_recharge" class="text-success fw-bold"><i class="fas fa-wallet"></i> Wallet Recharge</a>
            </li>
        <?php endif; ?>

        <?php if (in_array($user_role, ['retailer', 'freelancer'])): ?>
            <li class="<?= ($current_page == 'digital_service_history') ? 'active' : '' ?>">
                <a href="?page=digital_service_history" class="text-primary fw-bold">
                    <i class="fas fa-file-invoice-dollar text-primary"></i> My Usage Reports
                </a>
            </li>
        <?php endif; ?>
        
        <?php if(in_array($user_role, ['retailer', 'district_manager', 'guest', 'user'])): ?>
            <li class="nav-item <?= ($current_page == 'buy_subscription') ? 'active' : '' ?> mt-2">
                <a class="nav-link text-warning fw-bold" href="?page=buy_subscription" style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b;">
                    <i class="fas fa-fw fa-crown text-warning"></i> <span>Start Your Own Portal</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (in_array('user_settings', $user_permissions) || in_array($user_role, ['admin', 'master_admin'])): ?>
            <p class="menu-heading px-3 mt-3 text-uppercase small font-weight-bold text-muted">Settings</p>
             <li><a href="?page=user_settings"><i class="fas fa-user-cog"></i> My Settings</a></li>
        <?php endif; ?>
    </ul>

    <ul class="list-unstyled CTAs">
        <li>
            <a href="?page=logout" class="logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </li>
    </ul>
</nav>
<div id="content" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm mb-4">
        <div class="container-fluid">
            <button type="button" id="sidebarCollapse" class="btn btn-sm btn-primary d-md-none me-2 shadow-sm">
                <i class="fas fa-bars"></i>
            </button>

            <div id="headerClock" class="digital-clock d-none d-sm-block">
                <i class="far fa-clock"></i> Loading...
            </div>

            <!-- WALLET & POINTS (DESKTOP) -->
            <div class="ms-auto d-none d-lg-flex align-items-center gap-2 me-3">
                <span class="badge bg-light text-success border border-success px-3 py-2 rounded-pill shadow-sm fw-bold">
                    <i class="fas fa-wallet me-1"></i> <?= $header_balance_display ?>
                </span>
                <span class="badge bg-light text-dark border border-warning px-3 py-2 rounded-pill shadow-sm fw-bold">
                    <i class="fas fa-star me-1 text-warning"></i> <?= $header_points_display ?>
                </span>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="nav navbar-nav ms-auto align-items-center">
                    <li class="nav-item d-block d-sm-none me-2">
                         <span id="mobileClockText" class="fw-bold text-primary small"></span>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                           <i class="fas fa-user-circle fa-lg"></i> <?= $_SESSION['user_name'] ?? 'User' ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="?page=user_settings">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="?page=logout">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container-fluid">