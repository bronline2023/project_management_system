<?php
/**
 * views/customers.php
 * This file handles the management of customers.
 */

$pdo = connectDB();
$message = '';
$userRole = $_SESSION['user_role'] ?? 'guest';

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

// Permissions based on roles, excluding DEO and Freelancer
$canManageCustomers = !in_array($userRole, ['deo', 'freelancer']);

// Search and Pagination Logic
$searchQuery = trim($_GET['search'] ?? '');
$recordsPerPage = 10;
$currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($currentPage - 1) * $recordsPerPage;
$params = [];
$sqlBase = "FROM customers c LEFT JOIN clients cl ON c.client_id = cl.id";
$whereClauses = [];

if (!empty($searchQuery)) {
    $whereClauses[] = "(c.customer_name LIKE ? OR c.customer_phone LIKE ? OR c.customer_email LIKE ? OR cl.client_name LIKE ?)";
    $searchTerm = '%' . $searchQuery . '%';
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = " WHERE " . implode(' AND ', $whereClauses);
}

$countSql = "SELECT COUNT(c.id) " . $sqlBase . $whereSql;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $recordsPerPage);

// Fetch all customers, including client and appointment information
$sql = "
    SELECT c.*, cl.client_name, cl.company_name, 
           (SELECT id FROM appointments WHERE client_phone = c.customer_phone LIMIT 1) AS appointment_id
    " . $sqlBase . $whereSql . "
    ORDER BY c.customer_name ASC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
for($i = 0; $i < count($params); $i++) {
    $stmt->bindValue($i+1, $params[$i], PDO::PARAM_STR);
}
$stmt->execute();
$customersData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$clients = fetchAll($pdo, "SELECT id, client_name, company_name FROM clients ORDER BY client_name ASC");
?>

<h2 class="mb-4">Customer Management</h2>

<?php if (!empty($message)): ?>
    <?php include VIEWS_PATH . 'components/message_box.php'; ?>
<?php endif; ?>

<?php if ($canManageCustomers): ?>
<div class="card shadow-sm rounded-3 mb-4">
    <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Add New Customer</h5></div>
    <div class="card-body">
        <form action="index.php" method="POST">
            <input type="hidden" name="page" value="customers">
            <input type="hidden" name="action" value="add_customer">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="customer_name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Customer Phone <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="customer_phone" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Customer Email (Optional)</label>
                    <input type="email" class="form-control" name="customer_email">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Associated Client (Optional)</label>
                    <select class="form-select" name="client_id">
                        <option value="">Select a Client</option>
                        <?php foreach($clients as $client): ?>
                            <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['client_name']) ?> (<?= htmlspecialchars($client['company_name'] ?? 'N/A') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Customer Address (Optional)</label>
                <textarea class="form-control" name="customer_address" rows="1"></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Add Customer</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm rounded-3">
    <div class="card-header bg-secondary text-white"><h5 class="mb-0"><i class="fas fa-users me-2"></i>Existing Customers</h5></div>
    <div class="card-body">
         <form action="" method="GET" class="mb-3">
            <input type="hidden" name="page" value="customers">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by Name, Phone, Email, or Client..." value="<?= htmlspecialchars($searchQuery) ?>">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Associated Client</th><th>Source</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($customersData)): ?>
                        <tr><td colspan="7" class="text-center">No customers found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($customersData as $customer): ?>
                        <tr>
                            <td><?= htmlspecialchars($customer['id']) ?></td>
                            <td><?= htmlspecialchars($customer['customer_name']) ?></td>
                            <td><?= htmlspecialchars($customer['customer_phone']) ?></td>
                            <td><?= htmlspecialchars($customer['customer_email'] ?? 'N/A') ?></td>
                            <td>
                                <?php if ($customer['client_name']): ?>
                                    <?= htmlspecialchars($customer['client_name']) ?> (<?= htmlspecialchars($customer['company_name'] ?? 'N/A') ?>)
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $customer['source'] === 'appointment' ? 'info' : 'secondary' ?>">
                                    <?= ucfirst($customer['source']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($canManageCustomers): ?>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCustomerModal" data-customer='<?= htmlspecialchars(json_encode($customer), ENT_QUOTES, 'UTF-8') ?>'><i class="fas fa-edit"></i> Edit</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="showCustomConfirm('Delete Customer', 'Are you sure you want to delete customer: <b><?= htmlspecialchars($customer['customer_name']) ?></b>? This action cannot be undone.', 'delete-customer-form-<?= $customer['id'] ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    <form id="delete-customer-form-<?= $customer['id'] ?>" action="index.php" method="POST" style="display: none;">
                                        <input type="hidden" name="page" value="customers">
                                        <input type="hidden" name="action" value="delete_customer">
                                        <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=customers&search=<?= urlencode($searchQuery) ?>&p=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="editCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Customer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="index.php" method="POST">
                <input type="hidden" name="page" value="customers">
                <input type="hidden" name="action" value="edit_customer">
                <div class="modal-body">
                    <input type="hidden" name="customer_id" id="edit-customer-id">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Customer Name*</label><input type="text" class="form-control" name="customer_name" id="edit-customer-name" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Customer Phone*</label><input type="text" class="form-control" name="customer_phone" id="edit-customer-phone" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Customer Email (Optional)</label><input type="email" class="form-control" name="customer_email" id="edit-customer-email"></div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Associated Client (Optional)</label>
                            <select class="form-select" name="client_id" id="edit-client-id">
                                <option value="">Select a Client</option>
                                <?php foreach($clients as $client): ?>
                                    <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['client_name']) ?> (<?= htmlspecialchars($client['company_name'] ?? 'N/A') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3"><label class="form-label">Customer Address (Optional)</label><textarea class="form-control" name="customer_address" id="edit-customer-address" rows="1"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Changes</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editCustomerModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const customer = JSON.parse(button.getAttribute('data-customer'));
            
            document.getElementById('edit-customer-id').value = customer.id;
            document.getElementById('edit-customer-name').value = customer.customer_name;
            document.getElementById('edit-customer-phone').value = customer.customer_phone;
            document.getElementById('edit-customer-email').value = customer.customer_email || ''; 
            document.getElementById('edit-customer-address').value = customer.customer_address || '';
            document.getElementById('edit-client-id').value = customer.client_id || '';
        });
    }

    function showCustomConfirm(title, message, formId) {
        if (confirm(message)) {
            document.getElementById(formId).submit();
        }
    }
});
</script>