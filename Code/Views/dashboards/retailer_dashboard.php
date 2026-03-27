<?php
/**
 * views/dashboards/retailer_dashboard.php
 * Custom B2C Retailer / Branch Dashboard View
 */
?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold"><i class="fas fa-user-circle text-primary"></i> User Dashboard</h2>
            <p class="text-muted">Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>!</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 border-start border-warning border-4">
                <div class="card-body">
                    <h5 class="text-muted fw-bold">Wallet Balance</h5>
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="fw-bold text-dark mb-0">₹<?= number_format($_SESSION['wallet_balance'] ?? 0, 2) ?></h3>
                        <a href="?page=wallet_recharge" class="btn btn-sm btn-outline-success"><i class="fas fa-plus"></i> Recharge</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8 mb-4">
            <div class="card bg-dark text-white shadow-sm border-0">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="fw-bold text-warning"><i class="fas fa-crown"></i> Upgrade to B2B Subscription</h4>
                        <p class="mb-0">Start your own independent portal with a custom domain and full admin rights.</p>
                    </div>
                    <div>
                        <a href="?page=buy_subscription" class="btn btn-warning fw-bold px-4 py-2">View Plans</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Available Services Row -->
    <div class="row mt-2">
        <div class="col-12 mb-3">
            <h4 class="fw-bold text-muted">Quick Access Tools</h4>
        </div>
        
        <?php 
        $user_permissions = $_SESSION['user_permissions'] ?? []; 
        $isAdmin = in_array($_SESSION['user_role'], ['master_admin', 'admin']);
        $pdo = connectDB();
        $services = $pdo->query("SELECT * FROM digital_service_rates WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Icon definitions for various digital services
        $serviceIcons = [
            'poster_studio' => ['icon' => 'fa-paint-brush', 'color' => 'text-danger'],
            'resume_builder' => ['icon' => 'fa-file-alt', 'color' => 'text-info'],
            'smart_card' => ['icon' => 'fa-id-card', 'color' => 'text-success'],
            'passport_photo' => ['icon' => 'fa-camera-retro', 'color' => 'text-primary'],
            'document_converter' => ['icon' => 'fa-file-pdf', 'color' => 'text-warning'],
            'size_converter' => ['icon' => 'fa-compress-arrows-alt', 'color' => 'text-secondary'],
            'photo_studio' => ['icon' => 'fa-images', 'color' => 'text-purple']
        ];
        ?>
        
        <?php foreach($services as $svc): 
            $slug = $svc['service_slug'];
            $iconInfo = $serviceIcons[$slug] ?? ['icon' => 'fa-star', 'color' => 'text-primary'];
            // B2C portal logic check: standard users should see services even if they don't have explicit permission in B2B
            // Let's rely on the global service paywall / logic handles access
            // For B2C, mostly we just show all services and let the paywall stop them, OR check permissions
            $can_view = $isAdmin || $_SESSION['user_role'] === 'retailer' || in_array($slug, $user_permissions);
        ?>
            <?php if ($can_view): ?>
            <div class="col-md-3 mb-4">
                <a href="?page=<?= htmlspecialchars($slug) ?>" class="card shadow border-0 text-decoration-none text-center p-4 text-dark dashboard-hover rounded-4 h-100">
                    <div class="card-body p-0 d-flex flex-column align-items-center justify-content-center">
                        <div class="icon-circle mb-3 rounded-circle d-flex align-items-center justify-content-center bg-light" style="width: 70px; height: 70px;">
                            <i class="fas <?= $iconInfo['icon'] ?> <?= $iconInfo['color'] ?> fa-2x"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($svc['service_name']) ?></h6>
                        <span class="badge bg-primary rounded-pill mt-2 px-3 py-1 fw-normal">Launch Tool</span>
                    </div>
                </a>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <style>
        .dashboard-hover { transition: all 0.3s ease; }
        .dashboard-hover:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important; }
        .text-purple { color: #6f42c1; }
    </style>
</div>
