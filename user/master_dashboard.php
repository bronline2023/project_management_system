<?php
/**
 * user/master_dashboard.php
 * A dynamic dashboard for users like Accountant, Manager, Coordinator, etc.
 * The content is customized based on user roles and permissions.
 */

require_once MODELS_PATH . 'roles.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$currentUserRole = $_SESSION['user_role'] ?? 'guest';
$currentUserName = $_SESSION['user_name'] ?? 'User';

// --- [ Dashboard Configuration from roles.php ] ---
$dashboardPermissions = getDashboardPermissionsForRole($currentUserRole);

// --- [ Fetch Data based on Permissions ] ---
$data = [];
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

// Fetch data for financial cards
if (isset($dashboardPermissions['show_financial_summary']) && $dashboardPermissions['show_financial_summary']) {
    $data['totalEarnings'] = (float)fetchColumn($pdo, "SELECT SUM(fee - maintenance_fee - discount) FROM work_assignments WHERE status = 'completed'") ?? 0.00;
    $data['totalExpenses'] = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM expenses") ?? 0.00;
    $data['netProfit'] = $data['totalEarnings'] - $data['totalExpenses'];
    $data['monthlyExpenses'] = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM expenses WHERE YEAR(expense_date) = YEAR(CURDATE()) AND MONTH(expense_date) = MONTH(CURDATE())") ?? 0.00;
    $data['yearlyExpenses'] = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM expenses WHERE YEAR(expense_date) = YEAR(CURDATE())") ?? 0.00;
}

// Fetch data for task cards
if (isset($dashboardPermissions['show_task_summary']) && $dashboardPermissions['show_task_summary']) {
    // [FIX] Filter tasks by assigned user for non-admin roles
    $task_condition = ($currentUserRole === 'admin') ? '' : "WHERE assigned_to_user_id = {$currentUserId}";
    $data['totalTasks'] = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM work_assignments {$task_condition}");
    $data['pendingTasks'] = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM work_assignments WHERE status = 'pending' " . (($currentUserRole === 'admin') ? '' : "AND assigned_to_user_id = {$currentUserId}"));
    $data['inProcessTasks'] = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM work_assignments WHERE status = 'in_process' " . (($currentUserRole === 'admin') ? '' : "AND assigned_to_user_id = {$currentUserId}"));
}

// Fetch data for user and client cards
if (isset($dashboardPermissions['show_user_client_summary']) && $dashboardPermissions['show_user_client_summary']) {
    $data['totalUsers'] = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM users");
    $data['totalClients'] = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM clients");
}

// Fetch data for appointment cards
if (isset($dashboardPermissions['show_appointment_summary']) && $dashboardPermissions['show_appointment_summary']) {
    $appointment_condition = ($currentUserRole === 'admin') ? '' : "AND user_id = {$currentUserId}";
    $data['todayAppointments'] = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM appointments WHERE appointment_date = CURDATE() AND status = 'pending' {$appointment_condition}");
    $data['completedAppointments'] = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM appointments WHERE status = 'completed' {$appointment_condition}");
    $data['cancelledAppointments'] = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM appointments WHERE status = 'cancelled' {$appointment_condition}");
}

// Fetch data for pending actions
if (isset($dashboardPermissions['show_pending_actions']) && $dashboardPermissions['show_pending_actions']) {
    $data['pendingWithdrawals'] = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM withdrawals WHERE status = 'pending'");
    $data['pendingRecruitmentPosts'] = (int)fetchColumn($pdo, "SELECT COUNT(id) FROM recruitment_posts WHERE approval_status = 'pending'");
}

// Fetch data for recent activities
if (isset($dashboardPermissions['show_recent_activity']) && $dashboardPermissions['show_recent_activity']) {
    $task_activity_condition = ($currentUserRole === 'admin') ? '' : " WHERE wa.assigned_to_user_id = {$currentUserId}";
    $data['recentTasks'] = fetchAll($pdo, "SELECT wa.id, wa.work_description, u.name as assigned_to, cl.client_name, wa.created_at FROM work_assignments wa JOIN users u ON wa.assigned_to_user_id = u.id JOIN clients cl ON wa.client_id = cl.id {$task_activity_condition} ORDER BY wa.created_at DESC LIMIT 5");
    $data['recentUsers'] = fetchAll($pdo, "SELECT name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
}

// Fetch data for notifications
if (isset($dashboardPermissions['show_notifications']) && $dashboardPermissions['show_notifications']) {
    $data['recentNotifications'] = fetchAll($pdo, "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$currentUserId]);
}

?>

<div class="container-fluid master-dashboard">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= ucfirst($currentUserRole) ?> Dashboard</h1>
        <span class="text-muted">Welcome back, <strong><?= htmlspecialchars($currentUserName) ?></strong>!</span>
    </div>

    <div class="row">
        <?php if (isset($dashboardPermissions['show_financial_summary']) && $dashboardPermissions['show_financial_summary']): ?>
            <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-revenue"><div class="card-body"><div class="stat-icon"><i class="fas fa-dollar-sign"></i></div><div class="stat-content"><div class="text">Total Revenue</div><div class="number"><?= $currencySymbol ?><?= number_format($data['totalEarnings'], 2) ?></div></div></div></div></div>
            <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-expenses-monthly"><div class="card-body"><div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div><div class="stat-content"><div class="text">Monthly Expenses</div><div class="number"><?= $currencySymbol ?><?= number_format($data['monthlyExpenses'], 2) ?></div></div></div></div></div>
            <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-expenses-yearly"><div class="card-body"><div class="stat-icon"><i class="fas fa-money-check-alt"></i></div><div class="stat-content"><div class="text">Yearly Expenses</div><div class="number"><?= $currencySymbol ?><?= number_format($data['yearlyExpenses'], 2) ?></div></div></div></div></div>
            <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-profit"><div class="card-body"><div class="stat-icon"><i class="fas fa-chart-line"></i></div><div class="stat-content"><div class="text">Net Profit</div><div class="number"><?= $currencySymbol ?><?= number_format($data['netProfit'], 2) ?></div></div></div></div></div>
        <?php endif; ?>

        <?php if (isset($dashboardPermissions['show_task_summary']) && $dashboardPermissions['show_task_summary']): ?>
            <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-total-tasks"><div class="card-body"><div class="stat-icon"><i class="fas fa-list-ul"></i></div><div class="stat-content"><div class="text">Total Tasks</div><div class="number"><?= $data['totalTasks'] ?></div></div></div></div></div>
            <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-in-process"><div class="card-body"><div class="stat-icon"><i class="fas fa-cogs"></i></div><div class="stat-content"><div class="text">In Process Tasks</div><div class="number"><?= $data['inProcessTasks'] ?></div></div></div></div></div>
            <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-admin-tasks"><div class="card-body"><div class="stat-icon"><i class="fas fa-tasks"></i></div><div class="stat-content"><div class="text">Pending Tasks</div><div class="number"><?= $data['pendingTasks'] ?> / <?= $data['totalTasks'] ?></div></div></div></div></div>
        <?php endif; ?>
        
        <?php if (isset($dashboardPermissions['show_user_client_summary']) && $dashboardPermissions['show_user_client_summary']): ?>
            <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-users"><div class="card-body"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-content"><div class="text">Total Users</div><div class="number"><?= $data['totalUsers'] ?></div></div></div></div></div>
            <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-submitted"><div class="card-body"><div class="stat-icon"><i class="fas fa-user-tie"></i></div><div class="stat-content"><div class="text">Total Clients</div><div class="number"><?= $data['totalClients'] ?></div></div></div></div></div>
        <?php endif; ?>

        <?php if (isset($dashboardPermissions['show_appointment_summary']) && $dashboardPermissions['show_appointment_summary']): ?>
            <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-appointment-today"><div class="card-body"><div class="stat-icon"><i class="fas fa-calendar-day"></i></div><div class="stat-content"><div class="text">Today's Appointments</div><div class="number"><?= $data['todayAppointments'] ?></div></div></div></div></div>
            <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-appointment-completed"><div class="card-body"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><div class="text">Completed Appointments</div><div class="number"><?= $data['completedAppointments'] ?></div></div></div></div></div>
            <div class="col-xl-3 col-md-6 mb-4"><div class="stat-card card-appointment-cancelled"><div class="card-body"><div class="stat-icon"><i class="fas fa-times-circle"></i></div><div class="stat-content"><div class="text">Cancelled Appointments</div><div class="number"><?= $data['cancelledAppointments'] ?></div></div></div></div></div>
        <?php endif; ?>
    </div>

    <div class="row">
        <?php if (isset($dashboardPermissions['show_recent_activity']) && $dashboardPermissions['show_recent_activity']): ?>
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-dark text-white d-flex flex-row align-items-center justify-content-between"><h5 class="m-0 font-weight-bold"><i class="fas fa-history me-2"></i>Recent Activity</h5></div>
                    <div class="card-body">
                        <div class="activity-feed">
                            <?php if (empty($data['recentTasks'])): ?>
                                <p class="text-center text-muted">No recent activity found.</p>
                            <?php else: ?>
                                <?php foreach($data['recentTasks'] as $task): ?>
                                    <div class="activity-item"><div class="activity-icon bg-primary"><i class="fas fa-clipboard-list"></i></div><div><strong>New Task Assigned:</strong> #<?= $task['id'] ?> for client <?= htmlspecialchars($task['client_name']) ?><div class="activity-meta"><?= date('d M, Y H:i', strtotime($task['created_at'])) ?></div></div></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($dashboardPermissions['show_notifications']) && $dashboardPermissions['show_notifications']): ?>
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-dark text-white"><h5 class="m-0 font-weight-bold"><i class="fas fa-bell-on me-2"></i>User Notifications</h5></div>
                    <div class="card-body">
                        <div class="activity-feed">
                            <?php if(empty($data['recentNotifications'])): ?>
                                <p class="text-center text-muted">No new notifications.</p>
                            <?php else: ?>
                                <?php foreach($data['recentNotifications'] as $notification): ?>
                                    <div class="activity-item"><div class="activity-icon bg-info"><i class="fas fa-comment-alt-dots"></i></div><div><?= htmlspecialchars($notification['message']) ?><div class="activity-meta"><?= date('d M, Y H:i', strtotime($notification['created_at'])) ?></div></div></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>