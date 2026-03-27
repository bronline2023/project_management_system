<?php
/**
 * user/my_tasks.php
 * Displays a list of tasks assigned to the current logged-in user.
 * FINAL & COMPLETE: All query logic is updated to join with the clients table.
 * NEW: Added "Transfer Task" button with modal.
 */
if (!defined('ROOT_PATH')) {
    die('Invalid access');
}

$pdo = connectDB();
$message = '';
$currentUserId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'guest';

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

// Fetch all tasks for the current user
$tasks = fetchAll($pdo, "
    SELECT 
        wa.id, wa.work_description, wa.deadline, wa.status, wa.assigned_to_user_id,
        wa.transfer_status, wa.transfer_from_user_id, wa.transferred_to_user_id,
        wa.transfer_comments, wa.completion_receipt_path, wa.created_at,
        cl.client_name, cu.customer_name, cat.name as category_name
    FROM work_assignments wa
    LEFT JOIN clients cl ON wa.client_id = cl.id
    LEFT JOIN customers cu ON wa.customer_id = cu.id
    LEFT JOIN categories cat ON wa.category_id = cat.id
    WHERE wa.assigned_to_user_id = ? OR wa.transferred_to_user_id = ?
    ORDER BY wa.deadline ASC
", [$currentUserId, $currentUserId]);

// Fetch users for the transfer dropdown, excluding the current user and admins
$usersForTransfer = fetchAll($pdo, "SELECT u.id, u.name, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id != ? AND u.status = 'active' AND r.role_name != 'admin'", [$currentUserId]);

function getStatusBadgeColor($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'in_process': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        case 'pending_verification': return 'primary';
        case 'returned': return 'secondary';
        case 'verified_completed': return 'success';
        default: return 'secondary';
    }
}
?>

<h2 class="mb-4">My Assigned Tasks</h2>

<?php if (!empty($message)) { include VIEWS_PATH . 'components/message_box.php'; } ?>

<div class="card shadow-sm rounded-3">
    <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>My Tasks</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th><th>Customer Name</th><th>Client Name</th><th>Service</th><th>Deadline</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr><td colspan="7" class="text-center">No tasks assigned to you.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['id'] ?? '') ?></td>
                            <td><?= htmlspecialchars($task['customer_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($task['client_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($task['category_name'] ?? 'N/A') ?></td>
                            <td><?= date('Y-m-d', strtotime($task['deadline'] ?? '')) ?></td>
                            <td>
                                <span class="badge bg-<?= getStatusBadgeColor($task['status']) ?>">
                                    <?= ucwords(str_replace('_', ' ', $task['status'] ?? '')) ?>
                                </span>
                                <?php if (($task['transfer_status'] ?? '') === 'pending' && ($task['transferred_to_user_id'] ?? '') == $currentUserId): ?>
                                    <span class="badge bg-info">Transfer Pending</span>
                                <?php elseif (($task['transfer_status'] ?? '') === 'rejected' && ($task['transfer_from_user_id'] ?? '') == $currentUserId): ?>
                                    <span class="badge bg-danger">Transfer Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (($task['transfer_status'] ?? '') === 'pending' && ($task['transferred_to_user_id'] ?? '') == $currentUserId): ?>
                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#transferActionModal" data-task-id="<?= htmlspecialchars($task['id'] ?? '') ?>" data-action="accept">Accept</button>
                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#transferActionModal" data-task-id="<?= htmlspecialchars($task['id'] ?? '') ?>" data-action="reject">Reject</button>
                                <?php elseif (($task['transfer_status'] ?? '') === 'none' || (($task['transfer_status'] ?? '') === 'rejected' && ($task['assigned_to_user_id'] ?? '') == $currentUserId)): ?>
                                    <a href="?page=update_task&id=<?= htmlspecialchars($task['id'] ?? '') ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit me-1"></i> Update
                                    </a>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#transferRequestModal" data-task-id="<?= htmlspecialchars($task['id'] ?? '') ?>"><i class="fas fa-exchange-alt me-1"></i> Transfer</button>
                                <?php endif; ?>

                                <?php if (in_array($task['status'] ?? '', ['completed', 'verified_completed'])): ?>
                                     <a href="<?= BASE_URL . 'views/print_bill.php?task_id=' . htmlspecialchars($task['id'] ?? '') ?>" class="btn btn-sm btn-info" target="_blank">
                                        <i class="fas fa-print me-1"></i> Print Bill
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="transferRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Task Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="index.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="request_transfer">
                    <input type="hidden" name="page" value="my_tasks">
                    <input type="hidden" name="task_id" id="transfer-task-id">
                    <div class="mb-3">
                        <label for="transfer_to_user_id" class="form-label">Transfer To</label>
                        <select class="form-select" id="transfer_to_user_id" name="transfer_to_user_id" required>
                            <option value="">Select User</option>
                            <?php foreach ($usersForTransfer as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name'] ?? '') ?> (<?= htmlspecialchars($user['role_name'] ?? '') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="transfer_comments" class="form-label">Reason for Transfer</label>
                        <textarea class="form-control" name="transfer_comments" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="transferActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transfer-action-modal-title">Task Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                 <p id="transfer-action-message"></p>
                 <form action="index.php" method="POST" id="accept-form" style="display: none;">
                    <input type="hidden" name="action" value="accept_transfer">
                    <input type="hidden" name="page" value="my_tasks">
                    <input type="hidden" name="task_id" id="accept-task-id">
                    <button type="submit" class="btn btn-success">Accept</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                 </form>
                 <form action="index.php" method="POST" id="reject-form" style="display: none;">
                    <input type="hidden" name="action" value="reject_transfer">
                    <input type="hidden" name="page" value="my_tasks">
                    <input type="hidden" name="task_id" id="reject-task-id">
                     <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" name="rejection_reason" id="rejection_reason" rows="3" required></textarea>
                     </div>
                    <button type="submit" class="btn btn-danger">Reject</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                 </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const transferRequestModal = document.getElementById('transferRequestModal');
    transferRequestModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const taskId = button.getAttribute('data-task-id');
        const modalInput = transferRequestModal.querySelector('#transfer-task-id');
        modalInput.value = taskId;
    });

    const transferActionModal = document.getElementById('transferActionModal');
    transferActionModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const taskId = button.getAttribute('data-task-id');
        const action = button.getAttribute('data-action');
        
        const modalTitle = document.getElementById('transfer-action-modal-title');
        const messageContainer = document.getElementById('transfer-action-message');
        const acceptForm = document.getElementById('accept-form');
        const rejectForm = document.getElementById('reject-form');
        const acceptTaskId = document.getElementById('accept-task-id');
        const rejectTaskId = document.getElementById('reject-task-id');

        acceptForm.style.display = 'none';
        rejectForm.style.display = 'none';

        if (action === 'accept') {
            modalTitle.textContent = 'Accept Task Transfer';
            messageContainer.textContent = `Do you want to accept task #${taskId}?`;
            acceptTaskId.value = taskId;
            acceptForm.style.display = 'block';
        } else if (action === 'reject') {
            modalTitle.textContent = 'Reject Task Transfer';
            messageContainer.textContent = `Do you want to reject task #${taskId}?`;
            rejectTaskId.value = taskId;
            rejectForm.style.display = 'block';
        }
    });
});
</script>