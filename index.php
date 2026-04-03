<?php
/**
 * index.php
 * FULL MASTER ROUTER (280+ Lines): Preserves all old logic (Deletes, HR, Recruitment) + New B2B SaaS
 */

date_default_timezone_set('Asia/Kolkata');

// session start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load the core library and models
require_once 'config.php';
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';
require_once MODELS_PATH . 'roles.php';

// ==========================================
// [ POST Action Handler (Form Submits) ]
// ==========================================
// Allow certain views to handle their own POST requests
$bypassed_actions = ['unlock_digital_service', 'unlock_guest_digital_service'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || !in_array($_POST['action'], $bypassed_actions))) {
    require_once CORE_APP_PATH . 'actions.php';
    exit;
}

$is_logged_in = isLoggedIn();
$action = $_GET['action'] ?? '';

// ==========================================
// [ GET Action Handler (Delete / Status Toggle) ]
// ==========================================
if (!empty($action) && $is_logged_in) {
    $pdo = connectDB();
    $id = (int)($_GET['id'] ?? 0);
    $userRole = $_SESSION['user_role'] ?? 'guest';
    $isAdmin = in_array($userRole, ['master_admin', 'admin']);
    $redirectPage = $_GET['page'] ?? 'dashboard';

    if ($id > 0) {
        switch ($action) {
            case 'delete_user':
                if ($isAdmin && $id > 1 && $id != $_SESSION['user_id']) {
                    $_SESSION['status_message'] = '<div class="alert alert-info">Please use the delete button on the user list to delete a user.</div>';
                } else {
                    $_SESSION['status_message'] = '<div class="alert alert-danger">This user cannot be deleted.</div>';
                }
                break;
            case 'toggle_user_status':
                 if ($isAdmin && $id > 1 && $id != $_SESSION['user_id']) {
                    $user = fetchOne($pdo, "SELECT status FROM users WHERE id = ?", [$id]);
                    $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
                    $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
                    $_SESSION['status_message'] = '<div class="alert alert-success">User status updated.</div>';
                 } else {
                     $_SESSION['status_message'] = '<div class="alert alert-danger">This user\'s status cannot be changed.</div>';
                 }
                 break;
            case 'delete_task':
                if ($isAdmin) {
                    $pdo->prepare("DELETE FROM work_assignments WHERE id = ?")->execute([$id]);
                    $_SESSION['status_message'] = '<div class="alert alert-success">Task deleted.</div>';
                }
                break;
            case 'delete_expense':
                if ($isAdmin || in_array('expenses', $_SESSION['user_permissions'] ?? [])) {
                    $pdo->prepare("DELETE FROM expenses WHERE id = ?")->execute([$id]);
                    $_SESSION['status_message'] = '<div class="alert alert-success">Expense deleted.</div>';
                }
                break;
            case 'delete_category':
                if ($isAdmin) {
                    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
                    $_SESSION['status_message'] = '<div class="alert alert-success">Category deleted.</div>';
                }
                break;
             case 'delete_client':
                 if ($isAdmin) {
                     try {
                         $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
                         $stmt->execute([$id]);
                         $_SESSION['status_message'] = '<div class="alert alert-success">Client deleted successfully!</div>';
                     } catch (PDOException $e) {
                         if ($e->getCode() == 23000) {
                             $_SESSION['status_message'] = '<div class="alert alert-danger">Error: Client cannot be deleted because there are tasks associated with them.</div>';
                         } else {
                             $_SESSION['status_message'] = '<div class="alert alert-danger">Error deleting client. Please check server logs for details.</div>';
                         }
                     }
                 }
                 break;
            case 'delete_customer':
                 if ($isAdmin) {
                     try {
                         $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
                         $stmt->execute([$id]);
                         $_SESSION['status_message'] = '<div class="alert alert-success">Customer deleted successfully!</div>';
                     } catch (PDOException $e) {
                         if ($e->getCode() == 23000) {
                             $_SESSION['status_message'] = '<div class="alert alert-danger">Error: Customer cannot be deleted because there are tasks associated with them.</div>';
                         } else {
                             $_SESSION['status_message'] = '<div class="alert alert-danger">Error deleting customer. Please check server logs for details.</div>';
                         }
                     }
                 }
                 break;
        }
    }
    // Send back to the same page after the action is complete
    header('Location: ' . BASE_URL . '?page=' . $redirectPage);
    exit;
}

// ==========================================
// [ PUBLIC PAGES (pages viewable without login) ]
// ==========================================
$publicPages = [
    'login', 'register', 'b2c_register', 'b2c_home', 'b2c_login', 'b2c_page',
    'poster_studio', 'resume_builder', 'smart_card', 'passport_photo',
    'document_converter', 'size_converter', 'photo_studio',
    'public_appointment_form', 'logout', 'privacy_policy', 'terms_of_service', 'buy_subscription',
    'about_us', 'contact_us', 'services', 'print_bill', 'appointment'
];
$requestedPage = $_GET['page'] ?? 'b2c_home';

// ==========================================
// [ PAGE TO FILE MAPPING ]
// ==========================================
$pageToFileMap = [
    // --- 1. ADMIN & CORE PAGES ---
    'dashboard' => ADMIN_DASHBOARD_PATH ?? (ADMIN_VIEWS_PATH . 'dashboards/dashboard.php'), 
    'master_dashboard' => USER_MASTER_DASHBOARD_PATH ?? (USER_VIEWS_PATH . 'dashboards/master_dashboard.php'),
    'users' => ADMIN_USERS_PAGE_PATH ?? (ADMIN_VIEWS_PATH . 'users/users.php'),
    'edit_user' => ADMIN_EDIT_USER_PAGE_PATH ?? (ADMIN_VIEWS_PATH . 'users/edit_user.php'),
    'manage_roles' => ADMIN_MANAGE_ROLES_PAGE_PATH ?? (ADMIN_VIEWS_PATH . 'users/manage_roles.php'), 
    'manage_notices' => ADMIN_VIEWS_PATH . 'notices/manage_notices.php',
    'clients' => USER_VIEWS_PATH . 'clients/clients.php',
    'customers' => VIEWS_PATH . 'customers.php', 
    'categories' => ADMIN_CATEGORIES_PAGE_PATH ?? (ADMIN_VIEWS_PATH . 'categories/categories.php'),
    'assign_task' => ADMIN_ASSIGN_TASK_PAGE_PATH ?? (ADMIN_VIEWS_PATH . 'tasks/assign_task.php'), 
    'all_tasks' => ADMIN_ALL_TASKS_PAGE_PATH ?? (ADMIN_VIEWS_PATH . 'tasks/all_tasks.php'),
    'edit_task' => ADMIN_EDIT_TASK_PAGE_PATH ?? (ADMIN_VIEWS_PATH . 'tasks/edit_task.php'), 
    'daily_work_entry' => ADMIN_VIEWS_PATH . 'tasks/daily_work_entry.php',
    'my_daily_entries' => ADMIN_VIEWS_PATH . 'tasks/my_daily_entries.php',
    'expenses' => ADMIN_EXPENSES_PAGE_PATH ?? (ADMIN_VIEWS_PATH . 'finance/expenses.php'),
    'reports' => ADMIN_REPORTS_PAGE_PATH ?? (ADMIN_VIEWS_PATH . 'reports/reports.php'), 
    'settings' => ADMIN_SETTINGS_PAGE_PATH ?? (ADMIN_VIEWS_PATH . 'settings/settings.php'),
    'manage_withdrawals' => ADMIN_WITHDRAWAL_PAGE_PATH ?? (ADMIN_VIEWS_PATH . 'finance/withdrawals.php'), 
    'appointments' => ADMIN_APPOINTMENTS_PAGE_PATH ?? (ADMIN_VIEWS_PATH . 'appointments/appointments.php'),
    'manage_b2c_sliders' => VIEWS_PATH . 'admin/b2c_manage_sliders.php',
    'manage_b2c_menus' => VIEWS_PATH . 'admin/b2c_manage_menus.php',
    'manage_custom_pages' => VIEWS_PATH . 'admin/manage_custom_pages.php',
    'website_settings' => VIEWS_PATH . 'admin/website_settings.php',

    // --- 2. HR & ATTENDANCE ---
    'hr_management' => ADMIN_HR_MANAGEMENT_PATH ?? (ADMIN_VIEWS_PATH . 'hr/hr_management.php'),
    'hr_dashboard' => HR_DASHBOARD_PATH ?? (ADMIN_VIEWS_PATH . 'hr/hr_dashboard.php'), 
    'manage_attendance' => MANAGE_ATTENDANCE_PATH ?? (ADMIN_VIEWS_PATH . 'hr/manage_attendance.php'),
    'manage_salaries' => MANAGE_SALARIES_PATH ?? (ADMIN_VIEWS_PATH . 'hr/manage_salaries.php'), 
    'hr_settings' => HR_SETTINGS_PATH ?? (ADMIN_VIEWS_PATH . 'hr/hr_settings.php'),
    
    // --- 3. RECRUITMENT ---
    'manage_recruitment_posts' => ADMIN_MANAGE_RECRUITMENT_POSTS_PATH ?? (ADMIN_VIEWS_PATH . 'recruitment/manage_recruitment_posts.php'),
    'add_recruitment_post' => DEO_ADD_RECRUITMENT_POST_PATH ?? (USER_RECRUITMENT_PATH . 'add_recruitment_post.php'), 
    'my_recruitment_posts' => USER_RECRUITMENT_PATH . 'my_recruitment_posts.php',
    'generate_poster' => DEO_GENERATE_POSTER_PATH ?? (USER_RECRUITMENT_PATH . 'generate_poster.php'), 
    'view_recruitment_post' => USER_RECRUITMENT_PATH . 'view_recruitment_post.php',

    // --- 4. USER, SAAS & FREELANCER PAGES ---
    'user_dashboard' => USER_DASHBOARD_PATH ?? (USER_VIEWS_PATH . 'dashboards/dashboard.php'), 
    'worker_dashboard' => WORKER_DASHBOARD_PATH ?? (USER_VIEWS_PATH . 'dashboards/worker_dashboard.php'),
    'accountant_dashboard' => USER_ACCOUNTANT_DASHBOARD_PATH ?? (USER_VIEWS_PATH . 'dashboards/accountant_dashboard.php'),
    'super_admin_dashboard' => VIEWS_PATH . 'dashboards/super_admin_dashboard.php',
    'district_manager_dashboard' => VIEWS_PATH . 'dashboards/district_manager_dashboard.php',
    'retailer_dashboard' => VIEWS_PATH . 'dashboards/retailer_dashboard.php',
    'my_tasks' => USER_MY_TASKS_PAGE_PATH ?? (USER_VIEWS_PATH . 'tasks/my_tasks.php'),
    'update_task' => USER_UPDATE_TASK_PAGE_PATH ?? (USER_VIEWS_PATH . 'tasks/update_task.php'), 
    'submit_work' => USER_SUBMIT_WORK_PAGE_PATH ?? (USER_VIEWS_PATH . 'tasks/submit_work.php'),
    'user_settings' => USER_SETTINGS_PAGE_PATH ?? (USER_VIEWS_PATH . 'settings/settings.php'), 
    'my_freelancer_tasks' => FREELANCER_MY_TASKS_PAGE_PATH ?? (USER_VIEWS_PATH . 'tasks/my_freelancer_tasks.php'),
    'update_freelancer_task' => FREELANCER_UPDATE_TASK_PAGE_PATH ?? (USER_VIEWS_PATH . 'tasks/update_freelancer_task.php'), 
    'my_withdrawals' => USER_WITHDRAWAL_PAGE_PATH ?? (USER_VIEWS_PATH . 'finance/withdrawals.php'),
    'bank_details' => USER_BANK_DETAILS_PATH ?? (USER_VIEWS_PATH . 'finance/bank_details.php'), 
    'messages' => USER_MESSAGES_PATH ?? (USER_VIEWS_PATH . 'messages/messages.php'),
    'my_appointments' => USER_MY_APPOINTMENTS_PAGE_PATH ?? (USER_VIEWS_PATH . 'appointments/my_appointments.php'),
    'create_task_from_appointment' => USER_CREATE_TASK_FROM_APPOINTMENT_PATH ?? (USER_VIEWS_PATH . 'tasks/create_task_from_appointment.php'),
    
    // --- 5. 🚀 NEW B2B SAAS PAGES 🚀 ---
    'manage_b2b_users' => VIEWS_PATH . 'manage_b2b_users.php',
    'master_portals' => VIEWS_PATH . 'master_portals.php',
    'buy_subscription' => VIEWS_PATH . 'buy_subscription.php',
    'subscription_plans' => VIEWS_PATH . 'subscription_plans.php',
    'manage_b2c_subscriptions' => VIEWS_PATH . 'manage_b2c_subscriptions.php',
    'manage_service_rates' => VIEWS_PATH . 'manage_service_rates.php',
    'global_transactions' => VIEWS_PATH . 'global_transactions.php',
    'manage_retailers' => VIEWS_PATH . 'manage_retailers.php',
    'commission_report' => VIEWS_PATH . 'commission_report.php',
    'portal_settings' => VIEWS_PATH . 'portal_settings.php',
    'manage_managers' => VIEWS_PATH . 'manage_managers.php',
    
    // --- 6. 🚀 DIGITAL STUDIO & WALLET ROUTES 🚀 ---
    'poster_studio' => DIGITAL_SERVICES_PATH . 'poster_studio.php',
    'resume_builder' => DIGITAL_SERVICES_PATH . 'resume_builder.php',
    'smart_card' => DIGITAL_SERVICES_PATH . 'smart_card.php',
    'passport_photo' => DIGITAL_SERVICES_PATH . 'passport_photo.php',
    'document_converter' => DIGITAL_SERVICES_PATH . 'document_converter.php',
    'size_converter' => DIGITAL_SERVICES_PATH . 'size_converter.php',
    'photo_studio' => DIGITAL_SERVICES_PATH . 'photo_studio.php',
    'digital_service_history' => DIGITAL_SERVICES_PATH . 'digital_service_history.php',
    'digital_drafts' => DIGITAL_SERVICES_PATH . 'digital_drafts.php',
    'digital_services' => VIEWS_PATH . 'digital_services.php',
    
    // --- 7. PUBLIC LEGAL POLICIES & CMS ---
    'privacy_policy' => VIEWS_PATH . 'privacy_policy.php',
    'terms_of_service' => VIEWS_PATH . 'terms_of_service.php',
    'b2c_home' => VIEWS_PATH . 'b2c_home.php',
    'b2c_page' => VIEWS_PATH . 'b2c_page.php',
    'b2c_login' => VIEWS_PATH . 'b2c_login.php',
    'b2c_register' => VIEWS_PATH . 'b2c_register.php',
    'login' => VIEWS_PATH . 'login.php',
    'register' => VIEWS_PATH . 'register.php',
    'wallet_recharge' => VIEWS_PATH . 'wallet_recharge.php',
    'admin_wallet_requests' => VIEWS_PATH . 'admin_wallet_requests.php',
    'about_us' => VIEWS_PATH . 'about_us.php',
    'contact_us' => VIEWS_PATH . 'contact_us.php',
    'services' => VIEWS_PATH . 'services.php',
    'logout' => VIEWS_PATH . 'logout.php',
    'public_appointment_form' => VIEWS_PATH . 'public_appointment_form.php',
    'appointment' => VIEWS_PATH . 'appointment.php',
    'print_bill' => VIEWS_PATH . 'print_bill.php'
];

// Process logout first
if ($requestedPage === 'logout') {
    logoutUser();
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

// public page (which can be accessed without login)
if (in_array($requestedPage, $publicPages)) {
    if ($is_logged_in && $requestedPage === 'login') {
        $role = strtolower($_SESSION['user_role'] ?? 'guest');
        $dashboard_page = 'user_dashboard';
        
        if ($role === 'master_admin') $dashboard_page = 'master_dashboard';
        elseif ($role === 'admin') $dashboard_page = 'dashboard';
        elseif ($role === 'retailer') $dashboard_page = 'retailer_dashboard';
        elseif ($role === 'district_manager') $dashboard_page = 'district_manager_dashboard';
        elseif ($role === 'super_admin') $dashboard_page = 'super_admin_dashboard';
        elseif ($role === 'hr') $dashboard_page = 'hr_dashboard';
        elseif ($role === 'accountant') $dashboard_page = 'accountant_dashboard';
        elseif (in_array($role, ['deo', 'freelancer', 'data_entry_operator'])) $dashboard_page = 'worker_dashboard';
        
        header('Location: ' . BASE_URL . '?page=' . $dashboard_page);
        exit;
    }

    $pageToLoad = $pageToFileMap[$requestedPage] ?? (VIEWS_PATH . $requestedPage . '.php');

} else {
    // Send directly to login page if user is not logged in
    if (!$is_logged_in) {
        header('Location: ' . BASE_URL . '?page=login');
        exit;
    }

    // --- DYNAMIC DASHBOARD ROUTING FIX ---
    if ($requestedPage === 'dashboard') {
        $perms = $_SESSION['user_permissions'] ?? [];
        $role = strtolower($_SESSION['user_role'] ?? 'guest');

        if (in_array('master_dashboard', $perms) || in_array($role, ['admin', 'master_admin'])) {
            $requestedPage = 'dashboard';
        } elseif (in_array('super_admin_dashboard', $perms)) {
            $requestedPage = 'super_admin_dashboard';
        } elseif (in_array('district_manager_dashboard', $perms) || $role === 'district_manager') {
            $requestedPage = 'district_manager_dashboard';
        } elseif (in_array('retailer_dashboard', $perms) || $role === 'retailer') {
            $requestedPage = 'retailer_dashboard';
        } elseif (in_array('hr_dashboard', $perms) || $role === 'hr') {
            $requestedPage = 'hr_dashboard';
        } elseif (in_array('accountant_dashboard', $perms) || $role === 'accountant') {
            $requestedPage = 'accountant_dashboard';
        } elseif (in_array('worker_dashboard', $perms) || in_array($role, ['deo', 'freelancer', 'data_entry_operator'])) {
            $requestedPage = 'worker_dashboard';
        } else {
            $requestedPage = 'user_dashboard';
        }
    }

    $pageToLoad = NOT_FOUND_PAGE_PATH ?? (VIEWS_PATH . '404.php');
    $userRole = $_SESSION['user_role'] ?? 'guest';
    $userPermissions = $_SESSION['user_permissions'] ?? [];

    if (isset($pageToFileMap[$requestedPage])) {
        $canAccess = false;
        
        // 1. Safe Dashboards & Common Pages (No Infinite Loop)
        $safe_pages = ['dashboard', 'master_dashboard', 'user_dashboard', 'hr_dashboard', 'accountant_dashboard', 'worker_dashboard', 'user_settings', 'messages', 'buy_subscription', 'create_task_from_appointment', 'digital_service_history', 'digital_drafts', 'digital_services', 'daily_work_entry', 'my_daily_entries'];
        if (in_array($requestedPage, $safe_pages)) {
            $canAccess = true;
        }
        
        // 2. Master Admin & Admin Full Access
        if (!$canAccess) {
            if (in_array($userRole, ['master_admin', 'admin']) || in_array('*', $userPermissions) || in_array($requestedPage, $userPermissions)) {
                $canAccess = true;
            }
        }

        // 3. Digital Studio Access Permission (Granular check per service)
        $digital_studio_pages = ['poster_studio', 'resume_builder', 'smart_card', 'passport_photo', 'document_converter', 'size_converter', 'photo_studio'];
        if (in_array($requestedPage, $digital_studio_pages)) {
            if (in_array($userRole, ['master_admin', 'admin']) || in_array($requestedPage, $userPermissions)) {
                $canAccess = true;
            }
        }

        // 4. User Wallet Access
        if ($requestedPage === 'wallet_recharge') {
            $canAccess = true; // Everyone logged in can see wallet recharge
        }

        // 5. Admin Wallet Requests Access
        if ($requestedPage === 'admin_wallet_requests' && in_array($userRole, ['master_admin', 'admin'])) {
            $canAccess = true;
        }

        // Final Resolution
        if ($canAccess && file_exists($pageToFileMap[$requestedPage])) {
            $pageToLoad = $pageToFileMap[$requestedPage];
        } else {
            // Permission Denied, redirect to specific Dashboard
            $defaultDashboard = 'user_dashboard';
            if (in_array('master_dashboard', $userPermissions) || in_array($userRole, ['master_admin', 'admin'])) $defaultDashboard = 'master_dashboard';
            elseif (in_array('super_admin_dashboard', $userPermissions)) $defaultDashboard = 'super_admin_dashboard';
            elseif (in_array('district_manager_dashboard', $userPermissions)) $defaultDashboard = 'district_manager_dashboard';
            elseif (in_array('retailer_dashboard', $userPermissions)) $defaultDashboard = 'retailer_dashboard';
            elseif (in_array('hr_dashboard', $userPermissions)) $defaultDashboard = 'hr_dashboard';
            elseif (in_array('accountant_dashboard', $userPermissions)) $defaultDashboard = 'accountant_dashboard';
            elseif (in_array('worker_dashboard', $userPermissions)) $defaultDashboard = 'worker_dashboard';
            
            $_SESSION['status_message'] = '<div class="alert alert-danger text-center fw-bold">You are not allowed to view this page.</div>';
            header('Location: ' . BASE_URL . '?page=' . $defaultDashboard);
            exit;
        }
    } else {
        // Page Not Found in Map, redirect to default
        $defaultDashboard = 'user_dashboard';
        if ($is_logged_in) {
            if (in_array('master_dashboard', $userPermissions) || in_array($userRole, ['master_admin', 'admin'])) $defaultDashboard = 'master_dashboard';
            header('Location: ' . BASE_URL . '?page=' . $defaultDashboard);
            exit;
        }
        // If not logged in and not public, it would have been caught above.
    }
}

// ==========================================
// [ LOAD B2C OR DIGITAL SERVICE PAGES ]
// ==========================================
if (in_array($requestedPage, $publicPages)) {
    // Determine if it's a B2C Page or a Digital Service Page
    $isDigitalPage = in_array($requestedPage, ['poster_studio', 'resume_builder', 'smart_card', 'passport_photo', 'document_converter', 'size_converter', 'photo_studio']);
    // Pages like b2c_home, terms, login manually include their headers if needed. 
    // Digital tools usually stand alone or use internal headers.
    $isB2cLayout = false; // We set this to false because digital tools handle their own layouts.

    if ($isB2cLayout) {
        if (file_exists(INCLUDES_PATH . 'b2c_header.php')) {
            require_once INCLUDES_PATH . 'b2c_header.php';
        }
    }
    
    // Load the main view
    $fileToRequire = $pageToFileMap[$requestedPage] ?? $pageToLoad;
    if (file_exists($fileToRequire)) {
        require_once $fileToRequire;
    } else {
        echo "<div class='container mt-5 text-center'><h2>Page not found.</h2><p class='text-muted small'>Debug Path: " . htmlspecialchars($fileToRequire) . "</p></div>";
    }

    if ($isB2cLayout) {
        if (file_exists(INCLUDES_PATH . 'b2c_footer.php')) {
            require_once INCLUDES_PATH . 'b2c_footer.php';
        }
    }
    
    exit;
} else {
    // ==========================================
    // [ TEMPLATE RENDERING (with header, sidebar and footer) ]
    // ==========================================
    // Main dashboard structure
    if(file_exists(INCLUDES_PATH . 'header.php')) include INCLUDES_PATH . 'header.php';
    
    echo '<div class="wrapper d-flex align-items_stretch">';
    
    // The sidebar will load
    if(file_exists(INCLUDES_PATH . 'sidebar.php')) include INCLUDES_PATH . 'sidebar.php';
    
    echo '<div id="content" class="p-4 p-md-5 pt-5 w-100 bg-light">';
    
    // Print Status Messages (if any)
    if(isset($_SESSION['status_message'])) {
        echo $_SESSION['status_message'];
        unset($_SESSION['status_message']);
    }
    
    // load the main page contents
    if (file_exists($pageToLoad)) {
        include $pageToLoad;
    } else {
        http_response_code(404);
        if(file_exists(VIEWS_PATH . '404.php')) include VIEWS_PATH . '404.php';
        else echo "<h3>404 Error - Page File Not Found on Server</h3>";
    }
    
    echo '</div></div>';
    
    if(file_exists(INCLUDES_PATH . 'footer.php')) include INCLUDES_PATH . 'footer.php';
}
?>
?>