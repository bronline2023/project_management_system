<?php
/**
 * user/freelancer_dashboard.php
 * Dashboard for Freelancer users with detailed task status cards and a functional recent tasks table.
 */
$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'] ?? 'Freelancer';

// Fetch settings
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

// --- Task Status Counts ---
$taskCounts = fetchAll($pdo, 
    "SELECT status, COUNT(id) as count FROM work_assignments WHERE assigned_to_user_id = ? GROUP BY status", 
    [$currentUserId]
);
$counts = [
    'pending' => 0, 'in_process' => 0, 'pending_verification' => 0, 'verified_completed' => 0
];
foreach ($taskCounts as $row) {
    if (isset($counts[$row['status']])) {
        $counts[$row['status']] = $row['count'];
    }
}

// --- Corrected Balance Calculation ---
// FIXED: The query was updated to correctly match the status set by the admin action.
$totalEarnings = (float)fetchColumn($pdo, "SELECT SUM(task_price) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed' AND is_verified = 1", [$currentUserId]) ?? 0.00;
$totalWithdrawn = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM withdrawals WHERE user_id = ? AND status = 'approved'", [$currentUserId]) ?? 0.00;
$pendingWithdrawals = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM withdrawals WHERE user_id = ? AND status = 'pending'", [$currentUserId]) ?? 0.00;
$availableBalance = $totalEarnings - $totalWithdrawn - $pendingWithdrawals;

// Fetch Recent Tasks
$recentTasks = fetchAll($pdo, "SELECT wa.id, cl.client_name, wa.deadline, wa.status, wa.task_price FROM work_assignments wa JOIN clients cl ON wa.client_id = cl.id WHERE wa.assigned_to_user_id = ? ORDER BY wa.created_at DESC LIMIT 5", [$currentUserId]);

function getFreelancerStatusBadge($status) {
    switch ($status) {
        case 'pending': return '<span class="badge bg-secondary">Pending</span>';
        case 'in_process': return '<span class="badge bg-info text-dark">In Process</span>';
        case 'pending_verification': return '<span class="badge bg-warning text-dark">Awaiting Verification</span>';
        case 'verified_completed': return '<span class="badge bg-success">Completed</span>';
        case 'cancelled': return '<span class="badge bg-danger">Cancelled</span>';
        default: return '<span class="badge bg-light text-dark">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
    }
}
?>

<h2 class="mb-4">Freelancer Dashboard</h2>

<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">New / Pending</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?= $counts['pending'] ?></div></div><div class="col-auto"><i class="fas fa-inbox fa-2x text-gray-300"></i></div></div></div></div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">In Process</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?= $counts['in_process'] ?></div></div><div class="col-auto"><i class="fas fa-spinner fa-2x text-gray-300"></i></div></div></div></div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Awaiting Verification</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?= $counts['pending_verification'] ?></div></div><div class="col-auto"><i class="fas fa-user-check fa-2x text-gray-300"></i></div></div></div></div>
    </div>
     <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Available Balance</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?= $currencySymbol ?><?= number_format($availableBalance, 2) ?></div></div><div class="col-auto"><i class="fas fa-wallet fa-2x text-gray-300"></i></div></div></div></div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Tasks</h5>
        <a href="<?= BASE_URL ?>?page=my_freelancer_tasks" class="btn btn-outline-light btn-sm">View All My Tasks</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr><th>ID</th><th>Client</th><th>Deadline</th><th>Price</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php if(!empty($recentTasks)): foreach($recentTasks as $task): ?>
                    <tr>
                        <td>#<?= $task['id'] ?></td>
                        <td><?= htmlspecialchars($task['client_name']) ?></td>
                        <td><?= date('d M, Y', strtotime($task['deadline'])) ?></td>
                        <td><span class="badge bg-success"><?= $currencySymbol ?><?= number_format($task['task_price'], 2) ?></span></td>
                        <td><?= getFreelancerStatusBadge($task['status']) ?></td>
                        <td><a href="<?= BASE_URL ?>?page=update_freelancer_task&id=<?= $task['id'] ?>" class="btn btn-sm btn-primary">View/Update</a></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center">No tasks assigned yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.border-left-primary { border-left: .25rem solid #4e73df!important; }
.border-left-success { border-left: .25rem solid #1cc88a!important; }
.border-left-info { border-left: .25rem solid #36b9cc!important; }
.border-left-warning { border-left: .25rem solid #f6c23e!important; }
.text-xs { font-size: .8rem; }
.font-weight-bold { font-weight: 700!important; }
.text-gray-800 { color: #5a5c69!important; }
.text-gray-300 { color: #dddfeb!important; }
</style>