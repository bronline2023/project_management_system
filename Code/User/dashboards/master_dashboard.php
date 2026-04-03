<?php
/**
 * user/master_dashboard.php
 * PREMIUM MASTER DASHBOARD - REFINED UI
 * Features: Complete Financials, Receivable/Payable, Task Stages, Recruitment, Popups & Wallet Recharges
 */

require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'roles.php';
$pdo = connectDB();

$currentUserId = $_SESSION['user_id'];
$currencySymbol = '₹';
$dash_perms = getDashboardPermissionsForRole($_SESSION['user_role']);

// --- 1. FINANCIAL ANALYSIS ---
$totalRevenue = fetchColumn($pdo, "SELECT SUM(fee) FROM work_assignments WHERE status = 'verified_completed'") ?: 0;
$totalPayouts = fetchColumn($pdo, "SELECT SUM(task_price) FROM work_assignments WHERE status = 'verified_completed'") ?: 0;
$totalExpenses = fetchColumn($pdo, "SELECT SUM(amount) FROM expenses") ?: 0;
$netProfit = ($totalRevenue - $totalPayouts) - $totalExpenses;
$totalPayableToFreelancers = fetchColumn($pdo, "SELECT SUM(balance) FROM users WHERE role_id != 1 AND balance > 0") ?: 0;
$totalRecoverFromFreelancers = fetchColumn($pdo, "SELECT ABS(SUM(balance)) FROM users WHERE role_id != 1 AND balance < 0") ?: 0;
$currentMonth = date('m');
$monthlyExpense = fetchColumn($pdo, "SELECT SUM(amount) FROM expenses WHERE MONTH(expense_date) = ?", [$currentMonth]) ?: 0;
$pendingWithdrawalCount = fetchColumn($pdo, "SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'");
$pendingWithdrawalAmount = fetchColumn($pdo, "SELECT SUM(amount) FROM withdrawals WHERE status = 'pending'") ?: 0;

// --- 2. TASK & WORKFLOW STATS ---
$totalTasks = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments");
$pendingTasks = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE status = 'pending'");
$processTasks = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE status = 'in_process'");
$completedTasks = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE status = 'verified_completed'");
$pendingVerification = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE status = 'pending_verification'");

// --- 3. USER & RECRUITMENT STATS ---
$totalUsers = fetchColumn($pdo, "SELECT COUNT(*) FROM users WHERE role_id != 1");
$pendingRecruitment = fetchColumn($pdo, "SELECT COUNT(*) FROM recruitment_posts WHERE approval_status = 'pending'");
$activeRecruitment = fetchColumn($pdo, "SELECT COUNT(*) FROM recruitment_posts WHERE approval_status = 'approved'");

// --- 4. APPOINTMENT TRACKER ---
$todayAppointments = fetchColumn($pdo, "SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()");
$cancelledAppointments = fetchColumn($pdo, "SELECT COUNT(*) FROM appointments WHERE status = 'cancelled'");
$completedAppointments = fetchColumn($pdo, "SELECT COUNT(*) FROM appointments WHERE status = 'completed'");

// --- 5. CHART DATA ---
$months = []; $profitData = []; $expenseData = [];
for ($i = 5; $i >= 0; $i--) {
    $d = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    $income = fetchColumn($pdo, "SELECT SUM(fee - task_price) FROM work_assignments WHERE status = 'verified_completed' AND DATE_FORMAT(completion_date, '%Y-%m') = ?", [$d]) ?: 0;
    $profitData[] = $income;
    $exp = fetchColumn($pdo, "SELECT SUM(amount) FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?", [$d]) ?: 0;
    $expenseData[] = $exp;
}

// --- 6. RECENT ACTIVITY LISTS ---
$recentSubmissions = fetchAll($pdo, "SELECT wa.id, wa.updated_at, u.name as freelancer_name, c.name as category_name, wa.task_price FROM work_assignments wa JOIN users u ON wa.assigned_to_user_id = u.id JOIN categories c ON wa.category_id = c.id WHERE wa.status = 'pending_verification' ORDER BY wa.updated_at ASC LIMIT 5");
$recentWithdrawals = fetchAll($pdo, "SELECT w.id, w.amount, w.requested_at, u.name as user_name FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.status = 'pending' ORDER BY w.requested_at ASC LIMIT 5");

// --- 7. WALLET RECHARGE REQUESTS COUNT ---
$pendingRecharges = 0;
try { $pendingRecharges = fetchColumn($pdo, "SELECT COUNT(*) FROM wallet_recharge_requests WHERE status = 'pending'") ?: 0; } catch (Exception $e) { $pendingRecharges = 0; }

?>
<div class="container-fluid mb-5 py-4">
    <?php if ($dash_perms['show_notice_board']) include VIEWS_PATH . 'components/notice_board.php'; ?>
    
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold"><i class="fas fa-crown text-warning me-2"></i>Master Dashboard</h1>
            <p class="text-muted small mb-0">Portal Wide Performance & Operations Control</p>
        </div>
        <div class="d-flex align-items-center">
            <div class="bg-white px-3 py-2 rounded-3 border shadow-sm me-3">
                <i class="far fa-calendar-alt text-primary me-2"></i><span class="fw-bold"><?= date('d M, Y') ?></span>
            </div>
            <button class="btn btn-primary shadow-sm rounded-3" onclick="window.print()"><i class="fas fa-download me-2"></i>Export Report</button>
        </div>
    </div>

    <!-- MAIN FINANCIAL GRID -->
    <?php if ($dash_perms['show_points_card']): ?>
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="master-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <div class="master-card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="icon-box bg-white-20 rounded-circle"><i class="fas fa-chart-line text-white"></i></div>
                        <span class="badge bg-white-20 text-white rounded-pill">Net Profit</span>
                    </div>
                    <div class="master-card-value"><?= $currencySymbol ?><?= number_format($netProfit, 2) ?></div>
                    <div class="master-card-label">Total Company Earnings</div>
                </div>
                <div class="master-card-footer bg-black-10 text-white-70">
                    <i class="fas fa-info-circle me-1"></i> Revenue after payouts & expenses
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="master-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <div class="master-card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="icon-box bg-white-20 rounded-circle"><i class="fas fa-hand-holding-usd text-white"></i></div>
                        <span class="badge bg-white-20 text-white rounded-pill">Receivable</span>
                    </div>
                    <div class="master-card-value"><?= $currencySymbol ?><?= number_format($totalRecoverFromFreelancers, 2) ?></div>
                    <div class="master-card-label">Pending Cash Collection</div>
                </div>
                <a href="?page=users" class="master-card-footer bg-black-10 text-white-70 text-decoration-none d-block">
                    View Freelancers <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="master-card" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                <div class="master-card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="icon-box bg-white-20 rounded-circle"><i class="fas fa-wallet text-white"></i></div>
                        <span class="badge bg-white-20 text-white rounded-pill">Payable</span>
                    </div>
                    <div class="master-card-value"><?= $currencySymbol ?><?= number_format($totalPayableToFreelancers, 2) ?></div>
                    <div class="master-card-label">Freelancer Wallet Balances</div>
                </div>
                <a href="?page=users" class="master-card-footer bg-black-10 text-white-70 text-decoration-none d-block">
                    User Ledger <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="master-card" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <div class="master-card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="icon-box bg-white-20 rounded-circle"><i class="fas fa-money-bill-wave text-white"></i></div>
                        <span class="badge bg-white-20 text-white rounded-pill"><?= $pendingWithdrawalCount ?> Requests</span>
                    </div>
                    <div class="master-card-value"><?= $currencySymbol ?><?= number_format($pendingWithdrawalAmount, 0) ?></div>
                    <div class="master-card-label">Pending Withdrawals</div>
                </div>
                <a href="?page=withdrawals" class="master-card-footer bg-black-10 text-white-70 text-decoration-none d-block">
                    Process Payouts <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- TASK SUMMARY MINI TILES -->
    <?php if ($dash_perms['show_task_summary']): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-6">
            <div class="tile-card border-danger">
                <div class="tile-label">Monthly Exp.</div>
                <div class="tile-value text-danger"><?= $currencySymbol . number_format($monthlyExpense) ?></div>
                <i class="fas fa-receipt tile-icon"></i>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="tile-card border-info">
                <div class="tile-label">System Users</div>
                <div class="tile-value text-info"><?= $totalUsers ?></div>
                <i class="fas fa-users tile-icon"></i>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="tile-card border-primary">
                <div class="tile-label">In Process</div>
                <div class="tile-value text-primary"><?= $processTasks ?></div>
                <i class="fas fa-spinner fa-spin tile-icon"></i>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="tile-card border-warning">
                <div class="tile-label">Unassigned</div>
                <div class="tile-value text-warning"><?= $pendingTasks ?></div>
                <i class="fas fa-pause tile-icon"></i>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="tile-card border-success">
                <div class="tile-label">Completed</div>
                <div class="tile-value text-success"><?= $completedTasks ?></div>
                <i class="fas fa-check-double tile-icon"></i>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="tile-card bg-warning-light border-warning">
                <div class="tile-label">Need Verify</div>
                <div class="tile-value text-dark fw-bold"><?= $pendingVerification ?></div>
                <i class="fas fa-user-shield tile-icon"></i>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- LEFT COLUMN: CHART & VERIFICATION -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-chart-line text-primary me-2"></i>Financial Trends</h5>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm rounded-pill px-3 border" type="button">Last 6 Months</button>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 300px;"><canvas id="masterProfitChart"></canvas></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-primary py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-white"><i class="fas fa-tasks me-2"></i>Submissions Awaiting Verification</h5>
                    <a href="?page=all_tasks&status=pending_verification" class="btn btn-sm btn-light rounded-pill px-3 fw-bold">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Task ID</th>
                                <th>Freelancer Info</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($recentSubmissions)): foreach($recentSubmissions as $sub): ?>
                            <tr>
                                <td class="ps-4 text-muted fw-bold">#<?= $sub['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary-light text-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <?= strtoupper(substr($sub['freelancer_name'], 0, 1)) ?>
                                        </div>
                                        <span class="fw-bold"><?= htmlspecialchars($sub['freelancer_name']) ?></span>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($sub['category_name']) ?></span></td>
                                <td class="text-success fw-bold"><?= $currencySymbol . $sub['task_price'] ?></td>
                                <td class="text-end pe-4"><a href="?page=edit_task&id=<?= $sub['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">Verify Now</a></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-check-circle fa-2x mb-2 d-block opacity-25"></i>Everything is verified!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: STATUS & RECHARGES -->
        <div class="col-lg-4">
            <!-- WALLET RECHARGES -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-coins me-2 text-warning"></i>Wallet Refills</h5>
                    <?php if($pendingRecharges > 0): ?><span class="badge bg-danger pulse-effect"><?= $pendingRecharges ?> New</span><?php endif; ?>
                </div>
                <div class="card-body py-4 text-center">
                    <div class="mb-3">
                        <div class="display-6 fw-bold <?= $pendingRecharges > 0 ? 'text-danger' : 'text-success' ?>"><?= $pendingRecharges ?></div>
                        <div class="text-muted small text-uppercase fw-bold">Pending Requests</div>
                    </div>
                    <a href="?page=admin_wallet_requests" class="btn btn-outline-dark w-100 rounded-pill fw-bold border-2">Manage Recharges</a>
                </div>
            </div>

            <!-- APPOINTMENTS -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-info py-3 text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-alt me-2"></i>Appointments Tracker</h5>
                </div>
                <div class="card-body text-center p-0">
                    <div class="row g-0">
                        <div class="col-4 border-end py-3 bg-light">
                            <div class="fw-bold text-dark fs-5"><?= $todayAppointments ?></div>
                            <div class="text-muted smaller text-uppercase">Today</div>
                        </div>
                        <div class="col-4 border-end py-3">
                            <div class="fw-bold text-success fs-5"><?= $completedAppointments ?></div>
                            <div class="text-muted smaller text-uppercase">Done</div>
                        </div>
                        <div class="col-4 py-3">
                            <div class="fw-bold text-danger fs-5"><?= $cancelledAppointments ?></div>
                            <div class="text-muted smaller text-uppercase">Cancel</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 py-2">
                    <a href="?page=appointments" class="btn btn-link btn-sm text-info w-100 text-decoration-none fw-bold">Open Calendar <i class="fas fa-arrow-right scale-btn ms-1"></i></a>
                </div>
            </div>

            <!-- RECRUITMENT -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-bullhorn text-danger me-2"></i>Recruitment Status</h5>
                    <span class="badge bg-success-light text-success fw-bold"><?= $activeRecruitment ?> Active</span>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3 p-3 bg-warning-light rounded-3">
                        <div>
                            <div class="text-muted small fw-bold">Pending Approval</div>
                            <div class="h5 mb-0 fw-bold"><?= $pendingRecruitment ?> Posts</div>
                        </div>
                        <i class="fas fa-hourglass-half text-warning fs-3"></i>
                    </div>
                    <a href="?page=manage_recruitment_posts" class="btn btn-dark btn-sm w-100 rounded-pill py-2 fw-bold shadow-sm">Review Applications</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- STYLES -->
<style>
    /* Premium Master Cards */
    .master-card { border-radius: 20px; color: white; display: flex; flex-direction: column; overflow: hidden; height: 100%; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid rgba(255,255,255,0.1); }
    .master-card:hover { transform: translateY(-7px); box-shadow: 0 15px 35px rgba(0,0,0,0.2) !important; }
    .master-card-body { padding: 1.8rem; flex-grow: 1; }
    .master-card-value { font-size: 2.2rem; font-weight: 800; line-height: 1.2; margin-bottom: 0.3rem; letter-spacing: -0.5px; }
    .master-card-label { font-size: 0.85rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; }
    .master-card-footer { padding: 0.8rem 1.8rem; font-size: 0.75rem; border-top: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.05); }
    
    /* Utility UI */
    .bg-white-20 { background: rgba(255,255,255,0.2); }
    .bg-black-10 { background: rgba(0,0,0,0.1); }
    .text-white-70 { color: rgba(255,255,255,0.7); }
    .icon-box { width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    
    /* Tiles */
    .tile-card { background: white; border: 1px solid #edf2f9; border-left: 4px solid; padding: 1.2rem; border-radius: 12px; position: relative; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.02); height: 100%; transition: all 0.2s; }
    .tile-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.06); transform: scale(1.02); }
    .tile-label { font-size: 0.7rem; text-transform: uppercase; font-weight: 700; color: #64748b; margin-bottom: 0.3rem; }
    .tile-value { font-size: 1.25rem; font-weight: 800; line-height: 1; }
    .tile-icon { position: absolute; right: 10px; bottom: 10px; font-size: 1.8rem; opacity: 0.05; transition: transform 0.3s; }
    .tile-card:hover .tile-icon { transform: rotate(-10deg) scale(1.2); opacity: 0.1; }
    
    /* Colors & Helpers */
    .bg-primary-light { background: #eef2ff; }
    .bg-warning-light { background: #fffbeb; }
    .bg-success-light { background: #f0fdf4; }
    .pulse-effect { animation: pulse 2s infinite; }
    @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
    .smaller { font-size: 0.65rem; }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var ctx = document.getElementById('masterProfitChart').getContext('2d');
        var gradientP = ctx.createLinearGradient(0, 0, 0, 300);
        gradientP.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
        gradientP.addColorStop(1, 'rgba(59, 130, 246, 0.0)');
        
        var gradientE = ctx.createLinearGradient(0, 0, 0, 300);
        gradientE.addColorStop(0, 'rgba(239, 68, 68, 0.2)');
        gradientE.addColorStop(1, 'rgba(239, 68, 68, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [{
                    label: 'Net Profit',
                    data: <?= json_encode($profitData) ?>,
                    backgroundColor: gradientP,
                    borderColor: '#3b82f6',
                    borderWidth: 3, fill: true, pointRadius: 5, pointBackgroundColor: '#3b82f6', pointBorderColor: '#fff', tension: 0.4
                }, {
                    label: 'Expenses',
                    data: <?= json_encode($expenseData) ?>,
                    backgroundColor: gradientE,
                    borderColor: '#ef4444',
                    borderWidth: 2, borderDash: [5, 5], fill: true, pointRadius: 2, pointBackgroundColor: '#ef4444', tension: 0.4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false, padding: 12, borderRadius: 8 } },
                scales: { 
                    y: { beginAtZero: true, grid: { borderDash: [4, 4], color: '#f1f5f9' }, ticks: { padding: 10 } }, 
                    x: { grid: { display: false }, ticks: { padding: 10 } } 
                }
            }
        });
    });
</script>