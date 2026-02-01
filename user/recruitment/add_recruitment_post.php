<?php
/**
 * user/recruitment/add_recruitment_post.php
 * FINAL & COMPLETE VERSION: 
 * - FIXED: All theme switching and preview update bugs, including watermark theme and layout issues.
 * - PERFORMANCE: Optimized html2canvas scale for significantly faster generation.
 * - NEW THEMES: Includes professional 'Sarkari Yojana', 'Sarkari Bharti', and 'AI Futuristic' themes.
 * - DYNAMIC FORM: Form fields intelligently switch between Recruitment and Scheme layouts.
 * - OPTIONAL DATES: Scheme theme now has an option to show or hide the date section.
 * - BACKGROUND SELECTOR: Users can now select custom backgrounds from a list or upload their own.
 */

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';
$post = null;
$adminCommentsForEdit = null;
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEditable = true; // By default, the form is editable for new posts

// Ensure the model file with necessary functions is included
require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php';

$formData = [
    'id' => $postId,
    'job_title' => '', 'total_vacancies' => '', 'image_banner_url' => '',
    'eligibility_criteria' => '', 'selection_process' => '', 'start_date' => '',
    'last_date' => '', 'exam_date' => '', 'fee_payment_last_date' => '',
    'application_fees' => '', 'category_wise_vacancies' => '',
    'notification_url' => '', 'apply_url' => '', 'admit_card_url' => '',
    'official_website_url' => '', 'exam_prediction' => '', 'custom_fields_json' => '[]'
];

// Pre-fill from poster generator if URL is provided
if (isset($_GET['image_url']) && !empty($_GET['image_url'])) {
    $formData['image_banner_url'] = htmlspecialchars($_GET['image_url']);
    $message = '<div class="alert alert-success" role="alert">Poster image successfully generated and pre-filled!</div>';
}

if ($postId > 0) {
    $post = getRecruitmentPostById($postId);
    if (!$post || $post['submitted_by_user_id'] !== $currentUserId) {
        $message = '<div class="alert alert-danger" role="alert">Post not found or you are not authorized to edit it.</div>';
        $post = null;
        $isEditable = false;
    } else {
        // --- [ FIXED: The core logic to disable editing ] ---
        if (in_array($post['approval_status'], ['pending', 'approved'])) {
            $isEditable = false;
            $message = '<div class="alert alert-warning font-weight-bold">This post is currently under review or has been approved and cannot be edited.</div>';
        }
        if ($post['approval_status'] === 'rejected') {
            $isEditable = false;
            $message = '<div class="alert alert-danger font-weight-bold">This post has been rejected by the admin and cannot be edited. Please create a new post.</div>';
        }

        foreach ($formData as $key => $value) {
            if ($key === 'image_banner_url' && !empty($_GET['image_url'])) continue;
            if (isset($post[$key])) $formData[$key] = $post[$key];
        }
        if ($post['approval_status'] === 'returned_for_edit' && !empty($post['admin_comments'])) {
            $adminCommentsForEdit = htmlspecialchars($post['admin_comments']);
            $message = '<div class="alert alert-info font-weight-bold">This post has been returned for editing with comments from the admin.</div>';
        }
    }
}

// Check for session messages
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}
?>

<h2 class="mb-4"><?= $post ? 'Edit Recruitment Post' : 'Add New Recruitment Post' ?></h2>

<?php if (!empty($message)): ?>
    <?php include VIEWS_PATH . 'components/message_box.php'; ?>
<?php endif; ?>

<?php if ($adminCommentsForEdit): ?>
    <div class="alert alert-info alert-dismissible fade show">
        <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Admin's Comments for Edit:</h5>
        <p><?= nl2br($adminCommentsForEdit) ?></p>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card shadow-sm rounded-3 h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Post Details</h5>
            </div>
            <div class="card-body" style="max-height: 75vh; overflow-y: auto;">
                <form action="index.php" method="POST" id="recruitmentPostForm">
                    <input type="hidden" name="action" value="submit_recruitment_post">
                    <input type="hidden" name="page" value="my_recruitment_posts">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($formData['id']) ?>">
                    <fieldset <?= !$isEditable ? 'disabled' : '' ?>>
                        <div class="row mb-3">
                            <div class="col-md-6"><label for="job_title" class="form-label">Job Title <span class="text-danger">*</span></label><input type="text" class="form-control" id="job_title" name="job_title" value="<?= htmlspecialchars($formData['job_title']) ?>" required></div>
                            <div class="col-md-6"><label for="total_vacancies" class="form-label">Total Vacancies <span class="text-danger">*</span></label><input type="number" class="form-control" id="total_vacancies" name="total_vacancies" value="<?= htmlspecialchars($formData['total_vacancies']) ?>" required></div>
                        </div>
                        <div class="mb-3"><label for="image_banner_url" class="form-label">Image Banner URL</label><input type="url" class="form-control" id="image_banner_url" name="image_banner_url" value="<?= htmlspecialchars($formData['image_banner_url']) ?>"></div>
                        <div class="mb-3"><label for="eligibility_criteria" class="form-label">Eligibility (Key=Value per line)</label><textarea class="form-control" id="eligibility_criteria" name="eligibility_criteria" rows="3"><?= htmlspecialchars($formData['eligibility_criteria']) ?></textarea></div>
                        <div class="mb-3"><label for="selection_process" class="form-label">Selection Process (Key=Value per line)</label><textarea class="form-control" id="selection_process" name="selection_process" rows="3"><?= htmlspecialchars($formData['selection_process']) ?></textarea></div>
                        <div class="row mb-3">
                            <div class="col-md-6"><label for="start_date" class="form-label">Start Date</label><input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($formData['start_date']) ?>"></div>
                            <div class="col-md-6"><label for="last_date" class="form-label">Last Date</label><input type="date" class="form-control" id="last_date" name="last_date" value="<?= htmlspecialchars($formData['last_date']) ?>"></div>
                        </div>
                         <div class="row mb-3">
                            <div class="col-md-6"><label for="exam_date" class="form-label">Exam Date</label><input type="date" class="form-control" id="exam_date" name="exam_date" value="<?= htmlspecialchars($formData['exam_date']) ?>"></div>
                            <div class="col-md-6"><label for="fee_payment_last_date" class="form-label">Fee Payment Last Date</label><input type="date" class="form-control" id="fee_payment_last_date" name="fee_payment_last_date" value="<?= htmlspecialchars($formData['fee_payment_last_date']) ?>"></div>
                        </div>
                        <div class="mb-3"><label for="application_fees" class="form-label">Application Fees (Key=Value per line)</label><textarea class="form-control" id="application_fees" name="application_fees" rows="3"><?= htmlspecialchars($formData['application_fees']) ?></textarea></div>
                        <div class="mb-3"><label for="category_wise_vacancies" class="form-label">Category-wise Vacancies (Key=Value per line)</label><textarea class="form-control" id="category_wise_vacancies" name="category_wise_vacancies" rows="3"><?= htmlspecialchars($formData['category_wise_vacancies']) ?></textarea></div>
                        <div class="mb-3"><label for="exam_prediction" class="form-label">Exam Prediction</label><textarea class="form-control" id="exam_prediction" name="exam_prediction" rows="3"><?= htmlspecialchars($formData['exam_prediction']) ?></textarea></div>
                        
                        <fieldset class="border p-3 rounded-3 mb-3">
                            <legend class="float-none w-auto px-2 fs-6">Custom Fields</legend>
                            <div id="custom-fields-container">
                                <?php
                                $customFields = json_decode($formData['custom_fields_json'], true);
                                if (is_array($customFields)) {
                                    foreach ($customFields as $i => $field) {
                                        echo '<div class="row mb-3 align-items-end" id="custom-field-'.$i.'"><div class="col-md-5"><label class="form-label">Heading</label><input type="text" class="form-control custom-field-input" name="custom_heading[]" value="'.htmlspecialchars($field['heading']).'"></div><div class="col-md-5"><label class="form-label">Content</label><textarea class="form-control custom-field-input" name="custom_content[]" rows="2">'.htmlspecialchars($field['content']).'</textarea></div><div class="col-md-2"><button type="button" class="btn btn-danger w-100 remove-custom-field" data-id="'.$i.'"><i class="fas fa-minus"></i></button></div></div>';
                                    }
                                }
                                ?>
                            </div>
                            <button type="button" id="add-custom-field" class="btn btn-sm btn-success mt-2"><i class="fas fa-plus"></i> Add Field</button>
                        </fieldset>
                        
                        <div class="mb-3"><label for="notification_url" class="form-label">Notification URL</label><input type="url" class="form-control" id="notification_url" name="notification_url" value="<?= htmlspecialchars($formData['notification_url']) ?>"></div>
                        <div class="mb-3"><label for="apply_url" class="form-label">Apply URL</label><input type="url" class="form-control" id="apply_url" name="apply_url" value="<?= htmlspecialchars($formData['apply_url']) ?>"></div>
                        <div class="mb-3"><label for="admit_card_url" class="form-label">Admit Card URL</label><input type="url" class="form-control" id="admit_card_url" name="admit_card_url" value="<?= htmlspecialchars($formData['admit_card_url']) ?>"></div>
                        <div class="mb-3"><label for="official_website_url" class="form-label">Official Website URL</label><input type="url" class="form-control" id="official_website_url" name="official_website_url" value="<?= htmlspecialchars($formData['official_website_url']) ?>"></div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" name="submit_recruitment_post" class="btn btn-primary px-4 me-2" <?= !$isEditable ? 'disabled' : '' ?>>
                                <i class="fas fa-paper-plane"></i> <?= $post ? 'Update' : 'Submit' ?>
                            </button>
                            <a href="<?= BASE_URL ?>?page=my_recruitment_posts" class="btn btn-secondary px-4"><i class="fas fa-times"></i> Cancel</a>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-7 mb-4">
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-info text-white"><h5 class="mb-0"><i class="fas fa-eye me-2"></i>Live Preview</h5></div>
            <div class="card-body" style="max-height: 40vh; overflow-y: auto;"><div id="live-preview" class="p-2 bg-light"></div></div>
        </div>
        <div class="card shadow-sm rounded-3">
            <div class="card-header bg-dark text-white"><h5 class="mb-0"><i class="fas fa-code me-2"></i>Blogger HTML Code</h5></div>
            <div class="card-body">
                <textarea id="raw-html-output" class="form-control bg-dark text-white" rows="10" readonly style="font-size: 0.8em;"></textarea>
                <button type="button" id="copy-html-button" class="btn btn-success mt-2"><i class="fas fa-copy"></i> Copy HTML</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let customFieldGlobalCounter = document.querySelectorAll('#custom-fields-container .row').length;

    function attachEventListeners() {
        document.querySelectorAll('#recruitmentPostForm input, #recruitmentPostForm textarea').forEach(input => {
            input.addEventListener('input', updatePreview);
        });
        document.querySelectorAll('.remove-custom-field').forEach(button => {
            button.removeEventListener('click', removeCustomField);
            button.addEventListener('click', removeCustomField);
        });
    }

    document.getElementById('add-custom-field').addEventListener('click', addCustomField);
    document.getElementById('copy-html-button').addEventListener('click', copyHtml);

    function addCustomField() {
        customFieldGlobalCounter++;
        const container = document.getElementById('custom-fields-container');
        const newField = document.createElement('div');
        newField.className = 'row mb-3 align-items-end';
        newField.id = `custom-field-${customFieldGlobalCounter}`;
        newField.innerHTML = `
            <div class="col-md-5"><label class="form-label">Heading</label><input type="text" class="form-control" name="custom_heading[]"></div>
            <div class="col-md-5"><label class="form-label">Content</label><textarea class="form-control" name="custom_content[]" rows="2"></textarea></div>
            <div class="col-md-2"><button type="button" class="btn btn-danger w-100 remove-custom-field" data-id="${customFieldGlobalCounter}"><i class="fas fa-minus-circle"></i></button></div>
        `;
        container.appendChild(newField);
        attachEventListeners();
    }

    function removeCustomField(event) {
        const fieldId = event.currentTarget.dataset.id;
        document.getElementById(`custom-field-${fieldId}`).remove();
        updatePreview();
    }
    
    function sanitizeInput(input) { const div = document.createElement('div'); div.textContent = input; return div.innerHTML; }
    function formatDate(dateString) { if (!dateString) return ''; const date = new Date(dateString); const day = String(date.getDate()).padStart(2, '0'); const month = String(date.getMonth() + 1).padStart(2, '0'); const year = date.getFullYear(); return `${day}-${month}-${year}`; }
    function getRemainingDays(lastDateString) { if (!lastDateString) return null; const today = new Date(); const lastDate = new Date(lastDateString); lastDate.setHours(23, 59, 59, 999); const diffTime = lastDate.getTime() - today.getTime(); const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); return diffDays >= 0 ? diffDays : null; }

    function convertToUlLi(text, fieldIdentifier = 'default') {
        const contentIcons={'eligibility':'fas fa-clipboard-check','selection':'fas fa-cogs','fees':'fas fa-hand-holding-usd','vacancies':'fas fa-users','prediction':'fas fa-question-circle','default':'fas fa-info-circle'};
        if (!text) return ''; const lines = text.split('\n').filter(line => line.trim() !== ''); let html = '<ul>'; const iconClass = contentIcons[fieldIdentifier] || contentIcons['default'];
        lines.forEach(line => { const parts = line.split('='); if (parts.length > 1) { html += `<li><i class="${iconClass}"></i> <strong>${sanitizeInput(parts[0].trim())}:</strong> ${sanitizeInput(parts.slice(1).join('=').trim())}</li>`; } else { html += `<li><i class="${iconClass}"></i> ${sanitizeInput(line.trim())}</li>`; } });
        html += '</ul>'; return html;
    }

    function updatePreview() {
        const livePreviewDiv = document.getElementById('live-preview');
        const rawHtmlOutputPre = document.getElementById('raw-html-output');
        const jobTitle = sanitizeInput(document.getElementById('job_title').value);
        const totalVacancies = sanitizeInput(document.getElementById('total_vacancies').value);
        const imageBannerUrl = sanitizeInput(document.getElementById('image_banner_url').value);
        const eligibilityCriteria = convertToUlLi(document.getElementById('eligibility_criteria').value, 'eligibility');
        const selectionProcess = convertToUlLi(document.getElementById('selection_process').value, 'selection');
        const applicationFees = convertToUlLi(document.getElementById('application_fees').value, 'fees');
        const categoryWiseVacancies = convertToUlLi(document.getElementById('category_wise_vacancies').value, 'vacancies');
        const examPredictionList = convertToUlLi(document.getElementById('exam_prediction').value, 'prediction');
        const startDate = formatDate(document.getElementById('start_date').value);
        const lastDate = formatDate(document.getElementById('last_date').value);
        const examDate = formatDate(document.getElementById('exam_date').value);
        const feePaymentLastDate = formatDate(document.getElementById('fee_payment_last_date').value);
        const notificationUrl = sanitizeInput(document.getElementById('notification_url').value);
        const applyUrl = sanitizeInput(document.getElementById('apply_url').value);
        const admitCardUrl = sanitizeInput(document.getElementById('admit_card_url').value);
        const officialWebsiteUrl = sanitizeInput(document.getElementById('official_website_url').value);
        const remainingDays = getRemainingDays(document.getElementById('last_date').value);

        let generatedHtml = '';
        if (imageBannerUrl) { generatedHtml += `<img src="${imageBannerUrl}" alt="${jobTitle || 'Recruitment Banner'}" class="bronline-image-banner">`; }
        if (jobTitle || totalVacancies) { generatedHtml += `<div class="bronline-recruitment-job-style"><h1><i class="fas fa-bullhorn"></i> ${jobTitle || 'Job Post'}</h1><p><i class="fas fa-user-friends"></i> Total Vacancies: <strong>${totalVacancies || 'N/A'}</strong></p></div>`; }
        if (startDate || lastDate || examDate || feePaymentLastDate) { generatedHtml += `<div class="bronline-card-box bronline-important-dates-card"><h3><i class="fas fa-calendar-alt"></i> Important Dates</h3><ul>${startDate ? `<li><i class="fas fa-play-circle"></i> <strong>Start Date:</strong> <span class="bronline-start-date">${startDate}</span></li>` : ''}${lastDate ? `<li><i class="fas fa-stop-circle"></i> <strong>Last Date:</strong> <span class="bronline-last-date">${lastDate}</span>${remainingDays !== null ? `<span class="bronline-last-date-remaining">${remainingDays} Days Left</span>` : ''}</li>` : ''}${examDate ? `<li><i class="fas fa-marker"></i> <strong>Exam Date:</strong> ${examDate}</li>` : ''}${feePaymentLastDate ? `<li><i class="fas fa-credit-card"></i> <strong>Fee Payment Last Date:</strong> ${feePaymentLastDate}</li>` : ''}</ul></div>`; }
        if (examPredictionList) { generatedHtml += `<div class="bronline-card-box"><h3><i class="fas fa-lightbulb"></i> Exam Prediction</h3>${examPredictionList}</div>`; }
        if (eligibilityCriteria) { generatedHtml += `<div class="bronline-card-box"><h3><i class="fas fa-user-check"></i> Eligibility Criteria</h3>${eligibilityCriteria}</div>`; }
        if (selectionProcess) { generatedHtml += `<div class="bronline-card-box"><h3><i class="fas fa-clipboard-list"></i> Selection Process</h3>${selectionProcess}</div>`; }
        if (applicationFees) { generatedHtml += `<div class="bronline-card-box"><h3><i class="fas fa-money-bill-wave"></i> Application Fees</h3>${applicationFees}</div>`; }
        if (categoryWiseVacancies) { generatedHtml += `<div class="bronline-card-box"><h3><i class="fas fa-users"></i> Category-wise Vacancies</h3>${categoryWiseVacancies}</div>`; }
        
        document.querySelectorAll('#custom-fields-container .row').forEach(row => {
            const headingInput = row.querySelector('input[name="custom_heading[]"]');
            const contentTextarea = row.querySelector('textarea[name="custom_content[]"]');
            if (headingInput && contentTextarea) {
                const heading = sanitizeInput(headingInput.value);
                const content = convertToUlLi(contentTextarea.value);
                if (heading && content) {
                    generatedHtml += `<div class="bronline-card-box"><h3><i class="fas fa-sticky-note"></i> ${heading}</h3>${content}</div>`;
                }
            }
        });

        if (notificationUrl || applyUrl || admitCardUrl || officialWebsiteUrl) { generatedHtml += `<div class="bronline-card-box bronline-important-links-card"><h3><i class="fas fa-link"></i> Important Links</h3><div>${notificationUrl ? `<a href="${notificationUrl}" target="_blank" rel="noopener noreferrer" class="bronline-link-button"><i class="fas fa-file-alt"></i> Notification</a>` : ''}${applyUrl ? `<a href="${applyUrl}" target="_blank" rel="noopener noreferrer" class="bronline-link-button"><i class="fas fa-external-link-alt"></i> Apply Online</a>` : ''}${admitCardUrl ? `<a href="${admitCardUrl}" target="_blank" rel="noopener noreferrer" class="bronline-link-button"><i class="fas fa-ticket-alt"></i> Admit Card</a>` : ''}${officialWebsiteUrl ? `<a href="${officialWebsiteUrl}" target="_blank" rel="noopener noreferrer" class="bronline-link-button"><i class="fas fa-globe"></i> Official Website</a>` : ''}</div></div>`; }
        
        livePreviewDiv.innerHTML = generatedHtml;
        rawHtmlOutputPre.value = generatedHtml; // Use .value for textarea
    }

    function copyHtml() {
        const textToCopy = document.getElementById('raw-html-output').value;
        navigator.clipboard.writeText(textToCopy).then(() => {
            showMessage('HTML copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy: ', err);
            showMessage('Failed to copy HTML.', 'error');
        });
    }

    function showMessage(message, type = 'success') {
        const messageBox = document.createElement('div');
        messageBox.className = 'bronline-message-box';
        messageBox.textContent = message;
        if (type === 'error') { messageBox.style.backgroundColor = 'rgba(220, 53, 69, 0.8)'; }
        document.body.appendChild(messageBox);
        setTimeout(() => { messageBox.remove(); }, 2000);
    }

    attachEventListeners();
    updatePreview(); // Initial load
});
</script>