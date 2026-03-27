<?php
/**
 * admin/manage_roles.php
 * FINAL FIXED VERSION
 * Fixes:
 * 1. Added 'modal-dialog-scrollable' to fix incomplete popup issue.
 * 2. Changed Delete Link to POST Form to fix "Code not running" issue.
 * 3. Improved JSON parsing for Edit Modal to prevent JS errors.
 */

$pdo = connectDB();
$message = '';

// Display message from session
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$roles = getAllRoles();
$master_permissions_list = getMasterPermissionsList();
$dashboard_permissions_list = getDashboardPermissionsList();
?>
<h2 class="mb-4">Manage Roles & Permissions</h2>
<?php if ($message) { include VIEWS_PATH . 'components/message_box.php'; } ?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Create New Role</h5></div>
    <div class="card-body">
        <form action="index.php" method="POST">
            <input type="hidden" name="page" value="manage_roles">
            <input type="hidden" name="action" value="add_role">
            
            <div class="mb-3">
                <label for="role_name" class="form-label fw-bold">Role Name</label>
                <input type="text" name="role_name" id="role_name" class="form-control" placeholder="e.g. Senior Manager" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-primary">Page Access Permissions</label>
                <div class="accordion" id="permissionsAccordion">
                    <?php $i = 0; foreach ($master_permissions_list as $group => $permissions): $i++; ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $i ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $i ?>">
                                    <?= htmlspecialchars($group) ?>
                                </button>
                            </h2>
                            <div id="collapse<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body">
                                    <div class="row">
                                        <?php foreach ($permissions as $key => $label): ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $key ?>" id="perm_new_<?= $key ?>">
                                                <label class="form-check-label" for="perm_new_<?= $key ?>"><?= $label ?></label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <h5 class="mt-4 fw-bold text-primary">Dashboard Widgets</h5>
            <div class="mb-3 bg-light p-3 border rounded">
                <p class="small text-muted mb-2">Select which widgets this role can see on their dashboard.</p>
                <div class="row">
                <?php foreach ($dashboard_permissions_list as $key => $label): ?>
                    <div class="col-md-6 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="dashboard_permissions[]" value="<?= $key ?>" id="dash_perm_new_<?= $key ?>">
                            <label class="form-check-label" for="dash_perm_new_<?= $key ?>"><?= $label ?></label>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary px-4 fw-bold">Create Role</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-secondary text-white"><h5 class="mb-0"><i class="fas fa-list me-2"></i>Existing Roles</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>ID</th><th>Role Name</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                    <tr>
                        <td><?= $role['id'] ?></td>
                        <td><strong><?= htmlspecialchars($role['role_name']) ?></strong></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary me-1" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editRoleModal" 
                                data-role-id="<?= $role['id'] ?>" 
                                data-role-name="<?= htmlspecialchars($role['role_name']) ?>" 
                                data-role-permissions='<?= htmlspecialchars($role['permissions'] ?: '[]') ?>' 
                                data-dashboard-permissions='<?= htmlspecialchars($role['dashboard_permissions'] ?: '[]') ?>'>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            
                            <?php if ($role['id'] != 1): // Prevent Deleting Admin ?>
                            <form action="index.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure? Users with this role will lose access.');">
                                <input type="hidden" name="action" value="delete_role">
                                <input type="hidden" name="page" value="manage_roles">
                                <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> Delete</button>
                            </form>
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
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Edit Role</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="index.php" method="POST" id="editRoleForm">
                    <input type="hidden" name="page" value="manage_roles">
                    <input type="hidden" name="action" value="edit_role">
                    <input type="hidden" name="role_id" id="edit-role-id">
                    
                    <div class="mb-3">
                        <label for="edit-role-name" class="form-label fw-bold">Role Name</label>
                        <input type="text" name="role_name" id="edit-role-name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary">Page Permissions</label>
                         <?php foreach ($master_permissions_list as $group => $permissions): ?>
                            <fieldset class="border p-3 mb-3 bg-light rounded">
                                <legend class="w-auto px-2 fs-6 fw-bold"><?= htmlspecialchars($group) ?></legend>
                                <div class="row">
                                    <?php foreach ($permissions as $key => $label): ?>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $key ?>" id="perm_edit_<?= $key ?>">
                                            <label class="form-check-label" for="perm_edit_<?= $key ?>"><?= $label ?></label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        <?php endforeach; ?>
                    </div>
                    
                     <h5 class="mt-4 fw-bold text-primary">Dashboard Widgets</h5>
                    <div class="mb-3 bg-light p-3 border rounded">
                        <div class="row">
                        <?php foreach ($dashboard_permissions_list as $key => $label): ?>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="dashboard_permissions[]" value="<?= $key ?>" id="dash_perm_edit_<?= $key ?>">
                                    <label class="form-check-label" for="dash_perm_edit_<?= $key ?>"><?= $label ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="editRoleForm" class="btn btn-primary">Save Changes</button>
            </div>
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
        
        // Robust JSON Parsing
        var permissions = [];
        var dashboardPermissions = [];
        try {
            var pData = button.getAttribute('data-role-permissions');
            permissions = pData ? JSON.parse(pData) : [];
        } catch(e) { console.error('Perms Parse Error', e); }
        
        try {
            var dpData = button.getAttribute('data-dashboard-permissions');
            dashboardPermissions = dpData ? JSON.parse(dpData) : [];
        } catch(e) { console.error('Dash Perms Parse Error', e); }

        var modal = this;
        modal.querySelector('#edit-role-id').value = roleId;
        modal.querySelector('#edit-role-name').value = roleName;
        
        // Reset Checkboxes
        modal.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        
        // Set Page Permissions
        if (Array.isArray(permissions)) {
            permissions.forEach(p => {
                var cb = modal.querySelector('input[name="permissions[]"][value="' + p + '"]');
                if (cb) cb.checked = true;
            });
        }

        // Set Dashboard Permissions
        if (Array.isArray(dashboardPermissions)) {
            dashboardPermissions.forEach(p => {
                var cb = modal.querySelector('input[name="dashboard_permissions[]"][value="' + p + '"]');
                if (cb) cb.checked = true;
            });
        }
    });
});
</script>