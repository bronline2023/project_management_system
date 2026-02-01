<?php
/**
 * admin/clients.php
 *
 * This file handles the management of clients by the administrator.
 * It allows adding new clients, editing existing client details,
 * and deleting clients.
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

// --- Handle Client Actions (Add, Edit, Delete) ---

// Handle Add Client
if (isset($_POST['add_client'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email'] ?? ''); // Make optional
    $phone = trim($_POST['phone'] ?? ''); // Make optional
    $address = trim($_POST['address'] ?? ''); // Make optional
    $contact_person = trim($_POST['contact_person'] ?? ''); // New optional field
    $company = trim($_POST['company'] ?? ''); // New optional field

    // Basic validation: Client Name is still required
    if (empty($name)) {
        $message = '<div class="alert alert-danger" role="alert">Client Name is required.</div>';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger" role="alert">Invalid email format.</div>';
    } else {
        try {
            // Updated INSERT statement to include new columns
            $stmt = $pdo->prepare("INSERT INTO clients (name, email, phone, address, contact_person, company) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $address, $contact_person, $company]);
            $message = '<div class="alert alert-success" role="alert">Client added successfully!</div>';
            // Clear form fields after successful submission (optional, for UX)
            $_POST = array();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // SQLSTATE for integrity constraint violation (e.g., duplicate email)
                $message = '<div class="alert alert-danger" role="alert">Error: Client with this email already exists.</div>';
            } else {
                error_log("Error adding client: " . $e->getMessage());
                $message = '<div class="alert alert-danger" role="alert">Error adding client: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Handle Edit Client
if (isset($_POST['edit_client'])) {
    $id = $_POST['client_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email'] ?? ''); // Make optional
    $phone = trim($_POST['phone'] ?? ''); // Make optional
    $address = trim($_POST['address'] ?? ''); // Make optional
    $contact_person = trim($_POST['contact_person'] ?? ''); // New optional field
    $company = trim($_POST['company'] ?? ''); // New optional field

    // Basic validation: Client Name is still required for editing
    if (empty($id) || empty($name)) {
        $message = '<div class="alert alert-danger" role="alert">Client Name is required for editing.</div>';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger" role="alert">Invalid email format for editing.</div>';
    } else {
        try {
            // Updated UPDATE statement to include new columns
            $stmt = $pdo->prepare("UPDATE clients SET name = ?, email = ?, phone = ?, address = ?, contact_person = ?, company = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $address, $contact_person, $company, $id]);
            $message = '<div class="alert alert-success" role="alert">Client updated successfully!</div>';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = '<div class="alert alert-danger" role="alert">Error: Client with this email already exists.</div>';
            } else {
                error_log("Error updating client: " . $e->getMessage());
                $message = '<div class="alert alert-danger" role="alert">Error updating client: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Handle Delete Client
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        $message = '<div class="alert alert-success" role="alert">Client deleted successfully!</div>';
    } catch (PDOException $e) {
        error_log("Error deleting client: " . $e->getMessage());
        $message = '<div class="alert alert-danger" role="alert">Error deleting client. Make sure no work assignments are associated with this client.</div>';
    }
}

// --- Fetch All Clients for Display ---
$clients = [];
try {
    // Updated SELECT statement to include new columns
    $stmt = $pdo->query("SELECT id, name, email, phone, address, contact_person, company, created_at FROM clients ORDER BY name ASC");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching clients: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading clients.</div>';
}

// Include the header (contains HTML <head> and initial Bootstrap/CSS)
include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; // Include the sidebar for navigation ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Client Management</h2>

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

        <!-- Add New Client Form -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Add New Client</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Client Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-pill" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact_person" class="form-label">Contact Person</label>
                            <input type="text" class="form-control rounded-pill" id="contact_person" name="contact_person" value="<?= htmlspecialchars($_POST['contact_person'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control rounded-pill" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control rounded-pill" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="company" class="form-label">Company</label>
                            <input type="text" class="form-control rounded-pill" id="company" name="company" value="<?= htmlspecialchars($_POST['company'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control rounded-3" id="address" name="address" rows="2"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_client" class="btn btn-primary rounded-pill"><i class="fas fa-plus-circle me-2"></i>Add Client</button>
                </form>
            </div>
        </div>

        <!-- Client List -->
        <div class="card shadow-sm rounded-3">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Existing Clients</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Contact Person</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Company</th>
                                <th>Address</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($clients) > 0): ?>
                                <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($client['id']) ?></td>
                                        <td><?= htmlspecialchars($client['name']) ?></td>
                                        <td><?= htmlspecialchars($client['contact_person']) ?></td>
                                        <td><?= htmlspecialchars($client['email']) ?></td>
                                        <td><?= htmlspecialchars($client['phone']) ?></td>
                                        <td><?= htmlspecialchars($client['company']) ?></td>
                                        <td><?= htmlspecialchars($client['address']) ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($client['created_at'])) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1"
                                                    data-bs-toggle="modal" data-bs-target="#editClientModal"
                                                    data-id="<?= htmlspecialchars($client['id']) ?>"
                                                    data-name="<?= htmlspecialchars($client['name']) ?>"
                                                    data-contact-person="<?= htmlspecialchars($client['contact_person']) ?>"
                                                    data-email="<?= htmlspecialchars($client['email']) ?>"
                                                    data-phone="<?= htmlspecialchars($client['phone']) ?>"
                                                    data-company="<?= htmlspecialchars($client['company']) ?>"
                                                    data-address="<?= htmlspecialchars($client['address']) ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill"
                                                    onclick="showCustomConfirm('Delete Client', 'Are you sure you want to delete client: <?= htmlspecialchars($client['name']) ?>?', '<?= BASE_URL ?>?page=clients&action=delete&id=<?= htmlspecialchars($client['id']) ?>')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No clients found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Edit Client Modal -->
<div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="editClientModalLabel"><i class="fas fa-user-edit me-2"></i>Edit Client</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" id="edit-client-id" name="client_id">
                    <div class="mb-3">
                        <label for="edit-name" class="form-label">Client Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-pill" id="edit-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-contact-person" class="form-label">Contact Person</label>
                        <input type="text" class="form-control rounded-pill" id="edit-contact-person" name="contact_person">
                    </div>
                    <div class="mb-3">
                        <label for="edit-email" class="form-label">Email</label>
                        <input type="email" class="form-control rounded-pill" id="edit-email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="edit-phone" class="form-label">Phone</label>
                        <input type="text" class="form-control rounded-pill" id="edit-phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="edit-company" class="form-label">Company</label>
                        <input type="text" class="form-control rounded-pill" id="edit-company" name="company">
                    </div>
                    <div class="mb-3">
                        <label for="edit-address" class="form-label">Address</label>
                        <textarea class="form-control rounded-3" id="edit-address" name="address" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_client" class="btn btn-primary rounded-pill"><i class="fas fa-save me-2"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Custom Confirmation Modal (replaces alert/confirm) -->
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
    // JavaScript to populate the edit client modal when the edit button is clicked
    document.addEventListener('DOMContentLoaded', function() {
        const editClientModal = document.getElementById('editClientModal');
        editClientModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const contactPerson = button.getAttribute('data-contact-person'); // New
            const email = button.getAttribute('data-email');
            const phone = button.getAttribute('data-phone');
            const company = button.getAttribute('data-company'); // New
            const address = button.getAttribute('data-address');

            const modalTitle = editClientModal.querySelector('.modal-title');
            const modalBodyInputId = editClientModal.querySelector('#edit-client-id');
            const modalBodyInputName = editClientModal.querySelector('#edit-name');
            const modalBodyInputContactPerson = editClientModal.querySelector('#edit-contact-person'); // New
            const modalBodyInputEmail = editClientModal.querySelector('#edit-email');
            const modalBodyInputPhone = editClientModal.querySelector('#edit-phone');
            const modalBodyInputCompany = editClientModal.querySelector('#edit-company'); // New
            const modalBodyTextareaAddress = editClientModal.querySelector('#edit-address');

            modalTitle.textContent = 'Edit Client: ' + name;
            modalBodyInputId.value = id;
            modalBodyInputName.value = name;
            modalBodyInputContactPerson.value = contactPerson; // Set value
            modalBodyInputEmail.value = email;
            modalBodyInputPhone.value = phone;
            modalBodyInputCompany.value = company; // Set value
            modalBodyTextareaAddress.value = address;
        });
    });

    // Custom confirm dialog function (re-used from users.php, ensuring consistency)
    function showCustomConfirm(title, message, link) {
        const confirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
        document.getElementById('customConfirmModalLabel').textContent = title;
        document.getElementById('confirm-message').textContent = message;
        document.getElementById('confirm-link').href = link;
        confirmModal.show();
    }
</script>

<style>
    /* Custom CSS for fade-out alert (re-used from users.php, ensuring consistency) */
    .alert.fade-out {
        opacity: 0;
        transition: opacity 0.5s ease-out;
    }
</style>
