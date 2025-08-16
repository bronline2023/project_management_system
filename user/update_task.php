<?php
/**
 * user/update_task.php
 *
 * This file allows regular users (managers, coordinators, etc.) to update details
 * of work assignments that are assigned to them.
 *
 * It provides a form to edit work assignment details and processes the updates.
 * Only authenticated users can access this page, and they can only update tasks
 * assigned to their user ID.
 */

// Include the configuration file for database connection and session management.
require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';    // Database interaction functions
require_once MODELS_PATH . 'auth.php';  // Authentication functions

// Restrict access to authenticated users only.
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB(); // Establish database connection
$message = '';      // To store success or error messages
$task = null;       // To store task details for display

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['user_role'];

// Ensure a task ID is provided
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($taskId === 0) {
    $message = '<div class="alert alert-danger" role="alert">No task ID provided.</div>';
} else {
    // --- Fetch Task Details ---
    try {
        $stmt = $pdo->prepare("
            SELECT
                wa.id,
                cl.client_name AS client_name,  /* Changed from cl.name to cl.client_name */
                us.name AS user_name,
                cat.name AS category_name,
                sub.name AS subcategory_name,
                wa.work_description,
                wa.deadline,
                wa.fee,
                wa.fee_mode,
                wa.maintenance_fee,
                wa.maintenance_fee_mode,
                wa.status,
                wa.payment_status,
                wa.user_notes,
                wa.created_at,
                wa.updated_at,
                wa.completed_at,
                wa.assigned_to_user_id,
                wa.client_id,
                wa.category_id,
                wa.subcategory_id
            FROM
                work_assignments wa
            JOIN
                clients cl ON wa.client_id = cl.id
            JOIN
                users us ON wa.assigned_to_user_id = us.id
            JOIN
                categories cat ON wa.category_id = cat.id
            JOIN
                subcategories sub ON wa.subcategory_id = sub.id
            WHERE
                wa.id = :task_id AND wa.assigned_to_user_id = :user_id
        ");
        $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
        $stmt->execute();
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            $message = '<div class="alert alert-danger" role="alert">Task not found or you do not have permission to update this task.</div>';
            $taskId = 0; // Invalidate task ID to prevent processing updates
        }

    } catch (PDOException $e) {
        // DEBUGGING: Display the specific database error to the user (REMOVE IN PRODUCTION)
        error_log("Error fetching task for update (ID: $taskId, User: $current_user_id): " . $e->getMessage());
        $message = '<div class="alert alert-danger" role="alert">Database error: Could not load task details. Please try again later.<br><strong>Error: ' . htmlspecialchars($e->getMessage()) . '</strong></div>';
        $taskId = 0; // Invalidate task ID on error
    }
}

// --- Handle Form Submission (Update Task) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task_submit']) && $taskId > 0) {
    // Collect and sanitize input
    $new_status = trim($_POST['status']);
    $new_user_notes = trim($_POST['user_notes']);

    // Initialize payment status with current value, then update if role allows and field is present
    $new_payment_status = $task['payment_status']; // Default to existing payment status

    // Only allow 'manager' and 'accountant' to update payment status
    if (in_array($current_user_role, ['manager', 'accountant'])) {
        // Safely get payment status from POST, defaulting to existing if not set (should be set if shown)
        $new_payment_status = trim($_POST['payment_status'] ?? $task['payment_status']);
    }

    // Validate input (basic validation)
    if (empty($new_status)) {
        $message = '<div class="alert alert-danger" role="alert">Task status is required.</div>';
    } else {
        try {
            // Determine which fields to update based on user role
            $sql_parts = [];
            $update_params = [
                ':status' => $new_status,
                ':user_notes' => $new_user_notes,
                ':updated_at' => date('Y-m-d H:i:s'),
                ':task_id' => $taskId,
                ':user_id' => $current_user_id // Ensure only tasks assigned to this user are updated
            ];
            $sql_parts[] = 'status = :status';
            $sql_parts[] = 'user_notes = :user_notes';
            $sql_parts[] = 'updated_at = :updated_at';

            // Conditionally add payment_status to SQL and params if the role allows it
            if (in_array($current_user_role, ['manager', 'accountant'])) {
                $sql_parts[] = 'payment_status = :payment_status';
                $update_params[':payment_status'] = $new_payment_status;
            }

            // If status is 'completed', set completed_at
            if ($new_status === 'completed' && empty($task['completed_at'])) {
                $sql_parts[] = 'completed_at = :completed_at';
                $update_params[':completed_at'] = date('Y-m-d H:i:s');
            } elseif ($new_status !== 'completed' && !empty($task['completed_at'])) {
                // If status changes from completed to something else, clear completed_at
                $sql_parts[] = 'completed_at = NULL';
                $update_params[':completed_at'] = NULL; // Ensure it's explicitly set to NULL
            } else {
                // If status didn't change to completed or from completed, keep existing completed_at
                $sql_parts[] = 'completed_at = :existing_completed_at';
                $update_params[':existing_completed_at'] = $task['completed_at'];
            }

            $sql = "UPDATE work_assignments SET " . implode(', ', $sql_parts) . " WHERE id = :task_id AND assigned_to_user_id = :user_id";

            $stmt = $pdo->prepare($sql);
            foreach ($update_params as $key => &$val) {
                // For NULL values, specify PDO::PARAM_NULL
                if ($val === NULL) {
                    $stmt->bindParam($key, $val, PDO::PARAM_NULL);
                } else {
                    $stmt->bindParam($key, $val);
                }
            }

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success" role="alert">Task updated successfully!</div>';
                // Re-fetch task to show updated details immediately
                $stmt = $pdo->prepare("
                    SELECT
                        wa.id,
                        cl.client_name AS client_name, /* Changed from cl.name to cl.client_name */
                        us.name AS user_name,
                        cat.name AS category_name,
                        sub.name AS subcategory_name,
                        wa.work_description,
                        wa.deadline,
                        wa.fee,
                        wa.fee_mode,
                        wa.maintenance_fee,
                        wa.maintenance_fee_mode,
                        wa.status,
                        wa.payment_status,
                        wa.user_notes,
                        wa.created_at,
                        wa.updated_at,
                        wa.completed_at,
                        wa.assigned_to_user_id,
                        wa.client_id,
                        wa.category_id,
                        wa.subcategory_id
                    FROM
                        work_assignments wa
                    JOIN
                        clients cl ON wa.client_id = cl.id
                    JOIN
                        users us ON wa.assigned_to_user_id = us.id
                    JOIN
                        categories cat ON wa.category_id = cat.id
                    JOIN
                        subcategories sub ON wa.subcategory_id = sub.id
                    WHERE
                        wa.id = :task_id AND wa.assigned_to_user_id = :user_id
                ");
                $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
                $stmt->execute();
                $task = $stmt->fetch(PDO::FETCH_ASSOC);

            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Task update SQL error: " . $errorInfo[2]);
                $message = '<div class="alert alert-danger" role="alert">Error updating task. Please try again.</div>';
            }
        } catch (PDOException $e) {
            error_log("Task update PDOException: " . $e->getMessage());
            $message = '<div class="alert alert-danger" role="alert">Database error: ' . htmlspecialchars($e->getMessage()) . '.</div>';
        }
    }
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


// Include header and sidebar
include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; // Include the sidebar for navigation ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Update Work Assignment</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; // Custom message box ?>
            <script>
                // Auto-hide the message after 5 seconds
                // This function call has been moved to assets/js/script.js for global handling.
                // Call the global function
                window.setupAutoHideAlerts();
            </script>
        <?php endif; ?>

        <?php if ($task): ?>
            <div class="card shadow-sm rounded-3 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Task Details - #<?= htmlspecialchars($task['id']) ?></h5>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>?page=update_task&id=<?= htmlspecialchars($task['id']) ?>" method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Client:</label>
                                <input type="text" class="form-control rounded-pill" value="<?= htmlspecialchars($task['client_name']) ?>" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assigned To:</label>
                                <input type="text" class="form-control rounded-pill" value="<?= htmlspecialchars($task['user_name']) ?>" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category:</label>
                                <input type="text" class="form-control rounded-pill" value="<?= htmlspecialchars($task['category_name']) ?>" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subcategory:</label>
                                <input type="text" class="form-control rounded-pill" value="<?= htmlspecialchars($task['subcategory_name']) ?>" disabled>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="work_description" class="form-label">Work Description:</label>
                                <textarea class="form-control rounded-3" id="work_description" rows="3" disabled><?= htmlspecialchars($task['work_description']) ?></textarea>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fee:</label>
                                <input type="text" class="form-control rounded-pill" value="<?= $currencySymbol ?><?= number_format($task['fee'], 2) ?> (<?= htmlspecialchars($task['fee_mode']) ?>)" disabled>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Maintenance Fee:</label>
                                <input type="text" class="form-control rounded-pill" value="<?= $currencySymbol ?><?= number_format($task['maintenance_fee'], 2) ?> (<?= htmlspecialchars($task['maintenance_fee_mode']) ?>)" disabled>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Deadline:</label>
                                <input type="text" class="form-control rounded-pill" value="<?= date('Y-m-d', strtotime($task['deadline'])) ?>" disabled>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select rounded-pill" id="status" name="status" required>
                                    <option value="pending" <?= ($task['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                                    <option value="in_process" <?= ($task['status'] === 'in_process') ? 'selected' : '' ?>>In Process</option>
                                    <option value="completed" <?= ($task['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= ($task['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                <div class="invalid-feedback">Please select a status.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assigned Date:</label>
                                <input type="text" class="form-control rounded-pill" value="<?= date('Y-m-d H:i', strtotime($task['created_at'])) ?>" disabled>
                            </div>
                            <?php if ($task['completed_at']): ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Completed Date:</label>
                                    <input type="text" class="form-control rounded-pill" value="<?= date('Y-m-d H:i', strtotime($task['completed_at'])) ?>" disabled>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-12 mb-3">
                                <label for="user_notes" class="form-label">Your Notes:</label>
                                <textarea class="form-control rounded-3" id="user_notes" name="user_notes" rows="3"><?= htmlspecialchars($task['user_notes']) ?></textarea>
                            </div>

                            <?php
                            // Payment Status field only visible for specific roles
                            if (in_array($current_user_role, ['manager', 'accountant'])):
                            ?>
                            <div class="col-md-6 mb-3">
                                <label for="payment_status" class="form-label">Payment Status <span class="text-danger">*</span></label>
                                <select class="form-select rounded-pill" id="payment_status" name="payment_status" required>
                                    <option value="pending" <?= ($task['payment_status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                                    <option value="paid_partial" <?= ($task['payment_status'] === 'paid_partial') ? 'selected' : '' ?>>Paid Partial</option>
                                    <option value="paid_full" <?= ($task['payment_status'] === 'paid_full') ? 'selected' : '' ?>>Paid Full</option>
                                    <option value="refunded" <?= ($task['payment_status'] === 'refunded') ? 'selected' : '' ?>>Refunded</option>
                                </select>
                                <div class="invalid-feedback">Please select a payment status.</div>
                            </div>
                            <?php endif; ?>

                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" name="update_task_submit" class="btn btn-primary rounded-pill px-4">
                                <i class="fas fa-save me-2"></i>Update Task
                            </button>
                            <a href="<?= BASE_URL ?>?page=my_tasks" class="btn btn-secondary rounded-pill px-4">
                                <i class="fas fa-times-circle me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                Task details could not be loaded. Please ensure the task ID is valid and it is assigned to you.
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; // Include the footer ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap validation for the form
        window.setupFormValidation('updateTaskForm'); // Assuming your form has id="updateTaskForm"
                                                     // If not, it will apply to the first form on the page
    });
</script>
