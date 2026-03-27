<?php
/**
 * user/withdrawals.php
 * FINAL CORRECT VERSION
 * Features:
 * 1. Uses 'users.balance' (Wallet) as the main balance source.
 * 2. Subtracts 'Pending Requests' to show 'Real Available Balance'.
 * 3. Enforces Bank Details check before allowing withdrawal.
 */

require_once MODELS_PATH . 'withdrawal.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];

// Check Login
if (!$currentUserId) {
    header("Location: index.php?page=login");
    exit;
}

// Fetch Settings
$settings = fetchOne($pdo, "SELECT currency_symbol, minimum_withdrawal_amount FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');
$minimumWithdrawalAmount = floatval($settings['minimum_withdrawal_amount'] ?? 500.00);

// --- 1. FETCH USER WALLET & BANK DETAILS ---
// We get the live balance directly from the 'users' table
$user = fetchOne($pdo, "SELECT balance, bank_name, account_number, ifsc_code FROM users WHERE id = ?", [$currentUserId]);

$walletBalance = floatval($user['balance'] ?? 0); // This is the main Wallet Balance
$hasBankDetails = !empty($user['account_number']) && !empty($user['ifsc_code']);

// --- 2. CALCULATE PENDING REQUESTS ---
// Money that is requested but not yet approved/rejected
$pendingAmount = floatval(fetchColumn($pdo, "
    SELECT SUM(amount) FROM withdrawals 
    WHERE user_id = ? AND status = 'pending'
", [$currentUserId])) ?: 0.00;

// --- 3. CALCULATE AVAILABLE TO WITHDRAW ---
// Available = Wallet Balance - Already Requested (Pending)
$availableToWithdraw = $walletBalance - $pendingAmount;

// If user has negative balance (owes money), available is 0
if ($availableToWithdraw < 0) {
    $availableToWithdraw = 0;
}

$message = '';

// --- 4. HANDLE WITHDRAWAL REQUEST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $notes = trim($_POST['notes'] ?? '');

    if (!$hasBankDetails) {
        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Please add your Bank Details in settings before withdrawing.</div>';
    } elseif (!$amount || $amount < $minimumWithdrawalAmount) {
        $message = '<div class="alert alert-danger">Minimum withdrawal amount is ' . $currencySymbol . number_format($minimumWithdrawalAmount, 2) . '</div>';
    } elseif ($amount > $availableToWithdraw) {
        $message = '<div class="alert alert-danger">Insufficient Available Balance! You have ' . $currencySymbol . number_format($availableToWithdraw, 2) . ' available.</div>';
    } else {
        // Submit Request
        $stmt = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, status, requested_at, notes) VALUES (?, ?, 'pending', NOW(), ?)");
        if ($stmt->execute([$currentUserId, $amount, $notes])) {
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Withdrawal request submitted successfully!</div>';
            
            // Update local variables to reflect changes immediately in UI
            $pendingAmount += $amount;
            $availableToWithdraw -= $amount;
        } else {
            $message = '<div class="alert alert-danger">Database error. Please try again.</div>';
        }
    }
}

// --- 5. FETCH WITHDRAWAL HISTORY ---
$history = fetchAll($pdo, "SELECT * FROM withdrawals WHERE user_id = ? ORDER BY requested_at DESC", [$currentUserId]);

?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Withdrawals</h1>
    </div>

    <?= $message ?>

    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Current Wallet Balance</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($walletBalance, 2) ?></div>
                            <small class="text-muted">Total balance in account</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-wallet fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Requests</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($pendingAmount, 2) ?></div>
                            <small class="text-muted">Locked for processing</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-clock fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 bg-gradient-light">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Available to Withdraw</div>
                            <div class="h5 mb-0 font-weight-bold text-success"><?= $currencySymbol . number_format($availableToWithdraw, 2) ?></div>
                            <small class="text-muted">Wallet - Pending</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-hand-holding-usd fa-2x text-success"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Request New Withdrawal</h6>
        </div>
        <div class="card-body">
            
            <?php if (!$hasBankDetails): ?>
                <div class="alert alert-warning border-left-warning" role="alert">
                    <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Bank Details Missing!</h4>
                    <p>You need to add your bank details (Account Number & IFSC) before you can request a withdrawal.</p>
                    <hr>
                    <a href="index.php?page=bank_details" class="btn btn-warning btn-sm fw-bold">Add Bank Details Now</a>
                </div>
            <?php else: ?>
                
                <form method="POST" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Amount to Withdraw (<?= $currencySymbol ?>)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" 
                               min="<?= $minimumWithdrawalAmount ?>" max="<?= $availableToWithdraw ?>" 
                               placeholder="Min: <?= $minimumWithdrawalAmount ?>" required>
                        <small class="text-muted">Max: <?= $currencySymbol . number_format($availableToWithdraw, 2) ?></small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Notes (Optional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="E.g. UPI ID or specific instruction">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 font-weight-bold" 
                            <?= ($availableToWithdraw < $minimumWithdrawalAmount) ? 'disabled' : '' ?>>
                            <i class="fas fa-paper-plane me-1"></i> Submit
                        </button>
                    </div>
                </form>

                <?php if ($availableToWithdraw < $minimumWithdrawalAmount): ?>
                    <div class="mt-3 text-danger small">
                        <i class="fas fa-info-circle"></i> You need at least <?= $currencySymbol . number_format($minimumWithdrawalAmount, 2) ?> available balance to make a request.
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Transaction History</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Transaction ID</th>
                            <th>Processed On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($history)): ?>
                            <?php foreach ($history as $row): ?>
                                <tr>
                                    <td>#<?= $row['id'] ?></td>
                                    <td><?= date('d M Y, h:i A', strtotime($row['requested_at'])) ?></td>
                                    <td class="font-weight-bold"><?= $currencySymbol . number_format($row['amount'], 2) ?></td>
                                    <td>
                                        <?php 
                                            $badge = 'secondary';
                                            if ($row['status'] == 'approved') $badge = 'success';
                                            elseif ($row['status'] == 'rejected') $badge = 'danger';
                                            elseif ($row['status'] == 'pending') $badge = 'warning text-dark';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= ucfirst($row['status']) ?></span>
                                        <?php if (!empty($row['admin_note'])): ?>
                                            <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Admin Note: <?= htmlspecialchars($row['admin_note']) ?>"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['transaction_id'] ?? '-') ?></td>
                                    <td><?= $row['processed_at'] ? date('d M Y', strtotime($row['processed_at'])) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No withdrawal history found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>