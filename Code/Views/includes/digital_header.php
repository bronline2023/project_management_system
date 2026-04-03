<?php
/**
 * views/includes/digital_header.php
 * Unified header for all digital studio tools showing advanced stats.
 */
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config.php';
require_once MODELS_PATH . 'db.php';

$pdo = connectDB();
$u = fetchOne($pdo, "SELECT balance, poster_points, role FROM users WHERE id = ?", [$_SESSION['user_id'] ?? 0]);
$user_role_check = $u['role'] ?? $_SESSION['user_role'] ?? 'user';
$is_admin_check = in_array($user_role_check, ['admin', 'master_admin']);

$wallet_display = $is_admin_check ? "Unlimited" : "₹" . number_format((float)($u['balance'] ?? 0), 2);
$points_display = $is_admin_check ? "Unlimited" : number_format((int)($u['poster_points'] ?? 0)) . " Pts";

$settings = fetchOne($pdo, "SELECT app_name, app_logo_url FROM settings LIMIT 1");
$is_logged_in = isset($_SESSION['user_id']);

// Also check active subscription
$sub_badge = "";
if ($is_logged_in) {
    $sub_query = "SELECT p.plan_name FROM user_subscriptions us JOIN b2c_subscription_plans p ON us.plan_id = p.id WHERE us.user_id = ? AND us.status = 'active' AND us.end_date >= NOW() LIMIT 1";
    $sub = fetchOne($pdo, $sub_query, [$_SESSION['user_id']]);
    $sub_badge = $sub 
        ? "<span class='badge bg-success shadow-sm px-3 py-2 border border-success rounded-pill'><i class='fas fa-crown text-warning me-1'></i> " . htmlspecialchars($sub['plan_name']) . "</span>" 
        : "<a href='?page=buy_subscription' class='badge bg-warning text-dark text-decoration-none shadow-sm px-3 py-2 border border-warning rounded-pill'><i class='fas fa-bolt me-1'></i> Upgrade PRO</a>";
}

$back_url = $is_logged_in ? "?page=dashboard" : "?page=b2c_home";
$back_text = $is_logged_in ? "Dashboard" : "Back to Website";

// --- [ GOOGLE ADSENSE INTEGRATION ] ---
require_once __DIR__ . '/../../Core_Logic/App/ads_helper.php';
$current_service_slug = basename($_SERVER['PHP_SELF'], '.php');
$show_ads = should_show_ads($current_service_slug);
$ads_codes = get_adsense_codes();
?>
<!-- Google AdSense Global Script -->
<?php if ($show_ads && !empty($ads_codes['global'])): ?>
    <?= $ads_codes['global'] ?>
<?php endif; ?>
<!-- Ensure Bootstrap is available for digital tools that omit the main header -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- SweetAlert2 for Premium Popups -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Premium SweetAlert Global Styling */
    .swal2-popup {
        border-radius: 24px !important;
        padding: 2.5rem !important;
        font-family: 'Outfit', sans-serif !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(15px) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
    }
    .swal2-title { font-weight: 800 !important; font-size: 1.8rem !important; color: #1e293b !important; }
    .swal2-html-container { font-weight: 500 !important; color: #64748b !important; }
    .swal2-confirm {
        background: linear-gradient(135deg, #4f46e5, #0ea5e9) !important;
        border-radius: 14px !important;
        padding: 0.8rem 2rem !important;
        font-weight: 700 !important;
        box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4) !important;
    }
    .swal2-cancel {
        border-radius: 14px !important;
        font-weight: 700 !important;
    }
</style>

<div class="digital-studio-header p-3 d-flex justify-content-between align-items-center shadow-sm" style="background-color: #ffffff; border-bottom: 3px solid #3b82f6; position: relative; z-index: 1000;">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <a href="<?= $back_url ?>" class="btn btn-outline-primary btn-sm rounded-pill fw-bold" style="letter-spacing: 0.5px;"><i class="fas fa-arrow-left me-1"></i> <?= $back_text ?></a>
        
        <div class="d-none d-md-flex align-items-center bg-light rounded px-3 py-1 ms-2" style="border: 1px solid #e2e8f0;">
            <?php if (!empty($settings['app_logo_url'])): ?>
                <img src="<?= htmlspecialchars($settings['app_logo_url']) ?>" alt="Logo" style="height: 32px; object-fit: contain; margin-right: 10px;">
            <?php else: ?>
                <i class="fas fa-globe text-primary fs-4 me-2"></i>
            <?php endif; ?>
            <span class="mb-0 fw-bolder text-dark text-uppercase" style="letter-spacing: 0.5px; font-size: 15px;"><?= htmlspecialchars($settings['app_name'] ?? 'B R Online') ?></span>
        </div>
        
        <h5 class="mb-0 fw-bold d-none d-lg-block text-uppercase text-primary ms-3" style="letter-spacing: 1px; border-left: 2px solid #cbd5e1; padding-left: 15px;"><i class="fas fa-magic text-warning me-2"></i> <?= htmlspecialchars($page_title ?? 'Digital Studio') ?></h5>
    </div>
    
    <div class="d-flex align-items-center gap-2 gap-md-3">
        <?php if($is_logged_in): ?>
            <?= $sub_badge ?>
            <div class="px-3 py-2 bg-light rounded-pill border border-primary d-none d-md-flex gap-3 text-dark fw-bold shadow-sm" style="font-size: 0.95rem;">
                <span title="Wallet Balance" class="d-flex align-items-center"><i class="fas fa-wallet text-success me-2 fs-5"></i> <span id="dh_wallet"><?= $wallet_display ?></span></span>
                <span class="border-start border-secondary ps-3 d-flex align-items-center" title="Reward Points"><i class="fas fa-star text-warning me-2 fs-5"></i> <span id="dh_points"><?= $points_display ?></span></span>
            </div>
        <?php else: ?>
            <a href="?page=login" class="btn btn-primary btn-sm rounded-pill fw-bold shadow-sm px-3" style="letter-spacing: 0.5px;"><i class="fas fa-sign-in-alt me-1"></i> Login to Save & Download</a>
        <?php endif; ?>
    </div>
    
    <!-- Google AdSense Ad Unit -->
    <?php if ($show_ads && !empty($ads_codes['ad_unit'])): ?>
        <div class="ads-container text-center py-2" style="background: #f1f5f9; border-top: 1px solid #e2e8f0; width: 100%; position: absolute; bottom: -80px; left: 0;">
            <?= $ads_codes['ad_unit'] ?>
        </div>
    <?php endif; ?>
</div>

<style>
.shadow-inset { box-shadow: inset 0 2px 4px rgba(0,0,0,0.5); }
.canvas-container canvas { max-width: none !important; max-height: none !important; }
</style>
