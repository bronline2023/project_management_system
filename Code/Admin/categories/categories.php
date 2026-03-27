<?php
/**
 * admin/categories.php
 * This file handles the management of categories and subcategories by the administrator.
 * All POST logic is now handled by the central index.php handler.
 */

$pdo = connectDB();
$message = '';

// Display message from session if redirected from an action
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

// --- Fetch Data for Display ---
$categories = fetchAll($pdo, "SELECT id, name, description, required_documents, is_live FROM categories ORDER BY name ASC");
$subcategories = fetchAll($pdo, "SELECT s.id, s.name AS subcategory_name, s.fare, s.description AS subcategory_description, c.name AS category_name, c.id AS category_id FROM subcategories s JOIN categories c ON s.category_id = c.id ORDER BY c.name ASC, s.name ASC");
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '$');
?>

<h2 class="mb-4">Category & Subcategory Management</h2>

<?php if (!empty($message)): ?>
    <?php include VIEWS_PATH . 'components/message_box.php'; ?>
<?php endif; ?>

<div class="card shadow-sm rounded-3 mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-folder-plus me-2"></i>Add New Category</h5>
    </div>
    <div class="card-body">
        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="add_category">
            <input type="hidden" name="page" value="categories">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-pill" id="category_name" name="name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="category_description" class="form-label">Description</label>
                    <textarea class="form-control rounded-3" id="category_description" name="description" rows="1"></textarea>
                </div>
            </div>
             <div class="mb-3">
                <label for="required_documents" class="form-label">Required Documents (one per line)</label>
                <textarea class="form-control" id="required_documents" name="required_documents" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary rounded-pill"><i class="fas fa-plus-circle me-2"></i>Add Category</button>
        </form>
    </div>
</div>

<div class="card shadow-sm rounded-3 mb-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-folder me-2"></i>Existing Categories</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr><th>ID</th><th>Name</th><th>Description</th><th>Required Documents</th><th>Live</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?= htmlspecialchars($category['id']) ?></td>
                            <td><?= htmlspecialchars($category['name']) ?></td>
                            <td><?= htmlspecialchars($category['description'] ?? '') ?></td>
                            <td><?= nl2br(htmlspecialchars($category['required_documents'] ?? '')) ?></td>
                            <td>
                                <?php if ($category['is_live']): ?>
                                    <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1" data-bs-toggle="modal" data-bs-target="#editCategoryModal" data-id="<?= $category['id'] ?>" data-name="<?= htmlspecialchars($category['name']) ?>" data-description="<?= htmlspecialchars($category['description'] ?? '') ?>" data-docs="<?= htmlspecialchars($category['required_documents'] ?? '') ?>" data-is-live="<?= htmlspecialchars($category['is_live']) ?>"><i class="fas fa-edit"></i> Edit</button>
                                <a href="<?= BASE_URL ?>?page=categories&action=delete_category&id=<?= $category['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Are you sure? This will delete all associated subcategories.')"><i class="fas fa-trash-alt"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm rounded-3 mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i>Add New Subcategory</h5>
    </div>
    <div class="card-body">
        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="add_subcategory">
            <input type="hidden" name="page" value="categories">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="category_id_sub" class="form-label">Parent Category <span class="text-danger">*</span></label>
                    <select class="form-select rounded-pill" id="category_id_sub" name="category_id_sub" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category['id']) ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="sub_name" class="form-label">Subcategory Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-pill" id="sub_name" name="sub_name" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="fare" class="form-label">Fare / Fee (<?= $currencySymbol ?>) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control rounded-pill" id="fare" name="fare" min="0" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="sub_description" class="form-label">Description</label>
                    <textarea class="form-control rounded-3" id="sub_description" name="sub_description" rows="1"></textarea>
                </div>
            </div>
            <button type="submit" name="add_subcategory" class="btn btn-info text-white rounded-pill"><i class="fas fa-plus-circle me-2"></i>Add Subcategory</button>
        </form>
    </div>
</div>

<div class="card shadow-sm rounded-3">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Existing Subcategories</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr><th>ID</th><th>Category</th><th>Subcategory Name</th><th>Fare</th><th>Description</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($subcategories as $sub): ?>
                        <tr>
                            <td><?= htmlspecialchars($sub['id']) ?></td>
                            <td><span class="badge bg-primary"><?= htmlspecialchars($sub['category_name']) ?></span></td>
                            <td><?= htmlspecialchars($sub['subcategory_name']) ?></td>
                            <td><?= $currencySymbol ?><?= number_format($sub['fare'], 2) ?></td>
                            <td><?= htmlspecialchars($sub['subcategory_description'] ?? '') ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1" data-bs-toggle="modal" data-bs-target="#editSubcategoryModal" data-id="<?= $sub['id'] ?>" data-category-id="<?= $sub['category_id'] ?>" data-name="<?= htmlspecialchars($sub['subcategory_name']) ?>" data-fare="<?= $sub['fare'] ?>" data-description="<?= htmlspecialchars($sub['subcategory_description']) ?>"><i class="fas fa-edit"></i> Edit</button>
                                <a href="<?= BASE_URL ?>?page=categories&action=delete_subcategory&id=<?= $sub['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Are you sure?')"><i class="fas fa-trash-alt"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="index.php" method="POST">
                <input type="hidden" name="action" value="edit_category">
                <input type="hidden" name="page" value="categories">
                <div class="modal-body">
                    <input type="hidden" id="edit-category-id" name="category_id">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" class="form-control" id="edit-category-name" name="name" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="edit-category-description" name="description" rows="2"></textarea></div>
                    <div class="mb-3">
                        <label for="edit-required-docs" class="form-label">Required Documents (one per line)</label>
                        <textarea class="form-control" id="edit-required-docs" name="required_documents" rows="3"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="edit-is-live" name="is_live" value="1">
                        <label class="form-check-label" for="edit-is-live">Make Live for Online Booking</label>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Changes</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editSubcategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Subcategory</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="index.php" method="POST">
                <input type="hidden" name="action" value="edit_subcategory">
                <input type="hidden" name="page" value="categories">
                <div class="modal-body">
                    <input type="hidden" id="edit-subcategory-id" name="subcategory_id">
                    <div class="mb-3"><label class="form-label">Parent Category</label><select class="form-select" id="edit-category-id-sub" name="edit_category_id_sub" required><?php foreach ($categories as $category): ?><option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" class="form-control" id="edit-sub-name" name="edit_sub_name" required></div>
                    <div class="mb-3"><label class="form-label">Fare</label><input type="number" step="0.01" class="form-control" id="edit-fare" name="edit_fare" min="0" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="edit-sub-description" name="edit_sub_description" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Changes</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editCategoryModal = document.getElementById('editCategoryModal');
    editCategoryModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('edit-category-id').value = button.getAttribute('data-id');
        document.getElementById('edit-category-name').value = button.getAttribute('data-name');
        document.getElementById('edit-category-description').value = button.getAttribute('data-description');
        document.getElementById('edit-required-docs').value = button.getAttribute('data-docs');
        
        const isLive = button.getAttribute('data-is-live') === '1';
        document.getElementById('edit-is-live').checked = isLive;
    });

    const editSubcategoryModal = document.getElementById('editSubcategoryModal');
    editSubcategoryModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('edit-subcategory-id').value = button.getAttribute('data-id');
        document.getElementById('edit-category-id-sub').value = button.getAttribute('data-category-id');
        document.getElementById('edit-sub-name').value = button.getAttribute('data-name');
        document.getElementById('edit-fare').value = button.getAttribute('data-fare');
        document.getElementById('edit-sub-description').value = button.getAttribute('data-description');
    });
});
</script>