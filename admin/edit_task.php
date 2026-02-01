<?php
/**
 * admin/edit_task.php
 * Page for administrators to edit an existing task.
 * FINAL & COMPLETE: All required functionalities are implemented and stable.
 * [NEW] Added Customer dropdown and logic.
 * [NEW] Added Freelancer Fee field for specific user roles.
 * [NEW] Expanded payment modes to match the assign_task page.
 * [NEW] Added auto-calculation for Total Fee.
 * [NEW] Added Payment Status dropdown to manage earnings.
 */

// Block direct access to this file
if (!defined('ROOT_PATH')) {
    http_response_code(403);
    die('<strong>ભૂલ:</strong> આ ફાઈલ સીધી ખોલી શકાતી નથી. મહેરબાની કરીને સિસ્ટમ દ્વારા યોગ્ય રીતે ઍક્સેસ કરો.');
}

// Ensure the user has permission to view this page
$pdo = connectDB();
if (!hasPermission($pdo, $_SESSION['user_id'], 'edit_task')) {
    echo "<h1>Access Denied</h1><p>You do not have permission to view this page.</p>";
    return;
}

// Get task ID from URL
$taskId = $_GET['id'] ?? null;
if (!$taskId) {
    echo "<h1>Error</h1><p>No task ID provided.</p>";
    return;
}

// Fetch the task details to be edited
$stmt = $pdo->prepare("
    SELECT 
        wa.*,
        c.client_name,
        cu.customer_name,
        u.name as assigned_user_name,
        uby.name as assigned_by_user_name,
        a.document_path AS appointment_document_path
    FROM work_assignments wa
    LEFT JOIN clients c ON wa.client_id = c.id
    LEFT JOIN customers cu ON wa.customer_id = cu.id
    LEFT JOIN users u ON wa.assigned_to_user_id = u.id
    LEFT JOIN users uby ON wa.assigned_by_user_id = uby.id
    LEFT JOIN appointments a ON a.task_id = wa.id
    WHERE wa.id = ?
");
$stmt->execute([$taskId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    echo "<h1>Task Not Found</h1><p>The requested task could not be found.</p>";
    return;
}

// Fetch all necessary data for dropdowns
$customers = fetchAll($pdo, "SELECT id, customer_name, client_id FROM customers ORDER BY customer_name ASC");
$clients = fetchAll($pdo, "SELECT id, client_name, company_name FROM clients ORDER BY client_name ASC");
$users = fetchAll($pdo, "SELECT u.id, u.name, r.role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.name ASC");
$categories = fetchAll($pdo, "SELECT id, name FROM categories ORDER BY name ASC");
$subcategories = fetchAll($pdo, "SELECT id, name, category_id, fare FROM subcategories");
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '$');

$paymentModes = ['Pending', 'Online', 'Cash', 'Credit/Debit', 'Not Required'];
$paymentStatuses = ['Pending', 'Partially Paid', 'Paid']; // Array for statuses

$isVerificationPending = ($task['status'] === 'pending_verification');
$isCompleted = in_array($task['status'], ['completed', 'verified_completed']);
?>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Edit Task #<?= htmlspecialchars($task['id'] ?? '') ?></h4>
            <div>
                 <?php if (!empty($task['completion_receipt_path'])): ?>
                    <a href="<?= BASE_URL . htmlspecialchars($task['completion_receipt_path'] ?? '') ?>" target="_blank" class="btn btn-info btn-sm">
                        <i class="fas fa-receipt"></i> View Receipt
                    </a>
                <?php endif; ?>
                <?php if (!empty($task['attachment_path'])): ?>
                    <a href="<?= BASE_URL . htmlspecialchars($task['attachment_path'] ?? '') ?>" target="_blank" class="btn btn-secondary btn-sm">
                        <i class="fas fa-paperclip"></i> View Attachment
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <form action="?page=actions" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_task">
                <input type="hidden" name="task_id" value="<?= htmlspecialchars($task['id'] ?? '') ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                        <select class="form-select" id="customer_id" name="customer_id" required>
                            <option value="">Select a Customer</option>
                            <?php foreach($customers as $customer): ?>
                                <option value="<?= $customer['id'] ?>" data-client-id="<?= htmlspecialchars($customer['client_id'] ?? '') ?>" <?= ($task['customer_id'] ?? '') == $customer['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($customer['customer_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="client_id" class="form-label">Client (Optional)</label>
                        <select class="form-select" id="client_id" name="client_id">
                            <option value="">Select a Client</option>
                            <?php foreach($clients as $client): ?>
                                <option value="<?= $client['id'] ?>" <?= ($task['client_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($client['client_name']) ?> (<?= htmlspecialchars($client['company_name'] ?? 'N/A') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="assigned_to_user_id" class="form-label">Assign To <span class="text-danger">*</span></label>
                        <select name="assigned_to_user_id" id="assigned_to_user_id" class="form-select" required>
                            <option value="">Select a User</option>
                            <?php foreach ($users as $user): 
                                $isDisabled = strtolower($user['role_name']) === 'admin';
                            ?>
                                <option value="<?= $user['id'] ?>" data-role="<?= strtolower($user['role_name']) ?>" <?= ($task['assigned_to_user_id'] ?? '') == $user['id'] ? 'selected' : '' ?> <?= $isDisabled ? 'disabled' : '' ?>>
                                    <?= htmlspecialchars($user['name'] ?? '') ?> (<?= htmlspecialchars($user['role_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="deadline" class="form-label">Deadline <span class="text-danger">*</span></label>
                        <input type="date" name="deadline" id="deadline" class="form-control" value="<?= htmlspecialchars($task['deadline'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">Select a Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= ($task['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="subcategory_id" class="form-label">Subcategory</label>
                        <select name="subcategory_id" id="subcategory_id" class="form-select">
                            <option value="">Select Category First</option>
                             <?php foreach ($subcategories as $subcategory): ?>
                                <option value="<?= $subcategory['id'] ?>" class="subcategory-option" data-category="<?= $subcategory['category_id'] ?>" data-fare="<?= $subcategory['fare'] ?>" <?= ($task['subcategory_id'] ?? '') == $subcategory['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subcategory['name'] ?? '') ?> (Fare: <?= htmlspecialchars($subcategory['fare'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="work_description" class="form-label">Work Description</label>
                    <textarea name="work_description" id="work_description" class="form-control" rows="3"><?= htmlspecialchars($task['work_description'] ?? '') ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="fee" class="form-label">Task Fee (<?= $currencySymbol ?>) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="fee" id="fee" class="form-control" value="<?= htmlspecialchars($task['fee'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="fee_mode" class="form-label">Fee Mode</label>
                        <select class="form-select" name="fee_mode">
                            <?php foreach ($paymentModes as $mode): 
                                $value = strtolower(str_replace('/', '_', str_replace(' ', '_', $mode)));
                            ?>
                                <option value="<?= $value ?>" <?= ($task['fee_mode'] ?? '') == $value ? 'selected' : '' ?>><?= $mode ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div class="col-md-3 mb-3" id="maintenance_fee_mode_container">
                        <label for="maintenance_fee_mode" class="form-label">Maintenance Fee Mode</label>
                        <select class="form-select" id="maintenance_fee_mode" name="maintenance_fee_mode">
                             <?php foreach ($paymentModes as $mode): 
                                $value = strtolower(str_replace('/', '_', str_replace(' ', '_', $mode)));
                             ?>
                                <option value="<?= $value ?>" <?= ($task['maintenance_fee_mode'] ?? '') == $value ? 'selected' : '' ?>><?= $mode ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3" id="maintenance_fee_container">
                        <label for="maintenance_fee" class="form-label">Maintenance Fee</label>
                        <input type="number" step="0.01" name="maintenance_fee" id="maintenance_fee" class="form-control" value="<?= htmlspecialchars($task['maintenance_fee'] ?? '0.00') ?>">
                    </div>
                </div>
                
                <div class="row">
                     <div class="col-md-3 mb-3">
                        <label for="discount" class="form-label">Discount (<?= $currencySymbol ?>)</label>
                        <input type="number" step="0.01" name="discount" id="discount" class="form-control" value="<?= htmlspecialchars($task['discount'] ?? '0.00') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="payment_status" class="form-label">Payment Status</label>
                        <select name="payment_status" id="payment_status" class="form-select">
                            <?php foreach ($paymentStatuses as $status):
                                $value = strtolower(str_replace(' ', '_', $status));
                            ?>
                                <option value="<?= $value ?>" <?= ($task['payment_status'] ?? '') == $value ? 'selected' : '' ?>>
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3" id="task_price_container" style="display: none;">
                        <label for="task_price" class="form-label">Freelancer Fee (<?= $currencySymbol ?>)</label>
                        <input type="number" step="0.01" name="task_price" id="task_price" class="form-control" value="<?= htmlspecialchars($task['task_price'] ?? '0.00') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="total_fee" class="form-label">Total Fee (<?= $currencySymbol ?>)</label>
                        <input type="number" step="0.01" id="total_fee" class="form-control" value="0.00" disabled>
                    </div>
                </div>

                 <div class="mb-3">
                    <label for="status" class="form-label">Task Status</label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="pending" <?= ($task['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="in_process" <?= ($task['status'] ?? '') == 'in_process' ? 'selected' : '' ?>>In Process</option>
                        <option value="pending_verification" <?= ($task['status'] ?? '') == 'pending_verification' ? 'selected' : '' ?>>Pending Verification</option>
                        <option value="returned" <?= ($task['status'] ?? '') == 'returned' ? 'selected' : '' ?>>Returned</option>
                        <option value="verified_completed" <?= ($task['status'] ?? '') == 'verified_completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= ($task['status'] ?? '') == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="user_notes" class="form-label">User Notes</label>
                    <textarea id="user_notes" class="form-control" rows="3" readonly><?= htmlspecialchars($task['user_notes'] ?? 'No notes from user.') ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="admin_notes" class="form-label">Admin Notes</label>
                    <textarea name="admin_notes" id="admin_notes" class="form-control" rows="3"><?= htmlspecialchars($task['admin_notes'] ?? '') ?></textarea>
                </div>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Task
                    </button>
                </div>
            </form>
            
            <?php if ($isVerificationPending): ?>
            <hr>
            <div class="mt-4">
                <h5>Task Verification</h5>
                <p>The user has submitted this task for verification. You can either approve it as completed or return it for further work.</p>
                 <form action="?page=actions" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_task">
                    <input type="hidden" name="task_id" value="<?= htmlspecialchars($task['id'] ?? '') ?>">
                     <input type="hidden" name="client_id" value="<?= htmlspecialchars($task['client_id'] ?? '') ?>">
                     <input type="hidden" name="assigned_to_user_id" value="<?= htmlspecialchars($task['assigned_to_user_id'] ?? '') ?>">
                     <input type="hidden" name="category_id" value="<?= htmlspecialchars($task['category_id'] ?? '') ?>">
                     <input type="hidden" name="subcategory_id" value="<?= htmlspecialchars($task['subcategory_id'] ?? '') ?>">
                     <input type="hidden" name="work_description" value="<?= htmlspecialchars($task['work_description'] ?? '') ?>">
                     <input type="hidden" name="deadline" value="<?= htmlspecialchars($task['deadline'] ?? '') ?>">
                     <input type="hidden" name="fee" value="<?= htmlspecialchars($task['fee'] ?? '') ?>">
                     <input type="hidden" name="fee_mode" value="<?= htmlspecialchars($task['fee_mode'] ?? '') ?>">
                     <input type="hidden" name="maintenance_fee" value="<?= htmlspecialchars($task['maintenance_fee'] ?? '') ?>">
                     <input type="hidden" name="maintenance_fee_mode" value="<?= htmlspecialchars($task['maintenance_fee_mode'] ?? '') ?>">
                     <input type="hidden" name="discount" value="<?= htmlspecialchars($task['discount'] ?? '') ?>">
                     <input type="hidden" name="task_price" value="<?= htmlspecialchars($task['task_price'] ?? '') ?>">
                     <input type="hidden" name="payment_status" value="<?= htmlspecialchars($task['payment_status'] ?? '') ?>">

                    <div class="mb-3">
                        <label for="admin_notes_return" class="form-label">Admin Notes (required for returning)</label>
                        <textarea name="admin_notes" id="admin_notes_return" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" name="status" value="returned" class="btn btn-warning me-2">
                        <i class="fas fa-undo me-2"></i>Return to User
                    </button>
                    <button type="submit" name="status" value="verified_completed" class="btn btn-success">
                        <i class="fas fa-check-circle me-2"></i>Mark as Completed
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($isCompleted): ?>
            <hr>
            <div class="mt-4 text-center">
                 <h5 class="text-success">Task Completed!</h5>
                 <p>This task has been completed and verified.</p>
                 <a href="<?= BASE_URL . 'views/print_bill.php?task_id=' . htmlspecialchars($task['id']) ?>" class="btn btn-info">
                     <i class="fas fa-print me-2"></i>Print Customer Bill
                 </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// ... (JavaScript code remains the same, no changes needed here) ...
document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = document.getElementById('customer_id');
    const clientSelect = document.getElementById('client_id');
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory_id');
    const feeInput = document.getElementById('fee');
    const maintenanceFeeInput = document.getElementById('maintenance_fee');
    const discountInput = document.getElementById('discount');
    const totalFeeInput = document.getElementById('total_fee');
    const userSelect = document.getElementById('assigned_to_user_id');
    const taskPriceContainer = document.getElementById('task_price_container');
    const maintenanceFeeModeSelect = document.getElementById('maintenance_fee_mode');
    const maintenanceFeeContainer = document.getElementById('maintenance_fee_container');

    function filterSubcategories() {
        const selectedCategoryId = categorySelect.value;
        const currentSubcategoryId = '<?= $task['subcategory_id'] ?? '' ?>';
        
        let hasVisibleOptions = false;
        subcategorySelect.innerHTML = '<option value="">Select a Subcategory</option>';

        <?php foreach ($subcategories as $sub): ?>
            if ('<?= $sub['category_id'] ?>' === selectedCategoryId) {
                const option = document.createElement('option');
                option.value = '<?= $sub['id'] ?>';
                option.textContent = '<?= htmlspecialchars($sub['name']) ?> (Fare: <?= htmlspecialchars($sub['fare']) ?>)';
                option.dataset.fare = '<?= $sub['fare'] ?>';
                if ('<?= $sub['id'] ?>' === currentSubcategoryId) {
                    option.selected = true;
                }
                subcategorySelect.appendChild(option);
                hasVisibleOptions = true;
            }
        <?php endforeach; ?>
        
        subcategorySelect.disabled = !hasVisibleOptions;
    }

    function calculateTotalFee() {
        const fee = parseFloat(feeInput.value) || 0;
        const maintenanceFee = parseFloat(maintenanceFeeInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        const totalFee = fee + maintenanceFee - discount;
        totalFeeInput.value = totalFee.toFixed(2);
    }

    function toggleMaintenanceFee() {
        if (maintenanceFeeModeSelect.value === 'not_required') {
            maintenanceFeeContainer.style.display = 'none';
            maintenanceFeeInput.value = '0.00';
        } else {
            maintenanceFeeContainer.style.display = 'block';
        }
        calculateTotalFee();
    }
    
    function toggleTaskPrice() {
        const selectedOption = userSelect.options[userSelect.selectedIndex];
        const role = selectedOption ? selectedOption.dataset.role : '';
        const workerRoles = ['deo', 'freelancer', 'data_entry_operator'];
        
        if (workerRoles.includes(role)) {
            taskPriceContainer.style.display = 'block';
        } else {
            taskPriceContainer.style.display = 'none';
            document.getElementById('task_price').value = '0.00';
        }
    }
    
    customerSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const clientId = selectedOption.getAttribute('data-client-id');
        clientSelect.value = clientId || '';
    });

    categorySelect.addEventListener('change', filterSubcategories);
    
    subcategorySelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const fare = selectedOption.dataset.fare;
        if (fare) {
            feeInput.value = fare;
        }
        calculateTotalFee();
    });

    userSelect.addEventListener('change', toggleTaskPrice);
    feeInput.addEventListener('input', calculateTotalFee);
    maintenanceFeeInput.addEventListener('input', calculateTotalFee);
    discountInput.addEventListener('input', calculateTotalFee);
    maintenanceFeeModeSelect.addEventListener('change', toggleMaintenanceFee);
    
    filterSubcategories();
    calculateTotalFee();
    toggleMaintenanceFee();
    toggleTaskPrice();
});
</script>