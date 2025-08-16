<?php
/**
 * admin/export_report.php
 *
 * This file handles the export of various detailed reports (revenue, expenses, tasks, maintenance)
 * to CSV format, which can be opened in Excel.
 *
 * It receives the report type, time period, and search query via GET parameters.
 * It ensures that only authenticated admin users can access and trigger exports.
 */

// Include the configuration file for database connection and session management.
require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';   // Database interaction functions
require_once MODELS_PATH . 'auth.php'; // Authentication functions

// Restrict access to admin users only.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB(); // Establish database connection

$report_type = $_GET['type'] ?? '';
$time_period = $_GET['time_period'] ?? 'all';
$search_query = trim($_GET['search'] ?? '');

// Get currency symbol for display in reports
$currencySymbol = '$';
try {
    $stmt = $pdo->query("SELECT currency_symbol FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings && isset($settings['currency_symbol'])) {
        $currencySymbol = $settings['currency_symbol']; // No htmlspecialchars needed for CSV
    }
} catch (PDOException $e) {
    error_log("Error fetching currency symbol for export: " . $e->getMessage());
}

// Function to get date clauses (replicated from admin/reports.php)
function getDateClause($period, $date_field = 'created_at') {
    switch ($period) {
        case 'daily':
            return "DATE($date_field) = CURDATE()";
        case 'weekly':
            return "$date_field BETWEEN CURDATE() - INTERVAL (WEEKDAY(CURDATE()) + 7) % 7 DAY AND CURDATE() + INTERVAL (6 - (WEEKDAY(CURDATE()) + 7) % 7) DAY";
        case 'monthly':
            return "YEAR($date_field) = YEAR(CURDATE()) AND MONTH($date_field) = MONTH(CURDATE())";
        case 'yearly':
            return "YEAR($date_field) = YEAR(CURDATE())";
        case 'all':
        default:
            return "1=1";
    }
}

// Set CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $report_type . '_report_' . date('Y-m-d') . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

switch ($report_type) {
    case 'revenue':
        fputcsv($output, ['Task ID', 'Client', 'Assigned To', 'Category', 'Subcategory', 'Description', 'Fee', 'Maintenance Fee', 'Total Paid', 'Payment Status', 'Completed On']);

        $dateClause = getDateClause($time_period, 'wa.completed_at');
        $search_clause = '';
        if ($search_query) {
            $search_clause = "AND (cl.client_name LIKE :search_query OR wa.work_description LIKE :search_query OR cat.name LIKE :search_query OR sub.name LIKE :search_query)";
        }

        $sql = "SELECT wa.id, cl.client_name AS client_name, us.name AS user_name,
                       cat.name AS category_name, sub.name AS subcategory_name,
                       wa.work_description, wa.fee, wa.maintenance_fee, wa.payment_status, wa.completed_at
                FROM work_assignments wa
                JOIN clients cl ON wa.client_id = cl.id
                JOIN users us ON wa.assigned_to_user_id = us.id
                JOIN categories cat ON wa.category_id = cat.id
                JOIN subcategories sub ON wa.subcategory_id = sub.id
                WHERE wa.status = 'completed' AND $dateClause $search_clause
                ORDER BY wa.completed_at DESC";

        $stmt = $pdo->prepare($sql);
        if ($search_query) {
            $searchTerm = '%' . $search_query . '%';
            $stmt->bindParam(':search_query', $searchTerm, PDO::PARAM_STR);
        }
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $total_paid = $row['fee'] - $row['maintenance_fee'];
            fputcsv($output, [
                $row['id'],
                $row['client_name'],
                $row['user_name'],
                $row['category_name'],
                $row['subcategory_name'],
                $row['work_description'],
                $currencySymbol . number_format($row['fee'], 2),
                $currencySymbol . number_format($row['maintenance_fee'], 2),
                $currencySymbol . number_format($total_paid, 2),
                ucwords(str_replace('_', ' ', $row['payment_status'])),
                date('Y-m-d H:i', strtotime($row['completed_at']))
            ]);
        }
        break;

    case 'expenses':
        fputcsv($output, ['Expense ID', 'Type', 'Amount', 'Description', 'Date', 'Recorded At']);

        $dateClause = getDateClause($time_period, 'expense_date');
        $search_clause = '';
        if ($search_query) {
            $search_clause = "AND (expense_type LIKE :search_query OR description LIKE :search_query)";
        }

        $sql = "SELECT id, expense_type, amount, description, expense_date, created_at
                FROM expenses
                WHERE $dateClause $search_clause
                ORDER BY expense_date DESC, created_at DESC";

        $stmt = $pdo->prepare($sql);
        if ($search_query) {
            $searchTerm = '%' . $search_query . '%';
            $stmt->bindParam(':search_query', $searchTerm, PDO::PARAM_STR);
        }
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['expense_type'],
                $currencySymbol . number_format($row['amount'], 2),
                $row['description'],
                date('Y-m-d', strtotime($row['expense_date'])),
                date('Y-m-d H:i', strtotime($row['created_at']))
            ]);
        }
        break;

    case 'tasks':
        fputcsv($output, ['Task ID', 'Client', 'Assigned To', 'Category', 'Subcategory', 'Description', 'Deadline', 'Status', 'Payment Status', 'Created On']);

        $dateClause = getDateClause($time_period, 'wa.created_at');
        $search_clause = '';
        if ($search_query) {
            $search_clause = "AND (cl.client_name LIKE :search_query OR us.name LIKE :search_query OR wa.work_description LIKE :search_query OR cat.name LIKE :search_query OR sub.name LIKE :search_query)";
        }

        $sql = "SELECT wa.id, cl.client_name AS client_name, us.name AS user_name,
                       cat.name AS category_name, sub.name AS subcategory_name,
                       wa.work_description, wa.deadline, wa.status, wa.payment_status, wa.created_at
                FROM work_assignments wa
                JOIN clients cl ON wa.client_id = cl.id
                JOIN users us ON wa.assigned_to_user_id = us.id
                JOIN categories cat ON wa.category_id = cat.id
                JOIN subcategories sub ON wa.subcategory_id = sub.id
                WHERE $dateClause $search_clause
                ORDER BY wa.created_at DESC";

        $stmt = $pdo->prepare($sql);
        if ($search_query) {
            $searchTerm = '%' . $search_query . '%';
            $stmt->bindParam(':search_query', $searchTerm, PDO::PARAM_STR);
        }
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['client_name'],
                $row['user_name'],
                $row['category_name'],
                $row['subcategory_name'],
                $row['work_description'],
                date('Y-m-d', strtotime($row['deadline'])),
                ucwords(str_replace('_', ' ', $row['status'])),
                ucwords(str_replace('_', ' ', $row['payment_status'])),
                date('Y-m-d H:i', strtotime($row['created_at']))
            ]);
        }
        break;

    case 'maintenance':
        fputcsv($output, ['Task ID', 'Client', 'Assigned To', 'Category', 'Subcategory', 'Work Description', 'Maintenance Fee', 'Maintenance Fee Mode', 'Status', 'Payment Status', 'Created On', 'Completed On']);

        $dateClause = getDateClause($time_period, 'wa.created_at'); // Assuming maintenance tasks are reported based on creation date
        $search_clause = '';
        if ($search_query) {
            $search_clause = "AND (cl.client_name LIKE :search_query OR us.name LIKE :search_query OR wa.work_description LIKE :search_query OR cat.name LIKE :search_query OR sub.name LIKE :search_query)";
        }

        $sql = "SELECT wa.id, cl.client_name AS client_name, us.name AS user_name,
                       cat.name AS category_name, sub.name AS subcategory_name,
                       wa.work_description, wa.maintenance_fee, wa.maintenance_fee_mode,
                       wa.status, wa.payment_status, wa.created_at, wa.completed_at
                FROM work_assignments wa
                JOIN clients cl ON wa.client_id = cl.id
                JOIN users us ON wa.assigned_to_user_id = us.id
                JOIN categories cat ON wa.category_id = cat.id
                JOIN subcategories sub ON wa.subcategory_id = sub.id
                WHERE wa.maintenance_fee > 0 AND $dateClause $search_clause
                ORDER BY wa.created_at DESC";

        $stmt = $pdo->prepare($sql);
        if ($search_query) {
            $searchTerm = '%' . $search_query . '%';
            $stmt->bindParam(':search_query', $searchTerm, PDO::PARAM_STR);
        }
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['client_name'],
                $row['user_name'],
                $row['category_name'],
                $row['subcategory_name'],
                $row['work_description'],
                $currencySymbol . number_format($row['maintenance_fee'], 2),
                ucwords(str_replace('_', ' ', $row['maintenance_fee_mode'])),
                ucwords(str_replace('_', ' ', $row['status'])),
                ucwords(str_replace('_', ' ', $row['payment_status'])),
                date('Y-m-d H:i', strtotime($row['created_at'])),
                $row['completed_at'] ? date('Y-m-d H:i', strtotime($row['completed_at'])) : 'N/A'
            ]);
        }
        break;

    default:
        // Handle invalid report type
        fputcsv($output, ['Error', 'Invalid report type specified.']);
        break;
}

fclose($output);
exit; // Important to exit after outputting the file
