<?php
/**
 * user/create_task_from_appointment.php
 * This page provides a form to create a detailed task from a confirmed appointment.
 * COMPLETE AND FINAL (CORRECTED): This version includes the corrected logic for dynamic maintenance fees,
 * mirroring the functionality in admin/assign_task.php.
 */
if (!defined('ROOT_PATH')) {
    die('Invalid access');
}

$pdo = connectDB();
$appointmentId = $_GET['appointment_id'] ?? 0;

$appointment = fetchOne($pdo, "
    SELECT
        a.*,
        c.name as category_name,
        c.description as category_description,
        c.required_documents,
        u.name as user_name
    FROM appointments a
    JOIN categories c ON a.category_id = c.id
    LEFT JOIN users u ON a.user_id = u.id
    WHERE a.id = ? AND a.user_id = ? AND a.status = 'pending'
", [$appointmentId, $_SESSION['user_id']]);

if (!$appointment) {
    $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid appointment ID or this appointment is no longer valid for task creation.</div>';
    header('Location: ?page=my_appointments');
    exit;
}

$categories = fetchAll($pdo, "SELECT id, name FROM categories ORDER BY name");
$subcategories = fetchAll($pdo, "SELECT id, name, fare, maintenance_fee, maintenance_fee_required FROM subcategories WHERE category_id = ?", [$appointment['category_id']]);
$message = $_SESSION['status_message'] ?? '';
unset($_SESSION['status_message']);
?>

<div class="container-fluid">
    <h1 class="mt-4">Create Task from Appointment</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="?page=my_appointments">My Appointments</a></li>
        <li class="breadcrumb-item active">Create Task</li>
    </ol>

    <?php if ($message) echo $message; ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plus-circle me-1"></i>New Task Form</div>
        <div class="card-body">
            <div class="alert alert-info">
                You are creating a new task for <strong><?= htmlspecialchars($appointment['client_name']) ?></strong> based on Appointment #<?= $appointment['id'] ?>.
                Please fill in the required details below.
            </div>

            <form action="?page=actions" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create_task_from_appointment_submit">
                <input type="hidden" name="page" value="my_tasks">
                <input type="hidden" name="appointment_id" value="<?= $appointmentId ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $appointment['category_id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="subcategory_id" class="form-label">Subcategory</label>
                        <select class="form-select" id="subcategory_id" name="subcategory_id">
                            <option value="">Select Subcategory</option>
                            <?php foreach ($subcategories as $subcat): ?>
                                <option value="<?= $subcat['id'] ?>" data-fare="<?= htmlspecialchars($subcat['fare']) ?>" data-maintenance-fee="<?= htmlspecialchars($subcat['maintenance_fee']) ?>" data-maintenance-required="<?= $subcat['maintenance_fee_required'] ?>"><?= htmlspecialchars($subcat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="work_description" class="form-label">Work Description</label>
                    <textarea class="form-control" id="work_description" name="work_description" rows="4" required><?= htmlspecialchars($appointment['notes']) ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="deadline" class="form-label">Deadline</label>
                        <input type="date" class="form-control" id="deadline" name="deadline" required>
                    </div>
                    <div class="col-md-6 mb-3">
                         <label for="task_attachment" class="form-label">Attach File (Optional)</label>
                        <input type="file" class="form-control" id="task_attachment" name="task_attachment">
                         <?php if (!empty($appointment['document_path'])): ?>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> An attachment from the original appointment already exists. Uploading a new file will replace it.
                                <a href="<?= BASE_URL . htmlspecialchars($appointment['document_path']) ?>" target="_blank" class="ms-2"><i class="fas fa-paperclip"></i> View Original Attachment</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr>
                <h5 class="mb-3">Financials</h5>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="fee" class="form-label">Fee</label>
                        <input type="number" step="0.01" class="form-control" id="fee" name="fee" value="0" required>
                    </div>
                     <div class="col-md-3 mb-3">
                        <label for="fee_mode" class="form-label">Fee Mode</label>
                        <select class="form-select" name="fee_mode">
                             <option value="pending">Pending</option>
                            <option value="online">Online</option>
                            <option value="cash">Cash</option>
                            <option value="credit/debit">Credit/Debit Card</option>
                            <option value="upi">UPI</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3" id="maintenance-fee-group" style="display: none;">
                        <label for="maintenance_fee" class="form-label">Maintenance Fee</label>
                        <input type="number" step="0.01" class="form-control" id="maintenance_fee" name="maintenance_fee" value="0">
                    </div>
                     <div class="col-md-3 mb-3" id="maintenance-fee-mode-group">
                        <label for="maintenance_fee_mode" class="form-label">Maintenance Fee Mode</label>
                         <select class="form-select" id="maintenance_fee_mode" name="maintenance_fee_mode">
                            <option value="not_required" selected>Not Required</option>
                            <option value="pending">Pending</option>
                            <option value="online">Online</option>
                            <option value="cash">Cash</option>
                            <option value="credit/debit">Credit/Debit Card</option>
                            <option value="upi">UPI</option>
                        </select>
                    </div>
                </div>
                 <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="discount" class="form-label">Discount</label>
                        <input type="number" step="0.01" class="form-control" id="discount" name="discount" value="0">
                    </div>
                     <div class="col-md-6 mb-3">
                        <label for="task_price" class="form-label">Total Task Fee</label>
                        <input type="number" step="0.01" class="form-control" id="task_price" name="task_price" value="0" readonly>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const subcategorySelect = document.getElementById('subcategory_id');
    const feeInput = document.getElementById('fee');
    const maintenanceFeeGroup = document.getElementById('maintenance-fee-group');
    const maintenanceFeeModeSelect = document.getElementById('maintenance_fee_mode');
    const maintenanceFeeInput = document.getElementById('maintenance_fee');
    const taskPriceInput = document.getElementById('task_price');
    const discountInput = document.getElementById('discount');

    function calculateTotalPrice() {
        const fee = parseFloat(feeInput.value) || 0;
        const maintenanceFee = parseFloat(maintenanceFeeInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        const total = (fee + maintenanceFee) - discount;
        taskPriceInput.value = total.toFixed(2);
    }

    function updateMaintenanceFeeDisplay() {
        if (maintenanceFeeModeSelect.value === 'not_required') {
            maintenanceFeeGroup.style.display = 'none';
            maintenanceFeeInput.value = '0.00';
        } else {
            maintenanceFeeGroup.style.display = 'block';
        }
        calculateTotalPrice();
    }

    function updateFinancials() {
        const selectedOption = subcategorySelect.options[subcategorySelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            feeInput.value = '0.00';
            maintenanceFeeInput.value = '0.00';
            updateMaintenanceFeeDisplay();
            return;
        }

        const fare = selectedOption.getAttribute('data-fare') || '0';
        const maintenanceFee = selectedOption.getAttribute('data-maintenance-fee') || '0';
        const maintenanceRequired = selectedOption.getAttribute('data-maintenance-required');

        feeInput.value = parseFloat(fare).toFixed(2);
        
        // Update maintenance fee based on subcategory data
        if (maintenanceRequired === '1') {
            maintenanceFeeInput.value = parseFloat(maintenanceFee).toFixed(2);
        } else {
            maintenanceFeeInput.value = '0.00';
        }
        
        updateMaintenanceFeeDisplay();
    }

    subcategorySelect.addEventListener('change', updateFinancials);
    maintenanceFeeModeSelect.addEventListener('change', updateMaintenanceFeeDisplay);
    feeInput.addEventListener('input', calculateTotalPrice);
    maintenanceFeeInput.addEventListener('input', calculateTotalPrice);
    discountInput.addEventListener('input', calculateTotalPrice);

    // Initial check on page load
    updateFinancials();
});
</script>