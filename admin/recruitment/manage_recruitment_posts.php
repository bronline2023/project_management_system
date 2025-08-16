<?php
/**
 * admin/recruitment/manage_recruitment_posts.php
 *
 * This file allows administrators to manage recruitment posts submitted by DEOs.
 * It provides functionalities to view, approve, reject, and delete posts.
 *
 * It ensures that only authenticated admin users can access this page.
 */

// Ensure ROOT_PATH is defined and config.php is included.
// This file is located at ROOT_PATH/admin/recruitment/
// So, ROOT_PATH is two directories up from here.
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
}
require_once ROOT_PATH . 'config.php'; // Corrected path to config.php

// Now, other includes can safely use constants from config.php
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';
require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php'; // IMPORTANT: This line includes the functions

// Restrict access to Admin users only.
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';

// Handle status update (Approve/Reject/Return for Edit)
if (isset($_POST['update_status'])) {
    $postId = (int)$_POST['post_id'];
    $newStatus = $_POST['new_status'];
    $adminComments = $_POST['admin_comments'] ?? null; // Get admin comments for 'returned_for_edit'

    if (in_array($newStatus, ['approved', 'rejected', 'returned_for_edit'])) {
        if (updateRecruitmentPostStatus($postId, $newStatus, $currentUserId, $adminComments)) {
            $message = '<div class="alert alert-success" role="alert">Recruitment post status updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger" role="alert">Failed to update recruitment post status.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger" role="alert">Invalid status provided.</div>';
    }
}

// Handle deletion
if (isset($_POST['delete_post'])) {
    $postId = (int)$_POST['post_id'];
    if (deleteRecruitmentPost($postId)) {
        $message = '<div class="alert alert-success" role="alert">Recruitment post deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger" role="alert">Failed to delete recruitment post.</div>';
    }
}

$filterStatus = $_GET['status'] ?? 'all'; // 'all', 'pending', 'approved', 'rejected', 'returned_for_edit'

// Fetch all recruitment posts based on filter
$recruitmentPosts = getAllRecruitmentPosts($filterStatus);

include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Manage Recruitment Posts</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; ?>
            <script>
                setupAutoHideAlerts();
            </script>
        <?php endif; ?>

        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Posts</h5>
            </div>
            <div class="card-body">
                <form action="" method="GET" class="row g-3 align-items-center">
                    <input type="hidden" name="page" value="manage_recruitment_posts">
                    <div class="col-md-4">
                        <label for="statusFilter" class="form-label visually-hidden">Filter by Status</label>
                        <select class="form-select rounded-pill" id="statusFilter" name="status" onchange="this.form.submit()">
                            <option value="all" <?= ($filterStatus === 'all') ? 'selected' : '' ?>>All Statuses</option>
                            <option value="pending" <?= ($filterStatus === 'pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= ($filterStatus === 'approved') ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= ($filterStatus === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                            <option value="returned_for_edit" <?= ($filterStatus === 'returned_for_edit') ? 'selected' : '' ?>>Returned for Edit</option>
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Apply Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm rounded-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Recruitment Posts</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recruitmentPosts)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Job Title</th>
                                    <th>Vacancies</th>
                                    <th>Submitted By</th>
                                    <th>Submitted At</th>
                                    <th>Status</th>
                                    <th>Approved By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recruitmentPosts as $post): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($post['id']) ?></td>
                                        <td><?= htmlspecialchars($post['job_title']) ?></td>
                                        <td><?= htmlspecialchars($post['total_vacancies']) ?></td>
                                        <td><?= htmlspecialchars($post['submitted_by_name'] ?? 'N/A') ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getApprovalStatusBadgeColor($post['approval_status']) ?>">
                                                <?= ucwords(htmlspecialchars(str_replace('_', ' ', $post['approval_status']))) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($post['approved_by_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-info rounded-pill me-1 view-post-btn" data-bs-toggle="modal" data-bs-target="#viewPostModal" data-post='<?= json_encode($post) ?>' title="View Details">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <?php if ($post['approval_status'] === 'pending'): ?>
                                                <form action="" method="POST" class="d-inline">
                                                    <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id']) ?>">
                                                    <input type="hidden" name="new_status" value="approved">
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-outline-success rounded-pill me-1" title="Approve Post">
                                                        <i class="fas fa-check-circle"></i> Approve
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-outline-warning rounded-pill me-1 return-for-edit-btn" data-bs-toggle="modal" data-bs-target="#returnForEditModal" data-post-id="<?= htmlspecialchars($post['id']) ?>" title="Return for Edit">
                                                    <i class="fas fa-undo"></i> Return for Edit
                                                </button>
                                                <form action="" method="POST" class="d-inline">
                                                    <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id']) ?>">
                                                    <input type="hidden" name="new_status" value="rejected">
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-outline-danger rounded-pill me-1" title="Reject Post">
                                                        <i class="fas fa-times-circle"></i> Reject
                                                    </button>
                                                </form>
                                            <?php elseif ($post['approval_status'] === 'approved'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning rounded-pill me-1 return-for-edit-btn" data-bs-toggle="modal" data-bs-target="#returnForEditModal" data-post-id="<?= htmlspecialchars($post['id']) ?>" title="Send Back for Edit">
                                                    <i class="fas fa-undo"></i> Send Back for Edit
                                                </button>
                                            <?php endif; ?>
                                            <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                                <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id']) ?>">
                                                <button type="submit" name="delete_post" class="btn btn-sm btn-outline-danger rounded-pill" title="Delete Post">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No recruitment posts found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- View Post Modal -->
<div class="modal fade" id="viewPostModal" tabindex="-1" aria-labelledby="viewPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-info text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="viewPostModalLabel"><i class="fas fa-bullhorn me-2"></i>Recruitment Post Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="post-details-content">
                    <!-- Post details and HTML preview will be loaded here by JS -->
                    <div id="modal-live-preview" class="border p-3 rounded-3 bg-light mb-4"></div>
                    <h5 class="mt-4 mb-3">Raw HTML for Blogger:</h5>
                    <pre id="modal-raw-html-output" class="bg-dark text-white p-3 rounded-3 overflow-auto" style="max-height: 300px;"></pre>
                    <button type="button" id="modal-copy-html-button" class="btn btn-success rounded-pill mt-3"><i class="fas fa-copy"></i> Copy HTML</button>
                </div>
            </div>
            <div class="modal-footer border-0 rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Return for Edit Modal -->
<div class="modal fade" id="returnForEditModal" tabindex="-1" aria-labelledby="returnForEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-warning text-dark border-0 rounded-top-4">
                <h5 class="modal-title" id="returnForEditModalLabel"><i class="fas fa-undo me-2"></i>Return Post for Edit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="post_id" id="returnPostId">
                    <input type="hidden" name="new_status" value="returned_for_edit">
                    <div class="mb-3">
                        <label for="adminComments" class="form-label">Admin Comments (Reason for return)</label>
                        <textarea class="form-control rounded-3" id="adminComments" name="admin_comments" rows="4" required placeholder="Please provide detailed reasons for returning this post for edit."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-warning rounded-pill">Send Back</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>

<script>
    // Icon mapping for list item content - Global for modal and main form
    const contentIcons = {
        'eligibility': 'fas fa-clipboard-check',
        'selection': 'fas fa-cogs',
        'fees': 'fas fa-hand-holding-usd',
        'vacancies': 'fas fa-users',
        'prediction': 'fas fa-question-circle',
        'default': 'fas fa-info-circle' // Default icon for custom or unmapped fields
    };

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

    // Main function to generate the HTML for preview
    function generatePostHtml(postData) {
        const jobTitle = sanitizeInput(postData.job_title || '');
        const totalVacancies = sanitizeInput(postData.total_vacancies || '');
        const imageBannerUrl = sanitizeInput(postData.image_banner_url || '');

        const eligibilityCriteria = convertToUlLi(postData.eligibility_criteria || '', 'eligibility');
        const selectionProcess = convertToUlLi(postData.selection_process || '', 'selection');
        const applicationFees = convertToUlLi(postData.application_fees || '', 'fees');
        const categoryWiseVacancies = convertToUlLi(postData.category_wise_vacancies || '', 'vacancies');
        const examPredictionList = convertToUlLi(postData.exam_prediction || '', 'prediction');

        const startDate = formatDate(postData.start_date || '');
        const lastDate = formatDate(postData.last_date || '');
        const examDate = formatDate(postData.exam_date || '');
        const feePaymentLastDate = formatDate(postData.fee_payment_last_date || '');

        const notificationUrl = sanitizeInput(postData.notification_url || '');
        const applyUrl = sanitizeInput(postData.apply_url || '');
        const admitCardUrl = sanitizeInput(postData.admit_card_url || '');
        const officialWebsiteUrl = sanitizeInput(postData.official_website_url || '');

        const lastDateRaw = postData.last_date || '';
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
        if (postData.custom_fields_json) {
            try {
                const customFields = JSON.parse(postData.custom_fields_json);
                customFields.forEach(field => {
                    const heading = sanitizeInput(field.heading || '');
                    const content = convertToUlLi(field.content || '');
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
            } catch (e) {
                console.error("Error parsing custom_fields_json:", e);
            }
        }


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
        return generatedHtml;
    }


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

        // Modal specific JS for Admin's manage_recruitment_posts.php
        const viewPostModal = document.getElementById('viewPostModal');
        if (viewPostModal) {
            viewPostModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // Button that triggered the modal
                const postData = JSON.parse(button.getAttribute('data-post'));

                const modalLivePreview = viewPostModal.querySelector('#modal-live-preview');
                const modalRawHtmlOutput = viewPostModal.querySelector('#modal-raw-html-output');
                const modalCopyHtmlButton = viewPostModal.querySelector('#modal-copy-html-button');

                const generatedHtml = generatePostHtml(postData); // Use the shared function

                modalLivePreview.innerHTML = generatedHtml;
                modalRawHtmlOutput.textContent = generatedHtml;

                // Set up copy button for modal
                modalCopyHtmlButton.onclick = function() {
                    const textToCopy = modalRawHtmlOutput.textContent;
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
                };
            });
        }

        // Return for Edit Modal JS
        const returnForEditModal = document.getElementById('returnForEditModal');
        if (returnForEditModal) {
            returnForEditModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // Button that triggered the modal
                const postId = button.getAttribute('data-post-id');
                const returnPostIdInput = returnForEditModal.querySelector('#returnPostId');
                returnPostIdInput.value = postId;
            });
        }
    });
</script>
