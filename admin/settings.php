<?php
/**
 * admin/settings.php
 *
 * This file allows the administrator to manage various application settings,
 * such as the application name, logo, currency symbol, and now, the DEO earning rate.
 *
 * It ensures that only authenticated admin users can access this page.
 */

require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';
require_once MODELS_PATH . 'recruitment/recruitment_post.php'; // Include recruitment model for earning function

// Restrict access to admin users only.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB();
$message = '';

// --- Handle Form Submission for Settings Update ---
if (isset($_POST['update_settings'])) {
    $app_name = trim($_POST['app_name'] ?? '');
    $app_logo_url = trim($_POST['app_logo_url'] ?? '');
    $currency_symbol = trim($_POST['currency_symbol'] ?? '');
    $earning_per_approved_post = floatval($_POST['earning_per_approved_post'] ?? 0);

    if (empty($app_name) || empty($currency_symbol)) {
        $message = '<div class="alert alert-danger" role="alert">Application Name and Currency Symbol are required.</div>';
    } elseif ($earning_per_approved_post < 0) {
        $message = '<div class="alert alert-danger" role="alert">Earning per approved post cannot be negative.</div>';
    } else {
        try {
            // Update settings in the database
            $stmt = $pdo->prepare("UPDATE settings SET app_name = ?, app_logo_url = ?, currency_symbol = ?, earning_per_approved_post = ? WHERE id = 1"); // Assuming ID 1 for main settings
            $stmt->execute([$app_name, $app_logo_url, $currency_symbol, $earning_per_approved_post]);

            if ($stmt->rowCount()) {
                $message = '<div class="alert alert-success" role="alert">Settings updated successfully!</div>';
                // Reload constants if necessary, or just rely on next page load
                // For CURRENCY_SYMBOL, it's defined in config.php on each load.
            } else {
                $message = '<div class="alert alert-info" role="alert">No changes were made or settings row not found.</div>';
            }
        } catch (PDOException $e) {
            error_log("Error updating settings: " . $e->getMessage());
            $message = '<div class="alert alert-danger" role="alert">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// --- Fetch Current Settings ---
$currentSettings = [];
try {
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1 LIMIT 1");
    $currentSettings = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$currentSettings) {
        // If no settings exist, insert a default row
        $pdo->exec("INSERT INTO settings (id, app_name, app_logo_url, currency_symbol, earning_per_approved_post) VALUES (1, 'Project Management System', '', '₹', 10.00)");
        $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1 LIMIT 1");
        $currentSettings = $stmt->fetch(PDO::FETCH_ASSOC);
        $message = '<div class="alert alert-info" role="alert">Default settings initialized.</div>';
    }
} catch (PDOException $e) {
    error_log("Error fetching settings: " . $e->getMessage());
    $message = '<div class="alert alert-danger" role="alert">Error loading settings: ' . htmlspecialchars($e->getMessage()) . '</div>';
    // Fallback to default values if database error
    $currentSettings = [
        'app_name' => 'Project Management System',
        'app_logo_url' => '',
        'currency_symbol' => '₹',
        'earning_per_approved_post' => 10.00
    ];
}

// Include header and sidebar
include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Application Settings</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; ?>
            <script>
                setupAutoHideAlerts();
            </script>
        <?php endif; ?>

        <div class="card shadow-sm rounded-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>General Settings</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="app_name" class="form-label">Application Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-pill" id="app_name" name="app_name" value="<?= htmlspecialchars($currentSettings['app_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="app_logo_url" class="form-label">Application Logo URL</label>
                        <input type="url" class="form-control rounded-pill" id="app_logo_url" name="app_logo_url" value="<?= htmlspecialchars($currentSettings['app_logo_url'] ?? '') ?>" placeholder="e.g., https://example.com/logo.png">
                        <?php if (!empty($currentSettings['app_logo_url'])): ?>
                            <small class="form-text text-muted mt-2 d-block">Current Logo Preview:</small>
                            <img src="<?= htmlspecialchars($currentSettings['app_logo_url']) ?>" alt="Current Logo" style="max-height: 80px; margin-top: 5px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="currency_symbol" class="form-label">Currency Symbol <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-pill" id="currency_symbol" name="currency_symbol" value="<?= htmlspecialchars($currentSettings['currency_symbol'] ?? '') ?>" maxlength="5" required>
                        <small class="form-text text-muted">e.g., $, €, £, ₹</small>
                    </div>
                    <div class="mb-3">
                        <label for="earning_per_approved_post" class="form-label">DEO Earning per Approved Recruitment Post (<?= htmlspecialchars($currentSettings['currency_symbol'] ?? '₹') ?>) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control rounded-pill" id="earning_per_approved_post" name="earning_per_approved_post" value="<?= htmlspecialchars($currentSettings['earning_per_approved_post'] ?? '10.00') ?>" required min="0">
                        <small class="form-text text-muted">This is the amount a Data Entry Operator earns for each recruitment post approved by an admin.</small>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" name="update_settings" class="btn btn-primary rounded-pill px-4">
                            <i class="fas fa-save me-2"></i>Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
