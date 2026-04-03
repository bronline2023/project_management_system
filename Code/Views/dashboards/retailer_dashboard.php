<?php
/**
 * views/dashboards/retailer_dashboard.php
 * ROBUST RETAILER DASHBOARD - FINAL FIX
 */

// Global context safety
if (!isset($pdo)) {
    $pdo = connectDB();
}

// Normalize role name to lowercase for consistent checking
$user_role = strtolower($_SESSION['user_role'] ?? 'retailer');
$user_name = $_SESSION['user_name'] ?? 'Retailer';
$wallet_balance = (float)($_SESSION['wallet_balance'] ?? 0);

// Safe permission loading
require_once MODELS_PATH . 'roles.php';
$dash_perms = [];
try {
    $dash_perms = getDashboardPermissionsForRole($user_role);
} catch (Exception $e) { /* admin fallback */ }

// Ensure all keys exist
$p = [
    'show_notice_board' => $dash_perms['show_notice_board'] ?? true,
    'show_wallet_card' => $dash_perms['show_wallet_card'] ?? true,
    'show_subscription_status' => $dash_perms['show_subscription_status'] ?? true,
    'show_active_services' => $dash_perms['show_active_services'] ?? true
];

?>
<div class="container-fluid py-4 bg-light min-vh-100">
    <!-- 1. Notice Board -->
    <?php if ($p['show_notice_board']): ?>
        <?php if (file_exists(VIEWS_PATH . 'components/notice_board.php')) include VIEWS_PATH . 'components/notice_board.php'; ?>
    <?php endif; ?>

    <div class="mb-4">
        <h2 class="fw-bold text-dark"><i class="fas fa-store text-primary me-2"></i>Retailer Dashboard</h2>
        <p class="text-muted">Hello, <span class="fw-bold text-primary"><?= htmlspecialchars($user_name) ?></span>! Manage your wallet and services below.</p>
    </div>

    <!-- 2. Wallet & Subscription Row -->
    <div class="row g-4 mb-4">
        <?php if ($p['show_wallet_card']): ?>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-warning border-5 rounded-4 h-100">
                <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                    <div class="text-muted small fw-bold text-uppercase mb-2">My Balance</div>
                    <div class="h2 fw-bold text-dark mb-3">₹<?= number_format($wallet_balance, 2) ?></div>
                    <a href="?page=wallet_recharge" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fas fa-plus-circle me-1"></i> Recharge Wallet
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($p['show_subscription_status']): ?>
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4 text-white p-4 h-100" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
                <div class="d-md-flex align-items-center justify-content-between">
                    <div class="mb-3 mb-md-0">
                        <h4 class="fw-bold text-warning mb-2"><i class="fas fa-crown me-2"></i>Start Your Own Portal</h4>
                        <p class="mb-0 text-white-50">Launch a full-featured B2B portal with your own domain and branding. Get 100% control over your business.</p>
                    </div>
                    <a href="?page=buy_subscription" class="btn btn-light text-primary fw-bold px-4 py-2 rounded-pill shadow">Learn More</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- 3. Services Section -->
    <?php if ($p['show_active_services']): ?>
    <div class="row g-4">
        <div class="col-12">
            <h4 class="fw-bold text-dark d-flex align-items-center">
                <i class="fas fa-th-large text-primary me-2"></i>My Digital Services
            </h4>
        </div>
        
        <?php 
        $user_permissions = $_SESSION['user_permissions'] ?? [];
        $isAdmin = in_array($user_role, ['master_admin', 'admin']);
        $services = [];
        try {
            $services = $pdo->query("SELECT * FROM digital_service_rates WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { /* table missing error */ }

        // Icon Mapping
        $iconMap = [
            'poster_studio' => ['i' => 'fa-paint-brush', 'c' => 'text-danger', 'b' => 'rgba(239, 68, 68, 0.1)'],
            'resume_builder' => ['i' => 'fa-file-alt', 'c' => 'text-info', 'b' => 'rgba(6, 182, 212, 0.1)'],
            'smart_card' => ['i' => 'fa-id-card', 'c' => 'text-success', 'b' => 'rgba(34, 197, 94, 0.1)'],
            'passport_photo' => ['i' => 'fa-camera-retro', 'c' => 'text-primary', 'b' => 'rgba(59, 130, 246, 0.1)'],
            'document_converter' => ['i' => 'fa-file-pdf', 'c' => 'text-warning', 'b' => 'rgba(245, 158, 11, 0.1)'],
            'size_converter' => ['i' => 'fa-compress-arrows-alt', 'c' => 'text-secondary', 'b' => 'rgba(100, 116, 139, 0.1)'],
            'photo_studio' => ['i' => 'fa-images', 'c' => 'text-purple', 'b' => 'rgba(168, 85, 247, 0.1)']
        ];

        if (empty($services)): ?>
            <div class="col-12 text-center py-5">
                <div class="alert alert-info py-4 rounded-4 border-0 shadow-sm">
                    <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
                    <p class="mb-0 fw-bold">No digital services are currently enabled for your portal.</p>
                </div>
            </div>
        <?php else: 
            foreach($services as $svc): 
                $slug = $svc['service_slug'];
                $conf = $iconMap[$slug] ?? ['i' => 'fa-star', 'c' => 'text-primary', 'b' => '#f1f5f9'];
                $can_open = $isAdmin || $user_role === 'retailer' || in_array($slug, $user_permissions);
                
                if ($can_open):
        ?>
            <div class="col-xl-3 col-lg-4 col-md-6">
                <a href="?page=<?= htmlspecialchars($slug) ?>" class="card h-100 border-0 shadow-sm text-decoration-none p-4 rounded-4 text-center svc-card">
                    <div class="card-body p-0 d-flex flex-column align-items-center">
                        <div class="icon-sq mb-3 rounded-4 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background: <?= $conf['b'] ?>;">
                            <i class="fas <?= $conf['i'] ?> <?= $conf['c'] ?> fa-2x"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($svc['service_name']) ?></h6>
                        <span class="btn btn-sm btn-primary rounded-pill mt-3 px-4 shadow-sm">Open Service</span>
                    </div>
                </a>
            </div>
        <?php 
                endif; 
            endforeach; 
        endif; 
        ?>
    </div>
    <?php endif; ?>
    
    <style>
        .svc-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .svc-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.08) !important; background: #fff !important; }
        .text-purple { color: #8b5cf6; }
    </style>
</div>
