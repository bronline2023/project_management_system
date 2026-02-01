<?php
/**
 * admin/hr_management.php
 * HR Management Dashboard.
 * FINAL & COMPLETE: Form now correctly submits to the central index.php action handler.
 */

require_once MODELS_PATH . 'hr.php';
$pdo = connectDB();

$users = fetchAll($pdo, "SELECT id, name, salary FROM users WHERE role_id != 1"); // Exclude admin
$current_month = date('m');
$current_year = date('Y');
$message = '';

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}
?>

<h2 class="mb-4">HR Management</h2>
<?php if ($message) { include VIEWS_PATH . 'components/message_box.php'; } ?>

<div class="card shadow-sm">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#salary-report">Salary Report</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#attendance">Manual Attendance</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="salary-report">
                <h3>Salary Report for <?= date('F, Y') ?></h3>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Base Salary</th>
                                <th>Calculated Salary (This Month)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars(number_format($user['salary'], 2)) ?></td>
                                    <td><?= htmlspecialchars(number_format(calculateSalary($user['id'], $current_month, $current_year), 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="attendance">
                <h3>Manual Attendance Entry</h3>
                <div class="alert alert-info">Biometric integration is not possible via code alone. This form allows HR to manually enter attendance data.</div>
                
                <form method="POST" action="index.php">
                    <input type="hidden" name="page" value="hr_management">
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
    </div>
</div>