<?php
/**
 * models/roles.php
 * This file contains functions for managing user roles and permissions.
 * FINAL & COMPLETE: All necessary permissions are included in the master list and syntax errors are corrected.
 * NEW: Added appointment and view post permissions.
 * UPDATE: Added hasPermission() and getUserPermissions() functions.
 */

if (!function_exists('connectDB')) {
    require_once __DIR__ . '/db.php';
}

/**
 * Returns a master list of all available permissions grouped by category.
 * This is the single source of truth for all permissions in the application.
 * @return array
 */
function getMasterPermissionsList() {
    return [
        'Admin Pages' => [
            'dashboard' => 'Admin Dashboard',
            'users' => 'Manage Users',
            'register' => 'Register New User',
            'edit_user' => 'Edit Any User',
            'manage_roles' => 'Manage Roles',
            'clients' => 'Manage Clients',
            'appointments' => 'Manage All Appointments',
            'categories' => 'Manage Categories',
            'assign_task' => 'Assign Task',
            'all_tasks' => 'View All Tasks',
            'edit_task' => 'Edit Any Task',
            'expenses' => 'Manage Expenses',
            'reports' => 'View Reports',
            'manage_recruitment_posts' => 'Manage Posts',
            'manage_withdrawals' => 'Manage Withdrawals',
            'hr_management' => 'HR Dashboard (Admin View)',
            'settings' => 'System Settings',
        ],
        'HR Pages' => [
            'hr_dashboard' => 'HR Dashboard',
            'manage_attendance' => 'Manage Attendance',
            'manage_salaries' => 'Manage Salaries',
            'hr_settings' => 'HR Settings',
        ],
        'Accountant Pages' => [
            'accountant_dashboard' => 'Accountant Dashboard',
            'manage_salaries' => 'Manage Salaries',
            'manage_withdrawals' => 'Manage Withdrawals',
            'clients' => 'Manage Clients',
            'customers' => 'Manage Customers',
        ],
        'Manager Pages' => [
            'clients' => 'Manage Clients',
            'customers' => 'Manage Customers',
        ],
        'General User Pages' => [
            'user_dashboard' => 'User Dashboard',
            'my_tasks' => 'My Tasks',
            'update_task' => 'Update My Task',
            'submit_work' => 'Submit Work',
            'my_appointments' => 'My Appointments',
            'customers' => 'Manage Customers',
        ],
        'Worker Pages (DEO & Freelancer)' => [
            'worker_dashboard' => 'Worker Dashboard',
            'add_recruitment_post' => 'Add Recruitment Post',
            'my_recruitment_posts' => 'My Recruitment Posts',
            'view_recruitment_post' => 'View Own Recruitment Post',
            'generate_poster' => 'Poster Generator',
            'my_freelancer_tasks' => 'My Freelancer Tasks',
            'update_freelancer_task' => 'Update Freelancer Task',
            'my_withdrawals' => 'My Withdrawals',
            'bank_details' => 'Bank Details',
        ],
        'Common Pages' => [
             'messages' => 'Messenger Access',
             'user_settings' => 'My Settings Access',
             'master_dashboard' => 'Master Dashboard Access'
        ]
    ];
}

/**
 * Returns a master list of all available dashboard permissions.
 * @return array
 */
function getDashboardPermissionsList() {
    return [
        'show_financial_summary' => 'Show Financial Summary Cards',
        'show_task_summary' => 'Show Task Summary Cards',
        'show_user_client_summary' => 'Show User & Client Summary Cards',
        'show_appointment_summary' => 'Show Appointment Summary Cards',
        'show_pending_actions' => 'Show Pending Actions',
        'show_recent_activity' => 'Show Recent Activity Feed',
        'show_notifications' => 'Show User Notifications',
    ];
}

function getDashboardPermissionsForRole($role_name) {
    $pdo = connectDB();
    $sql = "SELECT dashboard_permissions FROM roles WHERE role_name = ?";
    $role = fetchOne($pdo, $sql, [$role_name]);
    $defaultPermissions = array_fill_keys(array_keys(getDashboardPermissionsList()), false);
    if (strtolower($role_name) === 'admin') {
        return array_fill_keys(array_keys($defaultPermissions), true);
    }
    if ($role && !empty($role['dashboard_permissions'])) {
        $dbPermissions = json_decode($role['dashboard_permissions'], true);
        if (is_array($dbPermissions)) {
            foreach ($dbPermissions as $perm) {
                if (array_key_exists($perm, $defaultPermissions)) {
                    $defaultPermissions[$perm] = true;
                }
            }
        }
    }
    return $defaultPermissions;
}

function getAllRoles() {
    $pdo = connectDB();
    return fetchAll($pdo, "SELECT * FROM roles ORDER BY role_name ASC");
}

function createRole($role_name, $permissions, $dashboard_permissions) {
    if (empty($role_name)) return false;
    $pdo = connectDB();
    $permissions_json = json_encode(array_values($permissions));
    $dashboard_permissions_json = json_encode(array_values($dashboard_permissions));
    try {
        $stmt = $pdo->prepare("INSERT INTO roles (role_name, permissions, dashboard_permissions) VALUES (?, ?, ?)");
        return $stmt->execute([$role_name, $permissions_json, $dashboard_permissions_json]);
    } catch (PDOException $e) {
        error_log("Error creating role: " . $e->getMessage());
        return false;
    }
}

function updateRole($role_id, $role_name, $permissions, $dashboard_permissions) {
    if (empty($role_id) || empty($role_name)) return false;
    $pdo = connectDB();
    $permissions_json = json_encode(array_values($permissions));
    $dashboard_permissions_json = json_encode(array_values($dashboard_permissions));
    try {
        $stmt = $pdo->prepare("UPDATE roles SET role_name = ?, permissions = ?, dashboard_permissions = ? WHERE id = ?");
        return $stmt->execute([$role_name, $permissions_json, $dashboard_permissions_json, $role_id]);
    } catch (PDOException $e) {
        error_log("Error updating role: " . $e->getMessage());
        return false;
    }
}

function deleteRole($role_id) {
    if (empty($role_id) || $role_id == 1) return false;
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
        return $stmt->execute([$role_id]);
    } catch (PDOException $e) {
        error_log("Error deleting role: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a user has a specific permission. This is the single, authoritative function.
 * @param PDO $pdo The database connection object.
 * @param int $userId The ID of the user.
 * @param string $permission The name of the permission to check.
 * @return bool True if the user has the permission, false otherwise.
 */
function hasPermission($pdo, $userId, $permission) {
    if (!isset($_SESSION['user_role']) || !isset($_SESSION['user_permissions'])) {
        // Fetch from DB if not in session
        $stmt = $pdo->prepare("SELECT r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $stmt->execute([$userId]);
        $roleInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$roleInfo) return false;
        
        $_SESSION['user_role'] = $roleInfo['role_name'];
        $_SESSION['user_permissions'] = getUserPermissions($pdo, $userId);
    }
    
    // Admin always has permission
    if ($_SESSION['user_role'] === 'admin') {
        return true;
    }
    
    // Check from session permissions
    return in_array($permission, $_SESSION['user_permissions']);
}

function getRolePermissions($role_id) {
    $pdo = connectDB();
    $role = fetchOne($pdo, "SELECT role_name, permissions FROM roles WHERE id = ?", [$role_id]);
    if (!$role) return [];

    if (strtolower($role['role_name']) === 'admin') {
        $all_perms = [];
        foreach (getMasterPermissionsList() as $group) {
            $all_perms = array_merge($all_perms, array_keys($group));
        }
        return $all_perms;
    }
    
    $permissions = json_decode($role['permissions'], true) ?: [];
    if (in_array(strtolower($role['role_name']), ['deo', 'freelancer']) && !in_array('view_recruitment_post', $permissions)) {
        $permissions[] = 'view_recruitment_post';
    }
    return $permissions;
}

function getUserPermissions($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT role_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) return []; 
    return getRolePermissions($user['role_id']);
}
?>