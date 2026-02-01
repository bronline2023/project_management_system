<?php
/**
 * user/update_freelancer_task.php
 * A detailed view for freelancers to see all task details, update status, and submit work with a receipt.
 * Allows freelancers to return tasks to admin if there are issues.
 * FINAL & COMPLETE: Corrected to display all task information and provide a consistent update workflow.
 */
require_once MODELS_PATH . 'notifications.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$task = null;

if ($taskId > 0) {
    // Query to fetch all necessary details
    $task = fetchOne($pdo, "
        SELECT 
            wa.*, cl.client_name, cl.phone as client_phone, cl.email as client_email,
            cat.name as category_name, sub.name as subcategory_name
        FROM work_assignments wa
        JOIN clients cl ON wa.client_id = cl.id
        JOIN categories cat ON wa.category_id = cat.id
        JOIN subcategories sub ON wa.subcategory_id = sub.id
        WHERE wa.id = ? AND wa.assigned_to_user_id = ?
    ", [$taskId, $currentUserId]);

    if (!$task) {
        $message = '<div class="alert alert-danger">Task not found or you do not have permission to view it.</div>';
    }
} else {
     $message = '<div class="alert alert-danger">No Task ID provided.</div>';
}

// All POST actions are now handled in index.php to prevent errors.
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

// Determine if the task is editable based on its status
$isEditable = in_array($task['status'], ['pending', 'in_process', 'returned']);
?>
<h2 class="mb-4">Task Details & Submission</h2>
<?php if ($message) { include VIEWS_PATH . 'components/message_box.php'; } ?>

<?php if ($task): ?>
<div class="card shadow-lg rounded-3">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Task #<?= htmlspecialchars($task['id']) ?></h5>
        <a href="<?= BASE_URL ?>?page=my_freelancer_tasks" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to My Tasks</a>
    </div>
    <div class="card-body p-4">
        <div class="row">
            <div class="col-lg-7 border-end pe-lg-4 mb-4 mb-lg-0">
                <h4>Task Information</h4>
                <dl class="row">
                    <dt class="col-sm-4">Client Name:</dt><dd class="col-sm-8 fw-bold"><?= htmlspecialchars($task['client_name']) ?></dd>
                    <dt class="col-sm-4">Client Phone:</dt><dd class="col-sm-8"><?= htmlspecialchars($task['client_phone'] ?? 'N/A') ?></dd>
                    <dt class="col-sm-4">Client Email:</dt><dd class="col-sm-8"><?= htmlspecialchars($task['client_email'] ?? 'N/A') ?></dd>
                    <hr class="my-2"><dt class="col-sm-4">Service:</dt><dd class="col-sm-8"><?= htmlspecialchars($task['category_name']) ?> - <?= htmlspecialchars($task['subcategory_name']) ?></dd>
                    <dt class="col-sm-4">Description:</dt><dd class="col-sm-8"><?= nl2br(htmlspecialchars($task['work_description'])) ?></dd>
                    <dt class="col-sm-4">Admin Notes:</dt><dd class="col-sm-8"><i><?= !empty($task['admin_notes']) ? nl2br(htmlspecialchars($task['admin_notes'])) : 'No notes from admin.' ?></i></dd>
                    <hr class="my-2"><dt class="col-sm-4">Deadline:</dt><dd class="col-sm-8"><span class="badge bg-danger fs-6"><?= date('F j, Y', strtotime($task['deadline'])) ?></span></dd>
                    <dt class="col-sm-4">Your Earning:</dt><dd class="col-sm-8"><span class="badge bg-success fs-6"><?= $currencySymbol ?><?= number_format($task['task_price'], 2) ?></span></dd>
                </dl>
                <?php if ($task['completion_receipt_path']): ?>
                    <hr class="my-2">
                    <p class="mb-0"><i class="fas fa-receipt"></i> **Completion Receipt:** <a href="<?= BASE_URL . htmlspecialchars($task['completion_receipt_path']) ?>" target="_blank">View Uploaded Receipt</a></p>
                <?php endif; ?>
            </div>

            <div class="col-lg-5 ps-lg-4">
                <h4>Update Progress</h4>
                <?php if ($task['status'] === 'pending_verification'): ?>
                    <div class="alert alert-info text-center"><i class="fas fa-info-circle me-2"></i> This task is currently awaiting verification.</div>
                <?php elseif ($task['status'] === 'verified_completed'): ?>
                    <div class="alert alert-success text-center"><i class="fas fa-check-circle me-2"></i> This task has been marked as completed.</div>
                <?php elseif ($task['status'] === 'cancelled'): ?>
                     <div class="alert alert-danger text-center"><i class="fas fa-times-circle me-2"></i> This task has been cancelled.</div>
                <?php else: ?>
                    <form action="index.php" method="POST" class="mb-4">
                        <input type="hidden" name="page" value="update_freelancer_task">
                        <input type="hidden" name="task_id" value="<?= $taskId ?>">
                        <input type="hidden" name="action" value="update_user_task">
                        <div class="mb-3">
                            <label class="form-label"><strong>Current Status</strong></label>
                            <div class="input-group">
                                <select name="status" class="form-select">
                                    <option value="pending" <?= $task['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="in_process" <?= $task['status'] === 'in_process' ? 'selected' : '' ?>>In Process</option>
                                </select>
                                <button type="submit" class="btn btn-secondary">Update</button>
                            </div>
                        </div>
                    </form>
                    <hr>
                    <div class="mb-3">
                        <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#returnTaskModal">
                            <i class="fas fa-undo-alt me-2"></i>Return Task to Admin
                        </button>
                    </div>
                    <form action="index.php" method="POST" enctype="multipart/form-data">
                         <input type="hidden" name="page" value="update_freelancer_task">
                        <input type="hidden" name="task_id" value="<?= $taskId ?>">
                        <input type="hidden" name="action" value="submit_for_verification">
                        <div class="mb-3">
                            <label for="completion_receipt" class="form-label"><strong>Attach Completion Proof <span class="text-danger">*</span></strong></label>
                            <input type="file" class="form-control" id="completion_receipt" name="completion_receipt" required>
                        </div>
                        <div class="mb-3">
                            <label for="user_notes" class="form-label"><strong>Notes for Admin</strong></label>
                            <textarea name="user_notes" class="form-control" rows="3" placeholder="Add any final notes..."><?= htmlspecialchars($task['user_notes'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="fas fa-paper-plane me-2"></i>Submit for Verification</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="returnTaskModal" tabindex="-1" aria-labelledby="returnTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="index.php" method="POST">
                <input type="hidden" name="page" value="update_freelancer_task&id=<?= $taskId ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="returnTaskModalLabel">Return Task to Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="task_id" value="<?= $taskId ?>">
                    <input type="hidden" name="action" value="return_task_to_admin">
                    <div class="mb-3">
                        <label for="return_reason" class="form-label">Reason for Returning <span class="text-danger">*</span></label>
                        <select class="form-select" name="return_reason" id="return_reason" required>
                            <option value="">-- Select a Reason --</option>
                            <option value="Client Not Responding">Client Not Responding</option>
                            <option value="Insufficient Information">Insufficient Information from Client</option>
                            <option value="Technical Issue">Facing a Technical Issue</option>
                            <option value="Other">Other (Please specify in notes)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="return_notes" class="form-label">Additional Notes <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="return_notes" id="return_notes" rows="4" placeholder="Explain the issue in detail for the admin." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Return</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>