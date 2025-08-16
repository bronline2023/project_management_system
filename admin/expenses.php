<?php
/**
 * admin/expenses.php
 *
 * This file handles the management of office expenses by the administrator.
 * It allows adding new expense entries, editing existing ones, and deleting them.
 * Expenses can be recorded with type, amount, description, and date.
 *
 * It ensures that only authenticated admin users can access this page.
 */

// Include the configuration file for database connection and session management.
require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';   // Database interaction functions
require_once MODELS_PATH . 'auth.php'; // Authentication functions

// Restrict access to admin users only.
// If the user is not logged in or not an admin, redirect them.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB(); // Establish database connection
$message = ''; // To store success or error messages

// --- Handle Expense Actions (Add, Edit, Delete) ---

// Handle Add Expense
if (isset($_POST['add_expense'])) {
    $expense_type = trim($_POST['expense_type']);
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);
    $expense_date = $_POST['expense_date'];

    // Basic validation
    if (empty($expense_type) || !is_numeric($amount) || $amount <= 0 || empty($expense_date)) {
        $message = '<div class="alert alert-danger" role="alert">Please fill in all required fields and ensure the amount is valid.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO expenses (expense_type, amount, description, expense_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$expense_type, $amount, $description, $expense_date]);
            $message = '<div class="alert alert-success" role="alert">Expense added successfully!</div>';
        } catch (PDOException $e) {
            error_log("Error adding expense: " . $e->getMessage());
            $message = '<div class="alert alert-danger" role="alert">Error adding expense: ' . $e->getMessage() . '</div>';
        }
    }
}

// Handle Edit Expense
if (isset($_POST['edit_expense'])) {
    $id = $_POST['expense_id'];
    $expense_type = trim($_POST['expense_type']);
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);
    $expense_date = $_POST['expense_date'];

    // Basic validation
    if (empty($id) || empty($expense_type) || !is_numeric($amount) || $amount <= 0 || empty($expense_date)) {
        $message = '<div class="alert alert-danger" role="alert">Please fill in all required fields for editing and ensure the amount is valid.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE expenses SET expense_type = ?, amount = ?, description = ?, expense_date = ? WHERE id = ?");
            $stmt->execute([$expense_type, $amount, $description, $expense_date, $id]);
            $message = '<div class="alert alert-success" role="alert">Expense updated successfully!</div>';
        } catch (PDOException $e) {
            error_log("Error updating expense: " . $e->getMessage());
            $message = '<div class="alert alert-danger" role="alert">Error updating expense: ' . $e->getMessage() . '</div>';
        }
    }
}

// Handle Delete Expense
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->execute([$id]);
        $message = '<div class="alert alert-success" role="alert">Expense deleted successfully!</div>';
    } catch (PDOException $e) {
        error_log("Error deleting expense: " . $e->getMessage());
        $message = '<div class="alert alert-danger" role="alert">Error deleting expense.</div>';
    }
}

// --- Fetch All Expenses for Display ---
$expenses = [];
try {
    $stmt = $pdo->query("SELECT id, expense_type, amount, description, expense_date, created_at FROM expenses ORDER BY expense_date DESC, created_at DESC");
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching expenses: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading expenses.</div>';
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

// Include the header (contains HTML <head> and initial Bootstrap/CSS)
include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; // Include the sidebar for navigation ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Office Expenses</h2>

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

        <!-- Add New Expense Form -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Expense</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="expense_type" class="form-label">Expense Type <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-pill" id="expense_type" name="expense_type" placeholder="e.g., Rent, Electricity Bill, Stationery" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Amount (<?= $currencySymbol ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control rounded-pill" id="amount" name="amount" min="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="expense_date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control rounded-pill" id="expense_date" name="expense_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control rounded-3" id="description" name="description" rows="1"></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_expense" class="btn btn-primary rounded-pill"><i class="fas fa-plus-circle me-2"></i>Add Expense</button>
                </form>
            </div>
        </div>

        <!-- Expense List -->
        <div class="card shadow-sm rounded-3">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Expense History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Recorded At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($expenses) > 0): ?>
                                <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($expense['id']) ?></td>
                                        <td><?= htmlspecialchars($expense['expense_type']) ?></td>
                                        <td><?= $currencySymbol ?><?= number_format($expense['amount'], 2) ?></td>
                                        <td><?= htmlspecialchars($expense['description']) ?></td>
                                        <td><?= date('Y-m-d', strtotime($expense['expense_date'])) ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($expense['created_at'])) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1"
                                                    data-bs-toggle="modal" data-bs-target="#editExpenseModal"
                                                    data-id="<?= htmlspecialchars($expense['id']) ?>"
                                                    data-type="<?= htmlspecialchars($expense['expense_type']) ?>"
                                                    data-amount="<?= htmlspecialchars($expense['amount']) ?>"
                                                    data-description="<?= htmlspecialchars($expense['description']) ?>"
                                                    data-date="<?= htmlspecialchars($expense['expense_date']) ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill"
                                                    onclick="showCustomConfirm('Delete Expense', 'Are you sure you want to delete this expense entry (ID: <?= htmlspecialchars($expense['id']) ?>)?', '<?= BASE_URL ?>?page=expenses&action=delete&id=<?= htmlspecialchars($expense['id']) ?>')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No expenses recorded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Edit Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="editExpenseModalLabel"><i class="fas fa-edit me-2"></i>Edit Expense</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" id="edit-expense-id" name="expense_id">
                    <div class="mb-3">
                        <label for="edit-expense-type" class="form-label">Expense Type <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-pill" id="edit-expense-type" name="expense_type" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-amount" class="form-label">Amount (<?= $currencySymbol ?>) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control rounded-pill" id="edit-amount" name="amount" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-expense-date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control rounded-pill" id="edit-expense-date" name="expense_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-description" class="form-label">Description</label>
                        <textarea class="form-control rounded-3" id="edit-description" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_expense" class="btn btn-primary rounded-pill"><i class="fas fa-save me-2"></i>Save Changes</button>
                </div>
            </form>
        </div>
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
    // JavaScript to populate the edit expense modal when the edit button is clicked
    document.addEventListener('DOMContentLoaded', function() {
        const editExpenseModal = document.getElementById('editExpenseModal');
        editExpenseModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const id = button.getAttribute('data-id');
            const type = button.getAttribute('data-type');
            const amount = button.getAttribute('data-amount');
            const description = button.getAttribute('data-description');
            const date = button.getAttribute('data-date'); // YYYY-MM-DD format

            const modalTitle = editExpenseModal.querySelector('.modal-title');
            const modalBodyInputId = editExpenseModal.querySelector('#edit-expense-id');
            const modalBodyInputType = editExpenseModal.querySelector('#edit-expense-type');
            const modalBodyInputAmount = editExpenseModal.querySelector('#edit-amount');
            const modalBodyTextareaDescription = editExpenseModal.querySelector('#edit-description');
            const modalBodyInputDate = editExpenseModal.querySelector('#edit-expense-date');

            modalTitle.textContent = 'Edit Expense (ID: ' + id + ')';
            modalBodyInputId.value = id;
            modalBodyInputType.value = type;
            modalBodyInputAmount.value = amount;
            modalBodyTextareaDescription.value = description;
            modalBodyInputDate.value = date;
        });
    });

    // Custom confirm dialog function (re-used across admin files for consistency)
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
