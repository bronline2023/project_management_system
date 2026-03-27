<?php
/**
 * views/manage_b2b_users.php
 * MASTER ADMIN: Create District Managers & Retailers manually.
 */

if (($_SESSION['user_role'] ?? '') !== 'master_admin') {
    echo "<div class='alert alert-danger text-center mt-5'>Access Denied. Only Master Admin can access this.</div>"; return;
}

$pdo = connectDB();

// Fetch the dynamic roles (Categories) from the database
$roles = $pdo->query("SELECT * FROM roles WHERE role_name != 'master_admin'")->fetchAll(PDO::FETCH_ASSOC);

// Fetch users created so far (using balance according to your database)
$users = $pdo->query("SELECT u.id, u.name, u.email, u.balance, u.created_at, r.display_name 
                      FROM users u 
                      LEFT JOIN roles r ON u.role = r.role_name 
                      WHERE u.role != 'master_admin' 
                      ORDER BY u.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
        <h3 class="fw-bold text-dark"><i class="fas fa-user-tie text-primary"></i> B2B Subscription Management</h3>
        <button class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="fas fa-plus"></i> Create New User
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Name & Contact</th>
                        <th>Subscription Role</th>
                        <th>Wallet Balance</th>
                        <th>Joined Date</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?= htmlspecialchars($u['name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($u['email']) ?></small>
                        </td>
                        <td><span class="badge bg-info text-dark px-3 py-2"><?= htmlspecialchars($u['display_name'] ?? 'User') ?></span></td>
                        
                        <td class="fw-bold text-success fs-5">₹<?= number_format($u['balance'], 2) ?></td>
                        
                        <td><?= date('d M, Y', strtotime($u['created_at'])) ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-success" onclick="addBalance(<?= $u['id'] ?>, '<?= addslashes($u['name']) ?>')" title="Add Funds">
                                <i class="fas fa-rupee-sign"></i> Add Fund
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Suspend User"><i class="fas fa-ban"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-rocket"></i> Assign New Subscription</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formB2BUser">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Client Full Name</label>
                            <input type="text" id="b_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Email Address (Login ID)</label>
                            <input type="email" id="b_email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Select Subscription Category (Role)</label>
                            <select id="b_role" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach($roles as $r): ?>
                                    <option value="<?= $r['role_name'] ?>"><?= $r['display_name'] ?> - <?= $r['description'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Set Password</label>
                            <input type="text" id="b_pass" class="form-control" value="<?= substr(md5(time()), 0, 8) ?>" required>
                        </div>
                    </div>
                    <hr>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold" id="btnSubmit">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('formB2BUser').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmit');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;

    const data = {
        name: document.getElementById('b_name').value,
        email: document.getElementById('b_email').value,
        pass: document.getElementById('b_pass').value,
        role: document.getElementById('b_role').value
    };

    fetch('app/create_b2b_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            alert("User successfully created!");
            location.reload();
        } else {
            alert("❌ Error: " + res.message);
            btn.innerHTML = 'Create User'; btn.disabled = false;
        }
    });
});

function addBalance(userId, userName) {
    let amount = prompt(`How much rupees do you want to add to ${userName}'s wallet?`);
    if(amount && amount > 0) {
        fetch('app/manual_recharge.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, amount: amount })
        }).then(res => res.json()).then(res => {
            if(res.success) { alert("✅ Balance has been added!"); location.reload(); }
            else { alert("❌ Error: " + res.message); }
        });
    }
}
</script>