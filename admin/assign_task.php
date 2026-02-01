<?php
/**
 * admin/assign_task.php
 * Form for assigning tasks to users.
 * FINAL & COMPLETE: 
 * - Includes Total Fee (Customer) vs Freelancer Fee logic.
 */
$pdo = connectDB();
$message = '';

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

// Fetch necessary data
$customers = fetchAll($pdo, "SELECT id, customer_name, client_id FROM customers ORDER BY customer_name ASC");
$clients = fetchAll($pdo, "SELECT id, client_name, company_name FROM clients ORDER BY client_name ASC");
$users = fetchAll($pdo, "SELECT u.id, u.name, r.role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.name ASC");
$categories = fetchAll($pdo, "SELECT id, name FROM categories ORDER BY name ASC");
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

$paymentModes = ['Pending', 'Online', 'Cash', 'Credit/Debit', 'Not Required'];
$paymentStatuses = ['Pending', 'Partially Paid', 'Paid'];
?>

<h2 class="mb-4">Assign New Work Task</h2>

<?php if (!empty($message)) { include VIEWS_PATH . 'components/message_box.php'; } ?>

<div class="card shadow-sm rounded-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>New Task Details</h5>
    </div>
    <div class="card-body">
        <form action="index.php" method="POST" id="assignTaskForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="assign_task">
            <input type="hidden" name="page" value="all_tasks">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                    <select class="form-select" id="customer_id" name="customer_id" required>
                        <option value="">Select a Customer</option>
                        <?php foreach($customers as $customer): ?>
                            <option value="<?= $customer['id'] ?>" data-client-id="<?= htmlspecialchars($customer['client_id'] ?? '') ?>"><?= htmlspecialchars($customer['customer_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="client_id" class="form-label">Client (Optional)</label>
                    <select class="form-select" id="client_id" name="client_id">
                        <option value="">Select a Client</option>
                        <?php foreach($clients as $client): ?>
                            <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['client_name']) ?> (<?= htmlspecialchars($client['company_name'] ?? 'N/A') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="assigned_to_user_id" class="form-label">Assign To (Freelancer/Worker) <span class="text-danger">*</span></label>
                    <select class="form-select" id="assigned_to_user_id" name="assigned_to_user_id" required>
                        <option value="">Select a User</option>
                        <?php foreach($users as $user): 
                            $isDisabled = strtolower($user['role_name']) === 'admin';
                        ?>
                            <option value="<?= $user['id'] ?>" data-role="<?= strtolower($user['role_name']) ?>" <?= $isDisabled ? 'disabled' : '' ?>>
                                <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="deadline" class="form-label">Deadline <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="deadline" name="deadline" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">Select a Category</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="subcategory_id" class="form-label">Subcategory <span class="text-danger">*</span></label>
                    <select class="form-select" id="subcategory_id" name="subcategory_id" required>
                        <option value="">Select Category First</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="work_description" class="form-label">Work Description</label>
                <textarea class="form-control" id="work_description" name="work_description" rows="3"></textarea>
            </div>
            
            <div class="mb-3">
                <label for="task_attachment" class="form-label">Attach Document (Optional)</label>
                <input type="file" class="form-control" id="task_attachment" name="task_attachment">
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="fee" class="form-label text-primary font-weight-bold">Total Fees (From Customer) (<?= $currencySymbol ?>) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control border-primary" id="fee" name="fee" required placeholder="Amount to collect from client">
                    <small class="text-muted">ગ્રાહક પાસેથી લેવાની કુલ રકમ</small>
                </div>

                <div class="col-md-3 mb-3" id="task_price_container">
                    <label for="task_price" class="form-label text-success font-weight-bold">Freelancer Fee (Payable) (<?= $currencySymbol ?>)</label>
                    <input type="number" step="0.01" class="form-control border-success" id="task_price" name="task_price" value="0.00" placeholder="Worker earnings">
                    <small class="text-muted">ફ્રીલાન્સરને મળતી રકમ</small>
                </div>

                <div class="col-md-3 mb-3">
                    <label for="fee_mode" class="form-label">Fee Mode</label>
                    <select class="form-select" name="fee_mode">
                        <?php foreach ($paymentModes as $mode): ?>
                            <option value="<?= strtolower(str_replace('/', '_', str_replace(' ', '_', $mode))) ?>" <?= ($mode === 'Pending') ? 'selected' : '' ?>><?= $mode ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 
                <div class="col-md-3 mb-3">
                    <label for="payment_status" class="form-label">Payment Status</label>
                    <select class="form-select" id="payment_status" name="payment_status">
                        <?php foreach ($paymentStatuses as $status): ?>
                            <option value="<?= strtolower(str_replace(' ', '_', $status)) ?>" <?= ($status === 'Pending') ? 'selected' : '' ?>>
                                <?= $status ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3" id="maintenance_fee_mode_container">
                    <label for="maintenance_fee_mode" class="form-label">Maintenance Fee Mode</label>
                    <select class="form-select" id="maintenance_fee_mode" name="maintenance_fee_mode">
                         <?php foreach ($paymentModes as $mode): ?>
                            <option value="<?= strtolower(str_replace('/', '_', str_replace(' ', '_', $mode))) ?>" <?= ($mode === 'Not Required') ? 'selected' : '' ?>><?= $mode ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3" id="maintenance_fee_container">
                    <label for="maintenance_fee" class="form-label">Maintenance Fee</label>
                    <input type="number" step="0.01" class="form-control" id="maintenance_fee" name="maintenance_fee" value="0.00">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="discount" class="form-label">Discount (<?= $currencySymbol ?>)</label>
                    <input type="number" step="0.01" class="form-control" id="discount" name="discount" value="0.00">
                </div>
                 <div class="col-md-3 mb-3">
                    <label for="total_fee" class="form-label">Net Total (After Discount) (<?= $currencySymbol ?>)</label>
                    <input type="number" step="0.01" class="form-control" id="total_fee" name="total_fee" value="0.00" disabled>
                </div>
            </div>

            <div class="mb-3">
                <label for="admin_notes" class="form-label">Admin Notes</label>
                <textarea class="form-control" id="admin_notes" name="admin_notes" rows="2"></textarea>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary"><i class="fas fa-check-circle me-2"></i>Assign Task</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Basic element references
    const customerSelect = document.getElementById('customer_id');
    const clientSelect = document.getElementById('client_id');
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory_id');
    const feeInput = document.getElementById('fee'); // Customer Price
    const maintenanceFeeInput = document.getElementById('maintenance_fee');
    const discountInput = document.getElementById('discount');
    const totalFeeInput = document.getElementById('total_fee'); // Display only
    const maintenanceFeeModeSelect = document.getElementById('maintenance_fee_mode');
    const maintenanceFeeContainer = document.getElementById('maintenance_fee_container');
    
    // Auto-select client based on customer
    customerSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const clientId = selectedOption.getAttribute('data-client-id');
        if (clientId) clientSelect.value = clientId;
        else clientSelect.value = '';
    });

    // Calculate Net Total
    function calculateTotalFee() {
        const fee = parseFloat(feeInput.value) || 0;
        const maintenanceFee = parseFloat(maintenanceFeeInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        const totalFee = fee + maintenanceFee - discount;
        totalFeeInput.value = totalFee.toFixed(2);
    }

    // Toggle Maintenance Fee Visibility
    function toggleMaintenanceFee() {
        maintenanceFeeContainer.style.display = (maintenanceFeeModeSelect.value === 'not_required') ? 'none' : 'block';
        calculateTotalFee();
    }

    // Load Subcategories via AJAX
    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        subcategorySelect.innerHTML = '<option value="">Loading...</option>';
        if (categoryId) {
            fetch('<?= BASE_URL ?>models/fetch_subcategories.php?category_id=' + categoryId)
                .then(response => response.json())
                .then(data => {
                    subcategorySelect.innerHTML = '<option value="">Select a Subcategory</option>';
                    data.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.name + ' (Fare: ' + sub.fare + ')';
                        option.dataset.fare = sub.fare;
                        subcategorySelect.appendChild(option);
                    });
                });
        }
    });

    // Auto-fill fee from subcategory
    subcategorySelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const fare = selectedOption.dataset.fare;
        if (fare) {
            feeInput.value = fare;
        }
        calculateTotalFee();
    });

    // Event Listeners for Calculation
    feeInput.addEventListener('input', calculateTotalFee);
    maintenanceFeeInput.addEventListener('input', calculateTotalFee);
    discountInput.addEventListener('input', calculateTotalFee);
    maintenanceFeeModeSelect.addEventListener('change', toggleMaintenanceFee);
    
    toggleMaintenanceFee();
    calculateTotalFee();
});
</script>