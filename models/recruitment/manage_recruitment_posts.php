<?php
/**
 * views/admin/recruitment/manage_recruitment_posts.php
 *
 * This file allows administrators to manage recruitment posts submitted by DEOs.
 * It provides functionalities to view, approve, reject, edit, and delete posts.
 */

require_once __DIR__ . '/../../../config.php';
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';
require_once MODELS_PATH . 'recruitment/recruitment_post.php'; // Include the new model

// Restrict access to admin users only.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB();
$adminUserId = $_SESSION['user_id'];
$message = '';

// Get filter/search parameters
$filter_status = $_GET['status'] ?? 'all';
$search_query = trim($_GET['search'] ?? '');

// Handle actions (Approve, Reject, Delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $postId = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        if (approveRecruitmentPost($postId, $adminUserId)) {
            $message = '<div class="alert alert-success" role="alert">Recruitment post approved successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger" role="alert">Failed to approve post or post not found.</div>';
        }
    } elseif ($action === 'reject') {
        if (rejectRecruitmentPost($postId, $adminUserId)) {
            $message = '<div class="alert alert-success" role="alert">Recruitment post rejected successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger" role="alert">Failed to reject post or post not found.</div>';
        }
    } elseif ($action === 'delete') {
        if (deleteRecruitmentPost($postId)) {
            $message = '<div class="alert alert-success" role="alert">Recruitment post deleted successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger" role="alert">Failed to delete post or post not found.</div>';
        }
    }
    // Redirect to clear GET parameters after action
    header('Location: ' . BASE_URL . '?page=manage_recruitment_posts&status=' . urlencode($filter_status) . '&search=' . urlencode($search_query));
    exit;
}

// Fetch recruitment posts based on filters
$recruitmentPosts = getAllRecruitmentPosts($filter_status, $search_query);

// Include header and sidebar
include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Manage Recruitment Posts</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; ?>
            <script>
                setupAutoHideAlerts();
            </script>
        <?php endif; ?>

        <!-- Filter and Search Form -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-dark"><i class="fas fa-filter me-2"></i>Filter Posts</h5>
            </div>
            <div class="card-body">
                <form action="" method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="page" value="manage_recruitment_posts">
                    <div class="col-md-4">
                        <label for="statusFilter" class="form-label">Approval Status:</label>
                        <select class="form-select rounded-pill" id="statusFilter" name="status">
                            <option value="all" <?= ($filter_status === 'all') ? 'selected' : '' ?>>All</option>
                            <option value="pending" <?= ($filter_status === 'pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= ($filter_status === 'approved') ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= ($filter_status === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="postSearch" class="form-label">Search:</label>
                        <input type="text" class="form-control rounded-pill" id="postSearch" name="search" placeholder="Job Title or Submitted By" value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary rounded-pill w-100"><i class="fas fa-search me-2"></i>Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recruitment Posts List -->
        <div class="card shadow-sm rounded-3">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>All Recruitment Posts</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Job Title</th>
                                <th>Submitted By</th>
                                <th>Submitted At</th>
                                <th>Approval Status</th>
                                <th>Approved By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recruitmentPosts)): ?>
                                <?php foreach ($recruitmentPosts as $post): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($post['id']) ?></td>
                                        <td><?= htmlspecialchars($post['job_title']) ?></td>
                                        <td><?= htmlspecialchars($post['submitted_by_name'] ?? 'N/A') ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getApprovalStatusBadgeColor($post['approval_status']) ?>">
                                                <?= ucwords(htmlspecialchars(str_replace('_', ' ', $post['approval_status']))) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($post['approved_by_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>?page=add_recruitment_post&id=<?= htmlspecialchars($post['id']) ?>" class="btn btn-sm btn-outline-info rounded-pill me-1" title="View/Edit Post"><i class="fas fa-eye"></i> View/Edit</a>
                                            <?php if ($post['approval_status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-sm btn-success rounded-pill me-1"
                                                        onclick="showCustomConfirm('Approve Post', 'Are you sure you want to approve this post?', '<?= BASE_URL ?>?page=manage_recruitment_posts&action=approve&id=<?= htmlspecialchars($post['id']) ?>&status=<?= urlencode($filter_status) ?>&search=<?= urlencode($search_query) ?>')"
                                                        title="Approve Post">
                                                    <i class="fas fa-check-circle"></i> Approve
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning rounded-pill me-1"
                                                        onclick="showCustomConfirm('Reject Post', 'Are you sure you want to reject this post?', '<?= BASE_URL ?>?page=manage_recruitment_posts&action=reject&id=<?= htmlspecialchars($post['id']) ?>&status=<?= urlencode($filter_status) ?>&search=<?= urlencode($search_query) ?>')"
                                                        title="Reject Post">
                                                    <i class="fas fa-times-circle"></i> Reject
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-danger rounded-pill"
                                                    onclick="showCustomConfirm('Delete Post', 'Are you sure you want to delete this post permanently?', '<?= BASE_URL ?>?page=manage_recruitment_posts&action=delete&id=<?= htmlspecialchars($post['id']) ?>&status=<?= urlencode($filter_status) ?>&search=<?= urlencode($search_query) ?>')"
                                                    title="Delete Post">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No recruitment posts found for the selected filters.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Custom Confirmation Modal (replaces alert/confirm) -->
<div class="modal fade" id="customConfirmModal" tabindex="-1" aria-labelledby="customConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="customConfirmModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirmation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p id="confirm-message" class="lead text-center"></p>
            </div>
            <div class="modal-footer border-0 rounded-bottom-4 justify-content-center">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirm-link" class="btn btn-danger rounded-pill">Confirm</a>
            </div>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>

<script>
    // Custom confirm dialog function (re-used across files for consistency)
    function showCustomConfirm(title, message, link) {
        const confirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
        document.getElementById('customConfirmModalLabel').textContent = title;
        document.getElementById('confirm-message').textContent = message;
        document.getElementById('confirm-link').href = link;
        confirmModal.show();
    }

    // Auto-hide alert functionality (re-used from users.php, ensuring consistency)
    document.addEventListener('DOMContentLoaded', function() {
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
    });
</script>

<style>
    /* Custom CSS for fade-out alert (re-used from users.php, ensuring consistency) */
    .alert.fade-out {
        opacity: 0;
        transition: opacity 0.5s ease-out;
    }
</style>
