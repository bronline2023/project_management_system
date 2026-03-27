<?php
/**
 * views/digital_drafts.php
 * View user saved drafts for Digital Services.
 */

if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-danger text-center mt-4 fw-bold'>Please login to view your saved drafts.</div>";
    return;
}

$pdo = connectDB();
$userId = $_SESSION['user_id'];

if (isset($_POST['delete_draft_id'])) {
    $draftId = (int)$_POST['delete_draft_id'];
    try {
        // Fetch to see if it's a file
        $s = $pdo->prepare("SELECT canvas_json FROM digital_service_history WHERE id = ? AND user_id = ?");
        $s->execute([$draftId, $userId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if ($row && str_starts_with($row['canvas_json'], 'FILE:')) {
            $filename = str_replace('FILE:', '', $row['canvas_json']);
            $filepath = UPLOADS_PATH . 'drafts/' . $filename;
            if (file_exists($filepath)) unlink($filepath);
        }
        $dStmt = $pdo->prepare("DELETE FROM digital_service_history WHERE id = ? AND user_id = ?");
        $dStmt->execute([$draftId, $userId]);
        $_SESSION['flash_msg'] = "Draft deleted successfully.";
        header("Location: ?page=digital_drafts");
        exit;
    } catch(Exception $e) {}
}

try {
    $stmt = $pdo->prepare("SELECT id, service_name, service_slug, created_at FROM digital_service_history WHERE user_id = ? AND is_draft = 1 ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $drafts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $drafts = [];
}
?>

<div class="container-fluid py-4">
    <?php if(isset($_SESSION['flash_msg'])): ?>
        <div class="alert alert-success fw-bold"><i class="fas fa-check-circle"></i> <?= $_SESSION['flash_msg'] ?></div>
        <?php unset($_SESSION['flash_msg']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark"><i class="fas fa-save text-primary"></i> My Saved Drafts</h3>
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
            <?php foreach ($drafts as $draft): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0 h-100" style="border-radius: 12px; overflow: hidden;">
                        <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #1e293b, #0f172a);">
                            <h5 class="mb-0 fw-bold text-white"><i class="fas fa-paint-brush me-2 text-info"></i> <?= htmlspecialchars($draft['service_name']) ?></h5>
                        </div>
                        <div class="card-body bg-light">
                            <p class="text-muted small fw-bold mb-1"><i class="far fa-calendar-alt"></i> Saved On:</p>
                            <p class="text-dark fw-bolder mb-3"><?= date('d M Y, h:i A', strtotime($draft['created_at'])) ?></p>
                            
                            <div class="d-flex gap-2">
                                <a href="?page=<?= $draft['service_slug'] ?>&draft_id=<?= $draft['id'] ?>" class="btn btn-success flex-grow-1 fw-bold"><i class="fas fa-external-link-alt"></i> Open</a>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this draft?');">
                                    <input type="hidden" name="delete_draft_id" value="<?= $draft['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger fw-bold px-3"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
