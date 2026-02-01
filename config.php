<?php
/**
 * config.php
 * FINAL & COMPLETE: 
 * - Defines all essential constants.
 * - Implements a robust error logging system that captures all errors and exceptions into a log file.
 */

// ADDITION: Check if this file has already been loaded to prevent errors.
if (!defined('CONFIG_LOADED')) {
    define('CONFIG_LOADED', true);

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // --- [NEW & ROBUST] Error Logging Setup ---
    define('LOGS_PATH', __DIR__ . '/logs');
    if (!is_dir(LOGS_PATH)) {
        @mkdir(LOGS_PATH, 0777, true);
    }
    $log_file = LOGS_PATH . '/error.log';

    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);

    ini_set('log_errors', 1);
    ini_set('error_log', $log_file);

    set_error_handler(function($severity, $message, $file, $line) use ($log_file) {
        if (!(error_reporting() & $severity)) {
            return;
        }
        $log_message = "[" . date('Y-m-d H:i:s') . "] PHP Error: [$severity] $message in $file on line $line" . PHP_EOL;
        file_put_contents($log_file, $log_message, FILE_APPEND);
        return true;
    });

    set_exception_handler(function($exception) use ($log_file) {
        $log_message = "[" . date('Y-m-d H:i:s') . "] Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . PHP_EOL . "Stack trace: " . $exception->getTraceAsString() . PHP_EOL;
        file_put_contents($log_file, $log_message, FILE_APPEND);
    });

    // --- Database Connection Details ---
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'project_management_system');
    define('DB_USER', 'root');
    define('DB_PASS', '');

    // --- Site Details ---
    define('APP_NAME', 'Project Management System');
    define('BASE_URL', 'http://localhost/project_management_system/');
    define('ASSETS_URL', BASE_URL . 'assets/');

    // --- Composer Autoloader ---
    $autoloader = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoloader)) {
        require_once $autoloader;
    } else {
        $error_msg = "<h3>Fatal Error: Required libraries are missing.</h3><p>Please run '<code>composer install</code>' in your project's root directory.</p>";
        error_log($error_msg);
        die($error_msg);
    }

    // --- Path Constants ---
    define('ROOT_PATH', __DIR__ . '/');
    define('MODELS_PATH', ROOT_PATH . 'models/');
    define('VIEWS_PATH', ROOT_PATH . 'views/');
    define('INCLUDES_PATH', VIEWS_PATH . 'includes/');
    define('ADMIN_VIEWS_PATH', ROOT_PATH . 'admin/');
    define('USER_VIEWS_PATH', ROOT_PATH . 'user/');
    define('RECRUITMENT_MODELS_PATH', MODELS_PATH . 'recruitment/');
    define('USER_RECRUITMENT_PATH', USER_VIEWS_PATH . 'recruitment/');

    // --- Page Path Constants ---
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
    define('ADMIN_APPOINTMENTS_PAGE_PATH', ADMIN_VIEWS_PATH . 'appointments.php'); // New
    define('ADMIN_HR_MANAGEMENT_PATH', ADMIN_VIEWS_PATH . 'hr_management.php');
    define('HR_DASHBOARD_PATH', ADMIN_VIEWS_PATH . 'hr_dashboard.php');
    define('MANAGE_ATTENDANCE_PATH', ADMIN_VIEWS_PATH . 'manage_attendance.php');
    define('MANAGE_SALARIES_PATH', ADMIN_VIEWS_PATH . 'manage_salaries.php');
    define('HR_SETTINGS_PATH', ADMIN_VIEWS_PATH . 'hr_settings.php');
    define('USER_DASHBOARD_PATH', USER_VIEWS_PATH . 'dashboard.php');
    define('USER_MY_TASKS_PAGE_PATH', USER_VIEWS_PATH . 'my_tasks.php');
    define('USER_UPDATE_TASK_PAGE_PATH', USER_VIEWS_PATH . 'update_task.php');
    define('USER_SUBMIT_WORK_PAGE_PATH', USER_VIEWS_PATH . 'submit_work.php');
    define('USER_SETTINGS_PAGE_PATH', USER_VIEWS_PATH . 'settings.php');
    define('USER_MY_APPOINTMENTS_PAGE_PATH', USER_VIEWS_PATH . 'my_appointments.php'); // New
    define('WORKER_DASHBOARD_PATH', USER_VIEWS_PATH . 'worker_dashboard.php'); 
    define('DEO_ADD_RECRUITMENT_POST_PATH', USER_RECRUITMENT_PATH . 'add_recruitment_post.php');
    define('DEO_GENERATE_POSTER_PATH', USER_RECRUITMENT_PATH . 'generate_poster.php');
    define('FREELANCER_MY_TASKS_PAGE_PATH', USER_VIEWS_PATH . 'my_freelancer_tasks.php');
    define('FREELANCER_UPDATE_TASK_PAGE_PATH', USER_VIEWS_PATH . 'update_freelancer_task.php');
    define('USER_WITHDRAWAL_PAGE_PATH', USER_VIEWS_PATH . 'withdrawals.php');
    define('NOT_FOUND_PAGE_PATH', VIEWS_PATH . '404.php');
    define('USER_BANK_DETAILS_PATH', USER_VIEWS_PATH . 'bank_details.php');
    define('USER_MESSAGES_PATH', USER_VIEWS_PATH . 'messages.php');
    define('ADMIN_APPOINTMENTS_PATH', ADMIN_VIEWS_PATH . 'appointments.php');
    define('USER_MY_APPOINTMENTS_PATH', USER_VIEWS_PATH . 'my_appointments.php');
    define('USER_VIEW_RECRUITMENT_POST_PATH', USER_RECRUITMENT_PATH . 'view_recruitment_post.php');
    define('USER_ACCOUNTANT_DASHBOARD_PATH', USER_VIEWS_PATH . 'accountant_dashboard.php');
    define('USER_MASTER_DASHBOARD_PATH', USER_VIEWS_PATH . 'master_dashboard.php');
    define('USER_CREATE_TASK_FROM_APPOINTMENT_PATH', USER_VIEWS_PATH . 'create_task_from_appointment.php');
}
?>