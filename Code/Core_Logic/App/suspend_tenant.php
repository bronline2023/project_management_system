<?php
/**
 * app/suspend_tenant.php
 * Master Admin backend action to suspend or activate a B2B SaaS Tenant
 */
require_once __DIR__ . '/../../../config.php';
require_once MODELS_PATH . 'db.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (($_SESSION['user_role'] ?? '') !== 'master_admin') {
    die("Access Denied.");
}

$tenant_id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($tenant_id > 0 && in_array($action, ['suspend', 'activate'])) {
    $pdo = connectDB();
    
    if ($action === 'suspend') {
        $pdo->prepare("UPDATE tenants SET status = 'suspended' WHERE id = ?")->execute([$tenant_id]);
        $_SESSION['status_message'] = "<div class='alert alert-warning fw-bold'><i class='fas fa-ban'></i> Tenant Portal Suspended Successfully.</div>";
    } elseif ($action === 'activate') {
        $pdo->prepare("UPDATE tenants SET status = 'active' WHERE id = ?")->execute([$tenant_id]);
        $_SESSION['status_message'] = "<div class='alert alert-success fw-bold'><i class='fas fa-check-circle'></i> Tenant Portal Activated.</div>";
    }
}

header("Location: " . BASE_URL . "?page=master_portals");
exit;
?>
