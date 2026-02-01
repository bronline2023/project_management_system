<?php
/**
 * admin/settings.php
 * This file allows the administrator to manage various application settings.
 * FINAL & COMPLETE: Includes all sections and submits to the central index.php action handler.
 * NEW: Added fields for appointment settings.
 */

$pdo = connectDB();
$message = '';

// Display message from session if redirected from an action
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$currentSettings = fetchOne($pdo, "SELECT * FROM settings WHERE id = 1 LIMIT 1");

// Extract working days for checkbox group
$working_days = explode(',', $currentSettings['office_working_days'] ?? '1,2,3,4,5,6');
$days_of_week = ['1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday', '7' => 'Sunday'];
?>

<h2 class="mb-4">Application Settings</h2>
<?php if (!empty($message)) { include VIEWS_PATH . 'components/message_box.php'; } ?>

<div class="card shadow-sm rounded-3">
    <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Settings</h5></div>
    <div class="card-body">
        <form action="index.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="page" value="settings">
            <input type="hidden" name="action" value="update_settings">

            <h5 class="mt-2">General Settings</h5>
            <div class="mb-3">
                <label for="app_name" class="form-label">Application Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="app_name" name="app_name" value="<?= htmlspecialchars($currentSettings['app_name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="app_logo" class="form-label">Upload New Logo</label>
                <input type="file" class="form-control" id="app_logo" name="app_logo" accept="image/*">
                 <?php if(!empty($currentSettings['app_logo_url'])): ?>
                    <img src="<?= htmlspecialchars($currentSettings['app_logo_url']) ?>" alt="Current Logo" class="mt-2" style="max-height: 50px; border-radius: 5px; background: #eee; padding: 5px;">
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="currency_symbol" class="form-label">Currency Symbol <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?= htmlspecialchars($currentSettings['currency_symbol'] ?? '') ?>" maxlength="5" required>
            </div>
            <div class="mb-3">
                <label for="office_address" class="form-label">Office Address</label>
                <textarea class="form-control" id="office_address" name="office_address" rows="2" placeholder="Enter your full office address"><?= htmlspecialchars($currentSettings['office_address'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label for="helpline_number" class="form-label">Helpline Number</label>
                <input type="text" class="form-control" id="helpline_number" name="helpline_number" value="<?= htmlspecialchars($currentSettings['helpline_number'] ?? '') ?>" placeholder="e.g., +91 98765 43210">
            </div>
            <hr>
             <h5 class="mt-4">Appointment Settings</h5>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="office_start_time" class="form-label">Office Start Time</label><input type="time" class="form-control" id="office_start_time" name="office_start_time" value="<?= htmlspecialchars($currentSettings['office_start_time'] ?? '10:00') ?>"></div>
                <div class="col-md-6 mb-3"><label for="office_end_time" class="form-label">Office End Time</label><input type="time" class="form-control" id="office_end_time" name="office_end_time" value="<?= htmlspecialchars($currentSettings['office_end_time'] ?? '18:00') ?>"></div>
            </div>
            <div class="mb-3"><label for="appointment_slot_duration" class="form-label">Appointment Slot Duration (in minutes)</label><input type="number" class="form-control" id="appointment_slot_duration" name="appointment_slot_duration" value="<?= htmlspecialchars($currentSettings['appointment_slot_duration'] ?? '30') ?>"></div>
             <div class="mb-3">
                <label class="form-label">Office Working Days</label>
                <div>
                    <?php foreach ($days_of_week as $num => $day): ?>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="office_working_days[]" value="<?= $num ?>" id="day_<?= $num ?>" <?= in_array($num, $working_days) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="day_<?= $num ?>"><?= $day ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <hr>
            <h5 class="mt-4">DEO & Withdrawal Settings</h5>
            <div class="mb-3">
                <label for="earning_per_approved_post" class="form-label">DEO Earning per Approved Post</label>
                <input type="number" step="0.01" class="form-control" id="earning_per_approved_post" name="earning_per_approved_post" value="<?= htmlspecialchars($currentSettings['earning_per_approved_post'] ?? '10.00') ?>" required min="0">
            </div>
            <div class="mb-3">
                <label for="minimum_withdrawal_amount" class="form-label">Minimum Withdrawal Amount</label>
                <input type="number" step="0.01" class="form-control" id="minimum_withdrawal_amount" name="minimum_withdrawal_amount" value="<?= htmlspecialchars($currentSettings['minimum_withdrawal_amount'] ?? '500.00') ?>" required min="0">
            </div>
            <hr>
            <h5 class="mt-4">WhatsApp API Settings (Meta Business)</h5>
            <div class="mb-3"><label for="whatsapp_phone_number_id" class="form-label">WhatsApp Business Phone Number ID</label><input type="text" class="form-control" id="whatsapp_phone_number_id" name="whatsapp_phone_number_id" value="<?= htmlspecialchars($currentSettings['whatsapp_business_number'] ?? '') ?>"></div>
            <div class="mb-3"><label for="whatsapp_access_token" class="form-label">WhatsApp Access Token</label><input type="text" class="form-control" id="whatsapp_access_token" name="whatsapp_access_token" value="<?= htmlspecialchars($currentSettings['whatsapp_api_key'] ?? '') ?>"></div>
            <hr>
            <h5 class="mt-4">SMTP Email Settings</h5>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="smtp_host" class="form-label">SMTP Host</label><input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($currentSettings['smtp_host'] ?? '') ?>"></div>
                <div class="col-md-3 mb-3"><label for="smtp_port" class="form-label">SMTP Port</label><input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($currentSettings['smtp_port'] ?? '587') ?>"></div>
                <div class="col-md-3 mb-3"><label for="smtp_encryption" class="form-label">Encryption</label><select class="form-select" id="smtp_encryption" name="smtp_encryption"><option value="tls" <?= ($currentSettings['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : '' ?>>TLS</option><option value="ssl" <?= ($currentSettings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL</option></select></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="smtp_username" class="form-label">SMTP Username</label><input type="email" class="form-control" id="smtp_username" name="smtp_username" value="<?= htmlspecialchars($currentSettings['smtp_username'] ?? '') ?>"></div>
                <div class="col-md-6 mb-3"><label for="smtp_password" class="form-label">SMTP Password</label><input type="password" class="form-control" id="smtp_password" placeholder="Leave blank to keep current password"></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="smtp_from_email" class="form-label">From Email Address</label><input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email" value="<?= htmlspecialchars($currentSettings['smtp_from_email'] ?? '') ?>"></div>
                <div class="col-md-6 mb-3"><label for="smtp_from_name" class="form-label">From Name</label><input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name" value="<?= htmlspecialchars($currentSettings['smtp_from_name'] ?? '') ?>"></div>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="fas fa-save me-2"></i>Save All Settings</button>
            </div>
        </form>
    </div>
</div>