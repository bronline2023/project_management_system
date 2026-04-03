<?php
/**
 * views/includes/b2c_footer.php
 * REDESIGNED: Premium Global Footer.
 * Matches the new Split-Screen and Home Page aesthetic.
 */
if (!isset($pdo)) {
    require_once __DIR__ . '/../../../config.php';
    require_once MODELS_PATH . 'db.php';
    $pdo = connectDB();
}
$stmtF = $pdo->query("SELECT footer_about_text, social_facebook, social_twitter, social_instagram, social_linkedin, social_youtube, contact_email_public, footer_copyright, office_address, helpline_number, app_name FROM settings WHERE id = 1 LIMIT 1");
$footerSettings = $stmtF->fetch(PDO::FETCH_ASSOC);
$appNameFooter = $footerSettings['app_name'] ?? APP_NAME;
?>

<style>
    .premium-footer {
        background: #0f172a;
        color: rgba(255, 255, 255, 0.8);
        padding: 120px 0 60px;
        position: relative;
        font-family: 'Outfit', sans-serif;
        border-top: 4px solid transparent;
        border-image: linear-gradient(90deg, var(--brand-primary), var(--brand-secondary), var(--brand-accent)) 1;
    }

    .footer-logo {
        font-size: 2rem;
        font-weight: 800;
        color: white;
        margin-bottom: 2rem;
        display: inline-block;
        background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,0.7) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .footer-heading {
        color: white;
        font-weight: 800;
        margin-bottom: 2.5rem;
        position: relative;
        padding-bottom: 15px;
        font-size: 1.25rem;
        letter-spacing: 0.5px;
    }

    .footer-heading::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; width: 50px; height: 4px;
        background: linear-gradient(90deg, var(--brand-primary), var(--brand-secondary));
        border-radius: 2px;
    }

    .footer-link {
        color: rgba(255, 255, 255, 0.5);
        text-decoration: none !important;
        transition: all 0.3s;
        display: block;
        padding: 0.6rem 0;
        font-weight: 500;
        font-size: 1rem;
    }

    .footer-link:hover {
        color: white;
        transform: translateX(8px);
    }

    .social-btn {
        width: 50px; height: 50px;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.03);
        color: white;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 1px solid rgba(255, 255, 255, 0.08);
        font-size: 1.3rem;
    }

    .social-btn:hover {
        background: var(--brand-primary);
        transform: translateY(-8px) rotate(8deg);
        color: white !important;
        box-shadow: 0 15px 35px rgba(79, 70, 229, 0.4);
        border-color: rgba(255,255,255,0.2);
    }

    .contact-item {
        display: flex;
        gap: 1.2rem;
        margin-bottom: 2rem;
        align-items: center;
    }

    .contact-icon {
        width: 45px; height: 45px;
        border-radius: 14px;
        background: rgba(79, 70, 229, 0.1);
        color: var(--brand-secondary);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
        border: 1px solid rgba(79, 70, 229, 0.1);
    }

    .footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        padding-top: 40px;
        margin-top: 100px;
        text-align: center;
        font-size: 0.95rem;
        color: rgba(255, 255, 255, 0.4);
    }
</style>

<footer class="premium-footer">
    <div class="container">
        <div class="row">
            <!-- Brand Section -->
            <div class="col-lg-4 mb-5 pe-lg-5">
                <a href="#" class="footer-logo text-decoration-none">
                    <i class="fas fa-layer-group text-primary me-2"></i> <?= htmlspecialchars($appNameFooter) ?>
                </a>
                <p class="mb-4" style="line-height: 1.8;">
                    <?= nl2br(htmlspecialchars($footerSettings['footer_about_text'] ?? 'Providing best-in-class digital solutions for our worldwide clients with a focus on quality and security.')) ?>
                </p>
                
                <div class="social-icons-footer">
                    <?php if(!empty($footerSettings['social_facebook'])): ?>
                        <a href="<?= htmlspecialchars($footerSettings['social_facebook']) ?>" target="_blank" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($footerSettings['social_twitter'])): ?>
                        <a href="<?= htmlspecialchars($footerSettings['social_twitter']) ?>" target="_blank" class="social-btn"><i class="fab fa-x-twitter"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($footerSettings['social_instagram'])): ?>
                        <a href="<?= htmlspecialchars($footerSettings['social_instagram']) ?>" target="_blank" class="social-btn"><i class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($footerSettings['social_linkedin'])): ?>
                        <a href="<?= htmlspecialchars($footerSettings['social_linkedin']) ?>" target="_blank" class="social-btn"><i class="fab fa-linkedin-in"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($footerSettings['social_youtube'])): ?>
                        <a href="<?= htmlspecialchars($footerSettings['social_youtube']) ?>" target="_blank" class="social-btn"><i class="fab fa-youtube"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-5">
                <h5 class="footer-heading">Company</h5>
                <ul class="list-unstyled">
                    <li><a href="<?= BASE_URL ?>?page=b2c_home" class="footer-link">Home</a></li>
                    <li><a href="<?= BASE_URL ?>?page=b2c_page&slug=about-us" class="footer-link">About Us</a></li>
                    <li><a href="<?= BASE_URL ?>?page=appointment" class="footer-link text-primary fw-bold">Book Visit</a></li>
                    <li><a href="<?= BASE_URL ?>?page=b2c_page&slug=our-services" class="footer-link">Our Services</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div class="col-lg-2 col-md-6 mb-5">
                <h5 class="footer-heading">Support</h5>
                <ul class="list-unstyled">
                    <li><a href="<?= BASE_URL ?>?page=privacy_policy" class="footer-link">Privacy Policy</a></li>
                    <li><a href="<?= BASE_URL ?>?page=terms_of_service" class="footer-link">Terms & Conditions</a></li>
                    <li><a href="<?= BASE_URL ?>?page=b2c_page&slug=help-center" class="footer-link">Help Center</a></li>
                </ul>
            </div>

            <!-- Contact Section -->
            <div class="col-lg-4 mb-5">
                <h5 class="footer-heading">Get in Touch</h5>
                
                <div class="contact-item">
                    <div class="contact-icon"><i class="fas fa-location-dot"></i></div>
                    <div>
                        <small class="d-block text-white-50">Headquarters</small>
                        <span><?= nl2br(htmlspecialchars($footerSettings['office_address'] ?? 'Gujarat, India')) ?></span>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon"><i class="fas fa-phone-volume"></i></div>
                    <div>
                        <small class="d-block text-white-50">Authorized Support</small>
                        <span class="fw-bold"><?= htmlspecialchars($footerSettings['helpline_number'] ?? '+91 00000 00000') ?></span>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon"><i class="fas fa-envelope-open-text"></i></div>
                    <div>
                        <small class="d-block text-white-50">Official Email</small>
                        <span><?= htmlspecialchars($footerSettings['contact_email_public'] ?? 'support@bronline.online') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="mb-0">&copy; <?= date('Y') ?> <?= htmlspecialchars($appNameFooter) ?>. Proudly built with Authorized Security Protocols. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
