<?php
/**
 * views/master_reports.php
 * MASTER ADMIN: View specific Tenant's internal reports via Cross-DB PDO.
 */

if (($_SESSION['user_role'] ?? '') !== 'master_admin') {
    die("<div class='alert alert-danger text-center mt-5'>Access Denied. Central Admin Only.</div>");
}

$tenant_id = (int)$_GET['tenant_id'] ?? 0;
if (!$tenant_id) {
    die("<div class='alert alert-danger text-center mt-5'>Invalid Tenant ID</div>");
}

$master_pdo = connectDB();
$tenant = fetchOne($master_pdo, "SELECT * FROM tenants WHERE id = ?", [$tenant_id]);

if (!$tenant) {
    die("<div class='alert alert-danger text-center mt-5'>Tenant Not Found</div>");
}

// Ensure the tenant has a database
try {
    // We connect to the tenant's specific database dynamically using master credentials assuming they have access
    $tenant_pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . $tenant['db_name'] . ";charset=utf8mb4", DB_USER, DB_PASS);
    $tenant_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch some basic metrics
    $total_users = $tenant_pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_clients = $tenant_pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
    $total_tasks = $tenant_pdo->query("SELECT COUNT(*) FROM work_assignments")->fetchColumn();
    $total_completed = $tenant_pdo->query("SELECT COUNT(*) FROM work_assignments WHERE status = 'completed'")->fetchColumn();
    $total_appointments = $tenant_pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
    
    // Fetch Recent Activity
    $recent_tasks = $tenant_pdo->query("
        SELECT w.id, w.work_description, w.status, w.created_at, u.name as assigned_to
        FROM work_assignments w
        LEFT JOIN users u ON w.assigned_to_user_id = u.id
        ORDER BY w.id DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $db_err = $e->getMessage();
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-line text-primary border-end border-3 border-primary pe-3 me-2"></i> Client Analytics</h2>
            <p class="mb-0 mt-1 text-muted">Viewing metrics for <strong><?= htmlspecialchars($tenant['company_name']) ?></strong> (<?= htmlspecialchars($tenant['domain_name']) ?>)</p>
        </div>
        <a href="?page=master_portals" class="btn btn-outline-secondary fw-bold shadow-sm"><i class="fas fa-arrow-left me-2"></i>Back to Portals</a>
    </div>

    <?php if(isset($db_err)): ?>
        <div class="alert alert-danger shadow-sm fw-bold">
            <i class="fas fa-exclamation-triangle me-2"></i> Could not connect to Tenant Database (<?= htmlspecialchars($tenant['db_name']) ?>). It may have been deleted or corrupted.
            <br>Error: <?= htmlspecialchars($db_err) ?>
        </div>
    <?php else: ?>

    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
            <div class="card shadow-sm border-0 border-start border-4 border-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Users/Staff</div>
                            <div class="h3 mb-0 fw-bold text-gray-800"><?= number_format($total_users) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
            <div class="card shadow-sm border-0 border-start border-4 border-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Assigned Tasks</div>
                            <div class="h3 mb-0 fw-bold text-gray-800"><?= number_format($total_tasks) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
            <div class="card shadow-sm border-0 border-start border-4 border-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Completed Tasks</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h3 mb-0 mr-3 fw-bold text-gray-800"><?= number_format($total_completed) ?></div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <?php $pct = $total_tasks > 0 ? ($total_completed / $total_tasks) * 100 : 0; ?>
                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?= $pct ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-double fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
            <div class="card shadow-sm border-0 border-start border-4 border-warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Appointments Booked</div>
                            <div class="h3 mb-0 fw-bold text-gray-800"><?= number_format($total_appointments) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white py-3">
            <h6 class="m-0 fw-bold"><i class="fas fa-history text-warning me-2"></i>Recent Work Activity in this Portal</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Task ID</th>
                            <th>Description</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($recent_tasks)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No activities found in this tenant yet.</td></tr>
                        <?php else: ?>
                            <?php foreach($recent_tasks as $t): ?>
                                <tr>
                                    <td class="fw-bold">#<?= $t['id'] ?></td>
                                    <td><?= htmlspecialchars(substr($t['work_description'] ?? 'No Description', 0, 80)) ?>...</td>
                                    <td><?= htmlspecialchars($t['assigned_to'] ?? 'Unassigned') ?></td>
                                    <td>
                                        <?php
                                            $badgeClass = 'bg-secondary';
                                            if ($t['status'] == 'completed') $badgeClass = 'bg-success';
                                            if ($t['status'] == 'in_process') $badgeClass = 'bg-primary';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= strtoupper($t['status']) ?></span>
                                    </td>
                                    <td><?= date('d M Y, h:i A', strtotime($t['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>
