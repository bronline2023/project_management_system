<?php
/**
 * views/b2c_register.php
 * REDESIGNED: Premium Split-Screen B2C User Registration.
 */
include __DIR__ . '/includes/b2c_header.php';
?>

<style>
    .auth-split-wrapper {
        display: flex;
        min-height: calc(100vh - var(--nav-height));
        background: #ffffff;
        margin-top: calc(-1 * var(--nav-height));
    }

    .auth-visual-panel {
        flex: 1;
        background: linear-gradient(135deg, var(--brand-accent), var(--brand-indigo), var(--brand-secondary));
        background-size: 400% 400%;
        animation: gradientShift 15s ease infinite;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 4rem;
        position: relative;
        overflow: hidden;
    }

    @media (min-width: 992px) {
        .auth-visual-panel { display: flex; }
    }

    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .auth-form-panel {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 4rem 2rem;
        background: #f8fafc;
    }

    .auth-glass-box {
        width: 100%;
        max-width: 520px;
        background: white;
        padding: 3.5rem;
        border-radius: 40px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
        border: 1px solid #f1f5f9;
        animation: slideUp 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .form-floating > .form-control {
        border-radius: 18px;
        border: 2px solid #f1f5f9;
        padding-left: 1.25rem;
        font-weight: 500;
    }

    .form-floating > .form-control:focus {
        border-color: var(--brand-indigo);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }

    .btn-auth-submit {
        background: linear-gradient(135deg, var(--brand-indigo), var(--brand-blue));
        border: none;
        border-radius: 20px;
        padding: 1rem;
        font-weight: 800;
        color: white;
        transition: 0.3s;
        box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
    }

    .btn-auth-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(79, 70, 229, 0.4);
        color: white;
    }

    .visual-content {
        color: white;
        text-align: center;
        z-index: 2;
    }

    .visual-content h2 { font-size: 3.5rem; font-weight: 800; letter-spacing: -2px; margin-bottom: 2rem; }
    .visual-content p { font-size: 1.25rem; opacity: 0.9; max-width: 550px; line-height: 1.6; }

    /* Floating shapes */
    .shape { position: absolute; background: rgba(255,255,255,0.1); border-radius: 50%; backdrop-filter: blur(5px); }

    .logo-square-mini {
        width: 80px;
        height: 80px;
        background: #f8fafc;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        border: 2px solid #f1f5f9;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }
</style>

<div class="auth-split-wrapper">
    <div class="auth-visual-panel">
        <div class="shape" style="width: 300px; height: 300px; top: -50px; left: -50px;"></div>
        <div class="shape" style="width: 200px; height: 200px; bottom: -30px; right: 50px;"></div>
        <div class="visual-content">
            <h2>Join the Future Today</h2>
            <p>Create your account in seconds and instantly receive <strong>10 Credit Points</strong> to explore our premium digital toolsuite.</p>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-glass-box">
            <div class="text-center mb-5">
                <div class="logo-square-mini mb-4">
                     <?php if (!empty($site_settings['website_logo_url'])): ?>
                        <img src="<?= htmlspecialchars($site_settings['website_logo_url']) ?>" alt="Logo" style="max-height: 40px;">
                    <?php else: ?>
                        <i class="fas fa-id-card fa-2x text-primary"></i>
                    <?php endif; ?>
                </div>
                <h2 class="fw-800 mb-2">Create Account</h2>
                <p class="text-muted">Join today and claim 10 bonus points.</p>
            </div>

            <form action="<?= BASE_URL ?>Code/Core_Logic/App/actions.php" method="POST" id="regForm">
                <input type="hidden" name="action" value="register_b2c">
                <input type="hidden" name="page" value="login">
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="regName" name="name" placeholder="John Doe" required>
                    <label for="regName">Full Name</label>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="regEmail" name="email" placeholder="name@example.com" required>
                    <label for="regEmail">Email Address</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="regPhone" name="phone" placeholder="9876543210" required>
                    <label for="regPhone">Mobile Number</label>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="regPass" name="password" placeholder="Password" required>
                    <label for="regPass">Create Password</label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="regConfirmPass" name="confirm_password" placeholder="Confirm Password" required>
                    <label for="regConfirmPass">Confirm Password</label>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-auth-submit">Register & Claim Points <i class="fas fa-gift ms-2"></i></button>
                </div>
            </form>
            
            <script>
            document.getElementById('regForm').addEventListener('submit', function(e) {
                var pass = document.getElementById('regPass').value;
                var confirmPass = document.getElementById('regConfirmPass').value;
                if(pass !== confirmPass) {
                    e.preventDefault();
                    alert("Passwords do not match!");
                }
            });
            </script>
            
            <div class="text-center mt-5 text-muted fw-500">
                Already part of the network? <br>
                <a href="<?= BASE_URL ?>?page=b2c_login" class="text-primary text-decoration-none fw-800">Login Here</a>
            </div>
        </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/b2c_footer.php'; ?>
