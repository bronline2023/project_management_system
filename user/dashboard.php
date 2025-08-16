<?php
/**
 * user/dashboard.php
 *
 * This file represents the main dashboard for regular users (manager, coordinator, sales, assistant, accountant).
 * It displays an overview of the user's assigned tasks, including task counts by status,
 * and recent work assignments.
 *
 * It ensures that only authenticated non-admin users can access this page.
 */

// Include the configuration file for database connection and session management.
require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';   // Database interaction functions
require_once MODELS_PATH . 'auth.php'; // Authentication functions

// Restrict access to non-admin users only.
// If the user is not logged in or is an admin, redirect them.
if (!isLoggedIn() || $_SESSION['user_role'] === 'admin') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB(); // Establish database connection
$current_user_id = $_SESSION['user_id'];
$message = ''; // To store status messages

// --- Fetch User-Specific Dashboard Data ---

// 1. Task Counts for the current user
$activeTasks = 0;
$pendingTasks = 0;
$inProcessTasks = 0;
$completedTasks = 0;
$cancelledTasks = 0;

try {
    $stmt = $pdo->prepare("SELECT status, COUNT(*) AS count FROM work_assignments WHERE assigned_to_user_id = ? GROUP BY status");
    $stmt->execute([$current_user_id]);
    $taskCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Fetches status => count

    $pendingTasks = $taskCounts['pending'] ?? 0;
    $inProcessTasks = $taskCounts['in_process'] ?? 0;
    $completedTasks = $taskCounts['completed'] ?? 0;
    $cancelledTasks = $taskCounts['cancelled'] ?? 0;
    $activeTasks = $pendingTasks + $inProcessTasks; // Active tasks are pending + in_process
} catch (PDOException $e) {
    error_log("Error fetching user task counts: " . $e->getMessage());
    $message = '<div class="alert alert-danger" role="alert">Error loading task counts.</div>';
}

// 2. Fetch Recent Tasks for the current user (e.g., last 5 tasks)
$recentTasks = [];
try {
    $stmt = $pdo->prepare("SELECT wa.id, cl.name AS client_name, cat.name AS category_name, sub.name AS subcategory_name,
                                  wa.work_description, wa.deadline, wa.status, wa.payment_status, wa.created_at
                           FROM work_assignments wa
                           JOIN clients cl ON wa.client_id = cl.id
                           JOIN categories cat ON wa.category_id = cat.id
                           JOIN subcategories sub ON wa.subcategory_id = sub.id
                           WHERE wa.assigned_to_user_id = ?
                           ORDER BY wa.created_at DESC
                           LIMIT 5"); // Display last 5 tasks
    $stmt->execute([$current_user_id]);
    $recentTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching user recent tasks: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading recent tasks.</div>';
}

// 3. Fetch Unread Message Count for current user
$unreadMessageCount = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND read_status = FALSE");
    $stmt->execute([$current_user_id]);
    $unreadMessageCount = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching unread message count: " . $e->getMessage());
}


// Get currency symbol from settings for display
$currencySymbol = '$'; // Default
try {
    $stmt = $pdo->query("SELECT currency_symbol FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings && isset($settings['currency_symbol'])) {
        $currencySymbol = htmlspecialchars($settings['currency_symbol']);
    }
} catch (PDOException $e) {
    error_log("Error fetching currency symbol: " . $e->getMessage());
}

// Helper functions for badge colors (can be moved to a utilities file or included)
function getStatusBadgeColor($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'in_process': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getPaymentStatusBadgeColor($payment_status) {
    switch ($payment_status) {
        case 'pending': return 'warning';
        case 'paid_full': return 'success';
        case 'paid_partial': return 'info';
        case 'refunded': return 'danger';
        default: return 'secondary';
    }
}

// Include the header (contains HTML <head> and initial Bootstrap/CSS)
include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; // Include the sidebar for navigation ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">User Dashboard</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; // Custom message box ?>
            <script>
                // Auto-hide the message after 5 seconds
                setTimeout(function() {
                    const alert = document.querySelector('.alert');
                    if (alert) {
                        alert.classList.add('fade-out');
                        setTimeout(() => alert.remove(), 500); // Remove after fade-out
                    }
                }, 5000);
            </script>
        <?php endif; ?>

        <!-- Notification Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Notifications</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($unreadMessageCount > 0): ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-envelope me-2"></i>You have <span class="fw-bold"><?= $unreadMessageCount ?></span> unread messages. <a href="<?= BASE_URL ?>?page=messages" class="alert-link">Go to Messenger</a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No new notifications at the moment.</p>
                        <?php endif; ?>
                        <!-- Admin task assigned notification placeholder -->
                        <div class="alert alert-primary mt-2" role="alert">
                            <i class="fas fa-info-circle me-2"></i>Check "My Tasks" for any newly assigned work.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Status Overview for Current User -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>My Task Status Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-primary-subtle rounded-3 shadow-sm">
                                    <h6 class="text-primary mb-2">Total Active Tasks</h6>
                                    <h3 class="fw-bold text-primary"><?= $activeTasks ?></h3>
                                    <small class="text-muted">(Pending + In Process)</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-warning-subtle rounded-3 shadow-sm">
                                    <h6 class="text-warning mb-2">Pending Tasks</h6>
                                    <h3 class="fw-bold text-warning"><?= $pendingTasks ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-info-subtle rounded-3 shadow-sm">
                                    <h6 class="text-info mb-2">In Process Tasks</h6>
                                    <h3 class="fw-bold text-info"><?= $inProcessTasks ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-success-subtle rounded-3 shadow-sm">
                                    <h6 class="text-success mb-2">Completed Tasks</h6>
                                    <h3 class="fw-bold text-success"><?= $completedTasks ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Work Assignments -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list-ul me-2"></i>Recent Work Assignments</h5>
                        <a href="<?= BASE_URL ?>?page=my_tasks" class="btn btn-outline-light btn-sm rounded-pill"><i class="fas fa-eye me-2"></i>View All My Tasks</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Category</th>
                                        <th>Subcategory</th>
                                        <th>Description</th>
                                        <th>Deadline</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Assigned On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recentTasks)): ?>
                                        <?php foreach ($recentTasks as $task): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($task['id']) ?></td>
                                                <td><?= htmlspecialchars($task['client_name']) ?></td>
                                                <td><?= htmlspecialchars($task['category_name']) ?></td>
                                                <td><?= htmlspecialchars($task['subcategory_name']) ?></td>
                                                <td><?= htmlspecialchars(substr($task['work_description'], 0, 50)) ?><?= (strlen($task['work_description']) > 50 ? '...' : '') ?></td>
                                                <td><?= date('Y-m-d', strtotime($task['deadline'])) ?></td>
                                                <td><span class="badge bg-<?= getStatusBadgeColor($task['status']) ?>"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $task['status']))) ?></span></td>
                                                <td><span class="badge bg-<?= getPaymentStatusBadgeColor($task['payment_status']) ?>"><?= ucwords(htmlspecialchars(str_replace('_', ' ', $task['payment_status']))) ?></span></td>
                                                <td><?= date('Y-m-d H:i', strtotime($task['created_at'])) ?></td>
                                                <td>
                                                    <a href="<?= BASE_URL ?>?page=update_task&id=<?= htmlspecialchars($task['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill" title="Update Task Status"><i class="fas fa-edit"></i></a>
                                                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill ms-1" onclick="window.open('<?= BASE_URL ?>views/print_bill.php?task_id=<?= htmlspecialchars($task['id']) ?>', '_blank')" title="Print Bill"><i class="fas fa-print"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center">No recent tasks assigned to you.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Custom Confirmation Modal (re-used for consistency across system) -->
<div class="modal fade" id="customConfirmModal" tabindex="-1" aria-labelledby="customConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="customConfirmModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirmation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p id="confirm-message" class="lead text-center"></p>
            </div>
            <div class="modal-footer border-0 rounded-bottom-4 justify-content-center">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirm-link" class="btn btn-danger rounded-pill">Confirm</a>
            </div>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; // Include the footer ?>

<script>
    // Custom confirm dialog function (re-used across files for consistency)
    function showCustomConfirm(title, message, link) {
        const confirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
        document.getElementById('customConfirmModalLabel').textContent = title;
        document.getElementById('confirm-message').textContent = message;
        document.getElementById('confirm-link').href = link;
        confirmModal.show();
    }
</script>

<style>
    /* Custom CSS for fade-out alert */
    .alert.fade-out {
        opacity: 0;
        transition: opacity 0.5s ease-out;
    }
</style>
