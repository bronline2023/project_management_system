<?php
// admin/appointments.php
// This page manages all client appointments.
// It allows admin/accountant/hr to view, approve, and manage appointments.

if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'accountant' && $_SESSION['user_role'] !== 'hr') {
    header("Location: " . BASE_URL . "?page=dashboard");
    exit;
}

$pdo = connectDB();
$sql = "SELECT a.*, c.name as category_name, u.name as user_name FROM appointments a LEFT JOIN categories c ON a.category_id = c.id LEFT JOIN users u ON a.user_id = u.id ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$appointments = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$status_colors = [
    'pending' => 'warning',
    'confirmed' => 'success',
    'cancelled' => 'danger',
    'completed' => 'primary'
];

?>

<div class="container-fluid">
    <h1 class="mt-4">Manage Appointments</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Appointments</li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            All Appointments
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="appointmentsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client Name</th>
                            <th>Category</th>
                            <th>Assigned To</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Document</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['id']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($appointment['user_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('d-M-Y', strtotime($appointment['appointment_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                <td><span class="badge bg-<?php echo $status_colors[$appointment['status']] ?? 'secondary'; ?>"><?php echo ucfirst($appointment['status']); ?></span></td>
                                <td><?php echo htmlspecialchars($appointment['notes']); ?></td>
                                <td>
                                    <?php if (!empty($appointment['document_path'])): ?>
                                        <a href="<?php echo BASE_URL . $appointment['document_path']; ?>" target="_blank" class="btn btn-info btn-sm">View Doc</a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($appointment['status'] === 'pending'): ?>
                                        <form action="index.php" method="POST" class="d-inline">
                                            <input type="hidden" name="page" value="appointments">
                                            <input type="hidden" name="action" value="update_appointment_status">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="status" value="confirmed">
                                            <button type="submit" class="btn btn-success btn-sm" <?php echo ($appointment['status'] === 'confirmed') ? 'disabled' : ''; ?>>Confirm</button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="index.php" method="POST" class="d-inline">
                                        <input type="hidden" name="page" value="appointments">
                                        <input type="hidden" name="action" value="update_appointment_status">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="btn btn-danger btn-sm" <?php echo ($appointment['status'] === 'cancelled') ? 'disabled' : ''; ?>>Cancel</button>
                                    </form>
                                    <form action="index.php" method="POST" class="d-inline">
                                        <input type="hidden" name="page" value="appointments">
                                        <input type="hidden" name="action" value="update_appointment_status">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="btn btn-primary btn-sm" <?php echo ($appointment['status'] === 'completed') ? 'disabled' : ''; ?>>Complete</button>
                                    </form>
                                    <form action="index.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this appointment? This action cannot be undone.');">
                                        <input type="hidden" name="page" value="appointments">
                                        <input type="hidden" name="action" value="delete_appointment">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#appointmentsTable').DataTable({
            "order": [[ 4, "desc" ]] 
        });
    });
</script>