<?php
/**
 * views/print_bill.php
 *
 * This file generates a printable bill/invoice for a specific work assignment (task).
 * It fetches task details, client information, and financial data from the database.
 *
 * It ensures that only authenticated users can access it and only if they are
 * an admin, manager, accountant, or the user to whom the task was assigned.
 */

// Include the configuration file for database connection and session management.
// Path to config.php: from views/print_bill.php, it's ../config.php
require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';   // Database interaction functions
require_once MODELS_PATH . 'auth.php'; // Authentication functions

// Restrict access to logged-in users only.
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB(); // Establish database connection
$current_user_id = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? 'guest';

$task_id = $_GET['task_id'] ?? null;
$task = null;
$message = '';

// Check if task_id is provided and is a valid integer
if (!$task_id || !is_numeric($task_id)) {
    $message = '<div class="alert alert-danger" role="alert">Invalid task ID provided.</div>';
    $task_id = null; // Invalidate task ID for further processing
} else {
    try {
        // Fetch task details, joining with related tables
        $stmt = $pdo->prepare("
            SELECT
                wa.id,
                wa.work_description,
                wa.deadline,
                wa.fee,
                wa.fee_mode,
                wa.maintenance_fee,
                wa.maintenance_fee_mode,
                wa.status,
                wa.payment_status,
                wa.created_at AS assigned_at,
                wa.completed_at,
                wa.admin_notes,
                wa.user_notes,
                cl.client_name,
                cl.contact_person,
                cl.email AS client_email,
                cl.phone AS client_phone,
                cl.address AS client_address,
                cl.company AS client_company,
                assigned_user.name AS assigned_to_user_name,
                assigned_user.role AS assigned_to_user_role,
                cat.name AS category_name,
                sub.name AS subcategory_name
            FROM
                work_assignments wa
            JOIN
                clients cl ON wa.client_id = cl.id
            JOIN
                users assigned_user ON wa.assigned_to_user_id = assigned_user.id
            JOIN
                categories cat ON wa.category_id = cat.id
            JOIN
                subcategories sub ON wa.subcategory_id = sub.id
            WHERE
                wa.id = :task_id
        ");
        $stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
        $stmt->execute();
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            $message = '<div class="alert alert-danger" role="alert">Task not found.</div>';
            $task_id = null; // Invalidate
        } else {
            // --- Authorization Check ---
            // Allowed roles: admin, manager, accountant, AND the user assigned to this task
            $allowedToViewBill = false;
            if ($userRole === 'admin' || $userRole === 'manager' || $userRole === 'accountant') {
                $allowedToViewBill = true;
            } elseif ($current_user_id === $task['assigned_to_user_name']) { // Assuming user_id is the actual ID, not name
                 // Corrected check: assigned_to_user_id from DB matches $_SESSION['user_id']
                if (isset($_SESSION['user_id']) && $task['assigned_to_user_id'] == $_SESSION['user_id']) {
                     $allowedToViewBill = true;
                 } else {
                     // Fallback for older code where assigned_to_user_name was used for comparison
                     error_log("DEBUG: print_bill.php - User ID mismatch for bill access. Session User ID: " . $_SESSION['user_id'] . ", Task Assigned User ID (from DB): " . $task['assigned_to_user_id']);
                 }
            }

            if (!$allowedToViewBill) {
                // If the user is not authorized, redirect to login or dashboard
                header('Location: ' . BASE_URL . '?page=login&msg=not_authorized_bill');
                exit;
            }
            // Corrected Authorization Check (assuming assigned_to_user_id is the INT ID from work_assignments table)
            if (!($userRole === 'admin' || $userRole === 'manager' || $userRole === 'accountant' || ($current_user_id == $task['assigned_to_user_id']))) {
                 header('Location: ' . BASE_URL . '?page=home&msg=not_authorized'); // Redirect to home or 403 page
                 exit;
            }

            // Calculate totals
            $subtotal = $task['fee'] + $task['maintenance_fee'];
            // You can add tax, discounts here if your system supports them
            $totalAmount = $subtotal; // For now, simple total
        }

    } catch (PDOException $e) {
        error_log("Error fetching task for bill: " . $e->getMessage());
        $message = '<div class="alert alert-danger" role="alert">Database error: Could not load bill details.</div>';
        $task_id = null; // Invalidate
    }
}

// Get currency symbol from settings for display
$currencySymbol = CURRENCY_SYMBOL; // Already defined in config.php

// Fetch app logo and name from settings for the bill header
$app_logo_url = '';
$app_name_setting = APP_NAME; // Default value from config
try {
    $stmt = $pdo->query("SELECT app_name, app_logo_url FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings) {
        $app_name_setting = htmlspecialchars($settings['app_name'] ?? APP_NAME);
        $app_logo_url = htmlspecialchars($settings['app_logo_url'] ?? '');
    }
} catch (PDOException $e) {
    error_log("Error fetching app settings for bill: " . $e->getMessage());
    // Fallback to default names already initialized
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill for Task #<?= htmlspecialchars($task_id ?? 'N/A') ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
            padding: 20px;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            position: relative;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
        }
        .invoice-header .logo-section {
            display: flex;
            align-items: center;
        }
        .invoice-header .logo {
            max-height: 60px;
            margin-right: 15px;
            border-radius: 5px;
        }
        .invoice-header h1 {
            color: #007bff;
            font-weight: 700;
            margin: 0;
        }
        .invoice-header .invoice-details {
            text-align: right;
        }
        .invoice-header .invoice-details h4 {
            margin-bottom: 5px;
            color: #495057;
        }
        .invoice-header .invoice-details p {
            margin-bottom: 0;
            font-size: 0.9em;
            color: #6c757d;
        }
        .section-title {
            background-color: #e9ecef;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 600;
            color: #495057;
        }
        .client-info, .task-details, .payment-info {
            margin-bottom: 25px;
        }
        .client-info p, .task-details p, .payment-info p {
            margin-bottom: 5px;
        }
        .table th, .table td {
            vertical-align: middle;
            font-size: 0.95em;
        }
        .table th {
            background-color: #f1f3f5;
        }
        .total-section {
            border-top: 2px solid #e9ecef;
            padding-top: 20px;
            margin-top: 30px;
            text-align: right;
        }
        .total-section h4 {
            margin-top: 10px;
            font-weight: 700;
            color: #28a745;
        }
        .footer-notes {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #ced4da;
            font-size: 0.85em;
            color: #6c757d;
        }
        .btn-print {
            margin-top: 30px;
            width: 100%;
            padding: 12px;
            font-size: 1.1em;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
            transition: all 0.3s ease;
        }
        .btn-print:hover {
            box-shadow: 0 6px 12px rgba(0, 123, 255, 0.3);
            transform: translateY(-2px);
        }

        /* Print specific styles */
        @media print {
            body {
                background-color: #fff;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                border-radius: 0;
                border: 1px solid #ccc;
            }
            .btn-print, .alert {
                display: none; /* Hide buttons and alerts when printing */
            }
            .invoice-header, .section-title, .total-section {
                border-color: #adb5bd !important;
            }
        }
    </style>
</head>
<body>

    <div class="invoice-container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-danger mb-4" role="alert">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($task): ?>
            <div class="invoice-header">
                <div class="logo-section">
                    <?php if (!empty($app_logo_url)): ?>
                        <img src="<?= $app_logo_url ?>" alt="<?= $app_name_setting ?> Logo" class="logo">
                    <?php else: ?>
                        <i class="fas fa-project-diagram fa-3x text-primary me-2"></i>
                    <?php endif; ?>
                    <h1><?= $app_name_setting ?></h1>
                </div>
                <div class="invoice-details">
                    <h4>INVOICE #<?= htmlspecialchars($task['id']) ?></h4>
                    <p>Date: <?= date('Y-m-d') ?></p>
                    <p>Task Assigned: <?= date('Y-m-d', strtotime($task['assigned_at'])) ?></p>
                    <?php if ($task['status'] === 'completed' && $task['completed_at']): ?>
                        <p>Completed On: <?= date('Y-m-d', strtotime($task['completed_at'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 client-info">
                    <div class="section-title">Client Details</div>
                    <p><strong>Name:</strong> <?= htmlspecialchars($task['client_name']) ?></p>
                    <p><strong>Contact Person:</strong> <?= htmlspecialchars($task['contact_person']) ?></p>
                    <p><strong>Company:</strong> <?= htmlspecialchars($task['client_company']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($task['client_email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($task['client_phone']) ?></p>
                    <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($task['client_address'])) ?></p>
                </div>
                <div class="col-md-6 task-details">
                    <div class="section-title">Task Details</div>
                    <p><strong>Category:</strong> <?= htmlspecialchars($task['category_name']) ?></p>
                    <p><strong>Subcategory:</strong> <?= htmlspecialchars($task['subcategory_name']) ?></p>
                    <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($task['work_description'])) ?></p>
                    <p><strong>Deadline:</strong> <?= date('Y-m-d', strtotime($task['deadline'])) ?></p>
                    <p><strong>Assigned To:</strong> <?= htmlspecialchars($task['assigned_to_user_name']) ?> (<?= ucwords(htmlspecialchars(str_replace('_', ' ', $task['assigned_to_user_role']))) ?>)</p>
                    <p><strong>Task Status:</strong> <span class="badge bg-info"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $task['status']))) ?></span></p>
                </div>
            </div>

            <div class="payment-info">
                <div class="section-title">Financial Summary</div>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Payment Status</th>
                            <th class="text-center">Payment Mode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Service Fee (<?= htmlspecialchars($task['category_name']) ?> - <?= htmlspecialchars($task['subcategory_name']) ?>)</td>
                            <td class="text-end"><?= $currencySymbol ?><?= number_format($task['fee'], 2) ?></td>
                            <td class="text-center"><span class="badge bg-primary"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $task['payment_status']))) ?></span></td>
                            <td class="text-center"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $task['fee_mode']))) ?></td>
                        </tr>
                        <?php if ($task['maintenance_fee'] > 0): ?>
                            <tr>
                                <td>Maintenance Fee</td>
                                <td class="text-end"><?= $currencySymbol ?><?= number_format($task['maintenance_fee'], 2) ?></td>
                                <td class="text-center"><span class="badge bg-secondary"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $task['maintenance_fee_mode']))) ?></span></td>
                                <td class="text-center"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $task['maintenance_fee_mode']))) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="total-section">
                    <h4>Total Amount: <?= $currencySymbol ?><?= number_format($totalAmount, 2) ?></h4>
                </div>
            </div>

            <?php if (!empty($task['admin_notes']) || !empty($task['user_notes'])): ?>
            <div class="footer-notes">
                <p><strong>Notes:</strong></p>
                <?php if (!empty($task['admin_notes'])): ?>
                    <p>Admin Notes: <?= nl2br(htmlspecialchars($task['admin_notes'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($task['user_notes'])): ?>
                    <p>User Notes: <?= nl2br(htmlspecialchars($task['user_notes'])) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <button class="btn btn-primary btn-print mt-4" onclick="window.print()"><i class="fas fa-print me-2"></i>Print Bill</button>

        <?php endif; ?>
    </div>

    <!-- Bootstrap JS (optional, for modal functionality if any is added later) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
