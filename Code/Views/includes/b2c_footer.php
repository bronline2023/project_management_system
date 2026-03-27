<?php
// b2c_footer.php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../../config.php';
    require_once MODELS_PATH . 'db.php';
    $pdo = connectDB();
}
$stmtF = $pdo->query("SELECT footer_about_text, social_facebook, social_twitter, social_instagram, social_linkedin, social_youtube, contact_email_public, footer_copyright, office_address, helpline_number, app_name FROM settings WHERE id = 1 LIMIT 1");
$footerSettings = $stmtF->fetch(PDO::FETCH_ASSOC);
$appNameFooter = $footerSettings['app_name'] ?? APP_NAME;
?>
<footer class="bg-dark text-white pt-5 pb-3 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5><i class="fas fa-globe"></i> <?= htmlspecialchars($appNameFooter) ?></h5>
                <p><?= nl2br(htmlspecialchars($footerSettings['footer_about_text'] ?? '')) ?></p>
                <div class="mt-3">
                    <?php if(!empty($footerSettings['social_facebook'])): ?>
                        <a href="<?= htmlspecialchars($footerSettings['social_facebook']) ?>" target="_blank" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($footerSettings['social_twitter'])): ?>
                        <a href="<?= htmlspecialchars($footerSettings['social_twitter']) ?>" target="_blank" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($footerSettings['social_instagram'])): ?>
                        <a href="<?= htmlspecialchars($footerSettings['social_instagram']) ?>" target="_blank" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($footerSettings['social_linkedin'])): ?>
                        <a href="<?= htmlspecialchars($footerSettings['social_linkedin']) ?>" target="_blank" class="text-white me-3"><i class="fab fa-linkedin-in fa-lg"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($footerSettings['social_youtube'])): ?>
                        <a href="<?= htmlspecialchars($footerSettings['social_youtube']) ?>" target="_blank" class="text-white me-3"><i class="fab fa-youtube fa-lg"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="<?= BASE_URL ?>?page=b2c_home" class="text-white-50 text-decoration-none">Home</a></li>
                    <li><a href="<?= BASE_URL ?>?page=b2c_page&slug=about-us" class="text-white-50 text-decoration-none">About Us</a></li>
                    <li><a href="<?= BASE_URL ?>?page=b2c_page&slug=contact-us" class="text-white-50 text-decoration-none">Contact Us</a></li>
                    <li><a href="<?= BASE_URL ?>?page=b2c_page&slug=services" class="text-white-50 text-decoration-none">Services</a></li>
                    <li><a href="<?= BASE_URL ?>?page=privacy_policy" class="text-white-50 text-decoration-none">Privacy Policy</a></li>
                    <li><a href="<?= BASE_URL ?>?page=terms_of_service" class="text-white-50 text-decoration-none">Terms of Service</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5>Contact Info</h5>
                <ul class="list-unstyled text-white-50">
                    <?php if(!empty($footerSettings['office_address'])): ?>
                        <li><i class="fas fa-map-marker-alt me-2 mt-1"></i> <span style="vertical-align: top; display: inline-block; width: 90%;"><?= nl2br(htmlspecialchars($footerSettings['office_address'])) ?></span></li>
                    <?php endif; ?>
                    <?php if(!empty($footerSettings['helpline_number'])): ?>
                        <li class="mt-2"><i class="fas fa-phone me-2"></i> <?= htmlspecialchars($footerSettings['helpline_number']) ?></li>
                    <?php endif; ?>
                    <?php if(!empty($footerSettings['contact_email_public'])): ?>
                        <li class="mt-2"><i class="fas fa-envelope me-2"></i> <?= htmlspecialchars($footerSettings['contact_email_public']) ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <hr class="border-secondary">
        <div class="text-center text-white-50">
            <small>&copy; <?= htmlspecialchars($footerSettings['footer_copyright'] ?: (date('Y') . ' ' . $appNameFooter . '. All Rights Reserved.')) ?></small>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
