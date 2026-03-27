<?php
/**
 * views/includes/service_paywall.php
 * Universal Paywall Component for Digital Services (AJAX Version).
 */

function enforce_service_paywall($service_slug) {
    if (session_status() == PHP_SESSION_NONE) session_start();
    
    $user_role = $_SESSION['user_role'] ?? 'guest';
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Admins always have free access
    if (in_array($user_role, ['master_admin', 'admin'])) {
        echo "<script>const SysAuthData = { is_unlocked: true };</script>";
        return;
    }
    
    // Check if session unlocked
    if (isset($_SESSION['unlocked_services'][$service_slug]) && $_SESSION['unlocked_services'][$service_slug] === true) {
        echo "<script>const SysAuthData = { is_unlocked: true };</script>";
        return;
    }

    $pdo = connectDB();
    
    // 1. Check if user has an active B2C subscription that includes this service
    $stmt = $pdo->prepare("SELECT p.allowed_services FROM user_subscriptions us JOIN b2c_subscription_plans p ON us.plan_id = p.id WHERE us.user_id = ? AND us.status = 'active' AND us.end_date >= NOW() ORDER BY us.end_date DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $active_sub = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($active_sub) {
        $allowed = json_decode($active_sub['allowed_services'], true) ?: [];
        if (in_array($service_slug, $allowed)) {
            $_SESSION['unlocked_services'][$service_slug] = true;
            echo "<script>const SysAuthData = { is_unlocked: true };</script>";
            return;
        }
    }

    // 2. Fetch the rate for this service
    $stmt = $pdo->prepare("SELECT service_name, price, points_price, is_active FROM digital_service_rates WHERE service_slug = ?");
    $stmt->execute([$service_slug]);
    $service_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service_info || !$service_info['is_active']) {
        echo "<div class='container mt-5'><div class='alert alert-danger fw-bold text-center'><i class='fas fa-exclamation-triangle'></i> This service is currently disabled or unavailable.</div></div>";
        exit;
    }
    
    $rate = (float)$service_info['price'];
    $pts_rate = (int)$service_info['points_price'];
    
    // If rate and points are both configured to 0, it's free
    if ($rate <= 0 && $pts_rate <= 0) {
        $_SESSION['unlocked_services'][$service_slug] = true;
        echo "<script>const SysAuthData = { is_unlocked: true };</script>";
        return;
    }

    // Fetch user wallet balance accurately from DB
    $user_data = fetchOne($pdo, "SELECT balance, poster_points, custom_poster_rate FROM users WHERE id = ?", [$user_id]);
    $balance = (float)($user_data['balance'] ?? 0);
    $points = (int)($user_data['poster_points'] ?? 0);

    if ($service_slug === 'poster_studio' && !empty($user_data['custom_poster_rate'])) {
        $rate = (float)$user_data['custom_poster_rate'];
    }

    echo "<script>const SysAuthData = { is_unlocked: false, service_slug: '{$service_slug}' };</script>";

    // Evaluate Can Afford First
    $canAffordPoints = ($pts_rate > 0 && $points >= $pts_rate);
    $canAffordWallet = ($rate > 0 && $balance >= $rate);
    $canAfford = $canAffordPoints || $canAffordWallet;

    // RENDER THE PAYWALL (Hidden initially, triggered on download via JS)
    ?>
    <style>
        .paywall-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.95); z-index: 1050; display: none; align-items: center; justify-content: center; backdrop-filter: blur(10px); }
        .paywall-card { background: #ffffff; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); width: 100%; max-width: 500px; padding: 2.5rem; text-align: center; position: relative; overflow: hidden; }
        .paywall-icon { font-size: 4rem; color: #3b82f6; margin-bottom: 1.5rem; text-shadow: 0 10px 15px rgba(59,130,246,0.3); }
        .paywall-title { font-weight: 800; font-size: 1.75rem; color: #1e293b; margin-bottom: 0.5rem; }
        .paywall-desc { color: #64748b; font-size: 1rem; margin-bottom: 2rem; line-height: 1.6; }
        .price-tag { font-size: 3.5rem; font-weight: 900; color: #10b981; line-height: 1; margin-bottom: 0.5rem; }
        .balance-pill { background: #f1f5f9; padding: 0.5rem 1rem; border-radius: 50px; display: inline-block; font-weight: 700; color: #475569; margin-bottom: 2rem; }
        .balance-amount { color: <?= $balance >= $rate ? '#10b981' : '#ef4444' ?>; }
        .unlock-btn { padding: 1rem 2rem; font-size: 1.1rem; font-weight: 700; border-radius: 50px; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s; cursor: pointer; }
        .unlock-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(59,130,246,0.4); }
        .sub-link { display: block; margin-top: 1.5rem; color: #3b82f6; font-weight: 600; text-decoration: none; transition: all 0.2s; cursor: pointer; }
        .sub-link:hover { color: #2563eb; text-decoration: underline; }
    </style>

    <div class="paywall-overlay" id="servicePaywallOverlay">
        <div class="paywall-card">
            <?php 
            $force_show_paywall = false;
            if ($user_id > 0 && !$canAfford) {
                $force_show_paywall = true;
            } elseif ($user_id <= 0 && isset($_COOKIE['guest_service_used'])) {
                $force_show_paywall = true;
            }
            ?>
            <button id="paywallCloseBtn" onclick="document.getElementById('servicePaywallOverlay').style.display='none'" style="position:absolute; top:15px; right:20px; background:none; border:none; font-size:1.5rem; color:#94a3b8; cursor:pointer; <?= $force_show_paywall ? 'display:none;' : '' ?>"><i class="fas fa-times"></i></button>

            <i class="fas fa-lock paywall-icon"></i>
            <h2 class="paywall-title">Premium Service Access</h2>
            <p class="paywall-desc">Unlock <strong><?= htmlspecialchars($service_info['service_name']) ?></strong> to confidently export, download, and utilize this premium document.</p>
            
            <?php if ($user_id > 0): ?>
                
                <div class="row gx-2 mb-4">
                    <?php if ($rate > 0): ?>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-4 h-100 border border-success">
                            <h6 class="text-success fw-bold text-uppercase small"><i class="fas fa-wallet"></i> Wallet Method</h6>
                            <h3 class="fw-bold mb-0">₹<?= number_format($rate, 2) ?></h3>
                            <small class="text-muted d-block mt-2">Deduct from Wallet</small>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($pts_rate > 0): ?>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-4 h-100 border border-warning">
                            <h6 class="text-warning fw-bold text-uppercase small"><i class="fas fa-star"></i> Points Method</h6>
                            <h3 class="fw-bold mb-0"><?= number_format($pts_rate) ?> Pts</h3>
                            <small class="text-muted d-block mt-2">Deduct from Rewards</small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex justify-content-center gap-3 mb-4">
                    <div class="balance-pill m-0 px-3 py-2 bg-success bg-opacity-10 text-success border border-success">
                        <i class="fas fa-wallet me-1"></i> Balance: ₹<?= number_format($balance, 2) ?>
                    </div>
                    <div class="balance-pill m-0 px-3 py-2 bg-warning bg-opacity-10 text-dark border border-warning">
                        <i class="fas fa-star me-1 text-warning"></i> Points: <?= number_format($points) ?>
                    </div>
                </div>

                <?php if ($canAfford): ?>
                    <button type="button" onclick="sysUnlockViaAjax('unlock_digital_service')" class="btn btn-primary w-100 unlock-btn border-0 shadow-sm" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                        <i class="fas fa-unlock-alt me-2"></i> Pay & Unlock Tool
                    </button>
                    <p class="text-muted mt-3 small fw-bold"><i class="fas fa-magic text-info"></i> The system will auto-deduct points first natively, otherwise wallet balance.</p>
                <?php else: ?>
                    <div class="alert alert-danger bg-danger text-white border-0 rounded-4 p-3 mb-4 shadow-sm text-start">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-circle fa-2x me-3 opacity-75"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Insufficient Funds</h6>
                                <p class="mb-0 small opacity-75">You do not have enough Wallet minimums (₹<?= number_format($rate,2) ?>) or Reward Points (<?= $pts_rate ?> Pts) to proceed.</p>
                            </div>
                        </div>
                    </div>
                    <a href="?page=wallet_recharge" class="btn btn-success w-100 unlock-btn border-0 shadow-sm d-block text-decoration-none">
                        <i class="fas fa-plus-circle me-2"></i> Recharge Wallet
                    </a>
                <?php endif; ?>
                
                
                <a href="?page=buy_subscription" class="sub-link mt-4">
                    <i class="fas fa-crown me-1"></i> Or get infinite access with a subscription!
                </a>
            <?php else: ?>
                <?php if (!isset($_COOKIE['guest_service_used'])): ?>
                    <div class="alert alert-info border-0 rounded-4 p-3 mb-4 shadow-sm text-center">
                        <h6 class="fw-bold mb-1"><i class="fas fa-gem text-warning me-1"></i> Special Guest Offer</h6>
                        <p class="mb-0 small">You can download directly from this suite for free <strong>ONCE EVERY 24 HOURS</strong> as a guest without signing up!</p>
                    </div>
                    <button type="button" onclick="sysUnlockViaAjax('unlock_guest_digital_service')" class="btn btn-warning w-100 unlock-btn border-0 text-dark fw-bold shadow-sm">
                        <i class="fas fa-gift me-2"></i> Download For Free (1 Left)
                    </button>
                    
                    <a href="?page=b2c_register" class="sub-link mt-4 text-secondary">
                        <i class="fas fa-user-plus me-1"></i> Sign Up to unlock more privileges.
                    </a>
                <?php else: ?>
                    <div class="alert alert-danger border-0 rounded-4 p-4 mb-4 shadow-sm text-center" style="background: #fff1f2; border: 2px dashed #fda4af !important;">
                        <h5 class="fw-bold mb-2 text-danger"><i class="fas fa-hourglass-end me-2"></i>Free Trial Exhausted</h5>
                        <p class="mb-0 text-dark fw-medium">Oops! You have officially consumed your daily free guest pass.</p>
                        <p class="small text-muted mt-2 mb-0">Don't lose your masterpiece! Create an account right now to save your progress and unlock unlimited downloads by choosing a premium Subscription!</p>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="?page=b2c_register" class="btn btn-primary btn-lg unlock-btn border-0 shadow-sm text-decoration-none" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fas fa-user-plus me-2"></i> Sign Up & Get 10 Free Points!
                        </a>
                        <a href="?page=buy_subscription" class="btn btn-success btn-lg unlock-btn border-0 shadow-sm text-decoration-none mt-2" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="fas fa-crown me-2"></i> View Subscriptions
                        </a>
                        <a href="?page=b2c_login" class="btn btn-outline-secondary btn-lg fw-bold rounded-pill mt-2 text-decoration-none">
                            Login To Existing Account
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- AJAX Intercept Logic -->
    <script>
        function sysUnlockViaAjax(actionType) {
            const formData = new URLSearchParams();
            formData.append('action', actionType);
            formData.append('service_slug', SysAuthData.service_slug);

            fetch('<?= BASE_URL ?>Code/Core_Logic/App/ajax_unlock_service.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    SysAuthData.is_unlocked = true;
                    document.getElementById('servicePaywallOverlay').style.display = 'none';
                    if (window.pendingDownloadFunc) {
                        try { window.pendingDownloadFunc(); } catch(e) { console.error("Export Callback Error:", e); }
                        window.pendingDownloadFunc = null;
                    }
                } else {
                    alert('Unlock Failed: ' + data.error);
                    if (actionType === 'unlock_guest_digital_service') {
                        // User might have exhausted guest pass in another tab, force reload to show exhausted UI
                        window.location.reload();
                    }
                }
            }).catch(err => {
                alert('Connection Error! Please check your network and try again.');
            });
        }

        // Setup Document Interceptors
        document.addEventListener("DOMContentLoaded", function() {
            // Target known export and download hooks globally across the apps
            const coreExportHooks = [
                'exportPoster', 'exportCards', 'exportPDF', 'exportToA4', 
                'processCompression', 'startConversion', 'downloadPhoto', 'downloadResume', 'handleExport'
            ];
            
            coreExportHooks.forEach(fnName => {
                if (typeof window[fnName] === 'function') {
                    const originalMethod = window[fnName];
                    window[fnName] = function(...args) {
                        if (!SysAuthData.is_unlocked) {
                            // Suppress default, launch paywall
                            window.pendingDownloadFunc = () => originalMethod.apply(this, args);
                            document.getElementById('servicePaywallOverlay').style.display = 'flex';
                        } else {
                            // User is verified, tunnel through safely
                            originalMethod.apply(this, args);
                        }
                    };
                }
            });

            // Additionally universally target buttons explicitly by class or text if they use inline onclicks that miss the hook
            const exportBtns = document.querySelectorAll('.btn-export, [id*="btnExport"], [onclick*="export" i], [onclick*="download" i]');
            exportBtns.forEach(btn => {
                const originalOnClick = btn.onclick;
                if (originalOnClick && !btn.hasAttribute('data-paywall-hooked')) {
                    btn.setAttribute('data-paywall-hooked', 'true');
                    btn.onclick = function(e) {
                        if (!SysAuthData.is_unlocked) {
                            e.preventDefault();
                            e.stopPropagation();
                            window.pendingDownloadFunc = () => {
                                // If verified, fire the literal original assigned click payload
                                originalOnClick.call(btn, e);
                            };
                            document.getElementById('servicePaywallOverlay').style.display = 'flex';
                        } else {
                            originalOnClick.call(btn, e);
                        }
                    };
                }
            });

            <?php if ($force_show_paywall): ?>
            // FORCE SHOW PAYWALL ON LOAD FOR 0 BALANCE
            setTimeout(() => {
                let overlay = document.getElementById('servicePaywallOverlay');
                overlay.style.display = 'flex';
                overlay.style.backdropFilter = 'blur(15px)';
                overlay.style.background = 'rgba(15, 23, 42, 0.98)';
                overlay.style.zIndex = '999999';
                
                // Block entire document interaction behind the overlay
                let children = document.body.children;
                for(let i=0; i<children.length; i++) {
                    if (children[i].id !== 'servicePaywallOverlay' && children[i].tagName !== 'SCRIPT' && children[i].tagName !== 'STYLE') {
                        children[i].style.filter = 'blur(5px)';
                        children[i].style.pointerEvents = 'none';
                        children[i].style.userSelect = 'none';
                    }
                }
                document.body.style.overflow = 'hidden';
                
                // Remove all close mechanisms
                let closeBtn = document.getElementById('paywallCloseBtn');
                if(closeBtn) closeBtn.remove();
            }, 100);
            <?php endif; ?>
        });
    </script>
    <?php
    // DO NOT EXIT! Let the workspace load natively behind the scenes!
}
?>
