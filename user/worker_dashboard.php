<?php
/**
 * user/worker_dashboard.php
 * FINAL UPDATED VERSION
 * Features:
 * 1. Live Wallet Balance from Database.
 * 2. Detailed "Self Collected" Card (Shows Payable to Company).
 * 3. 8 Interactive Dashboard Cards & Active Task List.
 */

// Load necessary models
require_once MODELS_PATH . 'db.php';
require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php';
require_once MODELS_PATH . 'withdrawal.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'] ?? 'Worker';

// Fetch Settings
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

// --- 1. KEY METRICS CALCULATION ---

// A. LIVE WALLET BALANCE
$availableBalance = fetchColumn($pdo, "SELECT balance FROM users WHERE id = ?", [$currentUserId]) ?: 0.00;

// B. TOTAL EARNINGS
$approvedPosts = getDeoApprovedPostCount($currentUserId);
$earningPerPost = getEarningPerApprovedPost();
$deoEarnings = $approvedPosts * $earningPerPost;
$freelancerEarnings = (float)fetchColumn($pdo, "SELECT SUM(task_price) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed'", [$currentUserId]) ?: 0.00;
$totalEarnings = $deoEarnings + $freelancerEarnings;

// C. WITHDRAWALS
$totalWithdrawn = getApprovedWithdrawalAmountForUser($currentUserId);
$pendingWithdrawals = getPendingWithdrawalAmountForUser($currentUserId);

// D. CASH COLLECTED & PAYABLE TO COMPANY (NEW LOGIC)
// Total Cash Collected by Freelancer
$selfCollected = fetchColumn($pdo, "SELECT SUM(fee) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed' AND payment_collected_by = 'self'", [$currentUserId]) ?: 0.00;

// Amount Payable to Company (Company Share from Self Collected tasks)
// Logic: (Total Fee - Freelancer Share) for self-collected tasks
$owedToCompany = fetchColumn($pdo, "
    SELECT SUM(fee - task_price) 
    FROM work_assignments 
    WHERE assigned_to_user_id = ? 
    AND status = 'verified_completed' 
    AND payment_collected_by = 'self'
", [$currentUserId]) ?: 0.00;

// E. MONTHLY EARNINGS
$currentMonth = date('m');
$currentYear = date('Y');
$monthlyEarnings = fetchColumn($pdo, "SELECT SUM(task_price) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed' AND MONTH(completion_date) = ? AND YEAR(completion_date) = ?", [$currentUserId, $currentMonth, $currentYear]) ?: 0.00;

// F. TASK COUNTS
$inProcessCount = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE assigned_to_user_id = ? AND status IN ('in_process', 'pending')", [$currentUserId]);
$completedCount = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed'", [$currentUserId]);

// --- 2. FETCH ACTIVE TASKS ---
$activeTasks = fetchAll($pdo, "
    SELECT wa.id, wa.deadline, wa.fee, wa.task_price, wa.status, 
           cat.name as category_name, cl.client_name 
    FROM work_assignments wa 
    LEFT JOIN categories cat ON wa.category_id = cat.id
    LEFT JOIN clients cl ON wa.client_id = cl.id 
    WHERE wa.assigned_to_user_id = ? AND wa.status IN ('in_process', 'pending') 
    ORDER BY wa.deadline ASC LIMIT 10
", [$currentUserId]);

?>

<style>
    .dashboard-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-radius: 12px;
        border: none;
        text-decoration: none !important;
        display: block;
        color: inherit;
        overflow: hidden;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
        color: inherit;
    }
    .text-xs { font-size: 0.85rem; }
    .card-body { position: relative; z-index: 1; }
    .card-icon-bg {
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 5rem;
        opacity: 0.1;
        z-index: 0;
        transform: rotate(-15deg);
    }
</style>

<div class="container-fluid worker-dashboard">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Freelancer Dashboard</h1>
        <span class="text-secondary">Welcome, <strong><?= htmlspecialchars($currentUserName) ?></strong>!</span>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="index.php?page=withdrawals" class="card border-left-primary shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Wallet Balance (Net)</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($availableBalance, 2) ?></div>
                            <small class="text-success"><i class="fas fa-arrow-right"></i> Click to Withdraw</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-wallet fa-2x text-gray-300"></i></div>
                    </div>
                    <i class="fas fa-wallet card-icon-bg"></i>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="index.php?page=my_freelancer_tasks" class="card border-left-success shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Earnings</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($totalEarnings, 2) ?></div>
                            <small class="text-muted">Lifetime Income</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-sack-dollar fa-2x text-gray-300"></i></div>
                    </div>
                    <i class="fas fa-sack-dollar card-icon-bg"></i>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="index.php?page=my_freelancer_tasks" class="card border-left-warning shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Self Collected (Cash)</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($selfCollected, 2) ?></div>
                            
                            <div class="mt-2 pt-2 border-top border-light">
                                <small class="text-danger fw-bold" style="font-size: 0.75rem;">
                                    <i class="fas fa-arrow-down"></i> Payable to Company: <?= $currencySymbol . number_format($owedToCompany, 2) ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-auto"><i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i></div>
                    </div>
                    <i class="fas fa-hand-holding-usd card-icon-bg"></i>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="index.php?page=my_freelancer_tasks" class="card border-left-info shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Monthly Earnings</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($monthlyEarnings, 2) ?></div>
                            <small class="text-muted"><?= date('F Y') ?></small>
                        </div>
                        <div class="col-auto"><i class="fas fa-calendar-check fa-2x text-gray-300"></i></div>
                    </div>
                    <i class="fas fa-calendar-check card-icon-bg"></i>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="index.php?page=my_freelancer_tasks" class="card border-left-primary shadow h-100 py-2 dashboard-card bg-light">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Active Tasks</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $inProcessCount ?></div>
                            <small class="text-muted">In Process / Pending</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-briefcase fa-2x text-gray-400"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="index.php?page=my_freelancer_tasks" class="card border-left-success shadow h-100 py-2 dashboard-card bg-light">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed Tasks</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $completedCount ?></div>
                            <small class="text-muted">Successfully Verified</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-400"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="index.php?page=withdrawals" class="card border-left-secondary shadow h-100 py-2 dashboard-card bg-light">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Total Withdrawn</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($totalWithdrawn, 2) ?></div>
                            <small class="text-muted">Paid to Bank</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-university fa-2x text-gray-400"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="index.php?page=withdrawals" class="card border-left-warning shadow h-100 py-2 dashboard-card bg-light">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Withdrawal</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($pendingWithdrawals, 2) ?></div>
                            <small class="text-muted">Processing...</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-clock fa-2x text-gray-400"></i></div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card shadow-sm rounded-3">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="m-0 font-weight-bold"><i class="fas fa-list-check me-2"></i>My Active Tasks</h5>
                    <a href="index.php?page=my_freelancer_tasks" class="btn btn-sm btn-light text-dark fw-bold">View All Tasks</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Service Category</th>
                                    <th>Client Name</th>
                                    <th>Deadline</th>
                                    <th>My Fee</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($activeTasks)): ?>
                                    <?php foreach ($activeTasks as $task): ?>
                                    <tr>
                                        <td><strong>#<?= $task['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($task['category_name']) ?></td>
                                        <td><?= htmlspecialchars($task['client_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php 
                                            $dueDate = strtotime($task['deadline']);
                                            $isOverdue = $dueDate < time();
                                            ?>
                                            <span class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                                <?= date('d M, Y', $dueDate) ?>
                                            </span>
                                        </td>
                                        <td class="text-success fw-bold"><?= $currencySymbol . number_format($task['task_price'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-info text-dark">In Process</span>
                                        </td>
                                        <td>
                                            <a href="index.php?page=update_freelancer_task&id=<?= $task['id'] ?>" class="btn btn-primary btn-sm px-3">
                                                <i class="fas fa-briefcase"></i> Work
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fas fa-clipboard-check fa-2x mb-2"></i><br>
                                            No active tasks found. Check 'Active Tasks' or ask Admin for new work.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>