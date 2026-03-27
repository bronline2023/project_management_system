<?php
/**
 * views/manage_service_rates.php
 * Master Admin Panel to config prices for Digital Services.
 */

if (!in_array($_SESSION['user_role'], ['master_admin', 'admin'])) {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    return;
}

$pdo = connectDB();
$rates = $pdo->query("SELECT * FROM digital_service_rates ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h3 class="fw-bold text-dark"><i class="fas fa-rupee-sign text-success"></i> Digital Service Wallet Rates</h3>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover table-borderless m-0">
                <thead class="bg-light text-muted">
                    <tr>
                        <th class="ps-4">Service</th>
                        <th>Identifier Slug</th>
                        <th>Wallet Deduction (₹)</th>
                        <th>Point Deduction (Pts)</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rates as $r): ?>
                    <tr class="border-bottom">
                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($r['service_name']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($r['service_slug']) ?></span></td>
                        <td><div class="fw-bold text-success fs-5">₹<?= number_format($r['price'], 2) ?></div></td>
                        <td><div class="fw-bold text-warning fs-5"><i class="fas fa-star text-warning fa-sm"></i> <?= number_format($r['points_price'] ?? 0) ?></div></td>
                        <td>
                            <?php if ($r['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary fw-bold" onclick="editRate(<?= $r['id'] ?>, '<?= addslashes($r['service_name']) ?>', <?= $r['price'] ?>, <?= $r['points_price'] ?? 0 ?>)">
                                <i class="fas fa-edit"></i> Edit Rate
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Rate Modal -->
<div class="modal fade" id="editRateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit"></i> Update Service Rate</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formEditRate" action="app/actions.php" method="POST">
                    <input type="hidden" name="action" value="update_service_rate">
                    <input type="hidden" name="rate_id" id="edit_rate_id">
                    
                    <div class="mb-3">
                        <label class="fw-bold form-label">Service Name</label>
                        <input type="text" id="edit_service_name" class="form-control bg-light fw-bold" readonly>
                    </div>
                    
                    <div class="mb-4">
                        <label class="fw-bold form-label">Wallet Deduction Amount (₹)</label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold bg-light">₹</span>
                            <input type="number" name="price" id="edit_price" class="form-control form-control-lg fw-bold text-success" step="0.01" required>
                        </div>
                        <small class="text-muted">This amount will be deducted if the user chooses to pay from Wallet.</small>
                    </div>

                    <div class="mb-4">
                        <label class="fw-bold form-label text-warning">Points Deduction Amount (Pts)</label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold bg-warning text-dark"><i class="fas fa-star"></i></span>
                            <input type="number" name="points_price" id="edit_points" class="form-control form-control-lg fw-bold text-warning border-warning" required>
                        </div>
                        <small class="text-muted">This amount will be deducted if the user chooses to pay using Reward Points.</small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editRate(id, name, price, points) {
    document.getElementById('edit_rate_id').value = id;
    document.getElementById('edit_service_name').value = name;
    document.getElementById('edit_price').value = price;
    document.getElementById('edit_points').value = points || 0;
    new bootstrap.Modal(document.getElementById('editRateModal')).show();
}
</script>
