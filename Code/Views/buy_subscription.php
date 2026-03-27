<?php
// views/buy_subscription.php
$pdo = connectDB();
$b2c_plans = $pdo->query("SELECT * FROM b2c_subscription_plans WHERE is_active = 1 ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
$services = $pdo->query("SELECT * FROM digital_service_rates")->fetchAll(PDO::FETCH_ASSOC);
$servicesMap = [];
foreach($services as $s) {
    $servicesMap[$s['service_slug']] = $s['service_name'];
}

// Include B2C Header for proper styling and navigation
if (file_exists(__DIR__ . '/includes/b2c_header.php')) {
    require_once __DIR__ . '/includes/b2c_header.php';
} elseif (file_exists(__DIR__ . '/../includes/header.php') && isset($_SESSION['user_id'])) {
    // Fallback for logged in users if b2c_header is meant only for homepage
    // Actually b2c_header is fine for both logged in and out
}
?>
<div class="container-fluid py-5" style="min-height: 80vh;">
    <div class="row text-center mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark"><i class="fas fa-gem text-primary"></i> Upgrade Your Account</h2>
            <p class="text-muted">Choose between a B2B SaaS Portal or a B2C Digital Services Package.</p>
        </div>
    </div>

    <!-- TABS -->
    <ul class="nav nav-pills justify-content-center mb-5" id="subTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold px-4" id="b2c-tab" data-bs-toggle="tab" data-bs-target="#b2c" type="button" role="tab">B2C Digital Service Plans</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4" id="b2b-tab" data-bs-toggle="tab" data-bs-target="#b2b" type="button" role="tab">B2B Portal Subscription</button>
        </li>
    </ul>

    <!-- TAB CONTENTS -->
    <div class="tab-content" id="subTabsContent">
        
        <!-- B2C TAB -->
        <div class="tab-pane fade show active" id="b2c" role="tabpanel">
            <div class="row justify-content-center">
                <?php foreach($b2c_plans as $plan): 
                    $allowed = json_decode($plan['allowed_services'], true) ?: [];
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0 h-100 rounded-4 text-center plan-card transition-all">
                        <div class="card-body p-4 d-flex flex-column">
                            <h4 class="fw-bold text-primary mb-2"><?= htmlspecialchars($plan['plan_name']) ?></h4>
                            <p class="text-muted small mb-4" style="min-height: 40px;"><?= htmlspecialchars($plan['description']) ?></p>
                            
                            <h1 class="fw-bold display-5 mb-0">₹<?= number_format($plan['price'], 0) ?></h1>
                            <p class="text-muted border-bottom pb-4">for <?= $plan['validity_days'] ?> Days</p>
                            
                            <ul class="list-unstyled text-start mb-4 flex-grow-1 mt-3">
                                <?php foreach($servicesMap as $slug => $name): ?>
                                    <?php if(in_array($slug, $allowed)): ?>
                                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> <strong>Unlimited</strong> <?= htmlspecialchars($name) ?></li>
                                    <?php else: ?>
                                        <li class="mb-2 text-muted opacity-50"><i class="fas fa-times me-2"></i> <del><?= htmlspecialchars($name) ?></del></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                            
                            <button class="btn btn-primary btn-lg fw-bold w-100 mt-auto rounded-pill shadow-sm" onclick="buyB2CPlan(<?= $plan['id'] ?>, <?= $plan['price'] ?>, '<?= addslashes($plan['plan_name']) ?>')">
                                Subscribe Now
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($b2c_plans)): ?>
                    <div class="col-12 text-center text-muted py-5"><i class="fas fa-box-open fa-3x mb-3"></i><br>No digital packages available right now.</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- B2B TAB -->
        <div class="tab-pane fade" id="b2b" role="tabpanel">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-5">
                            <h3 class="text-center fw-bold text-dark mb-4"><i class="fas fa-rocket text-warning"></i> Start Your Own Digital Portal</h3>
                            <p class="text-center text-muted mb-5">Take an annual subscription and launch your own brand of software. (Price: ₹5000 / Year)</p>
                            
                            <form id="subscriptionForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="fw-bold">Your desired domain name (eg mybusiness.com)</label>
                                        <input type="text" id="domain_name" class="form-control" placeholder="example.com" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="fw-bold">Name of admin</label>
                                        <input type="text" id="admin_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="fw-bold">Admin email</label>
                                        <input type="email" id="admin_email" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="fw-bold">Password (for login)</label>
                                        <input type="password" id="admin_password" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-warning btn-lg px-5 fw-bold w-100 rounded-pill"><i class="fas fa-credit-card"></i> Pay ₹5000 & Create Portal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
.plan-card:hover { transform: translateY(-10px); box-shadow: 0 1rem 3rem rgba(0,0,0,.15)!important; }
.transition-all { transition: all 0.3s ease; }
</style>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
// ---------- B2C PLAN CHECKOUT ----------
function buyB2CPlan(planId, price, planName) {
    if(price <= 0) return alert("Invalid price.");
    var options = {
        "key": "rzp_test_YOUR_RAZORPAY_KEY", 
        "amount": price * 100, // in paise
        "currency": "INR",
        "name": "BR Online Services",
        "description": planName + " Package",
        "handler": function (response) {
            processB2CBuy(planId, response.razorpay_payment_id);
        },
        "theme": { "color": "#0d6efd" }
    };
    var rzpB2C = new Razorpay(options);
    rzpB2C.open();
}

function processB2CBuy(planId, paymentId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'app/actions.php';
    
    const fields = { action: 'buy_b2c_plan', plan_id: planId, payment_id: paymentId };
    for (const key in fields) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = fields[key];
        form.appendChild(input);
    }
    document.body.appendChild(form);
    form.submit();
}

// ---------- B2B PORTAL CHECKOUT ----------
document.getElementById('subscriptionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var options = {
        "key": "rzp_test_YOUR_RAZORPAY_KEY",
        "amount": "500000",
        "currency": "INR",
        "name": "BR Online Services",
        "description": "Yearly Portal Subscription",
        "handler": function (response) {
            processMagicCreation(response.razorpay_payment_id);
        },
        "theme": { "color": "#ffc107" }
    };
    var rzpB2B = new Razorpay(options);
    rzpB2B.open();
});

function processMagicCreation(payment_id) {
    const data = {
        payment_id: payment_id,
        domain_name: document.getElementById('domain_name').value,
        name: document.getElementById('admin_name').value,
        email: document.getElementById('admin_email').value,
        password: document.getElementById('admin_password').value
    };

    fetch('app/process_subscription.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        if(result.success) {
            alert("🎉 Congratulations! Your portal and admin account have become automatic. Now you can login.");
            window.location.href = "?page=dashboard";
        } else {
            alert("❌ Error: " + result.message);
        }
    });
}
</script>

<?php 
// Include B2C Footer
if (file_exists(__DIR__ . '/includes/b2c_footer.php')) {
    require_once __DIR__ . '/includes/b2c_footer.php';
}
?>