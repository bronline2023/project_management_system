<?php
/**
 * user/my_tasks.php
 *
 * This file displays tasks assigned to the currently logged-in user.
 * It fetches tasks from the database and allows filtering by status.
 *
 * It ensures that only authenticated users (including DEOs now) can access this page.
 */

// Include the configuration file for database connection and session management.
require_once ROOT_PATH . 'config.php';
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';
require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php'; // Include recruitment model for DEO tasks

// Restrict access to authenticated users.
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$message = '';

$filterStatus = $_GET['status'] ?? 'all'; // 'all', 'pending', 'in_progress', 'completed', etc.

// --- Fetch Tasks for the Current User ---
$tasks = [];
$sql = "SELECT t.*, u_assigner.name AS assigned_by_name
        FROM tasks t
        LEFT JOIN users u_assigner ON t.assigned_by_user_id = u_assigner.id
        WHERE t.assigned_to_user_id = :user_id";
$params = [':user_id' => $currentUserId];

if ($filterStatus !== 'all') {
    $sql .= " AND t.status = :status";
    $params[':status'] = $filterStatus;
}

$sql .= " ORDER BY t.due_date ASC, t.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching user tasks: " . $e->getMessage());
    $message = '<div class="alert alert-danger" role="alert">Error loading your tasks: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Helper function to get Bootstrap badge color for task status
if (!function_exists('getTaskStatusBadgeColor')) {
    function getTaskStatusBadgeColor($status) {
        switch ($status) {
            case 'pending': return 'warning';
            case 'in_progress': return 'info';
            case 'completed': return 'success';
            case 'on_hold': return 'secondary';
            case 'cancelled': return 'danger';
            default: return 'secondary';
        }
    }
}

include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">My Tasks</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; ?>
            <script>
                setupAutoHideAlerts();
            </script>
        <?php endif; ?>

        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Tasks</h5>
            </div>
            <div class="card-body">
                <form action="" method="GET" class="row g-3 align-items-center">
                    <input type="hidden" name="page" value="my_tasks">
                    <div class="col-md-4">
                        <label for="statusFilter" class="form-label visually-hidden">Filter by Status</label>
                        <select class="form-select rounded-pill" id="statusFilter" name="status" onchange="this.form.submit()">
                            <option value="all" <?= ($filterStatus === 'all') ? 'selected' : '' ?>>All Statuses</option>
                            <option value="pending" <?= ($filterStatus === 'pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="in_progress" <?= ($filterStatus === 'in_progress') ? 'selected' : '' ?>>In Progress</option>
                            <option value="completed" <?= ($filterStatus === 'completed') ? 'selected' : '' ?>>Completed</option>
                            <option value="on_hold" <?= ($filterStatus === 'on_hold') ? 'selected' : '' ?>>On Hold</option>
                            <option value="cancelled" <?= ($filterStatus === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Apply Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm rounded-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Your Assigned Tasks</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($tasks)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Task Name</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Due Date</th>
                                    <th>Assigned By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $task): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($task['id']) ?></td>
                                        <td><?= htmlspecialchars($task['task_name']) ?></td>
                                        <td>
                                            <?php
                                            // Display full description in a tooltip or modal if it's long
                                            $shortDescription = htmlspecialchars(substr($task['description'], 0, 100));
                                            if (strlen($task['description']) > 100) {
                                                echo $shortDescription . '... <a href="#" data-bs-toggle="modal" data-bs-target="#taskDescriptionModal" data-description="' . htmlspecialchars($task['description']) . '">Read More</a>';
                                            } else {
                                                echo $shortDescription;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= ucwords(str_replace('_', ' ', htmlspecialchars($task['task_type']))) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= getTaskStatusBadgeColor($task['status']) ?>">
                                                <?= ucwords(htmlspecialchars(str_replace('_', ' ', $task['status']))) ?>
                                            </span>
                                        </td>
                                        <td><?= $task['due_date'] ? date('Y-m-d', strtotime($task['due_date'])) : 'N/A' ?></td>
                                        <td><?= htmlspecialchars($task['assigned_by_name'] ?? 'Admin') ?></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>?page=update_task&id=<?= htmlspecialchars($task['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill me-1" title="Update Task">
                                                <i class="fas fa-sync-alt"></i> Update
                                            </a>
                                            <?php if ($task['task_type'] === 'recruitment_data_entry' && $userRole === 'data_entry_operator'): ?>
                                                <?php
                                                // Attempt to parse description for a link if it's a recruitment task
                                                $link = '';
                                                if (preg_match('/(http[s]?:\/\/[^\s]+)/', $task['description'], $matches)) {
                                                    $link = $matches[0];
                                                }
                                                ?>
                                                <?php if (!empty($link)): ?>
                                                    <a href="<?= htmlspecialchars($link) ?>" target="_blank" class="btn btn-sm btn-outline-info rounded-pill" title="View Recruitment Link">
                                                        <i class="fas fa-external-link-alt"></i> View Link
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No tasks assigned to you.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Task Description Modal -->
<div class="modal fade" id="taskDescriptionModal" tabindex="-1" aria-labelledby="taskDescriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-info text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="taskDescriptionModalLabel"><i class="fas fa-file-alt me-2"></i>Task Description</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p id="full-task-description"></p>
            </div>
            <div class="modal-footer border-0 rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alert functionality
        const alertElement = document.querySelector('.alert.fade.show');
        if (alertElement) {
            setTimeout(function() {
                const bootstrapAlert = bootstrap.Alert.getInstance(alertElement);
                if (bootstrapAlert) {
                    bootstrapAlert.close();
                } else {
                    alertElement.classList.add('fade-out');
                    setTimeout(() => alertElement.remove(), 500);
                }
            }, 5000); // 5 seconds
        }

        // Populate task description modal
        const taskDescriptionModal = document.getElementById('taskDescriptionModal');
        if (taskDescriptionModal) {
            taskDescriptionModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // Button that triggered the modal
                const description = button.getAttribute('data-description');
                const modalBodyP = taskDescriptionModal.querySelector('#full-task-description');
                modalBodyP.textContent = description;
            });
        }
    });
</script>

<style>
    /* Custom CSS for fade-out alert */
    .alert.fade-out {
        opacity: 0;
        transition: opacity 0.5s ease-out;
    }
</style>
