<?php
/**
 * admin/dashboard.php
 * FINAL & COMPLETE: A completely redesigned admin dashboard.
 * All CSS styles have been moved to the central style.css file.
 */

$pdo = connectDB();
$currentUserName = $_SESSION['user_name'] ?? 'Admin';

// --- [ 1. Fetching Data for Stat Cards ] ---
$totalUsers = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM users");
$totalClients = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM clients");
$totalTasks = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM work_assignments");
$pendingTasks = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM work_assignments WHERE status = 'pending'");
$inProcessTasks = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM work_assignments WHERE status = 'in_process'");
$totalEarnings = (float)fetchColumn($pdo, "SELECT SUM(task_price) FROM work_assignments WHERE status = 'verified_completed'");
$totalExpenses = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM expenses");
$netProfit = $totalEarnings - $totalExpenses;
$pendingWithdrawals = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM withdrawals WHERE status = 'pending'");
$pendingRecruitmentPosts = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM recruitment_posts WHERE approval_status = 'pending'");
$newRecruitmentPosts = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM recruitment_posts WHERE is_new_for_admin = 1");

$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

// --- [ Appointment Counts ] ---
$totalAppointments = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM appointments");
$todayAppointments = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM appointments WHERE appointment_date = CURDATE() AND status = 'pending'");
$completedAppointments = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM appointments WHERE status = 'completed'");
$cancelledAppointments = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM appointments WHERE status = 'cancelled'");

// --- [ New Expense Counts ] ---
$monthlyExpenses = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM expenses WHERE YEAR(expense_date) = YEAR(CURDATE()) AND MONTH(expense_date) = MONTH(CURDATE())");
$yearlyExpenses = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM expenses WHERE YEAR(expense_date) = YEAR(CURDATE())");


// --- [ 2. Fetching Data for Recent Activities ] ---
$recentTasks = fetchAll($pdo, "SELECT wa.id, wa.work_description, u.name as assigned_to, cl.client_name, wa.created_at FROM work_assignments wa JOIN users u ON wa.assigned_to_user_id = u.id JOIN clients cl ON wa.client_id = cl.id ORDER BY wa.created_at DESC LIMIT 5");
$recentUsers = fetchAll($pdo, "SELECT name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recentNotifications = fetchAll($pdo, "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");

// --- [ 3. Fetching Data for Chart ] ---
$chartData = fetchAll($pdo, "
    SELECT 
        DATE_FORMAT(month_year, '%b %Y') as month,
        total_earnings,
        total_expenses
    FROM (
        SELECT DATE_FORMAT(created_at, '%Y-%m-01') as month_year, SUM(task_price) as total_earnings, 0 as total_expenses
        FROM work_assignments
        WHERE status = 'verified_completed'
        GROUP BY month_year
        UNION ALL
        SELECT DATE_FORMAT(expense_date, '%Y-%m-01') as month_year, 0 as total_earnings, SUM(amount) as total_expenses
        FROM expenses
        GROUP BY month_year
    ) as combined
    GROUP BY month_year
    ORDER BY month_year DESC
    LIMIT 6
");
$chartData = array_reverse($chartData); 
$chartLabels = json_encode(array_column($chartData, 'month'));
$chartEarnings = json_encode(array_column($chartData, 'total_earnings'));
$chartExpenses = json_encode(array_column($chartData, 'total_expenses'));
?>

<div class="container-fluid admin-dashboard">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Admin Dashboard</h1>
        <span class="text-muted">Welcome back, <strong><?= htmlspecialchars($currentUserName) ?></strong>!</span>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-revenue"><div class="card-body"><div class="stat-icon"><i class="fas fa-dollar-sign"></i></div><div class="stat-content"><div class="text">Total Revenue</div><div class="number"><?= $currencySymbol ?><?= number_format($totalEarnings, 2) ?></div></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-expenses-monthly"><div class="card-body"><div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div><div class="stat-content"><div class="text">Monthly Expenses</div><div class="number"><?= $currencySymbol ?><?= number_format($monthlyExpenses, 2) ?></div></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-expenses-yearly"><div class="card-body"><div class="stat-icon"><i class="fas fa-money-check-alt"></i></div><div class="stat-content"><div class="text">Yearly Expenses</div><div class="number"><?= $currencySymbol ?><?= number_format($yearlyExpenses, 2) ?></div></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-profit"><div class="card-body"><div class="stat-icon"><i class="fas fa-chart-line"></i></div><div class="stat-content"><div class="text">Net Profit</div><div class="number"><?= $currencySymbol ?><?= number_format($netProfit, 2) ?></div></div></div></div></div>
    </div>
    
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-total-tasks"><div class="card-body"><div class="stat-icon"><i class="fas fa-list-ul"></i></div><div class="stat-content"><div class="text">Total Tasks</div><div class="number"><?= $totalTasks ?></div></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-in-process"><div class="card-body"><div class="stat-icon"><i class="fas fa-cogs"></i></div><div class="stat-content"><div class="text">In Process Tasks</div><div class="number"><?= $inProcessTasks ?></div></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-admin-tasks"><div class="card-body"><div class="stat-icon"><i class="fas fa-tasks"></i></div><div class="stat-content"><div class="text">Pending Tasks</div><div class="number"><?= $pendingTasks ?> / <?= $totalTasks ?></div></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-users"><div class="card-body"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-content"><div class="text">Total Users</div><div class="number"><?= $totalUsers ?></div></div></div></div></div>
    </div>
    
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
             <div class="stat-card card-appointments">
                <div class="card-body">
                     <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                     <div class="stat-content">
                         <div class="text">Total Appointments</div>
                         <div class="number"><?= $totalAppointments ?></div>
                     </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-appointment-today"><div class="card-body"><div class="stat-icon"><i class="fas fa-calendar-day"></i></div><div class="stat-content"><div class="text">Today's Appointments</div><div class="number"><?= $todayAppointments ?></div></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-appointment-completed"><div class="card-body"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><div class="text">Completed Appointments</div><div class="number"><?= $completedAppointments ?></div></div></div></div></div>
        <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-appointment-cancelled"><div class="card-body"><div class="stat-icon"><i class="fas fa-times-circle"></i></div><div class="stat-content"><div class="text">Cancelled Appointments</div><div class="number"><?= $cancelledAppointments ?></div></div></div></div></div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white d-flex flex-row align-items-center justify-content-between"><h5 class="m-0 font-weight-bold"><i class="fas fa-chart-bar me-2"></i>Monthly Overview (Last 6 Months)</h5></div>
                <div class="card-body"><div class="chart-area"><canvas id="myAreaChart"></canvas></div></div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white"><h5 class="m-0 font-weight-bold"><i class="fas fa-bell me-2"></i>Pending Actions</h5></div>
                <div class="card-body">
                    <div class="quick-stats">
                        <a href="?page=manage_withdrawals" class="stat-item"><div class="icon bg-warning"><i class="fas fa-hand-holding-usd"></i></div><div class="content"><span class="value"><?= $pendingWithdrawals ?></span><span class="label">Withdrawal Requests</span></div></a>
                        <a href="?page=manage_recruitment_posts" class="stat-item"><div class="icon bg-info"><i class="fas fa-bullhorn"></i></div><div class="content"><span class="value"><?= $pendingRecruitmentPosts ?></span><span class="label">Recruitment Posts</span></div></a>
                        <a href="?page=all_tasks&status=pending" class="stat-item"><div class="icon bg-danger"><i class="fas fa-inbox"></i></div><div class="content"><span class="value"><?= $pendingTasks ?></span><span class="label">Unassigned Tasks</span></div></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white"><h5 class="m-0 font-weight-bold"><i class="fas fa-history me-2"></i>Recent Activity</h5></div>
                <div class="card-body">
                    <div class="activity-feed">
                        <?php foreach($recentTasks as $task): ?>
                        <div class="activity-item"><div class="activity-icon bg-primary"><i class="fas fa-clipboard-list"></i></div><div><strong>New Task Assigned:</strong> #<?= $task['id'] ?> for client <?= htmlspecialchars($task['client_name']) ?><div class="activity-meta"><?= date('d M, Y H:i', strtotime($task['created_at'])) ?></div></div></div>
                        <?php endforeach; ?>
                        <?php foreach($recentUsers as $user): ?>
                        <div class="activity-item"><div class="activity-icon bg-success"><i class="fas fa-user-plus"></i></div><div><strong>New User Registered:</strong> <?= htmlspecialchars($user['name']) ?><div class="activity-meta"><?= date('d M, Y H:i', strtotime($user['created_at'])) ?></div></div></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white"><h5 class="m-0 font-weight-bold"><i class="fas fa-bell-on me-2"></i>User Notifications</h5></div>
                <div class="card-body">
                    <div class="activity-feed">
                        <?php if(empty($recentNotifications)): ?>
                            <p class="text-center text-muted">No new notifications.</p>
                        <?php endif; ?>
                        <?php foreach($recentNotifications as $notification): ?>
                        <div class="activity-item"><div class="activity-icon bg-info"><i class="fas fa-comment-alt-dots"></i></div><div><?= htmlspecialchars($notification['message']) ?><div class="activity-meta"><?= date('d M, Y H:i', strtotime($notification['created_at'])) ?></div></div></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var ctx = document.getElementById("myAreaChart");
    if (ctx) {
        var myLineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= $chartLabels ?>,
                datasets: [{
                    label: "Earnings",
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    borderColor: "rgba(78, 115, 223, 1)",
                    data: <?= $chartEarnings ?>,
                    tension: 0.3
                }, {
                    label: "Expenses",
                    backgroundColor: 'rgba(231, 74, 59, 0.1)',
                    borderColor: "rgba(231, 74, 59, 1)",
                    data: <?= $chartExpenses ?>,
                    tension: 0.3
                }],
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    x: { grid: { display: false } },
                    y: { ticks: { callback: function(value) { return '<?= $currencySymbol ?>' + value; } } }
                }
            }
        });
    }
});
</script>