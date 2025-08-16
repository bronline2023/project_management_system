<?php
/**
 * admin/reports.php
 *
 * This file provides various financial and task-related reports for the administrator.
 * It includes:
 * - Revenue Summary (Total Income, Expenses, Profit).
 * - Detailed Revenue Report (filterable by time period, search, and paginated).
 * - Detailed Expenses Report (filterable by time period, search, and paginated).
 * - All Work Tasks Report (filterable by time period, search, and paginated).
 * - Detailed Maintenance Report (filterable by time period, search, and paginated).
 * - Filters for daily, weekly, monthly, yearly, and all-time data.
 * - Integration with currency symbol from settings.
 * - Export functionality for detailed reports.
 *
 * It ensures that only authenticated admin users can access this page.
 */

// Include the configuration file for database connection and session management.
require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';   // Database interaction functions
require_once MODELS_PATH . 'auth.php'; // Authentication functions

// Restrict access to admin users only.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB(); // Establish database connection
$message = ''; // To store success or error messages

// --- Report Parameters for Filtering/Searching ---
$time_period = $_GET['time_period'] ?? 'monthly'; // Default to monthly
$search_query = trim($_GET['search'] ?? ''); // General search for work description or client name

// Pagination settings (can be reused for multiple reports, or separate if needed)
$limit = 10;

// Get currency symbol from settings for display
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

// Function to get date clauses based on time period for MySQL
function getDateClause($period, $date_field = 'created_at') {
    switch ($period) {
        case 'daily':
            return "DATE($date_field) = CURDATE()";
        case 'weekly':
            // Adjust for Sunday start if needed: (WEEKDAY(CURDATE()) + 7) % 7 gives 0 for Monday, 6 for Sunday
            return "$date_field BETWEEN CURDATE() - INTERVAL (WEEKDAY(CURDATE()) + 7) % 7 DAY AND CURDATE() + INTERVAL (6 - (WEEKDAY(CURDATE()) + 7) % 7) DAY";
        case 'monthly':
            return "YEAR($date_field) = YEAR(CURDATE()) AND MONTH($date_field) = MONTH(CURDATE())";
        case 'yearly':
            return "YEAR($date_field) = YEAR(CURDATE())";
        case 'all':
        default:
            return "1=1"; // No specific date filter (all time)
    }
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

// Helper function for payment status badge color
function getPaymentStatusBadgeColor($payment_status) {
    switch ($payment_status) {
        case 'pending': return 'warning';
        case 'paid_full': return 'success';
        case 'paid_partial': return 'info';
        case 'refunded': return 'danger';
        default: return 'secondary';
    }
}


// --- 1. Revenue Summary ---
$totalIncome = 0;
$totalExpenses = 0;
$netProfit = 0;

try {
    // Calculate Total Income from Completed Tasks
    $dateClauseIncome = getDateClause($time_period, 'wa.completed_at');
    $stmt = $pdo->prepare("SELECT SUM(fee - maintenance_fee) AS total_income FROM work_assignments wa WHERE status = 'completed' AND $dateClauseIncome");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalIncome = $result['total_income'] ?? 0;

    // Calculate Total Expenses
    $dateClauseExpensesSummary = getDateClause($time_period, 'expense_date');
    $stmt = $pdo->prepare("SELECT SUM(amount) AS total_expenses FROM expenses WHERE $dateClauseExpensesSummary");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalExpenses = $result['total_expenses'] ?? 0;

    $netProfit = $totalIncome - $totalExpenses;

} catch (PDOException $e) {
    error_log("Error loading revenue summary: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading revenue summary. This often means your `work_assignments` table is missing the `completed_at` column, or the database is not fully set up.<br><strong>Error: ' . htmlspecialchars($e->getMessage()) . '</strong></div>';
}


// --- 2. Detailed Revenue Report (Completed Tasks) ---
$detailedRevenue = [];
$total_detailed_revenue_records = 0;
$page_revenue = isset($_GET['p_revenue']) && is_numeric($_GET['p_revenue']) ? (int)$_GET['p_revenue'] : 1;
$offset_revenue = ($page_revenue - 1) * $limit;

try {
    $dateClauseDetailedRevenue = getDateClause($time_period, 'wa.completed_at'); // Use completed_at for revenue
    $search_clause_detailed_revenue = '';
    if ($search_query) {
        // Changed client name column to cl.client_name
        $search_clause_detailed_revenue = "AND (cl.client_name LIKE :search_query OR wa.work_description LIKE :search_query OR cat.name LIKE :search_query OR sub.name LIKE :search_query)";
    }

    // Count total records for pagination
    $count_sql_detailed_revenue = "SELECT COUNT(*)
                           FROM work_assignments wa
                           JOIN clients cl ON wa.client_id = cl.id
                           JOIN categories cat ON wa.category_id = cat.id
                           JOIN subcategories sub ON wa.subcategory_id = sub.id
                           WHERE wa.status = 'completed' AND $dateClauseDetailedRevenue $search_clause_detailed_revenue";
    $stmt_count_detailed_revenue = $pdo->prepare($count_sql_detailed_revenue);
    if ($search_query) {
        $searchTerm = '%' . $search_query . '%';
        $stmt_count_detailed_revenue->bindParam(':search_query', $searchTerm, PDO::PARAM_STR);
    }
    $stmt_count_detailed_revenue->execute();
    $total_detailed_revenue_records = $stmt_count_detailed_revenue->fetchColumn();

    if ($total_detailed_revenue_records === false) {
        throw new PDOException("Error counting detailed revenue records.");
    }

    // Fetch detailed records with pagination
    $sql_detailed_revenue = "SELECT wa.id, cl.client_name AS client_name, us.name AS user_name,
                            cat.name AS category_name, sub.name AS subcategory_name,
                            wa.work_description, wa.fee, wa.maintenance_fee, wa.payment_status, wa.completed_at
                     FROM work_assignments wa
                     JOIN clients cl ON wa.client_id = cl.id
                     JOIN users us ON wa.assigned_to_user_id = us.id
                     JOIN categories cat ON wa.category_id = cat.id
                     JOIN subcategories sub ON wa.subcategory_id = sub.id
                     WHERE wa.status = 'completed' AND $dateClauseDetailedRevenue $search_clause_detailed_revenue
                     ORDER BY wa.completed_at DESC
                     LIMIT :limit OFFSET :offset";
    $stmt_detailed_revenue = $pdo->prepare($sql_detailed_revenue);
    $stmt_detailed_revenue->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt_detailed_revenue->bindParam(':offset', $offset_revenue, PDO::PARAM_INT);
    if ($search_query) {
        $searchTerm = '%' . $search_query . '%';
        $stmt_detailed_revenue->bindParam(':search_query', $searchTerm, PDO::PARAM_STR);
    }
    $stmt_detailed_revenue->execute();
    $detailedRevenue = $stmt_detailed_revenue->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error loading detailed revenue data: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading detailed revenue data. Please verify your database setup, specifically the `work_assignments` table.<br><strong>Error: ' . htmlspecialchars($e->getMessage()) . '</strong></div>';
}

$total_detailed_revenue_pages = ceil($total_detailed_revenue_records / $limit);


// --- 3. Detailed Expenses Report ---
$detailedExpenses = [];
$total_detailed_expense_records = 0;
$page_expense = isset($_GET['p_expense']) && is_numeric($_GET['p_expense']) ? (int)$_GET['p_expense'] : 1;
$offset_expense = ($page_expense - 1) * $limit;

try {
    $dateClauseDetailedExpense = getDateClause($time_period, 'expense_date');
    $search_clause_detailed_expense = '';
    if ($search_query) {
        $search_clause_detailed_expense = "AND (expense_type LIKE :search_query OR description LIKE :search_query)";
    }

    // Count total records for pagination
    $count_sql_detailed_expense = "SELECT COUNT(*) FROM expenses WHERE $dateClauseDetailedExpense $search_clause_detailed_expense";
    $stmt_count_detailed_expense = $pdo->prepare($count_sql_detailed_expense);
    if ($search_query) {
        $searchTerm = '%' . $search_query . '%';
        $stmt_count_detailed_expense->bindParam(':search_query', $searchTerm, PDO::PARAM_STR);
    }
    $stmt_count_detailed_expense->execute();
    $total_detailed_expense_records = $stmt_count_detailed_expense->fetchColumn();

    if ($total_detailed_expense_records === false) {
        throw new PDOException("Error counting detailed expense records.");
    }

    // Fetch detailed records with pagination
    $sql_detailed_expense = "SELECT id, expense_type, amount, description, expense_date, created_at
                             FROM expenses
                             WHERE $dateClauseDetailedExpense $search_clause_detailed_expense
                             ORDER BY expense_date DESC, created_at DESC
                             LIMIT :limit OFFSET :offset";
    $stmt_detailed_expense = $pdo->prepare($sql_detailed_expense);
    $stmt_detailed_expense->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt_detailed_expense->bindParam(':offset', $offset_expense, PDO::PARAM_INT);
    if ($search_query) {
        $searchTerm = '%' . $search_query . '%';
        $stmt_detailed_expense->bindParam(':search_query', $searchTerm, PDO::PARAM_STR);
    }
    $stmt_detailed_expense->execute();
    $detailedExpenses = $stmt_detailed_expense->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error loading detailed expense data: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading detailed expense data. Please verify your database setup.<br><strong>Error: ' . htmlspecialchars($e->getMessage()) . '</strong></div>';
}

$total_detailed_expense_pages = ceil($total_detailed_expense_records / $limit);


// --- 4. All Work Tasks Report ---
$allWorkTasks = [];
$total_all_tasks_records = 0;
$page_tasks = isset($_GET['p_tasks']) && is_numeric($_GET['p_tasks']) ? (int)$_GET['p_tasks'] : 1;
$offset_tasks = ($page_tasks - 1) * $limit;

try {
    $dateClauseAllTasks = getDateClause($time_period, 'wa.created_at'); // Use created_at for all tasks report
    $search_clause_all_tasks = '';
    if ($search_query) {
        // Changed client name column to cl.client_name
        $search_clause_all_tasks = "AND (cl.client_name LIKE :search_query OR us.name LIKE :search_query OR wa.work_description LIKE :search_query OR cat.name LIKE :search_query OR sub.name LIKE :search_query)";
    }

    // Count total records for pagination
    $count_sql_all_tasks = "SELECT COUNT(*)
                           FROM work_assignments wa
                           JOIN clients cl ON wa.client_id = cl.id
                           JOIN users us ON wa.assigned_to_user_id = us.id
                           JOIN categories cat ON wa.category_id = cat.id
                           JOIN subcategories sub ON wa.subcategory_id = sub.id
                           WHERE $dateClauseAllTasks $search_clause_all_tasks";
    $stmt_count_all_tasks = $pdo->prepare($count_sql_all_tasks);
    if ($search_query) {
        $searchTerm = '%' . $search_query . '%';
        $stmt_count_all_tasks->bindParam(':search_query', $searchTerm, PDO::PARAM_STR);
    }
    $stmt_count_all_tasks->execute();
    $total_all_tasks_records = $stmt_count_all_tasks->fetchColumn();

    if ($total_all_tasks_records === false) {
        throw new PDOException("Error counting all work tasks records.");
    }

    // Fetch all work tasks with pagination
    $sql_all_tasks = "SELECT wa.id, cl.client_name AS client_name, us.name AS user_name,
                            cat.name AS category_name, sub.name AS subcategory_name,
                            wa.work_description, wa.deadline, wa.status, wa.payment_status, wa.created_at
                     FROM work_assignments wa
                     JOIN clients cl ON wa.client_id = cl.id
                     JOIN users us ON wa.assigned_to_user_id = us.id
                     JOIN categories cat ON wa.category_id = cat.id
                     JOIN subcategories sub ON wa.subcategory_id = sub.id
                     WHERE $dateClauseAllTasks $search_clause_all_tasks
                     ORDER BY wa.created_at DESC
                     LIMIT :limit OFFSET :offset";
    $stmt_all_tasks = $pdo->prepare($sql_all_tasks);
    $stmt_all_tasks->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt_all_tasks->bindParam(':offset', $offset_tasks, PDO::PARAM_INT);
    if ($search_query) {
        $searchTerm = '%' . $search_query . '%';
        $stmt_all_tasks->bindParam(':search_query', $searchTerm, PDO::PARAM_STR);
    }
    $stmt_all_tasks->execute();
    $allWorkTasks = $stmt_all_tasks->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error loading all work tasks data: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading all work tasks data. Please verify your database setup.<br><strong>Error: ' . htmlspecialchars($e->getMessage()) . '</strong></div>';
}

$total_all_tasks_pages = ceil($total_all_tasks_records / $limit);

// --- 5. Detailed Maintenance Report ---
$detailedMaintenance = [];
$total_detailed_maintenance_records = 0;
$page_maintenance = isset($_GET['p_maintenance']) && is_numeric($_GET['p_maintenance']) ? (int)$_GET['p_maintenance'] : 1;
$offset_maintenance = ($page_maintenance - 1) * $limit;

try {
    // Maintenance tasks are those with maintenance_fee > 0
    $dateClauseMaintenance = getDateClause($time_period, 'wa.created_at'); // Filter by creation date
    $search_clause_maintenance = '';
    if ($search_query) {
        // Changed client name column to cl.client_name
        $search_clause_maintenance = "AND (cl.client_name LIKE :search_query OR us.name LIKE :search_query OR wa.work_description LIKE :search_query OR cat.name LIKE :search_query OR sub.name LIKE :search_query)";
    }

    // Count total records for pagination
    $count_sql_maintenance = "SELECT COUNT(*)
                           FROM work_assignments wa
                           JOIN clients cl ON wa.client_id = cl.id
                           JOIN users us ON wa.assigned_to_user_id = us.id
                           JOIN categories cat ON wa.category_id = cat.id
                           JOIN subcategories sub ON wa.subcategory_id = sub.id
                           WHERE wa.maintenance_fee > 0 AND $dateClauseMaintenance $search_clause_maintenance";
    $stmt_count_maintenance = $pdo->prepare($count_sql_maintenance);
    if ($search_query) {
        $searchTerm = '%' . $search_query . '%';
        $stmt_count_maintenance->bindParam(':search_query', $searchTerm, PDO::PARAM_STR);
    }
    $stmt_count_maintenance->execute();
    $total_detailed_maintenance_records = $stmt_count_maintenance->fetchColumn();

    if ($total_detailed_maintenance_records === false) {
        throw new PDOException("Error counting detailed maintenance records.");
    }

    // Fetch detailed records with pagination
    $sql_detailed_maintenance = "SELECT wa.id, cl.client_name AS client_name, us.name AS user_name,
                                        cat.name AS category_name, sub.name AS subcategory_name,
                                        wa.work_description, wa.maintenance_fee, wa.maintenance_fee_mode,
                                        wa.status, wa.payment_status, wa.created_at, wa.completed_at
                                 FROM work_assignments wa
                                 JOIN clients cl ON wa.client_id = cl.id
                                 JOIN users us ON wa.assigned_to_user_id = us.id
                                 JOIN categories cat ON wa.category_id = cat.id
                                 JOIN subcategories sub ON wa.subcategory_id = sub.id
                                 WHERE wa.maintenance_fee > 0 AND $dateClauseMaintenance $search_clause_maintenance
                                 ORDER BY wa.created_at DESC
                                 LIMIT :limit OFFSET :offset";
    $stmt_detailed_maintenance = $pdo->prepare($sql_detailed_maintenance);
    $stmt_detailed_maintenance->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt_detailed_maintenance->bindParam(':offset', $offset_maintenance, PDO::PARAM_INT);
    if ($search_query) {
        $searchTerm = '%' . $search_query . '%';
        $stmt_detailed_maintenance->bindParam(':search_query', $searchTerm, PDO::PARAM_STR);
    }
    $stmt_detailed_maintenance->execute();
    $detailedMaintenance = $stmt_detailed_maintenance->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error loading detailed maintenance data: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading detailed maintenance data. Please verify your database setup.<br><strong>Error: ' . htmlspecialchars($e->getMessage()) . '</strong></div>';
}

$total_detailed_maintenance_pages = ceil($total_detailed_maintenance_records / $limit);


// Include the header (contains HTML <head> and initial Bootstrap/CSS)
include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; // Include the sidebar for navigation ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Reports</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; // Custom message box ?>
            <!-- The JavaScript for auto-hiding alerts is now handled globally in script.js -->
        <?php endif; ?>

        <!-- Filters and Search -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-dark"><i class="fas fa-filter me-2"></i>Filter Reports</h5>
            </div>
            <div class="card-body">
                <form action="" method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="page" value="reports">
                    <div class="col-md-4">
                        <label for="timePeriodFilter" class="form-label">Time Period:</label>
                        <select class="form-select rounded-pill" id="timePeriodFilter" name="time_period">
                            <option value="daily" <?= ($time_period === 'daily') ? 'selected' : '' ?>>Daily</option>
                            <option value="weekly" <?= ($time_period === 'weekly') ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= ($time_period === 'monthly') ? 'selected' : '' ?>>Monthly</option>
                            <option value="yearly" <?= ($time_period === 'yearly') ? 'selected' : '' ?>>Yearly</option>
                            <option value="all" <?= ($time_period === 'all') ? 'selected' : '' ?>>All Time</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="reportSearch" class="form-label">Search:</label>
                        <input type="text" class="form-control rounded-pill" id="reportSearch" name="search" placeholder="Client, Description, Category, Subcategory" value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary rounded-pill w-100"><i class="fas fa-search me-2"></i>Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Revenue Summary -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Revenue Summary (<?= ucwords(htmlspecialchars($time_period)) ?>)</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4 mb-3">
                        <div class="p-3 bg-success-subtle rounded-3 shadow-sm">
                            <h6 class="text-success mb-2">Total Income</h6>
                            <h3 class="fw-bold text-success"><?= $currencySymbol ?><?= number_format($totalIncome, 2) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="p-3 bg-danger-subtle rounded-3 shadow-sm">
                            <h6 class="text-danger mb-2">Total Expenses</h6>
                            <h3 class="fw-bold text-danger"><?= $currencySymbol ?><?= number_format($totalExpenses, 2) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="p-3 bg-info-subtle rounded-3 shadow-sm">
                            <h6 class="text-info mb-2">Net Profit</h6>
                            <h3 class="fw-bold text-info"><?= $currencySymbol ?><?= number_format($netProfit, 2) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Revenue Report -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Detailed Revenue Report (Completed Tasks)</h5>
                <a href="<?= BASE_URL ?>admin/export_report.php?type=revenue&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>" class="btn btn-success rounded-pill btn-sm">
                    <i class="fas fa-file-excel me-2"></i>Export Excel
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Task ID</th>
                                <th>Client</th>
                                <th>Assigned To</th>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>Description</th>
                                <th>Fee</th>
                                <th>Maintenance Fee</th>
                                <th>Total Paid</th>
                                <th>Payment Status</th>
                                <th>Completed On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($detailedRevenue)): ?>
                                <?php foreach ($detailedRevenue as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id']) ?></td>
                                        <td><?= htmlspecialchars($row['client_name']) ?></td>
                                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                                        <td><?= htmlspecialchars($row['category_name']) ?></td>
                                        <td><?= htmlspecialchars($row['subcategory_name']) ?></td>
                                        <td><?= htmlspecialchars(substr($row['work_description'], 0, 50)) ?><?= (strlen($row['work_description']) > 50 ? '...' : '') ?></td>
                                        <td><?= $currencySymbol ?><?= number_format($row['fee'], 2) ?></td>
                                        <td><?= $currencySymbol ?><?= number_format($row['maintenance_fee'], 2) ?></td>
                                        <td><?= $currencySymbol ?><?= number_format($row['fee'] - $row['maintenance_fee'], 2) ?></td>
                                        <td><span class="badge bg-<?= getPaymentStatusBadgeColor($row['payment_status']) ?>"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $row['payment_status']))) ?></span></td>
                                        <td><?= date('Y-m-d H:i', strtotime($row['completed_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center">No completed tasks found for the selected filters.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Detailed Revenue -->
                <?php if ($total_detailed_revenue_records > $limit): ?>
                    <nav aria-label="Page navigation Detailed Revenue" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page_revenue <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-pill me-1" href="<?= BASE_URL ?>?page=reports&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&p_revenue=<?= $page_revenue - 1 ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_detailed_revenue_pages; $i++): ?>
                                <li class="page-item <?= ($page_revenue == $i) ? 'active' : '' ?>">
                                    <a class="page-link rounded-pill me-1" href="<?= BASE_URL ?>?page=reports&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&p_revenue=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page_revenue >= $total_detailed_revenue_pages) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-pill" href="<?= BASE_URL ?>?page=reports&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&p_revenue=<?= $page_revenue + 1 ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

        <!-- Detailed Expenses Report -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Detailed Expenses Report</h5>
                <a href="<?= BASE_URL ?>admin/export_report.php?type=expenses&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>" class="btn btn-success rounded-pill btn-sm">
                    <i class="fas fa-file-excel me-2"></i>Export Excel
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Expense ID</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Recorded At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($detailedExpenses)): ?>
                                <?php foreach ($detailedExpenses as $expense): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($expense['id']) ?></td>
                                        <td><?= htmlspecialchars($expense['expense_type']) ?></td>
                                        <td><?= $currencySymbol ?><?= number_format($expense['amount'], 2) ?></td>
                                        <td><?= htmlspecialchars(substr($expense['description'], 0, 50)) ?><?= (strlen($expense['description']) > 50 ? '...' : '') ?></td>
                                        <td><?= date('Y-m-d', strtotime($expense['expense_date'])) ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($expense['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No expenses found for the selected filters.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Detailed Expenses -->
                <?php if ($total_detailed_expense_records > $limit): ?>
                    <nav aria-label="Page navigation Detailed Expenses" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page_expense <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-pill me-1" href="<?= BASE_URL ?>?page=reports&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&p_expense=<?= $page_expense - 1 ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_detailed_expense_pages; $i++): ?>
                                <li class="page-item <?= ($page_expense == $i) ? 'active' : '' ?>">
                                    <a class="page-link rounded-pill me-1" href="<?= BASE_URL ?>?page=reports&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&p_expense=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page_expense >= $total_detailed_expense_pages) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-pill" href="<?= BASE_URL ?>?page=reports&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&p_expense=<?= $page_expense + 1 ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

        <!-- All Work Tasks Report -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>All Work Tasks Overview</h5>
                <a href="<?= BASE_URL ?>admin/export_report.php?type=tasks&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>" class="btn btn-success rounded-pill btn-sm">
                    <i class="fas fa-file-excel me-2"></i>Export Excel
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Task ID</th>
                                <th>Client</th>
                                <th>Assigned To</th>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>Description</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Created On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($allWorkTasks)): ?>
                                <?php foreach ($allWorkTasks as $task): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($task['id']) ?></td>
                                        <td><?= htmlspecialchars($task['client_name']) ?></td>
                                        <td><?= htmlspecialchars($task['user_name']) ?></td>
                                        <td><?= htmlspecialchars($task['category_name']) ?></td>
                                        <td><?= htmlspecialchars($task['subcategory_name']) ?></td>
                                        <td><?= htmlspecialchars(substr($task['work_description'], 0, 50)) ?><?= (strlen($task['work_description']) > 50 ? '...' : '') ?></td>
                                        <td><?= date('Y-m-d', strtotime($task['deadline'])) ?></td>
                                        <td><span class="badge bg-<?= getStatusBadgeColor($task['status']) ?>"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $task['status']))) ?></span></td>
                                        <td><span class="badge bg-<?= getPaymentStatusBadgeColor($task['payment_status']) ?>"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $task['payment_status']))) ?></span></td>
                                        <td><?= date('Y-m-d H:i', strtotime($task['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">No work tasks found for the selected filters.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for All Work Tasks -->
                <?php if ($total_all_tasks_records > $limit): ?>
                    <nav aria-label="Page navigation All Tasks" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page_tasks <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-pill me-1" href="<?= BASE_URL ?>?page=reports&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&p_tasks=<?= $page_tasks - 1 ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_all_tasks_pages; $i++): ?>
                                <li class="page-item <?= ($page_tasks == $i) ? 'active' : '' ?>">
                                    <a class="page-link rounded-pill me-1" href="<?= BASE_URL ?>?page=reports&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&p_tasks=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page_tasks >= $total_all_tasks_pages) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-pill" href="<?= BASE_URL ?>?page=reports&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&p_tasks=<?= $page_tasks + 1 ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

        <!-- Detailed Maintenance Report -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-wrench me-2"></i>Detailed Maintenance Report</h5>
                <a href="<?= BASE_URL ?>admin/export_report.php?type=maintenance&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>" class="btn btn-light rounded-pill btn-sm">
                    <i class="fas fa-file-excel me-2"></i>Export Excel
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Task ID</th>
                                <th>Client</th>
                                <th>Assigned To</th>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>Description</th>
                                <th>Maintenance Fee</th>
                                <th>Fee Mode</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Created On</th>
                                <th>Completed On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($detailedMaintenance)): ?>
                                <?php foreach ($detailedMaintenance as $task): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($task['id']) ?></td>
                                        <td><?= htmlspecialchars($task['client_name']) ?></td>
                                        <td><?= htmlspecialchars($task['user_name']) ?></td>
                                        <td><?= htmlspecialchars($task['category_name']) ?></td>
                                        <td><?= htmlspecialchars($task['subcategory_name']) ?></td>
                                        <td><?= htmlspecialchars(substr($task['work_description'], 0, 50)) ?><?= (strlen($task['work_description']) > 50 ? '...' : '') ?></td>
                                        <td><?= $currencySymbol ?><?= number_format($task['maintenance_fee'], 2) ?></td>
                                        <td><span class="badge bg-secondary"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $task['maintenance_fee_mode']))) ?></span></td>
                                        <td><span class="badge bg-<?= getStatusBadgeColor($task['status']) ?>"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $task['status']))) ?></span></td>
                                        <td><span class="badge bg-<?= getPaymentStatusBadgeColor($task['payment_status']) ?>"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $task['payment_status']))) ?></span></td>
                                        <td><?= date('Y-m-d H:i', strtotime($task['created_at'])) ?></td>
                                        <td><?= $task['completed_at'] ? date('Y-m-d H:i', strtotime($task['completed_at'])) : 'N/A' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="12" class="text-center">No maintenance tasks found for the selected filters.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Detailed Maintenance Report -->
                <?php if ($total_detailed_maintenance_records > $limit): ?>
                    <nav aria-label="Page navigation Detailed Maintenance" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page_maintenance <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-pill me-1" href="<?= BASE_URL ?>?page=reports&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&p_maintenance=<?= $page_maintenance - 1 ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_detailed_maintenance_pages; $i++): ?>
                                <li class="page-item <?= ($page_maintenance == $i) ? 'active' : '' ?>">
                                    <a class="page-link rounded-pill me-1" href="<?= BASE_URL ?>?page=reports&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&p_maintenance=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page_maintenance >= $total_detailed_maintenance_pages) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-pill" href="<?= BASE_URL ?>?page=reports&time_period=<?= $time_period ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&p_maintenance=<?= $page_maintenance + 1 ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; // Include the footer ?>
