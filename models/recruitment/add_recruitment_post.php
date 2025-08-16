<?php
/**
 * user/recruitment/add_recruitment_post.php
 *
 * This file allows Data Entry Operators (DEOs) to submit new recruitment posts.
 * It also allows editing existing posts if an ID is provided and the post belongs to the DEO.
 *
 * It ensures that only authenticated DEO users can access this page.
 */

// Include the main configuration file using ROOT_PATH.
require_once ROOT_PATH . 'config.php';

// Now, other includes can safely use constants from config.php
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';
require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php'; // IMPORTANT: This line includes the functions

// Restrict access to Data Entry Operator users only.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'data_entry_operator') {
    error_log("DEBUG: add_recruitment_post.php - Access denied. Not logged in or not DEO. User Role: " . ($_SESSION['user_role'] ?? 'N/A'));
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}
error_log("DEBUG: add_recruitment_post.php - Access granted for DEO. User ID: " . ($_SESSION['user_id'] ?? 'N/A'));


$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';
$post = null; // To store existing post details for editing
$adminCommentsForEdit = null; // To store admin comments if returned for edit

$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize form fields with empty values or default values
$formData = [
    'job_title' => '',
    'total_vacancies' => '',
    'image_banner_url' => '',
    'eligibility_criteria' => '',
    'selection_process' => '',
    'start_date' => '',
    'last_date' => '',
    'exam_date' => '',
    'fee_payment_last_date' => '',
    'application_fees' => '',
    'category_wise_vacancies' => '',
    'notification_url' => '',
    'apply_url' => '',
    'admit_card_url' => '',
    'official_website_url' => '',
    'exam_prediction' => '',
    'custom_fields_json' => '[]' // Store custom fields as JSON
];

// If an ID is provided, try to fetch the post for editing
if ($postId > 0) {
    try {
        $post = getRecruitmentPostById($postId);
        // Allow editing if status is 'pending' or 'returned_for_edit'
        if (!$post || $post['submitted_by_user_id'] !== $currentUserId || (!in_array($post['approval_status'], ['pending', 'returned_for_edit']))) {
            $message = '<div class="alert alert-danger" role="alert">Recruitment post not found or you are not authorized to edit it, or it has already been processed.</div>';
            $post = null; // Clear post data if not authorized or processed
            error_log("DEBUG: add_recruitment_post.php - Post ID " . $postId . " not found, not authorized, or already processed for editing.");
        } else {
            // Populate form data from fetched post
            foreach ($formData as $key => $value) {
                if (isset($post[$key])) {
                    $formData[$key] = $post[$key];
                }
            }
            if ($post['approval_status'] === 'returned_for_edit' && !empty($post['admin_comments'])) {
                $adminCommentsForEdit = htmlspecialchars($post['admin_comments']);
            }
            error_log("DEBUG: add_recruitment_post.php - Loaded post ID " . $postId . " for editing. Form Data: " . print_r($formData, true));
        }
    } catch (Exception $e) {
        error_log("Error fetching recruitment post for edit: " . $e->getMessage());
        $message = '<div class="alert alert-danger" role="alert">Error loading post details: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// --- Handle Form Submission for New/Edit Recruitment Post ---
if (isset($_POST['submit_recruitment_post'])) {
    $job_title = trim($_POST['job_title']);
    $total_vacancies = trim($_POST['total_vacancies']);
    $image_banner_url = trim($_POST['image_banner_url']);
    $eligibility_criteria = trim($_POST['eligibility_criteria']);
    $selection_process = trim($_POST['selection_process']);
    $start_date = trim($_POST['start_date']);
    $last_date = trim($_POST['last_date']);
    $exam_date = trim($_POST['exam_date']);
    $fee_payment_last_date = trim($_POST['fee_payment_last_date']);
    $application_fees = trim($_POST['application_fees']);
    $category_wise_vacancies = trim($_POST['category_wise_vacancies']);
    $notification_url = trim($_POST['notification_url']);
    $apply_url = trim($_POST['apply_url']);
    $admit_card_url = trim($_POST['admit_card_url']);
    $official_website_url = trim($_POST['official_website_url']);
    $exam_prediction = trim($_POST['exam_prediction']);
    $submitted_by_user_id = $currentUserId;

    // Handle custom fields (assuming they come as arrays of heading/content)
    $custom_headings = $_POST['custom_heading'] ?? [];
    $custom_contents = $_POST['custom_content'] ?? [];
    $custom_fields_array = [];
    for ($i = 0; $i < count($custom_headings); $i++) {
        if (!empty(trim($custom_headings[$i])) || !empty(trim($custom_contents[$i]))) {
            $custom_fields_array[] = [
                'heading' => trim($custom_headings[$i]),
                'content' => trim($custom_contents[$i])
            ];
        }
    }
    $custom_fields_json = json_encode($custom_fields_array);


    // Basic validation
    if (empty($job_title) || empty($total_vacancies)) {
        $message = '<div class="alert alert-danger" role="alert">Please fill in all required fields (Job Title, Total Vacancies).</div>';
    } else {
        try {
            if ($postId > 0 && $post) { // Editing existing post
                error_log("DEBUG: add_recruitment_post.php - Attempting to update post ID: " . $postId);
                if (updateRecruitmentPostHtml(
                    $postId, $job_title, $total_vacancies, $image_banner_url,
                    $eligibility_criteria, $selection_process, $start_date, $last_date,
                    $exam_date, $fee_payment_last_date, $application_fees, $category_wise_vacancies,
                    $notification_url, $apply_url, $admit_card_url, $official_website_url,
                    $exam_prediction, $custom_fields_json
                )) {
                    $message = '<div class="alert alert-success" role="alert">Recruitment post updated successfully! It has been sent for re-approval.</div>';
                    // Re-fetch post data to show updated info
                    $post = getRecruitmentPostById($postId);
                    // Update formData with new post data
                    foreach ($formData as $key => $value) {
                        if (isset($post[$key])) {
                            $formData[$key] = $post[$key];
                        }
                    }
                    $adminCommentsForEdit = null; // Clear comments after re-submission
                    error_log("DEBUG: add_recruitment_post.php - Post ID " . $postId . " updated successfully.");
                } else {
                    $message = '<div class="alert alert-danger" role="alert">Failed to update recruitment post.</div>';
                    error_log("ERROR: add_recruitment_post.php - Failed to update post ID: " . $postId);
                }
            } else { // Adding new post
                error_log("DEBUG: add_recruitment_post.php - Attempting to add new post.");
                if (addRecruitmentPostHtml(
                    $job_title, $total_vacancies, $image_banner_url,
                    $eligibility_criteria, $selection_process, $start_date, $last_date,
                    $exam_date, $fee_payment_last_date, $application_fees, $category_wise_vacancies,
                    $notification_url, $apply_url, $admit_card_url, $official_website_url,
                    $exam_prediction, $custom_fields_json, $submitted_by_user_id
                )) {
                    $message = '<div class="alert alert-success" role="alert">Recruitment post submitted for approval!</div>';
                    // Clear form fields after successful submission
                    $formData = [ // Reset form data
                        'job_title' => '', 'total_vacancies' => '', 'image_banner_url' => '',
                        'eligibility_criteria' => '', 'selection_process' => '', 'start_date' => '',
                        'last_date' => '', 'exam_date' => '', 'fee_payment_last_date' => '',
                        'application_fees' => '', 'category_wise_vacancies' => '',
                        'notification_url' => '', 'apply_url' => '', 'admit_card_url' => '',
                        'official_website_url' => '', 'exam_prediction' => '', 'custom_fields_json' => '[]'
                    ];
                    error_log("DEBUG: add_recruitment_post.php - New post added successfully.");
                } else {
                    $message = '<div class="alert alert-danger" role="alert">Failed to submit recruitment post.</div>';
                    error_log("ERROR: add_recruitment_post.php - Failed to add new post.");
                }
            }
        } catch (Exception $e) {
            error_log("Error submitting/updating recruitment post: " . $e->getMessage());
            $message = '<div class="alert alert-danger" role="alert">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4"><?= $post ? 'Edit Recruitment Post' : 'Add New Recruitment Post' ?></h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; ?>
            <script>
                setupAutoHideAlerts();
            </script>
        <?php endif; ?>

        <?php if ($adminCommentsForEdit): ?>
            <div class="alert alert-info alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Admin's Comments for Edit:</h5>
                <p class="mb-0"><?= nl2br($adminCommentsForEdit) ?></p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm rounded-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-bullhorn me-2"></i><?= $post ? 'Edit Post Details' : 'Enter New Post Details' ?></h5>
            </div>
            <div class="card-body">
                <form action="" method="POST" id="recruitmentPostForm">
                    <?php if ($post): ?>
                        <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id']) ?>">
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="job_title" class="form-label">Job Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-pill" id="job_title" name="job_title" value="<?= htmlspecialchars($formData['job_title']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="total_vacancies" class="form-label">Total Vacancies <span class="text-danger">*</span></label>
                            <input type="number" class="form-control rounded-pill" id="total_vacancies" name="total_vacancies" value="<?= htmlspecialchars($formData['total_vacancies']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="image_banner_url" class="form-label">Image Banner URL</label>
                        <input type="url" class="form-control rounded-pill" id="image_banner_url" name="image_banner_url" value="<?= htmlspecialchars($formData['image_banner_url']) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="eligibility_criteria" class="form-label">Eligibility Criteria (Key=Value per line)</label>
                        <textarea class="form-control rounded-3" id="eligibility_criteria" name="eligibility_criteria" rows="4" placeholder="e.g., Age=18-30 years&#10;Education=Graduation in any discipline"><?= htmlspecialchars($formData['eligibility_criteria']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="selection_process" class="form-label">Selection Process (Key=Value per line)</label>
                        <textarea class="form-control rounded-3" id="selection_process" name="selection_process" rows="4" placeholder="e.g., Stage 1=Written Exam&#10;Stage 2=Interview"><?= htmlspecialchars($formData['selection_process']) ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Application Start Date</label>
                            <input type="date" class="form-control rounded-pill" id="start_date" name="start_date" value="<?= htmlspecialchars($formData['start_date']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="last_date" class="form-label">Application Last Date</label>
                            <input type="date" class="form-control rounded-pill" id="last_date" name="last_date" value="<?= htmlspecialchars($formData['last_date']) ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="exam_date" class="form-label">Exam Date</label>
                            <input type="date" class="form-control rounded-pill" id="exam_date" name="exam_date" value="<?= htmlspecialchars($formData['exam_date']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="fee_payment_last_date" class="form-label">Fee Payment Last Date</label>
                            <input type="date" class="form-control rounded-pill" id="fee_payment_last_date" name="fee_payment_last_date" value="<?= htmlspecialchars($formData['fee_payment_last_date']) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="application_fees" class="form-label">Application Fees (Key=Value per line)</label>
                        <textarea class="form-control rounded-3" id="application_fees" name="application_fees" rows="3" placeholder="e.g., General=₹500&#10;SC/ST=₹250"><?= htmlspecialchars($formData['application_fees']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="category_wise_vacancies" class="form-label">Category-wise Vacancies (Key=Value per line)</label>
                        <textarea class="form-control rounded-3" id="category_wise_vacancies" name="category_wise_vacancies" rows="3" placeholder="e.g., UR=100&#10;OBC=50&#10;SC=30"><?= htmlspecialchars($formData['category_wise_vacancies']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="exam_prediction" class="form-label">Exam Prediction / Expected (Multi-line text)</label>
                        <textarea class="form-control rounded-3" id="exam_prediction" name="exam_prediction" rows="4" placeholder="e.g., Expected questions for each section&#10;Tips for preparation"><?= htmlspecialchars($formData['exam_prediction']) ?></textarea>
                    </div>

                    <!-- Custom Fields Section -->
                    <fieldset class="border p-3 rounded-3 mb-3">
                        <legend class="float-none w-auto px-2 fs-5">Custom Fields</legend>
                        <div id="custom-fields-container">
                            <?php
                            $customFields = json_decode($formData['custom_fields_json'], true);
                            if (is_array($customFields) && !empty($customFields)) {
                                $customFieldCounter = 0;
                                foreach ($customFields as $field) {
                                    $customFieldCounter++;
                                    echo '<div class="row mb-3 align-items-end" id="custom-field-' . $customFieldCounter . '">';
                                    echo '<div class="col-md-5">';
                                    echo '<label for="custom_heading_' . $customFieldCounter . '" class="form-label">Custom Heading</label>';
                                    echo '<input type="text" class="form-control rounded-pill custom-field-heading" id="custom_heading_' . $customFieldCounter . '" name="custom_heading[]" value="' . htmlspecialchars($field['heading'] ?? '') . '">';
                                    echo '</div>';
                                    echo '<div class="col-md-5">';
                                    echo '<label for="custom_content_' . $customFieldCounter . '" class="form-label">Custom Content (Multi-line)</label>';
                                    echo '<textarea class="form-control rounded-3 custom-field-content" id="custom_content_' . $customFieldCounter . '" name="custom_content[]" rows="2">' . htmlspecialchars($field['content'] ?? '') . '</textarea>';
                                    echo '</div>';
                                    echo '<div class="col-md-2 d-flex align-items-end">';
                                    echo '<button type="button" class="btn btn-danger rounded-pill w-100 remove-custom-field" data-id="' . $customFieldCounter . '"><i class="fas fa-minus-circle"></i> Remove</button>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                        <button type="button" id="add-custom-field" class="btn btn-success rounded-pill mt-3"><i class="fas fa-plus-circle"></i> Add Custom Field</button>
                    </fieldset>

                    <div class="mb-3">
                        <label for="notification_url" class="form-label">Notification URL</label>
                        <input type="url" class="form-control rounded-pill" id="notification_url" name="notification_url" value="<?= htmlspecialchars($formData['notification_url']) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="apply_url" class="form-label">Apply URL</label>
                        <input type="url" class="form-control rounded-pill" id="apply_url" name="apply_url" value="<?= htmlspecialchars($formData['apply_url']) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="admit_card_url" class="form-label">Admit Card URL</label>
                        <input type="url" class="form-control rounded-pill" id="admit_card_url" name="admit_card_url" value="<?= htmlspecialchars($formData['admit_card_url']) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="official_website_url" class="form-label">Official Website URL</label>
                        <input type="url" class="form-control rounded-pill" id="official_website_url" name="official_website_url" value="<?= htmlspecialchars($formData['official_website_url']) ?>">
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" name="submit_recruitment_post" class="btn btn-primary rounded-pill px-4">
                            <i class="fas fa-paper-plane me-2"></i><?= $post ? 'Update Post' : 'Submit Post' ?>
                        </button>
                        <a href="<?= BASE_URL ?>?page=deo_dashboard" class="btn btn-secondary rounded-pill px-4">
                            <i class="fas fa-times-circle me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm rounded-3 mt-5">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Live Preview</h5>
            </div>
            <div class="card-body">
                <div id="live-preview" class="border p-3 rounded-3 bg-light">
                    <!-- Live preview will be rendered here by JavaScript -->
                </div>
            </div>
        </div>

        <div class="card shadow-sm rounded-3 mt-4 mb-5">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-code me-2"></i>Raw HTML Output (for Blogger)</h5>
            </div>
            <div class="card-body">
                <pre id="raw-html-output" class="bg-dark text-white p-3 rounded-3 overflow-auto" style="max-height: 400px;"></pre>
                <button type="button" id="copy-html-button" class="btn btn-success rounded-pill mt-3"><i class="fas fa-copy"></i> Copy HTML</button>
            </div>
        </div>

    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>

<style>
    /* Custom CSS for fade-out alert */
    .alert.fade-out {
        opacity: 0;
        transition: opacity 0.5s ease-out;
    }
    /* Styling for the live preview and generated HTML */
    .bronline-image-banner {
        max-width: 100%;
        height: auto;
        display: block;
        margin: 0 auto 25px auto;
        border: 3px solid #dc3545; /* Red border */
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    .bronline-recruitment-job-style {
        text-align: center;
        margin-bottom: 30px;
        padding: 20px;
        background: linear-gradient(45deg, #e0efff, #cce0ff);
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }
    .bronline-recruitment-job-style::before {
        content: "\f0b1"; /* Briefcase icon */
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        font-size: 4em;
        color: rgba(0, 123, 255, 0.15); /* Slightly less transparent */
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 0;
        transform: rotate(-15deg);
    }
    .bronline-recruitment-job-style::after {
        content: "\f500"; /* Users icon */
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        font-size: 4em;
        color: rgba(0, 123, 255, 0.15); /* Slightly less transparent */
        position: absolute;
        bottom: 10px;
        right: 10px;
        z-index: 0;
        transform: rotate(15deg);
    }
    .bronline-recruitment-job-style h1 {
        color: #007bff;
        font-size: 2.5em;
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
    }
    .bronline-recruitment-job-style p {
        font-size: 1.1em;
        color: #555;
        position: relative;
        z-index: 1;
    }
    .bronline-card-box {
        background: linear-gradient(135deg, #ffffff, #f0f0f0);
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .bronline-card-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }
    .bronline-card-box h3 {
        color: #0056b3;
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 1.4em;
        border-bottom: 2px solid #cee5ff;
        padding-bottom: 10px;
        display: flex;
        align-items: center;
    }
    .bronline-card-box h3 i {
        margin-right: 10px;
        color: #007bff;
        font-size: 1.1em;
    }
    .bronline-card-box ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .bronline-card-box ul li {
        margin-bottom: 8px;
        padding-left: 0;
        position: relative;
        font-size: 1.05em;
    }
    .bronline-card-box ul li strong {
        color: #333;
        font-weight: bold;
    }
    .bronline-card-box ul li i {
        margin-right: 8px;
        font-size: 0.95em;
        vertical-align: middle;
    }
    .bronline-card-box:not(.bronline-important-dates-card) ul li i {
        color: #007bff;
    }
    .bronline-important-dates-card ul li i {
        color: #dc3545;
    }
    .bronline-card-box h3 .fa-user-check + span + ul li i,
    .bronline-card-box h3 .fa-clipboard-list + span + ul li i {
        color: #28a745;
    }
    .bronline-card-box h3 .fa-lightbulb + span + ul li i {
        color: #f0ad4e;
    }
    .bronline-important-dates-card h3 {
        color: #d9534f;
    }
    .bronline-important-dates-card h3 i {
        color: #d9534f;
    }
    .bronline-important-links-card {
        background: linear-gradient(45deg, #ffc107, #00bfff);
        color: #333;
    }
    .bronline-important-links-card h3 {
        color: #2b2b2b;
    }
    .bronline-important-links-card h3 i {
        color: #2b2b2b;
    }
    .bronline-important-links-card .bronline-link-button {
        display: inline-block;
        margin: 8px 10px 8px 0;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: bold;
        text-decoration: none;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        background: linear-gradient(45deg, #ff416c, #ff4b2b);
        color: white;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
    }
    .bronline-important-links-card .bronline-link-button.disabled {
        background: #adb5bd;
        cursor: not-allowed;
        opacity: 0.7;
        transform: none;
        box-shadow: none;
    }
    .bronline-important-links-card .bronline-link-button.disabled:hover {
        transform: none;
        box-shadow: none;
    }
    .bronline-link-button:hover:not(.disabled) {
        transform: scale(1.05);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
    }
    .bronline-start-date {
        color: #007bff;
        font-weight: bold;
    }
    .bronline-last-date {
        color: #dc3545;
        font-weight: bold;
    }
    .bronline-last-date-remaining {
        display: inline-block;
        background: linear-gradient(45deg, #dc3545, #8b0000);
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-weight: bold;
        animation: bronline-blink-effect 1s infinite alternate;
        box-shadow: 0 0 10px rgba(220, 53, 69, 0.6);
        margin-left: 10px;
    }
    @keyframes bronline-blink-effect {
        0% { background: linear-gradient(45deg, #dc3545, #8b0000); box-shadow: 0 0 10px rgba(220, 53, 69, 0.6); transform: scale(1); }
        50% { background: linear-gradient(45deg, #e67d7d, #ff6347); box-shadow: 0 0 20px rgba(255, 99, 71, 0.8); transform: scale(1.02); }
        100% { background: linear-gradient(45deg, #dc3545, #8b0000); box-shadow: 0 0 10px rgba(220, 53, 69, 0.6); transform: scale(1); }
    }
    .bronline-raw-html-output pre {
        background-color: #282c34;
        color: #f8f8f2;
        padding: 20px;
        border-radius: 8px;
        overflow-x: auto;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
        font-size: 0.9em;
        white-space: pre-wrap;
        word-wrap: break-word;
        tab-size: 4;
        line-height: 1.5;
        box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.2);
    }
    .bronline-message-box {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 15px 30px;
        border-radius: 10px;
        z-index: 1000;
        font-size: 1.1em;
        animation: bronline-fade-in-out 2s forwards;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    }
    @keyframes bronline-fade-in-out {
        0% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
        10% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        90% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        100% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
    }
</style>

<script>
    // Global counter for custom fields to ensure unique IDs
    let customFieldGlobalCounter = 0;

    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alert functionality (re-used across files for consistency)
        const alertElement = document.querySelector('.alert.fade.show');
        if (alertElement) {
            setTimeout(function() {
                const bootstrapAlert = bootstrap.Alert.getInstance(alertElement);
                if (bootstrapAlert) {
                    bootstrapAlert.close();
                } else {
                    alertElement.classList.add('fade-out');
                    setTimeout(() => alertElement.remove(), 500);
                }
            }, 5000); // 5 seconds
        }

        // Initialize customFieldGlobalCounter based on existing fields
        const existingCustomFields = document.querySelectorAll('.remove-custom-field');
        if (existingCustomFields.length > 0) {
            customFieldGlobalCounter = Math.max(...Array.from(existingCustomFields).map(btn => parseInt(btn.dataset.id))) + 1;
        } else {
            customFieldGlobalCounter = 0;
        }


        const form = document.getElementById('recruitmentPostForm');
        const livePreviewDiv = document.getElementById('live-preview');
        const rawHtmlOutputPre = document.getElementById('raw-html-output');
        const addCustomFieldButton = document.getElementById('add-custom-field');
        const customFieldsContainer = document.getElementById('custom-fields-container');
        const copyHtmlButton = document.getElementById('copy-html-button');

        // Icon mapping for list item content
        const contentIcons = {
            'eligibility': 'fas fa-clipboard-check',
            'selection': 'fas fa-cogs',
            'fees': 'fas fa-hand-holding-usd',
            'vacancies': 'fas fa-users',
            'prediction': 'fas fa-question-circle',
            'default': 'fas fa-info-circle' // Default icon for custom or unmapped fields
        };

        // Function to add a new custom field
        addCustomFieldButton.addEventListener('click', addCustomField);

        function addCustomField() {
            customFieldGlobalCounter++;
            const customFieldDiv = document.createElement('div');
            customFieldDiv.className = 'row mb-3 align-items-end';
            customFieldDiv.id = `custom-field-${customFieldGlobalCounter}`;

            customFieldDiv.innerHTML = `
                <div class="col-md-5">
                    <label for="custom_heading_${customFieldGlobalCounter}" class="form-label">Custom Heading</label>
                    <input type="text" class="form-control rounded-pill custom-field-heading" id="custom_heading_${customFieldGlobalCounter}" name="custom_heading[]" placeholder="e.g., Important Notes">
                </div>
                <div class="col-md-5">
                    <label for="custom_content_${customFieldGlobalCounter}" class="form-label">Custom Content (Multi-line)</label>
                    <textarea class="form-control rounded-3 custom-field-content" id="custom_content_${customFieldGlobalCounter}" name="custom_content[]" rows="2" placeholder="Key=Value or multi-line text"></textarea>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-danger rounded-pill w-100 remove-custom-field" data-id="${customFieldGlobalCounter}"><i class="fas fa-minus-circle"></i> Remove</button>
                </div>
            `;
            customFieldsContainer.appendChild(customFieldDiv);

            // Add event listener to the new remove button
            customFieldDiv.querySelector('.remove-custom-field').addEventListener('click', removeCustomField);

            // Update preview when a new custom field is added or changed
            customFieldDiv.querySelector('.custom-field-heading').addEventListener('input', updatePreview);
            customFieldDiv.querySelector('.custom-field-content').addEventListener('input', updatePreview);
            updatePreview(); // Call updatePreview to reflect the new empty field
        }

        // Function to remove a custom field
        function removeCustomField(event) {
            const fieldId = event.currentTarget.dataset.id;
            document.getElementById(`custom-field-${fieldId}`).remove();
            updatePreview(); // Update preview after removing a field
        }

        // Attach event listeners to existing remove buttons (for loaded data)
        document.querySelectorAll('.remove-custom-field').forEach(button => {
            button.addEventListener('click', removeCustomField);
        });


        // Live preview update on input change (for relevant fields)
        document.querySelectorAll('#recruitmentPostForm input, #recruitmentPostForm textarea').forEach(input => {
            input.addEventListener('input', updatePreview);
        });

        // Function to sanitize input using DOM methods
        function sanitizeInput(input) {
            const div = document.createElement('div');
            div.textContent = input;
            return div.innerHTML;
        }

        // Function to format date to DD-MM-YYYY
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}-${month}-${year}`;
        }

        // Function to calculate remaining days
        function getRemainingDays(lastDateString) {
            if (!lastDateString) return null;
            const today = new Date();
            const lastDate = new Date(lastDateString);
            lastDate.setHours(23, 59, 59, 999); // Set to end of day for accurate calculation

            const diffTime = lastDate.getTime() - today.getTime();
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            return diffDays >= 0 ? diffDays : null; // Return null if date has passed
        }

        // Function to convert multi-line key=value to UL LI HTML with dynamic icons
        function convertToUlLi(text, fieldIdentifier = 'default') {
            if (!text) return '';
            const lines = text.split('\n').filter(line => line.trim() !== '');
            let html = '<ul>';
            const iconClass = contentIcons[fieldIdentifier] || contentIcons['default'];

            lines.forEach(line => {
                const parts = line.split('=');
                if (parts.length > 1) {
                    html += `<li><i class="${iconClass}"></i> <strong>${sanitizeInput(parts[0].trim())}:</strong> ${sanitizeInput(parts.slice(1).join('=').trim())}</li>`;
                } else {
                    html += `<li><i class="${iconClass}"></i> ${sanitizeInput(line.trim())}</li>`;
                }
            });
            html += '</ul>';
            return html;
        }

        // Main function to update the preview and raw HTML
        function updatePreview() {
            const jobTitle = sanitizeInput(document.getElementById('job_title').value);
            const totalVacancies = sanitizeInput(document.getElementById('total_vacancies').value);
            const imageBannerUrl = sanitizeInput(document.getElementById('image_banner_url').value);

            // Convert multi-line fields to UL LI with specific icons
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

            const lastDateRaw = document.getElementById('last_date').value; // Get raw date for calculation
            const remainingDays = getRemainingDays(lastDateRaw);

            let generatedHtml = '';

            // Image Banner
            if (imageBannerUrl) {
                generatedHtml += `<img src="${imageBannerUrl}" alt="${jobTitle || 'Recruitment Banner'}" class="bronline-image-banner">`;
            }

            // Recruitment Job Style Design
            if (jobTitle || totalVacancies) {
                generatedHtml += `
                    <div class="bronline-recruitment-job-style">
                        <h1><i class="fas fa-bullhorn"></i> ${jobTitle || 'Job Post'}</h1>
                        <p><i class="fas fa-user-friends"></i> Total Vacancies: <strong>${totalVacancies || 'N/A'}</strong></p>
                    </div>
                `;
            }

            // Job Title & Total Vacancies Card
            if (jobTitle || totalVacancies) {
                generatedHtml += `
                    <div class="bronline-card-box">
                        <h3><i class="fas fa-info-circle"></i> Job Overview</h3>
                        <ul>
                            <li><i class="fas fa-briefcase"></i> <strong>Job Title:</strong> ${jobTitle || 'N/A'}</li>
                            <li><i class="fas fa-person-booth"></i> <strong>Total Vacancies:</strong> ${totalVacancies || 'N/A'}</li>
                        </ul>
                    </div>
                `;
            }

            // Important Dates Card
            if (startDate || lastDate || examDate || feePaymentLastDate) {
                generatedHtml += `
                    <div class="bronline-card-box bronline-important-dates-card">
                        <h3><i class="fas fa-calendar-alt"></i> Important Dates</h3>
                        <ul>
                            ${startDate ? `<li><i class="fas fa-play-circle"></i> <strong>Application Start Date:</strong> <span class="bronline-start-date">${startDate}</span></li>` : ''}
                            ${lastDate ? `<li><i class="fas fa-stop-circle"></i> <strong>Application Last Date:</strong> <span class="bronline-last-date">${lastDate}</span>
                                ${remainingDays !== null ? `<span class="bronline-last-date-remaining">${remainingDays} Days Left</span>` : ''}
                            </li>` : ''}
                            ${examDate ? `<li><i class="fas fa-marker"></i> <strong>Exam Date:</strong> ${examDate}</li>` : ''}
                            ${feePaymentLastDate ? `<li><i class="fas fa-credit-card"></i> <strong>Fee Payment Last Date:</strong> ${feePaymentLastDate}</li>` : ''}
                        </ul>
                    </div>
                `;
            }

            // Exam Prediction Card
            if (examPredictionList) {
                generatedHtml += `
                    <div class="bronline-card-box">
                        <h3><i class="fas fa-lightbulb"></i> Exam Prediction / Expected</h3>
                        ${examPredictionList}
                    </div>
                `;
            }

            // Eligibility Criteria Card
            if (eligibilityCriteria) {
                generatedHtml += `
                    <div class="bronline-card-box">
                        <h3><i class="fas fa-user-check"></i> Eligibility Criteria</h3>
                        ${eligibilityCriteria}
                    </div>
                `;
            }

            // Selection Process Card
            if (selectionProcess) {
                generatedHtml += `
                    <div class="bronline-card-box">
                        <h3><i class="fas fa-clipboard-list"></i> Selection Process</h3>
                        ${selectionProcess}
                    </div>
                `;
            }

            // Application Fees Card
            if (applicationFees) {
                generatedHtml += `
                    <div class="bronline-card-box">
                        <h3><i class="fas fa-money-bill-wave"></i> Application Fees</h3>
                        ${applicationFees}
                    </div>
                `;
            }

            // Category-wise Vacancies Card
            if (categoryWiseVacancies) {
                generatedHtml += `
                    <div class="bronline-card-box">
                        <h3><i class="fas fa-users"></i> Category-wise Vacancies</h3>
                        ${categoryWiseVacancies}
                    </div>
                `;
            }

            // Custom Field Blocks
            document.querySelectorAll('.custom-field-heading').forEach((headingInput, index) => {
                const contentTextarea = document.querySelectorAll('.custom-field-content')[index];
                const heading = sanitizeInput(headingInput.value);
                const content = convertToUlLi(contentTextarea.value);

                if (heading && content && content !== '<ul></ul>') {
                    generatedHtml += `
                        <div class="bronline-card-box">
                            <h3><i class="fas fa-sticky-note"></i> ${heading}</h3>
                            ${content}
                        </div>
                    `;
                } else if (heading && (content === '<ul></ul>' || !content)) {
                     generatedHtml += `
                        <div class="bronline-card-box">
                            <h3><i class="fas fa-sticky-note"></i> ${heading}</h3>
                            <p>No content provided.</p>
                        </div>
                    `;
                }
            });

            // Important Links Card
            if (notificationUrl || applyUrl || admitCardUrl || officialWebsiteUrl) {
                generatedHtml += `
                    <div class="bronline-card-box bronline-important-links-card">
                        <h3><i class="fas fa-link"></i> Important Links</h3>
                        <div>
                            ${notificationUrl ? `<a href="${notificationUrl === '#' ? 'javascript:void(0)' : notificationUrl}" target="_blank" rel="noopener noreferrer" class="bronline-link-button ${notificationUrl === '#' ? 'disabled' : ''}"><i class="fas fa-file-alt"></i> Notification</a>` : ''}
                            ${applyUrl ? `<a href="${applyUrl === '#' ? 'javascript:void(0)' : applyUrl}" target="_blank" rel="noopener noreferrer" class="bronline-link-button ${applyUrl === '#' ? 'disabled' : ''}"><i class="fas fa-external-link-alt"></i> Apply Online</a>` : ''}
                            ${admitCardUrl ? `<a href="${admitCardUrl === '#' ? 'javascript:void(0)' : admitCardUrl}" target="_blank" rel="noopener noreferrer" class="bronline-link-button ${admitCardUrl === '#' ? 'disabled' : ''}"><i class="fas fa-ticket-alt"></i> Admit Card</a>` : ''}
                            ${officialWebsiteUrl ? `<a href="${officialWebsiteUrl === '#' ? 'javascript:void(0)' : officialWebsiteUrl}" target="_blank" rel="noopener noreferrer" class="bronline-link-button ${officialWebsiteUrl === '#' ? 'disabled' : ''}"><i class="fas fa-globe"></i> Official Website</a>` : ''}
                        </div>
                    </div>
                `;
            }

            livePreviewDiv.innerHTML = generatedHtml;
            rawHtmlOutputPre.textContent = generatedHtml;
        }

        // Copy HTML functionality
        copyHtmlButton.addEventListener('click', copyHtml);

        function copyHtml() {
            const textToCopy = rawHtmlOutputPre.textContent;

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(textToCopy)
                    .then(() => showMessage('HTML copied to clipboard!'))
                    .catch(err => {
                        console.error('Failed to copy text using Clipboard API: ', err);
                        fallbackCopy(textToCopy);
                    });
            } else {
                fallbackCopy(textToCopy);
            }
        }

        function fallbackCopy(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed'; // Prevent scrolling to bottom
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            try {
                document.execCommand('copy');
                showMessage('HTML copied to clipboard (fallback)!');
            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
                showMessage('Failed to copy HTML.', 'error');
            }
            document.body.removeChild(textarea);
        }

        function showMessage(message, type = 'success') {
            const messageBox = document.createElement('div');
            messageBox.className = 'bronline-message-box';
            messageBox.textContent = message;
            if (type === 'error') {
                messageBox.style.backgroundColor = 'rgba(220, 53, 69, 0.8)';
            }
            document.body.appendChild(messageBox);

            setTimeout(() => {
                messageBox.remove();
            }, 2000); // Message disappears after 2 seconds
        }

        // Initial preview generation on load
        updatePreview();
    });
</script>
