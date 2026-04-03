<?php
/**
 * user/dashboard.php
 * REFINED & STABLE: General User / Coordinator Dashboard.
 */

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'] ?? 0;
$currentUserName = $_SESSION['user_name'] ?? 'User';

// --- [ 1. Fetching Data for Stat Cards ] ---
$counts = ['pending' => 0, 'in_process' => 0, 'submitted' => 0, 'completed' => 0, 'total' => 0];
if ($currentUserId > 0) {
    try {
        $taskCounts = fetchAll($pdo, "SELECT status, COUNT(id) as count FROM work_assignments WHERE assigned_to_user_id = ? GROUP BY status", [$currentUserId]);
        foreach ($taskCounts as $row) {
            $s = $row['status'];
            if (isset($counts[$s])) {
                $counts[$s] = $row['count'];
            }
            $counts['total'] += $row['count'];
        }
    } catch (Exception $e) { /* silent */ }
}

// [NEW] Fetch total appointments for the user
$totalAppointments = 0;
if ($currentUserId > 0) {
    $totalAppointments = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM appointments WHERE user_id = ?", [$currentUserId]);
}

// --- [ 2. Recent Tasks ] ---
$recentTasks = [];
if ($currentUserId > 0) {
    $recentTasks = fetchAll($pdo, "
        SELECT wa.id, wa.work_description, wa.deadline, wa.status, cl.client_name 
        FROM work_assignments wa 
        JOIN clients cl ON wa.client_id = cl.id 
        WHERE wa.assigned_to_user_id = ? 
        ORDER BY wa.created_at DESC 
        LIMIT 5
    ", [$currentUserId]);
}

// --- [ 3. Upcoming Appointments ] ---
$upcomingAppointments = [];
if ($currentUserId > 0) {
    $upcomingAppointments = fetchAll($pdo, "
        SELECT a.id, a.client_name, a.client_phone, a.appointment_date, a.appointment_time, c.name as category_name
        FROM appointments a
        JOIN categories c ON a.category_id = c.id
        WHERE a.user_id = ? AND a.appointment_date >= CURDATE() AND a.status = 'pending'
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT 5
    ", [$currentUserId]);
}

if (!function_exists('getStatusBadgeForUser')) {
    function getStatusBadgeForUser($status) {
        $badges = ['pending' => 'secondary', 'in_process' => 'primary', 'submitted' => 'warning', 'completed' => 'success', 'cancelled' => 'danger'];
        $color = $badges[$status] ?? 'light';
        return "<span class='badge bg-{$color}'>" . ucfirst(str_replace('_', ' ', $status)) . "</span>";
    }
}

require_once MODELS_PATH . 'roles.php';
$dash_perms = getDashboardPermissionsForRole($_SESSION['user_role'] ?? 'user');

?>
<div class="container-fluid user-dashboard py-4">
    <?php if (($dash_perms['show_notice_board'] ?? false)) include VIEWS_PATH . 'components/notice_board.php'; ?>

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">My Dashboard</h1>
        <div class="text-muted">Welcome, <span class="fw-bold text-primary"><?= htmlspecialchars($currentUserName) ?></span>!</div>
    </div>

    <?php if ($dash_perms['show_task_summary'] ?? true): ?>
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6"><div class="stat-card shadow-sm border-0 bg-primary text-white"><div class="card-body"><div class="stat-content"><div class="text text-white-50">Total Tasks</div><div class="number text-white"><?= $counts['total'] ?></div></div><i class="fas fa-layer-group fa-2x opacity-25"></i></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="stat-card shadow-sm border-0 bg-info text-white"><div class="card-body"><div class="stat-content"><div class="text text-white-50">In Process</div><div class="number text-white"><?= $counts['in_process'] ?></div></div><i class="fas fa-spinner fa-spin fa-2x opacity-25"></i></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="stat-card shadow-sm border-0 bg-warning text-dark"><div class="card-body"><div class="stat-content"><div class="text text-black-50">Submitted</div><div class="number text-dark"><?= $counts['submitted'] ?></div></div><i class="fas fa-paper-plane fa-2x opacity-25"></i></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="stat-card shadow-sm border-0 bg-success text-white"><div class="card-body"><div class="stat-content"><div class="text text-white-50">Completed</div><div class="number text-white"><?= $counts['completed'] ?></div></div><i class="fas fa-check-circle fa-2x opacity-25"></i></div></div></div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm h-100 border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="m-0 font-weight-bold"><i class="fas fa-history me-2"></i>Recent Work</h5>
                    <a href="?page=my_tasks" class="btn btn-outline-light btn-sm rounded-pill px-3">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr><th>ID</th><th>Client</th><th>Deadline</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentTasks)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No tasks assigned yet.</td></tr>
                            <?php else: foreach($recentTasks as $task): ?>
                                <tr>
                                    <td class="fw-bold">#<?= $task['id'] ?></td>
                                    <td><?= htmlspecialchars($task['client_name']) ?></td>
                                    <td><?= date('d M, Y', strtotime($task['deadline'])) ?></td>
                                    <td><?= getStatusBadgeForUser($task['status']) ?></td>
                                    <td><a href="?page=update_task&id=<?= $task['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Update</a></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden h-100">
                <div class="card-header bg-info text-white py-3">
                    <h5 class="m-0 font-weight-bold"><i class="fas fa-calendar-check me-2"></i>Appointments</h5>
                </div>
                <div class="card-body">
                     <?php if (empty($upcomingAppointments)): ?>
                        <p class="text-center text-muted py-5">No upcoming appointments.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach($upcomingAppointments as $apt): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 mb-2">
                            <div>
                                <strong class="text-dark d-block"><?= htmlspecialchars($apt['client_name']) ?></strong>
                                <small class="text-muted"><?= htmlspecialchars($apt['category_name']) ?></small>
                            </div>
                            <span class="badge bg-light text-primary border"><?= date('d M, H:i', strtotime($apt['appointment_date'] . ' ' . $apt['appointment_time'])) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                     <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .stat-card { border-radius: 15px; }
    .stat-card .card-body { display: flex; align-items: center; justify-content: space-between; padding: 1.5rem; }
    .stat-card .number { font-size: 2rem; font-weight: 800; line-height: 1; }
    .stat-card .text { font-size: 0.8rem; text-transform: uppercase; font-weight: 700; margin-bottom: 5px; }
</style>