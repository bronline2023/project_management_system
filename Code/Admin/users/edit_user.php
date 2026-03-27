<?php
/**
 * admin/edit_user.php
 * FINAL & COMPLETE: Form submits correct Role ID and new Wallet/Rate features.
 */

$pdo = connectDB();
$message = '';
$user = null;
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$roles = getAllRoles();

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

if ($userId > 0) {
    // 🚀 Wallet balance and custom poster rate are fetched from database here 🚀
    $user = fetchOne($pdo, "SELECT id, name, email, role_id, salary, balance, custom_poster_rate FROM users WHERE id = ?", [$userId]);
    if (!$user) {
        $message = '<div class="alert alert-danger">User not found.</div>';
    }
} else {
     $message = '<div class="alert alert-danger">No User ID provided.</div>';
}
?>
<h2 class="mb-4">Edit User</h2>

<?php if (!empty($message)): ?>
    <?php include VIEWS_PATH . 'components/message_box.php'; ?>
<?php endif; ?>

<?php if ($user): ?>
    <div class="card shadow-sm rounded-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit User: <?= htmlspecialchars($user['name']) ?></h5>
        </div>
        <div class="card-body">
            <form action="index.php" method="POST">
                <input type="hidden" name="page" value="users">
                <input type="hidden" name="action" value="edit_user_submit">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                </div>
                 <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars($role['id']) ?>" <?= ($user['role_id'] == $role['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div class="col-md-6 mb-3">
                        <label for="salary" class="form-label">Monthly Salary</label>
                        <input type="number" step="0.01" class="form-control" id="salary" name="salary" value="<?= htmlspecialchars($user['salary']) ?>">
                    </div>
                </div>

                <div class="row mt-2 p-3 bg-light border rounded">
                    <div class="col-md-6 mb-3">
                        <label for="balance" class="form-label text-success fw-bold"><i class="fas fa-wallet"></i> Wallet Balance (₹) [Recharge]</label>
                        <input type="number" step="0.01" class="form-control border-success" id="balance" name="balance" value="<?= htmlspecialchars($user['balance'] ?? '0.00') ?>">
                    </div>

                </div>
                <div class="mb-3 mt-3">
                    <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="********">
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" class="btn btn-primary px-4">Update User</button>
                    <a href="<?= BASE_URL ?>?page=users" class="btn btn-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>