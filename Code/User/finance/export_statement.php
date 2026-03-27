<?php
/**
 * user/export_statement.php
 * FINAL DESIGNED VERSION
 * Features:
 * 1. Beautiful Excel Design (HTML Table style) - Restored from old version.
 * 2. Accurate Calculations - Synced with Worker Dashboard logic.
 * 3. Detailed Summary Table (Earnings vs Liabilities vs Withdrawals).
 */

// 1. Setup & Auth
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../config.php';
require_once MODELS_PATH . 'db.php';

$pdo = connectDB();

if (!isset($_SESSION['user_id'])) { die("Access Denied"); }

$requestUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$currentUserId = $_SESSION['user_id'];
$currentUserRole = isset($_SESSION['user_role']) ? strtolower($_SESSION['user_role']) : '';

if ($requestUserId !== $currentUserId && $currentUserRole !== 'admin') {
    die("Unauthorized Access");
}

$userId = ($requestUserId > 0) ? $requestUserId : $currentUserId;

// 2. Fetch User & Settings
$user = fetchOne($pdo, "SELECT name, email FROM users WHERE id = ?", [$userId]);
$userName = $user['name'] ?? 'User';

$settings = fetchOne($pdo, "SELECT earning_per_approved_post, currency_symbol FROM settings LIMIT 1");
$ratePerPost = (float)($settings['earning_per_approved_post'] ?? 0);
$currency = $settings['currency_symbol'] ?? '₹';

// ==========================================
// 3. PERFORM CALCULATIONS (MATCHING DASHBOARD)
// ==========================================

// A. Recruitment Earnings
$postStats = fetchOne($pdo, "SELECT COUNT(*) as total, SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved FROM recruitment_posts WHERE submitted_by_user_id = ?", [$userId]);
$approvedPosts = (int)($postStats['approved'] ?? 0);
$totalPosts = (int)($postStats['total'] ?? 0);
$recruitmentEarnings = $approvedPosts * $ratePerPost;

// B. Task Earnings & Cash Collection
$tasks = fetchAll($pdo, "
    SELECT wa.id, wa.completion_date, wa.task_price, wa.fee, wa.payment_collected_by, 
           c.customer_name, cl.client_name, wa.status
    FROM work_assignments wa 
    LEFT JOIN customers c ON wa.customer_id = c.id
    LEFT JOIN clients cl ON wa.client_id = cl.id
    WHERE wa.assigned_to_user_id = ? AND wa.status = 'verified_completed' 
    ORDER BY wa.completion_date DESC
", [$userId]);

$taskEarnings = 0;
$selfCollected = 0;

foreach ($tasks as $t) {
    $taskEarnings += (float)$t['task_price'];
    if ($t['payment_collected_by'] === 'self') {
        $selfCollected += (float)$t['fee'];
    }
}

// C. Withdrawals
$withdrawals = fetchAll($pdo, "SELECT amount, requested_at, status FROM withdrawals WHERE user_id = ? ORDER BY requested_at DESC", [$userId]);
$totalWithdrawals = 0;
foreach ($withdrawals as $w) {
    // Include Approved AND Pending (because pending is deducted from balance)
    if ($w['status'] !== 'rejected') {
        $totalWithdrawals += (float)$w['amount'];
    }
}

// D. Final Net Balance
$totalEarnings = $recruitmentEarnings + $taskEarnings;
$netBalance = $totalEarnings - $selfCollected - $totalWithdrawals;

// ==========================================
// 4. GENERATE EXCEL HTML
// ==========================================

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Account_Statement_" . str_replace(' ', '_', $userName) . "_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: center; vertical-align: middle; }
        th { background-color: #4e73df; color: white; font-weight: bold; }
        .bg-sub-header { background-color: #eaecf4; color: #333; font-weight: bold; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-green { color: #1cc88a; font-weight: bold; }
        .text-red { color: #e74a3b; font-weight: bold; }
        .text-blue { color: #4e73df; font-weight: bold; }
        h3 { border-bottom: 2px solid #333; padding-bottom: 5px; margin-top: 30px; }
        .total-row { background-color: #ffffcc; font-weight: bold; font-size: 1.1em; }
    </style>
</head>
<body>
    <h2>Account Statement - <?= htmlspecialchars($userName) ?></h2>
    <p><strong>Generated on:</strong> <?= date('d M Y, h:i A') ?></p>

    <h3>1. Balance Summary (Live Calculation)</h3>
    <table border="1">
        <thead>
            <tr>
                <th class="bg-sub-header text-left" width="50%">Description</th>
                <th class="bg-sub-header text-right" width="25%">Type</th>
                <th class="bg-sub-header text-right" width="25%">Amount (<?= $currency ?>)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-left">Recruitment Earnings (<?= $approvedPosts ?> Posts)</td>
                <td class="text-right text-green">Credit (+)</td>
                <td class="text-right text-green"><?= number_format($recruitmentEarnings, 2) ?></td>
            </tr>
            <tr>
                <td class="text-left">Task Earnings (Freelance Work)</td>
                <td class="text-right text-green">Credit (+)</td>
                <td class="text-right text-green"><?= number_format($taskEarnings, 2) ?></td>
            </tr>
            <tr>
                <td class="text-left">Less: Cash Collected by Self (From Customers)</td>
                <td class="text-right text-red">Debit (-)</td>
                <td class="text-right text-red">-<?= number_format($selfCollected, 2) ?></td>
            </tr>
            <tr>
                <td class="text-left">Less: Total Withdrawals (Inc. Pending)</td>
                <td class="text-right text-red">Debit (-)</td>
                <td class="text-right text-red">-<?= number_format($totalWithdrawals, 2) ?></td>
            </tr>
            <tr class="total-row">
                <td class="text-left"><strong>NET WALLET BALANCE</strong></td>
                <td class="text-right"><strong>=</strong></td>
                <td class="text-right" style="color: <?= $netBalance < 0 ? 'red' : 'black' ?>;">
                    <strong><?= number_format($netBalance, 2) ?></strong>
                </td>
            </tr>
        </tbody>
    </table>

    <h3>2. Task Income & Liabilities</h3>
    <table border="1">
        <thead>
            <tr>
                <th>Date</th>
                <th>Task ID</th>
                <th>Client/Customer</th>
                <th>Payment Mode</th>
                <th>Total Bill</th>
                <th>My Earning</th>
                <th>Cash Taken</th>
                <th>Net Effect</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($tasks)): ?>
                <?php foreach ($tasks as $task): 
                    $earning = (float)$task['task_price'];
                    $collected = ($task['payment_collected_by'] === 'self') ? (float)$task['fee'] : 0;
                    $netEffect = $earning - $collected;
                    $customerName = !empty($task['customer_name']) ? $task['customer_name'] : $task['client_name'];
                ?>
                <tr>
                    <td><?= date('d-m-Y', strtotime($task['completion_date'])) ?></td>
                    <td>#<?= $task['id'] ?></td>
                    <td class="text-left"><?= htmlspecialchars($customerName) ?></td>
                    <td><?= ucfirst($task['payment_collected_by']) ?></td>
                    <td><?= number_format($task['fee'], 2) ?></td>
                    <td class="text-green">+<?= number_format($earning, 2) ?></td>
                    <td class="text-red"><?= $collected > 0 ? '-' . number_format($collected, 2) : '-' ?></td>
                    <td style="color: <?= $netEffect >= 0 ? 'green' : 'red' ?>; font-weight:bold;">
                        <?= number_format($netEffect, 2) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8">No completed tasks found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3>3. Recruitment Stats</h3>
    <table border="1">
        <thead>
            <tr>
                <th>Total Submitted</th>
                <th>Approved</th>
                <th>Rate Per Post</th>
                <th>Total Earned</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= $totalPosts ?></td>
                <td><?= $approvedPosts ?></td>
                <td><?= number_format($ratePerPost, 2) ?></td>
                <td class="text-green font-weight-bold"><?= number_format($recruitmentEarnings, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <h3>4. Withdrawal History</h3>
    <table border="1">
        <thead>
            <tr>
                <th>Date</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($withdrawals)): ?>
                <?php foreach ($withdrawals as $wd): ?>
                <tr>
                    <td><?= date('d-m-Y', strtotime($wd['requested_at'])) ?></td>
                    <td class="text-red">-<?= number_format($wd['amount'], 2) ?></td>
                    <td><?= ucfirst($wd['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3">No withdrawal history found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>