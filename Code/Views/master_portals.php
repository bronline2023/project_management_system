<?php
/**
 * views/master_portals.php
 * MASTER ADMIN ONLY: Manage Sub-Portals, Domains, and Subscriptions
 */

// 1. Strict Security (Access Control) - Only Master Admin can view this page
$user_role = $_SESSION['user_role'] ?? 'guest';
if ($user_role !== 'master_admin') {
    echo "<div class='alert alert-danger text-center mt-5' style='max-width:500px; margin:auto; border-radius:10px; box-shadow:0 10px 25px rgba(0,0,0,0.1);'>
            <h3 class='text-danger fw-bold'><i class='fas fa-shield-alt'></i> Access Denied</h3>
            <p>only <b>Master Admin</b> Only allowed to view this page.</p>
            <a href='?page=dashboard' class='btn btn-primary mt-3'>Go back to the dashboard</a>
          </div>";
    return;
}

$pdo = connectDB();

try {
    $stmt = $pdo->query("SELECT * FROM tenants ORDER BY id DESC");
    $portals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $portals = [];
    $db_error = "Database Error: " . $e->getMessage();
}

$plans = fetchAll($pdo, "SELECT * FROM subscription_plans");
?>

<style>
    .master-header { background: linear-gradient(135deg, #0f172a, #1e293b); color: white; padding: 25px 30px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 30px rgba(0,0,0,0.15); margin-bottom: 30px; border-left: 5px solid #38bdf8;}
    .stat-card { background: white; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; transition: 0.3s;}
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    .stat-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 24px; color: white;}
    .stat-info h4 { margin: 0; font-size: 24px; font-weight: bold; color: #0f172a;}
    .stat-info p { margin: 0; font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase;}
    
    .portal-table { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;}
    .portal-table table { margin: 0; }
    .portal-table th { background: #f8fafc; font-weight: 700; color: #334155; text-transform: uppercase; font-size: 12px; padding: 15px 20px; border-bottom: 2px solid #e2e8f0;}
    .portal-table td { padding: 15px 20px; vertical-align: middle; color: #1e293b; font-size: 14px; border-bottom: 1px solid #f1f5f9;}
    .portal-table tbody tr:hover { background: #f8fafc; }
    
    .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;}
    .status-active { background: #dcfce7; color: #059669; border: 1px solid #a7f3d0;}
    .status-expired { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;}
    .status-suspended { background: #fef3c7; color: #d97706; border: 1px solid #fde68a;}
    
    .btn-action { width: 35px; height: 35px; border-radius: 8px; border: none; display: inline-flex; justify-content: center; align-items: center; color: white; transition: 0.2s; text-decoration: none;}
    .btn-edit { background: #3b82f6; } .btn-edit:hover { background: #2563eb; }
    .btn-suspend { background: #f59e0b; } .btn-suspend:hover { background: #d97706; }
</style>

<div class="container-fluid py-4">
    
    <div class="master-header">
        <div>
            <h2 class="m-0 fw-bold"><i class="fas fa-globe"></i> B2B Tenant Management</h2>
            <p class="m-0 text-info mt-1" style="font-size: 14px;">Central Provisioning and Monitoring of Client Master Domains</p>
        </div>
        <div>
            <button class="btn btn-info fw-bold text-white px-4" style="background: #0ea5e9; border:none;" data-bs-toggle="modal" data-bs-target="#createTenantModal">
                <i class="fas fa-plus-circle"></i> Provision New Tenant
            </button>
        </div>
    </div>

    <?php if(isset($_SESSION['status_message'])) { echo $_SESSION['status_message']; unset($_SESSION['status_message']); } ?>
    <?php if(isset($db_error)): ?>
        <div class="alert alert-warning fw-bold"><i class="fas fa-exclamation-triangle"></i> <?= $db_error ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);"><i class="fas fa-server"></i></div>
                <div class="stat-info">
                    <h4><?= count($portals) ?></h4>
                    <p>Total Provisioned Tenants</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <?php $active = array_filter($portals, fn($p) => $p['status'] == 'active'); ?>
                    <h4><?= count($active) ?></h4>
                    <p>Active SaaS Clients</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);"><i class="fas fa-times-circle"></i></div>
                <div class="stat-info">
                    <?php $expired = array_filter($portals, fn($p) => $p['status'] == 'expired'); ?>
                    <h4><?= count($expired) ?></h4>
                    <p>Expired Subscriptions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><i class="fas fa-ban"></i></div>
                <div class="stat-info">
                    <?php $suspended = array_filter($portals, fn($p) => $p['status'] == 'suspended'); ?>
                    <h4><?= count($suspended) ?></h4>
                    <p>Suspended Portals</p>
                </div>
            </div>
        </div>
    </div>

    <div class="portal-table">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
            <h5 class="m-0 fw-bold text-dark"><i class="fas fa-list"></i> B2B Registered Tenant Domains</h5>
            <input type="text" class="form-control" placeholder="Search domain or company..." style="width: 250px; border-radius: 20px;">
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-borderless">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Domain / Company Name</th>
                        <th>Owner Contact</th>
                        <th>Sub. Expiry</th>
                        <th>Database Name</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($portals) > 0): ?>
                        <?php foreach($portals as $p): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= $p['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-primary"><i class="fas fa-link text-muted"></i> <a href="http://<?= htmlspecialchars($p['domain_name']) ?>/" target="_blank" class="text-decoration-none"><?= htmlspecialchars($p['domain_name']) ?></a></div>
                                    <small class="text-muted"><i class="fas fa-building"></i> <?= htmlspecialchars($p['company_name']) ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($p['owner_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($p['owner_email']) ?><br><?= htmlspecialchars($p['owner_phone']) ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $exp_date = strtotime($p['subscription_end']);
                                        $is_expired = $exp_date < time();
                                        $color = $is_expired ? 'text-danger fw-bold' : 'text-success fw-bold';
                                    ?>
                                    <span class="<?= $color ?>"><?= date('d M Y', $exp_date) ?></span>
                                </td>
                                <td><span class="badge bg-secondary text-light"><?= htmlspecialchars($p['db_name']) ?></span></td>
                                <td>
                                    <?php if($p['status'] == 'active' && !$is_expired): ?>
                                        <span class="badge-status status-active"><i class="fas fa-check"></i> Active</span>
                                    <?php elseif($is_expired || $p['status'] == 'expired'): ?>
                                        <span class="badge-status status-expired"><i class="fas fa-times"></i> Expired</span>
                                    <?php else: ?>
                                        <span class="badge-status status-suspended"><i class="fas fa-ban"></i> Suspended</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="?page=master_reports&tenant_id=<?= $p['id'] ?>" class="btn-action btn-login" title="View Tenant Reports"><i class="fas fa-chart-line"></i></a>
                                    
                                    <?php if($p['status'] == 'active'): ?>
                                        <a href="app/suspend_tenant.php?id=<?= $p['id'] ?>&action=suspend" class="btn-action btn-suspend" title="Suspend Portal" onclick="return confirm('Want to suspend business portal?');"><i class="fas fa-power-off"></i></a>
                                    <?php else: ?>
                                        <a href="app/suspend_tenant.php?id=<?= $p['id'] ?>&action=activate" class="btn-action btn-login bg-success" title="Activate Portal" onclick="return confirm('Want to activate again?');"><i class="fas fa-play"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-box-open fs-1 mb-3 text-light"></i>
                                <h4>No tenants discovered currently.</h4>
                                <p>When a B2B SaaS subscriber is provisioned, they will appear here.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Tenant Modal Form -->
<div class="modal fade" id="createTenantModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title fw-bold"><i class="fas fa-server text-info me-2"></i> Provision New Tenant</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body bg-light">
        <form action="app/create_tenant.php" method="POST" id="provisionForm">
          <h6 class="fw-bold mb-3 border-bottom pb-2 text-primary">Company & Domain Info</h6>
          <div class="row mb-3">
              <div class="col-md-6">
                 <label class="form-label fw-bold">Company Name</label>
                 <input type="text" class="form-control" name="company_name" required placeholder="e.g. Acme Corp">
              </div>
              <div class="col-md-6">
                 <label class="form-label fw-bold">Domain Name (Hostname)</label>
                 <input type="text" class="form-control" name="domain_name" required placeholder="e.g. acme.bronline.net or acme.localhost">
              </div>
          </div>
          <h6 class="fw-bold mb-3 border-bottom pb-2 text-primary">Master Admin / Contact Detail</h6>
          <div class="row mb-3">
              <div class="col-md-4">
                 <label class="form-label fw-bold">Owner Name</label>
                 <input type="text" class="form-control" name="owner_name" required>
              </div>
              <div class="col-md-4">
                 <label class="form-label fw-bold">Owner Email</label>
                 <input type="email" class="form-control" name="owner_email" required>
              </div>
              <div class="col-md-4">
                 <label class="form-label fw-bold">Phone Number</label>
                 <input type="text" class="form-control" name="owner_phone">
              </div>
          </div>
          <div class="row mb-3">
             <div class="col-12">
                 <label class="form-label fw-bold">Subscription Plan</label>
                 <select class="form-select" name="plan_id" required>
                     <?php foreach($plans as $plan): ?>
                        <option value="<?= $plan['id'] ?>"><?= htmlspecialchars($plan['plan_name']) ?> (₹<?= number_format($plan['yearly_price']) ?> / yr)</option>
                     <?php endforeach; ?>
                 </select>
             </div>
          </div>
          
          <div class="alert alert-info mt-3 border-info">
             <i class="fas fa-info-circle me-1"></i> A new dedicated master database and isolated filesystem folder will be automatically provisioned for this tenant upon creation.
          </div>
          
          <div class="text-end border-top pt-3">
              <button type="button" class="btn btn-secondary px-4 me-2" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary px-5 fw-bold" id="provisionSubmitBtn"><i class="fas fa-cogs me-1"></i> Provision Tenant Data</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('provisionForm').addEventListener('submit', function() {
    const btn = document.getElementById('provisionSubmitBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Provisioning Databases & Files...';
    btn.disabled = true;
});
</script>