<?php
/**
 * user/submit_work.php
 * Updated: Shows Fee Details, Payment Mode, and Work File Upload.
 */
$pdo = connectDB();
$message = '';
$currentUserId = $_SESSION['user_id'];
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$task = null;

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

if ($taskId > 0) {
    // Fetch task details including fees
    $task = fetchOne($pdo, "
        SELECT 
            wa.*, cu.customer_name, cl.client_name,
            cat.name as category_name, sub.name as subcategory_name
        FROM work_assignments wa
        LEFT JOIN customers cu ON wa.customer_id = cu.id
        LEFT JOIN clients cl ON wa.client_id = cl.id
        JOIN categories cat ON wa.category_id = cat.id
        JOIN subcategories sub ON wa.subcategory_id = sub.id
        WHERE wa.id = ? AND wa.assigned_to_user_id = ?
    ", [$taskId, $currentUserId]);

    if (!$task) {
        $message = '<div class="alert alert-danger">Task not found or permission denied.</div>';
    }
} else {
     $message = '<div class="alert alert-danger">No Task ID provided.</div>';
}
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');
?>

<h2 class="mb-4">Submit Work & Payment Details</h2>

<?php if ($message) { include VIEWS_PATH . 'components/message_box.php'; } ?>

<?php if ($task): ?>
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <h5 class="font-weight-bold text-primary mb-3">Billing Information</h5>
                <div class="row">
                    <div class="col-md-4">
                        <label class="small font-weight-bold text-gray-500">Customer Name</label>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($task['customer_name']) ?></div>
                    </div>
                    <div class="col-md-4 border-left">
                        <label class="small font-weight-bold text-gray-500">Total Amount to Collect (From Customer)</label>
                        <div class="h4 mb-0 font-weight-bold text-danger">
                            <?= $currencySymbol . number_format($task['fee'], 2) ?>
                        </div>
                        <small class="text-muted">ગ્રાહક પાસેથી આટલી રકમ લેવાની છે</small>
                    </div>
                    <div class="col-md-4 border-left">
                        <label class="small font-weight-bold text-gray-500">Your Earning (Task Fee)</label>
                        <div class="h4 mb-0 font-weight-bold text-success">
                            <?= $currencySymbol . number_format($task['task_price'], 2) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card shadow-sm rounded-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Complete Task #<?= htmlspecialchars($task['id']) ?></h5>
            </div>
            <div class="card-body p-4">
                <form action="index.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="submit_for_verification">
                    <input type="hidden" name="page" value="my_freelancer_tasks">
                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                    
                    <div class="form-group mb-4 p-3 border rounded bg-light">
                        <label class="form-label font-weight-bold text-dark" style="font-size: 1.1em;">
                            Payment Collected By? / પેમેન્ટ કોણે લીધું? <span class="text-danger">*</span>
                        </label>
                        <select name="payment_collected_by" class="form-select" required>
                            <option value="">-- Select Option --</option>
                            <option value="company">Company (Customer paid Online/Direct to Office)</option>
                            <option value="self">Self (I collected Cash/UPI from Customer)</option>
                        </select>
                        <div class="mt-2 small text-muted">
                            <i class="fas fa-info-circle"></i> 
                            If you select <strong>'Self'</strong>, the Company Share (<?= $currencySymbol . ($task['fee'] - $task['task_price']) ?>) will be deducted from your wallet balance.<br>
                            જો તમે 'Self' પસંદ કરશો, તો કંપનીનો ભાગ તમારા વોલેટમાંથી કપાશે.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="work_file" class="form-label">Upload Work File (Final Output - Zip/PDF) <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="work_file" id="work_file" required>
                            <small class="text-muted">તમારા કામની ફાઇલ અહી અપલોડ કરો.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="completion_receipt" class="form-label">Upload Payment Receipt / Bill <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="completion_receipt" id="completion_receipt" required>
                            <small class="text-muted">પેમેન્ટની રસીદ અથવા સ્ક્રીનશોટ.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="user_notes" class="form-label">Remarks / Notes</label>
                        <textarea class="form-control" name="user_notes" id="user_notes" rows="3" placeholder="Enter transaction details or any notes..."></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-success btn-lg px-5"><i class="fas fa-paper-plane me-2"></i>Submit & Finish</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>