<?php
/**
 * admin/manage_notices.php
 * Interface for Admin to post and manage notices.
 */

require_once MODELS_PATH . 'notices.php';
require_once MODELS_PATH . 'db.php';

$pdo = connectDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_notice') {
        $title = trim($_POST['title']);
        $content = trim($_POST['message']);
        $type = $_POST['target_type'];
        $userId = ($type === 'specific' && !empty($_POST['target_user_id'])) ? $_POST['target_user_id'] : null;
        
        if (createNotice($title, $content, $type, $userId, $_SESSION['user_id'])) {
            $message = "Notice posted successfully!";
        } else {
            $message = "Error posting notice.";
        }
    } elseif ($_POST['action'] === 'delete_notice') {
        if (deleteNotice($_POST['id'])) {
            $message = "Notice deleted.";
        }
    }
}

$notices = getAllNotices();
$users = fetchAll($pdo, "SELECT id, name FROM users WHERE id != ? ORDER BY name ASC", [$_SESSION['user_id']]);
?>

<div class="row mb-4 align-items-center">
    <div class="col">
        <h2>Manage Notice Board</h2>
        <p class="text-muted">Broadcast messages to all users or specific individuals.</p>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNoticeModal">
            <i class="fas fa-plus-circle me-2"></i>Post New Notice
        </button>
    </div>
</div>

<?php if ($message) echo '<div class="alert alert-info alert-dismissible fade show">' . $message . '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>'; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Title</th>
                        <th>Target</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notices)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No notices found.</td></tr>
                    <?php else: foreach ($notices as $n): ?>
                        <tr>
                            <td><?= date('d M, Y', strtotime($n['created_at'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($n['title']) ?></strong>
                                <div class="small text-muted text-truncate" style="max-width: 300px;"><?= htmlspecialchars($n['message']) ?></div>
                            </td>
                            <td>
                                <?php if ($n['target_type'] === 'all'): ?>
                                    <span class="badge bg-success text-white">All Users</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-white">User: <?= htmlspecialchars($n['target_name']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($n['is_active']): ?>
                                    <span class="badge bg-primary">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <form action="index.php?page=manage_notices" method="POST" class="d-inline" onsubmit="return confirm('Delete this notice?');">
                                    <input type="hidden" name="action" value="delete_notice">
                                    <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Notice Modal -->
<div class="modal fade" id="addNoticeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="index.php?page=manage_notices" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_notice">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Server Maintenance" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Message</label>
                        <textarea name="message" class="form-control" rows="4" placeholder="Detailed message..." required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Target Audience</label>
                        <select name="target_type" id="targetType" class="form-select" onchange="toggleUserSelect()">
                            <option value="all">All Registered Users</option>
                            <option value="specific">Specific Individual</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="userSelectBox" style="display:none;">
                        <label class="form-label fw-bold">Select User</label>
                        <select name="target_user_id" class="form-select">
                            <option value="">-- Search User --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Post Notice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleUserSelect() {
    const type = document.getElementById('targetType').value;
    document.getElementById('userSelectBox').style.display = (type === 'specific') ? 'block' : 'none';
}
</script>
