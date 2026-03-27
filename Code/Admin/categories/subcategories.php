<?php
/**
 * admin/subcategories.php
 * This file handles the management of subcategories.
 * It is now assumed that categories are managed in categories.php.
 */

$pdo = connectDB();
$message = '';

// Handle Add Subcategory
if (isset($_POST['add_subcategory'])) {
    $category_id = $_POST['category_id_sub'];
    $name = trim($_POST['sub_name']);
    $fare = floatval($_POST['fare']);
    $description = trim($_POST['sub_description']);

    if (empty($category_id) || empty($name) || !is_numeric($fare) || $fare < 0) {
        $message = '<div class="alert alert-danger" role="alert">Subcategory Name, Category, and a valid Fare are required.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO subcategories (category_id, name, fare, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$category_id, $name, $fare, $description]);
            $message = '<div class="alert alert-success" role="alert">Subcategory added successfully!</div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger" role="alert">Error: A subcategory with this name already exists in the selected category.</div>';
        }
    }
}

// Handle Edit Subcategory
if (isset($_POST['edit_subcategory'])) {
    $id = $_POST['subcategory_id'];
    $category_id = $_POST['edit_category_id_sub'];
    $name = trim($_POST['edit_sub_name']);
    $fare = floatval($_POST['edit_fare']);
    $description = trim($_POST['edit_sub_description']);

    if (empty($id) || empty($category_id) || empty($name) || !is_numeric($fare) || $fare < 0) {
        $message = '<div class="alert alert-danger" role="alert">All fields are required for editing.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE subcategories SET category_id = ?, name = ?, fare = ?, description = ? WHERE id = ?");
            $stmt->execute([$category_id, $name, $fare, $description, $id]);
            $message = '<div class="alert alert-success" role="alert">Subcategory updated successfully!</div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger" role="alert">Error: A subcategory with this name already exists in the selected category.</div>';
        }
    }
}

// Handle Delete Subcategory
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM subcategories WHERE id = ?");
        $stmt->execute([$id]);
        $message = '<div class="alert alert-success" role="alert">Subcategory deleted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger" role="alert">Error deleting subcategory. Make sure no tasks are associated with it.</div>';
    }
}

// Fetch Data for Display
$categories = fetchAll($pdo, "SELECT id, name FROM categories ORDER BY name ASC");
$subcategories = fetchAll($pdo, "SELECT s.id, s.name AS subcategory_name, s.fare, s.description AS subcategory_description, c.name AS category_name, c.id AS category_id FROM subcategories s JOIN categories c ON s.category_id = c.id ORDER BY c.name ASC, s.name ASC");
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '$');
?>

<h2 class="mb-4">Subcategory Management</h2>

<?php if (!empty($message)): ?>
    <?php include VIEWS_PATH . 'components/message_box.php'; ?>
<?php endif; ?>

<div class="card shadow-sm rounded-3 mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i>Add New Subcategory</h5>
    </div>
    <div class="card-body">
        <form action="" method="POST">
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
            <button type="submit" name="add_subcategory" class="btn btn-primary rounded-pill"><i class="fas fa-plus-circle me-2"></i>Add Subcategory</button>
        </form>
    </div>
</div>

<div class="card shadow-sm rounded-3">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Existing Subcategories</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr><th>ID</th><th>Category</th><th>Subcategory</th><th>Fare</th><th>Description</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($subcategories as $sub): ?>
                        <tr>
                            <td><?= htmlspecialchars($sub['id']) ?></td>
                            <td><span class="badge bg-primary"><?= htmlspecialchars($sub['category_name']) ?></span></td>
                            <td><?= htmlspecialchars($sub['subcategory_name']) ?></td>
                            <td><?= $currencySymbol ?><?= number_format($sub['fare'], 2) ?></td>
                            <td><?= htmlspecialchars($sub['subcategory_description']) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1" data-bs-toggle="modal" data-bs-target="#editSubcategoryModal" data-id="<?= $sub['id'] ?>" data-category-id="<?= $sub['category_id'] ?>" data-name="<?= htmlspecialchars($sub['subcategory_name']) ?>" data-fare="<?= $sub['fare'] ?>" data-description="<?= htmlspecialchars($sub['subcategory_description']) ?>"><i class="fas fa-edit"></i> Edit</button>
                                <a href="<?= BASE_URL ?>?page=subcategories&action=delete&id=<?= $sub['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Are you sure?')"><i class="fas fa-trash-alt"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editSubcategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Subcategory</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="edit-subcategory-id" name="subcategory_id">
                    <div class="mb-3">
                        <label class="form-label">Parent Category</label>
                        <select class="form-select" id="edit-category-id-sub" name="edit_category_id_sub" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" class="form-control" id="edit-sub-name" name="edit_sub_name" required></div>
                    <div class="mb-3"><label class="form-label">Fare</label><input type="number" step="0.01" class="form-control" id="edit-fare" name="edit_fare" min="0" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="edit-sub-description" name="edit_sub_description" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_subcategory" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editSubcategoryModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('edit-subcategory-id').value = button.getAttribute('data-id');
        document.getElementById('edit-category-id-sub').value = button.getAttribute('data-category-id');
        document.getElementById('edit-sub-name').value = button.getAttribute('data-name');
        document.getElementById('edit-fare').value = button.getAttribute('data-fare');
        document.getElementById('edit-sub-description').value = button.getAttribute('data-description');
    });
});
</script>