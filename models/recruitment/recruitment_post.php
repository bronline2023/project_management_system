<?php
/**
 * models/recruitment/recruitment_post.php
 *
 * This file contains functions for managing recruitment posts.
 * It handles database interactions for adding, retrieving, updating, and deleting recruitment posts.
 */

// Ensure db.php is included for database connection
if (!function_exists('connectDB')) {
    require_once ROOT_PATH . 'models/db.php';
}

/**
 * Adds a new recruitment post to the database with all new HTML fields.
 *
 * @param string $job_title
 * @param int $total_vacancies
 * @param string $image_banner_url
 * @param string $eligibility_criteria
 * @param string $selection_process
 * @param string $start_date
 * @param string $last_date
 * @param string $exam_date
 * @param string $fee_payment_last_date
 * @param string $application_fees
 * @param string $category_wise_vacancies
 * @param string $notification_url
 * @param string $apply_url
 * @param string $admit_card_url
 * @param string $official_website_url
 * @param string $exam_prediction
 * @param string $custom_fields_json JSON string of custom fields
 * @param int $submitted_by_user_id
 * @return bool True on success, false on failure.
 */
function addRecruitmentPostHtml(
    $job_title, $total_vacancies, $image_banner_url,
    $eligibility_criteria, $selection_process, $start_date, $last_date,
    $exam_date, $fee_payment_last_date, $application_fees, $category_wise_vacancies,
    $notification_url, $apply_url, $admit_card_url, $official_website_url,
    $exam_prediction, $custom_fields_json, $submitted_by_user_id
) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO recruitment_posts (
                job_title, total_vacancies, image_banner_url,
                eligibility_criteria, selection_process, start_date, last_date,
                exam_date, fee_payment_last_date, application_fees, category_wise_vacancies,
                notification_url, apply_url, admit_card_url, official_website_url,
                exam_prediction, custom_fields_json, submitted_by_user_id,
                approval_status, created_at, updated_at
            ) VALUES (
                :job_title, :total_vacancies, :image_banner_url,
                :eligibility_criteria, :selection_process, :start_date, :last_date,
                :exam_date, :fee_payment_last_date, :application_fees, :category_wise_vacancies,
                :notification_url, :apply_url, :admit_card_url, :official_website_url,
                :exam_prediction, :custom_fields_json, :submitted_by_user_id,
                'pending', NOW(), NOW()
            )
        ");

        $stmt->bindParam(':job_title', $job_title);
        $stmt->bindParam(':total_vacancies', $total_vacancies, PDO::PARAM_INT);
        $stmt->bindParam(':image_banner_url', $image_banner_url);
        $stmt->bindParam(':eligibility_criteria', $eligibility_criteria);
        $stmt->bindParam(':selection_process', $selection_process);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':last_date', $last_date);
        $stmt->bindParam(':exam_date', $exam_date);
        $stmt->bindParam(':fee_payment_last_date', $fee_payment_last_date);
        $stmt->bindParam(':application_fees', $application_fees);
        $stmt->bindParam(':category_wise_vacancies', $category_wise_vacancies);
        $stmt->bindParam(':notification_url', $notification_url);
        $stmt->bindParam(':apply_url', $apply_url);
        $stmt->bindParam(':admit_card_url', $admit_card_url);
        $stmt->bindParam(':official_website_url', $official_website_url);
        $stmt->bindParam(':exam_prediction', $exam_prediction);
        $stmt->bindParam(':custom_fields_json', $custom_fields_json);
        $stmt->bindParam(':submitted_by_user_id', $submitted_by_user_id, PDO::PARAM_INT);

        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error adding recruitment post (HTML version): " . $e->getMessage());
        return false;
    }
}

/**
 * Updates an existing recruitment post in the database with all new HTML fields.
 *
 * @param int $postId
 * @param string $job_title
 * @param int $total_vacancies
 * @param string $image_banner_url
 * @param string $eligibility_criteria
 * @param string $selection_process
 * @param string $start_date
 * @param string $last_date
 * @param string $exam_date
 * @param string $fee_payment_last_date
 * @param string $application_fees
 * @param string $category_wise_vacancies
 * @param string $notification_url
 * @param string $apply_url
 * @param string $admit_card_url
 * @param string $official_website_url
 * @param string $exam_prediction
 * @param string $custom_fields_json JSON string of custom fields
 * @return bool True on success, false on failure.
 */
function updateRecruitmentPostHtml(
    $postId, $job_title, $total_vacancies, $image_banner_url,
    $eligibility_criteria, $selection_process, $start_date, $last_date,
    $exam_date, $fee_payment_last_date, $application_fees, $category_wise_vacancies,
    $notification_url, $apply_url, $admit_card_url, $official_website_url,
    $exam_prediction, $custom_fields_json
) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("
            UPDATE recruitment_posts SET
                job_title = :job_title,
                total_vacancies = :total_vacancies,
                image_banner_url = :image_banner_url,
                eligibility_criteria = :eligibility_criteria,
                selection_process = :selection_process,
                start_date = :start_date,
                last_date = :last_date,
                exam_date = :exam_date,
                fee_payment_last_date = :fee_payment_last_date,
                application_fees = :application_fees,
                category_wise_vacancies = :category_wise_vacancies,
                notification_url = :notification_url,
                apply_url = :apply_url,
                admit_card_url = :admit_card_url,
                official_website_url = :official_website_url,
                exam_prediction = :exam_prediction,
                custom_fields_json = :custom_fields_json,
                updated_at = NOW(),
                -- When a DEO edits a post, its status should revert to 'pending'
                approval_status = 'pending',
                approved_by_user_id = NULL,
                approved_at = NULL,
                admin_comments = NULL -- Clear admin comments on re-submission
            WHERE id = :id
        ");

        $stmt->bindParam(':job_title', $job_title);
        $stmt->bindParam(':total_vacancies', $total_vacancies, PDO::PARAM_INT);
        $stmt->bindParam(':image_banner_url', $image_banner_url);
        $stmt->bindParam(':eligibility_criteria', $eligibility_criteria);
        $stmt->bindParam(':selection_process', $selection_process);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':last_date', $last_date);
        $stmt->bindParam(':exam_date', $exam_date);
        $stmt->bindParam(':fee_payment_last_date', $fee_payment_last_date);
        $stmt->bindParam(':application_fees', $application_fees);
        $stmt->bindParam(':category_wise_vacancies', $category_wise_vacancies);
        $stmt->bindParam(':notification_url', $notification_url);
        $stmt->bindParam(':apply_url', $apply_url);
        $stmt->bindParam(':admit_card_url', $admit_card_url);
        $stmt->bindParam(':official_website_url', $official_website_url);
        $stmt->bindParam(':exam_prediction', $exam_prediction);
        $stmt->bindParam(':custom_fields_json', $custom_fields_json);
        $stmt->bindParam(':id', $postId, PDO::PARAM_INT);

        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error updating recruitment post (HTML version): " . $e->getMessage());
        return false;
    }
}

/**
 * Retrieves a single recruitment post by its ID.
 *
 * @param int $postId The ID of the recruitment post.
 * @return array|false The recruitment post data as an associative array, or false if not found.
 */
function getRecruitmentPostById($postId) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("
            SELECT rp.*, u.name AS submitted_by_name, ua.name AS approved_by_name
            FROM recruitment_posts rp
            LEFT JOIN users u ON rp.submitted_by_user_id = u.id
            LEFT JOIN users ua ON rp.approved_by_user_id = ua.id
            WHERE rp.id = :id
        ");
        $stmt->bindParam(':id', $postId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching recruitment post by ID: " . $e->getMessage());
        return false;
    }
}

/**
 * Retrieves recruitment posts submitted by a specific DEO.
 *
 * @param int $userId The ID of the Data Entry Operator.
 * @param string $statusFilter Optional. Filter by approval status ('all', 'pending', 'approved', 'rejected', 'returned_for_edit').
 * @return array An array of recruitment posts.
 */
function getDeoRecruitmentPosts($userId, $statusFilter = 'all') {
    $pdo = connectDB();
    $sql = "
        SELECT rp.*, u.name AS submitted_by_name, ua.name AS approved_by_name
        FROM recruitment_posts rp
        LEFT JOIN users u ON rp.submitted_by_user_id = u.id
        LEFT JOIN users ua ON rp.approved_by_user_id = ua.id
        WHERE rp.submitted_by_user_id = :user_id
    ";
    $params = [':user_id' => $userId];

    if ($statusFilter !== 'all') {
        $sql .= " AND rp.approval_status = :status_filter";
        $params[':status_filter'] = $statusFilter;
    }

    $sql .= " ORDER BY rp.created_at DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching DEO recruitment posts: " . $e->getMessage());
        return [];
    }
}

/**
 * Retrieves all recruitment posts for admin review.
 *
 * @param string $statusFilter Optional. Filter by approval status ('all', 'pending', 'approved', 'rejected', 'returned_for_edit').
 * @return array An array of recruitment posts.
 */
function getAllRecruitmentPosts($statusFilter = 'all') {
    $pdo = connectDB();
    $sql = "
        SELECT rp.*, u.name AS submitted_by_name, ua.name AS approved_by_name
        FROM recruitment_posts rp
        LEFT JOIN users u ON rp.submitted_by_user_id = u.id
        LEFT JOIN users ua ON rp.approved_by_user_id = ua.id
    ";
    $params = [];

    if ($statusFilter !== 'all') {
        $sql .= " WHERE rp.approval_status = :status_filter";
        $params[':status_filter'] = $statusFilter;
    }

    $sql .= " ORDER BY rp.created_at DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching all recruitment posts: " . $e->getMessage());
        return [];
    }
}

/**
 * Updates the approval status of a recruitment post.
 *
 * @param int $postId The ID of the recruitment post.
 * @param string $status The new status ('pending', 'approved', 'rejected', 'returned_for_edit').
 * @param int $approvedByUserId The ID of the admin/user who approved/rejected/returned the post.
 * @param string|null $adminComments Optional comments from admin when returning for edit.
 * @return bool True on success, false on failure.
 */
function updateRecruitmentPostStatus($postId, $status, $approvedByUserId, $adminComments = null) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("
            UPDATE recruitment_posts
            SET approval_status = :approval_status,
                approved_by_user_id = :approved_by_user_id,
                approved_at = NOW(),
                admin_comments = :admin_comments
            WHERE id = :id
        ");
        $stmt->bindParam(':approval_status', $status);
        $stmt->bindParam(':approved_by_user_id', $approvedByUserId, PDO::PARAM_INT);
        $stmt->bindParam(':admin_comments', $adminComments); // Bind admin comments
        $stmt->bindParam(':id', $postId, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error updating recruitment post status: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes a recruitment post.
 *
 * @param int $postId The ID of the recruitment post to delete.
 * @return bool True on success, false on failure.
 */
function deleteRecruitmentPost($postId) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("DELETE FROM recruitment_posts WHERE id = :id");
        $stmt->bindParam(':id', $postId, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error deleting recruitment post: " . $e->getMessage());
        return false;
    }
}

/**
 * Get the earning per approved post from settings.
 *
 * @return float The earning amount.
 */
function getEarningPerApprovedPost() {
    $pdo = connectDB();
    try {
        $stmt = $pdo->query("SELECT earning_per_approved_post FROM settings LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['earning_per_approved_post'] ?? 0.00);
    } catch (PDOException $e) {
        error_log("Error fetching earning_per_approved_post from settings: " . $e->getMessage());
        return 0.00;
    }
}

/**
 * Get count of approved posts for a specific DEO.
 *
 * @param int $userId The ID of the Data Entry Operator.
 * @return int The count of approved posts.
 */
function getDeoApprovedPostCount($userId) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM recruitment_posts WHERE submitted_by_user_id = :user_id AND approval_status = 'approved'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching DEO approved post count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get count of pending posts for a specific DEO.
 *
 * @param int $userId The ID of the Data Entry Operator.
 * @return int The count of pending posts.
 */
function getDeoPendingPostCount($userId) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM recruitment_posts WHERE submitted_by_user_id = :user_id AND approval_status = 'pending'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching DEO pending post count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get count of rejected posts for a specific DEO.
 *
 * @param int $userId The ID of the Data Entry Operator.
 * @return int The count of rejected posts.
 */
function getDeoRejectedPostCount($userId) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM recruitment_posts WHERE submitted_by_user_id = :user_id AND approval_status = 'rejected'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching DEO rejected post count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get count of posts returned for edit for a specific DEO.
 *
 * @param int $userId The ID of the Data Entry Operator.
 * @return int The count of posts returned for edit.
 */
function getDeoReturnedForEditPostCount($userId) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM recruitment_posts WHERE submitted_by_user_id = :user_id AND approval_status = 'returned_for_edit'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching DEO returned for edit post count: " . $e->getMessage());
        return 0;
    }
}


// Helper function for approval status badge color
if (!function_exists('getApprovalStatusBadgeColor')) {
    function getApprovalStatusBadgeColor($status) {
        switch ($status) {
            case 'pending': return 'warning';
            case 'approved': return 'success';
            case 'rejected': return 'danger';
            case 'returned_for_edit': return 'info'; // New color for returned status
            default: return 'secondary';
        }
    }
}
?>
