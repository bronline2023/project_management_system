<?php
/**
 * admin/recruitment/manage_recruitment_posts.php
 * FINAL FIX: Fixed Missing Icons in View Modal (Added 'fas' class in JS generator)
 */

require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';

if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

markPendingRecruitmentPostsAsViewedByAdmin();

// --- Data Fetching Logic ---
$filterStatus = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');
$recordsPerPage = 15;
$currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($currentPage - 1) * $recordsPerPage;
$totalPosts = getTotalRecruitmentPostsCount($filterStatus, $searchQuery);
$totalPages = ceil($totalPosts / $recordsPerPage);
$recruitmentPosts = getAllRecruitmentPosts($filterStatus, $searchQuery, $recordsPerPage, $offset);
?>
<div class="container-fluid px-0">
    <h3 class="mb-4 fw-bold text-dark"><i class="fas fa-tasks text-primary me-2"></i>Manage Recruitment Posts</h3>

    <?php if (!empty($message)): echo $message; endif; ?>

    <div class="card shadow border-0 rounded-4 mb-4">
        <div class="card-header bg-primary text-white rounded-top-4 py-3"><h5 class="mb-0 fw-bold"><i class="fas fa-filter me-2"></i>Filter Posts</h5></div>
        <div class="card-body bg-light">
            <form action="index.php" method="GET" class="row g-3 align-items-center">
                <input type="hidden" name="page" value="manage_recruitment_posts">
                <div class="col-md-4">
                    <select class="form-select shadow-sm" name="status" onchange="this.form.submit()">
                        <option value="all" <?= ($filterStatus === 'all') ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending" <?= ($filterStatus === 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= ($filterStatus === 'approved') ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= ($filterStatus === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                        <option value="returned_for_edit" <?= ($filterStatus === 'returned_for_edit') ? 'selected' : '' ?>>Returned for Edit</option>
                    </select>
                </div>
                <div class="col-md-5"><input type="text" class="form-control shadow-sm" name="search" placeholder="Search by Job Title or Submitter" value="<?= htmlspecialchars($searchQuery) ?>"></div>
                <div class="col-md-auto"><button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm"><i class="fas fa-search"></i> Search</button></div>
            </form>
        </div>
    </div>

    <div class="card shadow border-0 rounded-4">
        <div class="card-header bg-success text-white rounded-top-4 py-3"><h5 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i>All Recruitment Posts</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3">ID</th>
                            <th>Job Title</th>
                            <th>Vacancies</th>
                            <th>Submitted By</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recruitmentPosts)): foreach ($recruitmentPosts as $post): ?>
                            <tr>
                                <td class="px-3 fw-bold text-secondary">#<?= htmlspecialchars($post['id']) ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($post['job_title']) ?></td>
                                <td><?= htmlspecialchars($post['total_vacancies']) ?></td>
                                <td><i class="fas fa-user text-muted"></i> <?= htmlspecialchars($post['submitted_by_name'] ?? 'Freelancer') ?></td>
                                <td><span class="badge bg-<?= getApprovalStatusBadgeColor($post['approval_status']) ?> px-2 py-1 fs-6 shadow-sm"><?= ucwords(str_replace('_', ' ', $post['approval_status'])) ?></span></td>
                                <td class="text-center">
                                    <div class="btn-group shadow-sm">
                                        <?php 
                                            // 100% Safe JSON Encoding
                                            $safeJsonData = htmlspecialchars(json_encode($post, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); 
                                        ?>
                                        <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#viewPostModal" data-post='<?= $safeJsonData ?>' title="View & Get Code"><i class="fas fa-eye"></i> View</button>
                                        
                                        <?php if ($post['approval_status'] === 'pending'): ?>
                                            <form action="index.php" method="POST" class="d-inline">
                                                <input type="hidden" name="page" value="manage_recruitment_posts">
                                                <input type="hidden" name="action" value="approve_post">
                                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success" title="Approve Post" onclick="return confirm('Are you sure you want to APPROVE this post?')"><i class="fas fa-check"></i></button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal" data-post-id="<?= $post['id'] ?>" title="Reject Post"><i class="fas fa-times"></i></button>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($post['approval_status'], ['pending', 'approved'])): ?>
                                            <button type="button" class="btn btn-sm btn-warning text-dark" data-bs-toggle="modal" data-bs-target="#returnForEditModal" data-post-id="<?= $post['id'] ?>" title="Return for Edit"><i class="fas fa-undo"></i></button>
                                        <?php endif; ?>
                                        
                                        <form action="index.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently DELETE this post?');">
                                            <input type="hidden" name="page" value="manage_recruitment_posts">
                                            <input type="hidden" name="action" value="delete_recruitment_post">
                                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Post"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted fw-bold">No posts found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white border-0 py-3">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($currentPage == $i) ? 'active' : '' ?>">
                                <a class="page-link shadow-sm" href="?page=manage_recruitment_posts&status=<?= $filterStatus ?>&search=<?= urlencode($searchQuery) ?>&p=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="viewPostModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-dark text-white rounded-top-4 py-3">
                <h5 class="modal-title fw-bold" id="viewPostModalLabel"><i class="fas fa-laptop-code text-warning me-2"></i>Post Preview & Blogger Code</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <ul class="nav nav-pills nav-fill bg-white p-1 rounded-3 shadow-sm mb-3" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active fw-bold" id="preview-tab" data-bs-toggle="pill" data-bs-target="#preview" type="button" role="tab">Live Preview</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link fw-bold" id="html-tab" data-bs-toggle="pill" data-bs-target="#html" type="button" role="tab">Blogger HTML</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link fw-bold" id="data-tab" data-bs-toggle="pill" data-bs-target="#data" type="button" role="tab">Raw Data</button></li>
                </ul>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="preview" role="tabpanel">
                        <div id="html-preview-container" class="p-3 bg-white border mt-2 rounded shadow-sm"></div>
                    </div>
                    <div class="tab-pane fade" id="html" role="tabpanel">
                        <textarea id="blogger-html-code" class="form-control mt-2 border-dark text-dark font-monospace shadow-inner" rows="18" readonly style="font-size: 0.85em;"></textarea>
                        <button type="button" class="btn btn-success w-100 fw-bold mt-2" onclick="navigator.clipboard.writeText(document.getElementById('blogger-html-code').value).then(()=>alert('Blogger Code Copied!'))"><i class="fas fa-copy"></i> Copy HTML</button>
                    </div>
                    <div class="tab-pane fade" id="data" role="tabpanel">
                        <div class="table-responsive bg-white rounded shadow-sm mt-2 p-2">
                            <table class="table table-sm table-bordered mb-0"><tbody id="post-details-table"></tbody></table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0"><button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="returnForEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <form action="index.php" method="POST">
                <input type="hidden" name="page" value="manage_recruitment_posts">
                <input type="hidden" name="action" value="return_post_for_edit">
                <input type="hidden" name="post_id" id="return-post-id">
                <div class="modal-header bg-warning text-dark rounded-top-4"><h5 class="modal-title fw-bold"><i class="fas fa-undo me-2"></i>Return Post for Edit</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body bg-light">
                    <p class="fw-bold text-muted">Provide comments explaining what the user needs to correct:</p>
                    <textarea class="form-control shadow-sm" name="admin_comments" rows="4" required placeholder="E.g., The apply link is not working..."></textarea>
                </div>
                <div class="modal-footer border-0 bg-light"><button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-warning fw-bold">Return Post</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <form action="index.php" method="POST">
                <input type="hidden" name="page" value="manage_recruitment_posts">
                <input type="hidden" name="action" value="reject_post">
                <input type="hidden" name="post_id" id="reject-post-id">
                <div class="modal-header bg-danger text-white rounded-top-4"><h5 class="modal-title fw-bold"><i class="fas fa-ban me-2"></i>Reject Post</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body bg-light">
                    <p class="fw-bold text-danger">Are you sure you want to reject this post?</p>
                    <textarea class="form-control shadow-sm" name="admin_comments" rows="3" placeholder="Reason for rejection (Optional)"></textarea>
                </div>
                <div class="modal-footer border-0 bg-light"><button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger fw-bold">Confirm Rejection</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Setup Modals ID binding ---
    const setupModalId = (modalId, inputId) => {
        const modal = document.getElementById(modalId);
        if (modal) { 
            modal.addEventListener('show.bs.modal', function(e) {
                document.getElementById(inputId).value = e.relatedTarget.getAttribute('data-post-id');
            }); 
        }
    };
    setupModalId('returnForEditModal', 'return-post-id');
    setupModalId('rejectModal', 'reject-post-id');

    // --- View Modal Logic ---
    const viewModal = document.getElementById('viewPostModal');
    if (viewModal) {
        viewModal.addEventListener('show.bs.modal', function(event) {
            try {
                const postDataStr = event.relatedTarget.getAttribute('data-post');
                if(!postDataStr) throw new Error("Empty Data");
                const postData = JSON.parse(postDataStr);
                populateViewModal(postData);
            } catch (e) {
                console.error("View Modal Parse Error:", e);
                document.getElementById('html-preview-container').innerHTML = '<div class="alert alert-danger">Error loading data.</div>';
            }
        });
    }

    function populateViewModal(data) {
        // 1. Raw Data Table
        const tb = document.getElementById('post-details-table'); tb.innerHTML = '';
        for (const [k, v] of Object.entries(data)) {
             if (v && typeof v !== 'object' && !k.includes('json')) {
                tb.innerHTML += `<tr><th class="bg-light" style="width: 30%; text-transform: capitalize;">${k.replace(/_/g, ' ')}</th><td>${escapeHtml(v.toString())}</td></tr>`;
            }
        }
        
        // 2. Generate HTML Preview 
        const htmlContent = generateHtmlPreview(data);
        
        const bloggerCSS = `<style>
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800;900&display=swap');
.bronline-wrapper { font-family: 'Poppins', sans-serif !important; box-sizing: border-box !important; line-height: 1.8 !important; color: #1a1a1a !important; background: transparent !important; width: 100% !important; font-size: 16px !important; text-align: left !important; }
.bronline-wrapper * { box-sizing: border-box !important; }
.bronline-wrapper strong { font-weight: 800 !important; color: #000 !important; }
.bronline-wrapper span { font-weight: 600 !important; color: #222 !important; }
.bronline-wrapper .bronline-image-banner { max-width: 100% !important; width: 450px !important; margin: 0 auto 25px auto !important; display: block !important; border: 4px solid #dc3545 !important; border-radius: 10px !important; box-shadow: 0 8px 20px rgba(0,0,0,.15) !important; }
.bronline-wrapper .bronline-recruitment-job-style { text-align: center !important; margin: 0 0 30px 0 !important; padding: 25px !important; background: linear-gradient(135deg, #e0efff, #cce0ff) !important; border-radius: 12px !important; box-shadow: 0 6px 18px rgba(0,0,0,.12) !important; }
.bronline-wrapper .bronline-recruitment-job-style h1 { color: #0056b3 !important; font-size: 2.4em !important; font-weight: 900 !important; margin: 0 0 10px 0 !important; letter-spacing: 0.5px !important; }
.bronline-wrapper .bronline-recruitment-job-style p { font-size: 1.3em !important; font-weight: 700 !important; color: #333 !important; margin: 0 !important; }
.bronline-wrapper .bronline-card-box { background: #fff !important; border: 2px solid #e0e0e0 !important; border-radius: 12px !important; padding: 25px !important; margin: 0 0 25px 0 !important; box-shadow: 0 5px 15px rgba(0,0,0,.08) !important; }
.bronline-wrapper .bronline-card-box h3 { color: #004085 !important; margin: 0 0 18px 0 !important; font-size: 1.6em !important; font-weight: 800 !important; border-bottom: 3px solid #cee5ff !important; padding-bottom: 12px !important; display: flex !important; align-items: center !important; }
.bronline-wrapper .bronline-card-box h3 i { margin-right: 12px !important; color: #007bff !important; }
.bronline-wrapper .bronline-card-box ul { list-style: none !important; padding: 0 !important; margin: 0 !important; }
.bronline-wrapper .bronline-card-box ul li { margin: 0 0 12px 0 !important; font-size: 1.15em !important; line-height: 1.7 !important; display: flex !important; align-items: flex-start !important; }
.bronline-wrapper .bronline-card-box ul li i { margin-right: 12px !important; margin-top: 5px !important; color: #007bff !important; font-size: 1.1em !important; }
.bronline-wrapper .bronline-important-dates-card ul li i { color: #d9534f !important; }
.bronline-wrapper .bronline-important-dates-card h3 { color: #c9302c !important; border-bottom-color: #f5c6cb !important; }
.bronline-wrapper .bronline-important-dates-card h3 i { color: #c9302c !important; }
.bronline-wrapper .bronline-start-date { color: #0056b3 !important; font-weight: 800 !important; }
.bronline-wrapper .bronline-last-date { color: #d9534f !important; font-weight: 900 !important; font-size: 1.1em !important; }
.bronline-wrapper .bronline-important-links-card { background: linear-gradient(135deg, #fff3cd, #fff8e5) !important; border-color: #ffeeba !important; }
.bronline-wrapper .bronline-important-links-card h3 { color: #856404 !important; border-bottom-color: #ffeeba !important; }
.bronline-wrapper .bronline-important-links-card h3 i { color: #856404 !important; }
.bronline-wrapper a.bronline-link-button { display: inline-block !important; margin: 8px 12px 8px 0 !important; padding: 12px 25px !important; border-radius: 30px !important; font-weight: 800 !important; font-size: 15px !important; text-decoration: none !important; background: linear-gradient(45deg, #ff416c, #ff4b2b) !important; color: #fff !important; box-shadow: 0 4px 15px rgba(255,65,108,.3) !important; transition: transform .2s, box-shadow .2s !important; }
.bronline-wrapper a.bronline-link-button:hover { transform: scale(1.05) !important; box-shadow: 0 6px 20px rgba(255,65,108,.4) !important; }
.bronline-wrapper a.bronline-link-button i { margin-right: 8px !important; color: #fff !important; }
.bronline-wrapper a.bronline-disabled-link { background: #6c757d !important; box-shadow: none !important; cursor: not-allowed !important; opacity: 0.8 !important; pointer-events: none !important; }
.bronline-wrapper .bronline-last-date-remaining { display: inline-block !important; background: linear-gradient(45deg, #dc3545, #8b0000) !important; color: #fff !important; padding: 4px 12px !important; border-radius: 6px !important; font-weight: 900 !important; margin-left: 12px !important; font-size: 0.9em !important; animation: bronline-blink 1s infinite alternate !important; box-shadow: 0 0 10px rgba(220,53,69,.6) !important; }
@keyframes bronline-blink { 0% { opacity: 1; transform: scale(1); } 100% { opacity: 0.85; transform: scale(1.05); box-shadow: 0 0 15px rgba(220,53,69,.9) !important; } }
@media (max-width: 600px) { .bronline-wrapper a.bronline-link-button { display: block !important; width: 100% !important; text-align: center !important; margin-bottom: 12px !important; } }
</style>`;
        
        const finalCode = bloggerCSS + `\n<div class="bronline-wrapper">\n` + htmlContent + `\n</div>`;
        document.getElementById('html-preview-container').innerHTML = finalCode;
        document.getElementById('blogger-html-code').value = finalCode;
    }

    function generateHtmlPreview(data) {
        const sanitize = (str) => escapeHtml(str || '');
        const formatDate = (dateString) => {
            if (!dateString || dateString === '0000-00-00') return '';
            const d = new Date(dateString); return isNaN(d.getTime()) ? dateString : `${String(d.getDate()).padStart(2,'0')}-${String(d.getMonth()+1).padStart(2,'0')}-${d.getFullYear()}`;
        };
        const getRemainingDays = (dStr) => {
            if (!dStr || dStr === '0000-00-00') return null;
            const diff = new Date(dStr).setHours(23,59,59,999) - new Date().getTime();
            const d = Math.ceil(diff / 86400000); return d >= 0 ? d : null;
        };
        const buildList = (text, iconClass) => {
            if (!text) return ''; let html = '<ul>';
            text.split(/\r\n|\n|\r/).filter(l => l.trim() !== '').forEach(l => {
                const p = l.split('=');
                if (p.length > 1) html += `<li><i class="fas ${iconClass}"></i> <strong>${sanitize(p[0].trim())}:</strong> <span>${sanitize(p.slice(1).join('=').trim())}</span></li>`;
                else html += `<li><i class="fas ${iconClass}"></i> <span>${sanitize(l.trim())}</span></li>`;
            }); return html + '</ul>';
        };
        
        // --- Main error was here (added fas class) ---
        const genLink = (nm, url, ic) => {
            if (!url) return ''; const isDis = (url.trim() === '#'); const hr = isDis ? 'javascript:void(0);' : sanitize(url);
            return `<a href="${hr}" target="_blank" class="bronline-link-button ${isDis ? 'bronline-disabled-link' : ''}"><i class="fas ${ic}"></i> ${sanitize(nm)}</a>`;
        };

        let h = '';
        if (data.image_banner_url) h += `<img src="${sanitize(data.image_banner_url)}" class="bronline-image-banner" alt="Banner">`;
        if (data.job_title || data.total_vacancies) h += `<div class="bronline-recruitment-job-style"><h1><i class="fas fa-bullhorn"></i> ${sanitize(data.job_title)}</h1><p><i class="fas fa-user-friends"></i> Total Vacancies: <strong>${sanitize(data.total_vacancies)}</strong></p></div>`;

        let dHtml = '';
        if (data.start_date && data.start_date !== '0000-00-00') dHtml += `<li><i class="fas fa-play-circle"></i> <strong>Start Date:</strong> <span class="bronline-start-date">${formatDate(data.start_date)}</span></li>`;
        if (data.last_date && data.last_date !== '0000-00-00') {
            const rd = getRemainingDays(data.last_date);
            dHtml += `<li><i class="fas fa-stop-circle"></i> <strong>Last Date:</strong> <span class="bronline-last-date">${formatDate(data.last_date)}</span>${rd !== null ? `<span class="bronline-last-date-remaining">${rd} Days Left</span>` : ''}</li>`;
        }
        if (data.exam_date && data.exam_date !== '0000-00-00') dHtml += `<li><i class="fas fa-marker"></i> <strong>Exam Date:</strong> <span>${formatDate(data.exam_date)}</span></li>`;
        if (data.fee_payment_last_date && data.fee_payment_last_date !== '0000-00-00') dHtml += `<li><i class="fas fa-credit-card"></i> <strong>Fee Date:</strong> <span>${formatDate(data.fee_payment_last_date)}</span></li>`;
        
        try {
            JSON.parse(data.custom_dates_json || '[]').forEach(c => { if(c.title && c.date) dHtml += `<li><i class="fas fa-calendar-day"></i> <strong>${sanitize(c.title)}:</strong> <span class="bronline-start-date">${formatDate(c.date)}</span></li>`; });
        } catch(e){}
        if (dHtml) h += `<div class="bronline-card-box bronline-important-dates-card"><h3><i class="fas fa-calendar-alt"></i> Important Dates</h3><ul>${dHtml}</ul></div>`;

        const blks = [
            {v: data.eligibility_criteria, i: 'fa-user-check', t: 'Eligibility Criteria'},
            {v: data.selection_process, i: 'fa-clipboard-list', t: 'Selection Process'},
            {v: data.age_limit, i: 'fa-user-clock', t: 'Age Limit'},
            {v: data.application_fees, i: 'fa-money-bill-wave', t: 'Application Fees'},
            {v: data.category_wise_vacancies, i: 'fa-users', t: 'Category-wise Vacancies'},
            {v: data.exam_prediction, i: 'fa-lightbulb', t: 'Exam Prediction'}
        ];
        blks.forEach(b => { if(b.v) h += `<div class="bronline-card-box"><h3><i class="fas ${b.i}"></i> ${b.t}</h3>${buildList(b.v, b.i)}</div>`; });

        try {
            JSON.parse(data.other_details_json || '[]').forEach(d => { if(d.title && d.content) h += `<div class="bronline-card-box"><h3><i class="fas fa-list"></i> ${sanitize(d.title)}</h3>${buildList(d.content, 'fa-info-circle')}</div>`; });
            JSON.parse(data.custom_fields_json || '[]').forEach(f => { if(f.heading && f.content) h += `<div class="bronline-card-box"><h3><i class="fas fa-asterisk"></i> ${sanitize(f.heading)}</h3>${buildList(f.content, 'fa-sticky-note')}</div>`; });
        } catch(e){}

        let lHtml = '';
        // Here the icon names are given, which will become <i class="fas fa-file-alt"></i> in genLink
        lHtml += genLink('Notification', data.notification_url, 'fa-file-alt');
        lHtml += genLink('Apply Online', data.apply_url, 'fa-external-link-alt');
        lHtml += genLink('Admit Card', data.admit_card_url, 'fa-ticket-alt');
        lHtml += genLink('Official Website', data.official_website_url, 'fa-globe');
        try { JSON.parse(data.custom_links_json || '[]').forEach(l => { if(l.name && l.url) lHtml += genLink(l.name, l.url, 'fa-link'); }); } catch(e){}
        if(lHtml) h += `<div class="bronline-card-box bronline-important-links-card"><h3><i class="fas fa-link"></i> Important Links</h3><div>${lHtml}</div></div>`;
        
        return h;
    }

    function escapeHtml(unsafe) {
        if (unsafe == null) return '';
        return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
});
</script>