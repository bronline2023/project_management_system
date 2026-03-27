<?php
// views/admin/b2c_manage_menus.php
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'master_admin') {
    die('<div class="alert alert-danger">Access Denied</div>');
}

$pdo = connectDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_b2c_menu') {
        $title = trim($_POST['title']);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'] ?: $title)));
        $link = $_POST['link'] ?? '';
        $parent_id = (int)$_POST['parent_id'];
        $is_dynamic = isset($_POST['is_dynamic_page']) ? 1 : 0;
        $page_content = $_POST['page_content'] ?? '';
        $display_order = (int)($_POST['display_order'] ?? 0);

        $stmt = $pdo->prepare("INSERT INTO b2c_menus (title, slug, link, parent_id, is_dynamic_page, page_content, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $link, $parent_id, $is_dynamic, $page_content, $display_order]);
        $_SESSION['status_message'] = '<div class="alert alert-success">Menu item added successfully!</div>';
        echo "<script>window.location.href='" . BASE_URL . "?page=manage_b2c_menus';</script>";
        exit;
    }

    if ($_POST['action'] === 'edit_b2c_menu') {
        $id = (int)$_POST['menu_id'];
        $title = trim($_POST['title']);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'] ?: $title)));
        $link = $_POST['link'] ?? '';
        $parent_id = (int)$_POST['parent_id'];
        $is_dynamic = isset($_POST['is_dynamic_page']) ? 1 : 0;
        $page_content = $_POST['page_content'] ?? '';
        $display_order = (int)($_POST['display_order'] ?? 0);

        $stmt = $pdo->prepare("UPDATE b2c_menus SET title=?, slug=?, link=?, parent_id=?, is_dynamic_page=?, page_content=?, display_order=? WHERE id=?");
        $stmt->execute([$title, $slug, $link, $parent_id, $is_dynamic, $page_content, $display_order, $id]);
        $_SESSION['status_message'] = '<div class="alert alert-success">Menu item updated successfully!</div>';
        echo "<script>window.location.href='" . BASE_URL . "?page=manage_b2c_menus';</script>";
        exit;
    }
}

if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    $pdo->prepare("DELETE FROM b2c_menus WHERE id = ?")->execute([$delId]);
    $pdo->prepare("UPDATE b2c_menus SET parent_id = 0 WHERE parent_id = ?")->execute([$delId]); 
    $_SESSION['status_message'] = '<div class="alert alert-success">Menu item deleted!</div>';
    echo "<script>window.location.href='" . BASE_URL . "?page=manage_b2c_menus';</script>";
    exit;
}

$menus = $pdo->query("SELECT * FROM b2c_menus ORDER BY parent_id ASC, display_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$parent_menus = array_filter($menus, function($m) { return $m['parent_id'] == 0; });
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-bars text-primary"></i> Manage B2C Menus & Pages</h1>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addMenuModal"><i class="fas fa-plus"></i> Add New Menu / Page</button>
</div>

<div class="card shadow-sm border-0" style="border-radius: 12px;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>URL / Slug</th>
                        <th>Order</th>
                        <th>Parent</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($menus) > 0): ?>
                        <?php foreach($menus as $menu): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($menu['title']) ?></td>
                            <td>
                                <?php if($menu['is_dynamic_page']): ?>
                                    <span class="badge bg-info">Dynamic Page</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Custom Link</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($menu['is_dynamic_page'] ? $menu['slug'] : $menu['link']) ?></td>
                            <td><?= $menu['display_order'] ?></td>
                            <td>
                                <?php 
                                    if($menu['parent_id'] > 0) {
                                        $p = array_filter($parent_menus, function($m) use($menu) { return $m['id'] == $menu['parent_id']; });
                                        if(!empty($p)) echo htmlspecialchars(array_values($p)[0]['title']);
                                    } else {
                                        echo '<span class="text-muted">None (Main)</span>';
                                    }
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editMenuModal<?= $menu['id'] ?>"><i class="fas fa-edit"></i> Edit</button>
                                <a href="<?= BASE_URL ?>?page=manage_b2c_menus&delete_id=<?= $menu['id'] ?>" class="btn btn-sm btn-outline-danger shadow-sm" onclick="return confirm('Are you sure you want to delete this menu?');"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>

                        <!-- Edit Modal for <?= $menu['id'] ?> -->
                        <div class="modal fade" id="editMenuModal<?= $menu['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-xl">
                                <form class="modal-content" method="POST">
                                    <input type="hidden" name="action" value="edit_b2c_menu">
                                    <input type="hidden" name="menu_id" value="<?= $menu['id'] ?>">
                                    <div class="modal-header bg-light">
                                        <h5 class="modal-title fw-bold">Edit Menu: <?= htmlspecialchars($menu['title']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label>Title</label>
                                                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($menu['title']) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Slug (URL format)</label>
                                                <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($menu['slug']) ?>" placeholder="Leave blank to auto-generate from title">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <div class="form-check mt-4 ms-2 form-switch">
                                                    <input class="form-check-input" type="checkbox" name="is_dynamic_page" value="1" id="isDynamicEdit<?= $menu['id'] ?>" <?= $menu['is_dynamic_page'] ? 'checked' : '' ?> onchange="toggleContentBox('<?= $menu['id'] ?>')">
                                                    <label class="form-check-label fw-bold" for="isDynamicEdit<?= $menu['id'] ?>">Is Dynamic Content Page?</label>
                                                </div>
                                            </div>
                                            <div class="col-md-8 mb-3 linkBox<?= $menu['id'] ?>" style="display: <?= $menu['is_dynamic_page'] ? 'none' : 'block' ?>;">
                                                <label>Custom Link URL</label>
                                                <input type="text" name="link" class="form-control" value="<?= htmlspecialchars($menu['link']) ?>">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label>Parent Menu</label>
                                                <select name="parent_id" class="form-select">
                                                    <option value="0">None (Main Menu Item)</option>
                                                    <?php foreach($parent_menus as $pm): if($pm['id'] != $menu['id']): ?>
                                                        <option value="<?= $pm['id'] ?>" <?= $menu['parent_id'] == $pm['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pm['title']) ?></option>
                                                    <?php endif; endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Display Order</label>
                                                <input type="number" name="display_order" class="form-control" value="<?= $menu['display_order'] ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3 contentBox<?= $menu['id'] ?>" style="display: <?= $menu['is_dynamic_page'] ? 'block' : 'none' ?>;">
                                            <label>Page HTML Content</label>
                                            <textarea name="page_content" class="form-control" rows="10"><?= htmlspecialchars($menu['page_content']) ?></textarea>
                                            <small class="text-info">You can use standard HTML here (e.g., &lt;h1&gt;, &lt;p&gt;, Bootstrap classes).</small>
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
                        <tr><td colspan="6" class="text-center text-muted py-4">No menus found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addMenuModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form class="modal-content" method="POST">
            <input type="hidden" name="action" value="add_b2c_menu">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Add New Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Slug (URL format - optional)</label>
                        <input type="text" name="slug" class="form-control" placeholder="Leave blank to auto-generate">
                    </div>
                </div>
                <div class="row align-items-center">
                    <div class="col-md-4 mb-3">
                        <div class="form-check form-switch mt-4 ms-2">
                            <input class="form-check-input" type="checkbox" name="is_dynamic_page" value="1" id="isDynamicAdd" checked onchange="toggleContentBox('Add')">
                            <label class="form-check-label fw-bold" for="isDynamicAdd">Is Dynamic Content Page?</label>
                        </div>
                    </div>
                    <div class="col-md-8 mb-3 linkBoxAdd" style="display: none;">
                        <label>Custom Link URL</label>
                        <input type="text" name="link" class="form-control" placeholder="http://...">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Parent Menu</label>
                        <select name="parent_id" class="form-select">
                            <option value="0">None (Main Menu Item)</option>
                            <?php foreach($parent_menus as $pm): ?>
                                <option value="<?= $pm['id'] ?>"><?= htmlspecialchars($pm['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Display Order</label>
                        <input type="number" name="display_order" class="form-control" value="0">
                    </div>
                </div>
                <div class="mb-3 contentBoxAdd">
                    <label>Page HTML Content</label>
                    <textarea name="page_content" class="form-control" rows="10" placeholder="Type your HTML content here..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary shadow-sm"><i class="fas fa-save"></i> Save Menu</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleContentBox(id) {
    let cb = document.getElementById(id === 'Add' ? 'isDynamicAdd' : ('isDynamicEdit' + id));
    let contentBox = document.querySelector('.contentBox' + id);
    let linkBox = document.querySelector('.linkBox' + id);
    
    if(cb.checked) {
        contentBox.style.display = 'block';
        linkBox.style.display = 'none';
    } else {
        contentBox.style.display = 'none';
        linkBox.style.display = 'block';
    }
}
</script>
