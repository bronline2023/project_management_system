<?php
/**
 * views/b2c_login.php
 * REDESIGNED: Premium Split-Screen B2C User Login.
 */
include __DIR__ . '/includes/b2c_header.php';

$message = '';
if(isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}
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
        background: linear-gradient(135deg, var(--brand-indigo), var(--brand-blue), var(--brand-accent));
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
        max-width: 480px;
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
    .visual-content p { font-size: 1.25rem; opacity: 0.9; max-width: 500px; line-height: 1.6; }

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
    <div class="auth-visual-panel d-none d-lg-flex">
        <div class="shape" style="width: 300px; height: 300px; top: -50px; left: -50px;"></div>
        <div class="shape" style="width: 200px; height: 200px; bottom: -30px; right: 50px;"></div>
        <div class="visual-content">
            <h2>Experience Digital Mastery</h2>
            <p>Access your personalized studio suite, manage your wallet, and unlock unlimited design potential.</p>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-glass-box">
            <div class="text-center mb-5">
                <div class="logo-square-mini mb-4">
                     <?php if (!empty($site_settings['website_logo_url'])): ?>
                        <img src="<?= htmlspecialchars($site_settings['website_logo_url']) ?>" alt="Logo" style="max-height: 40px;">
                    <?php else: ?>
                        <i class="fas fa-shield-halved fa-2x text-primary"></i>
                    <?php endif; ?>
                </div>
            <h2 class="fw-800 mb-2">User Login</h2>
            <p class="text-muted">Access your digital studio and tools.</p>
        </div>

        <?= $message ?>

        <form action="<?= BASE_URL ?>Code/Core_Logic/App/actions.php" method="POST">
            <input type="hidden" name="action" value="login_submit">
            <input type="hidden" name="page" value="b2c_home">
            
            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="loginEmail" name="email" placeholder="name@example.com" required>
                <label for="loginEmail">Email Address</label>
            </div>
            
            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="loginPass" name="password" placeholder="Password" required>
                <label for="loginPass">Password</label>
            </div>

            <div class="d-grid mb-4">
                <button type="submit" class="btn btn-auth-submit btn-lg">
                    Login Securely <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div>
        </form>
        
        <div class="mt-5 pt-4 border-top">
            <div class="text-center">
                <p class="text-muted mb-3">Don't have an account yet?</p>
                <a href="<?= BASE_URL ?>?page=b2c_register" class="btn btn-outline-primary w-100 rounded-pill py-2 fw-bold">
                    <i class="fas fa-user-plus me-2"></i> Create New Account
                </a>
                <p class="mt-3 small text-success fw-bold">
                    <i class="fas fa-gift me-1"></i> Sign up today and get 10 Points FREE!
                </p>
            </div>
        </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/b2c_footer.php'; ?>