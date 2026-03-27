<?php
/**
 * views/digital_drafts.php
 * List saved digital service drafts for the logged-in user.
 */

if (!isset($_SESSION['user_id'])) {
    echo "<div class='container mt-5 text-center'><h3>Please login to view your drafts.</h3></div>";
    return;
}

$pdo = connectDB();
$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id, service_name, service_slug, draft_name, created_at FROM digital_service_history WHERE user_id = ? AND is_draft = 1 ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $drafts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    $drafts = [];
}
?>

<div class="container-fluid py-4">
    <?php if(isset($_SESSION['status_message'])): ?>
        <?= $_SESSION['status_message'] ?>
        <?php unset($_SESSION['status_message']); ?>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <h3 class="fw-bold text-dark m-0"><i class="fas fa-save text-primary"></i> My Saved Drafts</h3>
        <div class="search-box" style="flex: 1; max-width: 400px; position: relative;">
            <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
            <input type="text" id="draftSearch" class="form-control" placeholder="Search by name or service..." style="padding-left: 40px; border-radius: 50px; border: 2px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
        </div>
        <a href="<?= BASE_URL ?>?page=dashboard" class="btn btn-outline-secondary btn-sm rounded-pill fw-bold"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="row">
        <?php if (empty($drafts)): ?>
            <div class="col-12 text-center py-5 bg-white border rounded shadow-sm">
                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                <h5 class="text-muted fw-bold">No saved drafts found.</h5>
                <p class="text-secondary small">Start designing in the Digital Studio and click "Save Draft" to see them here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($drafts as $draft): 
                $dName = !empty($draft['draft_name']) ? $draft['draft_name'] : $draft['service_name'];
                $searchData = $dName . ' ' . $draft['service_name'] . ' ' . $draft['service_slug'];
            ?>
                <div class="col-md-4 mb-4 draft-item" data-search="<?= htmlspecialchars($searchData) ?>">
                    <div class="card shadow-sm border-0 h-100" style="border-radius: 12px; overflow: hidden;">
                        <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #1e293b, #0f172a);">
                            <h5 class="mb-0 fw-bold text-white"><i class="fas fa-paint-brush me-2 text-info"></i> <?= htmlspecialchars(!empty($draft['draft_name']) ? $draft['draft_name'] : $draft['service_name']) ?></h5>
                        </div>
                        <div class="card-body bg-light">
                            <p class="text-muted small fw-bold mb-1"><i class="far fa-calendar-alt"></i> Saved On:</p>
                            <p class="text-dark fw-bolder mb-3"><?= date('d M Y, h:i A', strtotime($draft['created_at'])) ?></p>
                            
                            <div class="d-flex gap-2">
                                <a href="?page=<?= $draft['service_slug'] ?>&draft_id=<?= $draft['id'] ?>" class="btn btn-success flex-grow-1 fw-bold"><i class="fas fa-external-link-alt"></i> Open</a>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this draft?');">
                                    <input type="hidden" name="action" value="delete_digital_draft">
                                    <input type="hidden" name="id" value="<?= $draft['id'] ?>">
                                    <input type="hidden" name="page" value="digital_drafts">
                                    <button type="submit" class="btn btn-outline-danger fw-bold px-3" title="Delete Draft"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('draftSearch').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.draft-item');
    cards.forEach(card => {
        const text = card.getAttribute('data-search').toLowerCase();
        if (text.includes(term)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>
