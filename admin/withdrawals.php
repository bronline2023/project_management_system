<?php
/**
 * admin/withdrawals.php
 *
 * This file allows administrators to manage withdrawal requests from DEOs.
 * It provides functionalities to view, approve, reject, request details, and mark as paid.
 * It also allows setting the minimum withdrawal amount.
 *
 * It ensures that only authenticated admin users can access this page.
 */

// Ensure ROOT_PATH is defined and config.php is included.
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
}
require_once ROOT_PATH . 'config.php';

require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';
require_once WITHDRAWAL_MODELS_PATH . 'withdrawal.php'; // Include the withdrawal model

// Restrict access to Admin users only.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';

// --- Handle Minimum Withdrawal Amount Update ---
if (isset($_POST['update_min_withdrawal_amount'])) {
    $newMinAmount = filter_input(INPUT_POST, 'min_withdrawal_amount', FILTER_VALIDATE_FLOAT);
    if ($newMinAmount === false || $newMinAmount < 0) {
        $message = '<div class="alert alert-danger" role="alert">Please enter a valid non-negative amount for minimum withdrawal.</div>';
    } else {
        if (updateMinimumWithdrawalAmount($newMinAmount)) {
            $message = '<div class="alert alert-success" role="alert">Minimum withdrawal amount updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger" role="alert">Failed to update minimum withdrawal amount.</div>';
        }
    }
}

// --- Handle Withdrawal Request Status Updates ---
if (isset($_POST['update_request_status'])) {
    $requestId = (int)$_POST['request_id'];
    $newStatus = $_POST['new_status'];
    $transactionNumber = trim($_POST['transaction_number'] ?? '');
    $adminComments = trim($_POST['admin_comments'] ?? '');

    $request = getWithdrawalRequestById($requestId);

    if (!$request) {
        $message = '<div class="alert alert-danger" role="alert">Withdrawal request not found.</div>';
    } elseif ($newStatus === 'paid' && empty($transactionNumber)) {
        $message = '<div class="alert alert-danger" role="alert">Transaction number is required to mark as paid.</div>';
    } elseif ($newStatus === 'rejected' && empty($adminComments)) {
        $message = '<div class="alert alert-danger" role="alert">Comments are required to reject a request.</div>';
    } elseif ($newStatus === 'details_requested' && empty($adminComments)) {
        $message = '<div class="alert alert-danger" role="alert">Comments are required when requesting details.</div>';
    } else {
        if (updateWithdrawalRequestStatus($requestId, $newStatus, $currentUserId, $transactionNumber, $adminComments)) {
            $message = '<div class="alert alert-success" role="alert">Withdrawal request status updated to ' . ucwords(str_replace('_', ' ', $newStatus)) . '!</div>';
        } else {
            $message = '<div class="alert alert-danger" role="alert">Failed to update withdrawal request status.</div>';
        }
    }
}

$filterStatus = $_GET['status'] ?? 'all'; // 'all', 'pending', 'processing', 'details_requested', 'paid', 'rejected'
$withdrawalRequests = getAllWithdrawalRequests($filterStatus);
$currentMinWithdrawalAmount = getMinimumWithdrawalAmount();

include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Manage Withdrawals</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; ?>
            <script>
                setupAutoHideAlerts();
            </script>
        <?php endif; ?>

        <!-- Minimum Withdrawal Amount Setting -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Minimum Withdrawal Amount Setting</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST" class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <label for="min_withdrawal_amount" class="form-label">Minimum Amount (<?= CURRENCY_SYMBOL ?>)</label>
                        <input type="number" step="0.01" min="0" class="form-control rounded-pill" id="min_withdrawal_amount" name="min_withdrawal_amount" value="<?= htmlspecialchars($currentMinWithdrawalAmount) ?>" required>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" name="update_min_withdrawal_amount" class="btn btn-primary rounded-pill px-4 mt-3 mt-md-0">
                            <i class="fas fa-save me-2"></i>Update Setting
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Filter Withdrawal Requests -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Withdrawal Requests</h5>
            </div>
            <div class="card-body">
                <form action="" method="GET" class="row g-3 align-items-center">
                    <input type="hidden" name="page" value="manage_withdrawals">
                    <div class="col-md-4">
                        <label for="statusFilter" class="form-label visually-hidden">Filter by Status</label>
                        <select class="form-select rounded-pill" id="statusFilter" name="status" onchange="this.form.submit()">
                            <option value="all" <?= ($filterStatus === 'all') ? 'selected' : '' ?>>All Statuses</option>
                            <option value="pending" <?= ($filterStatus === 'pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= ($filterStatus === 'processing') ? 'selected' : '' ?>>Processing</option>
                            <option value="details_requested" <?= ($filterStatus === 'details_requested') ? 'selected' : '' ?>>Details Requested</option>
                            <option value="paid" <?= ($filterStatus === 'paid') ? 'selected' : '' ?>>Paid</option>
                            <option value="rejected" <?= ($filterStatus === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Apply Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- All Withdrawal Requests Table -->
        <div class="card shadow-sm rounded-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Withdrawal Requests</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($withdrawalRequests)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>DEO Name</th>
                                    <th>Amount</th>
                                    <th>Requested On</th>
                                    <th>Status</th>
                                    <th>Txn No.</th>
                                    <th>Processed By</th>
                                    <th>Processed At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($withdrawalRequests as $request): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($request['id']) ?></td>
                                        <td><?= htmlspecialchars($request['deo_name'] ?? 'N/A') ?></td>
                                        <td><?= CURRENCY_SYMBOL ?><?= number_format($request['amount'], 2) ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($request['request_date'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getWithdrawalStatusBadgeColor($request['status']) ?>">
                                                <?= ucwords(htmlspecialchars(str_replace('_', ' ', $request['status']))) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($request['transaction_number'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($request['processed_by_admin_name'] ?? 'N/A') ?></td>
                                        <td><?= $request['processed_at'] ? date('Y-m-d H:i', strtotime($request['processed_at'])) : 'N/A' ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-info rounded-pill me-1 view-request-btn"
                                                    data-bs-toggle="modal" data-bs-target="#viewRequestModal"
                                                    data-request='<?= json_encode($request) ?>' title="View Details">
                                                <i class="fas fa-eye"></i> View
                                            </button>

                                            <?php if ($request['status'] === 'pending' || $request['status'] === 'processing' || $request['status'] === 'details_requested'): ?>
                                                <?php if ($request['status'] !== 'paid'): // Only show action buttons if not already paid ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill me-1 mark-paid-btn"
                                                            data-bs-toggle="modal" data-bs-target="#markPaidModal"
                                                            data-request-id="<?= htmlspecialchars($request['id']) ?>" title="Mark as Paid">
                                                        <i class="fas fa-check-circle"></i> Pay
                                                    </button>
                                                    <?php if ($request['status'] !== 'details_requested'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-pill me-1 request-details-btn"
                                                                data-bs-toggle="modal" data-bs-target="#requestDetailsModal"
                                                                data-request-id="<?= htmlspecialchars($request['id']) ?>" title="Request Details">
                                                            <i class="fas fa-question-circle"></i> Request Details
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill me-1 reject-request-btn"
                                                            data-bs-toggle="modal" data-bs-target="#rejectRequestModal"
                                                            data-request-id="<?= htmlspecialchars($request['id']) ?>" title="Reject Request">
                                                        <i class="fas fa-times-circle"></i> Reject
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No withdrawal requests found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>

<!-- View Request Modal -->
<div class="modal fade" id="viewRequestModal" tabindex="-1" aria-labelledby="viewRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-info text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="viewRequestModalLabel"><i class="fas fa-info-circle me-2"></i>Withdrawal Request Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p><strong>Request ID:</strong> <span id="modalRequestId"></span></p>
                <p><strong>DEO Name:</strong> <span id="modalDeoName"></span></p>
                <p><strong>Amount:</strong> <span id="modalAmount"></span></p>
                <p><strong>Requested On:</strong> <span id="modalRequestDate"></span></p>
                <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                <p><strong>Transaction Number:</strong> <span id="modalTransactionNumber"></span></p>
                <p><strong>Admin Comments:</strong> <span id="modalAdminComments"></span></p>
                <hr>
                <h6>Payment Details:</h6>
                <p><strong>Bank Name:</strong> <span id="modalBankName"></span></p>
                <p><strong>Account Holder:</strong> <span id="modalAccountHolderName"></span></p>
                <p><strong>Account Number:</strong> <span id="modalAccountNumber"></span></p>
                <p><strong>IFSC Code:</strong> <span id="modalIfscCode"></span></p>
                <p><strong>UPI ID:</strong> <span id="modalUpiId"></span></p>
                <hr>
                <p><strong>Processed By:</strong> <span id="modalProcessedBy"></span></p>
                <p><strong>Processed At:</strong> <span id="modalProcessedAt"></span></p>
            </div>
            <div class="modal-footer border-0 rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Mark as Paid Modal -->
<div class="modal fade" id="markPaidModal" tabindex="-1" aria-labelledby="markPaidModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-success text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="markPaidModalLabel"><i class="fas fa-check-circle me-2"></i>Mark as Paid</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="request_id" id="markPaidRequestId">
                    <input type="hidden" name="new_status" value="paid">
                    <div class="mb-3">
                        <label for="transactionNumber" class="form-label">Transaction Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-pill" id="transactionNumber" name="transaction_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="adminCommentsPaid" class="form-label">Admin Comments (Optional)</label>
                        <textarea class="form-control rounded-3" id="adminCommentsPaid" name="admin_comments" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_request_status" class="btn btn-success rounded-pill">Mark Paid</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Request Details Modal -->
<div class="modal fade" id="requestDetailsModal" tabindex="-1" aria-labelledby="requestDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-warning text-dark border-0 rounded-top-4">
                <h5 class="modal-title" id="requestDetailsModalLabel"><i class="fas fa-question-circle me-2"></i>Request Payment Details from DEO</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="request_id" id="requestDetailsRequestId">
                    <input type="hidden" name="new_status" value="details_requested">
                    <div class="mb-3">
                        <label for="adminCommentsRequestDetails" class="form-label">Admin Comments (Required) <span class="text-danger">*</span></label>
                        <textarea class="form-control rounded-3" id="adminCommentsRequestDetails" name="admin_comments" rows="4" required placeholder="Please specify what details are needed (e.g., 'Please provide your bank account details or UPI ID within 7 days.')."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_request_status" class="btn btn-warning rounded-pill">Send Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Request Modal -->
<div class="modal fade" id="rejectRequestModal" tabindex="-1" aria-labelledby="rejectRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="rejectRequestModalLabel"><i class="fas fa-times-circle me-2"></i>Reject Withdrawal Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="request_id" id="rejectRequestId">
                    <input type="hidden" name="new_status" value="rejected">
                    <div class="mb-3">
                        <label for="adminCommentsReject" class="form-label">Admin Comments (Required) <span class="text-danger">*</span></label>
                        <textarea class="form-control rounded-3" id="adminCommentsReject" name="admin_comments" rows="4" required placeholder="Please provide a reason for rejecting this withdrawal request."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_request_status" class="btn btn-danger rounded-pill">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        // View Request Modal (Admin)
        const viewRequestModal = document.getElementById('viewRequestModal');
        if (viewRequestModal) {
            viewRequestModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // Button that triggered the modal
                const requestData = JSON.parse(button.getAttribute('data-request'));

                document.getElementById('modalRequestId').textContent = requestData.id;
                document.getElementById('modalDeoName').textContent = requestData.deo_name || 'N/A';
                document.getElementById('modalAmount').textContent = '<?= CURRENCY_SYMBOL ?>' + parseFloat(requestData.amount).toFixed(2);
                document.getElementById('modalRequestDate').textContent = requestData.request_date ? new Date(requestData.request_date).toLocaleString() : 'N/A';
                document.getElementById('modalStatus').textContent = requestData.status ? requestData.status.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase()) : 'N/A';
                document.getElementById('modalTransactionNumber').textContent = requestData.transaction_number || 'N/A';
                document.getElementById('modalAdminComments').textContent = requestData.admin_comments || 'N/A';

                document.getElementById('modalBankName').textContent = requestData.bank_name || 'N/A';
                document.getElementById('modalAccountHolderName').textContent = requestData.account_holder_name || 'N/A';
                document.getElementById('modalAccountNumber').textContent = requestData.account_number || 'N/A';
                document.getElementById('modalIfscCode').textContent = requestData.ifsc_code || 'N/A';
                document.getElementById('modalUpiId').textContent = requestData.upi_id || 'N/A';

                document.getElementById('modalProcessedBy').textContent = requestData.processed_by_admin_name || 'N/A';
                document.getElementById('modalProcessedAt').textContent = requestData.processed_at ? new Date(requestData.processed_at).toLocaleString() : 'N/A';
            });
        }

        // Mark Paid Modal (Admin)
        const markPaidModal = document.getElementById('markPaidModal');
        if (markPaidModal) {
            markPaidModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const requestId = button.getAttribute('data-request-id');
                markPaidModal.querySelector('#markPaidRequestId').value = requestId;
            });
        }

        // Request Details Modal (Admin)
        const requestDetailsModal = document.getElementById('requestDetailsModal');
        if (requestDetailsModal) {
            requestDetailsModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const requestId = button.getAttribute('data-request-id');
                requestDetailsModal.querySelector('#requestDetailsRequestId').value = requestId;
            });
        }

        // Reject Request Modal (Admin)
        const rejectRequestModal = document.getElementById('rejectRequestModal');
        if (rejectRequestModal) {
            rejectRequestModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const requestId = button.getAttribute('data-request-id');
                rejectRequestModal.querySelector('#rejectRequestId').value = requestId;
            });
        }
    });
</script>
