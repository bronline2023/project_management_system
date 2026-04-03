<?php
/**
 * views/wallet_recharge.php
 * Online & Manual Wallet Recharge
 */
?>
<div class="container mt-4" style="max-width: 800px;">
    
    <?php if (isset($_SESSION['status_message'])) { echo $_SESSION['status_message']; unset($_SESSION['status_message']); } ?>

    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-dark text-white text-center py-4 rounded-top-4">
            <h3 class="m-0 fw-bold"><i class="fas fa-wallet text-success"></i> Wallet Recharge</h3>
            <p class="m-0 mt-2 opacity-75">Current Balance: <b>₹<?= number_format($_SESSION['wallet_balance'] ?? 0, 2) ?></b></p>
        </div>
        <div class="card-body p-0">
            <!-- TABS -->
            <ul class="nav nav-pills nav-fill bg-light p-2 rounded-0 border-bottom" id="rechargeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold text-dark" id="online-tab" data-bs-toggle="pill" data-bs-target="#online-pills" type="button" role="tab" aria-controls="online-pills" aria-selected="true"><i class="fas fa-credit-card me-2 text-primary"></i> Online Payment</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-dark" id="manual-tab" data-bs-toggle="pill" data-bs-target="#manual-pills" type="button" role="tab" aria-controls="manual-pills" aria-selected="false"><i class="fas fa-university me-2 text-warning"></i> Manual Bank Transfer</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-dark" id="points-tab" data-bs-toggle="pill" data-bs-target="#points-pills" type="button" role="tab" aria-controls="points-pills" aria-selected="false"><i class="fas fa-star me-2 text-warning"></i> Buy Points</button>
                </li>
            </ul>

            <!-- TAB CONTENT -->
            <div class="tab-content p-4" id="rechargeTabsContent">
                
                <!-- ONLINE PAYMENT TAB -->
                <div class="tab-pane fade show active text-center" id="online-pills" role="tabpanel" aria-labelledby="online-tab">
                    <h5 class="text-muted mb-4">Select the recharge amount</h5>
                    
                    <div class="d-flex justify-content-center gap-3 mb-4">
                        <button class="btn btn-outline-primary fw-bold px-4 py-2" onclick="setAmount(100)">₹100</button>
                        <button class="btn btn-outline-primary fw-bold px-4 py-2" onclick="setAmount(500)">₹500</button>
                        <button class="btn btn-outline-primary fw-bold px-4 py-2" onclick="setAmount(1000)">₹1000</button>
                    </div>

                    <div class="input-group mb-4" style="max-width: 300px; margin: auto;">
                        <span class="input-group-text bg-light fw-bold fs-5">₹</span>
                        <input type="number" id="recharge_amount" class="form-control form-control-lg text-center fw-bold text-dark" placeholder="Or enter amount">
                    </div>

                    <button class="btn btn-success btn-lg w-100 fw-bold shadow-sm" onclick="startPayment()">
                        <i class="fas fa-lock me-2"></i> Pay Securely Online
                    </button>
                </div>

                <!-- MANUAL PAYMENT TAB -->
                <div class="tab-pane fade" id="manual-pills" role="tabpanel" aria-labelledby="manual-tab">
                    <div class="alert alert-info border-info fw-bold">
                        <i class="fas fa-info-circle me-2"></i> After making the payment to the company account / UPI given below, upload the amount and screenshot. Admin will check and deposit the balance.
                    </div>
                    
                    <?php 
                    $pdo = connectDB();
                    $s = fetchOne($pdo, "SELECT * FROM settings WHERE id = 1 LIMIT 1");
                    ?>
                    <div class="row mb-4 text-start">
                        <div class="col-md-7">
                            <div class="card bg-light border-0 h-100 shadow-sm">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-university me-2"></i>Bank Account Details</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small">Bank Name:</span>
                                        <span class="fw-bold small"><?= htmlspecialchars($s['manual_bank_name'] ?? 'Not Set') ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small">Account No:</span>
                                        <span class="fw-bold small text-primary" style="letter-spacing: 1px;"><?= htmlspecialchars($s['manual_account_number'] ?? 'Not Set') ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small">IFSC Code:</span>
                                        <span class="fw-bold small text-success"><?= htmlspecialchars($s['manual_ifsc_code'] ?? 'Not Set') ?></span>
                                    </div>
                                    <?php if(!empty($s['manual_micr_code'])): ?>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted small">MICR Code:</span>
                                        <span class="fw-bold small"><?= htmlspecialchars($s['manual_micr_code']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5 mt-3 mt-md-0">
                            <div class="card bg-light border-0 h-100 shadow-sm">
                                <div class="card-body text-center p-4">
                                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-qrcode me-2"></i>UPI / QR Payment</h6>
                                    <?php if(!empty($s['manual_qr_code_url'])): ?>
                                        <div class="mb-3">
                                            <img src="<?= htmlspecialchars($s['manual_qr_code_url']) ?>" alt="Payment QR" class="img-fluid rounded border shadow-sm p-1 bg-white" style="max-height: 120px;">
                                        </div>
                                    <?php endif; ?>
                                    <p class="mb-1 fw-bold text-dark" style="font-size: 1.1rem;"><?= htmlspecialchars($s['manual_upi_id'] ?? 'Not Set') ?></p>
                                    <div class="badge bg-info-subtle text-info px-3 py-2 rounded-pill"><?= htmlspecialchars($s['manual_upi_name'] ?? 'Manual Transfer') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="app/actions.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="submit_recharge_request">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Amount Paid</label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold">₹</span>
                                <input type="number" name="amount" class="form-control fw-bold text-primary" required min="10" placeholder="0.00">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Payment Screenshot</label>
                            <input type="file" name="screenshot" accept="image/*" class="form-control" required>
                            <small class="text-muted">Max size: 2MB. Clear screenshot required for approval.</small>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 fw-bold shadow-sm text-dark fs-5">
                            <i class="fas fa-upload me-2"></i> Submit Payment Proof
                        </button>
                    </form>
                </div> <!-- END MANUAL TAB -->

                <!-- BUY POINTS TAB -->
                <div class="tab-pane fade text-center" id="points-pills" role="tabpanel" aria-labelledby="points-tab">
                    <div class="alert alert-warning border-warning fw-bold text-dark mb-4">
                        <i class="fas fa-info-circle me-1"></i> You can use your wallet balance to buy reward points. Reward points are used for digital design services.
                    </div>
                    
                    <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);">
                        <div class="card-body p-5">
                            <i class="fas fa-star text-warning fa-4x mb-3"></i>
                            <h1 class="display-4 fw-bold text-dark mb-2">25 <span class="fs-2 text-muted">Pts</span></h1>
                            <h4 class="text-dark fw-bold mb-4">at just ₹100.00</h4>
                            <div class="p-2 bg-white bg-opacity-50 rounded-pill border border-warning d-inline-block px-4 mb-4">
                                <span class="text-muted small fw-bold text-uppercase">Recharge Reward System</span>
                            </div>
                            
                            <form action="app/actions.php" method="POST" onsubmit="return confirm('Confirm purchasing 25 points for ₹100?');">
                                <input type="hidden" name="action" value="buy_points">
                                <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold shadow-sm rounded-pill py-3">
                                    <i class="fas fa-shopping-cart me-2"></i> Confirm Purchase
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <p class="text-muted small">Points will be credited instantly from your current wallet balance.</p>
                </div> <!-- END POINTS TAB -->

            </div>
        </div>
    </div>
</div>

<!-- Razorpay Script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
function setAmount(amt) {
    document.getElementById('recharge_amount').value = amt;
}

function startPayment() {
    let amount = document.getElementById('recharge_amount').value;
    if(!amount || amount < 10) { alert("Recharge of at least ₹10 is mandatory."); return; }

    var options = {
        "key": "rzp_test_YOUR_KEY_HERE", // ⚠️ Enter your Razorpay Key
        "amount": amount * 100, // Convert to money
        "currency": "INR",
        "name": "BR Online Wallet",
        "description": "Wallet Recharge",
        "handler": function (response) {
            fetch('app/process_recharge.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ payment_id: response.razorpay_payment_id, amount: amount })
            })
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    alert("🎉 Your wallet has been recharged successfully!");
                    location.reload();
                } else {
                    alert("❌ Error: " + res.message);
                }
            });
        },
        "theme": { "color": "#10b981" }
    };
    var rzp1 = new Razorpay(options);
    rzp1.open();
}
</script>