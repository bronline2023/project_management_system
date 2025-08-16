<?php
/**
 * views/login.php
 *
 * This file handles the user login functionality.
 * It displays the login form and processes login attempts.
 */

// Ensure configuration and authentication functions are available.
// config.php is usually loaded by index.php, which then includes models/auth.php
// and models/db.php. We need session_start() to be called very early.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Include config.php if not already included (e.g., direct access)
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}
require_once MODELS_PATH . 'auth.php'; // Authentication functions
require_once MODELS_PATH . 'db.php';   // Database connection for fetching settings

$message = ''; // Initialize message variable for displaying status messages

// Handle login form submission
if (isset($_POST['login_submit'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Attempt to log in the user using the function from auth.php
    if (loginUser($email, $password)) {
        // Login successful, redirect to appropriate dashboard based on role
        if (isset($_SESSION['user_role'])) {
            if ($_SESSION['user_role'] === 'admin') {
                header('Location: ' . BASE_URL . '?page=dashboard');
            } else {
                // For regular users (including manager, coordinator, etc.)
                header('Location: ' . BASE_URL . '?page=my_tasks');
            }
            exit;
        }
    } else {
        // Login failed, message is already set in $_SESSION['status_message'] by loginUser()
        // The message will be displayed below.
    }
}

// Check for status message in session after redirect or failed login attempt
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']); // Clear the message after displaying it once
}

// --- Fetch App Logo and Name from Settings ---
$app_logo_url = '';
$app_name_setting = APP_NAME; // Default value from config.php
try {
    $pdo = connectDB(); // Connect to database to fetch settings
    $stmt = $pdo->query("SELECT app_name, app_logo_url FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings) {
        $app_name_setting = htmlspecialchars($settings['app_name'] ?? APP_NAME);
        $app_logo_url = htmlspecialchars($settings['app_logo_url'] ?? '');
    }
} catch (PDOException $e) {
    error_log("Error fetching app settings for login page: " . $e->getMessage());
    // Continue with default values if database error
}


// Include the header (contains HTML <head> and initial Bootstrap/CSS)
include INCLUDES_PATH . 'header.php';
?>

<div class="d-flex justify-content-center align-items-center min-vh-100 bg-light">
    <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px; border-radius: 15px;">
        <div class="card-body">
            <div class="text-center mb-4">
                <?php if (!empty($app_logo_url)): ?>
                    <img src="<?= $app_logo_url ?>" alt="<?= $app_name_setting ?> Logo" class="company-logo mb-3">
                <?php else: ?>
                    <!-- Fallback icon if no logo URL is set -->
                    <i class="fas fa-project-diagram fa-4x text-primary mb-3"></i>
                <?php endif; ?>
                <h4 class="card-title text-center text-primary fw-bold"><?= $app_name_setting ?></h4>
                <p class="card-text text-center text-muted">D1 Arvind Mega Trade, Ahmedabad</p>
				<p class="card-text text-center text-muted">Help Line No. 9067090369</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert-container mb-3">
                    <?php include VIEWS_PATH . 'components/message_box.php'; // Custom message box ?>
                </div>
                <script>
                    // Auto-hide the message after 5 seconds using the global function
                    // This function is defined in footer.php now.
                    setupAutoHideAlerts();
                </script>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control rounded-pill" id="email" name="email" required autocomplete="email" placeholder="name@example.com">
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control rounded-pill" id="password" name="password" required autocomplete="current-password" placeholder="********">
                </div>
                <div class="d-grid">
                    <button type="submit" name="login_submit" class="btn btn-primary btn-lg rounded-pill">Login</button>
                </div>
            </form>
            <div class="text-center mt-4">
                <p class="text-muted">Don't have an account? Contact administrator.</p>
                <!-- <a href="<?= BASE_URL ?>?page=register" class="text-decoration-none">Register here</a> -->
            </div>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; // Include the footer ?>

<style>
    .company-logo {
        max-width: 170px; /* Adjust as needed */
        height: auto;
    }
</style>
