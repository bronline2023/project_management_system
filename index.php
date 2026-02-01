<?php
/**
 * index.php
 * This is the main template and router for the entire application.
 * FINAL & COMPLETE VERSION:
 * - This file is now a clean router.
 * - All POST request logic is offloaded to the fully comprehensive `app/actions.php`.
 * - It correctly handles all page routing and authentication checks.
 * - It now also processes simple GET actions like delete and status toggles before any output.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- [ALL CORE LIBRARIES ARE LOADED HERE] ---
require_once 'config.php';
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';
require_once MODELS_PATH . 'roles.php';

// --- [ACTION HANDLING FOR POST] ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/app/actions.php';
    exit;
}

// --- [ROUTING LOGIC] ---
$is_logged_in = isLoggedIn(); // Correct function name
$action = $_GET['action'] ?? '';

// --- [HANDLE SIMPLE GET ACTIONS - e.g., Delete, Toggle Status] ---
if (!empty($action) && $is_logged_in) {
    $pdo = connectDB();
    $id = (int)($_GET['id'] ?? 0);
    $isAdmin = $_SESSION['user_role'] === 'admin';
    $redirectPage = $_GET['page'] ?? 'dashboard';

    if ($id > 0) {
        switch ($action) {
            case 'delete_user':
                if ($isAdmin && $id > 1 && $id != $_SESSION['user_id']) {
                    // Transaction logic moved to actions.php for POST request
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
                $pdo->prepare("DELETE FROM work_assignments WHERE id = ?")->execute([$id]);
                $_SESSION['status_message'] = '<div class="alert alert-success">Task deleted.</div>';
                break;
            case 'delete_expense':
                $pdo->prepare("DELETE FROM expenses WHERE id = ?")->execute([$id]);
                $_SESSION['status_message'] = '<div class="alert alert-success">Expense deleted.</div>';
                break;
            case 'delete_category':
                $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
                $_SESSION['status_message'] = '<div class="alert alert-success">Category deleted.</div>';
                break;
            case 'delete_subcategory':
                 $pdo->prepare("DELETE FROM subcategories WHERE id = ?")->execute([$id]);
                $_SESSION['status_message'] = '<div class="alert alert-success">Subcategory deleted.</div>';
                break;
             case 'delete_client':
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
                 break;
            case 'delete_customer':
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
                 break;
        }
    }
    // After processing action, redirect to the page it came from
    header('Location: ' . BASE_URL . '?page=' . $redirectPage);
    exit;
}


// --- [ROUTING LOGIC] ---
$requestedPage = $_GET['page'] ?? 'login';
$publicPages = ['login', 'register', 'public_appointment_form', 'logout']; // Add logout to public pages

// Handle logout explicitly before any other logic.
if ($requestedPage === 'logout') {
    logoutUser();
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

// Check if the requested page is a public page that doesn't require authentication.
if (in_array($requestedPage, $publicPages)) {
    if ($is_logged_in && $requestedPage === 'login') {
        // Redirect logged-in users from the login page to their dashboard.
        $role = $_SESSION['user_role'] ?? 'guest';
        $dashboard_page = 'user_dashboard';
        if ($role === 'admin') $dashboard_page = 'master_dashboard';
        elseif ($role === 'hr') $dashboard_page = 'hr_dashboard';
        elseif ($role === 'accountant') $dashboard_page = 'accountant_dashboard';
        elseif (in_array($role, ['deo', 'freelancer', 'data_entry_operator'])) $dashboard_page = 'worker_dashboard';
        else $dashboard_page = 'user_dashboard';
        header('Location: ' . BASE_URL . '?page=' . $dashboard_page);
        exit;
    }

    $pageToLoad = VIEWS_PATH . $requestedPage . '.php';

} else {
    // For all other pages, authentication is required.
    if (!$is_logged_in) {
        header('Location: ' . BASE_URL . '?page=login');
        exit;
    }
    
    $pageToFileMap = [
        'dashboard' => ADMIN_DASHBOARD_PATH, 'users' => ADMIN_USERS_PAGE_PATH,
        'register' => VIEWS_PATH . 'register.php', 'edit_user' => ADMIN_EDIT_USER_PAGE_PATH,
        'manage_roles' => ADMIN_MANAGE_ROLES_PAGE_PATH, 'clients' => VIEWS_PATH . 'clients.php',
        'customers' => VIEWS_PATH . 'customers.php', // NEW
        'categories' => ADMIN_CATEGORIES_PAGE_PATH, 'assign_task' => ADMIN_ASSIGN_TASK_PAGE_PATH,
        'all_tasks' => ADMIN_ALL_TASKS_PAGE_PATH, 'edit_task' => ADMIN_EDIT_TASK_PAGE_PATH,
        'expenses' => ADMIN_EXPENSES_PAGE_PATH, 'reports' => ADMIN_REPORTS_PAGE_PATH,
        'settings' => ADMIN_SETTINGS_PAGE_PATH, 'manage_recruitment_posts' => ADMIN_MANAGE_RECRUITMENT_POSTS_PATH,
        'manage_withdrawals' => ADMIN_WITHDRAWAL_PAGE_PATH, 'hr_management' => ADMIN_HR_MANAGEMENT_PATH,
        'hr_dashboard' => HR_DASHBOARD_PATH, 
        'manage_attendance' => MANAGE_ATTENDANCE_PATH, // Typo fixed here
        'manage_salaries' => MANAGE_SALARIES_PATH, 'hr_settings' => HR_SETTINGS_PATH,
        'user_dashboard' => USER_DASHBOARD_PATH, 'my_tasks' => USER_MY_TASKS_PAGE_PATH,
        'update_task' => USER_UPDATE_TASK_PAGE_PATH, 'submit_work' => USER_SUBMIT_WORK_PAGE_PATH,
        'user_settings' => USER_SETTINGS_PAGE_PATH, 'worker_dashboard' => WORKER_DASHBOARD_PATH,
        'add_recruitment_post' => DEO_ADD_RECRUITMENT_POST_PATH, 'my_recruitment_posts' => USER_RECRUITMENT_PATH . 'my_recruitment_posts.php',
        'generate_poster' => DEO_GENERATE_POSTER_PATH, 'my_freelancer_tasks' => FREELANCER_MY_TASKS_PAGE_PATH,
        'update_freelancer_task' => FREELANCER_UPDATE_TASK_PAGE_PATH, 'my_withdrawals' => USER_WITHDRAWAL_PAGE_PATH,
        'bank_details' => USER_VIEWS_PATH . 'bank_details.php', 'messages' => USER_VIEWS_PATH . 'messages.php',
        'appointments' => ADMIN_APPOINTMENTS_PAGE_PATH,
        'my_appointments' => USER_MY_APPOINTMENTS_PAGE_PATH,
        'login' => VIEWS_PATH . 'login.php',
        'view_recruitment_post' => USER_RECRUITMENT_PATH . 'view_recruitment_post.php',
        'accountant_dashboard' => USER_VIEWS_PATH . 'accountant_dashboard.php',
        'master_dashboard' => USER_VIEWS_PATH . 'master_dashboard.php',
        'create_task_from_appointment' => USER_VIEWS_PATH . 'create_task_from_appointment.php'
    ];
    
    $pageToLoad = NOT_FOUND_PAGE_PATH;
    $userRole = $_SESSION['user_role'] ?? 'guest';
    $userPermissions = $_SESSION['user_permissions'] ?? [];

    if (isset($pageToFileMap[$requestedPage])) {
        $canAccess = false;
        if ($userRole === 'admin' || in_array($requestedPage, $userPermissions) || $requestedPage === 'master_dashboard' || $requestedPage === 'create_task_from_appointment') {
            $canAccess = true;
        }

        if ($canAccess && file_exists($pageToFileMap[$requestedPage])) {
            $pageToLoad = $pageToFileMap[$requestedPage];
        } else {
            // User doesn't have permission, redirect to their default dashboard
            $defaultDashboard = 'user_dashboard';
            if ($userRole === 'admin') $defaultDashboard = 'master_dashboard';
            elseif ($userRole === 'hr') $defaultDashboard = 'hr_dashboard';
            elseif ($userRole === 'accountant') $defaultDashboard = 'accountant_dashboard';
            elseif (in_array($userRole, ['deo', 'freelancer', 'data_entry_operator'])) $defaultDashboard = 'worker_dashboard';
            else $defaultDashboard = 'user_dashboard';
            
            header('Location: ' . BASE_URL . '?page=' . $defaultDashboard);
            exit;
        }
    } else {
        // Requested page is not in the map, redirect to default dashboard
        $defaultDashboard = 'user_dashboard';
        if ($userRole === 'admin') $defaultDashboard = 'master_dashboard';
        elseif ($userRole === 'hr') $defaultDashboard = 'hr_dashboard';
        elseif ($userRole === 'accountant') $defaultDashboard = 'accountant_dashboard';
        elseif (in_array($userRole, ['deo', 'freelancer', 'data_entry_operator'])) $defaultDashboard = 'worker_dashboard';
        else $defaultDashboard = 'user_dashboard';
        
        header('Location: ' . BASE_URL . '?page=' . $defaultDashboard);
        exit;
    }
}


// --- [TEMPLATE RENDERING] ---
// The login/register pages do not have a sidebar or header/footer wrapper.
if (in_array($requestedPage, ['login', 'register', 'public_appointment_form', 'logout'])) {
    if (file_exists($pageToLoad)) {
        include $pageToLoad;
    } else {
        http_response_code(404);
        include VIEWS_PATH . '404.php';
    }
} else {
    // All other pages have the full wrapper template.
    include INCLUDES_PATH . 'header.php';
    echo '<div class="wrapper d-flex align-items-stretch">';
    include INCLUDES_PATH . 'sidebar.php';
    echo '<div id="content" class="p-4 p-md-5 pt-5 w-100">';
    if (file_exists($pageToLoad)) {
        include $pageToLoad;
    } else {
        http_response_code(404);
        include VIEWS_PATH . '404.php';
    }
    echo '</div></div>';
    include INCLUDES_PATH . 'footer.php';
}
?>