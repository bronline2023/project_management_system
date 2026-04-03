<?php
/**
 * views/digital_service_history.php
 * View user reports for Digital Service usage and Recharges.
 */

if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-danger'>Please login to view history.</div>";
    return;
}

require_once MODELS_PATH . 'reports_helper.php';
$pdo = connectDB();
$userId = $_SESSION['user_id'];

// Fetch All Data
$digitalUsage = getDigitalUsage($pdo, $userId);
$walletRecharges = getWalletRecharges($pdo, $userId);
$pointPurchases = getPointPurchases($pdo, $userId);

// Calculate Summary Statistics (from digitalUsage)
$stats = [
    'Passport Photo' => 0,
    'Poster Studio' => 0,
    'Size Converter' => 0,
    'PDF Editor' => 0,
    'Ultimate Photo Studio' => 0,
    'Resume Maker' => 0
];

foreach ($digitalUsage as $item) {
    $desc = strtolower($item['service_name']);
    if (strpos($desc, 'passport') !== false) $stats['Passport Photo']++;
    elseif (strpos($desc, 'poster') !== false) $stats['Poster Studio']++;
    elseif (strpos($desc, 'size') !== false) $stats['Size Converter']++;
    elseif (strpos($desc, 'pdf') !== false || strpos($desc, 'document') !== false || strpos($desc, 'converter') !== false) $stats['PDF Editor']++;
    elseif (strpos($desc, 'ultimate') !== false || strpos($desc, 'pro photo') !== false || strpos($desc, 'photo studio') !== false) $stats['Ultimate Photo Studio']++;
    elseif (strpos($desc, 'resume') !== false || strpos($desc, 'cv') !== false) $stats['Resume Maker']++;
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark"><i class="fas fa-chart-line text-primary me-2"></i> My Usage & Recharge Reports</h3>
        <div>
            <a href="<?= BASE_URL ?>?page=dashboard" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm bg-white"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="row mb-4">
        <?php foreach ($stats as $service => $count): ?>
        <div class="col-md-2 mb-3">
            <div class="card border-0 shadow-sm text-center p-3 h-100" style="border-radius: 12px; background: #f8fafc; border-bottom: 3px solid #3b82f6 !important;">
                <h6 class="text-muted small fw-bold mb-1"><?= $service ?></h6>
                <h3 class="fw-bold text-primary mb-0"><?= $count ?></h3>
                <span class="text-secondary small">Total Uses</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- TABS -->
    <ul class="nav nav-pills mb-4 gap-2 no-print" id="historyTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="pill" data-bs-target="#usage-tab"><i class="fas fa-rocket me-2"></i> Service Usage</button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="pill" data-bs-target="#recharges-tab"><i class="fas fa-wallet me-2"></i> Wallet Recharges</button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="pill" data-bs-target="#points-tab"><i class="fas fa-star me-2"></i> Points Purchased</button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- 1. SERVICE USAGE TAB -->
        <div class="tab-pane fade show active" id="usage-tab">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-secondary"><i class="fas fa-list-ul me-2"></i> Recent Usage Logs</h5>
                    <button onclick="exportToCSV('Usage_Report')" class="btn btn-sm btn-light border rounded-pill px-3"><i class="fas fa-download me-1"></i> Export</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="usageTable">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Date & Time</th>
                                <th>Service Name</th>
                                <th>₹ Deducted</th>
                                <th>Points Used</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($digitalUsage)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No usage history.</td></tr>
                            <?php else: ?>
                                <?php foreach ($digitalUsage as $row): ?>
                                    <tr>
                                        <td class="ps-4 small text-muted"><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                                        <td><span class="fw-bold text-dark"><?= htmlspecialchars($row['service_name']) ?></span></td>
                                        <td class="text-danger fw-bold">₹<?= number_format($row['amount_deducted'], 2) ?></td>
                                        <td><span class="badge bg-warning-subtle text-warning border border-warning px-2"><?= $row['points_deducted'] ?> Pts</span></td>
                                        <td><span class="badge bg-success-subtle text-success border border-success px-2">Completed</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. WALLET RECHARGES TAB -->
        <div class="tab-pane fade" id="recharges-tab">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-secondary text-primary"><i class="fas fa-university me-2"></i> Wallet Recharge History</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Proof</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($walletRecharges)): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">No recharge history.</td></tr>
                            <?php else: ?>
                                <?php foreach ($walletRecharges as $row): ?>
                                    <tr>
                                        <td class="ps-4 small text-muted"><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                                        <td class="fw-bold text-success">₹<?= number_format($row['amount'], 2) ?></td>
                                        <td>
                                            <?php 
                                            $badgeClass = ($row['status']=='approved')?'success':(($row['status']=='pending')?'warning':'danger');
                                            ?>
                                            <span class="badge bg-<?= $badgeClass ?>-subtle text-<?= $badgeClass ?> border border-<?= $badgeClass ?> px-2"><?= ucfirst($row['status']) ?></span>
                                        </td>
                                        <td>
                                            <?php if($row['screenshot_path']): ?>
                                                <a href="<?= UPLOADS_DIR_RELATIVE ?>recharge_proofs/<?= $row['screenshot_path'] ?>" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2 small">View</a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. POINTS PURCHASED TAB -->
        <div class="tab-pane fade" id="points-tab">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-secondary text-warning"><i class="fas fa-star me-2 icon-warning"></i> Points Exchange History</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Wallet Deduction</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pointPurchases)): ?>
                                <tr><td colspan="3" class="text-center py-5 text-muted">No point purchases.</td></tr>
                            <?php else: ?>
                                <?php foreach ($pointPurchases as $row): ?>
                                    <tr>
                                        <td class="ps-4 small text-muted"><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                                        <td class="text-danger fw-bold">- ₹<?= number_format($row['amount'], 2) ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($row['description']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .nav-pills .nav-link { color: #64748b; background: #f1f5f9; }
    .nav-pills .nav-link.active { background: #3b82f6; color: white; }
    .icon-warning { color: #f59e0b; }
    .bg-success-subtle { background-color: rgba(34, 197, 94, 0.1); }
    .bg-warning-subtle { background-color: rgba(245, 158, 11, 0.1); }
    .bg-danger-subtle { background-color: rgba(239, 68, 68, 0.1); }
</style>


<style>
    .bg-soft-danger { background-color: rgba(220, 53, 69, 0.1); }
    .bg-soft-success { background-color: rgba(25, 135, 84, 0.1); }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function exportToCSV() {
    let csv = [];
    let rows = document.querySelectorAll("table tr");
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        for (let j = 0; j < cols.length; j++) 
            row.push('"' + cols[j].innerText.trim().replace(/"/g, '""') + '"');
        csv.push(row.join(","));
    }
    let csvFile = new Blob(["\ufeff" + csv.join("\n")], {type: "text/csv;charset=utf-8;"});
    let downloadLink = document.createElement("a");
    downloadLink.download = "Digital_Service_Usage_Report.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

function exportToPDF() {
    let element = document.querySelector(".table-responsive");
    let btn = event.currentTarget;
    let originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
    btn.disabled = true;

    let opt = {
        margin:       10,
        filename:     'Digital_Service_Usage_Report.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
    };
    html2pdf().set(opt).from(element).save().then(function() {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}
</script>
