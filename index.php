<?php
/**
 * index.php
 *
 * This is the main entry point (front controller) for the Project Management System.
 * It handles routing based on the URL and includes the appropriate controller files.
 *
 * All requests are routed through this file thanks to the .htaccess configuration.
 */

// --- CRITICAL DEBUGGING LINES START ---
error_log("DEBUG: index.php - REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));
error_log("DEBUG: index.php - Raw \$_POST data received by index.php: " . print_r($_POST, true));
error_log("DEBUG: index.php - Raw \$_GET data received by index.php: " . print_r($_GET, true));
// --- CRITICAL DEBUGGING LINES END ---


// Start the session (must be the very first thing to do, before any output)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define ROOT_PATH consistently.
// This ensures ROOT_PATH is always consistently defined as the project's root directory.
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

// Include the main configuration file
// Use ROOT_PATH to ensure the path is always absolute and correct.
require_once ROOT_PATH . 'config.php';

// Include database and authentication models
// These paths use constants defined in config.php
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php'; // This file is expected to contain isLoggedIn()

// --- DEBUG: Session State at the very beginning of index.php ---
error_log("DEBUG: index.php - Session ID (start): " . session_id());
error_log("DEBUG: index.php - Session Data (start): " . print_r($_SESSION, true));
error_log("DEBUG: index.php - Is Logged In (start): " . (isLoggedIn() ? 'true' : 'false'));
// --- END DEBUG ---


// --- Routing Logic ---
// Prioritize 'page' from POST data, then from GET data, then default to 'login'.
$requestedPage = $_POST['page'] ?? $_GET['page'] ?? 'login';
error_log("DEBUG: index.php - Determined requestedPage: " . $requestedPage); // NEW DEBUG LOG

// --- Handle Logout, Login, Register, and 404 Separately and Early if not logged in ---
// These pages should not include header/sidebar if they need to perform redirects or session ops.
$noAuthPages = ['logout', 'login', 'register', '404'];

if (in_array($requestedPage, $noAuthPages)) {
    error_log("DEBUG: index.php - Handling non-authenticated page: " . $requestedPage . " early.");
    switch ($requestedPage) {
        case 'logout':
            require_once VIEWS_PATH . 'logout.php';
            break;
        case 'login':
            require_once VIEWS_PATH . 'login.php';
            break;
        case 'register':
            require_once VIEWS_PATH . 'register.php';
            break;
        case '404':
            http_response_code(404);
            require_once VIEWS_PATH . '404.php';
            break;
    }
    exit; // Terminate script execution after handling these pages
}


// Define allowed pages and their corresponding file paths
// This acts as a whitelist for security.
$allowedPages = [
    // 'login', 'register', 'logout', '404' are handled above
    'clients' => CLIENTS_MASTER_PAGE_PATH, // Unified clients page
];

// Add role-specific pages if user is logged in
// --- DEBUG: Session State before role-specific page assignment ---
error_log("DEBUG: index.php - Before role-specific page assignment. Is Logged In: " . (isLoggedIn() ? 'true' : 'false'));
// --- END DEBUG ---
if (isLoggedIn()) {
    $userRole = $_SESSION['user_role'] ?? 'guest';
    error_log("DEBUG: index.php - Logged in user role: " . $userRole); // NEW DEBUG LOG

    switch ($userRole) {
        case 'admin':
            $allowedPages['dashboard'] = ADMIN_DASHBOARD_PATH;
            $allowedPages['users'] = ADMIN_USERS_PAGE_PATH;
            $allowedPages['add_user'] = ADMIN_ADD_USER_PAGE_PATH;
            $allowedPages['edit_user'] = ADMIN_EDIT_USER_PAGE_PATH; // Specific edit user page
            $allowedPages['categories'] = ADMIN_CATEGORIES_PAGE_PATH;
            $allowedPages['subcategories'] = ADMIN_SUBCATEGORIES_PAGE_PATH;
            $allowedPages['assign_task'] = ADMIN_ASSIGN_TASK_PAGE_PATH;
            $allowedPages['reports'] = ADMIN_REPORTS_PAGE_PATH;
            // Expense pages now point to the correct file
            $allowedPages['expenses'] = ADMIN_EXPENSES_PAGE_PATH;
            $allowedPages['add_expense'] = ADMIN_EXPENSES_PAGE_PATH;
            $allowedPages['manage_expenses'] = ADMIN_EXPENSES_PAGE_PATH;
            $allowedPages['edit_expense'] = ADMIN_EXPENSES_PAGE_PATH;
            $allowedPages['settings'] = ADMIN_SETTINGS_PAGE_PATH;
            $allowedPages['messages'] = ADMIN_MESSAGES_PAGE_PATH; // Admin messages path
            $allowedPages['edit_task'] = ADMIN_EDIT_TASK_PAGE_PATH;
            $allowedPages['all_tasks'] = ADMIN_ALL_TASKS_PAGE_PATH;
            $allowedPages['manage_recruitment_posts'] = ADMIN_MANAGE_RECRUITMENT_POSTS_PATH;
            $allowedPages['manage_withdrawals'] = ADMIN_WITHDRAWAL_PAGE_PATH; // New: Admin withdrawal page
            error_log("DEBUG: index.php - Current user role: admin");
            break;
        case 'manager':
        case 'coordinator':
        case 'sales':
        case 'assistant':
        case 'accountant':
            $allowedPages['dashboard'] = USER_DASHBOARD_PATH;
            $allowedPages['my_tasks'] = USER_MY_TASKS_PAGE_PATH;
            $allowedPages['submit_work'] = USER_SUBMIT_WORK_PAGE_PATH;
            $allowedPages['update_task'] = USER_UPDATE_TASK_PAGE_PATH;
            $allowedPages['messages'] = USER_MESSAGES_PAGE_PATH; // General user messages path
            error_log("DEBUG: index.php - Current user role: " . $userRole);
            break;
        case 'data_entry_operator':
            // Explicitly add DEO specific pages
            $allowedPages['dashboard'] = DEO_DASHBOARD_PATH;
            $allowedPages['deo_dashboard'] = DEO_DASHBOARD_PATH; // Add this explicitly for direct access by page parameter
            $allowedPages['add_recruitment_post'] = DEO_ADD_RECRUITMENT_POST_PATH;
            $allowedPages['my_tasks'] = USER_MY_TASKS_PAGE_PATH; // Allow DEO to access My Tasks
            $allowedPages['messages'] = USER_MESSAGES_PAGE_PATH; // ALLOW DEO TO ACCESS MESSAGES
            $allowedPages['my_withdrawals'] = USER_WITHDRAWAL_PAGE_PATH; // New: DEO withdrawal page
            $allowedPages['bank_details'] = USER_VIEWS_PATH . 'bank_details.php'; // DEO bank details page
            $allowedPages['generate_poster'] = DEO_GENERATE_POSTER_PATH; // NEW: Poster Generator for DEO
            error_log("DEBUG: index.php - Current user role: data_entry_operator");
            error_log("DEBUG: index.php - DEO allowed pages: " . print_r($allowedPages, true)); // NEW DEBUG LOG
            break;
        default:
            // For any other logged-in role not explicitly handled, redirect to a generic dashboard or login
            error_log("DEBUG: index.php - Unknown user role: " . $userRole . ". Redirecting to login.");
            header('Location: ' . BASE_URL . '?page=login');
            exit;
    }
} else {
    // If not logged in and trying to access a page other than login/register/404, redirect to login
    if (!in_array($requestedPage, ['login', 'register', '404'])) {
        error_log("DEBUG: index.php - Not logged in. Redirecting to login page.");
        header('Location: ' . BASE_URL . '?page=login');
        exit;
    }
}


// Handle special case for 'home' page when logged in
if ($requestedPage === 'home' && isLoggedIn()) {
    $userRole = $_SESSION['user_role'] ?? 'guest';
    if ($userRole === 'admin') {
        error_log("DEBUG: index.php - Routing to admin dashboard.");
        require_once ADMIN_DASHBOARD_PATH;
    } elseif ($userRole === 'data_entry_operator') {
        error_log("DEBUG: index.php - Routing to DEO dashboard.");
        if (file_exists(DEO_DASHBOARD_PATH)) {
            require_once DEO_DASHBOARD_PATH;
        } else {
            error_log("ERROR: DEO Dashboard file not found at " . DEO_DASHBOARD_PATH);
            header('Location: ' . BASE_URL . '?page=404');
        }
    } else {
        error_log("DEBUG: index.php - Routing to general user dashboard.");
        require_once USER_DASHBOARD_PATH;
    }
    exit; // Exit after including the dashboard
}

// Special handling for the unified clients page
if ($requestedPage === 'clients' || $requestedPage === 'add_client' || (isset($_GET['action']) && ($_GET['action'] === 'edit_client' || $_GET['action'] === 'delete_client'))) {
    if (isLoggedIn()) {
        $pageToInclude = CLIENTS_MASTER_PAGE_PATH;
        error_log("DEBUG: index.php - Including unified clients file: " . $pageToInclude . " for role: " . $_SESSION['user_role']);
        if (!file_exists($pageToInclude)) {
            error_log("ERROR: index.php - Unified clients file not found: " . $pageToInclude);
            header('Location: ' . BASE_URL . 'index.php?page=404');
            exit;
        }
        require_once $pageToInclude;
        exit; // Exit after including the unified clients file
    } else {
        error_log("DEBUG: index.php - Clients page requested by unauthorized or non-logged-in user. Redirecting to login.");
        header('Location: ' . BASE_URL . '?page=login');
        exit;
    }
}

// --- DEBUG: Session State before final page inclusion check ---
error_log("DEBUG: index.php - Before final page inclusion check. Is Logged In: " . (isLoggedIn() ? 'true' : 'false'));
error_log("DEBUG: index.php - Session Data (before final include): " . print_r($_SESSION, true));
// --- END DEBUG ---

// Include the header (which includes Bootstrap CSS, Font Awesome, jQuery)
include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <div class="custom-menu">
        <button type="button" id="sidebarCollapse" class="btn btn-primary">
            <i class="fa fa-bars"></i>
            <span class="sr-only">Toggle Menu</span>
        </button>
    </div>

    <?php
    // Include the sidebar
    // Sidebar should only be included if the user is logged in
    if (isLoggedIn()) {
        include INCLUDES_PATH . 'sidebar.php';
    }
    ?>

    <div id="content" class="w-100">
        <?php
        // This is where the actual page file (e.g., dashboard.php, add_recruitment_post.php) is included
        if (isset($allowedPages[$requestedPage]) && file_exists($allowedPages[$requestedPage])) {
            require_once $allowedPages[$requestedPage];
        } else {
            // If the page is not found or not allowed, redirect to a generic 404 page.
            // This is a security measure to prevent direct access to non-whitelisted files.
            error_log("DEBUG: index.php - Page '" . $requestedPage . "' not found or not allowed. Redirecting to 404.");
            http_response_code(404); // Set 404 HTTP status code
            require_once NOT_FOUND_PAGE_PATH;
        }
        ?>
    </div>
</div>

<?php
// Include the footer (which includes Bootstrap JS bundle and custom main.js)
include INCLUDES_PATH . 'footer.php';
?>