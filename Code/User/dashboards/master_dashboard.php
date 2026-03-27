<?php
/**
 * user/master_dashboard.php
 * FINAL MASTER DASHBOARD - ENGLISH VERSION
 * Features: Complete Financials, Receivable/Payable, Task Stages, Recruitment, Popups & Wallet Recharges
 */

require_once MODELS_PATH . 'db.php';
$pdo = connectDB();

$currentUserId = $_SESSION['user_id'];
$currencySymbol = '₹';

// --- 1. FINANCIAL ANALYSIS ---

// A. Profit Calculation
$totalRevenue = fetchColumn($pdo, "SELECT SUM(fee) FROM work_assignments WHERE status = 'verified_completed'") ?: 0;
$totalPayouts = fetchColumn($pdo, "SELECT SUM(task_price) FROM work_assignments WHERE status = 'verified_completed'") ?: 0;
$totalExpenses = fetchColumn($pdo, "SELECT SUM(amount) FROM expenses") ?: 0;
$netProfit = ($totalRevenue - $totalPayouts) - $totalExpenses;

// B. Wallet Status (Receivable/Payable)
// Positive Balance = Company needs to PAY Freelancer (Liability)
$totalPayableToFreelancers = fetchColumn($pdo, "SELECT SUM(balance) FROM users WHERE role_id != 1 AND balance > 0") ?: 0;

// Negative Balance = Freelancer needs to PAY Company (Cash Collected / Receivable)
$totalRecoverFromFreelancers = fetchColumn($pdo, "SELECT ABS(SUM(balance)) FROM users WHERE role_id != 1 AND balance < 0") ?: 0;

// C. Monthly & Withdrawal Stats
$currentMonth = date('m');
$monthlyExpense = fetchColumn($pdo, "SELECT SUM(amount) FROM expenses WHERE MONTH(expense_date) = ?", [$currentMonth]) ?: 0;

$pendingWithdrawalCount = fetchColumn($pdo, "SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'");
$pendingWithdrawalAmount = fetchColumn($pdo, "SELECT SUM(amount) FROM withdrawals WHERE status = 'pending'") ?: 0;


// --- 2. TASK & WORKFLOW STATS ---
$totalTasks = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments");
$pendingTasks = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE status = 'pending'");
$processTasks = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE status = 'in_process'");
$completedTasks = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE status = 'verified_completed'");
// Work submitted but pending admin verification
$pendingVerification = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE status = 'pending_verification'");


// --- 3. USER & RECRUITMENT STATS ---
$totalUsers = fetchColumn($pdo, "SELECT COUNT(*) FROM users WHERE role_id != 1");
$pendingRecruitment = fetchColumn($pdo, "SELECT COUNT(*) FROM recruitment_posts WHERE approval_status = 'pending'");
$activeRecruitment = fetchColumn($pdo, "SELECT COUNT(*) FROM recruitment_posts WHERE approval_status = 'approved'");


// --- 4. APPOINTMENT TRACKER ---
$todayAppointments = fetchColumn($pdo, "SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()");
$cancelledAppointments = fetchColumn($pdo, "SELECT COUNT(*) FROM appointments WHERE status = 'cancelled'");
$completedAppointments = fetchColumn($pdo, "SELECT COUNT(*) FROM appointments WHERE status = 'completed'");


// --- 5. CHART DATA (Last 6 Months Income vs Expense) ---
$months = [];
$profitData = [];
$expenseData = [];

for ($i = 5; $i >= 0; $i--) {
    $d = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    
    // Net Income
    $income = fetchColumn($pdo, "SELECT SUM(fee - task_price) FROM work_assignments WHERE status = 'verified_completed' AND DATE_FORMAT(completion_date, '%Y-%m') = ?", [$d]) ?: 0;
    $profitData[] = $income;
    
    // Expenses
    $exp = fetchColumn($pdo, "SELECT SUM(amount) FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?", [$d]) ?: 0;
    $expenseData[] = $exp;
}


// --- 6. RECENT ACTIVITY LISTS ---

// Work submitted needing approval
$recentSubmissions = fetchAll($pdo, "
    SELECT wa.id, wa.updated_at, u.name as freelancer_name, c.name as category_name, wa.task_price
    FROM work_assignments wa
    JOIN users u ON wa.assigned_to_user_id = u.id
    JOIN categories c ON wa.category_id = c.id
    WHERE wa.status = 'pending_verification'
    ORDER BY wa.updated_at ASC LIMIT 5
");

// New withdrawal requests
$recentWithdrawals = fetchAll($pdo, "
    SELECT w.id, w.amount, w.requested_at, u.name as user_name
    FROM withdrawals w
    JOIN users u ON w.user_id = u.id
    WHERE w.status = 'pending'
    ORDER BY w.requested_at ASC LIMIT 5
");

// 🚀 7. WALLET RECHARGE REQUESTS COUNT 🚀
$pendingRecharges = 0;
try {
    $pendingRecharges = fetchColumn($pdo, "SELECT COUNT(*) FROM wallet_recharge_requests WHERE status = 'pending'") ?: 0;
} catch (Exception $e) {
    $pendingRecharges = 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Custom Dashboard Styling */
        .card-stats {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            color: white;
        }
        .card-stats:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .card-stats .card-body { position: relative; z-index: 2; padding: 1.5rem; }
        .card-stats .icon-bg {
            position: absolute; right: 15px; bottom: 10px;
            font-size: 4rem; opacity: 0.2; z-index: 1;
        }
        
        /* Gradients */
        .bg-gradient-success { background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); }
        .bg-gradient-danger { background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%); }
        .bg-gradient-primary { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); }
        .bg-gradient-warning { background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%); }
        .bg-gradient-info { background: linear-gradient(135deg, #36b9cc 0%, #258391 100%); }
        
        .stat-label { text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; opacity: 0.9; margin-bottom: 5px; font-weight: 600; }
        .stat-value { font-size: 1.8rem; font-weight: 700; margin-bottom: 0; }
        .stat-desc { font-size: 0.75rem; opacity: 0.8; margin-top: 5px; display: block; }

        /* Tables */
        .table-custom th { background-color: #f8f9fc; color: #5a5c69; font-weight: 700; font-size: 0.85rem; }
        .table-custom td { vertical-align: middle; font-size: 0.9rem; }
        
        /* Blink Animation */
        @keyframes blinker { 50% { opacity: 0; } }
        .blink { animation: blinker 1.5s linear infinite; }
    </style>
</head>
<body>

<div class="container-fluid mb-5">
    
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-tachometer-alt me-2"></i>Master Dashboard</h1>
        <div>
            <span class="badge bg-white text-dark border p-2 me-2 shadow-sm"><i class="far fa-calendar-alt"></i> <?= date('d M, Y') ?></span>
            <button class="btn btn-sm btn-primary shadow-sm" onclick="window.print()"><i class="fas fa-file-download fa-sm text-white-50"></i> Save Report</button>
        </div>
    </div>

    <?php if(isset($_SESSION['status_message'])) { echo $_SESSION['status_message']; unset($_SESSION['status_message']); } ?>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-stats bg-gradient-success">
                <div class="card-body">
                    <div class="stat-label">Total Company Profit</div>
                    <div class="stat-value"><?= $currencySymbol . number_format($netProfit, 2) ?></div>
                    <span class="stat-desc">Net Income (After Expenses)</span>
                    <i class="fas fa-chart-line icon-bg"></i>
                </div>
                <a href="index.php?page=reports" class="stretched-link"></a>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-stats bg-gradient-danger">
                <div class="card-body">
                    <div class="stat-label">Receivable (Cash Taken)</div>
                    <div class="stat-value"><?= $currencySymbol . number_format($totalRecoverFromFreelancers, 2) ?></div>
                    <span class="stat-desc">Cash Collected by Freelancers</span>
                    <i class="fas fa-hand-holding-usd icon-bg"></i>
                </div>
                <a href="index.php?page=users" class="stretched-link"></a>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-stats bg-gradient-primary">
                <div class="card-body">
                    <div class="stat-label">Payable (Wallet Balance)</div>
                    <div class="stat-value"><?= $currencySymbol . number_format($totalPayableToFreelancers, 2) ?></div>
                    <span class="stat-desc">Amount Credited to Wallets</span>
                    <i class="fas fa-wallet icon-bg"></i>
                </div>
                <a href="index.php?page=users" class="stretched-link"></a>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-stats bg-gradient-warning text-dark">
                <div class="card-body">
                    <div class="stat-label text-dark">Withdrawal Requests</div>
                    <div class="stat-value text-dark"><?= $pendingWithdrawalCount ?> <span class="fs-6 text-dark opacity-75">(<?= $currencySymbol . number_format($pendingWithdrawalAmount) ?>)</span></div>
                    <span class="stat-desc text-dark fw-bold">Pending Approvals</span>
                    <i class="fas fa-money-bill-wave icon-bg text-dark"></i>
                </div>
                <a href="index.php?page=withdrawals" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-2 col-md-4 mb-4">
            <a href="index.php?page=expenses" class="card shadow border-left-danger h-100 py-2 text-decoration-none">
                <div class="card-body text-center">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Monthly Expense</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($monthlyExpense) ?></div>
                </div>
            </a>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <a href="index.php?page=users" class="card shadow border-left-info h-100 py-2 text-decoration-none">
                <div class="card-body text-center">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Users</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalUsers ?></div>
                </div>
            </a>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <a href="index.php?page=all_tasks&status=in_process" class="card shadow border-left-primary h-100 py-2 text-decoration-none">
                <div class="card-body text-center">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">In Process Tasks</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $processTasks ?></div>
                </div>
            </a>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <a href="index.php?page=all_tasks&status=pending" class="card shadow border-left-warning h-100 py-2 text-decoration-none">
                <div class="card-body text-center">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Assignment</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $pendingTasks ?></div>
                </div>
            </a>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <a href="index.php?page=all_tasks&status=verified_completed" class="card shadow border-left-success h-100 py-2 text-decoration-none">
                <div class="card-body text-center">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed (Done)</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $completedTasks ?></div>
                </div>
            </a>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <a href="index.php?page=all_tasks&status=pending_verification" class="card shadow bg-warning text-white h-100 py-2 text-decoration-none">
                <div class="card-body text-center">
                    <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Pending Verify</div>
                    <div class="h5 mb-0 font-weight-bold"><?= $pendingVerification ?></div>
                </div>
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Financial Overview (Last 6 Months)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="profitChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-wallet me-2"></i>Wallet Recharges</h6>
                    <?php if($pendingRecharges > 0): ?>
                        <span class="badge bg-danger blink"><?= $pendingRecharges ?> New</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Pending Requests:</span>
                        <span class="fw-bold <?= $pendingRecharges > 0 ? 'text-danger' : 'text-success' ?> fs-5"><?= $pendingRecharges ?></span>
                    </div>
                    <a href="index.php?page=admin_wallet_requests" class="btn btn-outline-warning text-dark fw-bold btn-sm w-100 mt-2">Manage Recharges</a>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-info text-white d-flex justify-content-between">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-calendar-check me-2"></i>Appointments</h6>
                    <span class="badge bg-light text-dark"><?= $todayAppointments ?> Total</span>
                </div>
                <div class="card-body text-center py-2">
                    <div class="row">
                        <div class="col-6 border-end">
                            <h5 class="text-success font-weight-bold mb-0"><?= $completedAppointments ?></h5>
                            <small class="text-muted">Completed</small>
                        </div>
                        <div class="col-6">
                            <h5 class="text-danger font-weight-bold mb-0"><?= $cancelledAppointments ?></h5>
                            <small class="text-muted">Cancelled</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-dark text-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-bullhorn me-2"></i>Recruitment</h6>
                    <?php if($pendingRecruitment > 0): ?>
                        <span class="badge bg-danger blink"><?= $pendingRecruitment ?> New</span>
                    <?php endif; ?>
                </div>
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small">Active Posts:</span>
                        <span class="fw-bold text-success"><?= $activeRecruitment ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small">Pending Approval:</span>
                        <span class="fw-bold text-warning"><?= $pendingRecruitment ?></span>
                    </div>
                    <a href="index.php?page=manage_recruitment_posts" class="btn btn-outline-dark btn-sm w-100 mt-2">Manage Posts</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-tasks me-2"></i>Recent Work Submissions (Verify)</h6>
                    <a href="index.php?page=all_tasks&status=pending_verification" class="btn btn-sm btn-light text-primary fw-bold">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Freelancer</th>
                                    <th>Fee</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($recentSubmissions)): foreach($recentSubmissions as $sub): ?>
                                <tr>
                                    <td>#<?= $sub['id'] ?></td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($sub['freelancer_name']) ?></span>
                                        <br><small class="text-muted"><?= htmlspecialchars($sub['category_name']) ?></small>
                                    </td>
                                    <td class="text-success fw-bold"><?= $currencySymbol . $sub['task_price'] ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#taskModal<?= $sub['id'] ?>">Check</button>
                                        
                                        <div class="modal fade" id="taskModal<?= $sub['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Task Verification #<?= $sub['id'] ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p><strong>Freelancer:</strong> <?= htmlspecialchars($sub['freelancer_name']) ?></p>
                                                        <p><strong>Time:</strong> <?= date('d M, h:i A', strtotime($sub['updated_at'])) ?></p>
                                                        <div class="alert alert-info small">Please check the uploaded file and approve payment.</div>
                                                        <a href="index.php?page=edit_task&id=<?= $sub['id'] ?>" class="btn btn-primary w-100">Go to Task Details</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No pending work to verify.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 bg-danger text-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-hand-holding-usd me-2"></i>New Withdrawal Requests (Payouts)</h6>
                    <a href="index.php?page=withdrawals" class="btn btn-sm btn-light text-danger fw-bold">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($recentWithdrawals)): foreach($recentWithdrawals as $wd): ?>
                                <tr>
                                    <td>#<?= $wd['id'] ?></td>
                                    <td><?= htmlspecialchars($wd['user_name']) ?></td>
                                    <td class="text-danger fw-bold"><?= $currencySymbol . number_format($wd['amount'], 2) ?></td>
                                    <td>
                                        <a href="index.php?page=withdrawals" class="btn btn-sm btn-outline-danger rounded-pill px-3">Pay</a>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No new requests.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    var ctx = document.getElementById('profitChart').getContext('2d');
    var profitChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [{
                label: 'Net Profit',
                data: <?= json_encode($profitData) ?>,
                backgroundColor: 'rgba(28, 200, 138, 0.05)',
                borderColor: '#1cc88a',
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#1cc88a',
                tension: 0.3
            }, {
                label: 'Expenses',
                data: <?= json_encode($expenseData) ?>,
                backgroundColor: 'rgba(231, 74, 59, 0.05)',
                borderColor: '#e74a3b',
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#e74a3b',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { position: 'top' }
            }
        }
    });
</script>

</body>
</html>