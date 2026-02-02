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
    define('LOGS_PATH', __DIR__ . '/logs');
    if (!is_dir(LOGS_PATH)) {
        @mkdir(LOGS_PATH, 0777, true);
    }
    $log_file = LOGS_PATH . '/error_log.txt';

    ini_set('display_errors', 1); // XAMPP માં એરર દેખાય તે સારું છે
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    ini_set('log_errors', 1);
    ini_set('error_log', $log_file);

    // --- 2. DATABASE CONFIGURATION (XAMPP Default) ---
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'project_management_system'); // તમારું DB નામ અહીં ચેક કરજો
    define('DB_USER', 'root');
    define('DB_PASS', '');

    // --- 3. SITE URL ---
    define('APP_NAME', 'Freelancer Portal');
    define('BASE_URL', 'http://localhost/project_management_system/');
    define('ASSETS_URL', BASE_URL . 'assets/');

    // --- 4. TIMEZONE ---
    date_default_timezone_set('Asia/Kolkata');

    // --- 5. SYSTEM PATHS ---
    define('ROOT_PATH', __DIR__ . '/');
    define('MODELS_PATH', ROOT_PATH . 'models/');
    define('VIEWS_PATH', ROOT_PATH . 'views/');
    define('INCLUDES_PATH', VIEWS_PATH . 'includes/');
    
    define('ADMIN_VIEWS_PATH', ROOT_PATH . 'admin/');
    define('USER_VIEWS_PATH', ROOT_PATH . 'user/');
    define('RECRUITMENT_MODELS_PATH', MODELS_PATH . 'recruitment/');
    define('USER_RECRUITMENT_PATH', USER_VIEWS_PATH . 'recruitment/');

    // --- 6. PAGE PATH CONSTANTS (FIXED MISSING ONES) ---

    // > ADMIN PAGES
    define('ADMIN_DASHBOARD_PATH', ADMIN_VIEWS_PATH . 'dashboard.php');
    define('ADMIN_USERS_PAGE_PATH', ADMIN_VIEWS_PATH . 'users.php');
    define('ADMIN_EDIT_USER_PAGE_PATH', ADMIN_VIEWS_PATH . 'edit_user.php');
    define('ADMIN_MANAGE_ROLES_PAGE_PATH', ADMIN_VIEWS_PATH . 'manage_roles.php');
    define('ADMIN_CATEGORIES_PAGE_PATH', ADMIN_VIEWS_PATH . 'categories.php');
    define('ADMIN_ASSIGN_TASK_PAGE_PATH', ADMIN_VIEWS_PATH . 'assign_task.php');
    define('ADMIN_ALL_TASKS_PAGE_PATH', ADMIN_VIEWS_PATH . 'all_tasks.php');
    define('ADMIN_EDIT_TASK_PAGE_PATH', ADMIN_VIEWS_PATH . 'edit_task.php');
    define('ADMIN_EXPENSES_PAGE_PATH', ADMIN_VIEWS_PATH . 'expenses.php');
    define('ADMIN_REPORTS_PAGE_PATH', ADMIN_VIEWS_PATH . 'reports.php');
    define('ADMIN_SETTINGS_PAGE_PATH', ADMIN_VIEWS_PATH . 'settings.php');
    define('ADMIN_MANAGE_RECRUITMENT_POSTS_PATH', ADMIN_VIEWS_PATH . 'recruitment/manage_recruitment_posts.php');
    define('ADMIN_WITHDRAWAL_PAGE_PATH', ADMIN_VIEWS_PATH . 'withdrawals.php');
    define('ADMIN_APPOINTMENTS_PAGE_PATH', ADMIN_VIEWS_PATH . 'appointments.php');
    define('ADMIN_APPOINTMENTS_PATH', ADMIN_VIEWS_PATH . 'appointments.php'); // Duplicate ref for safety

    // > HR & ATTENDANCE PAGES (THIS WAS MISSING)
    define('ADMIN_HR_MANAGEMENT_PATH', ADMIN_VIEWS_PATH . 'hr_management.php');
    define('HR_DASHBOARD_PATH', ADMIN_VIEWS_PATH . 'hr_dashboard.php');
    define('MANAGE_ATTENDANCE_PATH', ADMIN_VIEWS_PATH . 'manage_attendance.php'); // Fixed Error
    define('MANAGE_SALARIES_PATH', ADMIN_VIEWS_PATH . 'manage_salaries.php');     // Added for safety
    define('HR_SETTINGS_PATH', ADMIN_VIEWS_PATH . 'hr_settings.php');

    // > USER PAGES
    define('USER_DASHBOARD_PATH', USER_VIEWS_PATH . 'dashboard.php');
    define('USER_MY_TASKS_PAGE_PATH', USER_VIEWS_PATH . 'my_tasks.php');
    define('USER_UPDATE_TASK_PAGE_PATH', USER_VIEWS_PATH . 'update_task.php');
    define('USER_SUBMIT_WORK_PAGE_PATH', USER_VIEWS_PATH . 'submit_work.php');
    define('USER_SETTINGS_PAGE_PATH', USER_VIEWS_PATH . 'settings.php');
    define('USER_MY_APPOINTMENTS_PAGE_PATH', USER_VIEWS_PATH . 'my_appointments.php');
    define('USER_MY_APPOINTMENTS_PATH', USER_VIEWS_PATH . 'my_appointments.php');
    define('WORKER_DASHBOARD_PATH', USER_VIEWS_PATH . 'worker_dashboard.php'); 
    define('USER_WITHDRAWAL_PAGE_PATH', USER_VIEWS_PATH . 'withdrawals.php');
    define('USER_BANK_DETAILS_PATH', USER_VIEWS_PATH . 'bank_details.php');
    define('USER_MESSAGES_PATH', USER_VIEWS_PATH . 'messages.php');
    define('USER_ACCOUNTANT_DASHBOARD_PATH', USER_VIEWS_PATH . 'accountant_dashboard.php');
    define('USER_MASTER_DASHBOARD_PATH', USER_VIEWS_PATH . 'master_dashboard.php');
    define('USER_CREATE_TASK_FROM_APPOINTMENT_PATH', USER_VIEWS_PATH . 'create_task_from_appointment.php');

    // > FREELANCER SPECIFIC
    define('FREELANCER_MY_TASKS_PAGE_PATH', USER_VIEWS_PATH . 'my_freelancer_tasks.php');
    define('FREELANCER_UPDATE_TASK_PAGE_PATH', USER_VIEWS_PATH . 'update_freelancer_task.php');

    // > RECRUITMENT
    define('DEO_ADD_RECRUITMENT_POST_PATH', USER_RECRUITMENT_PATH . 'add_recruitment_post.php');
    define('DEO_GENERATE_POSTER_PATH', USER_RECRUITMENT_PATH . 'generate_poster.php');
    define('USER_VIEW_RECRUITMENT_POST_PATH', USER_RECRUITMENT_PATH . 'view_recruitment_post.php');

    // > SYSTEM
    define('NOT_FOUND_PAGE_PATH', VIEWS_PATH . '404.php');
}
?>