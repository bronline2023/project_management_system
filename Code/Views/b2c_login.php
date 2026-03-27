<?php
// views/b2c_login.php
include __DIR__ . '/includes/b2c_header.php';

$message = '';
if(isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}
?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg border-0" style="border-radius: 15px;">
                <div class="card-body p-5">
                    <h2 class="text-center fw-bold mb-4" style="color: var(--primary-color);">Welcome Back!</h2>
                    <p class="text-center text-muted mb-4">Login to access your digital services and wallet.</p>
                    
                    <?= $message ?>

                    <form action="<?= BASE_URL ?>index.php" method="POST">
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

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm">Login Securely</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4 text-muted">
                        Don't have an account? <a href="<?= BASE_URL ?>?page=b2c_register" class="text-decoration-none fw-bold" style="color: var(--secondary-color);">Sign Up & Get 10 Points</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/b2c_footer.php'; ?>