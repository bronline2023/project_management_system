<?php
/**
 * user/worker_dashboard.php
 * FINAL FIX:
 * 1. Admin Tasks (assigned_by_user_id = 1) are FORCED to top.
 * 2. Task Generation Date is displayed.
 * 3. 🚀 WALLET BALANCE FIX: Now dynamically reflects accurate Database balance including Poster Deductions.
 */

require_once MODELS_PATH . 'db.php';
require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php';
require_once MODELS_PATH . 'withdrawal.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'] ?? 'Worker';

// Fetch Settings
$settings = fetchOne($pdo, "SELECT currency_symbol, earning_per_approved_post FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');
$ratePerPost = htmlspecialchars($settings['earning_per_approved_post'] ?? '0');

// --- 1. KEY METRICS CALCULATION ---
// Fetch accurate wallet balance from database (reflecting poster deductions)
$availableBalance = fetchColumn($pdo, "SELECT balance FROM users WHERE id = ?", [$currentUserId]) ?: 0.00;

$approvedPosts = getDeoApprovedPostCount($currentUserId);
$earningPerPost = getEarningPerApprovedPost();
$deoEarnings = $approvedPosts * $earningPerPost;
$freelancerEarnings = (float)fetchColumn($pdo, "SELECT SUM(task_price) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed'", [$currentUserId]) ?: 0.00;
$totalEarnings = $deoEarnings + $freelancerEarnings;

$totalWithdrawn = getApprovedWithdrawalAmountForUser($currentUserId);
$pendingWithdrawals = getPendingWithdrawalAmountForUser($currentUserId);
$totalDebitedWithdrawals = $totalWithdrawn + $pendingWithdrawals;

$selfCollected = fetchColumn($pdo, "SELECT SUM(fee) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed' AND payment_collected_by = 'self'", [$currentUserId]) ?: 0.00;

// 🚀 Major Fix: Show direct Database 'Balance' instead of old calculations (Earnings - Withdrawals) 🚀
$realWalletBalance = $availableBalance;

if ($realWalletBalance < 0) {
    $owedToCompany = abs($realWalletBalance);
} else {
    $owedToCompany = 0.00;
}

$inProcessCount = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'in_process'", [$currentUserId]);
$returnedCount = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'returned'", [$currentUserId]);
$completedCount = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed'", [$currentUserId]);


// --- 2. ACTIVE TASKS WITH STRICT SORTING ---

// Parameters
$search = $_GET['search'] ?? '';
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 10; 
$offset = ($page - 1) * $limit;

// Base Query
$sqlBase = "
    FROM work_assignments wa 
    LEFT JOIN categories cat ON wa.category_id = cat.id
    LEFT JOIN clients cl ON wa.client_id = cl.id 
    LEFT JOIN customers cust ON wa.customer_id = cust.id 
    WHERE wa.assigned_to_user_id = ? 
    AND wa.status IN ('in_process', 'pending', 'returned')
";
$params = [$currentUserId];

// Apply Search
if (!empty($search)) {
    $sqlBase .= " AND (wa.id LIKE ? OR cl.client_name LIKE ? OR cust.customer_name LIKE ? OR cat.name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Count Total
$totalTasks = fetchColumn($pdo, "SELECT COUNT(*) $sqlBase", $params);
$totalPages = ceil($totalTasks / $limit);

// Fetch Data with STRICT ORDERING
$sqlFinal = "SELECT wa.id, wa.deadline, wa.created_at, wa.fee, wa.task_price, wa.status, wa.assigned_by_user_id,
           cat.name as category_name, cl.client_name, cust.customer_name 
           $sqlBase 
           ORDER BY 
               CASE WHEN wa.assigned_by_user_id = 1 THEN 1 ELSE 0 END DESC,
               FIELD(wa.status, 'returned', 'in_process', 'pending'), 
               wa.deadline ASC 
           LIMIT $limit OFFSET $offset";

$activeTasks = fetchAll($pdo, $sqlFinal, $params);
require_once MODELS_PATH . 'roles.php';
$dash_perms = getDashboardPermissionsForRole($_SESSION['user_role']);
?>

<style>
    .dashboard-card { transition: transform 0.3s ease, box-shadow 0.3s ease; border-radius: 12px; border: none; overflow: hidden; display: block; text-decoration: none; color: inherit; }
    .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important; color: inherit; }
    .text-xs { font-size: 0.85rem; }
    .bg-gradient-red { background: linear-gradient(45deg, #ff6b6b, #ee5253); color: white; }
    
    /* Highlight Admin Task */
    .row-admin-task { background-color: #f0f8ff !important; border-left: 4px solid #0d6efd !important; }
    .badge-admin { background-color: #0d6efd; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; margin-left: 5px; }
</style>

<div class="container-fluid worker-dashboard py-4">
    <?php if ($dash_perms['show_notice_board']) include VIEWS_PATH . 'components/notice_board.php'; ?>

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Freelancer Dashboard</h1>
            <span class="text-secondary">Welcome, <strong><?= htmlspecialchars($currentUserName) ?></strong>!</span>
        </div>
        <a href="index.php?page=my_reports" class="btn btn-sm btn-info shadow-sm me-2">
            <i class="fas fa-chart-bar fa-sm text-white-50 me-2"></i> View My Reports
        </a>
    </div>

    <div class="row">
        <?php if ($dash_perms['show_wallet_card']): ?>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Wallet Balance</div>
                            <div class="h4 mb-0 font-weight-bold <?= $realWalletBalance < 0 ? 'text-danger' : 'text-gray-800' ?>">
                                <?= $currencySymbol . number_format($realWalletBalance, 2) ?>
                            </div>
                            <small class="text-muted">Live Calculated Balance</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-wallet fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Payable to Company</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($owedToCompany, 2) ?></div>
                            <small class="text-danger">Outstanding Debt</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($dash_perms['show_points_card']): ?>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Earnings</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($totalEarnings, 2) ?></div>
                            <small class="text-muted">Tasks + Recruitment</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-sack-dollar fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Rate Per Post</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . $ratePerPost ?></div>
                            <small class="text-muted">For Recruitment Posts</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-tags fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($dash_perms['show_task_summary']): ?>
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="index.php?page=my_freelancer_tasks&status=returned" class="card border-left-danger shadow h-100 py-2 dashboard-card bg-gradient-red">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Returned Tasks</div>
                            <div class="h3 mb-0 font-weight-bold text-white"><?= $returnedCount ?></div>
                            <small class="text-white-50">Action Required!</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-undo fa-2x text-white-50"></i></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="index.php?page=my_freelancer_tasks" class="card border-left-primary shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Active Tasks</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $inProcessCount ?></div>
                            <small class="text-muted">In Process</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-briefcase fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed Tasks</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $completedCount ?></div>
                            <small class="text-muted">Verified</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="index.php?page=withdrawals" class="card border-left-warning shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Withdrawn</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($totalWithdrawn, 2) ?></div>
                            <small class="text-muted"><?= $currencySymbol . number_format($pendingWithdrawals, 2) ?> Pending</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-university fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card shadow-sm rounded-3">
                <div class="card-header bg-dark text-white py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                        <h5 class="m-0 font-weight-bold mb-2 mb-md-0"><i class="fas fa-list-check me-2"></i>My Active Tasks</h5>
                        
                        <form method="GET" action="index.php" class="d-flex">
                            <input type="hidden" name="page" value="worker_dashboard">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search ID, Client..." value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-light btn-sm" type="submit"><i class="fas fa-search"></i></button>
                                <?php if($search): ?>
                                    <a href="index.php?page=worker_dashboard" class="btn btn-danger btn-sm"><i class="fas fa-times"></i></a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Task Date</th>
                                    <th>Category</th>
                                    <th>Customer / Client</th>
                                    <th>Deadline</th>
                                    <th>My Fee</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($activeTasks)): ?>
                                    <?php foreach ($activeTasks as $task): 
                                        $isAdminTask = ($task['assigned_by_user_id'] == 1);
                                        $rowClass = '';
                                        if ($isAdminTask) {
                                            $rowClass = 'row-admin-task';
                                        } elseif ($task['status'] == 'returned') {
                                            $rowClass = 'table-danger';
                                        }
                                    ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td>
                                            <strong>#<?= $task['id'] ?></strong>
                                            <?php if($isAdminTask): ?>
                                                <br><span class="badge-admin">ADMIN TASK</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td>
                                            <span class="fw-bold text-secondary" style="font-size: 0.85rem;">
                                                <?= date('d M, Y', strtotime($task['created_at'])) ?>
                                            </span>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                <?= date('h:i A', strtotime($task['created_at'])) ?>
                                            </div>
                                        </td>

                                        <td><?= htmlspecialchars($task['category_name']) ?></td>
                                        <td>
                                            <?php 
                                            $customerName = !empty($task['customer_name']) ? $task['customer_name'] : $task['client_name'];
                                            $clientLabel = !empty($task['customer_name']) ? $task['client_name'] : 'Direct Client';
                                            ?>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($customerName) ?></span>
                                                <small class="text-muted"><i class="fas fa-user-tie me-1"></i><?= htmlspecialchars($clientLabel) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $dueDate = strtotime($task['deadline']);
                                            $isOverdue = $dueDate < time() && $task['status'] != 'verified_completed';
                                            ?>
                                            <span class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                                <?= date('d M, Y', $dueDate) ?>
                                            </span>
                                        </td>
                                        <td class="text-success fw-bold"><?= $currencySymbol . number_format($task['task_price'], 2) ?></td>
                                        <td>
                                            <?php if ($task['status'] == 'returned'): ?>
                                                <span class="badge bg-danger">Returned</span>
                                            <?php elseif ($task['status'] == 'in_process'): ?>
                                                <span class="badge bg-info text-dark">In Process</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?= ucfirst($task['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="index.php?page=update_freelancer_task&id=<?= $task['id'] ?>" class="btn btn-<?= ($task['status'] == 'returned') ? 'danger' : 'primary' ?> btn-sm px-3">
                                                <?= ($task['status'] == 'returned') ? 'Fix & Resubmit' : 'Work' ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">No active tasks found matching your search.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="p-3 d-flex justify-content-end">
                        <nav aria-label="Dashboard Pagination">
                            <ul class="pagination pagination-sm m-0">
                                <?php 
                                $searchParam = $search ? "&search=" . urlencode($search) : "";
                                ?>
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="index.php?page=worker_dashboard&p=<?= $page - 1 ?><?= $searchParam ?>">&laquo;</a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                        <a class="page-link" href="index.php?page=worker_dashboard&p=<?= $i ?><?= $searchParam ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="index.php?page=worker_dashboard&p=<?= $page + 1 ?><?= $searchParam ?>">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>