<?php
/**
 * user/worker_dashboard.php
 * Freelancer Dashboard with Financial Stats.
 */
$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'];
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

// 1. Wallet Balance (Real-time from DB)
$balance = fetchColumn($pdo, "SELECT balance FROM users WHERE id = ?", [$currentUserId]) ?: 0.00;

// 2. Total Earnings (Only Freelancer Fee from Completed Tasks)
$totalEarnings = fetchColumn($pdo, "SELECT SUM(task_price) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed'", [$currentUserId]) ?: 0.00;

// 3. Self Collected Amount (Cash collected by Freelancer)
$selfCollected = fetchColumn($pdo, "SELECT SUM(fee) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed' AND payment_collected_by = 'self'", [$currentUserId]) ?: 0.00;

// 4. Counts
$inProcess = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'in_process'", [$currentUserId]);
$completed = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed'", [$currentUserId]);
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Freelancer Dashboard</h1>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Wallet Balance (Net)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($balance, 2) ?></div>
                            <small class="text-muted">Payable/Receivable</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-wallet fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Earnings</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($totalEarnings, 2) ?></div>
                            <small>My Profit</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-dollar-sign fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Self Collected (Cash)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($selfCollected, 2) ?></div>
                            <small>Direct form Client</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Tasks</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $inProcess ?> Active / <?= $completed ?> Done</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-clipboard-list fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">How Balance is Calculated?</h6>
                </div>
                <div class="card-body">
                    <p><strong>Scenario 1 (Company Collected):</strong> If customer pays to Company, your fee is <strong>ADDED (+)</strong> to your wallet.</p>
                    <p><strong>Scenario 2 (Self Collected):</strong> If you collect full cash from customer, you keep your fee, but you owe the company share. So, Company Share is <strong>DEDUCTED (-)</strong> from your wallet.</p>
                </div>
            </div>
        </div>
    </div>
</div>