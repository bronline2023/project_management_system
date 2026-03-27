<?php
/**
 * user/recruitment/view_recruitment_post.php
 * Displays the full content of a recruitment post for users.
 * FINAL & COMPLETE: This page is now fully functional with the requested design.
 */

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = null;
$message = '';

if ($postId > 0) {
    require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php';
    $post = getRecruitmentPostById($postId);

    if (!$post) {
        $message = '<div class="alert alert-danger" role="alert">Post not found.</div>';
    } elseif ($post['submitted_by_user_id'] !== $currentUserId) {
        $message = '<div class="alert alert-danger" role="alert">You are not authorized to view this post.</div>';
    }
} else {
    $message = '<div class="alert alert-danger" role="alert">Invalid post ID.</div>';
}
?>

<h2 class="mb-4">View Recruitment Post</h2>

<?php if (!empty($message)): ?>
    <?php include VIEWS_PATH . 'components/message_box.php'; ?>
<?php endif; ?>

<?php if ($post): ?>
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
             <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="m-0 font-weight-bold"><i class="fas fa-bullhorn me-2"></i><?= htmlspecialchars($post['job_title']) ?></h5>
                    <a href="<?= BASE_URL ?>?page=my_recruitment_posts" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to My Posts</a>
                </div>
                <div class="card-body bg-light d-flex align-items-center justify-content-center p-3">
                    <div id="live-preview" class="p-2 poster-container design-1">
                         <?php
                        // Helper function to escape HTML
                        function escapeHtml($unsafe) {
                            if (empty($unsafe)) return '';
                            return htmlspecialchars($unsafe, ENT_QUOTES, 'UTF-8');
                        }

                        // Helper function to format date
                        function formatDateForView($dateString) {
                            if (empty($dateString) || $dateString === '0000-00-00') return 'N/A';
                            return date('d-M-Y', strtotime($dateString));
                        }
                        
                        // Helper function to convert key=value lines to HTML list with icons
                        function convertToUlLiForView($text, $fieldIdentifier = 'default') {
                            $contentIcons = ['eligibility' => 'fas fa-clipboard-check', 'selection' => 'fas fa-cogs', 'fees' => 'fas fa-hand-holding-usd', 'vacancies' => 'fas fa-users', 'prediction' => 'fas fa-question-circle', 'default' => 'fas fa-info-circle'];
                            if (empty($text)) return '';
                            // Handle various line break types
                            $lines = preg_split("/\r\n|\n|\r/", $text);
                            $html = '<ul>';
                            $iconClass = $contentIcons[$fieldIdentifier] ?? $contentIcons['default'];
                            foreach ($lines as $line) {
                                if (trim($line) !== '') {
                                    $parts = explode('=', $line, 2);
                                    if (count($parts) > 1) {
                                        $html .= '<li><i class="' . $iconClass . '"></i> <strong>' . escapeHtml(trim($parts[0])) . ':</strong> ' . escapeHtml(trim($parts[1])) . '</li>';
                                    } else {
                                        $html .= '<li><i class="' . $iconClass . '"></i> ' . escapeHtml(trim($line)) . '</li>';
                                    }
                                }
                            }
                            $html .= '</ul>';
                            return $html;
                        }
                        
                        $posterHtml = '';

                        if (!empty($post['image_banner_url'])) {
                            $posterHtml .= '<img src="' . escapeHtml($post['image_banner_url']) . '" alt="' . escapeHtml($post['job_title']) . '" class="bronline-image-banner">';
                        }

                        if (!empty($post['job_title']) || !empty($post['total_vacancies'])) {
                            $posterHtml .= '<div class="bronline-recruitment-job-style">';
                            $posterHtml .= '<h1><i class="fas fa-bullhorn"></i> ' . escapeHtml($post['job_title']) . '</h1>';
                            $posterHtml .= '<p><i class="fas fa-user-friends"></i> Total Vacancies: <strong>' . escapeHtml($post['total_vacancies']) . '</strong></p>';
                            $posterHtml .= '</div>';
                        }
                        
                        if (!empty($post['start_date']) || !empty($post['last_date']) || !empty($post['exam_date']) || !empty($post['fee_payment_last_date'])) {
                            $posterHtml .= '<div class="bronline-card-box bronline-important-dates-card">';
                            $posterHtml .= '<h3><i class="fas fa-calendar-alt"></i> Important Dates</h3>';
                            $posterHtml .= '<ul>';
                            if (!empty($post['start_date'])) {
                                $posterHtml .= '<li><i class="fas fa-play-circle"></i> <strong>Start Date:</strong> <span class="bronline-start-date">' . formatDateForView($post['start_date']) . '</span></li>';
                            }
                            if (!empty($post['last_date'])) {
                                $posterHtml .= '<li><i class="fas fa-stop-circle"></i> <strong>Last Date:</strong> <span class="bronline-last-date">' . formatDateForView($post['last_date']) . '</span></li>';
                            }
                             if (!empty($post['exam_date'])) {
                                $posterHtml .= '<li><i class="fas fa-marker"></i> <strong>Exam Date:</strong> ' . formatDateForView($post['exam_date']) . '</li>';
                            }
                             if (!empty($post['fee_payment_last_date'])) {
                                $posterHtml .= '<li><i class="fas fa-credit-card"></i> <strong>Fee Payment Last Date:</strong> ' . formatDateForView($post['fee_payment_last_date']) . '</li>';
                            }
                            $posterHtml .= '</ul></div>';
                        }

                        if (!empty($post['exam_prediction'])) {
                            $posterHtml .= '<div class="bronline-card-box"><h3><i class="fas fa-lightbulb"></i> Exam Prediction</h3>' . convertToUlLiForView($post['exam_prediction'], 'prediction') . '</div>';
                        }
                        if (!empty($post['eligibility_criteria'])) {
                            $posterHtml .= '<div class="bronline-card-box"><h3><i class="fas fa-user-check"></i> Eligibility Criteria</h3>' . convertToUlLiForView($post['eligibility_criteria']) . '</div>';
                        }
                        if (!empty($post['selection_process'])) {
                            $posterHtml .= '<div class="bronline-card-box"><h3><i class="fas fa-clipboard-list"></i> Selection Process</h3>' . convertToUlLiForView($post['selection_process'], 'selection') . '</div>';
                        }
                        if (!empty($post['application_fees'])) {
                            $posterHtml .= '<div class="bronline-card-box"><h3><i class="fas fa-money-bill-wave"></i> Application Fees</h3>' . convertToUlLiForView($post['application_fees'], 'fees') . '</div>';
                        }
                        if (!empty($post['category_wise_vacancies'])) {
                            $posterHtml .= '<div class="bronline-card-box"><h3><i class="fas fa-users"></i> Category-wise Vacancies</h3>' . convertToUlLiForView($post['category_wise_vacancies'], 'vacancies') . '</div>';
                        }
                        
                        $customFields = json_decode($post['custom_fields_json'], true);
                        if (is_array($customFields)) {
                            foreach ($customFields as $field) {
                                if (!empty($field['heading']) && !empty($field['content'])) {
                                    $posterHtml .= '<div class="bronline-card-box"><h3><i class="fas fa-sticky-note"></i> ' . escapeHtml($field['heading']) . '</h3>' . convertToUlLiForView($field['content']) . '</div>';
                                }
                            }
                        }

                        if (!empty($post['notification_url']) || !empty($post['apply_url']) || !empty($post['admit_card_url']) || !empty($post['official_website_url'])) {
                            $posterHtml .= '<div class="bronline-card-box bronline-important-links-card"><h3><i class="fas fa-link"></i> Important Links</h3><div>';
                            if (!empty($post['notification_url'])) {
                                $posterHtml .= '<a href="' . escapeHtml($post['notification_url']) . '" target="_blank" rel="noopener noreferrer" class="bronline-link-button"><i class="fas fa-file-alt"></i> Notification</a>';
                            }
                            if (!empty($post['apply_url'])) {
                                $posterHtml .= '<a href="' . escapeHtml($post['apply_url']) . '" target="_blank" rel="noopener noreferrer" class="bronline-link-button"><i class="fas fa-external-link-alt"></i> Apply Online</a>';
                            }
                            if (!empty($post['admit_card_url'])) {
                                $posterHtml .= '<a href="' . escapeHtml($post['admit_card_url']) . '" target="_blank" rel="noopener noreferrer" class="bronline-link-button"><i class="fas fa-ticket-alt"></i> Admit Card</a>';
                            }
                             if (!empty($post['official_website_url'])) {
                                $posterHtml .= '<a href="' . escapeHtml($post['official_website_url']) . '" target="_blank" rel="noopener noreferrer" class="bronline-link-button"><i class="fas fa-globe"></i> Official Website</a>';
                            }
                            $posterHtml .= '</div></div>';
                        }
                        
                        echo $posterHtml;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>