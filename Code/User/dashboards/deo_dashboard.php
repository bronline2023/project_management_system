<?php
/**
 * user/deo_dashboard.php
 * FINAL & COMPLETE:
 * - The "Recent Submitted Posts" table now includes an "Admin Comments" column.
 * - The "View/Edit" button is now correctly disabled for posts with a 'rejected' status.
 */

require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php';
require_once MODELS_PATH . 'withdrawal.php';

$pdo = connectDB();
$current_user_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['user_name'] ?? 'User';

$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

// --- Complete balance calculation ---
$approvedPosts = getDeoApprovedPostCount($current_user_id);
$earningPerPost = getEarningPerApprovedPost();
$totalEarnings = $approvedPosts * $earningPerPost;
$totalWithdrawn = getApprovedWithdrawalAmountForUser($current_user_id);
$pendingWithdrawals = getPendingWithdrawalAmountForUser($current_user_id);
$availableBalance = $totalEarnings - $totalWithdrawn - $pendingWithdrawals;

// Fetch recent posts for the table below, including admin_comments
$recentPosts = getDeoRecruitmentPosts($current_user_id, 'all', '', 5, 0);
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">DEO Dashboard</h1>
        <span class="text-muted">Welcome back, <?= htmlspecialchars($current_user_name) ?>!</span>
    </div>

    <div class="card shadow-lg mb-4">
        <div class="card-header card-header-custom bg-primary text-white">
            <h5 class="m-0 font-weight-bold"><i class="fas fa-wallet me-2"></i>My Earnings Overview</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4"><div class="card h-100 bg-light border-0"><div class="card-body text-center"><i class="fas fa-coins fa-3x text-primary mb-2"></i><h6 class="text-uppercase text-muted">Total Earnings</h6><p class="display-5 font-weight-bold text-primary"><?= $currencySymbol ?><?= number_format($totalEarnings, 2) ?></p></div></div></div>
                <div class="col-md-6 col-lg-3 mb-4"><div class="card h-100 bg-light border-0"><div class="card-body text-center"><i class="fas fa-check-double fa-3x text-success mb-2"></i><h6 class="text-uppercase text-muted">Total Withdrawn</h6><p class="display-5 font-weight-bold text-success"><?= $currencySymbol ?><?= number_format($totalWithdrawn, 2) ?></p></div></div></div>
                <div class="col-md-6 col-lg-3 mb-4"><div class="card h-100 bg-light border-0"><div class="card-body text-center"><i class="fas fa-clock fa-3x text-warning mb-2"></i><h6 class="text-uppercase text-muted">Pending Withdrawals</h6><p class="display-5 font-weight-bold text-warning"><?= $currencySymbol ?><?= number_format($pendingWithdrawals, 2) ?></p></div></div></div>
                <div class="col-md-6 col-lg-3 mb-4"><div class="card h-100 bg-info text-white"><div class="card-body text-center"><i class="fas fa-hand-holding-usd fa-3x mb-2"></i><h6 class="text-uppercase">Available Balance</h6><p class="display-5 font-weight-bold"><?= $currencySymbol ?><?= number_format($availableBalance, 2) ?></p></div></div></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm rounded-3">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Submitted Posts</h5>
            <a href="<?= BASE_URL ?>?page=my_recruitment_posts" class="btn btn-outline-light btn-sm">View All My Posts</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Job Title</th>
                            <th>Status</th>
                            <th>Admin Comments</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($recentPosts)): ?>
                            <?php foreach ($recentPosts as $post): ?>
                            <tr>
                                <td><?= htmlspecialchars($post['id']) ?></td>
                                <td><?= htmlspecialchars($post['job_title']) ?></td>
                                <td><span class="badge bg-<?= getApprovalStatusBadgeColor($post['approval_status']) ?>"><?= ucfirst(str_replace('_', ' ', $post['approval_status'])) ?></span></td>
                                <td>
                                    <?php if (!empty($post['admin_comments'])): ?>
                                        <span class="text-info" data-bs-toggle="tooltip" title="<?= htmlspecialchars($post['admin_comments']) ?>">
                                            <i class="fas fa-comment-dots"></i> View
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($post['approval_status'] === 'rejected'): ?>
                                        <a href="#" class="btn btn-sm btn-outline-danger rounded-pill px-3 disabled" aria-disabled="true">
                                            <i class="fas fa-times-circle me-1"></i> Rejected
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>?page=add_recruitment_post&id=<?= $post['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">View/Edit</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No recent posts found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .font-weight-bold { font-weight: 700 !important; } .text-gray-800 { color: #5a5c69 !important; } .display-5 { font-size: 2.5rem; } .card-header-custom { border-bottom: none; border-radius: 1rem 1rem 0 0 !important; }
</style>

<script>
// This script enables tooltips, which are used to show admin comments.
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});
</script>