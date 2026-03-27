<?php include __DIR__ . '/includes/b2c_header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body p-5">
                    <h1 class="fw-bold text-center mb-4"><i class="fas fa-user-shield text-primary"></i> Privacy Policy</h1>
                    <p class="text-muted text-center mb-5">Effective Date: <?= date('F d, Y') ?></p>

                    <div class="policy-content" style="line-height: 1.8;">
                        <h4 class="fw-bold mt-4">1. Introduction</h4>
                        <p>Welcome to our Digital Services and Cyber Cafe Platform. We value your privacy and are committed to protecting your personal information. This Privacy Policy outlines how we collect, use, and safeguard your data when you visit our website, use our digital tools (like Resume Builder, Smart Card Generator, Photo Studio, etc.), or participate in our educational and Cyber Cafe services.</p>

                        <h4 class="fw-bold mt-4">2. Information We Collect</h4>
                        <p>When you register on our platform (directly or via social sign-ins like Facebook, Google, or WhatsApp), we may collect the following information:</p>
                        <ul>
                            <li><strong>Personal Identity Details:</strong> Your full name, email address, and profile picture provided during registration or OAuth sign-in.</li>
                            <li><strong>Service Data:</strong> Text, images, and documents you intentionally upload to use our services (e.g., photos for Passport Maker, details for Resume Builder).</li>
                            <li><strong>Cyber Cafe Usage Data:</strong> Session details related to your use of our physical or online cafe services for legal compliance and security.</li>
                        </ul>

                        <h4 class="fw-bold mt-4">3. How We Use Your Information</h4>
                        <p>We do not sell your personal data. We strictly use your information to:</p>
                        <ul>
                            <li>Provide seamless access to our digital tools (Poster design, size converters, etc.).</li>
                            <li>Deliver educational content and resources directly to your account.</li>
                            <li>Create and manage your Digital Retailer or User account and assign your signup bonus points.</li>
                            <li>Improve the performance, safety, and functionality of our online Cyber Cafe platform.</li>
                        </ul>

                        <h4 class="fw-bold mt-4">4. Third-Party Access (Facebook / Meta Integration)</h4>
                        <p>For your convenience, we offer Facebook and Google login functionalities. When you use these integrations, the respective third parties securely pass your basic profile data (name and email) to us so we can create your account. We do not have access to your Facebook passwords or private messages. You can revoke our app's access at any time through your social media account settings.</p>

                        <h4 class="fw-bold mt-4">5. Educational and Cyber Cafe Context</h4>
                        <p>Our platform frequently hosts educational materials, student resources, and acts as a digital hub for Cyber Cafe operations. Any data collected from students or cafe visitors is handled with strict confidentiality and is solely used to process their immediate service requests (such as document printing, form filling, or educational coaching).</p>

                        <h4 class="fw-bold mt-4">6. Data Security and Deletion</h4>
                        <p>We implement standard security measures to ensure your data stays safe. Documents you upload for conversion or processing are generally clear from our active processing servers after the task finishes. If you wish to delete your account entirely, please contact our support desk.</p>

                        <h4 class="fw-bold mt-4">7. Contact Us</h4>
                        <p>If you have any questions regarding this Privacy Policy, your account data, or how our Cyber Cafe handles your privacy, please connect with our admin desk via the <a href="<?= BASE_URL ?>?page=contact_us">Contact Us</a> page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .policy-content h4 { color: #2c3e50; border-bottom: 2px solid #f8f9fa; padding-bottom: 8px; }
    .policy-content p, .policy-content li { color: #555; font-size: 1.05rem; }
</style>

<?php include __DIR__ . '/includes/b2c_footer.php'; ?>
