<?php
/**
 * views/includes/sidebar.php
 * FINAL VERSION: Mobile Friendly & Real-time Badge
 */

// મેસેજ મોડલ લોડ કરો (જો લોડ ન હોય તો)
require_once MODELS_PATH . 'messages.php';

$current_page = basename($_GET['page'] ?? 'dashboard');
$user_role = $_SESSION['user_role'] ?? 'guest';
$user_permissions = $_SESSION['user_permissions'] ?? [];

// સેટિંગ્સ લાવો
$settings = fetchOne(connectDB(), "SELECT app_name, app_logo_url FROM settings LIMIT 1");
$current_user_id = $_SESSION['user_id'] ?? 0;

// પેનલ હેડિંગ નક્કી કરો
$panel_heading = 'Admin Panel';
switch ($user_role) {
    case 'hr': $panel_heading = 'HR Panel'; break;
    case 'accountant': $panel_heading = 'Accountant Panel'; break;
    case 'deo': case 'freelancer': case 'data_entry_operator': case 'user': case 'coordinator': $panel_heading = 'My Work Panel'; break;
    case 'manager': $panel_heading = 'Manager Panel'; break;
    default: $panel_heading = 'Admin Panel'; break;
}
?>

<nav id="sidebar">
    <div class="sidebar-header position-relative">
        <div id="dismiss" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 d-md-none" style="z-index: 1050;">
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
                <img src="<?= BASE_URL . htmlspecialchars($_SESSION['user_profile_picture']) ?>" alt="Profile" class="rounded-circle" style="width:60px; height:60px; object-fit:cover;">
            <?php else: ?>
                <div class="default-profile-icon"><i class="fas fa-user fa-2x"></i></div>
            <?php endif; ?>
        </div>
        <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Guest') ?></strong><br>
        <span class="text-muted small"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $user_role))) ?></span>
    </div>

    <ul class="list-unstyled components">
        <p class="menu-heading px-3 text-uppercase small font-weight-bold text-muted">Main Menu</p>
        
        <?php if (in_array('master_dashboard', $user_permissions) || $user_role === 'admin'): ?>
            <li class="<?= $current_page == 'master_dashboard' ? 'active' : '' ?>"><a href="?page=master_dashboard"><i class="fas fa-tachometer-alt"></i> Master Dashboard</a></li>
        <?php endif; ?>
        <?php if (in_array('hr_dashboard', $user_permissions) && $user_role !== 'admin'): ?>
            <li class="<?= $current_page == 'hr_dashboard' ? 'active' : '' ?>"><a href="?page=hr_dashboard"><i class="fas fa-briefcase"></i> HR Dashboard</a></li>
        <?php endif; ?>
        <?php if (in_array('worker_dashboard', $user_permissions) && $user_role !== 'admin'): ?>
            <li class="<?= $current_page == 'worker_dashboard' ? 'active' : '' ?>"><a href="?page=worker_dashboard"><i class="fas fa-user-clock"></i> Worker Dashboard</a></li>
        <?php endif; ?>
        <?php if (in_array('user_dashboard', $user_permissions) && $user_role !== 'admin'): ?>
             <li class="<?= $current_page == 'user_dashboard' ? 'active' : '' ?>"><a href="?page=user_dashboard"><i class="fas fa-home"></i> My Dashboard</a></li>
        <?php endif; ?>

        <?php if ($current_user_id > 0 && (in_array('messages', $user_permissions) || $user_role === 'admin')): 
            $unreadCount = getUnreadMessageCount($current_user_id); ?>
            <li class="<?= $current_page == 'messages' ? 'active' : '' ?>">
                <a href="?page=messages" class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-comments"></i> Messages</span>
                    <span id="sidebar-badge" class="badge bg-danger rounded-pill" style="<?= $unreadCount > 0 ? '' : 'display:none;' ?>">
                        <?= $unreadCount ?>
                    </span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (count(array_intersect(['users', 'manage_roles', 'all_tasks', 'clients', 'expenses', 'appointments', 'categories', 'manage_recruitment_posts', 'reports', 'settings'], $user_permissions)) > 0 || $user_role === 'admin'): ?>
        <p class="menu-heading px-3 mt-3 text-uppercase small font-weight-bold text-muted"><?= $panel_heading ?></p>
        <?php endif; ?>

        <?php if (in_array('users', $user_permissions) || in_array('manage_roles', $user_permissions) || $user_role === 'admin'): ?>
        <li><a href="#userManagementSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-users-cog"></i> User Management</a>
            <ul class="collapse list-unstyled" id="userManagementSubmenu">
                <?php if (in_array('users', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=users">Manage Users</a></li><?php endif; ?>
                <?php if (in_array('manage_roles', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=manage_roles">Manage Roles</a></li><?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <?php if (in_array('all_tasks', $user_permissions) || in_array('assign_task', $user_permissions) || $user_role === 'admin'): ?>
        <li><a href="#taskManagementSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-tasks"></i> Task Management</a>
            <ul class="collapse list-unstyled" id="taskManagementSubmenu">
                <?php if (in_array('all_tasks', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=all_tasks">View All Tasks</a></li><?php endif; ?>
                <?php if (in_array('assign_task', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=assign_task">Assign New Task</a></li><?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <?php if (in_array('clients', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=clients"><i class="fas fa-user-tie"></i> Client Management</a></li><?php endif; ?>
        <?php if (in_array('customers', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=customers"><i class="fas fa-user-friends"></i> Customer Management</a></li><?php endif; ?>
        <?php if (in_array('appointments', $user_permissions) && $user_role == 'admin'): ?><li><a href="?page=appointments"><i class="fas fa-calendar-check"></i> Appointments</a></li><?php endif; ?>
        <?php if (in_array('categories', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=categories"><i class="fas fa-folder-tree"></i> Categories</a></li><?php endif; ?>

        <?php if (in_array('expenses', $user_permissions) || in_array('manage_withdrawals', $user_permissions) || in_array('manage_salaries', $user_permissions) || $user_role === 'admin'): ?>
        <li><a href="#financialSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-wallet"></i> Financial</a>
            <ul class="collapse list-unstyled" id="financialSubmenu">
                <?php if (in_array('expenses', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=expenses">Office Expenses</a></li><?php endif; ?>
                <?php if (in_array('manage_withdrawals', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=manage_withdrawals">Withdrawals</a></li><?php endif; ?>
                <?php if (in_array('manage_salaries', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=manage_salaries">Salaries</a></li><?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <?php if (in_array('hr_management', $user_permissions) || in_array('manage_attendance', $user_permissions) || in_array('hr_settings', $user_permissions) || $user_role === 'admin'): ?>
        <li><a href="#hrSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-briefcase"></i> HR Management</a>
            <ul class="collapse list-unstyled" id="hrSubmenu">
                <?php if (in_array('hr_management', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=hr_management">HR Management</a></li><?php endif; ?>
                <?php if (in_array('manage_attendance', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=manage_attendance">Manage Attendance</a></li><?php endif; ?>
                <?php if (in_array('hr_settings', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=hr_settings">HR Settings</a></li><?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <?php if (in_array('manage_recruitment_posts', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=manage_recruitment_posts"><i class="fas fa-bullhorn"></i> Recruitment</a></li><?php endif; ?>
        <?php if (in_array('reports', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=reports"><i class="fas fa-chart-line"></i> Reports</a></li><?php endif; ?>
        <?php if (in_array('settings', $user_permissions) || $user_role === 'admin'): ?><li><a href="?page=settings"><i class="fas fa-cogs"></i> System Settings</a></li><?php endif; ?>
        
        <?php if ($user_role !== 'admin' && (in_array('my_tasks', $user_permissions) || in_array('my_freelancer_tasks', $user_permissions) || in_array('my_appointments', $user_permissions) || in_array('add_recruitment_post', $user_permissions) || in_array('my_withdrawals', $user_permissions) || in_array('bank_details', $user_permissions))): ?>
        <p class="menu-heading px-3 mt-3 text-uppercase small font-weight-bold text-muted">My Work</p>
        <?php endif; ?>
        
        <?php if ($user_role === 'freelancer' || $user_role === 'data_entry_operator'): ?>
            <?php if (in_array('my_freelancer_tasks', $user_permissions)): ?><li><a href="?page=my_freelancer_tasks"><i class="fas fa-clipboard-list"></i> My Assigned Tasks</a></li><?php endif; ?>
        <?php else: ?>
            <?php if (in_array('my_tasks', $user_permissions)): ?><li><a href="?page=my_tasks"><i class="fas fa-clipboard-list"></i> My Assigned Tasks</a></li><?php endif; ?>
        <?php endif; ?>

        <?php if (in_array('my_appointments', $user_permissions) && $user_role != 'admin'): ?><li><a href="?page=my_appointments"><i class="fas fa-calendar-alt"></i> My Appointments</a></li><?php endif; ?>

        <?php if ($user_role !== 'admin' && (in_array('add_recruitment_post', $user_permissions) || in_array('my_recruitment_posts', $user_permissions) || in_array('generate_poster', $user_permissions))): ?>
        <li><a href="#myRecruitmentSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-bullhorn"></i> My Recruitment</a>
             <ul class="collapse list-unstyled" id="myRecruitmentSubmenu">
                <?php if (in_array('add_recruitment_post', $user_permissions)): ?><li><a href="?page=add_recruitment_post">Add New Post</a></li><?php endif; ?>
                <?php if (in_array('my_recruitment_posts', $user_permissions)): ?><li><a href="?page=my_recruitment_posts">My Posts</a></li><?php endif; ?>
                <?php if (in_array('generate_poster', $user_permissions)): ?><li><a href="?page=generate_poster">Generate Poster</a></li><?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>
        
        <?php if ($user_role !== 'admin' && in_array('my_withdrawals', $user_permissions)): ?><li><a href="?page=my_withdrawals"><i class="fas fa-hand-holding-usd"></i> My Withdrawals</a></li><?php endif; ?>
        <?php if ($user_role !== 'admin' && in_array('bank_details', $user_permissions)): ?><li><a href="?page=bank_details"><i class="fas fa-university"></i> Bank Details</a></li><?php endif; ?>
        
        <?php if (in_array('user_settings', $user_permissions) || $user_role === 'admin'): ?>
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

            <button type="button" id="sidebarCollapse" class="btn btn-primary d-md-none me-2">
                <i class="fas fa-bars"></i>
            </button>

            <div id="headerClock" class="digital-clock d-none d-sm-block">
                <i class="far fa-clock"></i> Loading...
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