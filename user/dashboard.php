<?php
/**
 * user/dashboard.php
 * FINAL & COMPLETE: A completely redesigned dashboard for the general user/coordinator role.
 * All CSS styles have been moved to the central style.css file.
 * NEW: Displays upcoming appointments for the logged-in user.
 */

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'] ?? 'User';

// --- [ 1. Fetching Data for Stat Cards ] ---
$taskCounts = fetchAll($pdo, "SELECT status, COUNT(id) as count FROM work_assignments WHERE assigned_to_user_id = ? GROUP BY status", [$currentUserId]);
$counts = ['pending' => 0, 'in_process' => 0, 'submitted' => 0, 'completed' => 0, 'total' => 0];
foreach ($taskCounts as $row) {
    if (isset($counts[$row['status']])) {
        $counts[$row['status']] = $row['count'];
    }
    $counts['total'] += $row['count'];
}

// [NEW] Fetch total appointments for the user
$totalAppointments = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM appointments WHERE user_id = ?", [$currentUserId]);


// --- [ 2. Fetching Data for Recent Activities ] ---
$recentTasks = fetchAll($pdo, "
    SELECT wa.id, wa.work_description, wa.deadline, wa.status, cl.client_name 
    FROM work_assignments wa 
    JOIN clients cl ON wa.client_id = cl.id 
    WHERE wa.assigned_to_user_id = ? 
    ORDER BY wa.created_at DESC 
    LIMIT 5
", [$currentUserId]);

// --- [ 3. NEW: Fetching Upcoming Appointments ] ---
$upcomingAppointments = fetchAll($pdo, "
    SELECT a.id, a.client_name, a.client_phone, a.appointment_date, a.appointment_time, c.name as category_name
    FROM appointments a
    JOIN categories c ON a.category_id = c.id
    WHERE a.user_id = ? AND a.appointment_date >= CURDATE() AND a.status = 'pending'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 5
", [$currentUserId]);


function getStatusBadgeForUser($status) {
    $badges = ['pending' => 'secondary', 'in_process' => 'primary', 'submitted' => 'warning', 'completed' => 'success', 'cancelled' => 'danger'];
    $color = $badges[$status] ?? 'light';
    $textColor = in_array($color, ['warning', 'light']) ? 'dark' : 'white';
    return "<span class='badge bg-{$color} text-{$textColor}'>" . ucfirst(str_replace('_', ' ', $status)) . "</span>";
}
?>

<div class="container-fluid user-dashboard">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Dashboard</h1>
        <span class="text-muted">Welcome, <strong><?= htmlspecialchars($currentUserName) ?></strong>!</span>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card card-total-tasks">
                <div class="card-body">
                    <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                    <div class="stat-content">
                        <div class="text">Total Assigned Tasks</div>
                        <div class="number"><?= $counts['total'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card card-in-process">
                <div class="card-body">
                    <div class="stat-icon"><i class="fas fa-cogs"></i></div>
                    <div class="stat-content">
                        <div class="text">Tasks In Process</div>
                        <div class="number"><?= $counts['in_process'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card card-submitted">
                <div class="card-body">
                    <div class="stat-icon"><i class="fas fa-paper-plane"></i></div>
                    <div class="stat-content">
                        <div class="text">Submitted Tasks</div>
                        <div class="number"><?= $counts['submitted'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card card-user-completed">
                <div class="card-body">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-content">
                        <div class="text">Completed Tasks</div>
                        <div class="number"><?= $counts['completed'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="m-0 font-weight-bold"><i class="fas fa-history me-2"></i>My Recent Tasks</h5>
                    <a href="?page=my_tasks" class="btn btn-outline-light btn-sm">View All My Tasks</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Task ID</th><th>Client Name</th><th>Description</th><th>Deadline</th><th>Status</th><th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentTasks)): ?>
                                    <tr><td colspan="6" class="text-center text-muted">You have no tasks assigned yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach($recentTasks as $task): ?>
                                <tr>
                                    <td><strong>#<?= $task['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($task['client_name']) ?></td>
                                    <td><?= htmlspecialchars(substr($task['work_description'], 0, 50)) ?>...</td>
                                    <td><?= date('d M, Y', strtotime($task['deadline'])) ?></td>
                                    <td><?= getStatusBadgeForUser($task['status']) ?></td>
                                    <td>
                                        <a href="?page=update_task&id=<?= $task['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-pencil-alt me-1"></i> Update
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0 font-weight-bold"><i class="fas fa-calendar-check me-2"></i>Upcoming Appointments</h5>
                </div>
                <div class="card-body">
                     <?php if (empty($upcomingAppointments)): ?>
                        <p class="text-center text-muted mt-3">No upcoming appointments.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach($upcomingAppointments as $apt): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($apt['client_name']) ?></strong>
                                <small class="d-block text-muted"><?= htmlspecialchars($apt['category_name']) ?></small>
                            </div>
                            <span class="badge bg-primary rounded-pill"><?= date('d M, h:i A', strtotime($apt['appointment_date'] . ' ' . $apt['appointment_time'])) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                     <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>