<?php
/**
 * admin/recruitment/manage_recruitment_posts.php
 * FINAL VERSION: 
 * - FIXED: All action forms (Approve, Reject, Return, Delete) now submit correctly to the central index.php action handler.
 * - This resolves the issue of actions not responding.
 * - The necessary model file is included to prevent fatal errors.
 * - The view modal now correctly displays the post preview and Blogger HTML code.
 */

require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';

// Display any status message from the session (set by index.php after an action)
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

// When the admin visits this page, mark all new pending posts as "viewed".
markPendingRecruitmentPostsAsViewedByAdmin();

// --- Data Fetching Logic ---
$filterStatus = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');
$recordsPerPage = 10;
$currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($currentPage - 1) * $recordsPerPage;
$totalPosts = getTotalRecruitmentPostsCount($filterStatus, $searchQuery);
$totalPages = ceil($totalPosts / $recordsPerPage);
$recruitmentPosts = getAllRecruitmentPosts($filterStatus, $searchQuery, $recordsPerPage, $offset);
?>
<h2 class="mb-4">Manage Recruitment Posts</h2>

<?php if (!empty($message)): ?>
    <?php include VIEWS_PATH . 'components/message_box.php'; ?>
<?php endif; ?>

<div class="card shadow-sm rounded-3 mb-4">
    <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Posts</h5></div>
    <div class="card-body">
        <form action="" method="GET" class="row g-3 align-items-center">
            <input type="hidden" name="page" value="manage_recruitment_posts">
            <div class="col-md-4">
                <select class="form-select rounded-pill" name="status" onchange="this.form.submit()">
                    <option value="all" <?= ($filterStatus === 'all') ? 'selected' : '' ?>>All Statuses</option>
                    <option value="pending" <?= ($filterStatus === 'pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= ($filterStatus === 'approved') ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= ($filterStatus === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                    <option value="returned_for_edit" <?= ($filterStatus === 'returned_for_edit') ? 'selected' : '' ?>>Returned for Edit</option>
                </select>
            </div>
            <div class="col-md-5"><input type="text" class="form-control rounded-pill" name="search" placeholder="Search by Job Title or Submitter" value="<?= htmlspecialchars($searchQuery) ?>"></div>
            <div class="col-md-auto"><button type="submit" class="btn btn-primary rounded-pill px-4">Apply</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm rounded-3">
    <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="fas fa-list me-2"></i>All Recruitment Posts</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr><th>ID</th><th>Job Title</th><th>Vacancies</th><th>Submitted By</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recruitmentPosts as $post): ?>
                        <tr>
                            <td><?= htmlspecialchars($post['id']) ?></td>
                            <td><?= htmlspecialchars($post['job_title']) ?></td>
                            <td><?= htmlspecialchars($post['total_vacancies']) ?></td>
                            <td><?= htmlspecialchars($post['submitted_by_name'] ?? 'N/A') ?></td>
                            <td><span class="badge bg-<?= getApprovalStatusBadgeColor($post['approval_status']) ?>"><?= ucwords(str_replace('_', ' ', $post['approval_status'])) ?></span></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewPostModal" data-post='<?= htmlspecialchars(json_encode($post), ENT_QUOTES, 'UTF-8') ?>' title="View Details"><i class="fas fa-eye"></i></button>
                                    
                                    <?php if ($post['approval_status'] === 'pending'): ?>
                                        <form action="index.php?page=manage_recruitment_posts" method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="approve_post">
                                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Approve Post"><i class="fas fa-check"></i></button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal" data-post-id="<?= $post['id'] ?>" title="Reject Post"><i class="fas fa-times"></i></button>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($post['approval_status'], ['pending', 'approved'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#returnForEditModal" data-post-id="<?= $post['id'] ?>" title="Return for Edit"><i class="fas fa-undo"></i></button>
                                    <?php endif; ?>
                                    
                                    <form action="index.php?page=manage_recruitment_posts" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this post?');">
                                        <input type="hidden" name="action" value="delete_post">
                                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Post"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="viewPostModal" tabindex="-1" aria-labelledby="viewPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewPostModalLabel">Post Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview" type="button" role="tab">Preview</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="data-tab" data-bs-toggle="tab" data-bs-target="#data" type="button" role="tab">Raw Data</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="html-tab" data-bs-toggle="tab" data-bs-target="#html" type="button" role="tab">Blogger HTML</button></li>
                </ul>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="preview" role="tabpanel"><div id="html-preview-container" class="p-3"></div></div>
                    <div class="tab-pane fade" id="data" role="tabpanel"><table class="table table-sm table-bordered mt-3"><tbody id="post-details-table"></tbody></table></div>
                    <div class="tab-pane fade" id="html" role="tabpanel"><textarea id="blogger-html-code" class="form-control mt-3" rows="15" readonly></textarea></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="returnForEditModal" tabindex="-1" aria-labelledby="returnForEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="index.php?page=manage_recruitment_posts" method="POST">
                <div class="modal-header bg-warning text-dark"><h5 class="modal-title" id="returnForEditModalLabel">Return Post for Edit</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p>Please provide comments for the user explaining what needs to be corrected.</p>
                    <input type="hidden" name="action" value="return_post_for_edit">
                    <input type="hidden" name="post_id" id="return-post-id">
                    <textarea class="form-control" name="admin_comments" rows="4" required></textarea>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-warning">Return Post</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="index.php?page=manage_recruitment_posts" method="POST">
                <div class="modal-header bg-danger text-white"><h5 class="modal-title" id="rejectModalLabel">Reject Post</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p>Are you sure you want to reject this post? You can provide a reason below.</p>
                    <input type="hidden" name="action" value="reject_post">
                    <input type="hidden" name="post_id" id="reject-post-id">
                    <textarea class="form-control" name="admin_comments" rows="4"></textarea>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Confirm Rejection</button></div>
            </form>
        </div>
    </div>
</div>

<style>
    /* CSS for post preview (remains unchanged) */
    .bronline-image-banner{max-width:100%;height:auto;display:block;margin:0 auto 15px auto;border:2px solid #ddd;border-radius:8px;}
    .bronline-recruitment-job-style{text-align:center;margin-bottom:20px;padding:15px;background-color:#f8f9fa;border-radius:8px;}
    .bronline-recruitment-job-style h1{font-size:1.8em;color:#007bff;margin:0;}
    .bronline-recruitment-job-style p{font-size:1.1em;color:#555;margin-top:5px;}
    .bronline-card-box{border:1px solid #e0e0e0;border-radius:8px;padding:15px;margin-bottom:15px;box-shadow:0 2px 5px rgba(0,0,0,.05);}
    .bronline-card-box h3{color:#0056b3;margin-top:0;font-size:1.2em;border-bottom:2px solid #cee5ff;padding-bottom:8px;display:flex;align-items:center;}
    .bronline-card-box h3 i{margin-right:10px;}
    .bronline-card-box ul{list-style:none;padding:0;margin:10px 0 0 0;}
    .bronline-card-box ul li{margin-bottom:5px;position:relative;padding-left:20px;}
    .bronline-card-box ul li i{position:absolute;left:0;top:4px;color:#007bff;}
    .bronline-important-links-card .bronline-link-button{display:inline-block;margin:5px;padding:8px 15px;border-radius:20px;font-weight:600;text-decoration:none;background:#007bff;color:#fff;transition:background .2s;}
    .bronline-link-button:hover{background:#0056b3;color:#fff;}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Modal Event Listeners ---
    const viewModal = document.getElementById('viewPostModal');
    if (viewModal) {
        viewModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const postData = JSON.parse(button.getAttribute('data-post'));
            populateViewModal(postData);
        });
    }

    const returnModal = document.getElementById('returnForEditModal');
    if (returnModal) {
        returnModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const postId = button.getAttribute('data-post-id');
            document.getElementById('return-post-id').value = postId;
        });
    }

    const rejectModal = document.getElementById('rejectModal');
    if (rejectModal) {
        rejectModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const postId = button.getAttribute('data-post-id');
            document.getElementById('reject-post-id').value = postId;
        });
    }

    // --- Helper Functions to Populate Modal ---
    function populateViewModal(post) {
        const tableBody = document.getElementById('post-details-table');
        tableBody.innerHTML = '';
        for (const [key, value] of Object.entries(post)) {
             if (value && typeof value !== 'object' && key !== 'custom_fields_json') {
                const row = `<tr><th style="width: 30%; text-transform: capitalize;">${key.replace(/_/g, ' ')}</th><td>${escapeHtml(value.toString())}</td></tr>`;
                tableBody.innerHTML += row;
            }
        }
        
        const htmlContent = generateHtmlPreview(post);
        document.getElementById('html-preview-container').innerHTML = htmlContent;
        document.getElementById('blogger-html-code').value = htmlContent;
    }

    function generateHtmlPreview(data) {
        const sanitize = (str) => escapeHtml(str || '');
        const formatDate = (dateString) => {
            if (!dateString || dateString === '0000-00-00') return '';
            return new Intl.DateTimeFormat('en-GB').format(new Date(dateString));
        };

        const convertToUlLi = (text) => {
            if (!text) return '';
            return '<ul>' + text.split(/\\r\\n|\\n|\\r/).filter(line => line.trim() !== '').map(line => `<li><i class="fas fa-check-circle"></i> ${sanitize(line.trim())}</li>`).join('') + '</ul>';
        };

        let generatedHtml = '';
        if (data.image_banner_url) { generatedHtml += `<img src="${sanitize(data.image_banner_url)}" alt="${sanitize(data.job_title)}" class="bronline-image-banner">`; }
        generatedHtml += `<div class="bronline-recruitment-job-style"><h1>${sanitize(data.job_title)}</h1><p>Total Vacancies: <strong>${sanitize(data.total_vacancies)}</strong></p></div>`;

        if (data.start_date || data.last_date) { generatedHtml += `<div class="bronline-card-box"><h3><i class="fas fa-calendar-alt"></i> Important Dates</h3><ul>${data.start_date ? `<li><strong>Start Date:</strong> ${formatDate(data.start_date)}</li>` : ''}${data.last_date ? `<li><strong>Last Date:</strong> ${formatDate(data.last_date)}</li>` : ''}</ul></div>`; }
        if (data.eligibility_criteria) { generatedHtml += `<div class="bronline-card-box"><h3><i class="fas fa-user-check"></i> Eligibility Criteria</h3>${convertToUlLi(data.eligibility_criteria)}</div>`; }
        
        try {
            const customFields = JSON.parse(data.custom_fields_json);
            if (Array.isArray(customFields)) {
                customFields.forEach(field => {
                    if (field.heading && field.content) {
                        generatedHtml += `<div class="bronline-card-box"><h3><i class="fas fa-info-circle"></i> ${sanitize(field.heading)}</h3>${convertToUlLi(field.content)}</div>`;
                    }
                });
            }
        } catch(e) { /* Ignore invalid JSON */ }

        if (data.notification_url || data.apply_url || data.official_website_url) {
            generatedHtml += `<div class="bronline-card-box bronline-important-links-card"><h3><i class="fas fa-link"></i> Important Links</h3>`;
            if(data.notification_url) generatedHtml += `<a href="${sanitize(data.notification_url)}" target="_blank" class="bronline-link-button">Notification</a>`;
            if(data.apply_url) generatedHtml += `<a href="${sanitize(data.apply_url)}" target="_blank" class="bronline-link-button">Apply Online</a>`;
            if(data.official_website_url) generatedHtml += `<a href="${sanitize(data.official_website_url)}" target="_blank" class="bronline-link-button">Official Website</a>`;
            generatedHtml += `</div>`;
        }
        
        return generatedHtml;
    }

    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
});
</script>