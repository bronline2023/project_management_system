<?php
/**
 * admin/users.php
 * FINAL UPDATED: Added 'Recalculate Balance' button to fix sync issues.
 */

$pdo = connectDB();
$message = '';

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

// --- SEARCH & FILTER ---
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';

$sql = "SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id > 0";
$params = [];

if ($search) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($roleFilter) {
    $sql .= " AND r.role_name = ?";
    $params[] = $roleFilter;
}
$sql .= " ORDER BY u.id DESC";

$users = fetchAll($pdo, $sql, $params);
$roles = fetchAll($pdo, "SELECT * FROM roles");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Users & Wallet Management</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-user-plus me-2"></i> Add User
        </button>
    </div>

    <?php if ($message) { include VIEWS_PATH . 'components/message_box.php'; } ?>

    <div class="card shadow mb-4">
        <div class="card-body py-3">
            <form method="GET" action="index.php" class="row g-3 align-items-center">
                <input type="hidden" name="page" value="users">
                <div class="col-auto"><input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>"></div>
                <div class="col-auto">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $r): echo "<option value='{$r['role_name']}' " . ($roleFilter == $r['role_name'] ? 'selected' : '') . ">{$r['role_name']}</option>"; endforeach; ?>
                    </select>
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i></button></div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-dark text-white"><h6 class="m-0 font-weight-bold">User List & Balances</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>User Details</th>
                            <th>Role</th>
                            <th class="text-center">Wallet Balance</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users): foreach ($users as $user): ?>
                        <tr>
                            <td>#<?= $user['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?= !empty($user['profile_picture']) ? $user['profile_picture'] : 'assets/img/default_avatar.png' ?>" class="rounded-circle border me-2" width="40" height="40" style="object-fit:cover;">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($user['name']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($user['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($user['role_name']) ?></span></td>
                            
                            <td class="text-center">
                                <div class="h5 mb-0 fw-bold <?= $user['balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $currencySymbol . number_format($user['balance'], 2) ?>
                                </div>
                                
                                <form action="index.php" method="POST" class="d-inline-block mt-1">
                                    <input type="hidden" name="action" value="recalculate_user_balance">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn btn-outline-warning btn-sm p-0 px-2" title="Fix Wrong Balance" style="font-size: 0.75rem;">
                                        <i class="fas fa-sync-alt"></i> Fix Balance
                                    </button>
                                </form>
                            </td>

                            <td class="text-center">
                                <a href="index.php?page=edit_user&id=<?= $user['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                <?php if ($user['id'] != 1 && $user['id'] != $_SESSION['user_id']): ?>
                                <form action="index.php" method="POST" class="d-inline" onsubmit="return confirm('Delete user?');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" class="text-center py-4">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include VIEWS_PATH . 'register.php'; // Or inline modal code ?>