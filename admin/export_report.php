<?php
/**
 * admin/export_report.php
 * Handles the export of various reports to CSV format.
 * This is a standalone script accessed directly, so it needs its own includes and auth checks.
 */

require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';

if (!isLoggedIn() || !in_array('reports', $_SESSION['user_permissions'] ?? [])) {
    die("Access Denied.");
}

$pdo = connectDB();
$report_type = $_GET['type'] ?? '';
$time_period = $_GET['time_period'] ?? 'all';
$search_query = trim($_GET['search'] ?? '');

$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = $settings['currency_symbol'] ?? '$';

function getExportDateClause($period, $date_field = 'created_at') {
    switch ($period) {
        case 'daily': return "DATE($date_field) = CURDATE()";
        case 'weekly': return "$date_field >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
        case 'monthly': return "YEAR($date_field) = YEAR(CURDATE()) AND MONTH($date_field) = MONTH(CURDATE())";
        case 'yearly': return "YEAR($date_field) = YEAR(CURDATE())";
        default: return "1=1";
    }
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $report_type . '_report_' . date('Y-m-d') . '.csv"');
$output = fopen('php://output', 'w');

switch ($report_type) {
    case 'revenue':
        fputcsv($output, ['Task ID', 'Client', 'Assigned To', 'Fee', 'Maintenance Fee', 'Total Paid', 'Completed On']);
        $dateClause = getExportDateClause($time_period, 'wa.completed_at');
        $sql = "SELECT wa.id, cl.client_name, us.name, wa.fee, wa.maintenance_fee, wa.completed_at FROM work_assignments wa JOIN clients cl ON wa.client_id = cl.id JOIN users us ON wa.assigned_to_user_id = us.id WHERE wa.status = 'completed' AND $dateClause ORDER BY wa.completed_at DESC";
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$row['id'], $row['client_name'], $row['name'], $row['fee'], $row['maintenance_fee'], ($row['fee'] - $row['maintenance_fee']), $row['completed_at']]);
        }
        break;

    case 'expenses':
        fputcsv($output, ['ID', 'Type', 'Amount', 'Description', 'Date']);
        $dateClause = getExportDateClause($time_period, 'expense_date');
        $sql = "SELECT id, expense_type, amount, description, expense_date FROM expenses WHERE $dateClause ORDER BY expense_date DESC";
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
        break;
    
    // Add other report types as needed
    default:
        fputcsv($output, ['Error', 'Invalid report type specified.']);
        break;
}

fclose($output);
exit;