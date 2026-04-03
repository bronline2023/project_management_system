<?php
/**
 * admin/tasks/my_daily_entries.php
 * Displays a list of official daily work entries with financial summaries.
 */
$pdo = connectDB();
$message = '';

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$currentUserId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['user_role'] ?? 'guest';
$isAdmin = in_array($userRole, ['admin', 'master_admin']);

// Pagination and Search
$searchQuery = trim($_GET['search'] ?? '');
$recordsPerPage = 15;
$currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($currentPage - 1) * $recordsPerPage;

$sqlBase = " FROM work_assignments wa 
             LEFT JOIN customers cust ON wa.customer_id = cust.id 
             JOIN users u ON wa.assigned_to_user_id = u.id 
             JOIN categories cat ON wa.category_id = cat.id 
             JOIN subcategories sub ON wa.subcategory_id = sub.id
             WHERE wa.is_daily_entry = 1";

$params = [];
// Restriction: Non-admins with 'view_daily_reports' can see all, otherwise only their own.
// But usually, 'Official Work Tracker' implies seeing the team's work if you are a manager/accountant.
// Based on user request: "જે યુઝર entry નાખી છે તે ... ફેરફાર કરી શકે છે ... બાકી એડમીન સિવાઈ તે ડિલીટ કે એડિટ ના કરી શકે"
// So everyone sees their own unless they are admin.
if (!$isAdmin && !in_array('view_daily_reports', $_SESSION['user_permissions'] ?? [])) {
    $sqlBase .= " AND wa.assigned_to_user_id = ?";
    $params[] = $currentUserId;
}

if (!empty($searchQuery)) {
    $sqlBase .= " AND (cust.customer_name LIKE ? OR wa.work_description LIKE ? OR u.name LIKE ?)";
    $searchTerm = '%' . $searchQuery . '%';
    array_push($params, $searchTerm, $searchTerm, $searchTerm);
}

// Summary Statistics (Full set, ignore pagination for stats)
$statsSql = "SELECT 
                SUM(wa.fee) as gross_work_value,
                SUM(CASE 
                    WHEN wa.payment_status IN ('online_paid', 'cash_paid') THEN wa.fee 
                    WHEN wa.payment_status = 'partial_paid' THEN wa.partial_amount 
                    ELSE 0 
                END) as total_collected,
                SUM(wa.maintenance_fee) as total_maint, 
                SUM(wa.loss_amount) as total_loss " . $sqlBase;
$statsStmt = $pdo->prepare($statsSql);
$statsStmt->execute($params);
$totals = $statsStmt->fetch(PDO::FETCH_ASSOC);

$grossWorkValue = (float)($totals['gross_work_value'] ?? 0);
$totalCollected = (float)($totals['total_collected'] ?? 0);
$totalMaint = (float)($totals['total_maint'] ?? 0);
$totalLoss = (float)($totals['total_loss'] ?? 0);

$pendingBalance = $grossWorkValue - $totalCollected;
$netEarning = $totalCollected - $totalMaint - $totalLoss;

// Paging
$countSql = "SELECT COUNT(wa.id) " . $sqlBase;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $recordsPerPage);

// Final Data Query
$selectSql = "SELECT wa.*, cust.customer_name, u.name as entry_by, cat.name as category_name, sub.name as subcategory_name " . $sqlBase . " ORDER BY wa.created_at DESC LIMIT $offset, $recordsPerPage";
$stmt = $pdo->prepare($selectSql);
$stmt->execute($params);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch attachments for the current entries
$attachmentMap = [];
if (!empty($entries)) {
    $entryIds = array_column($entries, 'id');
    $placeholders = implode(',', array_fill(0, count($entryIds), '?'));
    $attStmt = $pdo->prepare("SELECT * FROM daily_work_attachments WHERE work_id IN ($placeholders)");
    $attStmt->execute($entryIds);
    $allAtts = $attStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allAtts as $att) {
        $attachmentMap[$att['work_id']][] = $att;
    }
}

$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currency = htmlspecialchars($settings['currency_symbol'] ?? '₹');
?>

<div class="row mb-4 g-3">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 rounded-4 bg-success text-white h-100">
            <div class="card-body p-3">
                <div class="small text-uppercase fw-bold opacity-75">Total Collected Fees</div>
                <h3 class="mb-0 fw-bolder"><?= $currency ?><?= number_format($totalCollected, 2) ?></h3>
                <i class="fas fa-money-bill-wave position-absolute top-0 end-0 m-3 opacity-25 fs-1"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 rounded-4 bg-info text-white h-100">
            <div class="card-body p-3">
                <div class="small text-uppercase fw-bold opacity-75">Total Work Value</div>
                <h3 class="mb-0 fw-bolder"><?= $currency ?><?= number_format($grossWorkValue, 2) ?></h3>
                <i class="fas fa-briefcase position-absolute top-0 end-0 m-3 opacity-25 fs-1"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 rounded-4 bg-warning text-dark h-100">
            <div class="card-body p-3">
                <div class="small text-uppercase fw-bold opacity-75">Customer Pending</div>
                <h3 class="mb-0 fw-bolder"><?= $currency ?><?= number_format($pendingBalance, 2) ?></h3>
                <i class="fas fa-user-clock position-absolute top-0 end-0 m-3 opacity-25 fs-1"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 rounded-4 bg-primary text-white h-100">
            <div class="card-body p-3">
                <div class="small text-uppercase fw-bold opacity-75">Current Net Profit</div>
                <h3 class="mb-0 fw-bolder"><?= $currency ?><?= number_format($netEarning, 2) ?></h3>
                <i class="fas fa-piggy-bank position-absolute top-0 end-0 m-3 opacity-25 fs-1"></i>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
        <h5 class="mb-0 text-dark fw-bold"><i class="fas fa-list-alt me-2 text-primary"></i> Daily Work Records</h5>
        <form class="d-flex" method="GET" action="index.php">
            <input type="hidden" name="page" value="my_daily_entries">
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control" placeholder="Search Customer or Work..." value="<?= htmlspecialchars($searchQuery) ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($message)) { echo '<div class="p-3">'.$message.'</div>'; } ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID / Date</th>
                        <th>Customer / Service</th>
                        <th>Work Description</th>
                        <th>Attachments</th>
                        <th>Financials</th>
                        <th>Status / Payment</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">No work entries found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($entries as $row): 
                        $statusClass = ($row['status'] === 'completed') ? 'bg-success' : (($row['status'] === 'in_process') ? 'bg-primary' : 'bg-warning text-dark');
                        $payClass = ($row['payment_status'] === 'partial_paid') ? 'text-warning' : (in_array($row['payment_status'], ['online_paid', 'cash_paid']) ? 'text-success' : 'text-danger');
                        
                        // Edit/Delete Logic
                        $can_edit = $isAdmin;
                        if (!$isAdmin && $row['assigned_to_user_id'] == $currentUserId) {
                             if ($row['status'] !== 'completed' && in_array($row['payment_status'], ['pending', 'partial_paid'])) {
                                 $can_edit = true;
                             }
                        }
                        $rowAtts = $attachmentMap[$row['id']] ?? [];
                    ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold">#<?= $row['id'] ?></div>
                                <div class="small text-muted"><?= date('d M Y', strtotime($row['created_at'])) ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['customer_name'] ?: 'Retail Customer') ?></div>
                                <div class="small badge bg-light text-primary border border-primary-subtle"><?= htmlspecialchars($row['category_name']) ?></div>
                                <div class="x-small text-muted mt-1"><?= htmlspecialchars($row['subcategory_name']) ?></div>
                            </td>
                            <td>
                                <div class="text-wrap" style="min-width: 150px; font-size: 0.9rem;">
                                    <?= htmlspecialchars($row['work_description']) ?>
                                </div>
                                <div class="small text-muted mt-1 italic">By: <?= htmlspecialchars($row['entry_by']) ?></div>
                            </td>
                            <td>
                                <?php if(!empty($rowAtts)): ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach(array_slice($rowAtts, 0, 3) as $a): 
                                            $ext = strtolower(pathinfo($a['file_path'], PATHINFO_EXTENSION));
                                            $isImg = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                                        ?>
                                            <a href="<?= UPLOADS_URL . 'daily_work/' . $a['file_path'] ?>" target="_blank" class="d-block">
                                                <?php if($isImg): ?>
                                                    <img src="<?= UPLOADS_URL . 'daily_work/' . $a['file_path'] ?>" class="rounded shadow-sm border" style="width:30px; height:30px; object-fit:cover;">
                                                <?php else: ?>
                                                    <div class="bg-light border rounded d-flex align-items-center justify-content-center" style="width:30px; height:30px;">
                                                        <i class="fas fa-file-alt text-muted" style="font-size: 0.6rem;"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </a>
                                        <?php endforeach; ?>
                                        <?php if(count($rowAtts) > 3): ?>
                                            <span class="badge bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width:30px; height:30px; font-size:0.6rem;">+<?= count($rowAtts)-3 ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">No files</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="text-success small fw-bold">Fee: <?= $currency ?><?= number_format($row['fee'], 2) ?></div>
                                <?php 
                                    $collected = ($row['payment_status'] === 'partial_paid') ? $row['partial_amount'] : (in_array($row['payment_status'], ['online_paid', 'cash_paid']) ? $row['fee'] : 0);
                                    $balance = $row['fee'] - $collected;
                                    if ($balance > 0):
                                ?>
                                    <div class="text-danger x-small fw-bold">Balance: <?= $currency ?><?= number_format($balance, 2) ?></div>
                                <?php endif; ?>
                                <?php if($row['maintenance_fee'] > 0): ?><div class="text-info x-small">Maint: -<?= $currency ?><?= number_format($row['maintenance_fee'], 2) ?></div><?php endif; ?>
                                <?php if($row['loss_amount'] > 0): ?><div class="text-danger x-small">Loss: -<?= $currency ?><?= number_format($row['loss_amount'], 2) ?></div><?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $statusClass ?> rounded-pill mb-1"><?= ucwords(str_replace('_', ' ', $row['status'])) ?></span>
                                <div class="small <?= $payClass ?> fw-bold">
                                    <i class="fas fa-wallet me-1"></i> <?= ucwords(str_replace('_', ' ', $row['payment_status'])) ?>
                                    <?php if($row['payment_status'] === 'partial_paid'): ?>
                                        (<?= $currency ?><?= number_format($row['partial_amount'], 2) ?>)
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="pe-4 text-end">
                                <?php if ($can_edit): ?>
                                    <a href="?page=daily_work_entry&id=<?= $row['id'] ?>" class="btn btn-sm btn-light text-primary border rounded-pill shadow-sm" title="Edit Entry">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="index.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this official entry?');">
                                        <input type="hidden" name="action" value="delete_daily_entry">
                                        <input type="hidden" name="task_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-light text-danger border rounded-pill shadow-sm ms-1" title="Delete Entry">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-light text-muted border rounded-pill disabled" title="Entry is locked (Completed/Paid)">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white border-top py-3">
                <nav>
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=my_daily_entries&search=<?= urlencode($searchQuery) ?>&p=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .x-small { font-size: 0.75rem; }
    .italic { font-style: italic; }
    .table thead th { font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
    .bg-light { background-color: #f8fafc !important; }
</style>
