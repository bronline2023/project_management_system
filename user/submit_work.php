<?php
/**
 * user/submit_work.php
 * Handles the submission of completed work.
 * FINAL: Includes "Payment Collected By" option for balance calculation.
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
    // Fetch task details including fees to show user
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
<div class="card shadow-sm rounded-3">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Complete Task #<?= htmlspecialchars($task['id']) ?></h5>
    </div>
    <div class="card-body p-4">
        
        <div class="alert alert-light border">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Customer:</strong> <?= htmlspecialchars($task['customer_name']) ?></p>
                    <p><strong>Total Fees (From Customer):</strong> <span class="text-primary font-weight-bold"><?= $currencySymbol . $task['fee'] ?></span></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Your Fee (Earnings):</strong> <span class="text-success font-weight-bold"><?= $currencySymbol . $task['task_price'] ?></span></p>
                    <p><strong>Company Share:</strong> <?= $currencySymbol . ($task['fee'] - $task['task_price']) ?></p>
                </div>
            </div>
        </div>

        <form action="index.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit_for_verification">
            <input type="hidden" name="page" value="my_freelancer_tasks">
            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
            
            <div class="row">
                <div class="col-md-12 mb-4">
                    <label class="form-label font-weight-bold">Who collected the payment from the customer? / પેમેન્ટ કોણે લીધું?</label>
                    <select name="payment_collected_by" class="form-select" required>
                        <option value="">-- Select Option --</option>
                        <option value="company">Company (Directly Paid to Company / Online)</option>
                        <option value="self">Self (I collected Cash/UPI from Customer)</option>
                    </select>
                    <small class="text-muted d-block mt-1">
                        <ul>
                            <li><strong>Company:</strong> You will receive <strong>+<?= $currencySymbol . $task['task_price'] ?></strong> in your wallet.</li>
                            <li><strong>Self:</strong> Company share <strong>-<?= $currencySymbol . ($task['fee'] - $task['task_price']) ?></strong> will be deducted from your wallet.</li>
                        </ul>
                    </small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="work_file" class="form-label">Upload Work File (Zip/PDF) <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" name="work_file" id="work_file">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="completion_receipt" class="form-label">Upload Payment/Bill Receipt <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" name="completion_receipt" id="completion_receipt" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="user_notes" class="form-label">Remarks / Notes</label>
                <textarea class="form-control" name="user_notes" id="user_notes" rows="3" placeholder="Enter transaction ID or any notes..."></textarea>
            </div>
            
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-paper-plane me-2"></i>Submit & Finish</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>