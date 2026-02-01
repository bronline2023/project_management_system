<?php
/**
 * admin/manage_roles.php
 * This file allows admins to manage user roles and their permissions.
 * FINAL & COMPLETE: All form submissions and actions are now correctly handled by the central index.php handler.
 */

$pdo = connectDB();
$message = '';

// Display message from session if redirected from an action
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$roles = getAllRoles();
$master_permissions_list = getMasterPermissionsList();
$dashboard_permissions_list = getDashboardPermissionsList(); // NEW: Get list of dashboard permissions
?>
<h2 class="mb-4">Manage Roles & Permissions</h2>
<?php if ($message) { include VIEWS_PATH . 'components/message_box.php'; } ?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">Create New Role</h5></div>
    <div class="card-body">
        <form action="index.php" method="POST">
            <input type="hidden" name="page" value="manage_roles">
            <input type="hidden" name="action" value="add_role">
            <div class="mb-3"><label for="role_name" class="form-label">Role Name</label><input type="text" name="role_name" id="role_name" class="form-control" required></div>
            <div class="mb-3">
                <label class="form-label">Permissions</label>
                <?php foreach ($master_permissions_list as $group => $permissions): ?>
                    <fieldset class="border p-3 mb-3"><legend class="w-auto px-2 fs-6"><?= htmlspecialchars($group) ?></legend>
                        <div class="row">
                            <?php foreach ($permissions as $key => $label): ?>
                            <div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $key ?>" id="perm_new_<?= $key ?>"><label class="form-check-label" for="perm_new_<?= $key ?>"><?= $label ?></label></div></div>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endforeach; ?>
            </div>

            <h5 class="mt-4">Dashboard Permissions</h5>
            <div class="mb-3">
                <p>Select which sections this role can see on their dashboard.</p>
                <?php foreach ($dashboard_permissions_list as $key => $label): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="dashboard_permissions[]" value="<?= $key ?>" id="dash_perm_new_<?= $key ?>">
                        <label class="form-check-label" for="dash_perm_new_<?= $key ?>"><?= $label ?></label>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-primary">Create Role</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-secondary text-white"><h5 class="mb-0">Existing Roles</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>ID</th><th>Role Name</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                    <tr>
                        <td><?= $role['id'] ?></td><td><?= htmlspecialchars($role['role_name']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editRoleModal" data-role-id="<?= $role['id'] ?>" data-role-name="<?= htmlspecialchars($role['role_name']) ?>" data-role-permissions='<?= htmlspecialchars($role['permissions']) ?>' data-dashboard-permissions='<?= htmlspecialchars($role['dashboard_permissions'] ?? '[]') ?>'><i class="fas fa-edit"></i> Edit</button>
                            <?php if ($role['id'] != 1): ?>
                            <a href="?page=manage_roles&action=delete_role&id=<?= $role['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i> Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="index.php" method="POST">
                <input type="hidden" name="page" value="manage_roles">
                <input type="hidden" name="action" value="edit_role">
                <div class="modal-body">
                    <input type="hidden" name="role_id" id="edit-role-id">
                    <div class="mb-3"><label for="edit-role-name" class="form-label">Role Name</label><input type="text" name="role_name" id="edit-role-name" class="form-control" required></div>
                    <div class="mb-3">
                        <label class="form-label">Permissions</label>
                         <?php foreach ($master_permissions_list as $group => $permissions): ?>
                            <fieldset class="border p-3 mb-3"><legend class="w-auto px-2 fs-6"><?= $group ?></legend>
                                <div class="row">
                                    <?php foreach ($permissions as $key => $label): ?>
                                    <div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $key ?>" id="perm_edit_<?= $key ?>"><label class="form-check-label" for="perm_edit_<?= $key ?>"><?= $label ?></label></div></div>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        <?php endforeach; ?>
                    </div>
                     <h5 class="mt-4">Dashboard Permissions</h5>
                    <div class="mb-3">
                        <p>Select which sections this role can see on their dashboard.</p>
                        <?php foreach ($dashboard_permissions_list as $key => $label): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="dashboard_permissions[]" value="<?= $key ?>" id="dash_perm_edit_<?= $key ?>">
                                <label class="form-check-label" for="dash_perm_edit_<?= $key ?>"><?= $label ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var editModal = document.getElementById('editRoleModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var roleId = button.getAttribute('data-role-id');
        var roleName = button.getAttribute('data-role-name');
        var permissions = JSON.parse(button.getAttribute('data-role-permissions') || '[]');
        var dashboardPermissions = JSON.parse(button.getAttribute('data-dashboard-permissions') || '[]'); // NEW

        var modal = this;
        modal.querySelector('#edit-role-id').value = roleId;
        modal.querySelector('#edit-role-name').value = roleName;
        
        // Reset all checkboxes first
        modal.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        
        // Set page permissions
        if (permissions === '*') {
            modal.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = true);
        } else if (Array.isArray(permissions)) {
            permissions.forEach(p => {
                var cb = modal.querySelector('input[name="permissions[]"][value="' + p + '"]');
                if (cb) cb.checked = true;
            });
        }

        // Set dashboard permissions
        if (Array.isArray(dashboardPermissions)) {
            dashboardPermissions.forEach(p => {
                var cb = modal.querySelector('input[name="dashboard_permissions[]"][value="' + p + '"]');
                if (cb) cb.checked = true;
            });
        }
    });
});
</script>