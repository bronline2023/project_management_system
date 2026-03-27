<?php
// views/admin/manage_custom_pages.php
if (!in_array($_SESSION['user_role'] ?? '', ['master_admin', 'admin'])) {
    echo "Access Denied."; exit;
}

$pdo = connectDB();
$pages = $pdo->query("SELECT * FROM custom_pages ORDER BY created_at DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Custom Website Pages</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPageModal"><i class="fas fa-plus"></i> Create New Page</button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>URL Slug</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pages as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['title']) ?></td>
                            <td><a href="<?= BASE_URL ?>?page=b2c_page&slug=<?= $p['slug'] ?>" target="_blank"><code><?= $p['slug'] ?></code></a></td>
                            <td><?= date('d M Y, h:i A', strtotime($p['created_at'])) ?></td>
                            <td>
                                <?php if($p['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#editPageModal_<?= $p['id'] ?>">Edit</button>
                                <form action="index.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this page?');">
                                    <input type="hidden" name="action" value="delete_custom_page">
                                    <input type="hidden" name="page_id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="page" value="manage_custom_pages">
                                    <button class="btn btn-sm btn-danger text-white"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        
                        <!-- Edit Modal for <?= $p['id'] ?> -->
                        <div class="modal fade" id="editPageModal_<?= $p['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Page</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="index.php" method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="edit_custom_page">
                                            <input type="hidden" name="page_id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="page" value="manage_custom_pages">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Page Title</label>
                                                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($p['title']) ?>" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">URL Slug</label>
                                                    <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($p['slug']) ?>" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Page Content (HTML Allowed)</label>
                                                <textarea name="content" class="form-control" rows="10"><?= htmlspecialchars($p['content']) ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-select">
                                                    <option value="active" <?= $p['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                                    <option value="inactive" <?= $p['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if(empty($pages)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No custom pages found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Page Modal -->
<div class="modal fade" id="addPageModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Page</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="index.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_custom_page">
                    <input type="hidden" name="page" value="manage_custom_pages">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Page Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">URL Slug (e.g. about-us)</label>
                            <input type="text" name="slug" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Page Content (HTML Allowed)</label>
                        <textarea name="content" class="form-control" rows="10"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Page</button>
                </div>
            </form>
        </div>
    </div>
</div>
