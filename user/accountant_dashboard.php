<?php
/**
 * user/accountant_dashboard.php
 * Accountant Dashboard with key financial metrics.
 */

$pdo = connectDB();
$currentUserName = $_SESSION['user_name'] ?? 'Accountant';

// Fetch key financial data
$totalEarnings = (float)fetchColumn($pdo, "SELECT SUM(fee - maintenance_fee - discount) FROM work_assignments WHERE status = 'completed'");
$totalExpenses = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM expenses");
$netProfit = $totalEarnings - $totalExpenses;
$totalWithdrawals = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM withdrawals WHERE status = 'approved'");
$pendingWithdrawals = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM withdrawals WHERE status = 'pending'");

$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');
?>

<div class="container-fluid accountant-dashboard">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Accountant Dashboard</h1>
        <span class="text-muted">Welcome back, <strong><?= htmlspecialchars($currentUserName) ?></strong>!</span>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card card-revenue">
                <div class="card-body">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-content">
                        <div class="text">Total Revenue</div>
                        <div class="number"><?= $currencySymbol ?><?= number_format($totalEarnings, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card card-profit">
                <div class="card-body">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-content">
                        <div class="text">Net Profit</div>
                        <div class="number"><?= $currencySymbol ?><?= number_format($netProfit, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card card-admin-tasks">
                <div class="card-body">
                    <div class="stat-icon"><i class="fas fa-hand-holding-usd"></i></div>
                    <div class="stat-content">
                        <div class="text">Total Withdrawn</div>
                        <div class="number"><?= $currencySymbol ?><?= number_format($totalWithdrawals, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card card-users">
                <div class="card-body">
                    <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div class="stat-content">
                        <div class="text">Pending Withdrawals</div>
                        <div class="number"><?= $pendingWithdrawals ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>