<?php
/**
 * user/recruitment/add_recruitment_post.php
 * FIXED: Added File Upload options for Notification & Custom Links.
 */

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = ''; $post = null; $postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEditable = true;
require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php';

$formData = [
    'id' => $postId, 'job_title' => '', 'total_vacancies' => '', 'image_banner_url' => '',
    'eligibility_criteria' => '', 'selection_process' => '', 'age_limit' => '', 
    'start_date' => '', 'last_date' => '', 'exam_date' => '', 'fee_payment_last_date' => '',
    'application_fees' => '', 'category_wise_vacancies' => '', 'exam_prediction' => '',
    'notification_url' => '', 'apply_url' => '', 'admit_card_url' => '', 'official_website_url' => '', 
    'custom_fields_json' => '[]', 'custom_links_json' => '[]', 'custom_dates_json' => '[]', 'other_details_json' => '[]'
];

if (isset($_GET['image_url']) && !empty($_GET['image_url'])) {
    $formData['image_banner_url'] = $_GET['image_url'];
    $message = '<div class="alert alert-success fw-bold shadow-sm"><i class="fas fa-check-circle me-2"></i> Poster image successfully generated and pre-filled!</div>';
}

if ($postId > 0) {
    $post = getRecruitmentPostById($postId);
    if ($post) {
        if (in_array($post['approval_status'], ['pending', 'approved', 'rejected'])) { $isEditable = false; }
        foreach ($formData as $key => $value) { if (isset($post[$key])) $formData[$key] = $post[$key]; }
    }
}

if (isset($_SESSION['status_message'])) { $message = $_SESSION['status_message']; unset($_SESSION['status_message']); }
?>

<div class="container-fluid px-2">
    <h3 class="mb-4 fw-bold text-dark"><i class="fas fa-edit text-primary"></i> <?= $post ? 'Edit Recruitment Post' : 'Add New Recruitment Post' ?></h3>
    <?php if (!empty($message)): echo $message; endif; ?>

    <div class="row g-3">
        <div class="col-lg-5 col-12">
            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-primary text-white rounded-top-4 py-3"><h5 class="mb-0 fw-bold"><i class="fas fa-list-alt me-2"></i>Post Details Form</h5></div>
                <div class="card-body bg-light p-3 p-md-4" style="max-height: 80vh; overflow-y: auto;">
                    <form action="index.php" method="POST" id="recruitmentPostForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="submit_recruitment_post">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($formData['id']) ?>">
                        <fieldset <?= !$isEditable ? 'disabled' : '' ?>>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-12 col-md-8"><label class="form-label fw-bold">Job Title <span class="text-danger">*</span></label><input type="text" class="form-control shadow-sm" id="job_title" name="job_title" value="<?= htmlspecialchars($formData['job_title']) ?>" required></div>
                                <div class="col-12 col-md-4"><label class="form-label fw-bold">Vacancies <span class="text-danger">*</span></label><input type="text" class="form-control shadow-sm" id="total_vacancies" name="total_vacancies" value="<?= htmlspecialchars($formData['total_vacancies']) ?>" required></div>
                            </div>
                            
                            <div class="mb-3"><label class="form-label fw-bold text-success"><i class="fas fa-image"></i> Image Banner URL</label><input type="url" class="form-control shadow-sm border-success fw-bold text-primary" id="image_banner_url" name="image_banner_url" value="<?= htmlspecialchars($formData['image_banner_url']) ?>"></div>
                            
                            <div class="mb-3"><label class="form-label fw-bold text-secondary">Eligibility (Key=Value)</label><textarea class="form-control shadow-sm" id="eligibility_criteria" name="eligibility_criteria" rows="2"><?= htmlspecialchars($formData['eligibility_criteria']) ?></textarea></div>
                            <div class="mb-3"><label class="form-label fw-bold text-secondary">Selection Process (Key=Value)</label><textarea class="form-control shadow-sm" id="selection_process" name="selection_process" rows="2"><?= htmlspecialchars($formData['selection_process']) ?></textarea></div>
                            <div class="mb-3"><label class="form-label fw-bold text-danger"><i class="fas fa-user-clock"></i> Age Limit (Key=Value)</label><textarea class="form-control border-danger shadow-sm" id="age_limit" name="age_limit" rows="2"><?= htmlspecialchars($formData['age_limit']) ?></textarea></div>

                            <fieldset class="border border-success p-3 rounded-3 mb-3 bg-white shadow-sm">
                                <legend class="float-none w-auto px-2 fs-6 fw-bold text-success"><i class="fas fa-plus-circle"></i> Multiple Other Details</legend>
                                <div id="other-details-container">
                                    <?php $oDetails = json_decode($formData['other_details_json'], true); if (is_array($oDetails)) { foreach ($oDetails as $i => $d) { echo '<div class="row g-2 mb-2 align-items-end" id="other-d-'.$i.'"><div class="col-12 col-md-5"><label class="form-label small fw-bold">Title</label><input type="text" class="form-control shadow-sm" name="other_details_title[]" value="'.htmlspecialchars($d['title']).'"></div><div class="col-12 col-md-5"><label class="form-label small fw-bold">Content (Key=Value)</label><textarea class="form-control shadow-sm" name="other_details_content[]" rows="1">'.htmlspecialchars($d['content']).'</textarea></div><div class="col-12 col-md-2"><button type="button" class="btn btn-danger w-100 shadow-sm rm-other-d" data-id="'.$i.'"><i class="fas fa-trash"></i></button></div></div>'; } } ?>
                                </div>
                                <button type="button" id="add-other-d" class="btn btn-sm btn-outline-success mt-2 fw-bold"><i class="fas fa-plus"></i> Add Info Box</button>
                            </fieldset>

                            <hr>
                            <h5 class="mt-4 fw-bold text-primary"><i class="fas fa-calendar-alt"></i> Important Dates</h5>
                            <div class="row g-2 mb-3">
                                <div class="col-6"><label class="form-label fw-bold">Start Date</label><input type="date" class="form-control shadow-sm" id="start_date" name="start_date" value="<?= htmlspecialchars($formData['start_date']) ?>"></div>
                                <div class="col-6"><label class="form-label fw-bold text-danger">Last Date</label><input type="date" class="form-control border-danger shadow-sm" id="last_date" name="last_date" value="<?= htmlspecialchars($formData['last_date']) ?>"></div>
                                <div class="col-6"><label class="form-label fw-bold">Exam Date</label><input type="date" class="form-control shadow-sm" id="exam_date" name="exam_date" value="<?= htmlspecialchars($formData['exam_date']) ?>"></div>
                                <div class="col-6"><label class="form-label fw-bold">Fee Last Date</label><input type="date" class="form-control shadow-sm" id="fee_payment_last_date" name="fee_payment_last_date" value="<?= htmlspecialchars($formData['fee_payment_last_date']) ?>"></div>
                            </div>
                            
                            <fieldset class="border border-primary p-2 rounded-3 mb-3 bg-white shadow-sm">
                                <legend class="float-none w-auto px-2 fs-6 fw-bold text-primary">Add Multiple Manual Dates</legend>
                                <div id="custom-dates-container">
                                    <?php $cDates = json_decode($formData['custom_dates_json'], true); if (is_array($cDates)) { foreach ($cDates as $i => $cd) { echo '<div class="row g-2 mb-2 align-items-end" id="custom-date-'.$i.'"><div class="col-6 col-md-5"><label class="form-label small fw-bold">Date Name</label><input type="text" class="form-control shadow-sm" name="custom_date_title[]" value="'.htmlspecialchars($cd['title']).'"></div><div class="col-6 col-md-5"><label class="form-label small fw-bold">Date</label><input type="date" class="form-control shadow-sm" name="custom_date_value[]" value="'.htmlspecialchars($cd['date']).'"></div><div class="col-12 col-md-2"><button type="button" class="btn btn-danger w-100 shadow-sm rm-custom-date" data-id="'.$i.'"><i class="fas fa-trash"></i></button></div></div>'; } } ?>
                                </div>
                                <button type="button" id="add-custom-date" class="btn btn-sm btn-outline-primary mt-2 fw-bold"><i class="fas fa-plus"></i> Add New Date</button>
                            </fieldset>

                            <hr>
                            <div class="mb-3"><label class="form-label fw-bold">Application Fees (Key=Value)</label><textarea class="form-control shadow-sm" id="application_fees" name="application_fees" rows="2"><?= htmlspecialchars($formData['application_fees']) ?></textarea></div>
                            <div class="mb-3"><label class="form-label fw-bold">Category-wise Vacancies</label><textarea class="form-control shadow-sm" id="category_wise_vacancies" name="category_wise_vacancies" rows="2"><?= htmlspecialchars($formData['category_wise_vacancies']) ?></textarea></div>
                            <div class="mb-3"><label class="form-label fw-bold text-info">Exam Prediction (Key=Value)</label><textarea class="form-control border-info shadow-sm" id="exam_prediction" name="exam_prediction" rows="2"><?= htmlspecialchars($formData['exam_prediction']) ?></textarea></div>
                            
                            <fieldset class="border border-secondary p-3 rounded-3 mb-3 bg-white shadow-sm">
                                <legend class="float-none w-auto px-2 fs-6 fw-bold">Custom Extra Fields</legend>
                                <div id="custom-fields-container">
                                    <?php $cFields = json_decode($formData['custom_fields_json'], true); if (is_array($cFields)) { foreach ($cFields as $i => $f) { echo '<div class="row g-2 mb-2 align-items-end" id="custom-f-'.$i.'"><div class="col-12 col-md-5"><label class="form-label small fw-bold">Heading</label><input type="text" class="form-control shadow-sm" name="custom_heading[]" value="'.htmlspecialchars($f['heading']).'"></div><div class="col-12 col-md-5"><label class="form-label small fw-bold">Content</label><textarea class="form-control shadow-sm" name="custom_content[]" rows="1">'.htmlspecialchars($f['content']).'</textarea></div><div class="col-12 col-md-2"><button type="button" class="btn btn-danger w-100 shadow-sm rm-custom-f" data-id="'.$i.'"><i class="fas fa-trash"></i></button></div></div>'; } } ?>
                                </div>
                                <button type="button" id="add-custom-f" class="btn btn-sm btn-outline-secondary mt-2 fw-bold"><i class="fas fa-plus"></i> Add Field</button>
                            </fieldset>

                            <hr>
                            <h5 class="mt-4 fw-bold text-dark"><i class="fas fa-link text-info"></i> Important Links</h5>
                            
                            <div class="mb-3 p-2 border rounded bg-white">
                                <label class="form-label fw-bold text-primary mb-1">Notification (URL OR File Upload)</label>
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text bg-light fw-bold"><i class="fas fa-link"></i> URL</span>
                                    <input type="text" class="form-control shadow-sm" id="notification_url" name="notification_url" value="<?= htmlspecialchars($formData['notification_url']) ?>" placeholder="https://...">
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold"><i class="fas fa-upload"></i> FILE</span>
                                    <input type="file" class="form-control shadow-sm" id="notification_file" name="notification_file" accept=".pdf,.doc,.docx,.jpg,.png">
                                </div>
                            </div>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-12 col-md-4"><label class="form-label fw-bold">Apply URL</label><input type="text" class="form-control shadow-sm" id="apply_url" name="apply_url" value="<?= htmlspecialchars($formData['apply_url']) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label fw-bold">Admit Card URL</label><input type="text" class="form-control shadow-sm" id="admit_card_url" name="admit_card_url" value="<?= htmlspecialchars($formData['admit_card_url']) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label fw-bold">Website URL</label><input type="text" class="form-control shadow-sm" id="official_website_url" name="official_website_url" value="<?= htmlspecialchars($formData['official_website_url']) ?>"></div>
                            </div>
                            
                            <fieldset class="border border-info p-3 rounded-3 mb-3 bg-white shadow-sm">
                                <legend class="float-none w-auto px-2 fs-6 fw-bold text-info">Custom Links & Uploads</legend>
                                <div id="custom-links-container">
                                    <?php 
                                    $cLinks = json_decode($formData['custom_links_json'], true); 
                                    if (is_array($cLinks)) { 
                                        foreach ($cLinks as $i => $l) { 
                                            echo '<div class="row g-2 mb-2 p-2 border rounded align-items-center bg-light" id="custom-link-'.$i.'">
                                                <div class="col-12 col-md-3"><label class="form-label small fw-bold">Link Name</label><input type="text" class="form-control form-control-sm shadow-sm" name="custom_link_name[]" value="'.htmlspecialchars($l['name']).'"></div>
                                                <div class="col-12 col-md-4"><label class="form-label small fw-bold">URL</label><input type="text" class="form-control form-control-sm shadow-sm" name="custom_link_url[]" value="'.htmlspecialchars($l['url']).'"></div>
                                                <div class="col-12 col-md-4"><label class="form-label small fw-bold">OR Upload File</label><input type="file" class="form-control form-control-sm shadow-sm" name="custom_link_file[]" accept=".pdf,.doc,.docx,.jpg,.png"></div>
                                                <div class="col-12 col-md-1 mt-auto"><button type="button" class="btn btn-danger btn-sm w-100 shadow-sm rm-custom-link" data-id="'.$i.'"><i class="fas fa-trash"></i></button></div>
                                            </div>'; 
                                        } 
                                    } 
                                    ?>
                                </div>
                                <button type="button" id="add-custom-link" class="btn btn-sm btn-outline-info mt-2 fw-bold text-dark"><i class="fas fa-plus"></i> Add Link / Upload Box</button>
                            </fieldset>

                            <div class="d-grid mt-4">
                                <button type="submit" name="submit_recruitment_post" class="btn btn-primary btn-lg fw-bold shadow" <?= !$isEditable ? 'disabled' : '' ?>><i class="fas fa-save"></i> <?= $post ? 'Update Post' : 'Submit Post' ?></button>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-7 col-12">
            <div class="card shadow border-0 rounded-4 mb-4">
                <div class="card-header bg-dark text-white rounded-top-4 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-eye text-warning me-2"></i>Live Preview</h5>
                </div>
                <div class="card-body p-2 bg-light" style="max-height: 50vh; overflow-y: auto;">
                    <div id="live-preview" class="p-2 p-md-3 rounded-3 bg-white shadow-sm border"></div>
                </div>
            </div>

            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-success text-white rounded-top-4 py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-code me-2"></i>Blogger HTML Code</h5>
                </div>
                <div class="card-body bg-light">
                    <textarea id="raw-html-output" class="form-control border-dark shadow-inner text-dark font-monospace" rows="6" readonly style="font-size: 0.85em;"></textarea>
                    <button type="button" id="copy-html-button" class="btn btn-success btn-lg w-100 fw-bold mt-3 shadow"><i class="fas fa-copy"></i> Copy HTML For Blogger</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let counters = { d: 100, f: 100, l: 100, od: 100 };

    function bindEvents() {
        document.querySelectorAll('#recruitmentPostForm input, #recruitmentPostForm textarea').forEach(el => {
            el.removeEventListener('input', updatePreview); el.addEventListener('input', updatePreview);
            el.removeEventListener('change', updatePreview); el.addEventListener('change', updatePreview); // Update preview when file is selected
        });
        document.querySelectorAll('.rm-custom-date').forEach(b => { b.onclick = (e) => { document.getElementById('custom-date-'+e.currentTarget.dataset.id).remove(); updatePreview(); } });
        document.querySelectorAll('.rm-other-d').forEach(b => { b.onclick = (e) => { document.getElementById('other-d-'+e.currentTarget.dataset.id).remove(); updatePreview(); } });
        document.querySelectorAll('.rm-custom-f').forEach(b => { b.onclick = (e) => { document.getElementById('custom-f-'+e.currentTarget.dataset.id).remove(); updatePreview(); } });
        document.querySelectorAll('.rm-custom-link').forEach(b => { b.onclick = (e) => { document.getElementById('custom-link-'+e.currentTarget.dataset.id).remove(); updatePreview(); } });
    }

    document.getElementById('add-custom-date').onclick = () => {
        counters.d++;
        document.getElementById('custom-dates-container').insertAdjacentHTML('beforeend', `<div class="row g-2 mb-2 align-items-end" id="custom-date-${counters.d}"><div class="col-6 col-md-5"><input type="text" class="form-control shadow-sm" name="custom_date_title[]" placeholder="Date Name"></div><div class="col-6 col-md-5"><input type="date" class="form-control shadow-sm" name="custom_date_value[]"></div><div class="col-12 col-md-2"><button type="button" class="btn btn-danger w-100 shadow-sm rm-custom-date" data-id="${counters.d}"><i class="fas fa-trash"></i></button></div></div>`);
        bindEvents(); updatePreview();
    };

    document.getElementById('add-other-d').onclick = () => {
        counters.od++;
        document.getElementById('other-details-container').insertAdjacentHTML('beforeend', `<div class="row g-2 mb-2 align-items-end" id="other-d-${counters.od}"><div class="col-12 col-md-5"><input type="text" class="form-control shadow-sm" name="other_details_title[]" placeholder="Title"></div><div class="col-12 col-md-5"><textarea class="form-control shadow-sm" name="other_details_content[]" rows="1" placeholder="Value"></textarea></div><div class="col-12 col-md-2"><button type="button" class="btn btn-danger w-100 shadow-sm rm-other-d" data-id="${counters.od}"><i class="fas fa-trash"></i></button></div></div>`);
        bindEvents(); updatePreview();
    };

    document.getElementById('add-custom-f').onclick = () => {
        counters.f++;
        document.getElementById('custom-fields-container').insertAdjacentHTML('beforeend', `<div class="row g-2 mb-2 align-items-end" id="custom-f-${counters.f}"><div class="col-12 col-md-5"><input type="text" class="form-control shadow-sm" name="custom_heading[]" placeholder="Heading"></div><div class="col-12 col-md-5"><textarea class="form-control shadow-sm" name="custom_content[]" rows="1" placeholder="Content"></textarea></div><div class="col-12 col-md-2"><button type="button" class="btn btn-danger w-100 shadow-sm rm-custom-f" data-id="${counters.f}"><i class="fas fa-trash"></i></button></div></div>`);
        bindEvents(); updatePreview();
    };

    document.getElementById('add-custom-link').onclick = () => {
        counters.l++;
        document.getElementById('custom-links-container').insertAdjacentHTML('beforeend', `<div class="row g-2 mb-2 p-2 border rounded align-items-center bg-light" id="custom-link-${counters.l}">
            <div class="col-12 col-md-3"><label class="form-label small fw-bold">Link Name</label><input type="text" class="form-control form-control-sm shadow-sm" name="custom_link_name[]" placeholder="Name"></div>
            <div class="col-12 col-md-4"><label class="form-label small fw-bold">URL</label><input type="text" class="form-control form-control-sm shadow-sm" name="custom_link_url[]" placeholder="https://..."></div>
            <div class="col-12 col-md-4"><label class="form-label small fw-bold">OR Upload File</label><input type="file" class="form-control form-control-sm shadow-sm" name="custom_link_file[]" accept=".pdf,.doc,.docx,.jpg,.png"></div>
            <div class="col-12 col-md-1 mt-auto"><button type="button" class="btn btn-danger btn-sm w-100 shadow-sm rm-custom-link" data-id="${counters.l}"><i class="fas fa-trash"></i></button></div>
        </div>`);
        bindEvents(); updatePreview();
    };

    document.getElementById('copy-html-button').onclick = () => {
        navigator.clipboard.writeText(document.getElementById('raw-html-output').value).then(() => alert('HTML Copied Successfully!'));
    };

    const getVal = id => document.getElementById(id) ? sanitizeInput(document.getElementById(id).value) : '';
    const getRawVal = id => document.getElementById(id) ? document.getElementById(id).value : '';

    function sanitizeInput(str) { const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }
    function formatDate(str) { if (!str) return ''; const d = new Date(str); return `${String(d.getDate()).padStart(2,'0')}-${String(d.getMonth()+1).padStart(2,'0')}-${d.getFullYear()}`; }
    function getRemainingDays(str) { if (!str) return null; const d = Math.ceil((new Date(str).setHours(23,59,59,999) - new Date().getTime()) / 86400000); return d >= 0 ? d : null; }

    function buildList(text, iconClass = 'fa-check-circle') {
        if (!text) return ''; let h = '<ul>';
        text.split('\n').filter(l => l.trim() !== '').forEach(l => {
            const p = l.split('=');
            if (p.length > 1) h += `<li><i class="fas ${iconClass}"></i> <strong>${sanitizeInput(p[0].trim())}:</strong> <span>${sanitizeInput(p.slice(1).join('=').trim())}</span></li>`;
            else h += `<li><i class="fas ${iconClass}"></i> <span>${sanitizeInput(l.trim())}</span></li>`;
        });
        return h + '</ul>';
    }

    function generateLinkBtn(name, url, icon) {
        if (!url) return ''; const isDis = (url.trim() === '#'); const hr = isDis ? 'javascript:void(0);' : url;
        return `<a href="${hr}" target="_blank" rel="noopener noreferrer" class="bronline-link-button ${isDis ? 'bronline-disabled-link' : ''}"><i class="${icon}"></i> ${name}</a>`;
    }

    function updatePreview() {
        try {
            const title = getVal('job_title');
            const vac = getVal('total_vacancies');
            const img = getVal('image_banner_url');

            let html = '';
            if (img) html += `<img src="${img}" class="bronline-image-banner" alt="Banner">`;
            if (title || vac) html += `<div class="bronline-recruitment-job-style"><h1><i class="fas fa-bullhorn"></i> ${title || 'Job Post'}</h1><p><i class="fas fa-user-friends"></i> Total Vacancies: <strong>${vac || 'N/A'}</strong></p></div>`;

            // DATES BLOCK
            const sD = formatDate(getRawVal('start_date'));
            const lD = formatDate(getRawVal('last_date'));
            const eD = formatDate(getRawVal('exam_date'));
            const fD = formatDate(getRawVal('fee_payment_last_date'));
            const rD = getRemainingDays(getRawVal('last_date'));
            
            let dateHtml = '';
            if (sD) dateHtml += `<li><i class="fas fa-play-circle"></i> <strong>Start Date:</strong> <span class="bronline-start-date">${sD}</span></li>`;
            if (lD) dateHtml += `<li><i class="fas fa-stop-circle"></i> <strong>Last Date:</strong> <span class="bronline-last-date">${lD}</span>${rD !== null ? `<span class="bronline-last-date-remaining">${rD} Days Left</span>` : ''}</li>`;
            if (eD) dateHtml += `<li><i class="fas fa-marker"></i> <strong>Exam Date:</strong> <span>${eD}</span></li>`;
            if (fD) dateHtml += `<li><i class="fas fa-credit-card"></i> <strong>Fee Date:</strong> <span>${fD}</span></li>`;
            
            document.querySelectorAll('#custom-dates-container .row').forEach(r => {
                const dt = sanitizeInput(r.querySelector('input[name="custom_date_title[]"]').value);
                const dv = formatDate(r.querySelector('input[name="custom_date_value[]"]').value);
                if (dt && dv) dateHtml += `<li><i class="fas fa-calendar-day"></i> <strong>${dt}:</strong> <span class="bronline-start-date">${dv}</span></li>`;
            });
            if (dateHtml) html += `<div class="bronline-card-box bronline-important-dates-card"><h3><i class="fas fa-calendar-alt"></i> Important Dates</h3><ul>${dateHtml}</ul></div>`;

            // MAIN BLOCKS
            const blocks = [
                { el: 'eligibility_criteria', icon: 'fa-user-check', tit: 'Eligibility Criteria' },
                { el: 'selection_process', icon: 'fa-clipboard-list', tit: 'Selection Process' },
                { el: 'age_limit', icon: 'fa-user-clock', tit: 'Age Limit' },
                { el: 'application_fees', icon: 'fa-money-bill-wave', tit: 'Application Fees' },
                { el: 'category_wise_vacancies', icon: 'fa-users', tit: 'Category-wise Vacancies' },
                { el: 'exam_prediction', icon: 'fa-lightbulb', tit: 'Exam Prediction' }
            ];

            blocks.forEach(b => {
                const rawVal = getRawVal(b.el);
                if (rawVal) { const list = buildList(rawVal, b.icon); html += `<div class="bronline-card-box"><h3><i class="fas ${b.icon}"></i> ${b.tit}</h3>${list}</div>`; }
            });

            // MULTIPLE OTHER DETAILS & CUSTOM FIELDS
            document.querySelectorAll('#other-details-container .row').forEach(r => {
                const t = sanitizeInput(r.querySelector('input[name="other_details_title[]"]').value);
                const c = buildList(r.querySelector('textarea[name="other_details_content[]"]').value, 'fa-info-circle');
                if (t && c && r.querySelector('textarea[name="other_details_content[]"]').value.trim() !== '') { html += `<div class="bronline-card-box"><h3><i class="fas fa-list"></i> ${t}</h3>${c}</div>`; }
            });
            document.querySelectorAll('#custom-fields-container .row').forEach(r => {
                const t = sanitizeInput(r.querySelector('input[name="custom_heading[]"]').value);
                const c = buildList(r.querySelector('textarea[name="custom_content[]"]').value, 'fa-sticky-note');
                if (t && c && r.querySelector('textarea[name="custom_content[]"]').value.trim() !== '') { html += `<div class="bronline-card-box"><h3><i class="fas fa-asterisk"></i> ${t}</h3>${c}</div>`; }
            });

            // --- LINKS BLOCK ---
            let lnk = '';
            
            // Notification URL or File Preview Logic
            const notifFile = document.getElementById('notification_file');
            let notifVal = getVal('notification_url');
            if(!notifVal && notifFile && notifFile.files.length > 0) { notifVal = '#'; } // If file selected but no URL, show dummy link in preview
            
            lnk += generateLinkBtn('Notification', notifVal, 'fas fa-file-alt');
            lnk += generateLinkBtn('Apply Online', getVal('apply_url'), 'fas fa-external-link-alt');
            lnk += generateLinkBtn('Admit Card', getVal('admit_card_url'), 'fas fa-ticket-alt');
            lnk += generateLinkBtn('Official Website', getVal('official_website_url'), 'fas fa-globe');

            // Custom Links URL or File Preview Logic
            document.querySelectorAll('#custom-links-container .row').forEach(r => {
                const ln = sanitizeInput(r.querySelector('input[name="custom_link_name[]"]').value);
                let lu = sanitizeInput(r.querySelector('input[name="custom_link_url[]"]').value);
                const lFile = r.querySelector('input[name="custom_link_file[]"]');
                
                if(!lu && lFile && lFile.files.length > 0) { lu = '#'; } // Show dummy link in preview if file selected
                
                if (ln && lu) lnk += generateLinkBtn(ln, lu, 'fas fa-link');
            });

            if (lnk) html += `<div class="bronline-card-box bronline-important-links-card"><h3><i class="fas fa-link"></i> Important Links</h3><div>${lnk}</div></div>`;

            // BOLD CSS
            const css = `<style>
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
.bronline-wrapper a.bronline-disabled-link { background: #6c757d !important; box-shadow: none !important; cursor: not-allowed !important; opacity: 0.8 !important; pointer-events: none !important; }
.bronline-wrapper .bronline-last-date-remaining { display: inline-block !important; background: linear-gradient(45deg, #dc3545, #8b0000) !important; color: #fff !important; padding: 4px 12px !important; border-radius: 6px !important; font-weight: 900 !important; margin-left: 12px !important; font-size: 0.9em !important; animation: bronline-blink 1s infinite alternate !important; box-shadow: 0 0 10px rgba(220,53,69,.6) !important; }
@keyframes bronline-blink { 0% { opacity: 1; transform: scale(1); } 100% { opacity: 0.85; transform: scale(1.05); box-shadow: 0 0 15px rgba(220,53,69,.9) !important; } }
@media (max-width: 600px) { .bronline-wrapper a.bronline-link-button { display: block !important; width: 100% !important; text-align: center !important; margin-bottom: 12px !important; } }
</style>`;

            const finalHtmlContent = `<div class="bronline-wrapper">\n` + html + `\n</div>`;
            document.getElementById('live-preview').innerHTML = css + finalHtmlContent;
            document.getElementById('raw-html-output').value = css + '\n' + finalHtmlContent;
            
        } catch (error) {
            console.error("Preview Update Error: ", error);
        }
    }
    
    bindEvents(); updatePreview();
});
</script>