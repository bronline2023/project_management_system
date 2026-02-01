<?php
/**
 * user/my_freelancer_tasks.php
 * Displays a list of tasks assigned to the logged-in Freelancer or DEO user.
 * This is a customized view for these specific roles.
 * FINAL & COMPLETE: All functionalities and database queries are finalized.
 */

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';
$userRole = $_SESSION['user_role'] ?? 'user';

// Fetch settings
$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

// Fetch all tasks for the current user, ordered by creation date
$tasks = fetchAll($pdo, "SELECT wa.id, cl.client_name, wa.deadline, wa.status, wa.task_price FROM work_assignments wa JOIN clients cl ON wa.client_id = cl.id WHERE wa.assigned_to_user_id = ? ORDER BY wa.created_at DESC", [$currentUserId]);

function getFreelancerStatusBadge($status) {
    switch ($status) {
        case 'pending': return '<span class="badge bg-secondary">Pending</span>';
        case 'in_process': return '<span class="badge bg-info text-dark">In Process</span>';
        case 'pending_verification': return '<span class="badge bg-warning text-dark">Awaiting Verification</span>';
        case 'verified_completed': return '<span class="badge bg-success">Completed</span>';
        case 'cancelled': return '<span class="badge bg-danger">Cancelled</span>';
        default: return '<span class="badge bg-light text-dark">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
    }
}

// Display a specific message if a status update or other action was performed
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
            <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>All My Tasks</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Deadline</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tasks)): ?>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($task['id']) ?></td>
                                    <td><?= htmlspecialchars($task['client_name']) ?></td>
                                    <td><?= date('d M, Y', strtotime($task['deadline'])) ?></td>
                                    <td><span class="badge bg-success"><?= $currencySymbol ?><?= number_format($task['task_price'], 2) ?></span></td>
                                    <td><?= getFreelancerStatusBadge($task['status']) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>?page=update_freelancer_task&id=<?= $task['id'] ?>" class="btn btn-sm btn-primary">View/Update</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No tasks assigned yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>