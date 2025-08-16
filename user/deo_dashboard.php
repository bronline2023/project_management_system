<?php
/**
 * user/deo_dashboard.php
 *
 * This file represents the main dashboard for Data Entry Operators.
 * It displays an overview of their submitted client entries, their approval status,
 * and calculated earnings based on approved entries.
 *
 * It ensures that only authenticated Data Entry Operator users can access this page.
 */

// Include the main configuration file using ROOT_PATH.
require_once ROOT_PATH . 'config.php';

// Now, other includes can safely use constants from config.php
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';
require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php'; // IMPORTANT: This line includes the functions
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
$currencySymbol = CURRENCY_SYMBOL; // Use the constant defined in config.php
$minimumWithdrawalAmount = MINIMUM_WITHDRAWAL_AMOUNT; // Use the constant from config.php

// Fetch DEO's saved bank details
$deoBankDetails = [];
try {
    $stmt = $pdo->prepare("SELECT bank_name, account_holder_name, account_number, ifsc_code, upi_id FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
    $stmt->execute();
    $deoBankDetails = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching DEO bank details for dashboard: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading your saved bank details.</div>';
}


// --- Handle Withdrawal Request Submission ---
if (isset($_POST['request_withdrawal'])) {
    $withdrawalAmount = filter_input(INPUT_POST, 'withdrawal_amount', FILTER_VALIDATE_FLOAT);

    // Calculate total earnings from approved posts
    $approvedRecruitmentPosts = getDeoApprovedPostCount($currentUserId);
    $earningPerPost = getEarningPerApprovedPost();
    $totalRecruitmentEarnings = $approvedRecruitmentPosts * $earningPerPost;

    // Fetch total paid amount from withdrawal requests
    $totalPaidWithdrawals = 0;
    try {
        $stmt = $pdo->prepare("SELECT SUM(amount) FROM withdrawal_requests WHERE deo_id = :deo_id AND status = 'paid'");
        $stmt->bindParam(':deo_id', $currentUserId, PDO::PARAM_INT);
        $stmt->execute();
        $totalPaidWithdrawals = (float)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching total paid withdrawals: " . $e->getMessage());
    }

    $availableBalance = $totalRecruitmentEarnings - $totalPaidWithdrawals;

    // Check if bank details are saved before allowing withdrawal request
    if (empty($deoBankDetails['bank_name']) || empty($deoBankDetails['account_number']) || empty($deoBankDetails['ifsc_code'])) {
        $message = '<div class="alert alert-warning" role="alert">Please save your bank details in "My Bank Details" section before requesting a withdrawal.</div>';
    } elseif ($withdrawalAmount === false || $withdrawalAmount <= 0) {
        $message = '<div class="alert alert-danger" role="alert">Please enter a valid positive amount for withdrawal.</div>';
    } elseif ($withdrawalAmount < $minimumWithdrawalAmount) {
        $message = '<div class="alert alert-danger" role="alert">Minimum withdrawal amount is ' . $currencySymbol . number_format($minimumWithdrawalAmount, 2) . '.</div>';
    } elseif ($withdrawalAmount > $availableBalance) {
        $message = '<div class="alert alert-danger" role="alert">Requested amount ' . $currencySymbol . number_format($withdrawalAmount, 2) . ' exceeds your available balance of ' . $currencySymbol . number_format($availableBalance, 2) . '.</div>';
    } else {
        // Check for existing pending/processing requests
        $existingRequests = getWithdrawalRequestsByDeo($currentUserId, 'pending');
        $existingProcessingRequests = getWithdrawalRequestsByDeo($currentUserId, 'processing');
        $existingDetailsRequested = getWithdrawalRequestsByDeo($currentUserId, 'details_requested');

        if (!empty($existingRequests) || !empty($existingProcessingRequests) || !empty($existingDetailsRequested)) {
            $message = '<div class="alert alert-warning" role="alert">You already have a pending, processing, or details requested withdrawal. Please wait for it to be processed.</div>';
        } else {
            // Pass saved bank details directly when adding withdrawal request
            $paymentDetails = [
                'bank_name' => $deoBankDetails['bank_name'],
                'account_holder_name' => $deoBankDetails['account_holder_name'],
                'account_number' => $deoBankDetails['account_number'],
                'ifsc_code' => $deoBankDetails['ifsc_code'],
                'upi_id' => $deoBankDetails['upi_id']
            ];
            // Add the request with initial status 'pending' and include payment details
            if (addWithdrawalRequest($currentUserId, $withdrawalAmount, $paymentDetails)) {
                $message = '<div class="alert alert-success" role="alert">Withdrawal request for ' . $currencySymbol . number_format($withdrawalAmount, 2) . ' submitted successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger" role="alert">Failed to submit withdrawal request.</div>';
            }
        }
    }
}

// --- Handle Payment Details Submission (if a request is 'details_requested') ---
// This block is also present in user/withdrawals.php. It's here for immediate action from dashboard.
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


// --- Fetch DEO Specific Data ---

// 1. Total Submitted Clients (from previous functionality)
$totalSubmittedClients = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE submitted_by_user_id = :user_id");
    $stmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
    $stmt->execute();
    $totalSubmittedClients = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching total submitted clients for DEO: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading submitted clients count.</div>';
}

// 2. Recruitment Post Counts and Earnings
$totalSubmittedRecruitmentPosts = 0;
$approvedRecruitmentPosts = 0;
$pendingRecruitmentPosts = 0;
$rejectedRecruitmentPosts = 0;
$returnedForEditRecruitmentPosts = 0;

$earningPerPost = getEarningPerApprovedPost(); // Get earning rate from settings
$totalRecruitmentEarnings = 0;

try {
    $approvedRecruitmentPosts = getDeoApprovedPostCount($currentUserId);
    $pendingRecruitmentPosts = getDeoPendingPostCount($currentUserId);
    $rejectedRecruitmentPosts = getDeoRejectedPostCount($currentUserId);
    $returnedForEditRecruitmentPosts = getDeoReturnedForEditPostCount($currentUserId);

    $totalSubmittedRecruitmentPosts = $approvedRecruitmentPosts + $pendingRecruitmentPosts + $rejectedRecruitmentPosts + $returnedForEditRecruitmentPosts;
    $totalRecruitmentEarnings = $approvedRecruitmentPosts * $earningPerPost;

} catch (Exception $e) {
    error_log("Error fetching recruitment post stats for DEO: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading recruitment post stats and earnings.</div>';
}

// Calculate Available Balance for Withdrawal
$totalPaidWithdrawals = 0;
try {
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM withdrawal_requests WHERE deo_id = :deo_id AND status = 'paid'");
    $stmt->bindParam(':deo_id', $currentUserId, PDO::PARAM_INT);
    $stmt->execute();
    $totalPaidWithdrawals = (float)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching total paid withdrawals: " . $e->getMessage());
}
$availableBalance = $totalRecruitmentEarnings - $totalPaidWithdrawals;

// 3. List of Recent Submitted Recruitment Posts
$recentSubmittedRecruitmentPosts = getDeoRecruitmentPosts($currentUserId, 'all');

// 4. List of Withdrawal Requests (for summary on dashboard)
$withdrawalRequests = getWithdrawalRequestsByDeo($currentUserId, 'all'); // Fetch all for summary

// Check if there's a 'details_requested' withdrawal request
$detailsRequestedWithdrawal = null;
foreach ($withdrawalRequests as $req) {
    if ($req['status'] === 'details_requested') {
        $detailsRequestedWithdrawal = $req;
        break;
    }
}


// Helper function for approval status badge color (already in recruitment_post.php, but redefined for safety)
// This check prevents "Cannot redeclare function" errors if it's already included.
if (!function_exists('getApprovalStatusBadgeColor')) {
    function getApprovalStatusBadgeColor($status) {
        switch ($status) {
            case 'pending': return 'warning';
            case 'approved': return 'success';
            case 'rejected': return 'danger';
            case 'returned_for_edit': return 'info'; // New color for returned status
            default: return 'secondary';
        }
    }
}


include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Data Entry Operator Dashboard</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; ?>
            <script>
                setupAutoHideAlerts();
            </script>
        <?php endif; ?>

        <!-- Overview Cards for Recruitment Posts and Earnings -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card shadow-sm rounded-3 p-3 text-center bg-primary text-white">
                    <h6 class="mb-2">Total Posts Submitted</h6>
                    <h3 class="fw-bold"><?= htmlspecialchars($totalSubmittedRecruitmentPosts) ?></h3>
                    <small>Recruitment entries you've submitted</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card shadow-sm rounded-3 p-3 text-center bg-success text-white">
                    <h6 class="mb-2">Approved Posts</h6>
                    <h3 class="fw-bold"><?= htmlspecialchars($approvedRecruitmentPosts) ?></h3>
                    <small>Recruitment entries approved by Admin</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card shadow-sm rounded-3 p-3 text-center bg-info text-white">
                    <h6 class="mb-2">Your Total Earnings</h6>
                    <h3 class="fw-bold"><?= $currencySymbol ?><?= number_format($totalRecruitmentEarnings, 2) ?></h3>
                    <small>(<?= number_format($earningPerPost, 2) ?> <?= $currencySymbol ?> per approved post)</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card shadow-sm rounded-3 p-3 text-center bg-warning text-dark">
                    <h6 class="mb-2">Posts Returned for Edit</h6>
                    <h3 class="fw-bold"><?= htmlspecialchars($returnedForEditRecruitmentPosts) ?></h3>
                    <small>Action required from you</small>
                </div>
            </div>
        </div>

        <!-- Withdrawal Section -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-wallet me-2"></i>Withdrawal Management</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card bg-light p-3 rounded-3 shadow-sm h-100">
                            <h6 class="mb-2">Available Balance for Withdrawal</h6>
                            <h3 class="fw-bold text-primary"><?= $currencySymbol ?><?= number_format($availableBalance, 2) ?></h3>
                            <small>Minimum withdrawal amount: <?= $currencySymbol ?><?= number_format($minimumWithdrawalAmount, 2) ?></small>
                            <p class="mt-2 mb-0"><a href="<?= BASE_URL ?>?page=bank_details" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="fas fa-edit me-1"></i>Manage My Bank Details</a></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light p-3 rounded-3 shadow-sm h-100">
                            <?php if ($detailsRequestedWithdrawal): ?>
                                <h6 class="mb-3 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Action Required: Submit Payment Details</h6>
                                <p>Admin has requested your bank/UPI details for Request ID: <strong><?= htmlspecialchars($detailsRequestedWithdrawal['id']) ?></strong> for amount <strong><?= $currencySymbol ?><?= number_format($detailsRequestedWithdrawal['amount'], 2) ?></strong>.</p>
                                <button type="button" class="btn btn-danger rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#paymentDetailsModal" data-request-id="<?= htmlspecialchars($detailsRequestedWithdrawal['id']) ?>"
                                    data-bank-name="<?= htmlspecialchars($deoBankDetails['bank_name'] ?? '') ?>"
                                    data-account-holder-name="<?= htmlspecialchars($deoBankDetails['account_holder_name'] ?? '') ?>"
                                    data-account-number="<?= htmlspecialchars($deoBankDetails['account_number'] ?? '') ?>"
                                    data-ifsc-code="<?= htmlspecialchars($deoBankDetails['ifsc_code'] ?? '') ?>"
                                    data-upi-id="<?= htmlspecialchars($deoBankDetails['upi_id'] ?? '') ?>">
                                    <i class="fas fa-money-check-alt me-2"></i>Submit Details Now
                                </button>
                                <p class="text-muted mt-2"><small>Please submit details within 7 days.</small></p>
                            <?php else: ?>
                                <h6 class="mb-3">Request New Withdrawal</h6>
                                <form action="" method="POST">
                                    <div class="mb-3">
                                        <label for="withdrawal_amount" class="form-label visually-hidden">Amount</label>
                                        <input type="number" step="0.01" min="<?= $minimumWithdrawalAmount ?>" class="form-control rounded-pill" id="withdrawal_amount" name="withdrawal_amount" placeholder="Enter amount (Min: <?= $currencySymbol ?><?= number_format($minimumWithdrawalAmount, 2) ?>)" required>
                                    </div>
                                    <button type="submit" name="request_withdrawal" class="btn btn-primary rounded-pill px-4" <?= ($availableBalance < $minimumWithdrawalAmount || empty($deoBankDetails['bank_name'])) ? 'disabled' : '' ?>>
                                        <i class="fas fa-paper-plane me-2"></i>Request Withdrawal
                                    </button>
                                    <?php if (empty($deoBankDetails['bank_name'])): ?>
                                        <small class="text-danger d-block mt-2">Please save your bank details first.</small>
                                    <?php elseif ($availableBalance < $minimumWithdrawalAmount): ?>
                                        <small class="text-danger d-block mt-2">You need at least <?= $currencySymbol ?><?= number_format($minimumWithdrawalAmount, 2) ?> to request a withdrawal.</small>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <h5 class="mt-4 mb-3"><i class="fas fa-list-alt me-2"></i>Recent Withdrawal History</h5>
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
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Display only recent 5 requests on dashboard
                                $displayCount = 0;
                                foreach ($withdrawalRequests as $request):
                                    if ($displayCount >= 5) break; // Limit to 5
                                    ?>
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
                                <?php
                                    $displayCount++;
                                endforeach;
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($withdrawalRequests) > 5): ?>
                        <div class="text-center mt-3">
                            <a href="<?= BASE_URL ?>?page=my_withdrawals" class="btn btn-outline-secondary rounded-pill">View All Withdrawals</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-center text-muted">No withdrawal requests found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status Breakdown for Recruitment Posts -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Recruitment Post Status Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <div class="p-3 bg-warning-subtle rounded-3 shadow-sm">
                                    <h6 class="text-warning mb-2">Pending Posts</h6>
                                    <h3 class="fw-bold text-warning"><?= htmlspecialchars($pendingRecruitmentPosts) ?></h3>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 bg-success-subtle rounded-3 shadow-sm">
                                    <h6 class="text-success mb-2">Approved Posts</h6>
                                    <h3 class="fw-bold text-success"><?= htmlspecialchars($approvedRecruitmentPosts) ?></h3>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 bg-danger-subtle rounded-3 shadow-sm">
                                    <h6 class="text-danger mb-2">Rejected Posts</h6>
                                    <h3 class="fw-bold text-danger"><?= htmlspecialchars($rejectedRecruitmentPosts) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Submitted Recruitment Posts -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Submitted Recruitment Posts</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentSubmittedRecruitmentPosts)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Job Title</th>
                                            <th>Submitted At</th>
                                            <th>Approval Status</th>
                                            <th>Approved By</th>
                                            <th>Admin Comments</th> <!-- New column -->
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $displayCount = 0;
                                        foreach ($recentSubmittedRecruitmentPosts as $post):
                                            if ($displayCount >= 5) break; // Limit to 5
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($post['id']) ?></td>
                                                <td><?= htmlspecialchars($post['job_title']) ?></td>
                                                <td><?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= getApprovalStatusBadgeColor($post['approval_status']) ?>">
                                                        <?= ucwords(htmlspecialchars(str_replace('_', ' ', $post['approval_status']))) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($post['approved_by_name'] ?? 'N/A') ?></td>
                                                <td>
                                                    <?php if ($post['approval_status'] === 'returned_for_edit' && !empty($post['admin_comments'])): ?>
                                                        <span class="text-info" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($post['admin_comments']) ?>">
                                                            <i class="fas fa-comment-dots me-1"></i> View Comments
                                                        </span>
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (in_array($post['approval_status'], ['pending', 'returned_for_edit'])): ?>
                                                        <a href="<?= BASE_URL ?>?page=add_recruitment_post&id=<?= htmlspecialchars($post['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill" title="Edit Post"><i class="fas fa-edit"></i> Edit</a>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-outline-secondary rounded-pill" disabled><i class="fas fa-edit"></i> Edit</button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php
                                            $displayCount++;
                                        endforeach;
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($recentSubmittedRecruitmentPosts) > 5): ?>
                                <div class="text-center mt-3">
                                    <a href="<?= BASE_URL ?>?page=manage_recruitment_posts" class="btn btn-outline-secondary rounded-pill">View All Recruitment Posts</a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-center text-muted">No recent submitted recruitment entries found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Original Client Entries Section (if still relevant for DEO) -->
        <h3 class="mb-4 mt-5">Your Client Entries Overview</h3>
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm rounded-3 p-3 text-center bg-warning text-white">
                    <h6 class="mb-2">Total Submitted Clients</h6>
                    <h3 class="fw-bold"><?= htmlspecialchars($totalSubmittedClients) ?></h3>
                    <small>Client entries you've submitted</small>
                </div>
            </div>
            <!-- You can add more client-related stats here if needed -->
        </div>
        <!-- You might want to add a table for recent client entries here too, similar to recruitment posts -->

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

                // Pre-fill modal fields with saved bank details
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
