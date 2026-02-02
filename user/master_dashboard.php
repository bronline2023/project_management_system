<?php
/**
 * user/master_dashboard.php
 * FINAL ADMIN DASHBOARD:
 * - Comprehensive Freelancer Overview.
 * - Financial Stats (Company Profit vs Freelancer Payouts).
 * - Recent Activity & Withdrawal Alerts.
 */

require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'withdrawal.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

// --- 1. KEY COUNTS ---

// Total Freelancers
$freelancerCount = fetchColumn($pdo, "
    SELECT COUNT(*) FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE r.role_name IN ('Freelancer', 'Data Entry Operator', 'DEO')
");

// Tasks waiting for Admin Verification (Submissions)
$pendingVerificationCount = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE status = 'pending_verification'");

// Active Tasks (In Process)
$activeTasksCount = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE status = 'in_process'");

// Pending Withdrawals
$pendingWithdrawalCount = fetchColumn($pdo, "SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'");


// --- 2. FINANCIAL STATS ---

// Total Company Profit (Fee - Task Price) from Completed Tasks
$companyProfit = fetchColumn($pdo, "
    SELECT SUM(fee - task_price) 
    FROM work_assignments 
    WHERE status = 'verified_completed'
") ?: 0.00;

// Total Wallet Balance of All Freelancers (Liability/Pending Payouts)
$totalFreelancerLiability = fetchColumn($pdo, "
    SELECT SUM(u.balance) 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE r.role_name IN ('Freelancer', 'Data Entry Operator', 'DEO')
") ?: 0.00;


// --- 3. RECENT WORK SUBMISSIONS (For "Work Updated" Card) ---
$recentSubmissions = fetchAll($pdo, "
    SELECT wa.id, wa.updated_at, u.name as freelancer_name, cl.client_name, wa.task_price
    FROM work_assignments wa 
    JOIN users u ON wa.assigned_to_user_id = u.id 
    LEFT JOIN clients cl ON wa.client_id = cl.id
    WHERE wa.status = 'pending_verification' 
    ORDER BY wa.updated_at DESC LIMIT 5
");

// --- 4. RECENT WITHDRAWAL REQUESTS ---
$recentWithdrawals = fetchAll($pdo, "
    SELECT w.id, w.amount, w.requested_at, u.name as freelancer_name 
    FROM withdrawals w 
    JOIN users u ON w.user_id = u.id 
    WHERE w.status = 'pending' 
    ORDER BY w.requested_at DESC LIMIT 5
");

?>

<style>
    .admin-card {
        transition: transform 0.2s;
        border-radius: 10px;
        text-decoration: none !important;
        display: block;
        color: inherit;
    }
    .admin-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
        color: inherit;
    }
    .text-gray-800 { color: #5a5c69 !important; }
</style>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Master Admin Dashboard</h1>
    </div>

    <div class="row">
        
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="index.php?page=users" class="card border-left-primary shadow h-100 py-2 admin-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Freelancers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $freelancerCount ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="index.php?page=all_tasks" class="card border-left-warning shadow h-100 py-2 admin-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Verification</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $pendingVerificationCount ?></div>
                            <small>Tasks submitted by Freelancers</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-file-signature fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="index.php?page=manage_withdrawals" class="card border-left-danger shadow h-100 py-2 admin-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Withdrawal Requests</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $pendingWithdrawalCount ?></div>
                            <small>Action Required</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="index.php?page=all_tasks" class="card border-left-info shadow h-100 py-2 admin-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Tasks In Process</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $activeTasksCount ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-spinner fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Company Profit</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($companyProfit, 2) ?></div>
                            <small class="text-muted">Net Earnings from Freelancer Tasks</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-chart-line fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Total Payable (Freelancer Wallets)</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol . number_format($totalFreelancerLiability, 2) ?></div>
                            <small class="text-muted">Amount currently in freelancer wallets</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-wallet fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-dark text-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-check-circle me-2"></i>Recent Work Submissions</h6>
                    <a href="index.php?page=all_tasks" class="btn btn-sm btn-light">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Task</th>
                                    <th>Freelancer</th>
                                    <th>Client</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentSubmissions)): ?>
                                    <?php foreach ($recentSubmissions as $sub): ?>
                                    <tr>
                                        <td>#<?= $sub['id'] ?></td>
                                        <td><?= htmlspecialchars($sub['freelancer_name']) ?></td>
                                        <td><?= htmlspecialchars($sub['client_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <a href="index.php?page=edit_task&id=<?= $sub['id'] ?>" class="btn btn-sm btn-primary">Verify</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-3">No pending submissions.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-danger text-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-money-bill-wave me-2"></i>New Withdrawal Requests</h6>
                    <a href="index.php?page=manage_withdrawals" class="btn btn-sm btn-light">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Freelancer</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentWithdrawals)): ?>
                                    <?php foreach ($recentWithdrawals as $with): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($with['freelancer_name']) ?></td>
                                        <td class="text-danger fw-bold"><?= $currencySymbol . number_format($with['amount'], 2) ?></td>
                                        <td><?= date('d M', strtotime($with['requested_at'])) ?></td>
                                        <td>
                                            <a href="index.php?page=manage_withdrawals" class="btn btn-sm btn-danger">Process</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-3">No pending requests.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>