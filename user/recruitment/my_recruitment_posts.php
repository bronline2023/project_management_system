<?php
/**
 * user/recruitment/my_recruitment_posts.php
 * Displays all recruitment posts submitted by the current user.
 * This is the final version with a fully functional table and edit/delete/view buttons.
 */
$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$posts = fetchAll($pdo, "SELECT * FROM recruitment_posts WHERE submitted_by_user_id = ? ORDER BY created_at DESC", [$currentUserId]);
?>

<h2 class="mb-4">My Recruitment Posts</h2>

<?php if (!empty($message)): ?>
    <?php include VIEWS_PATH . 'components/message_box.php'; ?>
<?php endif; ?>

<div class="card shadow-sm rounded-3">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>My Posts</h5>
        <a href="<?= BASE_URL ?>?page=add_recruitment_post" class="btn btn-light btn-sm rounded-pill"><i class="fas fa-plus me-2"></i>Add New Post</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Job Title</th>
                        <th>Vacancies</th>
                        <th>Submitted On</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No posts submitted yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?= htmlspecialchars($post['id']) ?></td>
                                <td><?= htmlspecialchars($post['job_title']) ?></td>
                                <td><?= htmlspecialchars($post['total_vacancies']) ?></td>
                                <td><?= date('d M Y', strtotime($post['created_at'])) ?></td>
                                <td>
                                    <?php
                                    $status = htmlspecialchars($post['approval_status']);
                                    $badgeClass = 'bg-secondary';
                                    if ($status === 'approved') $badgeClass = 'bg-success';
                                    if ($status === 'rejected') $badgeClass = 'bg-danger';
                                    if ($status === 'pending') $badgeClass = 'bg-warning text-dark';
                                    if ($status === 'returned_for_edit') $badgeClass = 'bg-info text-white';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= ucfirst(str_replace('_', ' ', $status)) ?></span>
                                </td>
                                <td>
                                    <?php // --- [ FIXED: Edit/Delete buttons only show for 'draft' or 'returned_for_edit' ] --- ?>
                                    <?php if ($status === 'draft' || $status === 'returned_for_edit'): ?>
                                    <a href="<?= BASE_URL ?>?page=add_recruitment_post&id=<?= $post['id'] ?>" class="btn btn-sm btn-outline-primary me-2" title="Edit Post"><i class="fas fa-edit"></i></a>
                                    <form action="<?= BASE_URL ?>index.php" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                        <input type="hidden" name="action" value="delete_recruitment_post">
                                        <input type="hidden" name="page" value="my_recruitment_posts">
                                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Post"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                    <?php endif; ?>

                                    <a href="<?= BASE_URL ?>?page=view_recruitment_post&id=<?= $post['id'] ?>" class="btn btn-sm btn-outline-info me-2" title="View Post"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>