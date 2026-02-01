<?php
require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';
require_once MODELS_PATH . 'hr.php';

if (!isLoggedIn() || !in_array('manage_salaries', $_SESSION['user_permissions'] ?? [])) {
    die("Access Denied.");
}

$pdo = connectDB();
$user_id = $_GET['user_id'] ?? 0;
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

$user = fetchOne($pdo, "SELECT u.name, u.email, r.role_name, u.salary FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$user_id]);
$settings = fetchOne($pdo, "SELECT app_name, app_logo_url, currency_symbol FROM settings LIMIT 1");

if (!$user) {
    die("User not found.");
}

$salaryDetails = calculateSalary($user_id, $month, $year, true); // true to get detailed breakdown
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Slip for <?= htmlspecialchars($user['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .slip-container { max-width: 800px; margin: 40px auto; background: #fff; border: 1px solid #dee2e6; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .slip-header { text-align: center; padding: 20px; border-bottom: 2px solid #000; }
        .slip-header img { max-width: 100px; margin-bottom: 10px; }
        .slip-body { padding: 30px; }
        .employee-details, .salary-details { width: 100%; margin-bottom: 30px; }
        .employee-details td, .salary-details td { padding: 8px; border: 1px solid #eee; }
        .salary-details .earnings, .salary-details .deductions { width: 50%; }
        .net-pay { font-weight: bold; font-size: 1.2em; }
        @media print {
            body { background-color: #fff; }
            .no-print { display: none; }
            .slip-container { box-shadow: none; border: none; margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="slip-container">
            <div class="slip-header">
                <?php if (!empty($settings['app_logo_url'])): ?>
                    <img src="<?= htmlspecialchars($settings['app_logo_url']) ?>" alt="Logo">
                <?php endif; ?>
                <h2><?= htmlspecialchars($settings['app_name']) ?></h2>
                <h5>Salary Slip for <?= date('F, Y', strtotime("$year-$month-01")) ?></h5>
            </div>
            <div class="slip-body">
                <h6>Employee Details</h6>
                <table class="employee-details">
                    <tr>
                        <td><strong>Employee Name:</strong></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><strong>Designation:</strong></td>
                        <td><?= htmlspecialchars($user['role_name']) ?></td>
                    </tr>
                </table>

                <div class="row">
                    <div class="col-md-6">
                        <h6>Earnings</h6>
                        <table class="salary-details">
                            <tr><td>Basic Salary</td><td class="text-end"><?= number_format($user['salary'], 2) ?></td></tr>
                        </table>
                    </div>
                     <div class="col-md-6">
                        <h6>Deductions</h6>
                        <table class="salary-details">
                            <tr><td>Absent/Unpaid Leave</td><td class="text-end">- <?= number_format($salaryDetails['deductions'], 2) ?></td></tr>
                        </table>
                    </div>
                </div>
                 <hr>
                <div class="d-flex justify-content-between">
                    <div class="net-pay">Net Salary Payable:</div>
                    <div class="net-pay"><?= htmlspecialchars($settings['currency_symbol']) ?> <?= number_format($salaryDetails['net_salary'], 2) ?></div>
                </div>
                 <hr>
                 <h6>Attendance Summary</h6>
                <table class="employee-details">
                     <tr>
                        <td><strong>Total Working Days:</strong></td><td><?= $salaryDetails['working_days'] ?></td>
                        <td><strong>Present Days:</strong></td><td><?= $salaryDetails['present_days'] ?></td>
                    </tr>
                     <tr>
                        <td><strong>Half Days:</strong></td><td><?= $salaryDetails['half_days'] ?></td>
                        <td><strong>Absent Days:</strong></td><td><?= $salaryDetails['absent_days'] ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="text-center my-4 no-print">
            <button onclick="window.print()" class="btn btn-primary">Print Salary Slip</button>
            <a href="?page=manage_salaries" class="btn btn-secondary">Back to Reports</a>
        </div>
    </div>
</body>
</html>