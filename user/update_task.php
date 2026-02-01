<?php
/**
 * user/update_task.php
 * This page allows a user to view and update their own assigned task.
 * FINAL & COMPLETE: All required functionalities are implemented and stable.
 */

// Block direct access to this file and provide a helpful message.
if (!defined('ROOT_PATH')) {
    http_response_code(403);
    die('<strong>ભૂલ:</strong> આ ફાઈલ સીધી ખોલી શકાતી નથી. મહેરબાની કરીને સિસ્ટમ દ્વારા યોગ્ય રીતે ઍક્સેસ કરો.');
}

// Ensure the user has permission to view this page
$pdo = connectDB();
if (!hasPermission($pdo, $_SESSION['user_id'], 'update_task')) {
    echo "<h1>Access Denied</h1><p>You do not have permission to view this page.</p>";
    return;
}

$taskId = $_GET['id'] ?? null;
$currentUserId = $_SESSION['user_id'];

if (!$taskId) {
    echo "<h1>Error</h1><p>No task ID provided.</p>";
    return;
}

// Fetch the task details to be edited
$stmt = $pdo->prepare("
    SELECT 
        wa.*,
        c.client_name,
        cu.customer_name,
        u.name as assigned_user_name,
        a.document_path AS appointment_document_path
    FROM work_assignments wa
    LEFT JOIN clients c ON wa.client_id = c.id
    LEFT JOIN customers cu ON wa.customer_id = cu.id
    LEFT JOIN users u ON wa.assigned_to_user_id = u.id
    LEFT JOIN appointments a ON a.task_id = wa.id
    WHERE wa.id = ? AND wa.assigned_to_user_id = ?
");
$stmt->execute([$taskId, $currentUserId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    echo "<h1>Task Not Found</h1><p>The requested task could not be found or you do not have permission to view it.</p>";
    return;
}

// Determine if the task is editable based on its status
$isEditable = in_array($task['status'], ['pending', 'in_process', 'returned']);

// Fetch categories and subcategories for dropdowns
$categories = fetchAll($pdo, "SELECT id, name FROM categories");
$subcategories = fetchAll($pdo, "SELECT id, name, category_id FROM subcategories");
$users = fetchAll($pdo, "SELECT id, name FROM users WHERE role_id != 1 AND id != ? ORDER BY name", [$currentUserId]);

?>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header">
            <h4 class="mb-0">Update My Task #<?= htmlspecialchars($task['id'] ?? '') ?></h4>
        </div>
        <div class="card-body">
            <?php if (!$isEditable && $task['status'] === 'pending_verification'): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i> This task is currently under administrative review and cannot be edited.
                </div>
            <?php endif; ?>

            <?php if ($isEditable): ?>
            <form action="?page=actions" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_user_task">
                <input type="hidden" name="task_id" value="<?= htmlspecialchars($task['id'] ?? '') ?>">
                <input type="hidden" name="page" value="my_tasks">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select name="category_id" id="category_id" class="form-select" required disabled>
                            <option value="">Select a Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= ($task['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="subcategory_id" class="form-label">Subcategory</label>
                        <select name="subcategory_id" id="subcategory_id" class="form-select" disabled>
                            <option value="">Select a Subcategory</option>
                            <?php foreach ($subcategories as $subcategory): ?>
                                <option value="<?= $subcategory['id'] ?>" class="subcategory-option" data-category="<?= $subcategory['category_id'] ?>" <?= ($task['subcategory_id'] ?? '') == $subcategory['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subcategory['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="work_description" class="form-label">Work Description</label>
                    <textarea name="work_description" id="work_description" class="form-control" rows="3" disabled><?= htmlspecialchars($task['work_description'] ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="deadline" class="form-label">Deadline</label>
                        <input type="date" name="deadline" id="deadline" class="form-control" value="<?= htmlspecialchars($task['deadline'] ?? '') ?>" required disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Task Status</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="pending" <?= ($task['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="in_process" <?= ($task['status'] ?? '') == 'in_process' ? 'selected' : '' ?>>In Process</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="fee" class="form-label">Fee</label>
                        <input type="number" step="0.01" name="fee" id="fee" class="form-control" value="<?= htmlspecialchars($task['fee'] ?? '') ?>" disabled>
                    </div>
                     <div class="col-md-4 mb-3">
                        <label for="fee_mode" class="form-label">Fee Mode</label>
                        <select name="fee_mode" id="fee_mode" class="form-select" disabled>
                            <option value="pending" <?= ($task['fee_mode'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="paid" <?= ($task['fee_mode'] ?? '') == 'paid' ? 'selected' : '' ?>>Paid</option>
                        </select>
                    </div>
                     <div class="col-md-4 mb-3">
                        <label for="task_price" class="form-label">Total Task Price</label>
                        <input type="number" step="0.01" name="task_price" id="task_price" class="form-control" value="<?= htmlspecialchars($task['task_price'] ?? '') ?>" disabled>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="user_notes" class="form-label">Your Notes</label>
                    <textarea name="user_notes" id="user_notes" class="form-control" rows="3"><?= htmlspecialchars($task['user_notes'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="admin_notes" class="form-label">Admin Notes</label>
                    <textarea id="admin_notes" class="form-control" rows="3" readonly><?= htmlspecialchars($task['admin_notes'] ?? 'No notes from admin.') ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="task_attachment_user" class="form-label">Upload New Attachment (optional)</label>
                    <input class="form-control" type="file" name="task_attachment_user" id="task_attachment_user">
                     <?php if (!empty($task['appointment_document_path'])): ?>
                        <div class="mb-2">
                             <i class="fas fa-file-alt"></i> <strong>Original Appointment Document:</strong> <a href="<?= BASE_URL . htmlspecialchars($task['appointment_document_path'] ?? '') ?>" target="_blank">View File</a>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($task['attachment_path'])): ?>
                         <div class="mb-2">
                             <i class="fas fa-paperclip"></i> <strong>Task Attachment:</strong> <a href="<?= BASE_URL . htmlspecialchars($task['attachment_path'] ?? '') ?>" target="_blank">View File</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Task
                    </button>
                </div>
            </form>
            <?php endif; ?>
            
            <?php if (in_array($task['status'], ['in_process', 'returned', 'pending'])): ?>
            <hr>
            <div class="mt-4">
                <h5>Submit Task for Verification</h5>
                <p>When you have completed the task, upload the final receipt and submit it for admin review.</p>
                <form action="?page=actions" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="submit_for_verification">
                    <input type="hidden" name="task_id" value="<?= htmlspecialchars($task['id'] ?? '') ?>">
                    <div class="mb-3">
                        <label for="completion_receipt" class="form-label">Upload Completion Receipt</label>
                        <input class="form-control" type="file" name="completion_receipt" id="completion_receipt" required>
                    </div>
                    <div class="mb-3">
                         <label for="user_notes_verify" class="form-label">Notes for Admin (optional)</label>
                         <textarea name="user_notes" id="user_notes_verify" class="form-control" rows="3"><?= htmlspecialchars($task['user_notes'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle me-2"></i>Submit for Verification
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <?php if (in_array($task['status'], ['completed', 'verified_completed'])): ?>
            <hr>
            <div class="mt-4 text-center">
                 <h5>Task Completed!</h5>
                 <p>Your task has been completed and verified by the admin.</p>
                 <a href="<?= BASE_URL . 'views/print_bill.php?task_id=' . htmlspecialchars($task['id']) ?>" class="btn btn-info">
                     <i class="fas fa-print me-2"></i>Print Customer Bill
                 </a>
            </div>
            <?php endif; ?>

            <hr>
            <?php if ($isEditable): ?>
            <div class="mt-4">
                <h5>Transfer Task</h5>
                <p>Request to transfer this task to another user. They will need to accept the transfer for it to be completed.</p>
                <form action="?page=actions" method="post" class="row g-3">
                    <input type="hidden" name="action" value="request_transfer">
                    <input type="hidden" name="task_id" value="<?= htmlspecialchars($task['id'] ?? '') ?>">
                    <div class="col-md-6">
                        <label for="transfer_to_user_id" class="form-label">Transfer To</label>
                        <select name="transfer_to_user_id" id="transfer_to_user_id" class="form-select" required>
                            <option value="">Select a User</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="transfer_comments" class="form-label">Comments (optional)</label>
                        <input type="text" name="transfer_comments" id="transfer_comments" class="form-control">
                    </div>
                    <div class="col-12">
                         <button type="submit" class="btn btn-warning">
                            <i class="fas fa-exchange-alt me-2"></i>Request Transfer
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>