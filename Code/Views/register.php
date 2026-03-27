<?php
/**
 * views/register.php
 * Provides a form for the administrator to register new users.
 * FINAL: Form submits to the central index.php handler.
 */
$pdo = connectDB();
$message = '';
$roles = getAllRoles(); 

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}
?>

<h2 class="mb-4">Register New User</h2>

<?php if (!empty($message)): ?>
    <?php include VIEWS_PATH . 'components/message_box.php'; ?>
<?php endif; ?>

<div class="card shadow-sm rounded-3 mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>User Registration Form</h5>
    </div>
    <div class="card-body">
        <form action="index.php" method="POST">
            <input type="hidden" name="page" value="users">
            <input type="hidden" name="action" value="register_user">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-pill" id="name" name="name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control rounded-pill" id="email" name="email" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control rounded-pill" id="password" name="password" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                    <select class="form-select rounded-pill" id="role_id" name="role_id" required>
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role['id']) ?>">
                                <?= htmlspecialchars($role['role_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary rounded-pill"><i class="fas fa-user-plus me-2"></i>Register User</button>
        </form>
    </div>
</div>