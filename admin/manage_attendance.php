<?php
/**
 * admin/manage_attendance.php
 * Page for HR to manage user attendance.
 * FIXED: The form now correctly submits to the central index.php action handler.
 */
require_once MODELS_PATH . 'hr.php';
$pdo = connectDB();
$users = fetchAll($pdo, "SELECT id, name FROM users WHERE role_id != 1"); // Exclude admin
$message = '';

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}
?>
<h2 class="mb-4">Manage Attendance</h2>
<?php if ($message) { include VIEWS_PATH . 'components/message_box.php'; } ?>
<div class="card shadow-sm">
    <div class="card-header">
        <h3>Manual Attendance Entry</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">Biometric integration is not possible via code alone. This form allows HR to manually enter attendance data.</div>
        <form method="POST" action="index.php">
            <input type="hidden" name="page" value="manage_attendance">
            <input type="hidden" name="action" value="mark_attendance">
            
            <div class="row">
                 <div class="col-md-6 mb-3">
                    <label for="user_id" class="form-label">User</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Select User</option>
                        <?php foreach($users as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="entry_date" class="form-label">Date</label>
                    <input type="date" name="entry_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
            </div>
             <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="present">Present</option>
                        <option value="half_day">Half Day</option>
                        <option value="absent">Absent</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="check_in" class="form-label">Check-in Time</label>
                    <input type="time" name="check_in" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="check_out" class="form-label">Check-out Time</label>
                    <input type="time" name="check_out" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Mark Attendance</button>
        </form>
    </div>
</div>