<?php
/**
 * admin/all_tasks.php
 * Displays a list of all work assignments.
 * FINAL: Fixed Delete Button to use POST method for Balance Reversal.
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
$sqlBase = " FROM work_assignments wa JOIN clients cl ON wa.client_id = cl.id JOIN users u ON wa.assigned_to_user_id = u.id JOIN categories cat ON wa.category_id = cat.id JOIN subcategories sub ON wa.subcategory_id = sub.id";
$params = [];
if (!empty($searchQuery)) {
    $sqlBase .= " WHERE cl.client_name LIKE ? OR u.name LIKE ? OR wa.work_description LIKE ?";
    $searchTerm = '%' . $searchQuery . '%';
    $params = [$searchTerm, $searchTerm, $searchTerm];
}
$countSql = "SELECT COUNT(wa.id) " . $sqlBase;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $recordsPerPage);

// Bind parameters for the main query
$sql = "SELECT wa.id, cl.client_name, u.name AS assigned_to_name, cat.name AS category_name, sub.name AS subcategory_name, wa.work_description, wa.deadline, wa.status " . $sqlBase . " ORDER BY wa.created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
if (!empty($searchQuery)) {
    $stmt->bindValue(1, $params[0]);
    $stmt->bindValue(2, $params[1]);
    $stmt->bindValue(3, $params[2]);
}
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!function_exists('getStatusBadgeColor')) {
    function getStatusBadgeColor($status) {
        switch ($status) {
            case 'pending': return 'warning';
            case 'in_process': return 'info';
            case 'verified_completed': return 'success'; // Fixed status name
            case 'completed': return 'success';
            case 'cancelled': return 'danger';
            case 'returned': return 'danger';
            default: return 'secondary';
        }
    }
}

$userRole = $_SESSION['user_role'] ?? 'guest';
$canEditOrDelete = in_array($userRole, ['admin', 'manager']);
?>

<h2 class="mb-4">All Assigned Tasks</h2>

<?php if (!empty($message)): ?>
    <?php include VIEWS_PATH . 'components/message_box.php'; ?>
<?php endif; ?>

<div class="card shadow-sm rounded-3">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Task List</h5>
         <a href="<?= BASE_URL ?>?page=assign_task" class="btn btn-light btn-sm">Assign New Task</a>
    </div>
    <div class="card-body">
        <form action="" method="GET" class="mb-3">
            <input type="hidden" name="page" value="all_tasks">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by Client, User, or Description..." value="<?= htmlspecialchars($searchQuery) ?>">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th><th>Client</th><th>Assigned To</th><th>Service</th><th>Deadline</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['id']) ?></td>
                            <td><?= htmlspecialchars($task['client_name']) ?></td>
                            <td><?= htmlspecialchars($task['assigned_to_name']) ?></td>
                            <td><?= htmlspecialchars($task['category_name']) ?> - <?= htmlspecialchars($task['subcategory_name']) ?></td>
                            <td><?= date('d M Y', strtotime($task['deadline'])) ?></td>
                            <td><span class="badge bg-<?= getStatusBadgeColor($task['status']) ?>"><?= ucwords(str_replace('_', ' ', $task['status'])) ?></span></td>
                            <td>
                                <?php if ($canEditOrDelete): ?>
                                    <a href="<?= BASE_URL ?>?page=edit_task&id=<?= $task['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Task"><i class="fas fa-edit"></i></a>
                                    
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