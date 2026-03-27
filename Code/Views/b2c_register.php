<?php
// b2c_register.php
include __DIR__ . '/includes/b2c_header.php';
?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0" style="border-radius: 15px;">
                <div class="card-body p-5">
                    <h2 class="text-center fw-bold text-primary mb-4">Create Your Account</h2>
                    <p class="text-center text-muted mb-4">Sign up to get a <strong>10 Credit Points</strong> bonus instantly!</p>
                    


                    <!-- Direct System Registration Form -->
                    <form action="<?= BASE_URL ?>" method="POST" id="regForm">
                        <input type="hidden" name="action" value="register_b2c">
                        
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
                        
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="regConfirmPass" name="confirm_password" placeholder="Confirm Password" required>
                            <label for="regConfirmPass">Confirm Password</label>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm">Sign Up Now & Get 10 Points</button>
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
                    
                    <div class="text-center mt-4 text-muted">
                        Already have an account? <a href="<?= BASE_URL ?>?page=b2c_login" class="text-decoration-none fw-bold">Login Here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/b2c_footer.php'; ?>
