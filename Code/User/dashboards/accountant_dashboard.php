<?php
/**
 * user/accountant_dashboard.php
 * Enhanced Accountant Dashboard with key financial metrics and recent activity.
 */

$pdo = connectDB();
$currentUserName = $_SESSION['user_name'] ?? 'Accountant';

// Fetch key financial data based on "Collected Fee" logic
$totalEarnings = (float)fetchColumn($pdo, "SELECT 
    SUM(CASE 
        WHEN payment_status IN ('online_paid', 'cash_paid') THEN fee 
        WHEN payment_status = 'partial_paid' THEN partial_amount 
        ELSE 0 
    END) FROM work_assignments");

$totalExpenses = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM expenses");
$netProfit = $totalEarnings - $totalExpenses;
$totalWithdrawals = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM withdrawals WHERE status = 'approved'");
$pendingWithdrawals = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM withdrawals WHERE status = 'pending'");

$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

require_once MODELS_PATH . 'roles.php';
$dash_perms = getDashboardPermissionsForRole($_SESSION['user_role']);

// Recent 5 Expenses
$recentExpenses = fetchAll($pdo, "SELECT * FROM expenses ORDER BY created_at DESC LIMIT 5");

// Recent 5 Withdrawals - Fix: Use requested_at instead of created_at
$recentWithdrawals = fetchAll($pdo, "SELECT * FROM withdrawals ORDER BY requested_at DESC LIMIT 5");
?>

<div class="container-fluid accountant-dashboard py-4" style="background: #f8fafc; min-height: 100vh;">
    <?php if ($dash_perms['show_notice_board']): ?>
        <?php if (file_exists(VIEWS_PATH . 'components/notice_board.php')) include VIEWS_PATH . 'components/notice_board.php'; ?>
    <?php endif; ?>

    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3">
        <h1 class="h3 mb-0 text-gray-800 fw-bold"><i class="fas fa-calculator text-primary me-2"></i> Accountant Dashboard</h1>
        <div class="text-muted small">Welcome back, <strong class="text-dark"><?= htmlspecialchars($currentUserName) ?></strong>!</div>
    </div>

    <!-- MAIN METRIC CARDS -->
    <?php if ($dash_perms['show_financial_summary']): ?>
    <div class="row g-4 mb-4">
        <!-- Total Revenue (Collected) -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 text-white h-100" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small opacity-75 text-uppercase fw-bold">Total Collected</div>
                        <div class="h3 fw-bold mb-0"><?= $currencySymbol ?><?= number_format($totalEarnings, 2) ?></div>
                    </div>
                    <i class="fas fa-money-bill-wave fa-3x opacity-25"></i>
                </div>
            </div>
        </div>

        <!-- Total Expenses -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 text-white h-100" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small opacity-75 text-uppercase fw-bold">Total Expenses</div>
                        <div class="h3 fw-bold mb-0"><?= $currencySymbol ?><?= number_format($totalExpenses, 2) ?></div>
                    </div>
                    <i class="fas fa-receipt fa-3x opacity-25"></i>
                </div>
            </div>
        </div>

        <!-- Net Profit -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 text-white h-100" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small opacity-75 text-uppercase fw-bold">Current Net Profit</div>
                        <div class="h3 fw-bold mb-0"><?= $currencySymbol ?><?= number_format($netProfit, 2) ?></div>
                    </div>
                    <i class="fas fa-piggy-bank fa-3x opacity-25"></i>
                </div>
            </div>
        </div>

        <!-- Withdrawals -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 text-white h-100" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small opacity-75 text-uppercase fw-bold">Total Withdrawn</div>
                        <div class="h3 fw-bold mb-0"><?= $currencySymbol ?><?= number_format($totalWithdrawals, 2) ?></div>
                    </div>
                    <i class="fas fa-wallet fa-3x opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4 mt-2">
        <!-- Recent Expenses -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold m-0 text-dark"><i class="fas fa-list-ul me-2 text-primary"></i>Recent Expenses</h5>
                    <a href="?page=expenses" class="btn btn-sm btn-outline-primary rounded-pill px-3">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr><th>Title</th><th>Category</th><th>Amount</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentExpenses)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No expenses recorded yet.</td></tr>
                            <?php else: foreach($recentExpenses as $exp): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($exp['expense_type'] ?? 'N/A') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($exp['description'] ?? 'General') ?></span></td>
                                    <td class="text-danger fw-bold"><?= $currencySymbol ?><?= number_format($exp['amount'], 2) ?></td>
                                    <td class="text-muted small"><?= date('d M Y', strtotime($exp['expense_date'])) ?></td>
                                </tr>
<?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pending/Recent Withdrawals -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold m-0 text-dark"><i class="fas fa-handshake me-2 text-primary"></i>Withdrawal Status</h5>
                    <a href="?page=manage_withdrawals" class="btn btn-sm btn-outline-primary rounded-pill px-3">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr><th>User ID</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentWithdrawals)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No withdrawals found.</td></tr>
                            <?php else: foreach($recentWithdrawals as $wd): ?>
                                <tr>
                                    <td><div class="small fw-bold">#<?= $wd['user_id'] ?></div></td>
                                    <td class="fw-bold text-dark"><?= $currencySymbol ?><?= number_format($wd['amount'], 2) ?></td>
                                    <td>
                                        <?php 
                                            $statusClass = 'bg-warning text-dark';
                                            if($wd['status'] === 'approved') $statusClass = 'bg-success text-white';
                                            if($wd['status'] === 'rejected') $statusClass = 'bg-danger text-white';
                                        ?>
                                        <span class="badge <?= $statusClass ?> rounded-pill px-3"><?= ucfirst($wd['status']) ?></span>
                                    </td>
                                    <td class="text-muted small"><?= date('d M Y', strtotime($wd['requested_at'])) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Shortcuts for Accountant -->
    <div class="row mt-4 g-4 pb-5">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <h6 class="text-primary text-uppercase fw-bold mb-4 small" style="letter-spacing: 1px;"><i class="fas fa-tools me-2"></i> Quick Financial Actions</h6>
                <div class="d-flex flex-wrap gap-2">
                    <a href="?page=expenses" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm btn-action flex-grow-1"><i class="fas fa-plus-circle me-2"></i> Add Expense</a>
                    <a href="?page=manage_withdrawals" class="btn btn-secondary px-3 py-2 rounded-3 shadow-sm btn-action flex-grow-1"><i class="fas fa-check-circle me-2"></i> Manage Requests</a>
                    <a href="?page=manage_salaries" class="btn btn-dark px-3 py-2 rounded-3 shadow-sm btn-action flex-grow-1"><i class="fas fa-users-cog me-2"></i> Process Salaries</a>
                    <a href="?page=clients" class="btn btn-outline-info px-3 py-2 rounded-3 shadow-sm btn-action flex-grow-1 text-dark fw-bold"><i class="fas fa-address-book me-2"></i> Manage Clients</a>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <h6 class="text-success text-uppercase fw-bold mb-4 small" style="letter-spacing: 1px;"><i class="fas fa-chart-pie me-2"></i> Financial Analysis & Records</h6>
                <div class="d-flex flex-wrap gap-2">
                    <a href="?page=my_daily_entries" class="btn btn-success px-3 py-2 rounded-3 shadow-sm btn-action flex-grow-1"><i class="fas fa-history me-2"></i> Work Entry Records</a>
                    <a href="?page=reports" class="btn btn-outline-success px-3 py-2 rounded-3 shadow-sm btn-action flex-grow-1"><i class="fas fa-file-invoice-dollar me-2"></i> General Reports</a>
                    <a href="?page=all_tasks" class="btn btn-outline-primary px-3 py-2 rounded-3 shadow-sm btn-action flex-grow-1"><i class="fas fa-briefcase me-2"></i> View All Tasks</a>
                    <a href="?page=daily_work_entry" class="btn btn-outline-dark px-3 py-2 rounded-3 shadow-sm btn-action flex-grow-1"><i class="fas fa-keyboard me-2"></i> Tracker Entry</a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .btn-action { transition: all 0.3s ease; min-width: 140px; }
        .btn-action:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    </style>
</div>