<?php
/**
 * admin/edit_task.php
 *
 * This file allows the administrator to view and edit details of an existing work task.
 * It includes updating task status, payment status, client, assigned user,
 * category, subcategory, work description, deadline, fees, and notes.
 *
 * It ensures that only authenticated admin users can access this page.
 */

// Include the configuration file for database connection and session management.
require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';   // Database interaction functions
require_once MODELS_PATH . 'auth.php'; // Authentication functions

// Restrict access to admin users only.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB(); // Establish database connection
$message = ''; // To store success or error messages
$task_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- Fetch Task Details ---
$task = null;
if ($task_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT wa.*, cl.client_name AS client_name, us.name AS user_name,
                                      cat.name AS category_name, sub.name AS subcategory_name
                               FROM work_assignments wa
                               JOIN clients cl ON wa.client_id = cl.id
                               JOIN users us ON wa.assigned_to_user_id = us.id
                               JOIN categories cat ON wa.category_id = cat.id
                               JOIN subcategories sub ON wa.subcategory_id = sub.id
                               WHERE wa.id = ?");
        $stmt->execute([$task_id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            $message = '<div class="alert alert-danger" role="alert">Task not found.</div>';
            $task_id = 0; // Reset task_id if not found
        }
    } catch (PDOException $e) {
        error_log("Error fetching task details: " . $e->getMessage());
        $message = '<div class="alert alert-danger" role="alert">Error loading task details: ' . $e->getMessage() . '</div>';
        $task_id = 0;
    }
} else {
    $message = '<div class="alert alert-warning" role="alert">No task ID provided.</div>';
}

// --- Handle Task Update Form Submission ---
if (isset($_POST['update_task']) && $task_id > 0) {
    $client_id = $_POST['client_id'];
    $assigned_to_user_id = $_POST['assigned_to_user_id'];
    $category_id = $_POST['category_id'];
    $subcategory_id = $_POST['subcategory_id'];
    $work_description = trim($_POST['work_description']);
    $deadline = $_POST['deadline'];
    $fee = floatval($_POST['fee']);
    $fee_mode = $_POST['fee_mode'];
    $maintenance_fee = floatval($_POST['maintenance_fee'] ?? 0);
    $maintenance_fee_mode = $_POST['maintenance_fee_mode'] ?? 'pending';
    $status = $_POST['status'];
    $payment_status = $_POST['payment_status'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    $user_notes = trim($_POST['user_notes'] ?? ''); // Admin can also see/edit user notes

    // Basic validation
    if (empty($client_id) || empty($assigned_to_user_id) || empty($category_id) || empty($subcategory_id) || empty($work_description) || empty($deadline) || !is_numeric($fee) || $fee < 0 || empty($fee_mode) || empty($status) || empty($payment_status)) {
        $message = '<div class="alert alert-danger" role="alert">Please fill in all required fields.</div>';
    } else {
        try {
            $update_sql = "UPDATE work_assignments SET
                            client_id = ?, assigned_to_user_id = ?, category_id = ?, subcategory_id = ?,
                            work_description = ?, deadline = ?, fee = ?, fee_mode = ?,
                            maintenance_fee = ?, maintenance_fee_mode = ?, status = ?, payment_status = ?,
                            admin_notes = ?, user_notes = ?, completed_at = ?
                           WHERE id = ?";

            $completed_at = null;
            if ($status === 'completed' && empty($task['completed_at'])) {
                $completed_at = date('Y-m-d H:i:s'); // Set completion timestamp if task is now completed
            } elseif ($status !== 'completed' && !empty($task['completed_at'])) {
                $completed_at = null; // Clear completion timestamp if task is no longer completed
            } else {
                $completed_at = $task['completed_at']; // Keep existing timestamp
            }


            $stmt = $pdo->prepare($update_sql);
            $stmt->execute([
                $client_id, $assigned_to_user_id, $category_id, $subcategory_id,
                $work_description, $deadline, $fee, $fee_mode,
                $maintenance_fee, $maintenance_fee_mode, $status, $payment_status,
                $admin_notes, $user_notes, $completed_at, $task_id
            ]);

            $message = '<div class="alert alert-success" role="alert">Task updated successfully!</div>';

            // Re-fetch the updated task details to display current state
            $stmt = $pdo->prepare("SELECT wa.*, cl.client_name AS client_name, us.name AS user_name,
                                          cat.name AS category_name, sub.name AS subcategory_name
                                   FROM work_assignments wa
                                   JOIN clients cl ON wa.client_id = cl.id
                                   JOIN users us ON wa.assigned_to_user_id = us.id
                                   JOIN categories cat ON wa.category_id = cat.id
                                   JOIN subcategories sub ON wa.subcategory_id = sub.id
                                   WHERE wa.id = ?");
            $stmt->execute([$task_id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error updating task: " . $e->getMessage());
            $message = '<div class="alert alert-danger" role="alert">Error updating task: ' . $e->getMessage() . '</div>';
        }
    }
}

// --- Fetch Data for Dropdowns ---
// (Needed even if task not found, for consistency)

// Fetch Clients
$clients = [];
try {
    $stmt = $pdo->query("SELECT id, client_name FROM clients ORDER BY client_name ASC"); // Changed 'name' to 'client_name'
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching clients for dropdown: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading clients dropdown.</div>';
}

// Fetch Users
$users = [];
try {
    $stmt = $pdo->query("SELECT id, name, role FROM users ORDER BY name ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users for dropdown: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading users dropdown.</div>';
}

// Fetch Categories
$categories = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories for dropdown: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading categories dropdown.</div>';
}

// Fetch Subcategories for the initially selected category (if a task is loaded)
$initial_subcategories = [];
if ($task && isset($task['category_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, fare FROM subcategories WHERE category_id = ? ORDER BY name ASC");
        $stmt->execute([$task['category_id']]);
        $initial_subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching initial subcategories: " . $e->getMessage());
        $message .= '<div class="alert alert-danger" role="alert">Error loading initial subcategories.</div>';
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

// Helper functions for badge colors (can be moved to a utilities file)
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
        <h2 class="mb-4">Edit Work Task <?= $task ? '(#' . htmlspecialchars($task['id']) . ')' : '' ?></h2>

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

        <?php if ($task): ?>
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Task Details</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST" id="editTaskForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="client_id" class="form-label">Client <span class="text-danger">*</span></label>
                            <select class="form-select rounded-pill" id="client_id" name="client_id" required>
                                <option value="">Select Client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= htmlspecialchars($client['id']) ?>" <?= ($task['client_id'] == $client['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($client['client_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="assigned_to_user_id" class="form-label">Assign To User <span class="text-danger">*</span></label>
                            <select class="form-select rounded-pill" id="assigned_to_user_id" name="assigned_to_user_id" required>
                                <option value="">Select User</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= htmlspecialchars($user['id']) ?>" <?= ($task['assigned_to_user_id'] == $user['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['name']) ?> (<?= ucwords(htmlspecialchars(str_replace('_', ' ', $user['role']))) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select rounded-pill" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['id']) ?>" <?= ($task['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="subcategory_id" class="form-label">Subcategory <span class="text-danger">*</span></label>
                            <select class="form-select rounded-pill" id="subcategory_id" name="subcategory_id" required>
                                <option value="">Select Subcategory</option>
                                <?php foreach ($initial_subcategories as $sub): ?>
                                    <option value="<?= htmlspecialchars($sub['id']) ?>" data-fare="<?= htmlspecialchars($sub['fare']) ?>" <?= ($task['subcategory_id'] == $sub['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sub['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="work_description" class="form-label">Work Description <span class="text-danger">*</span></label>
                        <textarea class="form-control rounded-3" id="work_description" name="work_description" rows="3" required><?= htmlspecialchars($task['work_description']) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="deadline" class="form-label">Deadline (Due Date) <span class="text-danger">*</span></label>
                            <input type="date" class="form-control rounded-pill" id="deadline" name="deadline" value="<?= htmlspecialchars($task['deadline']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fee" class="form-label">Fee (<?= $currencySymbol ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control rounded-pill" id="fee" name="fee" min="0" value="<?= htmlspecialchars($task['fee']) ?>" required readonly>
                            <small class="text-muted">Auto-populated based on selected subcategory.</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fee_mode" class="form-label">Fee Mode <span class="text-danger">*</span></label>
                            <select class="form-select rounded-pill" id="fee_mode" name="fee_mode" required>
                                <option value="online" <?= ($task['fee_mode'] == 'online') ? 'selected' : '' ?>>Online</option>
                                <option value="cash" <?= ($task['fee_mode'] == 'cash') ? 'selected' : '' ?>>Cash</option>
                                <option value="credit_card" <?= ($task['fee_mode'] == 'credit_card') ? 'selected' : '' ?>>Credit Card</option>
                                <option value="pending" <?= ($task['fee_mode'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="maintenance_fee" class="form-label">Maintenance Fee (<?= $currencySymbol ?>)</label>
                            <input type="number" step="0.01" class="form-control rounded-pill" id="maintenance_fee" name="maintenance_fee" min="0" value="<?= htmlspecialchars($task['maintenance_fee']) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="maintenance_fee_mode" class="form-label">Maintenance Fee Mode</label>
                        <select class="form-select rounded-pill" id="maintenance_fee_mode" name="maintenance_fee_mode">
                            <option value="pending" <?= ($task['maintenance_fee_mode'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="online" <?= ($task['maintenance_fee_mode'] == 'online') ? 'selected' : '' ?>>Online</option>
                            <option value="cash" <?= ($task['maintenance_fee_mode'] == 'cash') ? 'selected' : '' ?>>Cash</option>
                            <option value="credit_card" <?= ($task['maintenance_fee_mode'] == 'credit_card') ? 'selected' : '' ?>>Credit Card</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Task Status <span class="text-danger">*</span></label>
                            <select class="form-select rounded-pill" id="status" name="status" required>
                                <option value="pending" <?= ($task['status'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                                <option value="in_process" <?= ($task['status'] == 'in_process') ? 'selected' : '' ?>>In Process</option>
                                <option value="completed" <?= ($task['status'] == 'completed') ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= ($task['status'] == 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="payment_status" class="form-label">Payment Status <span class="text-danger">*</span></label>
                            <select class="form-select rounded-pill" id="payment_status" name="payment_status" required>
                                <option value="pending" <?= ($task['payment_status'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                                <option value="paid_full" <?= ($task['payment_status'] == 'paid_full') ? 'selected' : '' ?>>Paid (Full)</option>
                                <option value="paid_partial" <?= ($task['payment_status'] == 'paid_partial') ? 'selected' : '' ?>>Paid (Partial)</option>
                                <option value="refunded" <?= ($task['payment_status'] == 'refunded') ? 'selected' : '' ?>>Refunded</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Admin Notes (Internal)</label>
                        <textarea class="form-control rounded-3" id="admin_notes" name="admin_notes" rows="2"><?= htmlspecialchars($task['admin_notes']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="user_notes" class="form-label">User Notes</label>
                        <textarea class="form-control rounded-3" id="user_notes" name="user_notes" rows="2" readonly><?= htmlspecialchars($task['user_notes']) ?></textarea>
                        <small class="text-muted">User-submitted notes (read-only for admin on this form).</small>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" name="update_task" class="btn btn-primary rounded-pill"><i class="fas fa-save me-2"></i>Update Task</button>
                        <button type="button" class="btn btn-outline-info rounded-pill" onclick="window.open('<?= BASE_URL ?>views/print_bill.php?task_id=<?= htmlspecialchars($task['id']) ?>', '_blank')">
                            <i class="fas fa-print me-2"></i>Print Client Bill
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Custom Confirmation Modal (re-used across admin files) -->
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
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('category_id');
        const subcategorySelect = document.getElementById('subcategory_id');
        const feeInput = document.getElementById('fee');

        // Function to dynamically load subcategories based on category_id
        function loadSubcategories(categoryId, selectedSubcategoryId = null) {
            subcategorySelect.innerHTML = '<option value="">Loading Subcategories...</option>';
            subcategorySelect.disabled = true;
            feeInput.value = '0.00'; // Reset fee when category changes or loading

            if (categoryId) {
                fetch('<?= BASE_URL ?>models/fetch_subcategories.php?category_id=' + categoryId)
                    .then(response => response.json())
                    .then(data => {
                        subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
                        if (data.length > 0) {
                            data.forEach(sub => {
                                const option = document.createElement('option');
                                option.value = sub.id;
                                option.textContent = sub.name;
                                option.setAttribute('data-fare', sub.fare); // Store fare in data attribute
                                if (selectedSubcategoryId && sub.id == selectedSubcategoryId) {
                                    option.selected = true;
                                }
                                subcategorySelect.appendChild(option);
                            });
                            subcategorySelect.disabled = false;
                            // Trigger change to auto-populate fee if a subcategory is selected
                            subcategorySelect.dispatchEvent(new Event('change'));
                        } else {
                            subcategorySelect.innerHTML = '<option value="">No Subcategories Found</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching subcategories:', error);
                        subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
                    });
            } else {
                subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
            }
        }

        // Event listener for category change
        categorySelect.addEventListener('change', function() {
            loadSubcategories(this.value);
        });

        // Event listener for subcategory change to update fee
        subcategorySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.getAttribute('data-fare')) {
                feeInput.value = parseFloat(selectedOption.getAttribute('data-fare')).toFixed(2);
            } else {
                feeInput.value = '0.00';
            }
        });

        // Initial load of subcategories and fee when the page loads (for existing task)
        <?php if ($task && isset($task['category_id'])): ?>
            loadSubcategories(<?= htmlspecialchars($task['category_id']) ?>, <?= htmlspecialchars($task['subcategory_id']) ?>);
        <?php endif; ?>

        // Custom confirm dialog function (re-used across admin files for consistency)
        function showCustomConfirm(title, message, link) {
            const confirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
            document.getElementById('customConfirmModalLabel').textContent = title;
            document.getElementById('confirm-message').textContent = message;
            document.getElementById('confirm-link').href = link;
            confirmModal.show();
        }

        // Auto-hide message functionality (if message is present from PHP)
        <?php if (!empty($message)): ?>
            setTimeout(function() {
                const alertElement = document.querySelector('.alert');
                if (alertElement) {
                    alertElement.classList.add('fade-out');
                    setTimeout(() => alertElement.remove(), 500);
                }
            }, 5000);
        <?php endif; ?>
    });
</script>

<style>
    /* Custom CSS for fade-out alert */
    .alert.fade-out {
        opacity: 0;
        transition: opacity 0.5s ease-out;
    }
</style>
