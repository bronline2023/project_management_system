<?php
/**
 * admin/hr_settings.php
 * Allows HR and Admin to manage HR-specific settings like required work hours.
 */

$pdo = connectDB();
$message = '';

// The action is now handled in app/actions.php
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$currentSettings = fetchOne($pdo, "SELECT required_daily_hours FROM settings WHERE id = 1 LIMIT 1");
?>

<h2 class="mb-4">HR Settings</h2>
<?php if (!empty($message)) { include VIEWS_PATH . 'components/message_box.php'; } ?>

<div class="card shadow-sm rounded-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Work Hours Configuration</h5>
    </div>
    <div class="card-body">
        <form action="index.php" method="POST">
            <input type="hidden" name="page" value="hr_settings">
            <input type="hidden" name="action" value="update_hr_settings">
            <div class="mb-3">
                <label for="required_daily_hours" class="form-label">Required Daily Work Hours</label>
                <input type="number" step="0.5" class="form-control" id="required_daily_hours" name="required_daily_hours" value="<?= htmlspecialchars($currentSettings['required_daily_hours'] ?? '8') ?>" required>
                <div class="form-text">
                    This value is used to calculate the per-hour salary rate for employees. For example: 8, 8.5, etc.
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Save Settings
            </button>
        </form>
    </div>
</div>