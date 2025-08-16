<?php
/**
 * views/register.php
 *
 * This file provides a form for the administrator to register (create) new users.
 * It is not a public registration page.
 * It handles the form submission, validates input, hashes the password,
 * and inserts the new user into the database.
 *
 * It ensures that only authenticated admin users can access this page.
 */

// Include the configuration file, which also starts the session.
require_once __DIR__ . '/../config.php';
// Include authentication and database functions.
require_once MODELS_PATH . 'auth.php';
require_once MODELS_PATH . 'db.php';

$message = ''; // Variable to store success or error messages

// Restrict access to admin users only.
// If the user is not logged in or not an admin, redirect them.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB(); // Establish database connection

// Handle registration form submission
if (isset($_POST['register_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $message = '<div class="alert alert-danger" role="alert">Please fill in all required fields.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger" role="alert">Invalid email format.</div>';
    } elseif (strlen($password) < 6) { // Minimum password length
        $message = '<div class="alert alert-danger" role="alert">Password must be at least 6 characters long.</div>';
    } else {
        // Hash the password before storing
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => $hashed_password,
            'role' => $role
        ];

        try {
            $lastId = insert('users', $userData);
            if ($lastId) {
                $message = '<div class="alert alert-success" role="alert">User registered successfully!</div>';
                // Clear form fields after successful submission
                $_POST = array(); // This is a simple way to clear form on success
            } else {
                $message = '<div class="alert alert-danger" role="alert">Error registering user. Please try again.</div>';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // SQLSTATE for integrity constraint violation (e.g., duplicate email)
                $message = '<div class="alert alert-danger" role="alert">Error: User with this email already exists.</div>';
            } else {
                error_log("Error registering user: " . $e->getMessage());
                $message = '<div class="alert alert-danger" role="alert">An unexpected error occurred: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Include the header (contains HTML <head> and initial Bootstrap/CSS)
include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; // Include the sidebar for navigation ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Register New User (Admin Only)</h2>

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

        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>User Registration Form</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-pill" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control rounded-pill" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control rounded-pill" id="password" name="password" required>
                            <small class="form-text text-muted">Minimum 6 characters.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select rounded-pill" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                                <option value="manager" <?= (($_POST['role'] ?? '') === 'manager') ? 'selected' : '' ?>>Manager</option>
                                <option value="coordinator" <?= (($_POST['role'] ?? '') === 'coordinator') ? 'selected' : '' ?>>Coordinator</option>
                                <option value="sales" <?= (($_POST['role'] ?? '') === 'sales') ? 'selected' : '' ?>>Sales</option>
                                <option value="assistant" <?= (($_POST['role'] ?? '') === 'assistant') ? 'selected' : '' ?>>Assistant</option>
                                <option value="accountant" <?= (($_POST['role'] ?? '') === 'accountant') ? 'selected' : '' ?>>Accountant</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="register_user" class="btn btn-primary rounded-pill"><i class="fas fa-user-plus me-2"></i>Register User</button>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- Custom Confirmation Modal (re-used for consistency across system) -->
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
    // Custom confirm dialog function (re-used across files for consistency)
    function showCustomConfirm(title, message, link) {
        const confirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
        document.getElementById('customConfirmModalLabel').textContent = title;
        document.getElementById('confirm-message').textContent = message;
        document.getElementById('confirm-link').href = link;
        confirmModal.show();
    }
</script>

<style>
    /* Custom CSS for fade-out alert */
    .alert.fade-out {
        opacity: 0;
        transition: opacity 0.5s ease-out;
    }
</style>
