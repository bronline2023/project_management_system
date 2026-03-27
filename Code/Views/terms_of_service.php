<?php include __DIR__ . '/includes/b2c_header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body p-5">
                    <h1 class="fw-bold text-center mb-4"><i class="fas fa-file-contract text-warning"></i> Terms of Service</h1>
                    <p class="text-muted text-center mb-5">Last Updated: <?= date('F d, Y') ?></p>

                    <div class="policy-content" style="line-height: 1.8;">
                        <h4 class="fw-bold mt-4">1. Agreement to Terms</h4>
                        <p>By accessing or using our Digital Services Portal and our Cyber Cafe facilities (online or offline), you agree to be bound by these Terms of Service. This platform offers various digital utilities, including but not limited to, Poster Design, Document Converters, Passport Photo Makers, and Educational material access. If you disagree with any part of these terms, please do not use our platform.</p>

                        <h4 class="fw-bold mt-4">2. Usage Rules for Cyber Cafe and Digital Tools</h4>
                        <p>Our platform is designed to assist users, students, and businesses with creative and administrative digital tasks. You agree to use our services responsibly:</p>
                        <ul>
                            <li><strong>Lawful Use Only:</strong> You will not use our tools (like Photo Studio or Document Converter) to forge documents, create misleading identities, or conduct any illegal activities.</li>
                            <li><strong>Educational Purpose:</strong> The educational content, tutorials, and mock exams provided on this site are for learning strictly. Reproducing them for commercial resale without our permission is strictly prohibited.</li>
                            <li><strong>Account Security:</strong> You are responsible for keeping your login credentials secure, whether you sign up manually or use our Facebook/Google OAuth integrations.</li>
                        </ul>

                        <h4 class="fw-bold mt-4">3. Subscriptions, Credits, and Payments</h4>
                        <p>We provide a <strong>Credit Points</strong> system (e.g., the 10 points signup bonus) that can be used to execute certain digital services. We also offer Premium Gold Subscriptions for B2B portal creations. All transactions are closely monitored. If our administration suspects credit fraud, your Retailer or user account may be frozen without preliminary notice.</p>

                        <h4 class="fw-bold mt-4">4. Facebook Identity Registration</h4>
                        <p>By using the "Sign in with Facebook" or "Sign in with Google" features, you authorize us to retrieve your public profile information solely for the purpose of creating and validating your account on our Cyber Cafe platform. You understand that your usage of those social platforms is governed by their respective terms of service.</p>

                        <h4 class="fw-bold mt-4">5. Service Availability and Revisions</h4>
                        <p>We constantly strive to make sure our tools (Resume builder, Smart Card, etc.) are up. However, we do not guarantee 100% uninterrupted service. We reserve the right to modify, add, or remove tools to align with operational upgrades or Cyber Cafe regulations at any given time.</p>

                        <h4 class="fw-bold mt-4">6. Intellectual Property</h4>
                        <p>The tools, source logic, and specific educational materials provided on our portal are the intellectual property of our company. You are granted a limited license to use these tools for your personal or small-business needs. The output you generate (e.g., your own resume) is completely yours to own and distribute.</p>

                        <h4 class="fw-bold mt-4">7. Governing Law and Dispute Resolution</h4>
                        <p>These terms shall be governed in accordance with local laws. Any disputes arising directly from your use of our Cyber Cafe portal or educational services will first be handled through friendly negotiation via our administrative contact center.</p>

                        <p class="mt-5 text-center fw-bold">By creating an account, you signify your acceptance of these Terms of Service.</p>
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
