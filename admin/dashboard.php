<?php
/**
 * admin/dashboard.php
 *
 * This file represents the main dashboard for the administrator.
 * It displays an overview of the system, including revenue reports,
 * user task reports, office expense summaries, task statuses,
 * and a section for managing all assigned tasks.
 *
 * It ensures that only authenticated admin users can access this page.
 */

// Include the configuration file for database connection and session management.
// Path to config.php: from admin/dashboard.php, it's ../config.php
require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';     // Database interaction functions
require_once MODELS_PATH . 'auth.php'; // Authentication functions

// Restrict access to admin users only.
// If the user is not logged in or not an admin, redirect them.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB(); // Establish database connection

// Initialize message variable for display
$message = '';

// --- Handle Task Deletion (for any task by admin) via POST request ---
if (isset($_POST['action']) && $_POST['action'] === 'delete_task' && isset($_POST['id'])) {
    // Log received POST data for debugging
    error_log("DEBUG: dashboard.php - POST request received for task deletion.");
    error_log("DEBUG: dashboard.php - Received action: " . $_POST['action'] . ", ID: " . $_POST['id']);
    error_log("DEBUG: dashboard.php - Full POST data for delete_task: " . print_r($_POST, true));


    // CSRF Protection: Add a check for CSRF token here if implemented
    // if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    //     $_SESSION['status_message'] = '<div class="alert alert-danger" role="alert">Security check failed. Please try again.</div>';
    //     header('Location: ' . BASE_URL . 'index.php?page=dashboard');
    //     exit;
    // }

    $taskIdToDelete = $_POST['id']; // Get ID from POST

    try {
        // Prepare the DELETE statement to allow admin to delete ANY task
        $stmt = $pdo->prepare("DELETE FROM work_assignments WHERE id = :task_id");
        $stmt->bindParam(':task_id', $taskIdToDelete, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $_SESSION['status_message'] = '<div class="alert alert-success" role="alert">Task deleted successfully!</div>';
            error_log("DEBUG: dashboard.php - Task ID: " . $taskIdToDelete . " deleted successfully.");
        } else {
            $_SESSION['status_message'] = '<div class="alert alert-danger" role="alert">Task not found or could not be deleted.</div>';
            error_log("DEBUG: dashboard.php - Task ID: " . $taskIdToDelete . " not found or no rows affected during deletion.");
        }
    } catch (PDOException $e) {
        // Log detailed PDO exception
        error_log("ERROR: dashboard.php - PDOException during task deletion (ID: " . $taskIdToDelete . "): " . $e->getMessage());
        // Check for specific error code for foreign key constraint violation
        if ($e->getCode() == 23000) { // SQLSTATE for integrity constraint violation
            $_SESSION['status_message'] = '<div class="alert alert-danger" role="alert">Cannot delete task. It might have related records (e.g., messages) or financial entries. Please reassign the task or delete related records first if necessary.</div>';
        } else {
            $_SESSION['status_message'] = '<div class="alert alert-danger" role="alert">Error deleting task: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
    // Redirect to the dashboard to prevent form re-submission and display message
    // Explicitly include index.php in the redirect URL to ensure it's handled correctly by the server.
    header('Location: ' . BASE_URL . 'index.php?page=dashboard');
    exit;
}

// Check for status message in session after redirect
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']); // Clear the message after displaying it once
}


// --- Fetch Dashboard Data ---

// 1. Total Revenue
$totalRevenue = 0;
try {
    // Summing both fee and maintenance_fee as they contribute to total revenue
    $stmt = $pdo->query("SELECT SUM(fee) AS total_fee, SUM(maintenance_fee) AS total_maintenance_fee FROM work_assignments WHERE status = 'completed' AND payment_status IN ('paid_full', 'paid_partial')");
    $result = $stmt->fetch();
    $totalRevenue = ($result['total_fee'] ?? 0) - ($result['total_maintenance_fee'] ?? 0);
} catch (PDOException $e) {
    error_log("Error fetching total revenue: " . $e->getMessage());
    // Continue with 0, or display an error message on the dashboard.
}

// Daily Revenue
$dailyRevenue = 0;
try {
    // Using completed_at for daily revenue
    $stmt = $pdo->prepare("SELECT SUM(fee) AS daily_fee, SUM(maintenance_fee) AS daily_maintenance_fee FROM work_assignments WHERE status = 'completed' AND payment_status IN ('paid_full', 'paid_partial') AND DATE(completed_at) = CURDATE()");
    $stmt->execute();
    $result = $stmt->fetch();
    $dailyRevenue = ($result['daily_fee'] ?? 0) - ($result['daily_maintenance_fee'] ?? 0);
} catch (PDOException $e) {
    error_log("Error fetching daily revenue: " . $e->getMessage());
}

// Weekly Revenue
$weeklyRevenue = 0;
try {
    // Using completed_at for weekly revenue
    $stmt = $pdo->prepare("SELECT SUM(fee) AS weekly_fee, SUM(maintenance_fee) AS weekly_maintenance_fee FROM work_assignments WHERE status = 'completed' AND payment_status IN ('paid_full', 'paid_partial') AND WEEK(completed_at) = WEEK(CURDATE()) AND YEAR(completed_at) = YEAR(CURDATE())");
    $stmt->execute();
    $result = $stmt->fetch();
    $weeklyRevenue = ($result['weekly_fee'] ?? 0) - ($result['weekly_maintenance_fee'] ?? 0);
} catch (PDOException $e) {
    error_log("Error fetching weekly revenue: " . $e->getMessage());
}

// Monthly Revenue
$monthlyRevenue = 0;
try {
    // Using completed_at for monthly revenue
    $stmt = $pdo->prepare("SELECT SUM(fee) AS monthly_fee, SUM(maintenance_fee) AS monthly_maintenance_fee FROM work_assignments WHERE status = 'completed' AND payment_status IN ('paid_full', 'paid_partial') AND MONTH(completed_at) = MONTH(CURDATE()) AND YEAR(completed_at) = YEAR(CURDATE())");
    $stmt->execute();
    $result = $stmt->fetch();
    $monthlyRevenue = ($result['monthly_fee'] ?? 0) - ($result['monthly_maintenance_fee'] ?? 0);
} catch (PDOException $e) {
    error_log("Error fetching monthly revenue: " . $e->getMessage());
}


// 2. Task Counts (Active, Pending, Completed)
$activeTasks = 0;
$pendingTasks = 0;
$inProcessTasks = 0; // Added for clarity, as active = pending + in_process
$completedTasks = 0;
$cancelledTasks = 0;

try {
    $stmt = $pdo->query("SELECT status, COUNT(*) AS count FROM work_assignments GROUP BY status");
    $taskCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Fetches status => count

    $pendingTasks = $taskCounts['pending'] ?? 0;
    $inProcessTasks = $taskCounts['in_process'] ?? 0;
    $completedTasks = $taskCounts['completed'] ?? 0;
    $cancelledTasks = $taskCounts['cancelled'] ?? 0;
    $activeTasks = $pendingTasks + $inProcessTasks; // Active tasks could be pending + in_process
} catch (PDOException $e) {
    error_log("Error fetching task counts: " . $e->getMessage());
    // Continue with 0 for counts
}

// 3. Office Expense Report
$monthlyExpenses = 0;
$yearlyExpenses = 0;

try {
    // Current month's expenses (MySQL compatible query)
    $stmt = $pdo->prepare("SELECT SUM(amount) AS total_monthly FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')");
    $stmt->execute();
    $monthlyExpenses = $stmt->fetchColumn() ?? 0;

    // Current year's expenses (MySQL compatible query)
    $stmt = $pdo->prepare("SELECT SUM(amount) AS total_yearly FROM expenses WHERE YEAR(expense_date) = YEAR(NOW())");
    $stmt->execute();
    $yearlyExpenses = $stmt->fetchColumn() ?? 0;

} catch (PDOException $e) {
    error_log("Error fetching expense data: " . $e->getMessage());
    // Continue with 0 for expenses or display an error message on the dashboard.
}

// Get currency symbol from settings
$currencySymbol = '$'; // Default
try {
    $stmt = $pdo->query("SELECT currency_symbol FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings && isset($settings['currency_symbol'])) {
        $currencySymbol = htmlspecialchars($settings['currency_symbol']);
    }
} catch (PDOException $e) {
    error_log("Error fetching currency symbol: " . $e->getMessage());
}

// Helper function for status badge color
function getStatusBadgeColor($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'in_process': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}


// --- Fetch ALL Work Assignments for Admin Dashboard Overview ---
$allAssignedTasks = [];
try {
    $stmt = $pdo->prepare("
        SELECT
            wa.id,
            cl.client_name AS client_name,
            us.name AS assigned_user_name,
            cat.name AS category_name,
            sub.name AS subcategory_name,
            wa.work_description,
            wa.deadline,
            wa.status
        FROM
            work_assignments wa
        JOIN
            clients cl ON wa.client_id = cl.id
        JOIN
            users us ON wa.assigned_to_user_id = us.id
        JOIN
            categories cat ON wa.category_id = cat.id
        JOIN
            subcategories sub ON wa.subcategory_id = sub.id
        ORDER BY
            wa.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $allAssignedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching all assigned tasks for dashboard: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading all assigned tasks.</div>';
}

// --- Fetch User Task Reports ---
$userTaskReports = [];
try {
    $stmt = $pdo->query("
        SELECT
            u.id AS user_id,
            u.name AS user_name,
            u.role AS user_role,
            COALESCE(SUM(CASE WHEN wa.status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_tasks,
            COALESCE(SUM(CASE WHEN wa.status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_tasks,
            COUNT(wa.id) AS total_assigned
        FROM
            users u
        LEFT JOIN
            work_assignments wa ON u.id = wa.assigned_to_user_id
        WHERE
            u.role != 'admin'
        GROUP BY
            u.id, u.name, u.role
        ORDER BY
            u.name ASC
    ");
    $userTaskReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching user task reports: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading user task reports.</div>';
}

// --- Fetch Latest Admin Activities ---
$latestActivities = [];
try {
    // Fetch recent work assignments (created or updated)
    $stmt_tasks = $pdo->query("
        SELECT
            wa.id,
            'task' AS activity_type,
            wa.status,
            wa.created_at AS activity_timestamp,
            wa.updated_at,
            cl.client_name AS client_name,
            us.name AS user_name,
            wa.work_description
        FROM
            work_assignments wa
        JOIN
            users us ON wa.assigned_to_user_id = us.id
        JOIN
            clients cl ON wa.client_id = cl.id
        ORDER BY wa.updated_at DESC, wa.created_at DESC
        LIMIT 10
    ");
    $recentTasksActivities = $stmt_tasks->fetchAll(PDO::FETCH_ASSOC);

    // Fetch recent expenses
    $stmt_expenses = $pdo->query("
        SELECT
            id,
            'expense' AS activity_type,
            expense_type,
            amount,
            created_at AS activity_timestamp,
            description
        FROM
            expenses
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $recentExpensesActivities = $stmt_expenses->fetchAll(PDO::FETCH_ASSOC);

    // Merge and sort all activities by their most recent timestamp
    $mergedActivities = array_merge($recentTasksActivities, $recentExpensesActivities);

    usort($mergedActivities, function($a, $b) {
        $timestampA = isset($a['updated_at']) ? strtotime($a['updated_at']) : strtotime($a['activity_timestamp']);
        $timestampB = isset($b['updated_at']) ? strtotime($b['updated_at']) : strtotime($b['activity_timestamp']);
        return $timestampB - $timestampA; // Sort descending
    });

    // Take the top 10 latest activities
    $latestActivities = array_slice($mergedActivities, 0, 10);

} catch (PDOException $e) {
    error_log("Error fetching latest activities: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading latest activities.</div>';
}


// Include the header (contains HTML <head> and initial Bootstrap/CSS)
include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; // Include the sidebar for navigation ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Admin Dashboard</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; ?>
            <script>
                // Auto-hide the message after 5 seconds using the global function
                // Assuming setupAutoHideAlerts() is defined in footer.php or a global JS file.
                if (typeof setupAutoHideAlerts === 'function') {
                    setupAutoHideAlerts();
                } else {
                    console.warn("setupAutoHideAlerts function not found. Auto-hide alerts may not work.");
                }
            </script>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Revenue Overview</h5>
                        <button class="btn btn-outline-light btn-sm" onclick="location.href='<?= BASE_URL ?>?page=reports'"><i class="fas fa-eye"></i> View All Reports</button>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-success-subtle rounded-3 shadow-sm">
                                    <h6 class="text-success mb-2">Total Revenue</h6>
                                    <h3 class="fw-bold text-success"><?= $currencySymbol ?><?= number_format($totalRevenue, 2) ?></h3>
                                    <small class="text-muted">From completed & paid tasks</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-info-subtle rounded-3 shadow-sm">
                                    <h6 class="text-info mb-2">Daily Revenue</h6>
                                    <h3 class="fw-bold text-info"><?= $currencySymbol ?><?= number_format($dailyRevenue, 2) ?></h3>
                                    <small class="text-muted">Today's earnings</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-warning-subtle rounded-3 shadow-sm">
                                    <h6 class="text-warning mb-2">Weekly Revenue</h6>
                                    <h3 class="fw-bold text-warning"><?= $currencySymbol ?><?= number_format($weeklyRevenue, 2) ?></h3>
                                    <small class="text-muted">Last 7 days</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-danger-subtle rounded-3 shadow-sm">
                                    <h6 class="text-danger mb-2">Monthly Revenue</h6>
                                    <h3 class="fw-bold text-danger"><?= $currencySymbol ?><?= number_format($monthlyRevenue, 2) ?></h3>
                                    <small class="text-muted">Current month</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Task Status Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-primary-subtle rounded-3 shadow-sm">
                                    <h6 class="text-primary mb-2">Total Active Tasks</h6>
                                    <h3 class="fw-bold text-primary"><?= $activeTasks ?></h3>
                                    <small class="text-muted">(Pending + In Process)</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-warning-subtle rounded-3 shadow-sm">
                                    <h6 class="text-warning mb-2">Pending Tasks</h6>
                                    <h3 class="fw-bold text-warning"><?= $pendingTasks ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-info-subtle rounded-3 shadow-sm">
                                    <h6 class="text-info mb-2">In Process Tasks</h6>
                                    <h3 class="fw-bold text-info"><?= $inProcessTasks ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-success-subtle rounded-3 shadow-sm">
                                    <h6 class="text-success mb-2">Completed Tasks</h6>
                                    <h3 class="fw-bold text-success"><?= $completedTasks ?></h3>
                                </div>
                            </div>
                             <div class="col-md-3 mb-3">
                                <div class="p-3 bg-danger-subtle rounded-3 shadow-sm">
                                    <h6 class="text-danger mb-2">Cancelled Tasks</h6>
                                    <h3 class="fw-bold text-danger"><?= $cancelledTasks ?></h3>
                                    <small class="text-muted">Tasks cancelled</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Office Expenses</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-6 mb-3">
                                <div class="p-3 bg-light rounded-3 shadow-sm">
                                    <h6 class="text-muted mb-2">Monthly Expenses</h6>
                                    <h3 class="fw-bold text-dark"><?= $currencySymbol ?><?= number_format($monthlyExpenses, 2) ?></h3>
                                    <small class="text-muted">Current month</small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="p-3 bg-light rounded-3 shadow-sm">
                                    <h6 class="text-muted mb-2">Yearly Expenses</h6>
                                    <h3 class="fw-bold text-dark"><?= $currencySymbol ?><?= number_format($yearlyExpenses, 2) ?></h3>
                                    <small class="text-muted">Current year</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>All Assigned Tasks Overview</h5>
                        <a href="<?= BASE_URL ?>?page=reports" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-eye me-2"></i>View All Tasks
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($allAssignedTasks)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Task ID</th>
                                            <th>Client</th>
                                            <th>Assigned To</th>
                                            <th>Category</th>
                                            <th>Description</th>
                                            <th>Deadline</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allAssignedTasks as $task): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($task['id']) ?></td>
                                                <td><?= htmlspecialchars($task['client_name']) ?></td>
                                                <td><?= htmlspecialchars($task['assigned_user_name']) ?></td>
                                                <td><?= htmlspecialchars($task['category_name']) ?> - <?= htmlspecialchars($task['subcategory_name']) ?></td>
                                                <td><?= htmlspecialchars(substr($task['work_description'], 0, 50)) ?><?= (strlen($task['work_description']) > 50 ? '...' : '') ?></td>
                                                <td><?= date('Y-m-d', strtotime($task['deadline'])) ?></td>
                                                <td><span class="badge bg-<?= getStatusBadgeColor($task['status']) ?>"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $task['status']))) ?></span></td>
                                                <td>
                                                    <a href="<?= BASE_URL ?>?page=edit_task&id=<?= htmlspecialchars($task['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill me-1">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <?php
                                                    // Using htmlspecialchars and addslashes for JavaScript string literal safety
                                                    $taskDeleteConfirmMsg = addslashes('Are you sure you want to delete task ID ' . htmlspecialchars($task['id'], ENT_QUOTES, 'UTF-8') . '? This action cannot be undone.');
                                                    ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill"
                                                            onclick="showCustomConfirm(
                                                                '<?= addslashes('Delete Task') ?>',
                                                                '<?= $taskDeleteConfirmMsg ?>',
                                                                'dashboard', /* target page for index.php routing */
                                                                'delete_task', /* action for dashboard.php */
                                                                '<?= htmlspecialchars($task['id'], ENT_QUOTES, 'UTF-8') ?>' /* ID of the task to delete */
                                                            )">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No tasks have been assigned yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>


        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i>User Task Reports (Overview)</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Detailed user task reports can be viewed in the <a href="<?= BASE_URL ?>?page=reports" class="text-info">Reports</a> section.</p>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>User Name</th>
                                        <th>Role</th>
                                        <th>Completed Tasks</th>
                                        <th>Pending Tasks</th>
                                        <th>Total Assigned</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($userTaskReports)): ?>
                                        <?php foreach ($userTaskReports as $report): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($report['user_name']) ?></td>
                                                <td><span class="badge bg-secondary"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $report['user_role']))) ?></span></td>
                                                <td><span class="badge bg-success"><?= htmlspecialchars($report['completed_tasks']) ?></span></td>
                                                <td><span class="badge bg-warning"><?= htmlspecialchars($report['pending_tasks']) ?></span></td>
                                                <td><span class="badge bg-primary"><?= htmlspecialchars($report['total_assigned']) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No user task data found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Latest Activities</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php if (!empty($latestActivities)): ?>
                                <?php foreach ($latestActivities as $activity): ?>
                                    <li class="list-group-item">
                                        <small class="text-muted float-end"><?= date('Y-m-d H:i', strtotime($activity['activity_timestamp'])) ?></small>
                                        <?php if ($activity['activity_type'] === 'task'): ?>
                                            Task #<?= htmlspecialchars($activity['id']) ?> (Status: <?= ucwords(htmlspecialchars(str_replace('_', ' ', $activity['status']))) ?>) for Client: <?= htmlspecialchars($activity['client_name']) ?> assigned to User: <?= htmlspecialchars($activity['user_name']) ?>.
                                        <?php elseif ($activity['activity_type'] === 'expense'): ?>
                                            New expense added: <?= htmlspecialchars($activity['expense_type']) ?> (<?= $currencySymbol ?><?= number_format($activity['amount'], 2) ?>) - <?= htmlspecialchars(substr($activity['description'], 0, 50)) ?><?= (strlen($activity['description']) > 50 ? '...' : '') ?>.
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center text-muted">No recent activities found.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; // Include the footer (contains closing HTML tags and Bootstrap JS) ?>