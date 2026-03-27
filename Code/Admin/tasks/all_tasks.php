<?php
/**
 * admin/all_tasks.php
 * Displays a list of all work assignments with Client and Customer name.
 * FINAL: Added Customer Name alongside Client Name.
 */
$pdo = connectDB();
$message = '';

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

// Pagination and Search Logic
$searchQuery = trim($_GET['search'] ?? '');
$recordsPerPage = 10;
$currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($currentPage - 1) * $recordsPerPage;

// Base SQL query with JOIN on customers table
$sqlBase = " FROM work_assignments wa 
             JOIN clients cl ON wa.client_id = cl.id 
             LEFT JOIN customers cust ON wa.customer_id = cust.id 
             JOIN users u ON wa.assigned_to_user_id = u.id 
             JOIN categories cat ON wa.category_id = cat.id 
             JOIN subcategories sub ON wa.subcategory_id = sub.id";

$params = [];
if (!empty($searchQuery)) {
    $sqlBase .= " WHERE cl.client_name LIKE ? OR cust.customer_name LIKE ? OR u.name LIKE ? OR wa.work_description LIKE ?";
    $searchTerm = '%' . $searchQuery . '%';
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

$countSql = "SELECT COUNT(wa.id) " . $sqlBase;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $recordsPerPage);

// Select Query with Customer Name field
$selectSql = "SELECT wa.*, cl.client_name, cust.customer_name, u.name as freelancer_name, cat.name as category_name, sub.name as subcategory_name " . $sqlBase . " ORDER BY wa.created_at DESC LIMIT $offset, $recordsPerPage";
$stmt = $pdo->prepare($selectSql);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-tasks me-2"></i> All Task Assignments</h5>
        <form class="d-flex" method="GET" action="index.php">
            <input type="hidden" name="page" value="all_tasks">
            <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Search Client, Customer, Task..." value="<?= htmlspecialchars($searchQuery) ?>">
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
        </form>
    </div>
    <div class="card-body p-0">
        <?= $message ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Client / Customer</th>
                        <th>Assigned To</th>
                        <th>Work Category</th>
                        <th>Description</th>
                        <th>Fee / Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td><span class="fw-bold text-muted">#<?= $task['id'] ?></span></td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($task['client_name']) ?></div>
                                <?php if (!empty($task['customer_name'])): ?>
                                    <div class="small text-primary"><i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($task['customer_name']) ?></div>
                                <?php else: ?>
                                    <div class="small text-muted italic">No Customer</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($task['freelancer_name']) ?></div>
                                <div class="small text-muted">Deadline: <?= date('d M Y', strtotime($task['deadline'])) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark"><?= htmlspecialchars($task['category_name']) ?></span>
                                <div class="small text-muted mt-1"><?= htmlspecialchars($task['subcategory_name']) ?></div>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($task['work_description']) ?>">
                                    <?= htmlspecialchars($task['work_description']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-success">Fee: <?= number_format($task['fee'], 2) ?></div>
                                <div class="small text-danger">Price: <?= number_format($task['task_price'], 2) ?></div>
                            </td>
                            <td>
                                <?php
                                $statusClass = 'bg-secondary';
                                if ($task['status'] === 'verified_completed') $statusClass = 'bg-success';
                                elseif ($task['status'] === 'in_process') $statusClass = 'bg-primary';
                                elseif ($task['status'] === 'pending_verification') $statusClass = 'bg-warning text-dark';
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= ucwords(str_replace('_', ' ', $task['status'])) ?></span>
                                <div class="small mt-1 <?= ($task['payment_status'] === 'paid') ? 'text-success' : 'text-danger' ?>">
                                    <i class="fas fa-money-bill-wave me-1"></i> <?= ucfirst($task['payment_status']) ?>
                                </div>
                            </td>
                            <td>
                                <a href="?page=edit_task&id=<?= $task['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Task">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                    <form action="index.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure? If this task was completed, the balance transaction will be reversed.');">
                                        <input type="hidden" name="action" value="delete_task">
                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Task">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=all_tasks&search=<?= urlencode($searchQuery) ?>&p=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>