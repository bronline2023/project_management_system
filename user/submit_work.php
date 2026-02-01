<?php
/**
 * user/submit_work.php
 * Handles the submission of completed work by DEO or Freelancer roles.
 * FINAL & COMPLETE.
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
    // Fetch task details, including client information for display
    $task = fetchOne($pdo, "
        SELECT 
            wa.*, cu.customer_name, cl.client_name, cl.phone as client_phone,
            cat.name as category_name, sub.name as subcategory_name
        FROM work_assignments wa
        LEFT JOIN customers cu ON wa.customer_id = cu.id
        LEFT JOIN clients cl ON wa.client_id = cl.id
        JOIN categories cat ON wa.category_id = cat.id
        JOIN subcategories sub ON wa.subcategory_id = sub.id
        WHERE wa.id = ? AND wa.assigned_to_user_id = ?
    ", [$taskId, $currentUserId]);

    if (!$task) {
        $message = '<div class="alert alert-danger">Task not found or you do not have permission to submit this work.</div>';
    }
} else {
     $message = '<div class="alert alert-danger">No Task ID provided.</div>';
}
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '$');
?>

<h2 class="mb-4">Submit Work for Verification</h2>

<?php if ($message) { include VIEWS_PATH . 'components/message_box.php'; } ?>

<?php if ($task): ?>
<div class="card shadow-sm rounded-3">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Submission for Task #<?= htmlspecialchars($task['id']) ?></h5>
    </div>
    <div class="card-body p-4">
        <div class="row mb-3">
            <div class="col-md-6"><strong>Customer:</strong> <?= htmlspecialchars($task['customer_name'] ?? 'N/A') ?></div>
            <div class="col-md-6"><strong>Client:</strong> <?= htmlspecialchars($task['client_name'] ?? 'N/A') ?></div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6"><strong>Service:</strong> <?= htmlspecialchars($task['category_name']) ?> - <?= htmlspecialchars($task['subcategory_name']) ?></div>
            <div class="col-md-6"><strong>Deadline:</strong> <span class="badge bg-danger"><?= date('F j, Y', strtotime($task['deadline'])) ?></span></div>
        </div>
        <div class="mb-3">
            <strong>Description:</strong>
            <p><?= nl2br(htmlspecialchars($task['work_description'])) ?></p>
        </div>
        <hr>
        <form action="index.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit_for_verification">
            <input type="hidden" name="page" value="my_freelancer_tasks">
            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
            
            <div class="mb-3">
                <label for="completion_receipt" class="form-label">Attach Completion Receipt <span class="text-danger">*</span></label>
                <input type="file" class="form-control" name="completion_receipt" id="completion_receipt" required>
            </div>
            <div class="mb-3">
                <label for="user_notes" class="form-label">Additional Notes for Admin</label>
                <textarea class="form-control" name="user_notes" id="user_notes" rows="3"></textarea>
            </div>
            
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-upload me-2"></i>Submit for Verification</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>