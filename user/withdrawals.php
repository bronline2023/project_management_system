<?php
/**
 * user/withdrawals.php
 *
 * This file allows Data Entry Operators (DEOs) to view their withdrawal requests.
 * It displays a list of their requests with status and transaction details.
 * It also allows them to submit bank details if requested by an admin.
 *
 * It ensures that only authenticated DEO users can access this page.
 */

// Include the main configuration file using ROOT_PATH.
require_once ROOT_PATH . 'config.php';

// Now, other includes can safely use constants from config.php
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';
require_once WITHDRAWAL_MODELS_PATH . 'withdrawal.php'; // Include the new withdrawal model

// Restrict access to Data Entry Operator users only.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'data_entry_operator') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';

// Get currency symbol from settings
$currencySymbol = CURRENCY_SYMBOL;

// Fetch DEO's saved bank details
$deoBankDetails = [];
try {
    $stmt = $pdo->prepare("SELECT bank_name, account_holder_name, account_number, ifsc_code, upi_id FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
    $stmt->execute();
    $deoBankDetails = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching DEO bank details for withdrawals page: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading your saved bank details.</div>';
}


// --- Handle Payment Details Submission ---
if (isset($_POST['submit_payment_details'])) {
    $requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $bankName = trim($_POST['bank_name'] ?? '');
    $accountHolderName = trim($_POST['account_holder_name'] ?? '');
    $accountNumber = trim($_POST['account_number'] ?? '');
    $ifscCode = trim($_POST['ifsc_code'] ?? '');
    $upiId = trim($_POST['upi_id'] ?? '');

    // Basic validation for payment details
    if (empty($bankName) || empty($accountHolderName) || empty($accountNumber) || empty($ifscCode)) {
        $message = '<div class="alert alert-danger" role="alert">Please fill in all required bank details. UPI ID is optional.</div>';
    } elseif ($requestId === false) {
        $message = '<div class="alert alert-danger" role="alert">Invalid withdrawal request ID.</div>';
    } else {
        $request = getWithdrawalRequestById($requestId);
        if (!$request || $request['deo_id'] !== $currentUserId || $request['status'] !== 'details_requested') {
            $message = '<div class="alert alert-danger" role="alert">Invalid request or not authorized to update details.</div>';
        } else {
            $paymentDetails = [
                'bank_name' => $bankName,
                'account_holder_name' => $accountHolderName,
                'account_number' => $accountNumber,
                'ifsc_code' => $ifscCode,
                'upi_id' => $upiId
            ];
            if (updateWithdrawalRequestStatus($requestId, 'processing', null, null, null, $paymentDetails)) {
                $message = '<div class="alert alert-success" role="alert">Payment details submitted successfully! Your request is now processing.</div>';
            } else {
                $message = '<div class="alert alert-danger" role="alert">Failed to submit payment details.</div>';
            }
        }
    }
}


// Fetch Withdrawal Requests for the current DEO
$withdrawalRequests = getWithdrawalRequestsByDeo($currentUserId, 'all');

// Check if there's a 'details_requested' withdrawal request
$detailsRequestedWithdrawal = null;
foreach ($withdrawalRequests as $req) {
    if ($req['status'] === 'details_requested') {
        $detailsRequestedWithdrawal = $req;
        break;
    }
}

include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">My Withdrawals</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; ?>
            <script>
                setupAutoHideAlerts();
            </script>
        <?php endif; ?>

        <!-- Action Required: Submit Payment Details - Prominent display -->
        <?php if ($detailsRequestedWithdrawal): ?>
            <div class="card shadow-sm rounded-3 mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Action Required: Submit Payment Details</h5>
                </div>
                <div class="card-body">
                    <p>Admin has requested your bank/UPI details for Request ID: <strong><?= htmlspecialchars($detailsRequestedWithdrawal['id']) ?></strong> for amount <strong><?= $currencySymbol ?><?= number_format($detailsRequestedWithdrawal['amount'], 2) ?></strong>.</p>
                    <?php if (!empty($detailsRequestedWithdrawal['admin_comments'])): ?>
                        <p class="text-muted"><strong>Admin Comments:</strong> <?= htmlspecialchars($detailsRequestedWithdrawal['admin_comments']) ?></p>
                    <?php endif; ?>
                    <button type="button" class="btn btn-danger rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#paymentDetailsModal" data-request-id="<?= htmlspecialchars($detailsRequestedWithdrawal['id']) ?>"
                        data-bank-name="<?= htmlspecialchars($deoBankDetails['bank_name'] ?? '') ?>"
                        data-account-holder-name="<?= htmlspecialchars($deoBankDetails['account_holder_name'] ?? '') ?>"
                        data-account-number="<?= htmlspecialchars($deoBankDetails['account_number'] ?? '') ?>"
                        data-ifsc-code="<?= htmlspecialchars($deoBankDetails['ifsc_code'] ?? '') ?>"
                        data-upi-id="<?= htmlspecialchars($deoBankDetails['upi_id'] ?? '') ?>">
                        <i class="fas fa-money-check-alt me-2"></i>Submit Details Now
                    </button>
                    <p class="text-muted mt-2"><small>Please submit details within 7 days.</small></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Your Withdrawal History -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Your Withdrawal History</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($withdrawalRequests)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Amount</th>
                                    <th>Requested On</th>
                                    <th>Status</th>
                                    <th>Transaction No.</th>
                                    <th>Processed By</th>
                                    <th>Processed At</th>
                                    <th>Comments</th>
                                    <th>Payment Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($withdrawalRequests as $request): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($request['id']) ?></td>
                                        <td><?= $currencySymbol ?><?= number_format($request['amount'], 2) ?></td>
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
                                            <?php if (!empty($request['admin_comments'])): ?>
                                                <span class="text-info" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($request['admin_comments']) ?>">
                                                    <i class="fas fa-comment-dots me-1"></i> View
                                                </span>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($request['bank_name']) || !empty($request['upi_id'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-info rounded-pill" data-bs-toggle="modal" data-bs-target="#viewPaymentDetailsModal"
                                                        data-bank-name="<?= htmlspecialchars($request['bank_name'] ?? '') ?>"
                                                        data-account-holder-name="<?= htmlspecialchars($request['account_holder_name'] ?? '') ?>"
                                                        data-account-number="<?= htmlspecialchars($request['account_number'] ?? '') ?>"
                                                        data-ifsc-code="<?= htmlspecialchars($request['ifsc_code'] ?? '') ?>"
                                                        data-upi-id="<?= htmlspecialchars($request['upi_id'] ?? '') ?>">
                                                    <i class="fas fa-info-circle"></i> View
                                                </button>
                                            <?php else: ?>
                                                N/A
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

<!-- Payment Details Modal (for DEO to submit details, pre-filled with saved details) -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="paymentDetailsModalLabel"><i class="fas fa-money-check-alt me-2"></i>Submit Payment Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="request_id" id="paymentDetailsRequestId">
                    <p class="text-muted mb-3">Please provide or confirm your bank account or UPI ID for withdrawal. You have 7 days to submit these details.</p>
                    <div class="mb-3">
                        <label for="modal_bank_name" class="form-label">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-pill" id="modal_bank_name" name="bank_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="modal_account_holder_name" class="form-label">Account Holder Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-pill" id="modal_account_holder_name" name="account_holder_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="modal_account_number" class="form-label">Account Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-pill" id="modal_account_number" name="account_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="modal_ifsc_code" class="form-label">IFSC Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-pill" id="modal_ifsc_code" name="ifsc_code" required>
                    </div>
                    <div class="mb-3">
                        <label for="modal_upi_id" class="form-label">UPI ID (Optional)</label>
                        <input type="text" class="form-control rounded-pill" id="modal_upi_id" name="upi_id">
                    </div>
                </div>
                <div class="modal-footer border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="submit_payment_details" class="btn btn-primary rounded-pill">Submit Details</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Payment Details Modal (for DEO to view their own submitted details) -->
<div class="modal fade" id="viewPaymentDetailsModal" tabindex="-1" aria-labelledby="viewPaymentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-info text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="viewPaymentDetailsModalLabel"><i class="fas fa-info-circle me-2"></i>Payment Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p><strong>Bank Name:</strong> <span id="viewModalBankName"></span></p>
                <p><strong>Account Holder:</strong> <span id="viewModalAccountHolderName"></span></p>
                <p><strong>Account Number:</strong> <span id="viewModalAccountNumber"></span></p>
                <p><strong>IFSC Code:</strong> <span id="viewModalIfscCode"></span></p>
                <p><strong>UPI ID:</strong> <span id="viewModalUpiId"></span></p>
            </div>
            <div class="modal-footer border-0 rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
            </div>
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

        // Payment Details Modal (for DEO to submit details)
        const paymentDetailsModal = document.getElementById('paymentDetailsModal');
        if (paymentDetailsModal) {
            paymentDetailsModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // Button that triggered the modal
                const requestId = button.getAttribute('data-request-id');
                const paymentDetailsRequestIdInput = paymentDetailsModal.querySelector('#paymentDetailsRequestId');
                paymentDetailsRequestIdInput.value = requestId;

                // Pre-fill modal fields with data attributes from the button (which should come from deoBankDetails)
                document.getElementById('modal_bank_name').value = button.getAttribute('data-bank-name') || '';
                document.getElementById('modal_account_holder_name').value = button.getAttribute('data-account-holder-name') || '';
                document.getElementById('modal_account_number').value = button.getAttribute('data-account-number') || '';
                document.getElementById('modal_ifsc_code').value = button.getAttribute('data-ifsc-code') || '';
                document.getElementById('modal_upi_id').value = button.getAttribute('data-upi-id') || '';
            });
        }

        // View Payment Details Modal (for DEO to view their own submitted details)
        const viewPaymentDetailsModal = document.getElementById('viewPaymentDetailsModal');
        if (viewPaymentDetailsModal) {
            viewPaymentDetailsModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // Button that triggered the modal
                document.getElementById('viewModalBankName').textContent = button.getAttribute('data-bank-name');
                document.getElementById('viewModalAccountHolderName').textContent = button.getAttribute('data-account-holder-name');
                document.getElementById('viewModalAccountNumber').textContent = button.getAttribute('data-account-number');
                document.getElementById('viewModalIfscCode').textContent = button.getAttribute('data-ifsc-code');
                document.getElementById('viewModalUpiId').textContent = button.getAttribute('data-upi-id');
            });
        }
    });
</script>
