<?php
// views/admin/website_settings.php
if (!in_array($_SESSION['user_role'] ?? '', ['master_admin', 'admin'])) {
    echo "Access Denied."; exit;
}

$pdo = connectDB();
$message = '';

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$currentSettings = fetchOne($pdo, "SELECT * FROM settings WHERE id = 1 LIMIT 1");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-palette text-primary"></i> Website Theme & Settings</h2>
</div>

<?= $message ?>

<div class="card shadow-sm border-0" style="border-radius: 12px;">
    <div class="card-body p-4">
        <form action="index.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_website_settings">
            <input type="hidden" name="page" value="website_settings">

            <h5 class="mb-4 text-primary border-bottom pb-2">Public Website Header & Logo</h5>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">Upload Website Logo</label>
                    <div class="mb-2 text-muted small">This logo will be displayed publicly on the B2C website and headers.</div>
                    <input type="file" class="form-control" name="website_logo" accept="image/*">
                    <?php if(!empty($currentSettings['website_logo_url'])): ?>
                        <div class="mt-3 p-3 bg-light rounded text-center border">
                            <img src="<?= htmlspecialchars($currentSettings['website_logo_url']) ?>" alt="Website Logo" style="height: <?= htmlspecialchars($currentSettings['website_logo_size'] ?: 50) ?>px; object-fit: contain;">
                        </div>
                    <?php endif; ?>
                    <div class="mt-3">
                        <label class="form-label fw-bold">Logo Size (Height in PX)</label>
                        <input type="number" class="form-control" name="website_logo_size" value="<?= htmlspecialchars($currentSettings['website_logo_size'] ?: 50) ?>" min="20" max="150">
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">Website Header Design Style</label>
                    <div class="mb-2 text-muted small">Select the visual appearance of the navigation bar.</div>
                    <select class="form-select form-select-lg mb-3" name="header_style">
                        <option value="style1" <?= ($currentSettings['header_style']??'') == 'style1' ? 'selected' : '' ?>>Style 1: Modern Centered</option>
                        <option value="style2" <?= ($currentSettings['header_style']??'') == 'style2' ? 'selected' : '' ?>>Style 2: Classic Left (White Background)</option>
                        <option value="style3" <?= ($currentSettings['header_style']??'') == 'style3' ? 'selected' : '' ?>>Style 3: Minimal Dark (Black Background)</option>
                        <option value="style4" <?= ($currentSettings['header_style']??'') == 'style4' ? 'selected' : '' ?>>Style 4: Vibrant Gradient (Blue-Indigo)</option>
                    </select>

                    <label class="form-label fw-bold">Menu Text Color</label>
                    <input type="color" class="form-control form-control-color mb-3" name="menu_color" value="<?= htmlspecialchars($currentSettings['menu_color'] ?: '#ffffff') ?>" title="Choose Menu Color">

                    <label class="form-label fw-bold">Active Menu Text Color</label>
                    <input type="color" class="form-control form-control-color" name="menu_active_color" value="<?= htmlspecialchars($currentSettings['menu_active_color'] ?: '#f39c12') ?>" title="Choose Active Menu Color">
                </div>
            </div>
            
            <h5 class="mb-4 mt-4 text-primary border-bottom pb-2">Dynamic Website Footer</h5>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">Footer About Text</label>
                    <div class="mb-2 text-muted small">A short description of your online portal displayed in the bottom-left corner.</div>
                    <textarea class="form-control" name="footer_about_text" rows="3"><?= htmlspecialchars($currentSettings['footer_about_text'] ?? '') ?></textarea>
                    
                    <label class="form-label fw-bold mt-3">Copyright Notice</label>
                    <input type="text" class="form-control" name="footer_copyright" value="<?= htmlspecialchars($currentSettings['footer_copyright'] ?? '') ?>">
                </div>
                
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">Contact Info (Public Footer)</label>
                    <div class="mb-2 text-muted small">Change the address, contact number and support email mapped to your public footer block.</div>
                    
                    <div class="input-group mb-2">
                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                        <textarea class="form-control" name="office_address" placeholder="123 Street Name, City" rows="2"><?= htmlspecialchars($currentSettings['office_address'] ?? '') ?></textarea>
                    </div>

                    <div class="input-group mb-2 mt-3">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" class="form-control" name="helpline_number" placeholder="+91 9876543210" value="<?= htmlspecialchars($currentSettings['helpline_number'] ?? '') ?>">
                    </div>

                    <div class="input-group mb-2 mt-3">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" name="contact_email_public" placeholder="support@yourdomain.com" value="<?= htmlspecialchars($currentSettings['contact_email_public'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <h6 class="mb-3 text-secondary border-bottom pb-1">Social Media Links (Leave blank to hide icons)</h6>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-primary"><i class="fab fa-facebook-f"></i></span>
                        <input type="url" class="form-control" name="social_facebook" placeholder="https://facebook.com/..." value="<?= htmlspecialchars($currentSettings['social_facebook'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-info"><i class="fab fa-twitter"></i></span>
                        <input type="url" class="form-control" name="social_twitter" placeholder="https://x.com/..." value="<?= htmlspecialchars($currentSettings['social_twitter'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-danger"><i class="fab fa-instagram"></i></span>
                        <input type="url" class="form-control" name="social_instagram" placeholder="https://instagram.com/..." value="<?= htmlspecialchars($currentSettings['social_instagram'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light" style="color: #0077b5;"><i class="fab fa-linkedin-in"></i></span>
                        <input type="url" class="form-control" name="social_linkedin" placeholder="https://linkedin.com/in/..." value="<?= htmlspecialchars($currentSettings['social_linkedin'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-danger"><i class="fab fa-youtube"></i></span>
                        <input type="url" class="form-control" name="social_youtube" placeholder="https://youtube.com/..." value="<?= htmlspecialchars($currentSettings['social_youtube'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm"><i class="fas fa-save me-2"></i> Save Website Settings</button>
            </div>
        </form>
    </div>
</div>
