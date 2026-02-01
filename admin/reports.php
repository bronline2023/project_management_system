<?php
/**
 * admin/reports.php
 * This file provides various reports for the administrator.
 */

$pdo = connectDB();
$message = '';
$time_period = $_GET['time_period'] ?? 'monthly';
$search_query = trim($_GET['search'] ?? '');
$limit = 10;
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '$');

function getReportDateClause($period, $date_field = 'created_at') {
    switch ($period) {
        case 'daily': return "DATE($date_field) = CURDATE()";
        case 'weekly': return "$date_field >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
        case 'monthly': return "YEAR($date_field) = YEAR(CURDATE()) AND MONTH($date_field) = MONTH(CURDATE())";
        case 'yearly': return "YEAR($date_field) = YEAR(CURDATE())";
        default: return "1=1";
    }
}

// Revenue Summary
$dateClauseIncome = getReportDateClause($time_period, 'wa.completed_at');
$totalIncome = fetchColumn($pdo, "SELECT SUM(fee - maintenance_fee) FROM work_assignments wa WHERE status = 'completed' AND $dateClauseIncome") ?? 0;
$dateClauseExpenses = getReportDateClause($time_period, 'expense_date');
$totalExpenses = fetchColumn($pdo, "SELECT SUM(amount) FROM expenses WHERE $dateClauseExpenses") ?? 0;
$netProfit = $totalIncome - $totalExpenses;

// Detailed Revenue Report
$page_revenue = isset($_GET['p_revenue']) ? (int)$_GET['p_revenue'] : 1;
$offset_revenue = ($page_revenue - 1) * $limit;
$total_revenue_records = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE status = 'completed' AND " . getReportDateClause($time_period, 'completed_at'));
$detailedRevenue = fetchAll($pdo, "SELECT wa.id, cl.client_name, us.name, wa.fee, wa.maintenance_fee, wa.completed_at FROM work_assignments wa JOIN clients cl ON wa.client_id = cl.id JOIN users us ON wa.assigned_to_user_id = us.id WHERE wa.status = 'completed' AND " . getReportDateClause($time_period, 'wa.completed_at') . " ORDER BY wa.completed_at DESC LIMIT $limit OFFSET $offset_revenue");
?>
<h2 class="mb-4">Reports</h2>

<div class="card shadow-sm rounded-3 mb-4">
    <div class="card-header"><h5 class="mb-0">Filter Reports</h5></div>
    <div class="card-body">
        <form action="" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="reports">
            <div class="col-md-4">
                <label for="timePeriodFilter" class="form-label">Time Period:</label>
                <select class="form-select rounded-pill" id="timePeriodFilter" name="time_period">
                    <option value="daily" <?= $time_period == 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="weekly" <?= $time_period == 'weekly' ? 'selected' : '' ?>>Weekly</option>
                    <option value="monthly" <?= $time_period == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="yearly" <?= $time_period == 'yearly' ? 'selected' : '' ?>>Yearly</option>
                    <option value="all" <?= $time_period == 'all' ? 'selected' : '' ?>>All Time</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary rounded-pill w-100">Apply</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm rounded-3 mb-4">
    <div class="card-header"><h5 class="mb-0">Revenue Summary (<?= ucfirst($time_period) ?>)</h5></div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-4"><div class="p-3 bg-light rounded-3"><h6>Total Income</h6><h3><?= $currencySymbol ?><?= number_format($totalIncome, 2) ?></h3></div></div>
            <div class="col-md-4"><div class="p-3 bg-light rounded-3"><h6>Total Expenses</h6><h3><?= $currencySymbol ?><?= number_format($totalExpenses, 2) ?></h3></div></div>
            <div class="col-md-4"><div class="p-3 bg-light rounded-3"><h6>Net Profit</h6><h3><?= $currencySymbol ?><?= number_format($netProfit, 2) ?></h3></div></div>
        </div>
    </div>
</div>

<div class="card shadow-sm rounded-3">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Detailed Revenue Report</h5>
        <a href="<?= BASE_URL ?>admin/export_report.php?type=revenue&time_period=<?= $time_period ?>" class="btn btn-success btn-sm">Export Excel</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Task ID</th><th>Client</th><th>Assigned To</th><th>Total Paid</th><th>Completed On</th></tr></thead>
                <tbody>
                    <?php foreach ($detailedRevenue as $row): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['client_name']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= $currencySymbol ?><?= number_format($row['fee'] - $row['maintenance_fee'], 2) ?></td>
                        <td><?= date('Y-m-d', strtotime($row['completed_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </div>
</div>