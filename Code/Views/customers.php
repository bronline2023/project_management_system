<?php
/**
 * views/customers.php
 * This file handles the management of customers.
 * UPDATED: Fixed Delete Functionality and preserved all existing features.
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

// Total records for pagination
$totalRecords = fetchColumn($pdo, "SELECT COUNT(*) " . $sqlBase . $whereSql, $params);
$totalPages = ceil($totalRecords / $recordsPerPage);

// Fetch customers
$customers = fetchAll($pdo, "SELECT c.*, cl.client_name " . $sqlBase . $whereSql . " ORDER BY c.created_at DESC LIMIT $offset, $recordsPerPage", $params);

// Fetch clients for dropdowns
$clients = fetchAll($pdo, "SELECT id, client_name FROM clients ORDER BY client_name ASC");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Customers Management</h1>
        <?php if ($canManageCustomers): ?>
            <button class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                <i class="fas fa-plus fa-sm text-white-50"></i> Add New Customer
            </button>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <?= $message ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <form method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="customers">
                <div class="col-auto">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search customers..." value="<?= htmlspecialchars($searchQuery) ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                    <?php if (!empty($searchQuery)): ?>
                        <a href="index.php?page=customers" class="btn btn-secondary btn-sm">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Linked Client</th>
                            <?php if ($canManageCustomers): ?>
                                <th class="text-center">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($customers)): ?>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?= $customer['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($customer['customer_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($customer['customer_phone']) ?></td>
                                    <td><?= htmlspecialchars($customer['customer_email'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($customer['customer_address'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($customer['client_name'] ?? 'Direct') ?></td>
                                    <?php if ($canManageCustomers): ?>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#editCustomerModal" 
                                                        data-customer='<?= json_encode($customer, JSON_HEX_APOS) ?>'>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <form id="delete-form-<?= $customer['id'] ?>" action="app/actions.php" method="POST" style="display:none;">
                                                    <input type="hidden" name="action" value="delete_customer">
                                                    <input type="hidden" name="id" value="<?= $customer['id'] ?>">
                                                </form>
                                                <button class="btn btn-danger btn-sm" onclick="showCustomConfirm('Delete Customer', 'Are you sure you want to delete this customer?', 'delete-form-<?= $customer['id'] ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="<?= $canManageCustomers ? 7 : 6 ?>" class="text-center py-4">No customers found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Customer list navigation">
                    <ul class="pagination pagination-sm justify-content-end">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                                <a class="page-link" href="index.php?page=customers&p=<?= $i ?><?= !empty($searchQuery) ? '&search='.urlencode($searchQuery) : '' ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="app/actions.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_customer">
                    <div class="mb-3"><label class="form-label">Full Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="customer_name" required></div>
                    <div class="mb-3"><label class="form-label">Phone Number <span class="text-danger">*</span></label><input type="text" class="form-control" name="customer_phone" required></div>
                    <div class="mb-3"><label class="form-label">Email Address</label><input type="email" class="form-control" name="customer_email"></div>
                    <div class="mb-3">
                        <label class="form-label">Link to Client (Optional)</label>
                        <select class="form-select" name="client_id">
                            <option value="">-- None (Direct) --</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['client_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="customer_address" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add Customer</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="app/actions.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_customer">
                    <input type="hidden" name="id" id="edit-customer-id">
                    <div class="mb-3"><label class="form-label">Full Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="customer_name" id="edit-customer-name" required></div>
                    <div class="mb-3"><label class="form-label">Phone Number <span class="text-danger">*</span></label><input type="text" class="form-control" name="customer_phone" id="edit-customer-phone" required></div>
                    <div class="mb-3"><label class="form-label">Email Address</label><input type="email" class="form-control" name="customer_email" id="edit-customer-email"></div>
                    <div class="mb-3">
                        <label class="form-label">Link to Client</label>
                        <select class="form-select" name="client_id" id="edit-client-id">
                            <option value="">-- None (Direct) --</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['client_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="customer_address" id="edit-customer-address" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Changes</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit Modal Logic
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
});

// Fixed Confirm and Submit Function
function showCustomConfirm(title, message, formId) {
    if (confirm(message)) {
        document.getElementById(formId).submit();
    }
}
</script>