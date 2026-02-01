<?php
/**
 * views/print_bill.php
 * FINAL & COMPLETE: 
 * - Redesigned to include a Discount field.
 * - Calculates and displays the final Grand Total.
 */

require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB();
$task_id = $_GET['task_id'] ?? null;
$task = null;
$message = '';

if ($task_id && is_numeric($task_id)) {
    // Ensure the query fetches the new 'discount' column
    $task = fetchOne($pdo, "SELECT wa.*, cl.client_name, cl.phone as client_phone, cl.address as client_address, us.name AS user_name, cat.name AS category_name, sub.name AS subcategory_name FROM work_assignments wa JOIN clients cl ON wa.client_id = cl.id JOIN users us ON wa.assigned_to_user_id = us.id JOIN categories cat ON wa.category_id = cat.id JOIN subcategories sub ON wa.subcategory_id = sub.id WHERE wa.id = ?", [$task_id]);

    if (!$task) {
        $message = 'Task not found.';
    } elseif ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_id'] != $task['assigned_to_user_id']) {
        $task = null;
        $message = 'You are not authorized to view this bill.';
    }
} else {
    $message = 'Invalid Task ID.';
}

$settings = fetchOne($pdo, "SELECT app_name, app_logo_url, currency_symbol FROM settings LIMIT 1");
$app_name = htmlspecialchars($settings['app_name'] ?? APP_NAME);
$app_logo_url = htmlspecialchars($settings['app_logo_url'] ?? '');
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

// Calculations
if($task) {
    $subtotal = $task['fee'] + $task['maintenance_fee'];
    $discount = $task['discount'] ?? 0;
    $grandTotal = $subtotal - $discount;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= htmlspecialchars($task['id'] ?? 'N/A') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
        .invoice-container { max-width: 850px; margin: 40px auto; background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 40px; }
        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #0d6efd; padding-bottom: 20px; margin-bottom: 30px; }
        .invoice-header .logo { max-width: 150px; max-height: 80px; }
        .invoice-title h1 { margin: 0; font-size: 3rem; font-weight: 700; color: #6c757d; }
        .invoice-table thead { background-color: #0d6efd; color: #fff; }
        .invoice-summary { text-align: right; }
        .invoice-summary .summary-item { display: flex; justify-content: space-between; padding: 8px 0; }
        .invoice-summary .total { font-size: 1.5rem; font-weight: 700; color: #0d6efd; border-top: 2px solid #dee2e6; margin-top: 10px; }
        @media print { body { background-color: #fff; } .no-print { display: none; } .invoice-container { box-shadow: none; margin: 0; max-width: 100%; } }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($task): ?>
        <div class="invoice-container">
            <header class="invoice-header">
                <div class="company-details">
                    <?php if (!empty($app_logo_url)): ?><img src="<?= $app_logo_url ?>" alt="<?= $app_name ?>" class="logo mb-3"><?php endif; ?>
                    <h2><?= $app_name ?></h2>
                </div>
                <div class="invoice-title text-end">
                    <h1>INVOICE</h1>
                    <p class="mb-0"><strong>Invoice #:</strong> <?= str_pad($task['id'], 6, '0', STR_PAD_LEFT) ?></p>
                    <p class="mb-0"><strong>Date:</strong> <?= date('F j, Y', strtotime($task['completed_at'] ?? 'now')) ?></p>
                </div>
            </header>

            <div class="row mb-4">
                <div class="col-md-6 client-details">
                    <h5>Bill To:</h5>
                    <p class="fw-bold mb-0 fs-5"><?= htmlspecialchars($task['client_name']) ?></p>
                </div>
            </div>

            <table class="table table-bordered invoice-table">
                <thead><tr><th>Description</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    <tr><td><strong>Task Fee:</strong> <?= htmlspecialchars($task['category_name']) ?> - <?= htmlspecialchars($task['subcategory_name']) ?></td><td class="text-end"><?= $currencySymbol ?><?= number_format($task['fee'], 2) ?></td></tr>
                    <?php if ($task['maintenance_fee'] > 0): ?>
                    <tr><td><strong>Maintenance Fee</strong></td><td class="text-end"><?= $currencySymbol ?><?= number_format($task['maintenance_fee'], 2) ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="row justify-content-end">
                <div class="col-md-5">
                    <div class="invoice-summary">
                        <div class="summary-item"><span>Subtotal</span><span><?= $currencySymbol ?><?= number_format($subtotal, 2) ?></span></div>
                        <?php if ($discount > 0): ?>
                        <div class="summary-item text-danger"><span>Discount</span><span>- <?= $currencySymbol ?><?= number_format($discount, 2) ?></span></div>
                        <?php endif; ?>
                        <div class="summary-item total"><span>GRAND TOTAL</span><span><?= $currencySymbol ?><?= number_format($grandTotal, 2) ?></span></div>
                    </div>
                </div>
            </div>
            
            <footer class="invoice-footer mt-5 pt-4 border-top text-center text-muted"><p>Thank you for your business!</p></footer>
        </div>
        <div class="text-center my-4 no-print">
            <button onclick="window.print()" class="btn btn-primary btn-lg"><i class="fas fa-print me-2"></i>Print Invoice</button>
            <a href="<?= BASE_URL ?>?page=all_tasks" class="btn btn-secondary btn-lg">Back to All Tasks</a>
        </div>
        <?php else: ?>
        <div class="alert alert-danger mt-5"><?= $message ?></div>
        <?php endif; ?>
    </div>
</body>
</html>