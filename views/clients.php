<?php
/**
 * views/clients.php
 *
 * This unified file handles client management for all authorized roles (Admin, Manager, Assistant).
 * It allows viewing, adding, and editing clients.
 * Deletion is restricted to Admin users only.
 */

// Path to config.php: from views/clients.php, it's ../config.php
require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';

// Restrict access to logged-in users only
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? 'guest';
$message = '';
$client = null; // Used to pre-populate edit client form

// Define roles allowed to perform certain actions
$allowedToAddClients = ['admin', 'manager', 'assistant'];
$allowedToEditClients = ['admin', 'manager', 'assistant'];
$allowedToDeleteClients = ['admin']; // Only admin can delete

// Determine the current action based on $_GET['page']
$currentPage = $_GET['page'] ?? 'clients'; // Default to 'clients' for listing
$clientId = $_GET['id'] ?? null; // For edit/delete actions


// Handle messages from redirects (e.g., after add/update/delete success)
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'add_success':
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">Client added successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
            break;
        case 'update_success':
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">Client updated successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
            break;
        case 'delete_success':
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">Client deleted successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
            break;
        case 'not_authorized':
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">You are not authorized to perform this action.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
            break;
    }
}


// --- Handle Form Submissions (Add/Update/Delete) ---

// Handle Add Client Form Submission
if (isset($_POST['add_client'])) {
    if (in_array($userRole, $allowedToAddClients)) {
        $clientName = trim($_POST['client_name'] ?? '');
        $contactPerson = trim($_POST['contact_person'] ?? ''); // Now optional
        $email = trim($_POST['email'] ?? ''); // Now optional
        $phone = trim($_POST['phone'] ?? ''); // Now optional
        $company = trim($_POST['company'] ?? ''); // Now optional
        $address = trim($_POST['address'] ?? ''); // Now optional
        $status = $_POST['status'] ?? 'Active';

        // Only clientName is strictly required
        if (empty($clientName)) {
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Client Name is required.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Email is optional, but if provided, it must be a valid format
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Please enter a valid email address.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } else {
            try {
                // Check for duplicate client name (optional, but good practice if names should be unique)
                $checkNameStmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE client_name = :client_name");
                $checkNameStmt->bindParam(':client_name', $clientName, PDO::PARAM_STR);
                $checkNameStmt->execute();
                if ($checkNameStmt->fetchColumn() > 0) {
                    $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">A client with this name already exists.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO clients (client_name, contact_person, email, phone, company, address, status, created_at) VALUES (:client_name, :contact_person, :email, :phone, :company, :address, :status, NOW())");
                    $stmt->bindParam(':client_name', $clientName, PDO::PARAM_STR);
                    $stmt->bindParam(':contact_person', $contactPerson, PDO::PARAM_STR);
                    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                    $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
                    $stmt->bindParam(':company', $company, PDO::PARAM_STR);
                    $stmt->bindParam(':address', $address, PDO::PARAM_STR);
                    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                    $stmt->execute();

                    if ($stmt->rowCount()) {
                        header('Location: ' . BASE_URL . '?page=clients&msg=add_success');
                        exit;
                    } else {
                        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Failed to add client. Please try again.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>';
                    }
                }
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Database error: ' . htmlspecialchars($e->getMessage()) . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
                error_log("Error adding client: " . $e->getMessage());
            }
        }
    } else {
        header('Location: ' . BASE_URL . '?page=clients&msg=not_authorized');
        exit;
    }
}


// Handle Update Client Form Submission
if (isset($_POST['update_client'])) {
    if (in_array($userRole, $allowedToEditClients)) {
        $clientId = $_POST['client_id'] ?? null;
        $clientName = trim($_POST['client_name'] ?? '');
        $contactPerson = trim($_POST['contact_person'] ?? ''); // Now optional
        $email = trim($_POST['email'] ?? ''); // Now optional
        $phone = trim($_POST['phone'] ?? ''); // Now optional
        $company = trim($_POST['company'] ?? ''); // Now optional
        $address = trim($_POST['address'] ?? ''); // Now optional
        $status = $_POST['status'] ?? 'Active';

        if (!$clientId) {
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Client ID is missing for update.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } elseif (empty($clientName)) { // Only clientName is strictly required for update
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Client Name is required for update.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Please enter a valid email address for update.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } else {
            try {
                // Check for duplicate client name, excluding the current client being updated
                $checkNameStmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE client_name = :client_name AND id != :currentClientId");
                $checkNameStmt->bindParam(':client_name', $clientName, PDO::PARAM_STR);
                $checkNameStmt->bindParam(':currentClientId', $clientId, PDO::PARAM_INT);
                $checkNameStmt->execute();
                if ($checkNameStmt->fetchColumn() > 0) {
                    $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">A client with this name already exists.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>';
                } else {
                    $stmt = $pdo->prepare("UPDATE clients SET client_name = :client_name, contact_person = :contact_person, email = :email, phone = :phone, company = :company, address = :address, status = :status WHERE id = :id");
                    $stmt->bindParam(':client_name', $clientName, PDO::PARAM_STR);
                    $stmt->bindParam(':contact_person', $contactPerson, PDO::PARAM_STR);
                    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                    $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
                    $stmt->bindParam(':company', $company, PDO::PARAM_STR);
                    $stmt->bindParam(':address', $address, PDO::PARAM_STR);
                    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                    $stmt->bindParam(':id', $clientId, PDO::PARAM_INT);
                    $stmt->execute();

                    if ($stmt->rowCount()) {
                        header('Location: ' . BASE_URL . '?page=clients&msg=update_success');
                        exit;
                    } else {
                        $message = '<div class="alert alert-info alert-dismissible fade show" role="alert">No changes detected or update failed.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>';
                    }
                }
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Database error: ' . htmlspecialchars($e->getMessage()) . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
                error_log("Error updating client ID " . $clientId . ": " . $e->getMessage());
            }
        }
    } else {
        header('Location: ' . BASE_URL . '?page=clients&msg=not_authorized');
        exit;
    }
}

// Handle Delete Client (via GET request from confirmation)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (in_array($userRole, $allowedToDeleteClients)) {
        $clientIdToDelete = $_GET['id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM clients WHERE id = :id");
            $stmt->bindParam(':id', $clientIdToDelete, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount()) {
                header('Location: ' . BASE_URL . '?page=clients&msg=delete_success');
                exit;
            } else {
                $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">Client not found or already deleted.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
                error_log("Attempted to delete non-existent client ID " . $clientIdToDelete . " by User ID: " . $currentUserId);
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger" role="alert">Error deleting client: ' . htmlspecialchars($e->getMessage()) . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
            error_log("PDOException deleting client ID " . $clientIdToDelete . ": " . $e->getMessage());
        }
    } else {
        header('Location: ' . BASE_URL . '?page=clients&msg=not_authorized');
        exit;
    }
}


// --- Logic to load client data for edit form (if in edit mode) ---
if ($currentPage === 'edit_client' && $clientId) {
    if (in_array($userRole, $allowedToEditClients)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = :id");
            $stmt->bindParam(':id', $clientId, PDO::PARAM_INT);
            $stmt->execute();
            $client = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$client) {
                // If client not found, revert to clients list view and show error
                $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">Client not found for editing.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
                $currentPage = 'clients'; // Redirect to list view
                // We will rely on the redirect message for 'not_authorized' or the error message above
            }
        } catch (PDOException $e) {
            error_log("Error fetching client for edit: " . $e->getMessage());
            $message = '<div class="alert alert-danger" role="alert">Database error fetching client: ' . htmlspecialchars($e->getMessage()) . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
            $currentPage = 'clients'; // Revert to list view on error
        }
    } else {
        // If not authorized for edit, redirect immediately
        header('Location: ' . BASE_URL . '?page=clients&msg=not_authorized');
        exit;
    }
}

// --- Fetch All Clients for Display (always needed for the list view) ---
$clientsData = [];
// This block will execute regardless of whether it's add/edit/list view,
// because the list needs to be available in the 'clients' mode.
try {
    $sql = "SELECT id, client_name, contact_person, email, phone, company, address, status, created_at FROM clients ORDER BY client_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $clientsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching clients for display: " . $e->getMessage());
    $message = '<div class="alert alert-danger" role="alert">Error loading clients: ' . htmlspecialchars($e->getMessage()) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
}


include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex flex-grow-1">
    <?php include INCLUDES_PATH . 'sidebar.php'; ?>
    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <?php if (!empty($message)): echo $message; endif; ?>

        <?php
        // Render Add/Edit Form if current page action is 'add_client' or 'edit_client'
        // AND the user role is allowed for that action.
        if (($currentPage === 'add_client' && in_array($userRole, $allowedToAddClients)) ||
            ($currentPage === 'edit_client' && $client && in_array($userRole, $allowedToEditClients))):
        ?>
            <!-- Unified Add/Edit Client Form -->
            <h2 class="mb-4"><?= ($currentPage === 'add_client') ? 'Add New Client' : 'Edit Client: ' . htmlspecialchars($client['client_name'] ?? 'N/A') ?></h2>
            <div class="card shadow rounded-3">
                <div class="card-header bg-primary text-white rounded-top-3">
                    <h5 class="mb-0">Client Details</h5>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>?page=<?= ($currentPage === 'add_client') ? 'add_client' : 'edit_client&id=' . htmlspecialchars($client['id']) ?>" method="POST">
                        <?php if ($currentPage === 'edit_client'): ?>
                            <input type="hidden" name="client_id" value="<?= htmlspecialchars($client['id']) ?>">
                        <?php endif; ?>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="client_name" class="form-label">Client Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-pill" id="client_name" name="client_name" value="<?= htmlspecialchars($client['client_name'] ?? $_POST['client_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="contact_person" class="form-label">Contact Person</label>
                                <input type="text" class="form-control rounded-pill" id="contact_person" name="contact_person" value="<?= htmlspecialchars($client['contact_person'] ?? $_POST['contact_person'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control rounded-pill" id="email" name="email" value="<?= htmlspecialchars($client['email'] ?? $_POST['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control rounded-pill" id="phone" name="phone" value="<?= htmlspecialchars($client['phone'] ?? $_POST['phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="company" class="form-label">Company</label>
                                <input type="text" class="form-control rounded-pill" id="company" name="company" value="<?= htmlspecialchars($client['company'] ?? $_POST['company'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control rounded-4" id="address" name="address" rows="3"><?= htmlspecialchars($client['address'] ?? $_POST['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select rounded-pill" id="status" name="status">
                                    <option value="Active" <?= (($client['status'] ?? $_POST['status'] ?? '') == 'Active') ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= (($client['status'] ?? $_POST['status'] ?? '') == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="<?= ($currentPage === 'add_client') ? 'add_client' : 'update_client' ?>" class="btn btn-primary rounded-pill px-4">
                                <i class="fas <?= ($currentPage === 'add_client') ? 'fa-plus-circle' : 'fa-save' ?> me-2"></i><?= ($currentPage === 'add_client') ? 'Add Client' : 'Update Client' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: // Default: Show Clients List ?>
            <h2 class="mb-4">Client Management</h2>

            <div class="card shadow rounded-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center rounded-top-3">
                    <h5 class="mb-0">All Clients</h5>
                    <?php if (in_array($userRole, $allowedToAddClients)): ?>
                        <a href="<?= BASE_URL ?>?page=add_client" class="btn btn-light btn-sm rounded-pill">
                            <i class="fas fa-plus-circle me-2"></i>Add New Client
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <!-- Search Bar -->
                    <div class="mb-3">
                        <input type="text" id="clientSearchInput" class="form-control rounded-pill" placeholder="Search clients...">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped" id="clientsTable">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Client Name</th>
                                    <th scope="col">Contact Person</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Phone</th>
                                    <th scope="col">Company</th>
                                    <th scope="col">Address</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Created At</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($clientsData)): ?>
                                    <?php foreach ($clientsData as $clientItem): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($clientItem['id'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($clientItem['client_name'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($clientItem['contact_person'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($clientItem['email'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($clientItem['phone'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($clientItem['company'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($clientItem['address'] ?? '') ?></td>
                                            <td>
                                                <span class="badge <?= ($clientItem['status'] ?? '') === 'Active' ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= htmlspecialchars($clientItem['status'] ?? '') ?>
                                                </span>
                                            </td>
                                            <td><?= date('Y-m-d H:i', strtotime($clientItem['created_at'] ?? '')) ?></td>
                                            <td>
                                                <div class="d-flex flex-nowrap">
                                                    <?php if (in_array($userRole, $allowedToEditClients)): ?>
                                                    <a href="<?= BASE_URL ?>?page=edit_client&id=<?= htmlspecialchars($clientItem['id'] ?? '') ?>" class="btn btn-sm btn-info me-2 rounded-pill" title="Edit Client">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php endif; ?>

                                                    <?php if (in_array($userRole, $allowedToDeleteClients)): ?>
                                                    <button type="button" class="btn btn-sm btn-danger rounded-pill"
                                                            onclick="showCustomConfirm('Delete Client', 'Are you sure you want to delete client: <?= htmlspecialchars($clientItem['client_name'] ?? '') ?>?', '<?= BASE_URL ?>?page=clients&action=delete&id=<?= htmlspecialchars($clientItem['id'] ?? '') ?>')"
                                                            title="Delete Client">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center">No clients found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Custom Confirmation Modal (replaces alert/confirm) - This can be in footer.php if global -->
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

<?php include INCLUDES_PATH . 'footer.php'; ?>

<script>
    // Custom confirm dialog function (re-used from users.php, ensuring consistency)
    function showCustomConfirm(title, message, link) {
        const confirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
        document.getElementById('customConfirmModalLabel').textContent = title;
        document.getElementById('confirm-message').textContent = message;
        document.getElementById('confirm-link').href = link;
        confirmModal.show();
    }

    // Auto-hide alert functionality (re-using from footer.php if available, otherwise defining here)
    document.addEventListener('DOMContentLoaded', function() {
        const alertElement = document.querySelector('.alert.fade.show');
        if (alertElement) {
            setTimeout(function() {
                const bootstrapAlert = bootstrap.Alert.getInstance(alertElement);
                if (bootstrapAlert) {
                    bootstrapAlert.close();
                } else {
                    alertElement.classList.add('fade-out');
                    setTimeout(() => alertElement.remove(), 500);
                }
            }, 5000); // 5 seconds
        }

        // --- Client Search Functionality ---
        const clientSearchInput = document.getElementById('clientSearchInput');
        const clientsTable = document.getElementById('clientsTable');

        if (clientSearchInput && clientsTable) {
            clientSearchInput.addEventListener('keyup', function() {
                const searchTerm = clientSearchInput.value.toLowerCase();
                const rows = clientsTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

                for (let i = 0; i < rows.length; i++) {
                    let rowVisible = false;
                    const cells = rows[i].getElementsByTagName('td');
                    // Skip the last cell which is 'Actions'
                    for (let j = 0; j < cells.length - 1; j++) {
                        const cellText = cells[j].textContent || cells[j].innerText;
                        if (cellText.toLowerCase().indexOf(searchTerm) > -1) {
                            rowVisible = true;
                            break;
                        }
                    }
                    rows[i].style.display = rowVisible ? '' : 'none';
                }
            });
        }
    });
</script>

