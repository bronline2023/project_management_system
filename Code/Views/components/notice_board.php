<?php
/**
 * views/components/notice_board.php
 * A beautiful, alert-style notice board for the dashboard.
 */

require_once MODELS_PATH . 'notices.php';
$notices = getActiveNotices($_SESSION['user_id'] ?? null);

if (!empty($notices)):
?>
<div class="notice-board mb-4">
    <?php foreach ($notices as $n): ?>
        <div class="alert alert-notice shadow-sm border-0 mb-3 animate__animated animate__fadeInDown" 
             style="background: #fff; border-left: 5px solid #00a884 !important; border-radius: 12px; position: relative; overflow: hidden;">
            
            <div class="d-flex align-items-center">
                <div class="notice-icon p-3 me-3" style="background: rgba(0, 168, 132, 0.1); border-radius: 50%;">
                    <i class="fas fa-bullhorn text-success fs-4"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="alert-heading mb-1 text-dark fw-bold"><?= htmlspecialchars($n['title']) ?></h5>
                        <small class="text-muted"><i class="far fa-clock me-1"></i><?= date('d M', strtotime($n['created_at'])) ?></small>
                    </div>
                    <p class="mb-0 text-secondary" style="font-size: 0.95rem; line-height: 1.5;"><?= nl2br(htmlspecialchars($n['message'])) ?></p>
                </div>
            </div>
            
            <!-- Subtle Background Decoration -->
            <i class="fas fa-quote-right position-absolute" style="right: 15px; bottom: 10px; font-size: 4rem; color: rgba(0,0,0,0.03); transform: rotate(-10deg);"></i>
        </div>
    <?php endforeach; ?>
</div>
<?php elseif (in_array(strtolower($_SESSION['user_role'] ?? ''), ['admin', 'master_admin'])): ?>
<!-- Empty Notice Board for Admins (with Action) -->
<div class="card border-0 shadow-sm rounded-4 mb-4" style="background: #f8fafc; border: 1px dashed #cbd5e1 !important;">
    <div class="card-body py-4 text-center">
        <div class="icon-circle bg-white text-muted mx-auto mb-3" style="width:60px; height:60px; border: 1px solid #e2e8f0; border-radius: 50%; display: flex; align-items:center; justify-content:center;">
             <i class="fas fa-bullhorn fa-lg"></i>
        </div>
        <h6 class="fw-bold text-dark mb-2">No Active Notices</h6>
        <p class="text-muted small mb-3">Broadcast an announcement to all users or specific roles.</p>
        <a href="?page=manage_notices" class="btn btn-sm btn-outline-primary rounded-pill px-4">Create First Notice</a>
    </div>
</div>
<?php endif; ?>

<style>
.alert-notice {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.alert-notice:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}
.icon-circle {
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
</style>
