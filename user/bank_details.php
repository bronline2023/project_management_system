<?php
/**
 * user/bank_details.php
 *
 * This file allows Data Entry Operators (DEOs) to manage their bank account
 * and UPI details for withdrawals. They can add or update these details.
 *
 * It ensures that only authenticated DEO users can access this page.
 */

// Include the main configuration file using ROOT_PATH.
require_once ROOT_PATH . 'config.php';

// Now, other includes can safely use constants from config.php
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';

// Restrict access to Data Entry Operator users only.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'data_entry_operator') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';
$bankDetails = []; // To store existing bank details of the DEO

// Fetch existing bank details for the current DEO
try {
    $stmt = $pdo->prepare("SELECT bank_name, account_holder_name, account_number, ifsc_code, upi_id FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
    $stmt->execute();
    $bankDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    // If no details exist, initialize with empty strings
    if (!$bankDetails) {
        $bankDetails = [
            'bank_name' => '',
            'account_holder_name' => '',
            'account_number' => '',
            'ifsc_code' => '',
            'upi_id' => ''
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching DEO bank details: " . $e->getMessage());
    $message = '<div class="alert alert-danger" role="alert">Error loading your bank details.</div>';
}

// Handle form submission for saving/updating bank details
if (isset($_POST['save_bank_details'])) {
    $bankName = trim($_POST['bank_name'] ?? '');
    $accountHolderName = trim($_POST['account_holder_name'] ?? '');
    $accountNumber = trim($_POST['account_number'] ?? '');
    $ifscCode = trim($_POST['ifsc_code'] ?? '');
    $upiId = trim($_POST['upi_id'] ?? '');

    // Basic validation
    if (empty($bankName) || empty($accountHolderName) || empty($accountNumber) || empty($ifscCode)) {
        $message = '<div class="alert alert-danger" role="alert">Please fill in all required bank details (Bank Name, Account Holder Name, Account Number, IFSC Code).</div>';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE users
                SET bank_name = :bank_name,
                    account_holder_name = :account_holder_name,
                    account_number = :account_number,
                    ifsc_code = :ifsc_code,
                    upi_id = :upi_id
                WHERE id = :user_id
            ");
            $stmt->bindParam(':bank_name', $bankName);
            $stmt->bindParam(':account_holder_name', $accountHolderName);
            $stmt->bindParam(':account_number', $accountNumber);
            $stmt->bindParam(':ifsc_code', $ifscCode);
            $stmt->bindParam(':upi_id', $upiId);
            $stmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success" role="alert">Your bank details have been saved successfully!</div>';
                // Update $bankDetails array to reflect saved changes
                $bankDetails = [
                    'bank_name' => $bankName,
                    'account_holder_name' => $accountHolderName,
                    'account_number' => $accountNumber,
                    'ifsc_code' => $ifscCode,
                    'upi_id' => $upiId
                ];
            } else {
                $message = '<div class="alert alert-danger" role="alert">Failed to save bank details. Please try again.</div>';
            }
        } catch (PDOException $e) {
            error_log("Error saving DEO bank details: " . $e->getMessage());
            $message = '<div class="alert alert-danger" role="alert">An error occurred while saving your bank details.</div>';
        }
    }
}

include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">My Bank Details</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; ?>
            <script>
                setupAutoHideAlerts();
            </script>
        <?php endif; ?>

        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-bank me-2"></i>Manage Your Payment Information</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
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
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
