<?php
/**
 * user/my_freelancer_tasks.php
 * FINAL UPDATED: Includes Pagination, Company Fee Column, and Totals Row.
 */

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';

$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

// --- 1. PAGINATION LOGIC ---
$limit = 10; // ટાસ્ક પ્રતિ પેજ
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $limit;

// Count Total Records
$totalRecords = fetchColumn($pdo, "SELECT COUNT(*) FROM work_assignments WHERE assigned_to_user_id = ?", [$currentUserId]);
$totalPages = ceil($totalRecords / $limit);

// Fetch Tasks with Limit & Offset
$tasks = fetchAll($pdo, "
    SELECT wa.*, cl.client_name 
    FROM work_assignments wa 
    JOIN clients cl ON wa.client_id = cl.id 
    WHERE wa.assigned_to_user_id = ? 
    ORDER BY wa.created_at DESC 
    LIMIT $limit OFFSET $offset
", [$currentUserId]);

// Calculate Page Totals
$pageTotalMyFee = 0;
$pageTotalCompanyFee = 0;

// Status Badge Function
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
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Assigned Tasks</h1>
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
                            <th>Client</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th class="text-end">My Fee (Earnings)</th>
                            <th class="text-end">Company Fee</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tasks)): ?>
                            <?php foreach ($tasks as $task): ?>
                                <?php 
                                    // Calculate Fees
                                    $myFee = $task['task_price'];
                                    $totalFee = $task['fee'];
                                    $companyFee = $totalFee - $myFee;

                                    // Add to Page Totals
                                    $pageTotalMyFee += $myFee;
                                    $pageTotalCompanyFee += $companyFee;

                                    // Lock Logic
                                    $isLocked = in_array($task['status'], ['pending_verification', 'verified_completed', 'cancelled']);
                                ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($task['id']) ?></strong></td>
                                    <td><?= htmlspecialchars($task['client_name']) ?></td>
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
                                    
                                    <td class="text-end text-success fw-bold">
                                        <?= $currencySymbol . number_format($myFee, 2) ?>
                                    </td>
                                    
                                    <td class="text-end text-muted">
                                        <?= $currencySymbol . number_format($companyFee, 2) ?>
                                    </td>

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

            <?php if ($totalPages > 1): ?>
            <nav aria-label="Task Page Navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?page=my_freelancer_tasks&p=<?= $page - 1 ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo; Previous</span>
                        </a>
                    </li>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?page=my_freelancer_tasks&p=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?page=my_freelancer_tasks&p=<?= $page + 1 ?>" aria-label="Next">
                            <span aria-hidden="true">Next &raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>

        </div>
    </div>
</div>