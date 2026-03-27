<?php
/**
 * views/clients.php
 * This unified file handles client management for all authorized roles.
 * FINAL & COMPLETE: 
 * - The PHP logic has been moved to the central index.php action handler.
 * - Forms are updated to submit to index.php with correct action fields.
 */

$pdo = connectDB();
$message = '';

// Display message from session if redirected from an action
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$userPermissions = $_SESSION['user_permissions'] ?? [];
$allowedToAdd = in_array('clients', $userPermissions);
$allowedToEdit = in_array('clients', $userPermissions);
$allowedToDelete = in_array('users', $userPermissions);

// Fetch all clients
$clientsData = fetchAll($pdo, "SELECT * FROM clients ORDER BY client_name ASC");
?>

<h2 class="mb-4">Client Management</h2>

<?php if (!empty($message)): ?>
    <?php include VIEWS_PATH . 'components/message_box.php'; ?>
<?php endif; ?>

<?php if ($allowedToAdd): ?>
<div class="card shadow-sm rounded-3 mb-4">
    <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Add New Client</h5></div>
    <div class="card-body">
        <form action="index.php" method="POST">
             <input type="hidden" name="page" value="clients">
             <input type="hidden" name="action" value="add_client">
             <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Client Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="client_name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Company Name (Optional)</label>
                    <input type="text" class="form-control" name="company_name">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="phone" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email (Optional)</label>
                    <input type="email" class="form-control" name="email" placeholder="client@example.com">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Address (Optional)</label>
                <textarea class="form-control" name="address" rows="1"></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Add Client</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm rounded-3">
    <div class="card-header bg-secondary text-white"><h5 class="mb-0"><i class="fas fa-users me-2"></i>Existing Clients</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Company Name</th><th>Phone</th><th>Email</th><th>Address</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($clientsData as $client): ?>
                    <tr>
                        <td><?= htmlspecialchars($client['id']) ?></td>
                        <td><?= htmlspecialchars($client['client_name']) ?></td>
                        <td><?= htmlspecialchars($client['company_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($client['phone']) ?></td>
                        <td><?= htmlspecialchars($client['email'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($client['address'] ?? 'N/A') ?></td>
                        <td>
                            <?php if ($allowedToEdit): ?>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editClientModal" data-client='<?= htmlspecialchars(json_encode($client), ENT_QUOTES, 'UTF-8') ?>'><i class="fas fa-edit"></i> Edit</button>
                            <?php endif; ?>
                            <?php if ($allowedToDelete): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="showCustomConfirm('Delete Client', 'Are you sure you want to delete client: <b><?= htmlspecialchars($client['client_name']) ?></b>? This action cannot be undone.', 'delete-client-form-<?= $client['id'] ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                                <form id="delete-client-form-<?= $client['id'] ?>" action="index.php" method="POST" style="display: none;">
                                    <input type="hidden" name="page" value="clients">
                                    <input type="hidden" name="action" value="delete_client">
                                    <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
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

<div class="modal fade" id="editClientModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Client</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="index.php" method="POST">
                <input type="hidden" name="page" value="clients">
                <input type="hidden" name="action" value="edit_client">
                <div class="modal-body">
                    <input type="hidden" name="client_id" id="edit-client-id">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Name*</label><input type="text" class="form-control" name="client_name" id="edit-client-name" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Company Name (Optional)</label><input type="text" class="form-control" name="company_name" id="edit-company-name"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Phone*</label><input type="text" class="form-control" name="phone" id="edit-phone" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Email (Optional)</label><input type="email" class="form-control" name="email" id="edit-email"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Address (Optional)</label><textarea class="form-control" name="address" id="edit-address" rows="1"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Changes</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editClientModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const client = JSON.parse(button.getAttribute('data-client'));
            
            document.getElementById('edit-client-id').value = client.id;
            document.getElementById('edit-client-name').value = client.client_name;
            document.getElementById('edit-company-name').value = client.company_name || ''; 
            document.getElementById('edit-phone').value = client.phone;
            document.getElementById('edit-email').value = client.email || ''; 
            document.getElementById('edit-address').value = client.address || '';
        });
    }

    function showCustomConfirm(title, message, formId) {
        if (confirm(message)) {
            document.getElementById(formId).submit();
        }
    }
});
</script>