<?php
/**
 * user/worker_dashboard.php
 * FINAL & COMPLETE: A completely redesigned dashboard for DEO and Freelancer roles.
 * All CSS styles have been moved to the central style.css file.
 */

// --- PHP Data Fetching Logic (No changes needed here) ---
require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php';
require_once MODELS_PATH . 'withdrawal.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'] ?? 'Worker';

$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

// Balance Calculation
$approvedPosts = getDeoApprovedPostCount($currentUserId);
$earningPerPost = getEarningPerApprovedPost();
$deoEarnings = $approvedPosts * $earningPerPost;
$freelancerEarnings = (float)fetchColumn($pdo, "SELECT SUM(task_price) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed' AND is_verified = 1", [$currentUserId]) ?? 0.00;
$totalEarnings = $deoEarnings + $freelancerEarnings;
$totalWithdrawn = getApprovedWithdrawalAmountForUser($currentUserId);
$pendingWithdrawals = getPendingWithdrawalAmountForUser($currentUserId);
$availableBalance = $totalEarnings - $totalWithdrawn - $pendingWithdrawals;

// Task Status Counts
$taskCounts = fetchAll($pdo, "SELECT status, COUNT(id) as count FROM work_assignments WHERE assigned_to_user_id = ? GROUP BY status", [$currentUserId]);
$counts = ['pending' => 0, 'in_process' => 0, 'pending_verification' => 0, 'verified_completed' => 0];
foreach ($taskCounts as $row) {
    if (isset($counts[$row['status']])) {
        $counts[$row['status']] = $row['count'];
    }
}

// Recent Activities
$recentPosts = getDeoRecruitmentPosts($currentUserId, 'all', '', 5, 0);
$recentTasks = fetchAll($pdo, "SELECT wa.id, cl.client_name, wa.status, wa.task_price FROM work_assignments wa JOIN clients cl ON wa.client_id = cl.id WHERE wa.assigned_to_user_id = ? ORDER BY wa.created_at DESC LIMIT 5", [$currentUserId]);

function getFreelancerStatusBadge($status) {
    $badges = ['pending' => 'secondary', 'in_process' => 'info', 'pending_verification' => 'warning', 'verified_completed' => 'success', 'cancelled' => 'danger'];
    $color = $badges[$status] ?? 'light';
    $textColor = in_array($color, ['info', 'warning', 'light']) ? 'dark' : 'white';
    return "<span class='badge bg-{$color} text-{$textColor}'>" . ucfirst(str_replace('_', ' ', $status)) . "</span>";
}
?>

<div class="container-fluid worker-dashboard">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Worker Dashboard</h1>
        <span class="text-muted">Welcome back, <strong><?= htmlspecialchars($currentUserName) ?></strong>!</span>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card card-balance">
                <div class="card-body">
                    <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                    <div class="stat-content">
                        <div class="text">Available Balance</div>
                        <div class="number"><?= $currencySymbol ?><?= number_format($availableBalance, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card card-worker-earnings">
                <div class="card-body">
                    <div class="stat-icon"><i class="fas fa-coins"></i></div>
                    <div class="stat-content">
                        <div class="text">Total Earnings</div>
                        <div class="number"><?= $currencySymbol ?><?= number_format($totalEarnings, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card card-processing">
                <div class="card-body">
                    <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                    <div class="stat-content">
                        <div class="text">Tasks in Process</div>
                        <div class="number"><?= $counts['in_process'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card card-worker-completed">
                <div class="card-body">
                    <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                    <div class="stat-content">
                        <div class="text">Completed Tasks</div>
                        <div class="number"><?= $counts['verified_completed'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white"><h5 class="m-0 font-weight-bold"><i class="fas fa-money-bill-wave me-2"></i>Financial Overview</h5></div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">Total Withdrawn <span class="badge bg-success rounded-pill"><?= $currencySymbol ?><?= number_format($totalWithdrawn, 2) ?></span></li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">Pending Withdrawals <span class="badge bg-warning rounded-pill"><?= $currencySymbol ?><?= number_format($pendingWithdrawals, 2) ?></span></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                 <div class="card-header bg-dark text-white"><h5 class="m-0 font-weight-bold"><i class="fas fa-history me-2"></i>Recent Activity</h5></div>
                <div class="card-body">
                    <div class="activity-feed">
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>