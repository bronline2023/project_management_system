<?php
/**
 * admin/edit_user.php
 *
 * This file allows the administrator to view and edit details of an existing user.
 * It includes updating user name, email, and role. Password can also be reset.
 *
 * It ensures that only authenticated admin users can access this page.
 */

// Include the configuration file for database connection and session management.
require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';   // Database interaction functions
require_once MODELS_PATH . 'auth.php'; // Authentication functions (contains hashPassword)

// Restrict access to admin users only.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB(); // Establish database connection
$message = ''; // To store success or error messages
$user = null; // To store user details for display

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId === 0) {
    $message = '<div class="alert alert-danger" role="alert">No user ID provided.</div>';
} else {
    // --- Fetch User Details ---
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $message = '<div class="alert alert-danger" role="alert">User not found.</div>';
        }
    } catch (PDOException $e) {
        error_log("Error fetching user details for edit: " . $e->getMessage());
        $message = '<div class="alert alert-danger" role="alert">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// --- Handle Form Submission for User Update ---
if (isset($_POST['edit_user_submit'])) {
    $user_id = (int)$_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password']; // New password, optional

    if (empty($name) || empty($email) || empty($role)) {
        $message = '<div class="alert alert-danger" role="alert">Name, Email, and Role are required.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger" role="alert">Invalid email format.</div>';
    } else {
        try {
            // Check if email already exists for another user (excluding current user)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetchColumn() > 0) {
                $message = '<div class="alert alert-danger" role="alert">Email already registered for another user.</div>';
            } else {
                $sql = "UPDATE users SET name = ?, email = ?, role = ?";
                $params = [$name, $email, $role];

                if (!empty($password)) {
                    $hashedPassword = hashPassword($password); // Hash the password using function from auth.php
                    $sql .= ", password_hash = ?";
                    $params[] = $hashedPassword;
                }
                $sql .= " WHERE id = ?";
                $params[] = $user_id;

                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($params)) {
                    $message = '<div class="alert alert-success" role="alert">User updated successfully!</div>';
                    // Re-fetch user data to reflect changes immediately
                    $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    // If the current logged-in user's role was changed, update session
                    // This is crucial to reflect role changes immediately for the admin themselves
                    if ($user_id === $_SESSION['user_id']) {
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_role'] = $role;
                    }

                } else {
                    $message = '<div class="alert alert-danger" role="alert">Failed to update user.</div>';
                }
            }
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            $message = '<div class="alert alert-danger" role="alert">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}


include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Edit User</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; ?>
            <script>
                setupAutoHideAlerts();
            </script>
        <?php endif; ?>

        <?php if ($user): ?>
            <!-- DEBUG: Current User Role after fetch -->
            <p style="color: blue; font-weight: bold;">DEBUG: Fetched User Role: <?= htmlspecialchars($user['role'] ?? 'N/A') ?></p>
            <!-- END DEBUG -->

            <div class="card shadow-sm rounded-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit User: <?= htmlspecialchars($user['name']) ?></h5>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">

                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-pill" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control rounded-pill" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select rounded-pill" id="role" name="role" required autocomplete="off"> <!-- Added autocomplete="off" -->
                                <option value="admin" <?= ($user['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                                <option value="manager" <?= ($user['role'] === 'manager') ? 'selected' : '' ?>>Manager</option>
                                <option value="coordinator" <?= ($user['role'] === 'coordinator') ? 'selected' : '' ?>>Coordinator</option>
                                <option value="sales" <?= ($user['role'] === 'sales') ? 'selected' : '' ?>>Sales</option>
                                <option value="assistant" <?= ($user['role'] === 'assistant') ? 'selected' : '' ?>>Assistant</option>
                                <option value="accountant" <?= ($user['role'] === 'accountant') ? 'selected' : '' ?>>Accountant</option>
                                <option value="data_entry_operator" <?= ($user['role'] === 'data_entry_operator') ? 'selected' : '' ?>>Data Entry Operator</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control rounded-pill" id="password" name="password" placeholder="********">
                            <small class="form-text text-muted">Enter a new password only if you want to change it.</small>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" name="edit_user_submit" class="btn btn-primary rounded-pill px-4">
                                <i class="fas fa-save me-2"></i>Update User
                            </button>
                            <a href="<?= BASE_URL ?>?page=users" class="btn btn-secondary rounded-pill px-4">
                                <i class="fas fa-times-circle me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                User details could not be loaded. Please ensure the user ID is valid.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alert functionality (re-used from users.php, ensuring consistency)
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
    /* Custom CSS for fade-out alert (re-used from users.php, ensuring consistency) */
    .alert.fade-out {
        opacity: 0;
        transition: opacity 0.5s ease-out;
    }
</style>
