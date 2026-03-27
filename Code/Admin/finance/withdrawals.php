<?php
/**
 * admin/withdrawals.php
 * Allows the administrator to manage withdrawal requests.
 * FINAL & COMPLETE: The form now correctly submits to the central index.php action handler, fixing the accept/reject functionality.
 */

require_once MODELS_PATH . 'withdrawal.php';
require_once MODELS_PATH . 'notifications.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';

// The main POST handling logic is now in index.php
// This page now only handles displaying data and displaying status messages from the session.
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$filterStatus = $_GET['status'] ?? 'all';
$withdrawalRequests = getAllWithdrawalRequests($filterStatus);
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '$');
?>

<h2 class="mb-4">Manage Withdrawal Requests</h2>
<?php if (!empty($message)) { include VIEWS_PATH . 'components/message_box.php'; } ?>

<div class="card shadow-sm rounded-3">
    <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="fas fa-list me-2"></i>Withdrawal Requests</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr><th>ID</th><th>User</th><th>Amount</th><th>Requested At</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if(!empty($withdrawalRequests)): foreach ($withdrawalRequests as $request): ?>
                        <tr>
                            <td><?= htmlspecialchars($request['id']) ?></td>
                            <td><?= htmlspecialchars($request['user_name'] ?? 'N/A') ?></td>
                            <td><?= $currencySymbol ?><?= number_format($request['amount'], 2) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($request['requested_at'])) ?></td>
                            <td><span class="badge bg-<?= getWithdrawalStatusBadgeColor($request['status']) ?>"><?= ucfirst($request['status']) ?></span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#bankDetailsModal" data-bank='<?= htmlspecialchars($request['bank_details_json'], ENT_QUOTES, 'UTF-8') ?>' data-user-name="<?= htmlspecialchars($request['user_name']) ?>">Details</button>
                                <?php if ($request['status'] === 'pending'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-id="<?= $request['id'] ?>" data-status="approved">Approve</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-id="<?= $request['id'] ?>" data-status="rejected">Reject</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center">No withdrawal requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="bankDetailsModal" tabindex="-1" aria-labelledby="bankDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="bankDetailsModalLabel">Bank Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <h6 id="modalUserName" class="mb-3"></h6>
                <table class="table table-bordered">
                    <tbody id="bankDetailsTable"></tbody>
                </table>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="index.php?page=manage_withdrawals" method="POST">
                <input type="hidden" name="action" value="update_withdrawal_status">
                <div class="modal-header"><h5 class="modal-title" id="updateStatusModalLabel">Update Status</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="withdrawal_id" id="updateWithdrawalId">
                    <input type="hidden" name="new_status" id="updateNewStatus">
                    <p>Confirm status update to: <strong id="confirmStatusText"></strong></p>
                    <div class="mb-3" id="transactionIdField" style="display:none;">
                        <label for="transaction_id" class="form-label">Transaction ID / Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="transaction_id" id="transaction_id">
                    </div>
                    <div class="mb-3"><label class="form-label">Admin Comments (Optional)</label><textarea class="form-control" name="admin_comments" rows="3"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="update_withdrawal_status" class="btn btn-primary">Confirm</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bankDetailsModal = document.getElementById('bankDetailsModal');
    bankDetailsModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const userName = button.getAttribute('data-user-name');
        const bankDetailsJson = button.getAttribute('data-bank');
        
        document.getElementById('modalUserName').textContent = 'Details for: ' + userName;
        
        try {
            const details = JSON.parse(bankDetailsJson || '{}');
            const tableBody = document.getElementById('bankDetailsTable');
            tableBody.innerHTML = `
                <tr><th>Bank Name</th><td>${details.bank_name || 'N/A'}</td></tr>
                <tr><th>Account Holder</th><td>${details.account_holder_name || 'N/A'}</td></tr>
                <tr><th>Account Number</th><td>${details.account_number || 'N/A'}</td></tr>
                <tr><th>IFSC Code</th><td>${details.ifsc_code || 'N/A'}</td></tr>
            `;
        } catch (e) {
            document.getElementById('bankDetailsTable').innerHTML = '<tr><td colspan="2">Error parsing bank details.</td></tr>';
        }
    });

    const statusModal = document.getElementById('updateStatusModal');
    statusModal.addEventListener('show.bs.modal', function(e) {
        const button = e.relatedTarget;
        const id = button.getAttribute('data-id');
        const status = button.getAttribute('data-status');
        
        document.getElementById('updateWithdrawalId').value = id;
        document.getElementById('updateNewStatus').value = status;
        document.getElementById('confirmStatusText').textContent = status.charAt(0).toUpperCase() + status.slice(1);

        const transactionIdField = document.getElementById('transactionIdField');
        const transactionIdInput = document.getElementById('transaction_id');
        if (status === 'approved') {
            transactionIdField.style.display = 'block';
            transactionIdInput.required = true;
        } else {
            transactionIdField.style.display = 'none';
            transactionIdInput.required = false;
        }
    });
});
</script>