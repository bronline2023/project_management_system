<?php
/**
 * user/my_freelancer_tasks.php
 * FINAL UPDATED: 
 * 1. Added Search Bar (Supports ID, Client, Customer, Category).
 * 2. Pagination persists search query.
 * 3. Shows Customer Name (Big) and Client Name (Small).
 */

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';

// Get Params
$statusFilter = $_GET['status'] ?? ''; 
$search = $_GET['search'] ?? '';
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

// --- BUILD QUERY ---
$sqlBase = "
    FROM work_assignments wa 
    JOIN clients cl ON wa.client_id = cl.id 
    LEFT JOIN customers cust ON wa.customer_id = cust.id
    LEFT JOIN categories cat ON wa.category_id = cat.id
    WHERE wa.assigned_to_user_id = ? 
";
$params = [$currentUserId];

// Apply Status Filter
if ($statusFilter) {
    $sqlBase .= " AND wa.status = ? ";
    $params[] = $statusFilter;
}

// Apply Search Filter
if ($search) {
    $sqlBase .= " AND (wa.id LIKE ? OR cl.client_name LIKE ? OR cust.customer_name LIKE ? OR cat.name LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

// Count Total
$totalRecords = fetchColumn($pdo, "SELECT COUNT(*) $sqlBase", $params);
$totalPages = ceil($totalRecords / $limit);

// Fetch Data
$sql = "SELECT wa.*, cl.client_name, cust.customer_name, cat.name as category_name $sqlBase ORDER BY wa.created_at DESC LIMIT $limit OFFSET $offset";
$tasks = fetchAll($pdo, $sql, $params);

// Calculate Page Totals
$pageTotalMyFee = 0;
$pageTotalCompanyFee = 0;

function getFreelancerStatusBadge($status) {
    $badges = [
        'pending' => 'secondary',
        'in_process' => 'info',
        'pending_verification' => 'warning',
        'verified_completed' => 'success', 
        'cancelled' => 'danger',
        'returned' => 'danger'
    ];
    $color = $badges[$status] ?? 'light';
    $text = ucfirst(str_replace('_', ' ', $status));
    return "<span class='badge bg-{$color}'>{$text}</span>";
}

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h1 class="h3 mb-2 text-gray-800">My Assigned Tasks</h1>
        
        <form method="GET" action="index.php" class="d-flex align-items-center">
            <input type="hidden" name="page" value="my_freelancer_tasks">
            
            <?php if($statusFilter): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                <span class="badge bg-secondary text-white fs-6 me-2"><?= ucfirst(str_replace('_', ' ', $statusFilter)) ?></span>
            <?php endif; ?>

            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search ID, Client..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                <?php if($search || $statusFilter): ?>
                    <a href="index.php?page=my_freelancer_tasks" class="btn btn-outline-danger" title="Clear All Filters"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if ($message) { include VIEWS_PATH . 'components/message_box.php'; } ?>

    <div class="card shadow-sm rounded-3">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>Task List (Total: <?= $totalRecords ?>)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Customer / Client</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th class="text-end">My Fee</th>
                            <th class="text-end">Company Fee</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tasks)): ?>
                            <?php foreach ($tasks as $task): ?>
                                <?php 
                                    $myFee = $task['task_price'];
                                    $totalFee = $task['fee'];
                                    $companyFee = $totalFee - $myFee;
                                    $pageTotalMyFee += $myFee;
                                    $pageTotalCompanyFee += $companyFee;
                                    $isLocked = in_array($task['status'], ['pending_verification', 'verified_completed', 'cancelled']);
                                ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($task['id']) ?></strong></td>
                                    <td>
                                        <?php 
                                            $customerName = !empty($task['customer_name']) ? $task['customer_name'] : $task['client_name'];
                                            $clientLabel = !empty($task['customer_name']) ? $task['client_name'] : 'Direct Client';
                                        ?>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($customerName) ?></span>
                                            <small class="text-muted"><i class="fas fa-building me-1"></i><?= htmlspecialchars($clientLabel) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                            $dueDate = strtotime($task['deadline']);
                                            $isOverdue = $dueDate < time() && $task['status'] == 'in_process';
                                        ?>
                                        <span class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                            <?= date('d M, Y', $dueDate) ?>
                                        </span>
                                    </td>
                                    <td><?= getFreelancerStatusBadge($task['status']) ?></td>
                                    <td class="text-end text-success fw-bold"><?= $currencySymbol . number_format($myFee, 2) ?></td>
                                    <td class="text-end text-muted"><?= $currencySymbol . number_format($companyFee, 2) ?></td>
                                    <td class="text-center">
                                        <?php if ($isLocked): ?>
                                            <a href="index.php?page=update_freelancer_task&id=<?= $task['id'] ?>" class="btn btn-sm btn-secondary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="index.php?page=update_freelancer_task&id=<?= $task['id'] ?>" class="btn btn-sm btn-primary" title="Update / Work">
                                                <i class="fas fa-edit"></i> Action
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4">No tasks found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($tasks)): ?>
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td colspan="4" class="text-end text-uppercase">Page Total:</td>
                            <td class="text-end text-success"><?= $currencySymbol . number_format($pageTotalMyFee, 2) ?></td>
                            <td class="text-end text-secondary"><?= $currencySymbol . number_format($pageTotalCompanyFee, 2) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <?php if ($totalPages > 1): 
                $queryStr = "";
                if ($statusFilter) $queryStr .= "&status=" . urlencode($statusFilter);
                if ($search) $queryStr .= "&search=" . urlencode($search);
            ?>
            <nav aria-label="Task Page Navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?page=my_freelancer_tasks&p=<?= $page - 1 ?><?= $queryStr ?>">&laquo; Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?page=my_freelancer_tasks&p=<?= $i ?><?= $queryStr ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?page=my_freelancer_tasks&p=<?= $page + 1 ?><?= $queryStr ?>">Next &raquo;</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>

        </div>
    </div>
</div>