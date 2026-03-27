<?php
/**
 * admin/reports.php
 * FINAL VERSION: Comprehensive Admin & User Reports
 * Features: Financials, Worker Liability, Recruitment Earnings, Expenses
 */

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// 1. Config & DB Connection
if (!defined('ROOT_PATH')) {
    if (file_exists('../../../config.php')) { require_once '../../../config.php'; } 
    else { die("Config missing. Please run from index.php"); }
}
if (!isset($pdo)) { require_once MODELS_PATH . 'db.php'; $pdo = connectDB(); }

// 2. User Identification
$currentUserId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['user_role'] ?? '';
$roleId = $_SESSION['role_id'] ?? 0;

// Admin Check: Role 'admin' or Role ID 1
$isAdmin = ($userRole === 'admin' || $roleId == 1);

// Security
if ($currentUserId == 0) {
    echo "<div class='alert alert-danger'>Access Denied. Please Login.</div>"; exit;
}

// 3. Filters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// ---------------------------------------------------------
// DATA FETCHING LOGIC
// ---------------------------------------------------------

// Query Condition: If Admin -> All Data. If User -> Only Self Data.
$userCondition = $isAdmin ? "" : " AND wa.assigned_to_user_id = $currentUserId ";
$recruitCondition = $isAdmin ? "" : " AND submitted_by_user_id = $currentUserId ";
$withdrawCondition = $isAdmin ? "" : " AND user_id = $currentUserId ";

// A. FINANCIAL / TASK REPORT
// Fetches verified completed tasks
$finQuery = "
    SELECT 
        wa.completion_date as date, 
        wa.id as task_id,
        wa.fee as client_fee,       -- Revenue (Full amount from client)
        wa.task_price as worker_pay, -- Expense (Amount promised to worker)
        wa.payment_collected_by,    -- 'self' or 'company'
        c.name as category_name,
        u.name as worker_name,
        cust.customer_name,
        cust.customer_phone
    FROM work_assignments wa
    LEFT JOIN categories c ON wa.category_id = c.id
    LEFT JOIN users u ON wa.assigned_to_user_id = u.id
    LEFT JOIN customers cust ON wa.customer_id = cust.id
    WHERE wa.status = 'verified_completed' 
    AND wa.completion_date BETWEEN ? AND ?
    $userCondition
    ORDER BY wa.completion_date DESC
";
$stmt = $pdo->prepare($finQuery);
$stmt->execute([$startDate, $endDate]);
$taskData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// B. EXPENSES (Only for Admin to calculate Net Profit)
$expenses = [];
if ($isAdmin) {
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE expense_date BETWEEN ? AND ? ORDER BY expense_date DESC");
    $stmt->execute([$startDate, $endDate]);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// C. RECRUITMENT POSTS (With Earnings)
// Get Earnings Rate from Settings
$ratePerPost = fetchColumn($pdo, "SELECT earning_per_approved_post FROM settings LIMIT 1") ?: 0;

$recSql = "SELECT * FROM recruitment_posts 
           WHERE created_at BETWEEN ? AND ? 
           $recruitCondition 
           ORDER BY created_at DESC";
$stmt = $pdo->prepare($recSql);
$stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$recruitmentData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// D. WITHDRAWALS (Payout History)
$withSql = "SELECT * FROM withdrawals WHERE requested_at BETWEEN ? AND ? $withdrawCondition ORDER BY requested_at DESC";
$stmt = $pdo->prepare($withSql);
$stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$withdrawalData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// E. APPOINTMENTS (Admin & Users with permission)
$appointmentData = [];
if ($isAdmin || in_array('my_appointments', $_SESSION['user_permissions'] ?? [])) {
    $aptSql = "SELECT * FROM appointments WHERE appointment_date BETWEEN ? AND ?";
    $stmt = $pdo->prepare($aptSql);
    $stmt->execute([$startDate, $endDate]);
    $appointmentData = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ---------------------------------------------------------
// CALCULATIONS
// ---------------------------------------------------------

$totalRevenue = 0;      // Total Fees
$totalWorkerEarning = 0; // Total Pay for Workers
$totalCashTaken = 0;    // Cash collected by workers (Liability)
$totalProfit = 0;       // Net Profit (Admin Only)
$totalRecruitEarn = 0;  // Recruitment Earnings

// Process Task Data
foreach ($taskData as $row) {
    $totalRevenue += $row['client_fee'];
    $totalWorkerEarning += $row['worker_pay'];
    $totalProfit += ($row['client_fee'] - $row['worker_pay']);

    // If worker collected payment, they owe it to company
    if ($row['payment_collected_by'] == 'self') {
        $totalCashTaken += $row['client_fee']; 
    }
}

// Process Recruitment Data
foreach ($recruitmentData as $post) {
    if ($post['approval_status'] == 'approved') {
        $totalRecruitEarn += $ratePerPost;
    }
}

// Process Expenses (Admin Only)
$totalExpenses = 0;
foreach ($expenses as $e) { $totalExpenses += $e['amount']; }

// FINAL NET CALCULATIONS
// Admin Net Profit = (Task Profit - Expenses)
$adminNetProfit = $totalProfit - $totalExpenses;

// User Net Payable = (Task Earnings + Recruitment Earnings) - (Cash Taken from Client)
// If Positive: Company needs to Pay User.
// If Negative: User needs to Pay Company.
$userNetPayable = ($totalWorkerEarning + $totalRecruitEarn) - $totalCashTaken;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        @media print { .no-print { display: none !important; } }
        .bg-light-gray { background-color: #f8f9fc; }
        .nav-tabs .nav-link.active { background-color: #4e73df; color: white; border-color: #4e73df; }
        .nav-tabs .nav-link { color: #4e73df; font-weight: bold; }
        .table-sm td, .table-sm th { font-size: 0.85rem; vertical-align: middle; }
    </style>
</head>
<body>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <?= $isAdmin ? 'Master Reports' : 'My Performance Reports' ?>
        </h1>
        <div class="no-print">
            <a href="admin/export_report_excel.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-sm btn-success shadow-sm me-2">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            <button onclick="window.print()" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <div class="card shadow mb-4 no-print border-left-primary">
        <div class="card-body py-2">
            <form method="GET" action="index.php" class="row align-items-center">
                <input type="hidden" name="page" value="reports">
                <div class="col-md-3"><label class="small fw-bold">Start Date</label><input type="date" name="start_date" class="form-control form-control-sm" value="<?= $startDate ?>"></div>
                <div class="col-md-3"><label class="small fw-bold">End Date</label><input type="date" name="end_date" class="form-control form-control-sm" value="<?= $endDate ?>"></div>
                <div class="col-md-2 pt-4"><button type="submit" class="btn btn-primary btn-sm w-100">Apply</button></div>
            </form>
        </div>
    </div>

    <div class="row">
        <?php if($isAdmin): ?>
            <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-success shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Revenue</div><div class="h5 mb-0 font-weight-bold">₹<?= number_format($totalRevenue, 2) ?></div></div></div></div>
            <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-danger shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Expenses</div><div class="h5 mb-0 font-weight-bold">₹<?= number_format($totalExpenses, 2) ?></div></div></div></div>
            <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Net Profit</div><div class="h5 mb-0 font-weight-bold">₹<?= number_format($adminNetProfit, 2) ?></div></div></div></div>
            <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-warning shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Payouts</div><div class="h5 mb-0 font-weight-bold">See Below</div></div></div></div>
        <?php else: ?>
            <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-success shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Earnings</div><div class="h5 mb-0 font-weight-bold">₹<?= number_format($totalWorkerEarning + $totalRecruitEarn, 2) ?></div></div></div></div>
            <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-danger shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Cash Taken (Liability)</div><div class="h5 mb-0 font-weight-bold">₹<?= number_format($totalCashTaken, 2) ?></div></div></div></div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Net Balance</div>
                        <div class="h5 mb-0 font-weight-bold">
                            <?php if($userNetPayable >= 0): ?>
                                <span class="text-success">Take: ₹<?= number_format($userNetPayable, 2) ?></span>
                            <?php else: ?>
                                <span class="text-danger">Give: ₹<?= number_format(abs($userNetPayable), 2) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <ul class="nav nav-tabs mb-3 no-print" id="reportTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tasks">✅ Task Reports</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#recruitment">📢 Recruitment</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#withdrawals">🏦 Payments</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#appointment">📅 Appointments</button></li>
    </ul>

    <div class="tab-content">
        
        <div class="tab-pane fade show active" id="tasks">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr class="bg-light-gray">
                                    <th>Date</th>
                                    <th>Task ID</th>
                                    <th>Category</th>
                                    <th>Customer</th>
                                    <?php if($isAdmin): ?><th>Worker</th><?php endif; ?>
                                    <th>Fee</th>
                                    <th>Earning/Cost</th>
                                    <th>Cash Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($taskData): foreach($taskData as $row): ?>
                                <tr>
                                    <td><?= date('d-m-Y', strtotime($row['date'])) ?></td>
                                    <td>#<?= $row['task_id'] ?></td>
                                    <td><?= htmlspecialchars($row['category_name']) ?></td>
                                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                    <?php if($isAdmin): ?><td><?= htmlspecialchars($row['worker_name']) ?></td><?php endif; ?>
                                    <td>₹<?= $row['client_fee'] ?></td>
                                    <td class="text-success font-weight-bold">₹<?= $row['worker_pay'] ?></td>
                                    <td>
                                        <?php if($row['payment_collected_by'] == 'self'): ?>
                                            <span class="badge bg-danger">Self (Received Cash)</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Company</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="8" class="text-center">No Tasks Found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if($isAdmin && $expenses): ?>
                <h5 class="text-danger mt-4">Office Expenses</h5>
                <table class="table table-bordered table-sm bg-white">
                    <?php foreach($expenses as $e): ?>
                        <tr><td><?= $e['expense_date'] ?></td><td><?= $e['description'] ?></td><td class="text-danger">- ₹<?= $e['amount'] ?></td></tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="recruitment">
            <div class="card shadow">
                <div class="card-body">
                    <table class="table table-bordered table-sm">
                        <thead><tr><th>Date</th><th>Job Title</th><th>Vacancies</th><th>Status</th><th>Earning</th></tr></thead>
                        <tbody>
                            <?php foreach($recruitmentData as $post): 
                                $earn = ($post['approval_status']=='approved') ? $ratePerPost : 0;
                            ?>
                            <tr>
                                <td><?= date('d-m-Y', strtotime($post['created_at'])) ?></td>
                                <td><?= htmlspecialchars($post['job_title']) ?></td>
                                <td><?= $post['total_vacancies'] ?></td>
                                <td><?= ucfirst($post['approval_status']) ?></td>
                                <td class="text-success font-weight-bold">₹<?= $earn ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="withdrawals">
            <div class="card shadow">
                <div class="card-body">
                    <table class="table table-bordered table-sm">
                        <thead><tr><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach($withdrawalData as $w): ?>
                            <tr>
                                <td><?= date('d-m-Y', strtotime($w['requested_at'])) ?></td>
                                <td class="text-primary font-weight-bold">₹<?= $w['amount'] ?></td>
                                <td><?= ucfirst($w['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="appointment">
            <div class="card shadow">
                <div class="card-body">
                    <table class="table table-bordered table-sm">
                        <thead><tr><th>Date</th><th>Client Name</th><th>Phone</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach($appointmentData as $apt): ?>
                            <tr>
                                <td><?= date('d-m-Y', strtotime($apt['appointment_date'])) ?></td>
                                <td><?= htmlspecialchars($apt['client_name']) ?></td>
                                <td><?= htmlspecialchars($apt['client_phone']) ?></td>
                                <td><?= ucfirst($apt['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div> </div>
</body>
</html>