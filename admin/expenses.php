<?php
/**
 * admin/expenses.php
 * This file handles the management of office expenses.
 * FINAL & COMPLETE: All form submissions and actions are now correctly handled by the central index.php handler.
 */

$pdo = connectDB();
$message = '';

// Display message from session if redirected from an action
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

// Fetch All Expenses for Display
$expenses = fetchAll($pdo, "SELECT id, expense_type, amount, description, expense_date, created_at FROM expenses ORDER BY expense_date DESC, created_at DESC");
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '$');
?>

<h2 class="mb-4">Office Expenses</h2>

<?php if (!empty($message)): ?>
    <?php include VIEWS_PATH . 'components/message_box.php'; ?>
<?php endif; ?>

<div class="card shadow-sm rounded-3 mb-4">
    <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Expense</h5></div>
    <div class="card-body">
        <form action="index.php" method="POST">
            <input type="hidden" name="page" value="expenses">
            <input type="hidden" name="action" value="add_expense">
            <div class="row">
                <div class="col-md-6 mb-3"><label for="expense_type" class="form-label">Expense Type <span class="text-danger">*</span></label><input type="text" class="form-control" id="expense_type" name="expense_type" required></div>
                <div class="col-md-6 mb-3"><label for="amount" class="form-label">Amount (<?= $currencySymbol ?>) <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" id="amount" name="amount" min="0.01" required></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="expense_date" class="form-label">Date <span class="text-danger">*</span></label><input type="date" class="form-control" id="expense_date" name="expense_date" value="<?= date('Y-m-d') ?>" required></div>
                <div class="col-md-6 mb-3"><label for="description" class="form-label">Description</label><textarea class="form-control" id="description" name="description" rows="1"></textarea></div>
            </div>
            <button type="submit" class="btn btn-primary">Add Expense</button>
        </form>
    </div>
</div>

<div class="card shadow-sm rounded-3">
    <div class="card-header bg-secondary text-white"><h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Expense History</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light"><tr><th>ID</th><th>Type</th><th>Amount</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?= htmlspecialchars($expense['id']) ?></td>
                            <td><?= htmlspecialchars($expense['expense_type']) ?></td>
                            <td><?= $currencySymbol ?><?= number_format($expense['amount'], 2) ?></td>
                            <td><?= date('Y-m-d', strtotime($expense['expense_date'])) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editExpenseModal" data-id="<?= $expense['id'] ?>" data-type="<?= htmlspecialchars($expense['expense_type']) ?>" data-amount="<?= $expense['amount'] ?>" data-description="<?= htmlspecialchars($expense['description']) ?>" data-date="<?= $expense['expense_date'] ?>"><i class="fas fa-edit"></i></button>
                                <a href="<?= BASE_URL ?>?page=expenses&action=delete_expense&id=<?= $expense['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Expense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="index.php" method="POST">
                <input type="hidden" name="page" value="expenses">
                <input type="hidden" name="action" value="edit_expense">
                <div class="modal-body">
                    <input type="hidden" id="edit-expense-id" name="expense_id">
                    <div class="mb-3"><label class="form-label">Expense Type</label><input type="text" class="form-control" id="edit-expense-type" name="expense_type" required></div>
                    <div class="mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" class="form-control" id="edit-amount" name="amount" required></div>
                    <div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" id="edit-expense-date" name="expense_date" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="edit-description" name="description" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Changes</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editExpenseModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('edit-expense-id').value = button.getAttribute('data-id');
        document.getElementById('edit-expense-type').value = button.getAttribute('data-type');
        document.getElementById('edit-amount').value = button.getAttribute('data-amount');
        document.getElementById('edit-description').value = button.getAttribute('data-description');
        document.getElementById('edit-expense-date').value = button.getAttribute('data-date');
    });
});
</script>