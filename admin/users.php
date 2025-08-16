<?php
/**
 * admin/users.php
 *
 * This file allows the administrator to manage all system users.
 * It provides functionalities to view, edit, and delete users.
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

// --- Handle User Deletion via POST request ---
if (isset($_POST['action']) && $_POST['action'] === 'delete_user' && isset($_POST['id'])) {
    $userIdToDelete = (int)$_POST['id'];

    // Prevent admin from deleting their own account
    if ($userIdToDelete === $_SESSION['user_id']) {
        $message = '<div class="alert alert-danger" role="alert">You cannot delete your own account.</div>';
    } else {
        try {
            // Check if the user exists before attempting to delete
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$userIdToDelete]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt->execute([$userIdToDelete])) {
                    $message = '<div class="alert alert-success" role="alert">User deleted successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger" role="alert">Failed to delete user.</div>';
                }
            } else {
                $message = '<div class="alert alert-warning" role="alert">User not found.</div>';
            }
        } catch (PDOException $e) {
            error_log("Error deleting user: " . $e->getMessage());
            $message = '<div class="alert alert-danger" role="alert">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// --- Fetch All Users ---
$users = [];
try {
    // Fetch all users except the currently logged-in admin (optional, but common for security)
    // Or fetch all users and handle self-deletion prevention in UI/logic
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $message = '<div class="alert alert-danger" role="alert">Error loading users: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Manage System Users</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; ?>
            <script>
                setupAutoHideAlerts();
            </script>
        <?php endif; ?>

        <div class="card shadow-sm rounded-3">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i>All System Users</h5>
                <a href="<?= BASE_URL ?>?page=add_user" class="btn btn-success rounded-pill px-4">
                    <i class="fas fa-user-plus me-2"></i>Register New User
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($users)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Registered On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['id']) ?></td>
                                        <td><?= htmlspecialchars($user['name']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <!-- Display role with proper formatting -->
                                            <?= ucwords(str_replace('_', ' ', htmlspecialchars($user['role']))) ?>
                                        </td>
                                        <td><?= date('Y-m-d H:i', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>?page=edit_user&id=<?= htmlspecialchars($user['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill me-1" title="Edit User">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill"
                                                    onclick="showCustomConfirm('Delete User', 'Are you sure you want to delete user: <?= htmlspecialchars($user['name']) ?>?', '<?= BASE_URL ?>?page=users')"
                                                    data-action="delete_user" data-id="<?= htmlspecialchars($user['id']) ?>"
                                                    title="Delete User"
                                                    <?= ($user['id'] === $_SESSION['user_id']) ? 'disabled' : '' ?>>
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No users found in the system.</p>
                <?php endif; ?>
            </div>
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

<?php include INCLUDES_PATH . 'footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Custom confirm dialog function (re-used across files for consistency)
        function showCustomConfirm(title, message, link) {
            const confirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
            document.getElementById('customConfirmModalLabel').textContent = title;
            document.getElementById('confirm-message').textContent = message;
            document.getElementById('confirm-link').href = link;

            // Handle action buttons for delete
            const confirmButton = document.getElementById('confirm-link');
            const relatedButton = event.relatedTarget; // The button that triggered the confirm modal

            if (relatedButton) {
                const action = relatedButton.getAttribute('data-action');
                const userId = relatedButton.getAttribute('data-id');

                // Create a form dynamically to submit POST request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= BASE_URL ?>?page=users'; // Submit to the current page

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = action;
                form.appendChild(actionInput);

                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'id';
                userIdInput.value = userId;
                form.appendChild(userIdInput);

                // Replace the href of the confirm button with a click listener that submits the form
                confirmButton.onclick = function() {
                    document.body.appendChild(form);
                    form.submit();
                };
                confirmButton.removeAttribute('href'); // Remove href to prevent GET request
            } else {
                // Fallback for generic confirms if no specific action button triggered it
                confirmButton.onclick = null; // Clear any previous click handler
                confirmButton.setAttribute('href', link); // Re-add href for simple link confirms
            }

            confirmModal.show();
        }
        window.showCustomConfirm = showCustomConfirm; // Make it globally accessible

        // Auto-hide alert functionality
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
    });
</script>

<style>
    /* Custom CSS for fade-out alert */
    .alert.fade-out {
        opacity: 0;
        transition: opacity 0.5s ease-out;
    }
</style>
