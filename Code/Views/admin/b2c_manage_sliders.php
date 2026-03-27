<?php
// views/admin/b2c_manage_sliders.php
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'master_admin') {
    die('<div class="alert alert-danger">Access Denied</div>');
}

$pdo = connectDB();



$sliders = $pdo->query("SELECT * FROM b2c_sliders ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-images text-primary"></i> Manage B2C Sliders / Video</h1>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addSliderModal"><i class="fas fa-plus"></i> Add New Slide</button>
</div>

<div class="card shadow-sm border-0" style="border-radius: 12px;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Media</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($sliders) > 0): ?>
                        <?php foreach($sliders as $sld): ?>
                        <tr>
                            <td>
                                <?php if($sld['media_type'] === 'video'): ?>
                                    <video src="<?= BASE_URL . $sld['media_path'] ?>" style="width: 100px; height: 60px; object-fit: cover;" muted></video>
                                <?php else: ?>
                                    <img src="<?= BASE_URL . $sld['media_path'] ?>" alt="Slide" style="width: 100px; height: 60px; object-fit: cover; border-radius: 5px;">
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold"><?= htmlspecialchars($sld['title']) ?></td>
                            <td><span class="badge bg-secondary"><?= strtoupper($sld['media_type']) ?></span></td>
                            <td><?= $sld['display_order'] ?></td>
                            <td><span class="badge bg-<?= $sld['status']=='active'?'success':'danger' ?>"><?= ucfirst($sld['status']) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSliderModal<?= $sld['id'] ?>"><i class="fas fa-edit"></i> Edit</button>
                                <form method="POST" action="<?= BASE_URL ?>app/actions.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this slide?');">
                                    <input type="hidden" name="action" value="delete_b2c_slider">
                                    <input type="hidden" name="slider_id" value="<?= $sld['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Modal for <?= $sld['id'] ?> -->
                        <div class="modal fade" id="editSliderModal<?= $sld['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <form class="modal-content" method="POST" action="<?= BASE_URL ?>app/actions.php" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="edit_b2c_slider">
                                    <input type="hidden" name="slider_id" value="<?= $sld['id'] ?>">
                                    <div class="modal-header bg-light">
                                        <h5 class="modal-title fw-bold">Edit Slide</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label>Title</label>
                                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($sld['title']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Description (Optional)</label>
                                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($sld['description']) ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label>Redirect Link (Optional)</label>
                                            <input type="text" name="link" class="form-control" value="<?= htmlspecialchars($sld['link']) ?>">
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <label>Media Type</label>
                                                <select name="media_type" class="form-select">
                                                    <option value="image" <?= $sld['media_type'] == 'image' ? 'selected' : '' ?>>Image</option>
                                                    <option value="video" <?= $sld['media_type'] == 'video' ? 'selected' : '' ?>>Video</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label>Display Order</label>
                                                <input type="number" name="display_order" class="form-control" value="<?= $sld['display_order'] ?>">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label>Status</label>
                                            <select name="status" class="form-select">
                                                <option value="active" <?= $sld['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                                <option value="inactive" <?= $sld['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label>Update Media File (Optional - Leaves current if empty)</label>
                                            <input type="file" name="media" class="form-control" accept="image/*,video/*">
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light">
                                        <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary shadow-sm"><i class="fas fa-save"></i> Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No sliders found. Click "Add New Slide" to get started.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Slider Modal -->
<div class="modal fade" id="addSliderModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="<?= BASE_URL ?>app/actions.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_b2c_slider">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Add New Slide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Description (Optional)</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label>Redirect Link (Optional)</label>
                    <input type="text" name="link" class="form-control" placeholder="https://...">
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label>Media Type</label>
                        <select name="media_type" class="form-select">
                            <option value="image">Image</option>
                            <option value="video">Video</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label>Display Order</label>
                        <input type="number" name="display_order" class="form-control" value="0">
                    </div>
                </div>
                <div class="mb-3">
                    <label>Upload Media File</label>
                    <input type="file" name="media" class="form-control" accept="image/*,video/*" required>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary shadow-sm"><i class="fas fa-upload"></i> Upload & Save</button>
            </div>
        </form>
    </div>
</div>
