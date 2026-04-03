<?php
/**
 * views/dashboards/district_manager_dashboard.php
 * Custom B2B District Manager Dashboard View
 */
require_once MODELS_PATH . 'roles.php';
$dash_perms = getDashboardPermissionsForRole($_SESSION['user_role']);
?>
<div class="container-fluid py-4">
    <?php if ($dash_perms['show_notice_board']) include VIEWS_PATH . 'components/notice_board.php'; ?>
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold"><i class="fas fa-sitemap text-primary"></i> District Manager Dashboard</h2>
            <p class="text-muted">Welcome to your operational overview, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>!</p>
        </div>
    </div>
    <div class="row">
        <?php if ($dash_perms['show_wallet_card']): ?>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body">
                    <h5 class="text-muted fw-bold">Wallet Balance</h5>
                    <h3 class="fw-bold text-dark">₹<?= number_format($_SESSION['wallet_balance'] ?? 0, 2) ?></h3>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($dash_perms['show_user_client_summary']): ?>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 border-start border-info border-4">
                <div class="card-body">
                    <h5 class="text-muted fw-bold">My Retailers</h5>
                    <h3 class="fw-bold text-dark"><a href="?page=manage_retailers" class="text-decoration-none">View Network <i class="fas fa-arrow-right"></i></a></h3>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
