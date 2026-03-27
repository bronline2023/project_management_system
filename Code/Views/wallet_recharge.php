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
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold text-primary"><i class="fas fa-university"></i> Bank Account Details</h6>
                                    <p class="mb-1 small"><strong>Bank:</strong> HDFC Bank Ltd.</p>
                                    <p class="mb-1 small"><strong>A/C No:</strong> 50200012345678</p>
                                    <p class="mb-0 small"><strong>IFSC:</strong> HDFC0001234</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mt-3 mt-md-0">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body text-center">
                                    <h6 class="fw-bold text-primary"><i class="fas fa-qrcode"></i> UPI Payment Details</h6>
                                    <p class="mb-2 fw-bold text-dark fs-5">bronline@ybl</p>
                                    <p class="mb-0 small text-muted">Scan or use above UPI ID.</p>
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