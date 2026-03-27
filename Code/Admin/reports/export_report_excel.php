<?php
// 1. Start Output Buffering IMMEDIATELY to prevent header errors
ob_start();

/**
 * admin/export_report_excel.php
 * FINAL EXCEL VERSION - FIXED HEADER ERRORS
 */

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// 2. Config & DB Paths
$configPath = __DIR__ . '/../config.php';
$dbPath = __DIR__ . '/../models/db.php';

if (file_exists($configPath)) { require_once $configPath; }
if (file_exists($dbPath)) { require_once $dbPath; }

$pdo = connectDB();

// 3. User & Permissions
$currentUserId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['user_role'] ?? '';
$isAdmin = ($userRole === 'admin' || ($_SESSION['role_id'] ?? 0) == 1);

if ($currentUserId == 0) { 
    // Clear buffer and exit
    ob_end_clean();
    die("Access Denied"); 
}

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// --- DATA FETCHING (Matched with reports.php) ---
$userFilter = $isAdmin ? "" : " AND wa.assigned_to_user_id = $currentUserId ";
$recruitFilter = $isAdmin ? "" : " AND submitted_by_user_id = $currentUserId ";

// 1. Tasks Query
$finQuery = "SELECT wa.*, cust.customer_name, cust.customer_phone, u.name as worker_name, c.name as category_name
             FROM work_assignments wa
             LEFT JOIN categories c ON wa.category_id = c.id
             LEFT JOIN users u ON wa.assigned_to_user_id = u.id
             LEFT JOIN customers cust ON wa.customer_id = cust.id
             WHERE wa.status = 'verified_completed' AND wa.completion_date BETWEEN ? AND ?
             $userFilter ORDER BY wa.completion_date DESC";
$stmt = $pdo->prepare($finQuery);
$stmt->execute([$startDate, $endDate]);
$taskData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Recruitment Query
$recSql = "SELECT * FROM recruitment_posts WHERE created_at BETWEEN ? AND ? $recruitFilter ORDER BY created_at DESC";
$stmt = $pdo->prepare($recSql);
$stmt->execute([$startDate.' 00:00:00', $endDate.' 23:59:59']);
$recData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Withdrawals Query
$withSql = "SELECT * FROM withdrawals WHERE requested_at BETWEEN ? AND ? " . ($isAdmin ? "" : " AND user_id=$currentUserId");
$stmt = $pdo->prepare($withSql);
$stmt->execute([$startDate.' 00:00:00', $endDate.' 23:59:59']);
$withData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rate Settings
$rate = 0;
if(!$isAdmin) {
    $rate = fetchColumn($pdo, "SELECT earning_per_approved_post FROM settings LIMIT 1") ?: 0;
}

// ---------------------------------------------------------
// EXCEL GENERATION START
// ---------------------------------------------------------

// Check if buffer exists before cleaning to prevent "Notice: ob_end_clean()"
if (ob_get_length()) {
    ob_end_clean();
}

$filename = "Report_" . date('d-M-Y') . ".xls";

// Force Headers for Download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

?>
<html xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        th { background: #4e73df; color: #ffffff; border: 1px solid #000000; text-align: center; font-weight: bold; }
        td { border: 1px solid #000000; text-align: center; vertical-align: middle; }
        .section-header { background: #f8f9fc; font-weight: bold; text-align: left; font-size: 14px; }
        .text-left { text-align: left; }
    </style>
</head>
<body>
    <h3><?= $isAdmin ? 'Admin Master Report' : 'My Performance Report' ?></h3>
    <p>From: <?= $startDate ?> To: <?= $endDate ?></p>

    <table>
        <tr><td colspan="<?= $isAdmin?8:6 ?>" class="section-header">1. Task Reports</td></tr>
        <tr>
            <th>Date</th><th>Task ID</th><th>Category</th><th>Customer</th>
            <?php if($isAdmin): ?><th>Worker</th><th>Fee (Revenue)</th><th>Pay (Expense)</th><?php else: ?><th>My Earning</th><?php endif; ?>
            <th>Payment Status</th>
        </tr>
        <?php foreach($taskData as $row): ?>
        <tr>
            <td><?= $row['completion_date'] ?></td>
            <td>#<?= $row['id'] ?></td>
            <td class="text-left"><?= $row['category_name'] ?></td>
            <td class="text-left"><?= $row['customer_name'] ?></td>
            <?php if($isAdmin): ?>
                <td class="text-left"><?= $row['worker_name'] ?></td>
                <td><?= $row['fee'] ?></td>
                <td><?= $row['task_price'] ?></td>
            <?php else: ?>
                <td><?= $row['task_price'] ?></td>
            <?php endif; ?>
            <td><?= $row['payment_collected_by']=='self' ? 'Cash Taken (Liability)' : 'Company Paid' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <br>

    <table>
        <tr><td colspan="4" class="section-header">2. Recruitment</td></tr>
        <tr><th>Date</th><th>Job Title</th><th>Status</th><th>Earning</th></tr>
        <?php foreach($recData as $p): $e = ($p['approval_status']=='approved' && !$isAdmin) ? $rate : '-'; ?>
        <tr>
            <td><?= $p['created_at'] ?></td>
            <td class="text-left"><?= $p['job_title'] ?></td>
            <td><?= $p['approval_status'] ?></td>
            <td><?= $e ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <br>

    <table>
        <tr><td colspan="3" class="section-header">3. Payments / Withdrawals</td></tr>
        <tr><th>Date</th><th>Amount</th><th>Status</th></tr>
        <?php foreach($withData as $w): ?>
        <tr>
            <td><?= $w['requested_at'] ?></td>
            <td><?= $w['amount'] ?></td>
            <td><?= $w['status'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

</body>
</html>
<?php exit; ?>