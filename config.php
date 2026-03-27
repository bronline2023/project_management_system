<?php
/**
 * config.php
 * FINAL FIXED VERSION
 * - Fixed: Added missing 'MANAGE_ATTENDANCE_PATH' and other HR paths.
 * - DB: Configured for XAMPP (Localhost).
 * - Error Logging: Enabled.
 */

if (!defined('CONFIG_LOADED')) {
    define('CONFIG_LOADED', true);

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // --- 1. ERROR LOGGING ---
    define('LOGS_PATH', __DIR__ . '/Reports/Logs');
    if (!is_dir(LOGS_PATH)) {
        @mkdir(LOGS_PATH, 0777, true);
    }
    $log_file = LOGS_PATH . '/error_log.txt';

    ini_set('display_errors', 1); // XAMPP માં એરર દેખાય તે સારું છે
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    ini_set('log_errors', 1);
    ini_set('error_log', $log_file);

    // --- 2. MULTI-TENANT DATABASE CONFIGURATION ---
    $master_host = 'localhost';
    $master_db   = 'project_management_system';
    $master_user = 'root';
    $master_pass = '';

    define('DB_HOST', $master_host);
    
    // Determine the current domain
    $current_domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $is_tenant = false;
    $tenant_db = '';
    $tenant_user = '';
    $tenant_pass = '';
    $tenant_folder = '';

    // Master domains where the central portal and B2C lives
    $master_domains = ['www.bronline.online', 'bronline.online', '127.0.0.1', 'localhost'];
    
    // Check if the domain belongs to a tenant
    if (!in_array($current_domain, $master_domains)) {
        try {
            $master_pdo = new PDO("mysql:host=$master_host;dbname=$master_db;charset=utf8mb4", $master_user, $master_pass);
            $stmt = $master_pdo->prepare("SELECT db_name, db_user, db_pass, folder_path FROM tenants WHERE domain_name = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$current_domain]);
            $tenantData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tenantData) {
                $is_tenant = true;
                $tenant_db = $tenantData['db_name'];
                $tenant_user = $tenantData['db_user'];
                $tenant_pass = $tenantData['db_pass'];
                $tenant_folder = $tenantData['folder_path'];
            }
        } catch (PDOException $e) {
            die("Critical Error: Unable to connect to master database for multi-tenant routing.");
        }
    }

    if ($is_tenant) {
        define('DB_NAME', $tenant_db);
        define('DB_USER', $tenant_user);
        define('DB_PASS', $tenant_pass);
        define('IS_TENANT', true);
        define('UPLOADS_DIR_RELATIVE', $tenant_folder . '/');
    } else {
        define('DB_NAME', $master_db);
        define('DB_USER', $master_user);
        define('DB_PASS', $master_pass);
        define('IS_TENANT', false);
        define('UPLOADS_DIR_RELATIVE', 'Resources/Uploads/');
    }

    // --- 3. SITE URL ---
    define('APP_NAME', 'B R Online Services - Portal');
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $protocol . $host . '/project_management_system/');
    define('ASSETS_URL', BASE_URL . 'Resources/Assets/');

    define('UPLOADS_PATH', __DIR__ . '/' . UPLOADS_DIR_RELATIVE);
    define('UPLOADS_URL', BASE_URL . UPLOADS_DIR_RELATIVE);

    // --- 4. TIMEZONE ---
    date_default_timezone_set('Asia/Kolkata');

    // --- 5. SYSTEM PATHS ---
    define('ROOT_PATH', __DIR__ . '/');
    define('MODELS_PATH', ROOT_PATH . 'Code/Core_Logic/Models/');
    define('VIEWS_PATH', ROOT_PATH . 'Code/Views/');
    define('INCLUDES_PATH', VIEWS_PATH . 'includes/');
    define('CORE_INCLUDES_PATH', ROOT_PATH . 'Code/Core_Logic/Includes/');
    
    define('ADMIN_VIEWS_PATH', ROOT_PATH . 'Code/Admin/');
    define('USER_VIEWS_PATH', ROOT_PATH . 'Code/User/');
    define('DIGITAL_SERVICES_PATH', ROOT_PATH . 'Code/Digital_Services/');
    define('CORE_APP_PATH', ROOT_PATH . 'Code/Core_Logic/App/');
    define('APP_URL', BASE_URL . 'Code/Core_Logic/App/');
    define('RECRUITMENT_MODELS_PATH', MODELS_PATH . 'recruitment/');
    define('USER_RECRUITMENT_PATH', USER_VIEWS_PATH . 'recruitment/');


    // --- 6. PAGE PATH CONSTANTS (FIXED MISSING ONES) ---

    // > ADMIN PAGES
    define('ADMIN_DASHBOARD_PATH', ADMIN_VIEWS_PATH . 'dashboards/dashboard.php');
    define('ADMIN_USERS_PAGE_PATH', ADMIN_VIEWS_PATH . 'users/users.php');
    define('ADMIN_EDIT_USER_PAGE_PATH', ADMIN_VIEWS_PATH . 'users/edit_user.php');
    define('ADMIN_MANAGE_ROLES_PAGE_PATH', ADMIN_VIEWS_PATH . 'users/manage_roles.php');
    define('ADMIN_CATEGORIES_PAGE_PATH', ADMIN_VIEWS_PATH . 'categories/categories.php');
    define('ADMIN_ASSIGN_TASK_PAGE_PATH', ADMIN_VIEWS_PATH . 'tasks/assign_task.php');
    define('ADMIN_ALL_TASKS_PAGE_PATH', ADMIN_VIEWS_PATH . 'tasks/all_tasks.php');
    define('ADMIN_EDIT_TASK_PAGE_PATH', ADMIN_VIEWS_PATH . 'tasks/edit_task.php');
    define('ADMIN_EXPENSES_PAGE_PATH', ADMIN_VIEWS_PATH . 'finance/expenses.php');
    define('ADMIN_REPORTS_PAGE_PATH', ADMIN_VIEWS_PATH . 'reports/reports.php');
    define('ADMIN_SETTINGS_PAGE_PATH', ADMIN_VIEWS_PATH . 'settings/settings.php');
    define('ADMIN_MANAGE_RECRUITMENT_POSTS_PATH', ADMIN_VIEWS_PATH . 'recruitment/manage_recruitment_posts.php');
    define('ADMIN_WITHDRAWAL_PAGE_PATH', ADMIN_VIEWS_PATH . 'finance/withdrawals.php');
    define('ADMIN_APPOINTMENTS_PAGE_PATH', ADMIN_VIEWS_PATH . 'appointments/appointments.php');
    define('ADMIN_APPOINTMENTS_PATH', ADMIN_VIEWS_PATH . 'appointments/appointments.php'); // Duplicate ref for safety

    // > HR & ATTENDANCE PAGES (THIS WAS MISSING)
    define('ADMIN_HR_MANAGEMENT_PATH', ADMIN_VIEWS_PATH . 'hr/hr_management.php');
    define('HR_DASHBOARD_PATH', ADMIN_VIEWS_PATH . 'hr/hr_dashboard.php');
    define('MANAGE_ATTENDANCE_PATH', ADMIN_VIEWS_PATH . 'hr/manage_attendance.php'); // Fixed Error
    define('MANAGE_SALARIES_PATH', ADMIN_VIEWS_PATH . 'hr/manage_salaries.php');     // Added for safety
    define('HR_SETTINGS_PATH', ADMIN_VIEWS_PATH . 'hr/hr_settings.php');

    // > USER PAGES
    define('USER_DASHBOARD_PATH', USER_VIEWS_PATH . 'dashboards/dashboard.php');
    define('USER_MY_TASKS_PAGE_PATH', USER_VIEWS_PATH . 'tasks/my_tasks.php');
    define('USER_UPDATE_TASK_PAGE_PATH', USER_VIEWS_PATH . 'tasks/update_task.php');
    define('USER_SUBMIT_WORK_PAGE_PATH', USER_VIEWS_PATH . 'tasks/submit_work.php');
    define('USER_SETTINGS_PAGE_PATH', USER_VIEWS_PATH . 'settings/settings.php');
    define('USER_MY_APPOINTMENTS_PAGE_PATH', USER_VIEWS_PATH . 'appointments/my_appointments.php');
    define('USER_MY_APPOINTMENTS_PATH', USER_VIEWS_PATH . 'appointments/my_appointments.php');
    define('WORKER_DASHBOARD_PATH', USER_VIEWS_PATH . 'dashboards/worker_dashboard.php'); 
    define('USER_WITHDRAWAL_PAGE_PATH', USER_VIEWS_PATH . 'finance/withdrawals.php');
    define('USER_BANK_DETAILS_PATH', USER_VIEWS_PATH . 'finance/bank_details.php');
    define('USER_MESSAGES_PATH', USER_VIEWS_PATH . 'messages/messages.php');
    define('USER_ACCOUNTANT_DASHBOARD_PATH', USER_VIEWS_PATH . 'dashboards/accountant_dashboard.php');
    define('USER_MASTER_DASHBOARD_PATH', USER_VIEWS_PATH . 'dashboards/master_dashboard.php');
    define('USER_CREATE_TASK_FROM_APPOINTMENT_PATH', USER_VIEWS_PATH . 'tasks/create_task_from_appointment.php');

    // > FREELANCER SPECIFIC
    define('FREELANCER_MY_TASKS_PAGE_PATH', USER_VIEWS_PATH . 'tasks/my_freelancer_tasks.php');
    define('FREELANCER_UPDATE_TASK_PAGE_PATH', USER_VIEWS_PATH . 'tasks/update_freelancer_task.php');

    // > RECRUITMENT
    define('DEO_ADD_RECRUITMENT_POST_PATH', USER_RECRUITMENT_PATH . 'add_recruitment_post.php');
    define('DEO_GENERATE_POSTER_PATH', USER_RECRUITMENT_PATH . 'generate_poster.php');
    define('USER_VIEW_RECRUITMENT_POST_PATH', USER_RECRUITMENT_PATH . 'view_recruitment_post.php');

    // > SYSTEM
    define('NOT_FOUND_PAGE_PATH', VIEWS_PATH . '404.php');
}
?>