<?php
/**
 * admin/manage_salaries.php
 * Page for HR to manage salaries and generate slips.
 */
require_once MODELS_PATH . 'hr.php';
$pdo = connectDB();
$users = fetchAll($pdo, "SELECT id, name, salary FROM users WHERE role_id != 1");
$current_month = $_GET['month'] ?? date('m');
$current_year = $_GET['year'] ?? date('Y');

// Generate month and year options for dropdowns
$month_options = '';
for ($i = 1; $i <= 12; $i++) {
    $month_name = date('F', mktime(0, 0, 0, $i, 10));
    $selected = ($current_month == $i) ? 'selected' : '';
    $month_options .= "<option value=\"{$i}\" {$selected}>{$month_name}</option>";
}
$year_options = '';
for ($i = date('Y'); $i >= date('Y') - 5; $i--) {
    $selected = ($current_year == $i) ? 'selected' : '';
    $year_options .= "<option value=\"{$i}\" {$selected}>{$i}</option>";
}
?>
<h2 class="mb-4">Salary Report</h2>
<div class="card shadow-sm mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3>Salary Report</h3>
            <form action="" method="GET" class="d-flex align-items-center">
                <input type="hidden" name="page" value="manage_salaries">
                <select name="month" class="form-select me-2">
                    <?= $month_options ?>
                </select>
                <select name="year" class="form-select me-2">
                    <?= $year_options ?>
                </select>
                <button type="submit" class="btn btn-primary">Go</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <h4 class="mb-3">Report for <?= date('F, Y', strtotime("$current_year-$current_month-01")) ?></h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Base Salary</th>
                        <th>Calculated Salary</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars(number_format($user['salary'], 2)) ?></td>
                            <td><?= htmlspecialchars(number_format(calculateSalary($user['id'], $current_month, $current_year), 2)) ?></td>
                            <td>
                                <a href="admin/generate_salary_slip.php?user_id=<?= $user['id'] ?>&month=<?= $current_month ?>&year=<?= $current_year ?>" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-print"></i> Generate Slip
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>