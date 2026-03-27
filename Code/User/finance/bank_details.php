<?php
/**
 * user/bank_details.php
 * This file allows users (specifically DEOs) to manage their bank details.
 * All layout and authentication are handled by index.php.
 */

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';

// Fetch existing bank details
$bankDetails = fetchOne($pdo, "SELECT bank_name, account_holder_name, account_number, ifsc_code, upi_id FROM users WHERE id = :user_id", [':user_id' => $currentUserId]);
if (!$bankDetails) {
    $bankDetails = ['bank_name' => '', 'account_holder_name' => '', 'account_number' => '', 'ifsc_code' => '', 'upi_id' => ''];
}

// Handle form submission
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}
?>

<h2 class="mb-4">My Bank Details</h2>

<?php if (!empty($message)): ?>
    <?php include VIEWS_PATH . 'components/message_box.php'; ?>
<?php endif; ?>

<div class="card shadow-sm rounded-3 mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-university me-2"></i>Manage Your Payment Information</h5>
    </div>
    <div class="card-body">
        <form action="index.php" method="POST">
            <input type="hidden" name="page" value="bank_details">
            <input type="hidden" name="action" value="save_bank_details">
            <div class="mb-3">
                <label for="bank_name" class="form-label">Bank Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control rounded-pill" id="bank_name" name="bank_name" value="<?= htmlspecialchars($bankDetails['bank_name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="account_holder_name" class="form-label">Account Holder Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control rounded-pill" id="account_holder_name" name="account_holder_name" value="<?= htmlspecialchars($bankDetails['account_holder_name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="account_number" class="form-label">Account Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control rounded-pill" id="account_number" name="account_number" value="<?= htmlspecialchars($bankDetails['account_number'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="ifsc_code" class="form-label">IFSC Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control rounded-pill" id="ifsc_code" name="ifsc_code" value="<?= htmlspecialchars($bankDetails['ifsc_code'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="upi_id" class="form-label">UPI ID (Optional)</label>
                <input type="text" class="form-control rounded-pill" id="upi_id" name="upi_id" value="<?= htmlspecialchars($bankDetails['upi_id'] ?? '') ?>">
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" name="save_bank_details" class="btn btn-primary rounded-pill px-4">
                    <i class="fas fa-save me-2"></i>Save Details
                </button>
            </div>
        </form>
    </div>
</div>