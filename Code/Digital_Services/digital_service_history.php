<?php
/**
 * views/digital_service_history.php
 * View user reports for Digital Service usage.
 */

if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-danger'>Please login to view history.</div>";
    return;
}

$pdo = connectDB();
$userId = $_SESSION['user_id'];

$pdo = connectDB();
$userId = $_SESSION['user_id'];

// Robust query to fetch usage history from BOTH wallet_transactions AND digital_service_history logs (where not a draft)
$stmt = $pdo->prepare("
    (SELECT created_at, description, type, amount, 'Wallet Transaction' as source FROM wallet_transactions 
     WHERE user_id = ? AND (description LIKE '%Poster Studio%' OR description LIKE '%Passport Photo%' OR description LIKE '%PVC Card%' OR description LIKE '%Size Converter%' OR description LIKE '%PDF Editor%'))
    UNION ALL
    (SELECT created_at, service_name as description, 'usage' as type, 0 as amount, 'Service Log' as source FROM digital_service_history 
     WHERE user_id = ? AND is_draft = 0)
    ORDER BY created_at DESC
");
$stmt->execute([$userId, $userId]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Summary Statistics
$stats = [
    'Passport Photo' => 0,
    'Poster Studio' => 0,
    'Size Converter' => 0,
    'PDF Editor' => 0,
    'Ultimate Photo Studio' => 0
];

foreach ($history as $item) {
    $desc = strtolower($item['description']);
    if (strpos($desc, 'passport') !== false) $stats['Passport Photo']++;
    elseif (strpos($desc, 'poster') !== false) $stats['Poster Studio']++;
    elseif (strpos($desc, 'size') !== false) $stats['Size Converter']++;
    elseif (strpos($desc, 'pdf') !== false || strpos($desc, 'document') !== false) $stats['PDF Editor']++;
    elseif (strpos($desc, 'ultimate') !== false || strpos($desc, 'pro photo') !== false) $stats['Ultimate Photo Studio']++;
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <?php foreach ($stats as $service => $count): ?>
        <div class="col-md-2 mb-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 12px; background: #f8fafc; border-bottom: 3px solid #3b82f6 !important;">
                <h6 class="text-muted small fw-bold mb-1"><?= $service ?></h6>
                <h3 class="fw-bold text-primary mb-0"><?= $count ?></h3>
                <span class="text-secondary small">Total Uses</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark"><i class="fas fa-history text-primary"></i> Digital Service Usage Report</h3>
        <a href="<?= BASE_URL ?>?page=dashboard" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-list-ul me-2"></i> Recent Transactions</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted">
                        <tr>
                            <th class="ps-4">Date & Time</th>
                            <th>Service Details</th>
                            <th>Source / Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-folder-open fa-3x mb-3"></i>
                                        <p class="mb-0">No digital service history found.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $item): ?>
                                <tr>
                                    <td class="ps-4 text-muted">
                                        <?= date('d M Y, h:i A', strtotime($item['created_at'])) ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($item['description']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border me-2"><?= $item['source'] ?? 'Transaction' ?></span>
                                        <?php if ($item['type'] === 'debit'): ?>
                                            <span class="badge bg-soft-danger text-danger border border-danger">Debit</span>
                                        <?php elseif($item['type'] === 'usage'): ?>
                                            <span class="badge bg-soft-info text-info border border-info">Usage</span>
                                        <?php else: ?>
                                            <span class="badge bg-soft-success text-success border border-success">Credit</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold <?= ($item['type'] === 'debit') ? 'text-danger' : (($item['type'] === 'usage') ? 'text-muted' : 'text-success') ?>">
                                            <?= $item['type'] === 'debit' ? '-' : ($item['type'] === 'usage' ? '' : '+') ?> ₹<?= number_format($item['amount'], 2) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Completed</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-soft-danger { background-color: rgba(220, 53, 69, 0.1); }
    .bg-soft-success { background-color: rgba(25, 135, 84, 0.1); }
</style>
