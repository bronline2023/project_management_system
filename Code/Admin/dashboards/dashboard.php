<?php
/**
 * admin/dashboards/dashboard.php
 * ROBUST & HIGH-CONTRAST ADMIN DASHBOARD
 */

// Global context safety
if (!isset($pdo)) {
    $pdo = connectDB();
}

$user_role = $_SESSION['user_role'] ?? 'admin';
$user_name = $_SESSION['user_name'] ?? 'Admin';

// Fetch Core Metrics with fallback to avoid blank page on DB error
$metrics = [
    'total_appointments' => 0,
    'today_appointments' => 0,
    'completed_appointments' => 0,
    'cancelled_appointments' => 0,
    'total_tasks' => 0,
    'pending_tasks' => 0,
    'total_users' => 0,
    'revenue' => 0,
    'expenses' => 0
];

try {
    $metrics['total_appointments'] = (int)fetchColumn($pdo, "SELECT COUNT(*) FROM appointments") ?: 0;
    $metrics['today_appointments'] = (int)fetchColumn($pdo, "SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()") ?: 0;
    $metrics['completed_appointments'] = (int)fetchColumn($pdo, "SELECT COUNT(*) FROM appointments WHERE status = 'completed'") ?: 0;
    $metrics['cancelled_appointments'] = (int)fetchColumn($pdo, "SELECT COUNT(*) FROM appointments WHERE status = 'cancelled'") ?: 0;
    $metrics['total_tasks'] = (int)fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments") ?: 0;
    $metrics['pending_tasks'] = (int)fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE status = 'pending'") ?: 0;
    $metrics['total_users'] = (int)fetchColumn($pdo, "SELECT COUNT(*) FROM users") ?: 0;
    $metrics['revenue'] = (float)fetchColumn($pdo, "SELECT SUM(fee) FROM work_assignments WHERE status = 'completed'") ?: 0;
    $metrics['expenses'] = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM expenses") ?: 0;
} catch (Exception $e) { /* silent fail, show 0 */ }

// Recent Tasks
$recentTasksList = [];
try {
    $recentTasksList = fetchAll($pdo, "SELECT wa.id, wa.work_description, u.name as assigned_to, cl.client_name, wa.created_at, wa.status FROM work_assignments wa LEFT JOIN users u ON wa.assigned_to_user_id = u.id LEFT JOIN clients cl ON wa.client_id = cl.id ORDER BY wa.id DESC LIMIT 5");
} catch (Exception $e) { /* fallback empty list */ }

require_once MODELS_PATH . 'roles.php';
$dash_perms = [];
try {
    $dash_perms = getDashboardPermissionsForRole($user_role);
} catch (Exception $e) { /* admin fallback */ }

?>
<div class="p-4" style="background: #f8fafc; min-height: 100vh;">
    <!-- Notice Board -->
    <?php if ($dash_perms['show_notice_board'] ?? true): ?>
        <?php if (file_exists(VIEWS_PATH . 'components/notice_board.php')) include VIEWS_PATH . 'components/notice_board.php'; ?>
    <?php endif; ?>

    <div class="d-flex align-items-center justify-content-between mb-4 mt-2">
        <div>
            <?php 
                $display_title = ucfirst(str_replace(['_', '-'], ' ', $user_role)) . ' Dashboard';
                if (strtolower($user_role) === 'admin' || strtolower($user_role) === 'master_admin') $display_title = 'Admin Dashboard';
            ?>
            <h2 class="fw-bold text-dark mb-1"><?= $display_title ?></h2>
            <p class="text-muted small">Overview of your operations and system metrics.</p>
        </div>
        <div class="badge bg-primary px-3 py-2 rounded-pill">Welcome, <?= htmlspecialchars($user_name) ?></div>
    </div>

    <!-- MAIN METRIC CARDS (High Visibility) -->
    <div class="row g-4 mb-4">
        <!-- Revenue -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 text-white p-2" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small opacity-75 text-uppercase fw-bold">Earning</div>
                        <div class="h3 fw-bold mb-0">₹<?= number_format($metrics['revenue'], 2) ?></div>
                    </div>
                    <i class="fas fa-indian-rupee-sign fa-2x opacity-25"></i>
                </div>
            </div>
        </div>
        <!-- Expenses -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 text-white p-2" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small opacity-75 text-uppercase fw-bold">Expenses</div>
                        <div class="h3 fw-bold mb-0">₹<?= number_format($metrics['expenses'], 2) ?></div>
                    </div>
                    <i class="fas fa-money-bill-transfer fa-2x opacity-25"></i>
                </div>
            </div>
        </div>
        <!-- Tasks -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 text-white p-2" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small opacity-75 text-uppercase fw-bold">Total Tasks</div>
                        <div class="h3 fw-bold mb-0"><?= $metrics['total_tasks'] ?></div>
                    </div>
                    <i class="fas fa-clipboard-list fa-2x opacity-25"></i>
                </div>
            </div>
        </div>
        <!-- Users -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 text-white p-2" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small opacity-75 text-uppercase fw-bold">Active Users</div>
                        <div class="h3 fw-bold mb-0"><?= $metrics['total_users'] ?></div>
                    </div>
                    <i class="fas fa-users-gear fa-2x opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- APPOINTMENT METRICS -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-1" style="background: #fff; border-left: 5px solid #0284c7 !important;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><div class="text-muted small fw-bold">ALL APPOINTMENTS</div><div class="h4 fw-bold mb-0 text-dark"><?= $metrics['total_appointments'] ?></div></div>
                    <i class="fas fa-calendar-check text-info fa-lg"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-1" style="background: #fff; border-left: 5px solid #8b5cf6 !important;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><div class="text-muted small fw-bold">TODAY'S BOOKINGS</div><div class="h4 fw-bold mb-0 text-dark"><?= $metrics['today_appointments'] ?></div></div>
                    <i class="fas fa-calendar-day text-purple fa-lg"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-1" style="background: #fff; border-left: 5px solid #22c55e !important;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><div class="text-muted small fw-bold">DONE / COMPLETED</div><div class="h4 fw-bold mb-0 text-dark"><?= $metrics['completed_appointments'] ?></div></div>
                    <i class="fas fa-check-double text-success fa-lg"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-1" style="background: #fff; border-left: 5px solid #ef4444 !important;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><div class="text-muted small fw-bold">CANCELLATIONS</div><div class="h4 fw-bold mb-0 text-dark"><?= $metrics['cancelled_appointments'] ?></div></div>
                    <i class="fas fa-calendar-xmark text-danger fa-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Work History -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold m-0 text-dark"><i class="fas fa-history me-2 text-primary"></i>Recent Task Activity</h5>
                    <?php 
                        $view_all_url = "";
                        if (strtolower($user_role) === 'admin' || strtolower($user_role) === 'master_admin') {
                            $view_all_url = "?page=all_tasks";
                        } elseif (strtolower($user_role) === 'accountant') {
                            $view_all_url = "?page=my_daily_entries";
                        } elseif (in_array('all_tasks', $_SESSION['user_permissions'] ?? [])) {
                            $view_all_url = "?page=all_tasks";
                        }
                    ?>
                    <?php if ($view_all_url): ?>
                        <a href="<?= $view_all_url ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">View All</a>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr><th>Work ID</th><th>Client</th><th>Assigned To</th><th>Status</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentTasksList)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No recent tasks found.</td></tr>
                            <?php else: foreach($recentTasksList as $task): ?>
                                <tr>
                                    <td class="fw-bold text-primary">#<?= $task['id'] ?></td>
                                    <td><?= htmlspecialchars($task['client_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($task['assigned_to'] ?? 'Unassigned') ?></td>
                                    <td><span class="badge bg-light text-dark shadow-sm border"><?= ucfirst($task['status'] ?? 'pending') ?></span></td>
                                    <td class="text-muted small"><?= date('d M Y', strtotime($task['created_at'] ?? 'now')) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Summary Quick List -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 h-100">
                <h5 class="fw-bold mb-4 text-dark"><i class="fas fa-bolt me-2 text-warning"></i>Quick Summary</h5>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 mb-2">
                        <div class="d-flex align-items-center">
                            <div class="icon-circle bg-danger-subtle text-danger me-3"><i class="fas fa-clock"></i></div>
                            <span class="text-muted fw-bold small">Pending Tasks</span>
                        </div>
                        <span class="badge bg-danger rounded-pill"><?= $metrics['pending_tasks'] ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 mb-2">
                        <div class="d-flex align-items-center">
                            <div class="icon-circle bg-primary-subtle text-primary me-3"><i class="fas fa-calendar"></i></div>
                            <span class="text-muted fw-bold small">Today's Appointments</span>
                        </div>
                        <span class="badge bg-primary rounded-pill"><?= $metrics['today_appointments'] ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .text-purple { color: #8b5cf6; }
    .icon-circle { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
</style>