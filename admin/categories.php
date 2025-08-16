<?php
/**
 * admin/categories.php
 *
 * This file handles the management of categories and subcategories by the administrator.
 * It allows adding, editing, and deleting categories and their associated subcategories.
 * When a subcategory is added, a specified fee (fare) value can be assigned to it.
 *
 * It ensures that only authenticated admin users can access this page.
 */

// Include the configuration file for database connection and session management.
require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';   // Database interaction functions
require_once MODELS_PATH . 'auth.php'; // Authentication functions

// Restrict access to admin users only.
// If the user is not logged in or not an admin, redirect them.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB(); // Establish database connection
$message = ''; // To store success or error messages

// --- Handle Category Actions (Add, Edit, Delete) ---

// Handle Add Category
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    // Basic validation
    if (empty($name)) {
        $message = '<div class="alert alert-danger" role="alert">Category Name is required.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $message = '<div class="alert alert-success" role="alert">Category added successfully!</div>';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $message = '<div class="alert alert-danger" role="alert">Error: Category with this name already exists.</div>';
            } else {
                error_log("Error adding category: " . $e->getMessage());
                $message = '<div class="alert alert-danger" role="alert">Error adding category: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Handle Edit Category
if (isset($_POST['edit_category'])) {
    $id = $_POST['category_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    // Basic validation
    if (empty($id) || empty($name)) {
        $message = '<div class="alert alert-danger" role="alert">Category Name and ID are required for editing.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);
            $message = '<div class="alert alert-success" role="alert">Category updated successfully!</div>';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $message = '<div class="alert alert-danger" role="alert">Error: Category with this name already exists.</div>';
            } else {
                error_log("Error updating category: " . $e->getMessage());
                $message = '<div class="alert alert-danger" role="alert">Error updating category: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Handle Delete Category
if (isset($_GET['action']) && $_GET['action'] === 'delete_category' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        // Deleting a category will cascade delete its subcategories due to ON DELETE CASCADE
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $message = '<div class="alert alert-success" role="alert">Category and its subcategories deleted successfully!</div>';
    } catch (PDOException $e) {
        error_log("Error deleting category: " . $e->getMessage());
        $message = '<div class="alert alert-danger" role="alert">Error deleting category. Make sure no work assignments are associated with this category.</div>';
    }
}

// --- Handle Subcategory Actions (Add, Edit, Delete) ---

// Handle Add Subcategory
if (isset($_POST['add_subcategory'])) {
    $category_id = $_POST['category_id_sub'];
    $name = trim($_POST['sub_name']);
    $fare = floatval($_POST['fare']);
    $description = trim($_POST['sub_description']);

    // Basic validation
    if (empty($category_id) || empty($name) || !is_numeric($fare) || $fare < 0) {
        $message = '<div class="alert alert-danger" role="alert">Subcategory Name, Category, and a valid Fare are required.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO subcategories (category_id, name, fare, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$category_id, $name, $fare, $description]);
            $message = '<div class="alert alert-success" role="alert">Subcategory added successfully!</div>';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry (category_id, name) unique constraint
                $message = '<div class="alert alert-danger" role="alert">Error: Subcategory with this name already exists under the selected category.</div>';
            } else {
                error_log("Error adding subcategory: " . $e->getMessage());
                $message = '<div class="alert alert-danger" role="alert">Error adding subcategory: ' . $e->getMessage() . '</div>';
            }
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

    // Basic validation
    if (empty($id) || empty($category_id) || empty($name) || !is_numeric($fare) || $fare < 0) {
        $message = '<div class="alert alert-danger" role="alert">Subcategory Name, Category, Fare, and ID are required for editing.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE subcategories SET category_id = ?, name = ?, fare = ?, description = ? WHERE id = ?");
            $stmt->execute([$category_id, $name, $fare, $description, $id]);
            $message = '<div class="alert alert-success" role="alert">Subcategory updated successfully!</div>';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry (category_id, name) unique constraint
                $message = '<div class="alert alert-danger" role="alert">Error: Subcategory with this name already exists under the selected category.</div>';
            } else {
                error_log("Error updating subcategory: " . $e->getMessage());
                $message = '<div class="alert alert-danger" role="alert">Error updating subcategory: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Handle Delete Subcategory
if (isset($_GET['action']) && $_GET['action'] === 'delete_subcategory' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM subcategories WHERE id = ?");
        $stmt->execute([$id]);
        $message = '<div class="alert alert-success" role="alert">Subcategory deleted successfully!</div>';
    } catch (PDOException $e) {
        error_log("Error deleting subcategory: " . $e->getMessage());
        $message = '<div class="alert alert-danger" role="alert">Error deleting subcategory. Make sure no work assignments are associated with this subcategory.</div>';
    }
}

// --- Fetch All Categories and Subcategories for Display ---

$categories = [];
try {
    $stmt = $pdo->query("SELECT id, name, description FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading categories.</div>';
}

$subcategories = [];
try {
    $stmt = $pdo->query("SELECT s.id, s.name AS subcategory_name, s.fare, s.description AS subcategory_description,
                                c.name AS category_name, c.id AS category_id
                         FROM subcategories s
                         JOIN categories c ON s.category_id = c.id
                         ORDER BY c.name ASC, s.name ASC");
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching subcategories: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading subcategories.</div>';
}

// Get currency symbol from settings for display
$currencySymbol = '$'; // Default
try {
    $stmt = $pdo->query("SELECT currency_symbol FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings && isset($settings['currency_symbol'])) {
        $currencySymbol = htmlspecialchars($settings['currency_symbol']);
    }
} catch (PDOException $e) {
    error_log("Error fetching currency symbol: " . $e->getMessage());
}

// Include the header (contains HTML <head> and initial Bootstrap/CSS)
include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; // Include the sidebar for navigation ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Category & Subcategory Management</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; // Custom message box ?>
            <script>
                // Auto-hide the message after 5 seconds
                setTimeout(function() {
                    const alert = document.querySelector('.alert');
                    if (alert) {
                        alert.classList.add('fade-out');
                        setTimeout(() => alert.remove(), 500); // Remove after fade-out
                    }
                }, 5000);
            </script>
        <?php endif; ?>

        <!-- Add New Category Form -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-folder-plus me-2"></i>Add New Category</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
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
                    <button type="submit" name="add_category" class="btn btn-primary rounded-pill"><i class="fas fa-plus-circle me-2"></i>Add Category</button>
                </form>
            </div>
        </div>

        <!-- Category List -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-folder me-2"></i>Existing Categories</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($categories) > 0): ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($category['id']) ?></td>
                                        <td><?= htmlspecialchars($category['name']) ?></td>
                                        <td><?= htmlspecialchars($category['description']) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1"
                                                    data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                                    data-id="<?= htmlspecialchars($category['id']) ?>"
                                                    data-name="<?= htmlspecialchars($category['name']) ?>"
                                                    data-description="<?= htmlspecialchars($category['description']) ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill"
                                                    onclick="showCustomConfirm('Delete Category', 'Are you sure you want to delete category: <?= htmlspecialchars($category['name']) ?>? This will also delete all associated subcategories.', '<?= BASE_URL ?>?page=categories&action=delete_category&id=<?= htmlspecialchars($category['id']) ?>')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No categories found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add New Subcategory Form -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-info text-white">
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
                    <button type="submit" name="add_subcategory" class="btn btn-info text-white rounded-pill"><i class="fas fa-plus-circle me-2"></i>Add Subcategory</button>
                </form>
            </div>
        </div>

        <!-- Subcategory List -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Existing Subcategories</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Category</th>
                                <th>Subcategory Name</th>
                                <th>Fare</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($subcategories) > 0): ?>
                                <?php foreach ($subcategories as $sub): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($sub['id']) ?></td>
                                        <td><span class="badge bg-primary"><?= htmlspecialchars($sub['category_name']) ?></span></td>
                                        <td><?= htmlspecialchars($sub['subcategory_name']) ?></td>
                                        <td><?= $currencySymbol ?><?= number_format($sub['fare'], 2) ?></td>
                                        <td><?= htmlspecialchars($sub['subcategory_description']) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1"
                                                    data-bs-toggle="modal" data-bs-target="#editSubcategoryModal"
                                                    data-id="<?= htmlspecialchars($sub['id']) ?>"
                                                    data-category-id="<?= htmlspecialchars($sub['category_id']) ?>"
                                                    data-name="<?= htmlspecialchars($sub['subcategory_name']) ?>"
                                                    data-fare="<?= htmlspecialchars($sub['fare']) ?>"
                                                    data-description="<?= htmlspecialchars($sub['subcategory_description']) ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill"
                                                    onclick="showCustomConfirm('Delete Subcategory', 'Are you sure you want to delete subcategory: <?= htmlspecialchars($sub['subcategory_name']) ?>?', '<?= BASE_URL ?>?page=categories&action=delete_subcategory&id=<?= htmlspecialchars($sub['id']) ?>')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No subcategories found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="editCategoryModalLabel"><i class="fas fa-edit me-2"></i>Edit Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" id="edit-category-id" name="category_id">
                    <div class="mb-3">
                        <label for="edit-category-name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-pill" id="edit-category-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-category-description" class="form-label">Description</label>
                        <textarea class="form-control rounded-3" id="edit-category-description" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_category" class="btn btn-primary rounded-pill"><i class="fas fa-save me-2"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subcategory Modal -->
<div class="modal fade" id="editSubcategoryModal" tabindex="-1" aria-labelledby="editSubcategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-info text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="editSubcategoryModalLabel"><i class="fas fa-edit me-2"></i>Edit Subcategory</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" id="edit-subcategory-id" name="subcategory_id">
                    <div class="mb-3">
                        <label for="edit-category-id-sub" class="form-label">Parent Category <span class="text-danger">*</span></label>
                        <select class="form-select rounded-pill" id="edit-category-id-sub" name="edit_category_id_sub" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category['id']) ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit-sub-name" class="form-label">Subcategory Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-pill" id="edit-sub-name" name="edit_sub_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-fare" class="form-label">Fare / Fee (<?= $currencySymbol ?>) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control rounded-pill" id="edit-fare" name="edit_fare" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-sub-description" class="form-label">Description</label>
                        <textarea class="form-control rounded-3" id="edit-sub-description" name="edit_sub_description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_subcategory" class="btn btn-info text-white rounded-pill"><i class="fas fa-save me-2"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Custom Confirmation Modal (re-used across admin files) -->
<div class="modal fade" id="customConfirmModal" tabindex="-1" aria-labelledby="customConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="customConfirmModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirmation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p id="confirm-message" class="lead text-center"></p>
            </div>
            <div class="modal-footer border-0 rounded-bottom-4 justify-content-center">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirm-link" class="btn btn-danger rounded-pill">Confirm</a>
            </div>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; // Include the footer ?>

<script>
    // JavaScript to populate the edit category modal when the edit button is clicked
    document.addEventListener('DOMContentLoaded', function() {
        const editCategoryModal = document.getElementById('editCategoryModal');
        editCategoryModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const description = button.getAttribute('data-description');

            const modalTitle = editCategoryModal.querySelector('.modal-title');
            const modalBodyInputId = editCategoryModal.querySelector('#edit-category-id');
            const modalBodyInputName = editCategoryModal.querySelector('#edit-category-name');
            const modalBodyTextareaDescription = editCategoryModal.querySelector('#edit-category-description');

            modalTitle.textContent = 'Edit Category: ' + name;
            modalBodyInputId.value = id;
            modalBodyInputName.value = name;
            modalBodyTextareaDescription.value = description;
        });

        // JavaScript to populate the edit subcategory modal when the edit button is clicked
        const editSubcategoryModal = document.getElementById('editSubcategoryModal');
        editSubcategoryModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const id = button.getAttribute('data-id');
            const categoryId = button.getAttribute('data-category-id');
            const name = button.getAttribute('data-name');
            const fare = button.getAttribute('data-fare');
            const description = button.getAttribute('data-description');

            const modalTitle = editSubcategoryModal.querySelector('.modal-title');
            const modalBodyInputId = editSubcategoryModal.querySelector('#edit-subcategory-id');
            const modalBodySelectCategoryId = editSubcategoryModal.querySelector('#edit-category-id-sub');
            const modalBodyInputName = editSubcategoryModal.querySelector('#edit-sub-name');
            const modalBodyInputFare = editSubcategoryModal.querySelector('#edit-fare');
            const modalBodyTextareaDescription = editSubcategoryModal.querySelector('#edit-sub-description');

            modalTitle.textContent = 'Edit Subcategory: ' + name;
            modalBodyInputId.value = id;
            modalBodySelectCategoryId.value = categoryId;
            modalBodyInputName.value = name;
            modalBodyInputFare.value = fare;
            modalBodyTextareaDescription.value = description;
        });
    });

    // Custom confirm dialog function (re-used across admin files for consistency)
    function showCustomConfirm(title, message, link) {
        const confirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
        document.getElementById('customConfirmModalLabel').textContent = title;
        document.getElementById('confirm-message').textContent = message;
        document.getElementById('confirm-link').href = link;
        confirmModal.show();
    }
</script>

<style>
    /* Custom CSS for fade-out alert (re-used across admin files for consistency) */
    .alert.fade-out {
        opacity: 0;
        transition: opacity 0.5s ease-out;
    }
</style>
