<?php
/**
 * admin/users.php
 * FINAL & COMPLETE:
 * - Shows user status (Active/Inactive).
 * - Adds Edit, Delete, and Disable/Enable buttons that trigger a custom confirmation modal.
 * - Prevents actions against the main admin (ID 1) and the currently logged-in user.
 */
$pdo = connectDB();
$currentUserId = $_SESSION['user_id']; // Get current logged-in user's ID

$users = fetchAll($pdo, "
    SELECT u.id, u.name, u.email, u.created_at, u.status, r.role_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    ORDER BY u.created_at DESC
");
?>

<h2 class="mb-4">Manage System Users</h2>

<?php if (isset($_SESSION['status_message'])): ?>
    <?php 
    $message = $_SESSION['status_message'];
    include VIEWS_PATH . 'components/message_box.php'; 
    unset($_SESSION['status_message']); 
    ?>
<?php endif; ?>

<div class="card shadow-sm rounded-3">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i>All System Users</h5>
        <a href="<?= BASE_URL ?>?page=register" class="btn btn-success rounded-pill px-4">Register New User</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($user['role_name'] ?? 'No Role') ?></span></td>
                            <td>
                                <?php if (strtolower($user['status']) === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['id'] == 1): // Main Admin User ?>
                                    <a href="<?= BASE_URL ?>?page=edit_user&id=<?= htmlspecialchars($user['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit Admin Profile"><i class="fas fa-edit"></i> Edit</a>
                                <?php elseif ($user['id'] != $currentUserId): // Other users, but not self ?>
                                    <div class="btn-group">
                                        <a href="<?= BASE_URL ?>?page=edit_user&id=<?= htmlspecialchars($user['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit User"><i class="fas fa-edit"></i></a>
                                        
                                        <?php if (strtolower($user['status']) === 'active'): ?>
                                            <a href="<?= BASE_URL ?>?page=users&action=toggle_user_status&id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-warning" title="Disable User" onclick="return confirm('Are you sure you want to disable this user?');">
                                                <i class="fas fa-user-slash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>?page=users&action=toggle_user_status&id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-success" title="Enable User" onclick="return confirm('Are you sure you want to enable this user?');">
                                                <i class="fas fa-user-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <form action="index.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this user? This action cannot be undone.');">
                                            <input type="hidden" name="page" value="users">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete User"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                <?php else: // Current logged-in user ?>
                                     <a href="<?= BASE_URL ?>?page=edit_user&id=<?= htmlspecialchars($user['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit My Profile"><i class="fas fa-edit"></i> Edit</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>