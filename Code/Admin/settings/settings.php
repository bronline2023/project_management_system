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

// Fetch digital services for ad control
$services = fetchAll($pdo, "SELECT service_slug, service_name FROM digital_service_rates ORDER BY service_name ASC");
$ads_enabled_services = json_decode($currentSettings['ads_enabled_services'] ?? '[]', true);
if (!is_array($ads_enabled_services)) $ads_enabled_services = [];
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
                <label for="app_logo" class="form-label">Upload New Admin Portal App Logo</label>
                <input type="file" class="form-control" id="app_logo" name="app_logo" accept="image/*">
                 <?php if(!empty($currentSettings['app_logo_url'])): ?>
                    <img src="<?= htmlspecialchars($currentSettings['app_logo_url']) ?>" alt="Current Admin Logo" class="mt-2" style="max-height: 50px; border-radius: 5px; background: #eee; padding: 5px;">
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
            <h5 class="mt-4 text-success"><i class="fas fa-university me-2"></i>Manual Recharge (Bank & UPI) Settings</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Company Bank Name</label>
                    <input type="text" class="form-control" name="manual_bank_name" value="<?= htmlspecialchars($currentSettings['manual_bank_name'] ?? '') ?>" placeholder="e.g. HDFC Bank">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Account Number</label>
                    <input type="text" class="form-control" name="manual_account_number" value="<?= htmlspecialchars($currentSettings['manual_account_number'] ?? '') ?>" placeholder="Enter account number">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">IFSC Code</label>
                    <input type="text" class="form-control" name="manual_ifsc_code" value="<?= htmlspecialchars($currentSettings['manual_ifsc_code'] ?? '') ?>" placeholder="IFSC Code">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">MICR Code (Optional)</label>
                    <input type="text" class="form-control" name="manual_micr_code" value="<?= htmlspecialchars($currentSettings['manual_micr_code'] ?? '') ?>" placeholder="MICR Code">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">UPI Display Name</label>
                    <input type="text" class="form-control" name="manual_upi_name" value="<?= htmlspecialchars($currentSettings['manual_upi_name'] ?? '') ?>" placeholder="e.g. BR Online Services">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">UPI ID (VPA)</label>
                    <input type="text" class="form-control" name="manual_upi_id" value="<?= htmlspecialchars($currentSettings['manual_upi_id'] ?? '') ?>" placeholder="e.g. yourname@upi">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Payment QR Code (Scan & Pay)</label>
                    <input type="file" class="form-control" name="manual_qr_code" accept="image/*">
                    <?php if(!empty($currentSettings['manual_qr_code_url'])): ?>
                        <div class="mt-2">
                            <img src="<?= htmlspecialchars($currentSettings['manual_qr_code_url']) ?>" alt="QR Code" style="height: 100px; border: 1px solid #ddd; padding: 5px;" class="rounded">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
            <hr>
            <h5 class="mt-4 text-primary"><i class="fas fa-search me-2"></i>SEO & Content Optimization</h5>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="seo_title" class="form-label fw-bold">Global Meta Title (SEO Title)</label>
                    <input type="text" class="form-control" id="seo_title" name="seo_title" value="<?= htmlspecialchars($currentSettings['seo_title'] ?? '') ?>" placeholder="e.g. B R Online Services | Professional Digital Solutions">
                    <small class="text-muted">This title appears in browser tabs and search results.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="seo_description" class="form-label fw-bold">Meta Description</label>
                    <textarea class="form-control" id="seo_description" name="seo_description" rows="3" placeholder="A brief summary of your website for search engines..."><?= htmlspecialchars($currentSettings['seo_description'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="seo_keywords" class="form-label fw-bold">Meta Keywords</label>
                    <textarea class="form-control" id="seo_keywords" name="seo_keywords" rows="3" placeholder="keyword1, keyword2, digital services, online work..."><?= htmlspecialchars($currentSettings['seo_keywords'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="google_site_verification" class="form-label fw-bold">Google Search Console Verification Code</label>
                    <input type="text" class="form-control" id="google_site_verification" name="google_site_verification" value="<?= htmlspecialchars($currentSettings['google_site_verification'] ?? '') ?>" placeholder="Paste the content value from the meta tag">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="seo_og_image" class="form-label fw-bold">Social Sharing Image (OG Image)</label>
                    <input type="file" class="form-control" id="seo_og_image" name="seo_og_image" accept="image/*">
                    <?php if(!empty($currentSettings['seo_og_image'])): ?>
                        <img src="<?= htmlspecialchars($currentSettings['seo_og_image']) ?>" alt="OG Image" class="mt-2" style="max-height: 50px; border: 1px solid #ddd;">
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="seo_global_code_head" class="form-label fw-bold">Global Header Code (Inside &lt;head&gt;)</label>
                    <textarea class="form-control" id="seo_global_code_head" name="seo_global_code_head" rows="3" placeholder="Paste Google Analytics, Pixel or other heatmaps code here"><?= htmlspecialchars($currentSettings['seo_global_code_head'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="seo_global_code_body" class="form-label fw-bold">Global Body Code (After &lt;body&gt;)</label>
                    <textarea class="form-control" id="seo_global_code_body" name="seo_global_code_body" rows="3" placeholder="Code to appear at the start of the body tag"><?= htmlspecialchars($currentSettings['seo_global_code_body'] ?? '') ?></textarea>
                </div>
            </div>
            <hr>
            <h5 class="mt-4 text-danger"><i class="fab fa-google me-2"></i>Google AdSense & Monetization</h5>
            <div class="mb-3">
                <div class="form-check form-switch custom-switch">
                    <input class="form-check-input" type="checkbox" id="ads_global_toggle" name="ads_global_toggle" value="1" <?= ($currentSettings['ads_global_toggle'] ?? 0) == 1 ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="ads_global_toggle">Enable Google AdSense Globally</label>
                </div>
                <small class="text-muted">Turning this OFF will hide all ads for everyone immediately.</small>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="adsense_global_code" class="form-label fw-bold">AdSense Global Header Script (Head Tag)</label>
                    <textarea class="form-control" id="adsense_global_code" name="adsense_global_code" rows="4" placeholder="Paste the <script async src='...'> tag here"><?= htmlspecialchars($currentSettings['adsense_global_code'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="adsense_ad_unit_code" class="form-label fw-bold">Display Ad Unit Code (In-Page)</label>
                    <textarea class="form-control" id="adsense_ad_unit_code" name="adsense_ad_unit_code" rows="4" placeholder="Paste the <ins class='adsbygoogle' ...> tag here"><?= htmlspecialchars($currentSettings['adsense_ad_unit_code'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Enable Ads for Specific Services</label>
                <div class="p-3 bg-light border rounded" style="max-height: 200px; overflow-y: auto;">
                    <div class="row">
                        <?php foreach($services as $svc): ?>
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="ads_enabled_services[]" value="<?= $svc['service_slug'] ?>" id="ads_svc_<?= $svc['service_slug'] ?>" <?= in_array($svc['service_slug'], $ads_enabled_services) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ads_svc_<?= $svc['service_slug'] ?>"><?= htmlspecialchars($svc['service_name']) ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <small class="text-info"><i class="fas fa-info-circle me-1"></i> Pro Users (active subscription) will never see these ads regardless of these settings.</small>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="fas fa-save me-2"></i>Save All Settings</button>
            </div>
        </form>
    </div>
</div>