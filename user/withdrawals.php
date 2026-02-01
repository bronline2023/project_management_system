<?php
/**
 * user/withdrawals.php
 * COMPLETE & FIXED:
 * - Shows a correct balance sheet for the user.
 * - Displays admin comments for rejected requests.
 * - POST action for withdrawal request is now correctly handled by index.php via hidden form fields.
 */

require_once MODELS_PATH . 'withdrawal.php';
require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$userRole = strtolower($_SESSION['user_role'] ?? 'guest');
$message = '';

$settings = fetchOne($pdo, "SELECT currency_symbol, minimum_withdrawal_amount FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '$');
$minimumWithdrawalAmount = floatval($settings['minimum_withdrawal_amount'] ?? 500.00);

// --- Balance Calculation for BOTH Freelancer and DEO ---
$totalEarnings = 0;
if (in_array($userRole, ['deo', 'data_entry_operator'])) {
    $approvedPosts = getDeoApprovedPostCount($currentUserId);
    $earningPerPost = getEarningPerApprovedPost();
    $totalEarnings = $approvedPosts * $earningPerPost;
} elseif ($userRole === 'freelancer') {
    $totalEarnings = (float)fetchColumn($pdo, "SELECT SUM(task_price) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed' AND is_verified = 1", [$currentUserId]) ?? 0.00;
}

$totalWithdrawn = getApprovedWithdrawalAmountForUser($currentUserId);
$pendingWithdrawals = getPendingWithdrawalAmountForUser($currentUserId);
$availableBalance = $totalEarnings - $totalWithdrawn - $pendingWithdrawals;

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$userBankDetails = fetchOne($pdo, "SELECT bank_name, account_holder_name, account_number, ifsc_code FROM users WHERE id = ?", [$currentUserId]);
$withdrawalHistory = getUserWithdrawalRequests($currentUserId);
?>

<h2 class="mb-4">My Withdrawals</h2>

<?php if (!empty($message)): ?>
    <?php include VIEWS_PATH . 'components/message_box.php'; ?>
<?php endif; ?>

<div class="card shadow-sm rounded-3 mb-4">
    <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>My Balance Sheet & New Request</h5></div>
    <div class="card-body">
        <div class="row text-center mb-4">
            <div class="col-md-3 mb-2"><div class="p-3 bg-light rounded-3"><h6>Total Earnings</h6><h3><?= $currencySymbol ?><?= number_format($totalEarnings, 2) ?></h3></div></div>
            <div class="col-md-3 mb-2"><div class="p-3 bg-light rounded-3"><h6>Total Withdrawn</h6><h3 class="text-success"><?= $currencySymbol ?><?= number_format($totalWithdrawn, 2) ?></h3></div></div>
            <div class="col-md-3 mb-2"><div class="p-3 bg-light rounded-3"><h6>Pending Request</h6><h3 class="text-warning"><?= $currencySymbol ?><?= number_format($pendingWithdrawals, 2) ?></h3></div></div>
            <div class="col-md-3 mb-2"><div class="p-3 bg-info text-white rounded-3"><h6>Available Balance</h6><h3><?= $currencySymbol ?><?= number_format($availableBalance, 2) ?></h3></div></div>
        </div>
        <hr>
        <h5 class="mt-4">Submit a New Withdrawal Request</h5>
        <?php if (empty($userBankDetails['account_number'])): ?>
            <div class="alert alert-warning">Please <a href="?page=bank_details" class="fw-bold">update your bank details</a> to request a withdrawal.</div>
        <?php else: ?>
        <form action="index.php" method="POST">
            <input type="hidden" name="page" value="my_withdrawals">
            <input type="hidden" name="action" value="request_withdrawal">
            <div class="mb-3">
                <label for="amountToWithdraw" class="form-label">Amount to Withdraw (<?= $currencySymbol ?>)</label>
                <input type="number" step="0.01" class="form-control" id="amountToWithdraw" name="amount_to_withdraw" required max="<?= $availableBalance ?>" placeholder="Minimum: <?= number_format($minimumWithdrawalAmount, 2) ?>">
            </div>
            <button type="submit" class="btn btn-primary" <?= ($availableBalance < $minimumWithdrawalAmount) ? 'disabled' : '' ?>>Request Withdrawal</button>
            <?php if ($availableBalance < $minimumWithdrawalAmount): ?>
                <small class="text-danger d-block mt-2">Your available balance is less than the minimum withdrawal amount.</small>
            <?php endif; ?>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm rounded-3">
    <div class="card-header bg-dark text-white"><h5 class="mb-0"><i class="fas fa-history me-2"></i>Withdrawal History</h5></div>
    <div class="card-body">
         <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>ID</th><th>Amount</th><th>Requested</th><th>Status</th><th>Transaction No.</th><th>Processed</th></tr></thead>
                <tbody>
                    <?php if(!empty($withdrawalHistory)): foreach($withdrawalHistory as $w): ?>
                    <tr>
                        <td><?= $w['id'] ?></td>
                        <td><?= $currencySymbol ?><?= number_format($w['amount'], 2) ?></td>
                        <td><?= date('d M, Y', strtotime($w['requested_at'])) ?></td>
                        <td>
                            <span class="badge bg-<?= getWithdrawalStatusBadgeColor($w['status']) ?>"><?= ucfirst($w['status']) ?></span>
                            <?php if (!empty($w['admin_comments'])): ?>
                                <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Admin Comment: <?= htmlspecialchars($w['admin_comments']) ?>"></i>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($w['transaction_id'] ?? 'N/A') ?></td>
                        <td><?= $w['processed_at'] ? date('d M, Y', strtotime($w['processed_at'])) : 'N/A' ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center">No history found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>