<?php
/**
 * views/manage_users.php
 * MASTER ADMIN & ADMIN PANEL: Create Users, Assign Subscriptions & Manual Recharge
 */

$user_role = $_SESSION['user_role'] ?? 'guest';
$portal_id = $_SESSION['current_portal_id'] ?? null;
$my_user_id = $_SESSION['user_id'] ?? 0;

// Only management level people can see this page
if (!in_array($user_role, ['master_admin', 'admin', 'manager', 'district_manager'])) {
    echo "<div class='alert alert-danger text-center mt-5'><h4>You are not allowed to view this page.</h4></div>";
    return;
}

$pdo = connectDB();

// Query to fetch user list (based on who is logged in)
$query = "SELECT u.*, r.role_name as dynamic_role, p.domain_name FROM users u LEFT JOIN roles r ON u.role_id = r.id LEFT JOIN portals p ON u.portal_id = p.id WHERE 1=1 ";
if ($user_role !== 'master_admin') {
    // If there is no Master, only users of own portal are visible
    $query .= " AND u.portal_id = $portal_id AND u.id != $my_user_id";
}
$query .= " ORDER BY u.id DESC";

try {
    $users = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $users = []; }

// Separate users into two groups for organized display
$high_level_roles = ['master_admin', 'admin', 'manager', 'district_manager'];
$portal_users = [];
$retailer_users = [];

foreach($users as $u) {
    if (in_array($u['role'], $high_level_roles)) {
        $portal_users[] = $u;
    } else {
        $retailer_users[] = $u;
    }
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h3 class="fw-bold text-dark"><i class="fas fa-users-cog text-primary"></i> User & Network Management</h3>
        <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="fas fa-user-plus"></i> Create New User
        </button>
    </div>

    <!-- High Level Portal Users View -->
    <h5 class="fw-bold text-dark mt-4 mb-3"><i class="fas fa-user-tie text-success"></i> Portal Partners & Managers</h5>
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless m-0">
                    <thead class="bg-light text-muted">
                        <tr>
                            <th class="ps-4">Name & Email</th>
                            <th>Role</th>
                            <th>Assigned Portal</th>
                            <th>Wallet Balance</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($portal_users)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No high-level portal users found.</td></tr>
                        <?php else: ?>
                            <?php foreach($portal_users as $u): ?>
                            <tr class="border-bottom">
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($u['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($u['email']) ?></small>
                                </td>
                                <td>
                                    <?php $displayRole = !empty($u['dynamic_role']) ? $u['dynamic_role'] : $u['role']; ?>
                                    <span class="badge bg-primary px-3 py-2 text-uppercase"><?= str_replace('_', ' ', $displayRole) ?></span>
                                </td>
                                <td>
                                    <?php if($u['domain_name']): ?>
                                        <span class="text-success fw-bold"><i class="fas fa-globe"></i> <?= htmlspecialchars($u['domain_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Standard Link</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-success fs-5">₹<?= number_format($u['wallet_balance'], 2) ?></div>
                                </td>
                                <td class="text-center">
                                    <?php if($user_role === 'master_admin' || $user_role === 'admin'): ?>
                                    <button class="btn btn-sm btn-outline-success fw-bold" onclick="addManualBalance(<?= $u['id'] ?>, '<?= $u['name'] ?>')">
                                        <i class="fas fa-plus"></i> Add Fund
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Standard Retailers and Users View -->
    <h5 class="fw-bold text-dark mb-3"><i class="fas fa-users text-info"></i> Retailers & Standard Users</h5>
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless m-0">
                    <thead class="bg-light text-muted">
                        <tr>
                            <th class="ps-4">Name & Email</th>
                            <th>Role</th>
                            <th>Assigned Portal</th>
                            <th>Wallet Balance</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($retailer_users)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No retailers or standard users found.</td></tr>
                        <?php else: ?>
                            <?php foreach($retailer_users as $u): ?>
                            <tr class="border-bottom">
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($u['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($u['email']) ?></small>
                                </td>
                                <td>
                                    <?php $displayRole = !empty($u['dynamic_role']) ? $u['dynamic_role'] : $u['role']; ?>
                                    <span class="badge bg-secondary px-3 py-2 text-uppercase"><?= str_replace('_', ' ', $displayRole) ?></span>
                                </td>
                                <td>
                                    <?php if($u['domain_name']): ?>
                                        <span class="text-success fw-bold"><i class="fas fa-globe"></i> <?= htmlspecialchars($u['domain_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Standard User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-success fs-5">₹<?= number_format($u['wallet_balance'], 2) ?></div>
                                </td>
                                <td class="text-center">
                                    <?php if($user_role === 'master_admin' || $user_role === 'admin'): ?>
                                    <button class="btn btn-sm btn-outline-success fw-bold" onclick="addManualBalance(<?= $u['id'] ?>, '<?= $u['name'] ?>')">
                                        <i class="fas fa-plus"></i> Add Fund
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus"></i> Register New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formCreateUser">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Full Name</label>
                            <input type="text" id="c_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Email Address</label>
                            <input type="email" id="c_email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Password</label>
                            <input type="password" id="c_pass" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Assign Role</label>
                            <select id="c_role" class="form-select" onchange="togglePortalSection()" required>
                                <?php if($user_role === 'master_admin'): ?>
                                    <option value="admin">Admin (Give New Portal)</option>
                                <?php endif; ?>
                                <option value="manager">Manager</option>
                                <option value="district_manager">District Manager</option>
                                <option value="retailer" selected>Retailer</option>
                            </select>
                        </div>
                    </div>

                    <div id="portalSection" class="p-3 bg-light border rounded mt-3" style="display: <?= ($user_role === 'master_admin') ? 'block' : 'none' ?>;">
                        <h6 class="fw-bold text-primary"><i class="fas fa-crown"></i> Direct Portal Subscription (For Admin)</h6>
                        <p class="text-muted small mb-3">If you are creating an 'Admin', he needs to be allocated a new domain and personal folder.</p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Assign Domain Name</label>
                                <input type="text" id="c_domain" class="form-control" placeholder="e.g. client-bronline.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Subscription Plan</label>
                                <select id="c_plan" class="form-select">
                                    <option value="1">1 Year Premium Plan</option>
                                    <option value="2">Monthly Basic Plan</option>
                                    <option value="3">Lifetime Free (Complimentary)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Create User & Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePortalSection() {
    const role = document.getElementById('c_role').value;
    const section = document.getElementById('portalSection');
    if (role === 'admin') { section.style.display = 'block'; } 
    else { section.style.display = 'none'; }
}

document.getElementById('formCreateUser').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const data = {
        name: document.getElementById('c_name').value,
        email: document.getElementById('c_email').value,
        pass: document.getElementById('c_pass').value,
        role: document.getElementById('c_role').value,
        domain: document.getElementById('c_domain').value,
        plan: document.getElementById('c_plan').value,
        creator_portal_id: <?= $portal_id ?: 'null' ?>
    };

    fetch('app/create_user_direct.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            alert("✅ " + res.message);
            location.reload();
        } else {
            alert("❌ Error: " + res.message);
        }
    });
});

function addManualBalance(userId, userName) {
    let amount = prompt(`How much rupees do you want to add to the wallet of ${userName}? (Manual Recharge)`);
    if(amount && amount > 0) {
        fetch('app/manual_recharge.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, amount: amount })
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) { alert("✅ Balance added successfully!"); location.reload(); }
            else { alert("❌ Error: " + res.message); }
        });
    }
}
</script>