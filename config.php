<?php
/**
 * config.php
 *
 * This file contains global configuration settings for the Project Management System.
 * It defines important constants such as base URL, database credentials,
 * and paths to various directories (models, views, admin, user, includes).
 * All other PHP files should include this file to ensure consistent settings.
 */

// Start the session (important to access session variables)
// This check prevents "headers already sent" errors if session_start() is called multiple times.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define ROOT_PATH consistently.
// If config.php is included directly (e.g., from a model), __DIR__ is its directory.
// If config.php is included from index.php, index.php defines ROOT_PATH.
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

// --- Error Reporting & Display ---
// Set to 'E_ALL' during development for full error reporting.
// Set to '0' or 'E_ERROR | E_WARNING | E_PARSE' for production to hide sensitive errors.
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 0 in production for security

// --- Define Base URL ---
// This attempts to dynamically determine the BASE_URL.
// It's robust for most local and server environments.
// If it doesn't work for your specific setup, uncomment the manual define below
// and set it explicitly (e.g., define('BASE_URL', 'http://localhost/project_management_system/');).
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $scriptDir = rtrim(dirname($scriptName), '/\\'); // Get directory, remove trailing slash/backslash

    // If the script is in the web root, $scriptDir will be empty or just '/'.
    // If it's in a subfolder, it will be /subfolder.
    // We need to ensure BASE_URL ends with a slash for consistent linking.
    $base_url_calculated = $protocol . "://" . $host;
    if (!empty($scriptDir) && $scriptDir !== '/') { // Avoid double slash if already root
        $base_url_calculated .= $scriptDir;
    }
    $base_url_calculated .= '/'; // Ensure trailing slash

    define('BASE_URL', $base_url_calculated);
}


// --- Database Configuration ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'project_management_system'); // તમારા ડેટાબેઝનું નામ
define('DB_USER', 'root'); // તમારા ડેટાબેઝનું યુઝરનેમ
define('DB_PASS', '');     // તમારા ડેટાબેઝનો પાસવર્ડ

// --- Path Definitions ---
// Define paths to various directories for easy inclusion
// Use ROOT_PATH for absolute paths
define('MODELS_PATH', ROOT_PATH . 'models' . DIRECTORY_SEPARATOR);
define('VIEWS_PATH', ROOT_PATH . 'views' . DIRECTORY_SEPARATOR);

// Admin and User views are directly under ROOT_PATH, not under VIEWS_PATH
define('ADMIN_VIEWS_PATH', ROOT_PATH . 'admin' . DIRECTORY_SEPARATOR);
define('USER_VIEWS_PATH', ROOT_PATH . 'user' . DIRECTORY_SEPARATOR);

define('INCLUDES_PATH', VIEWS_PATH . 'includes' . DIRECTORY_SEPARATOR);

// NEW: Define ASSETS_URL based on the confirmed structure: project_management_system/assets/
define('ASSETS_URL', BASE_URL . 'assets' . DIRECTORY_SEPARATOR);


// New Recruitment Paths
define('RECRUITMENT_MODELS_PATH', MODELS_PATH . 'recruitment' . DIRECTORY_SEPARATOR);
// Recruitment views are under their respective admin/user folders
define('RECRUITMENT_USER_VIEWS_PATH', USER_VIEWS_PATH . 'recruitment' . DIRECTORY_SEPARATOR);
define('RECRUITMENT_ADMIN_VIEWS_PATH', ADMIN_VIEWS_PATH . 'recruitment' . DIRECTORY_SEPARATOR);

// New Withdrawal Paths
define('WITHDRAWAL_MODELS_PATH', MODELS_PATH); // withdrawal.php is directly under models/
define('ADMIN_WITHDRAWAL_PAGE_PATH', ADMIN_VIEWS_PATH . 'withdrawals.php'); // New admin withdrawal page
define('USER_WITHDRAWAL_PAGE_PATH', USER_VIEWS_PATH . 'withdrawals.php'); // New user withdrawal page (for DEO)


// --- Application Settings ---
define('DEFAULT_CURRENCY_SYMBOL', '₹'); // Default currency symbol (e.g., $, €, £, ₹)
define('DEFAULT_APP_NAME', 'Project Management System'); // Default app name

// Add a constant for default timezone to avoid warnings
date_default_timezone_set('Asia/Kolkata'); // Set your appropriate timezone

// Include necessary helper functions or authentication logic here,
// or in a separate file and include it in index.php
require_once MODELS_PATH . 'db.php'; // Contains database interaction functions
require_once MODELS_PATH . 'auth.php'; // Contains authentication functions
require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php'; // Include the new recruitment model
require_once WITHDRAWAL_MODELS_PATH . 'withdrawal.php'; // Include the new withdrawal model (now correctly points to models/withdrawal.php)

// Define APP_NAME and CURRENCY_SYMBOL constants, fetched from settings if available
try {
    $pdo_config = connectDB(); // Use a separate PDO instance for config to avoid conflicts
    $stmt_config = $pdo_config->query("SELECT app_name, app_logo_url, currency_symbol, earning_per_approved_post, minimum_withdrawal_amount FROM settings LIMIT 1");
    $settings_config = $stmt_config->fetch(PDO::FETCH_ASSOC);

    // If no settings exist, insert a default row
    if (!$settings_config) {
        $pdo_config->exec("INSERT INTO settings (id, app_name, app_logo_url, currency_symbol, earning_per_approved_post, minimum_withdrawal_amount) VALUES (1, '" . DEFAULT_APP_NAME . "', '', '" . DEFAULT_CURRENCY_SYMBOL . "', 10.00, 500.00)");
        $stmt_config = $pdo_config->query("SELECT app_name, app_logo_url, currency_symbol, earning_per_approved_post, minimum_withdrawal_amount FROM settings LIMIT 1");
        $settings_config = $stmt_config->fetch(PDO::FETCH_ASSOC);
    }

    define('APP_NAME', htmlspecialchars($settings_config['app_name'] ?? DEFAULT_APP_NAME));
    define('CURRENCY_SYMBOL', htmlspecialchars($settings_config['currency_symbol'] ?? DEFAULT_CURRENCY_SYMBOL));
    // The earning_per_approved_post is retrieved by a function in recruitment_post.php, no need for a constant here.
    // Minimum withdrawal amount is now also a constant
    define('MINIMUM_WITHDRAWAL_AMOUNT', (float)($settings_config['minimum_withdrawal_amount'] ?? 500.00));

} catch (PDOException $e) {
    error_log("Error fetching app settings in config.php: " . $e->getMessage());
    // Fallback to default names on database error
    define('APP_NAME', DEFAULT_APP_NAME);
    define('CURRENCY_SYMBOL', DEFAULT_CURRENCY_SYMBOL);
    define('MINIMUM_WITHDRAWAL_AMOUNT', 500.00); // Fallback for minimum withdrawal
}

// Define common page paths
define('LOGIN_PAGE_PATH', VIEWS_PATH . 'login.php');
define('REGISTER_PAGE_PATH', VIEWS_PATH . 'register.php');
define('NOT_FOUND_PAGE_PATH', VIEWS_PATH . '404.php');

// Specific Admin Page Paths
define('ADMIN_DASHBOARD_PATH', ADMIN_VIEWS_PATH . 'dashboard.php');
define('ADMIN_USERS_PAGE_PATH', ADMIN_VIEWS_PATH . 'users.php');
define('ADMIN_ADD_USER_PAGE_PATH', ADMIN_VIEWS_PATH . 'add_user.php');
define('ADMIN_EDIT_USER_PAGE_PATH', ADMIN_VIEWS_PATH . 'edit_user.php'); // Specific edit user page
define('ADMIN_CATEGORIES_PAGE_PATH', ADMIN_VIEWS_PATH . 'categories.php');
define('ADMIN_SUBCATEGORIES_PAGE_PATH', ADMIN_VIEWS_PATH . 'subcategories.php');
define('ADMIN_ASSIGN_TASK_PAGE_PATH', ADMIN_VIEWS_PATH . 'assign_task.php');
define('ADMIN_REPORTS_PAGE_PATH', ADMIN_VIEWS_PATH . 'reports.php');
define('ADMIN_EXPENSES_PAGE_PATH', ADMIN_VIEWS_PATH . 'expenses.php');
define('ADMIN_SETTINGS_PAGE_PATH', ADMIN_VIEWS_PATH . 'settings.php');
define('ADMIN_MESSAGES_PAGE_PATH', ADMIN_VIEWS_PATH . 'messages.php');
define('ADMIN_EDIT_TASK_PAGE_PATH', ADMIN_VIEWS_PATH . 'edit_task.php');
define('ADMIN_ALL_TASKS_PAGE_PATH', ADMIN_VIEWS_PATH . 'all_tasks.php');
define('ADMIN_ADD_EXPENSE_PAGE_PATH', ADMIN_VIEWS_PATH . 'add_expense.php');
define('ADMIN_MANAGE_EXPENSES_PAGE_PATH', ADMIN_VIEWS_PATH . 'manage_expenses.php');
    define('ADMIN_EDIT_EXPENSE_PAGE_PATH', ADMIN_VIEWS_PATH . 'edit_expense.php');
// Admin Recruitment Post Management
define('ADMIN_MANAGE_RECRUITMENT_POSTS_PATH', RECRUITMENT_ADMIN_VIEWS_PATH . 'manage_recruitment_posts.php');


// Specific User Page Paths
define('USER_DASHBOARD_PATH', USER_VIEWS_PATH . 'dashboard.php');
define('USER_MY_TASKS_PAGE_PATH', USER_VIEWS_PATH . 'my_tasks.php');
define('USER_SUBMIT_WORK_PAGE_PATH', USER_VIEWS_PATH . 'submit_work.php');
define('USER_UPDATE_TASK_PAGE_PATH', USER_VIEWS_PATH . 'update_task.php');
define('USER_MESSAGES_PAGE_PATH', USER_VIEWS_PATH . 'messages.php');

// New DEO Dashboard Path
define('DEO_DASHBOARD_PATH', USER_VIEWS_PATH . 'deo_dashboard.php');
// DEO Recruitment Post Submission
define('DEO_ADD_RECRUITMENT_POST_PATH', RECRUITMENT_USER_VIEWS_PATH . 'add_recruitment_post.php');
// NEW: DEO Recruitment Poster Generator Path
define('DEO_GENERATE_POSTER_PATH', RECRUITMENT_USER_VIEWS_PATH . 'generate_poster.php');


// Unified Clients Page Path (accessible by multiple roles)
define('CLIENTS_MASTER_PAGE_PATH', VIEWS_PATH . 'clients.php');

?>
