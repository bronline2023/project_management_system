<?php
/**
 * admin/tasks/daily_work_entry.php
 * Form for logging official daily work entries (Self-Entry).
 * Features: Financial tracking (Loss, Maintenance, Partial Payments), Restricted Edits.
 */

$pdo = connectDB();
$message = '';

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

// Check if we are editing
$is_edit = false;
$entry_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$entry = null;

if ($entry_id > 0) {
    $entry = fetchOne($pdo, "SELECT * FROM work_assignments WHERE id = ? AND is_daily_entry = 1", [$entry_id]);
    if ($entry) {
        $is_edit = true;
        
        // Permission Check: Non-admins cannot edit if status is 'completed' OR payment is 'paid'
        $is_admin = ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'master_admin');
        if (!$is_admin && $entry['status'] === 'completed') {
             $_SESSION['status_message'] = '<div class="alert alert-warning">Completed work cannot be edited by staff. Please contact admin.</div>';
             header('Location: ' . BASE_URL . '?page=my_daily_entries');
             exit;
        }
        if (!$is_admin && !in_array($entry['payment_status'], ['pending', 'partial_paid'])) {
             $_SESSION['status_message'] = '<div class="alert alert-warning">Only pending or partial paid entries can be edited.</div>';
             header('Location: ' . BASE_URL . '?page=my_daily_entries');
             exit;
        }
    }
}

// Fetch necessary data
$customers = fetchAll($pdo, "SELECT id, customer_name, client_id FROM customers ORDER BY customer_name ASC");
$clients = fetchAll($pdo, "SELECT id, client_name, company_name FROM clients ORDER BY client_name ASC");
$categories = fetchAll($pdo, "SELECT id, name FROM categories ORDER BY name ASC");
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

$attachments = [];
if ($is_edit) {
    $attachments = fetchAll($pdo, "SELECT * FROM daily_work_attachments WHERE work_id = ?", [$entry_id]);
}

$workStatuses = [
    'pending' => 'Pending / Waiting',
    'in_process' => 'In Process / Working',
    'completed' => 'Completed / Finished'
];

$paymentStatuses = [
    'pending' => 'Pending (Unpaid)',
    'partial_paid' => 'Partially Paid',
    'online_paid' => 'Online Paid (Full)',
    'cash_paid' => 'Cash Paid (Full)'
];

$paymentModes = ['Online', 'Cash', 'Credit Card', 'Pending'];
?>

<?php if (!empty($message)) { include VIEWS_PATH . 'components/message_box.php'; } ?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Select2 Bootstrap 5 Theme -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><?= $is_edit ? 'Edit Work Entry' : 'Daily Work Entry' ?></h2>
    <a href="?page=my_daily_entries" class="btn btn-outline-secondary rounded-pill">
        <i class="fas fa-list-alt me-2"></i>View Records
    </a>
</div>



<div class="card shadow-sm border-0 rounded-4 overflow-hidden">
    <div class="card-header bg-gradient-primary text-white p-3" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
        <h5 class="mb-0"><i class="fas <?= $is_edit ? 'fa-edit' : 'fa-plus-circle' ?> me-2"></i>Work Details</h5>
    </div>
    <div class="card-body p-4">
        <form action="index.php" method="POST" id="dailyWorkForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $is_edit ? 'update_daily_entry' : 'add_daily_entry' ?>">
            <input type="hidden" name="task_id" value="<?= $entry_id ?>">
            <input type="hidden" name="page" value="my_daily_entries">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="customer_id" class="form-label fw-bold">Customer <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <select class="form-select rounded-start-3 p-2" id="customer_id" name="customer_id" required>
                            <option value="">Select a Customer</option>
                            <?php foreach($customers as $customer): ?>
                                <option value="<?= $customer['id'] ?>" data-client-id="<?= htmlspecialchars($customer['client_id'] ?? '') ?>" <?= (isset($entry['customer_id']) && $entry['customer_id'] == $customer['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($customer['customer_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-primary rounded-end-3" type="button" data-bs-toggle="modal" data-bs-target="#quickAddCustomerModal" title="Add New Customer">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="client_id" class="form-label fw-bold">Client (Auto-selected)</label>
                    <select class="form-select rounded-3 p-2" id="client_id" name="client_id">
                        <option value="">Select a Client</option>
                        <?php foreach($clients as $client): ?>
                            <option value="<?= $client['id'] ?>" <?= (isset($entry['client_id']) && $entry['client_id'] == $client['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($client['client_name']) ?> (<?= htmlspecialchars($client['company_name'] ?? 'N/A') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="category_id" class="form-label fw-bold">Service Category <span class="text-danger">*</span></label>
                    <select class="form-select rounded-3 p-2" id="category_id" name="category_id" required>
                        <option value="">Select a Category</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= (isset($entry['category_id']) && $entry['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="subcategory_id" class="form-label fw-bold">Specific Service <span class="text-danger">*</span></label>
                    <select class="form-select rounded-3 p-2" id="subcategory_id" name="subcategory_id" required>
                        <option value="">Select Category First</option>
                        <?php if($is_edit && $entry['subcategory_id']): ?>
                            <option value="<?= $entry['subcategory_id'] ?>" selected>Current Selection</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="deadline" class="form-label fw-bold">Work Deadline <span class="text-danger">*</span></label>
                    <input type="date" class="form-control rounded-3 p-2" id="deadline" name="deadline" value="<?= $entry['deadline'] ?? date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="work_description" class="form-label fw-bold">Work Description</label>
                <textarea class="form-control rounded-3 p-2" id="work_description" name="work_description" rows="3" placeholder="Describe the work done for the customer..."><?= htmlspecialchars($entry['work_description'] ?? '') ?></textarea>
            </div>

            <!-- Multiple Attachments & Preview -->
            <div class="mb-4">
                <label class="form-label fw-bold"><i class="fas fa-paperclip me-2"></i>Attachments (Multiple Photos/Files)</label>
                <input type="file" name="attachments[]" id="attachments" class="form-control rounded-3 border-dashed" multiple accept="image/*,.pdf,.doc,.docx">
                <div id="attachment-preview" class="d-flex flex-wrap gap-3 mt-3">
                    <?php if ($is_edit && !empty($attachments)): ?>
                        <?php foreach ($attachments as $att): ?>
                            <div class="attachment-thumb position-relative" data-id="<?= $att['id'] ?>">
                                <?php 
                                    $ext = pathinfo($att['file_path'], PATHINFO_EXTENSION);
                                    if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])):
                                ?>
                                    <img src="<?= UPLOADS_URL . 'daily_work/' . htmlspecialchars($att['file_path']) ?>" class="rounded-3 shadow-sm border" style="width: 100px; height: 100px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light rounded-3 d-flex align-items-center justify-content-center border" style="width: 100px; height: 100px;">
                                        <i class="fas fa-file-alt fa-2x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle delete-old-attachment" data-id="<?= $att['id'] ?>" style="transform: translate(50%, -50%); padding: 0.1rem 0.3rem;">
                                    <i class="fas fa-times fa-xs"></i>
                                </button>
                                <input type="hidden" name="existing_attachments[]" value="<?= $att['id'] ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <small class="text-muted mt-2 d-block">You can select multiple images or documents at once.</small>
            </div>

            <div class="row bg-light p-3 rounded-4 mb-3">
                <div class="col-md-12 mb-3"><h6 class="text-primary fw-bold mb-0">Financial & Status Tracking</h6></div>
                
                <div class="col-md-3 mb-3">
                    <label for="status" class="form-label fw-bold text-dark">Work Status</label>
                    <select class="form-select rounded-3 border-primary" id="status" name="status">
                        <?php foreach ($workStatuses as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= (isset($entry['status']) && $entry['status'] == $val) ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label for="payment_status" class="form-label fw-bold text-dark">Payment Status</label>
                    <select class="form-select rounded-3 border-primary" id="payment_status" name="payment_status">
                        <?php foreach ($paymentStatuses as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= (isset($entry['payment_status']) && $entry['payment_status'] == $val) ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label for="fee_mode" class="form-label fw-bold">Payment Mode</label>
                    <select class="form-select rounded-3" name="fee_mode">
                        <?php foreach ($paymentModes as $mode): ?>
                            <option value="<?= strtolower(str_replace(' ', '_', $mode)) ?>" <?= (isset($entry['fee_mode']) && $entry['fee_mode'] == strtolower(str_replace(' ', '_', $mode))) ? 'selected' : '' ?>><?= $mode ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label for="fee" class="form-label fw-bold text-success">Total Fee (Customer) (<?= $currencySymbol ?>)</label>
                    <input type="number" step="0.01" class="form-control rounded-3 border-success fw-bold" id="fee" name="fee" value="<?= $entry['fee'] ?? '0.00' ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-3" id="partial_amount_container">
                    <label for="partial_amount" class="form-label fw-bold text-warning">Received Amount (Partial)</label>
                    <input type="number" step="0.01" class="form-control rounded-3 border-warning" id="partial_amount" name="partial_amount" value="<?= $entry['partial_amount'] ?? '0.00' ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="maintenance_fee" class="form-label fw-bold text-info">Maintenance Charge (-)</label>
                    <input type="number" step="0.01" class="form-control rounded-3 border-info" id="maintenance_fee" name="maintenance_fee" value="<?= $entry['maintenance_fee'] ?? '0.00' ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="loss_amount" class="form-label fw-bold text-danger">Loss Amount (-)</label>
                    <input type="number" step="0.01" class="form-control rounded-3 border-danger" id="loss_amount" name="loss_amount" value="<?= $entry['loss_amount'] ?? '0.00' ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="net_earning" class="form-label fw-bold text-dark">Net Earning (Approx.)</label>
                    <input type="text" class="form-control rounded-3 bg-white fw-bolder text-primary" id="net_earning" value="0.00" readonly>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow">
                    <i class="fas fa-save me-2"></i><?= $is_edit ? 'Update Entry' : 'Save Work Entry' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Add Customer Modal -->
<div class="modal fade" id="quickAddCustomerModal" tabindex="-1" aria-labelledby="quickAddCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title" id="quickAddCustomerModalLabel"><i class="fas fa-user-plus me-2"></i>Quick Add Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="quickAddCustomerForm">
                    <input type="hidden" name="action" value="ajax_add_customer">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" name="customer_name" required placeholder="e.g. Rajesh Kumar">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" name="customer_phone" required placeholder="e.g. 9876543210">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Link to Client</label>
                        <select class="form-select rounded-3" name="client_id">
                            <option value="">-- None (Direct) --</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['client_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-block rounded-pill py-2">
                            <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                            Save Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- jQuery and Select2 JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    const $customerSelect = $('#customer_id');
    const $clientSelect = $('#client_id');
    const $categorySelect = $('#category_id');
    const $subcategorySelect = $('#subcategory_id');
    const $pStatusSelect = $('#payment_status');
    const $partialContainer = $('#partial_amount_container');
    
    // Initialize Select2
    $customerSelect.select2({
        theme: 'bootstrap-5',
        placeholder: 'Select a Customer',
        width: '100%'
    });

    const quickAddForm = document.getElementById('quickAddCustomerForm');
    const quickAddModal = new bootstrap.Modal(document.getElementById('quickAddCustomerModal'));

    // Quick Add Customer Submission
    quickAddForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = this.querySelector('button[type="submit"]');
        const spinner = submitBtn.querySelector('.spinner-border');
        
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');

        const formData = new FormData(this);
        fetch('Code/Core_Logic/App/actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Add to dropdown
                const newOption = new Option(data.name, data.id, true, true);
                $(newOption).attr('data-client-id', data.client_id || '');
                $customerSelect.append(newOption).trigger('change').trigger('select2:select');
                
                // Trigger client auto-select
                $clientSelect.val(data.client_id || '');
                
                quickAddModal.hide();
                quickAddForm.reset();
                alert('Customer added and selected!');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => alert('Network error. Please try again.'))
        .finally(() => {
            submitBtn.disabled = false;
            spinner.classList.add('d-none');
        });
    });

    // Auto-select client (Native Change for Select2)
    $customerSelect.on('change', function() {
        const selectedData = $(this).find(':selected').data('client-id');
        $clientSelect.val(selectedData || '');
    });

    // Attachment Preview Logic
    const $attachmentsInput = $('#attachments');
    const $previewContainer = $('#attachment-preview');

    $attachmentsInput.on('change', function() {
        // Remove only new previews, keep existing ones unless deleted
        $('.new-preview').remove();
        
        const files = this.files;
        if (files) {
            Array.from(files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const isImage = file.type.startsWith('image/');
                    let html = `<div class="attachment-thumb position-relative new-preview">`;
                    if (isImage) {
                        html += `<img src="${e.target.result}" class="rounded-3 shadow-sm border" style="width: 100px; height: 100px; object-fit: cover;">`;
                    } else {
                        html += `<div class="bg-light rounded-3 d-flex align-items-center justify-content-center border" style="width: 100px; height: 100px;">
                                    <i class="fas fa-file-alt fa-2x text-muted"></i>
                                 </div>`;
                    }
                    html += `<span class="badge bg-primary position-absolute bottom-0 start-50 translate-middle-x mb-1" style="font-size: 0.6rem;">New</span>`;
                    html += `</div>`;
                    $previewContainer.append(html);
                }
                reader.readAsDataURL(file);
            });
        }
    });

    // Delete existing attachment logic
    $(document).on('click', '.delete-old-attachment', function() {
        const id = $(this).data('id');
        if (confirm('Are you sure you want to remove this attachment?')) {
            $(this).closest('.attachment-thumb').fadeOut(300, function() {
                $(this).remove();
                // Add a hidden input to track deletions if needed, 
                // but here we just rely on NOT sending the existing_attachments[] ID anymore.
            });
        }
    });

    // Toggle partial amount field
    $pStatusSelect.on('change', function() {
        $partialContainer.toggle(this.value === 'partial_paid');
        calculateNet();
    });

    // Load Subcategories
    $categorySelect.on('change', function() {
        const categoryId = this.value;
        $subcategorySelect.html('<option value="">Loading...</option>');
        if (categoryId) {
            // FIXED PATH
            fetch('Code/Core_Logic/Models/fetch_subcategories.php?category_id=' + categoryId)
                .then(response => response.json())
                .then(data => {
                    $subcategorySelect.html('<option value="">Select a Subcategory</option>');
                    data.forEach(sub => {
                        const option = new Option(sub.name + ' (Rate: ' + sub.fare + ')', sub.id);
                        $(option).attr('data-fare', sub.fare);
                        <?php if($is_edit): ?>
                            if(sub.id == '<?= $entry['subcategory_id'] ?>') option.selected = true;
                        <?php endif; ?>
                        $subcategorySelect.append(option);
                    });
                    calculateNet();
                });
        }
    });

    // Auto-fill fee
    $subcategorySelect.on('change', function() {
        const fare = $(this).find(':selected').data('fare');
        if (fare) $('#fee').val(fare);
        calculateNet();
    });

    // Financial Calculation
    function calculateNet() {
        const fee = parseFloat($('#fee').val()) || 0;
        const maint = parseFloat($('#maintenance_fee').val()) || 0;
        const loss = parseFloat($('#loss_amount').val()) || 0;
        
        // Approx Earning: Fee - Maint - Loss
        const net = fee - maint - loss;
        $('#net_earning').val('<?= $currencySymbol ?>' + net.toFixed(2));
    }

    ['fee', 'maintenance_fee', 'loss_amount'].forEach(id => {
        $('#' + id).on('input', calculateNet);
    });

    // Initial Trigger
    if($categorySelect.val()) $categorySelect.trigger('change');
    $pStatusSelect.trigger('change');
    calculateNet();
});
</script>

<style>
    .bg-gradient-primary { background: linear-gradient(135deg, #6366f1, #4f46e5); }
    .card { border: none; transition: transform 0.2s; }
    .form-control:focus, .form-select:focus { border-color: #6366f1; box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25); }
    
    /* Fix Select2 Height to match Bootstrap 5 */
    .select2-container--bootstrap-5 .select2-selection {
        height: calc(2.25rem + 10px) !important;
        padding-top: 5px;
    }
    .input-group > .select2-container--bootstrap-5 {
        flex: 1 1 auto;
        width: 1% !important;
    }
    .input-group > .select2-container--bootstrap-5 .select2-selection {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }
</style>
