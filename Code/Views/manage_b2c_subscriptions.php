<?php
/**
 * views/manage_b2c_subscriptions.php
 * Master Admin Panel to config B2C Subscription Plans for Digital Services.
 */

if (!in_array($_SESSION['user_role'], ['master_admin', 'admin'])) {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    return;
}

$pdo = connectDB();
$services = $pdo->query("SELECT * FROM digital_service_rates WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$plans = $pdo->query("SELECT * FROM b2c_subscription_plans ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h3 class="fw-bold text-dark"><i class="fas fa-box-open text-primary"></i> B2C Digital Service Plans</h3>
        <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addPlanModal">
            <i class="fas fa-plus"></i> Create New Plan
        </button>
    </div>

    <div class="row">
        <?php foreach ($plans as $p): 
            $allowed = json_decode($p['allowed_services'], true) ?: [];
        ?>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 border-top border-<?= $p['is_active'] ? 'primary' : 'secondary' ?> border-4 h-100">
                <div class="card-body position-relative">
                    <?php if (!$p['is_active']): ?>
                        <span class="badge bg-secondary position-absolute top-0 end-0 m-3">Disabled</span>
                    <?php endif; ?>
                    
                    <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($p['plan_name']) ?></h4>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($p['description']) ?></p>
                    
                    <h2 class="fw-bold text-primary mb-0">₹<?= number_format($p['price'], 2) ?></h2>
                    <p class="text-muted small">Valid for <?= $p['validity_days'] ?> Days</p>
                    
                    <hr>
                    <h6 class="fw-bold text-dark">Included Services:</h6>
                    <ul class="list-unstyled mb-4">
                        <?php foreach ($services as $srv): ?>
                            <?php if (in_array($srv['service_slug'], $allowed)): ?>
                                <li><i class="fas fa-check-circle text-success me-2"></i> <?= htmlspecialchars($srv['service_name']) ?> <span class="badge bg-light text-success ms-1">Free</span></li>
                            <?php else: ?>
                                <li class="text-muted"><i class="fas fa-times-circle text-danger me-2 opacity-50"></i> <del><?= htmlspecialchars($srv['service_name']) ?></del></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="d-flex gap-2 mt-auto">
                        <button class="btn btn-sm btn-outline-dark fw-bold w-100" onclick="editPlan(<?= htmlspecialchars(json_encode($p)) ?>)"><i class="fas fa-edit"></i> Edit</button>
                        <form action="app/actions.php" method="POST" class="w-100">
                            <input type="hidden" name="action" value="toggle_b2c_plan">
                            <input type="hidden" name="plan_id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-sm <?= $p['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?> fw-bold w-100">
                                <i class="fas fa-power-off"></i> <?= $p['is_active'] ? 'Disable' : 'Enable' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($plans)): ?>
            <div class="col-12 text-center py-5">
                <h4 class="text-muted"><i class="fas fa-box-open fa-3x mb-3 text-light"></i><br>No B2C plans created yet.</h4>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Plan Modal -->
<div class="modal fade" id="addPlanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="planModalTitle"><i class="fas fa-plus"></i> Create B2C Plan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formPlan" action="app/actions.php" method="POST">
                    <input type="hidden" name="action" id="plan_action" value="save_b2c_plan">
                    <input type="hidden" name="plan_id" id="plan_id">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="fw-bold form-label">Plan Name</label>
                            <input type="text" name="plan_name" id="plan_name" class="form-control fw-bold" required placeholder="e.g. VIP Studio Pass">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="fw-bold form-label">Validity (Days)</label>
                            <input type="number" name="validity_days" id="validity_days" class="form-control fw-bold" required value="30">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="fw-bold form-label">Description (Optional)</label>
                            <textarea name="description" id="description" class="form-control" rows="2" placeholder="Brief details about the plan"></textarea>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="fw-bold form-label">Subscription Price (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold bg-light">₹</span>
                                <input type="number" name="price" id="plan_price" class="form-control form-control-lg fw-bold text-primary" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="fw-bold form-label text-dark border-bottom w-100 pb-2 mb-3">Select Allowed Services (Free in this Plan)</label>
                        <div class="row">
                            <?php foreach ($services as $srv): ?>
                            <div class="col-md-6 mb-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input service-checkbox" type="checkbox" name="services[]" value="<?= $srv['service_slug'] ?>" id="srv_<?= $srv['service_slug'] ?>">
                                    <label class="form-check-label fw-bold cursor-pointer" for="srv_<?= $srv['service_slug'] ?>">
                                        <?= htmlspecialchars($srv['service_name']) ?> <span class="text-muted small">(₹<?= number_format($srv['price'], 0) ?>/use)</span>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm py-2">Save Subscription Plan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editPlan(plan) {
    document.getElementById('planModalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit B2C Plan';
    document.getElementById('plan_action').value = 'save_b2c_plan';
    document.getElementById('plan_id').value = plan.id;
    document.getElementById('plan_name').value = plan.plan_name;
    document.getElementById('description').value = plan.description;
    document.getElementById('plan_price').value = plan.price;
    document.getElementById('validity_days').value = plan.validity_days;
    
    // Reset checkboxes
    document.querySelectorAll('.service-checkbox').forEach(cb => cb.checked = false);
    
    // Check allowed services
    if (plan.allowed_services) {
        try {
            const allowed = JSON.parse(plan.allowed_services);
            allowed.forEach(slug => {
                const cb = document.getElementById('srv_' + slug);
                if (cb) cb.checked = true;
            });
        } catch(e) {}
    }
    
    new bootstrap.Modal(document.getElementById('addPlanModal')).show();
}

// Reset form on modal hidden
document.getElementById('addPlanModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('planModalTitle').innerHTML = '<i class="fas fa-plus"></i> Create B2C Plan';
    document.getElementById('formPlan').reset();
    document.getElementById('plan_id').value = '';
});
</script>
