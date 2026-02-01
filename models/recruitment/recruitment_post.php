<?php
/**
 * models/recruitment/recruitment_post.php
 * UPDATED: Fixed PDO parameter binding issue by using unique placeholders for search queries.
 */

if (!function_exists('connectDB')) {
    require_once ROOT_PATH . 'models/db.php';
}

// --- Helper function for status badge color ---
if (!function_exists('getApprovalStatusBadgeColor')) {
    function getApprovalStatusBadgeColor($status) {
        switch ($status) {
            case 'pending': return 'warning';
            case 'approved': return 'success';
            case 'rejected': return 'danger';
            case 'returned_for_edit': return 'info';
            default: return 'secondary';
        }
    }
}

/**
 * Gets the count of new posts pending admin review.
 *
 * @return int The count of new pending posts.
 */
function getNewPendingPostCount() {
    $pdo = connectDB();
    try {
        return (int)fetchColumn($pdo, "SELECT COUNT(id) FROM recruitment_posts WHERE approval_status = 'pending' AND is_new_for_admin = 1");
    } catch (PDOException $e) {
        error_log("Error fetching new pending post count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Marks all new pending posts as viewed by the admin.
 *
 * @return bool True on success, false on failure.
 */
function markPendingRecruitmentPostsAsViewedByAdmin() {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("UPDATE recruitment_posts SET is_new_for_admin = 0 WHERE approval_status = 'pending' AND is_new_for_admin = 1");
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error marking pending posts as viewed: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetches the total count of all recruitment posts based on filters.
 *
 * @param string $statusFilter
 * @param string $searchQuery
 * @return int
 */
function getTotalRecruitmentPostsCount($statusFilter = 'all', $searchQuery = '') {
    $pdo = connectDB();
    $sql = "SELECT COUNT(p.id) FROM recruitment_posts p JOIN users u ON p.submitted_by_user_id = u.id";
    $params = [];
    $whereClauses = [];

    if ($statusFilter !== 'all') {
        $whereClauses[] = "p.approval_status = :status";
        $params[':status'] = $statusFilter;
    }
    if (!empty($searchQuery)) {
        // --- [ સુધારો: યુનિક પ્લેસહોલ્ડર્સનો ઉપયોગ ] ---
        $whereClauses[] = "(p.job_title LIKE :search_title OR u.name LIKE :search_name)";
        $params[':search_title'] = '%' . $searchQuery . '%';
        $params[':search_name'] = '%' . $searchQuery . '%';
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }

    return (int)fetchColumn($pdo, $sql, $params);
}

/**
 * Fetches all recruitment posts with pagination and filters.
 *
 * @param string $statusFilter
 * @param string $searchQuery
 * @param int $limit
 * @param int $offset
 * @return array
 */
function getAllRecruitmentPosts($statusFilter = 'all', $searchQuery = '', $limit = 10, $offset = 0) {
    $pdo = connectDB();
    $sql = "SELECT p.*, u.name as submitted_by_name FROM recruitment_posts p JOIN users u ON p.submitted_by_user_id = u.id";
    $params = [];
    $whereClauses = [];

    if ($statusFilter !== 'all') {
        $whereClauses[] = "p.approval_status = :status";
        $params[':status'] = $statusFilter;
    }
    if (!empty($searchQuery)) {
        // --- [ સુધારો: યુનિક પ્લેસહોલ્ડર્સનો ઉપયોગ ] ---
        $whereClauses[] = "(p.job_title LIKE :search_title OR u.name LIKE :search_name)";
        $params[':search_title'] = '%' . $searchQuery . '%';
        $params[':search_name'] = '%' . $searchQuery . '%';
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }

    $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Fetches a single recruitment post by its ID.
 *
 * @param int $postId
 * @return array|false
 */
function getRecruitmentPostById($postId) {
    $pdo = connectDB();
    return fetchOne($pdo, "SELECT * FROM recruitment_posts WHERE id = ?", [$postId]);
}


/**
 * Updates the approval status of a recruitment post.
 *
 * @param int $postId
 * @param string $newStatus
 * @param int $adminId
 * @param string|null $adminComments
 * @return bool
 */
function updateRecruitmentPostStatus($postId, $newStatus, $adminId, $adminComments = null) {
    $pdo = connectDB();
    $sql = "UPDATE recruitment_posts SET approval_status = ?, approved_by_user_id = ?, admin_comments = ?, approved_at = NOW() WHERE id = ?";
    $params = [$newStatus, $adminId, $adminComments, $postId];
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Deletes a recruitment post.
 *
 * @param int $postId
 * @return bool
 */
function deleteRecruitmentPost($postId) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("DELETE FROM recruitment_posts WHERE id = ?");
    return $stmt->execute([$postId]);
}

// --- DEO Specific Functions ---

function getDeoRecruitmentPostsCount($userId, $statusFilter = 'all', $searchQuery = '') {
    $pdo = connectDB();
    $sql = "SELECT COUNT(id) FROM recruitment_posts WHERE submitted_by_user_id = :userId";
    $params = [':userId' => $userId];
    if ($statusFilter !== 'all') {
        $sql .= " AND approval_status = :status";
        $params[':status'] = $statusFilter;
    }
    if (!empty($searchQuery)) {
        $sql .= " AND job_title LIKE :search";
        $params[':search'] = '%' . $searchQuery . '%';
    }
    return (int)fetchColumn($pdo, $sql, $params);
}


function getDeoRecruitmentPosts($userId, $statusFilter = 'all', $searchQuery = '', $limit = 10, $offset = 0) {
    $pdo = connectDB();
    $sql = "SELECT * FROM recruitment_posts WHERE submitted_by_user_id = :userId";
    $params = [':userId' => $userId];

    if ($statusFilter !== 'all') {
        $sql .= " AND approval_status = :status";
        $params[':status'] = $statusFilter;
    }
    if (!empty($searchQuery)) {
        $sql .= " AND job_title LIKE :search";
        $params[':search'] = '%' . $searchQuery . '%';
    }
    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getDeoPendingPostCount($userId) { return getDeoRecruitmentPostsCount($userId, 'pending'); }
function getDeoApprovedPostCount($userId) { return getDeoRecruitmentPostsCount($userId, 'approved'); }
function getDeoRejectedPostCount($userId) { return getDeoRecruitmentPostsCount($userId, 'rejected'); }
function getDeoReturnedForEditPostCount($userId) { return getDeoRecruitmentPostsCount($userId, 'returned_for_edit'); }

function getEarningPerApprovedPost() {
    $pdo = connectDB();
    return (float)fetchColumn($pdo, "SELECT earning_per_approved_post FROM settings LIMIT 1");
}

// Functions for adding and updating posts
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
                job_title, total_vacancies, image_banner_url, eligibility_criteria, selection_process, start_date, last_date,
                exam_date, fee_payment_last_date, application_fees, category_wise_vacancies, notification_url, apply_url,
                admit_card_url, official_website_url, exam_prediction, custom_fields_json, submitted_by_user_id,
                approval_status, is_new_for_admin, created_at, updated_at
            ) VALUES (
                :job_title, :total_vacancies, :image_banner_url, :eligibility_criteria, :selection_process, :start_date, :last_date,
                :exam_date, :fee_payment_last_date, :application_fees, :category_wise_vacancies, :notification_url, :apply_url,
                :admit_card_url, :official_website_url, :exam_prediction, :custom_fields_json, :submitted_by_user_id,
                'pending', 1, NOW(), NOW()
            )
        ");
        $stmt->execute([
            ':job_title' => $job_title, ':total_vacancies' => $total_vacancies, ':image_banner_url' => $image_banner_url,
            ':eligibility_criteria' => $eligibility_criteria, ':selection_process' => $selection_process, ':start_date' => $start_date, ':last_date' => $last_date,
            ':exam_date' => $exam_date, ':fee_payment_last_date' => $fee_payment_last_date, ':application_fees' => $application_fees, ':category_wise_vacancies' => $category_wise_vacancies,
            ':notification_url' => $notification_url, ':apply_url' => $apply_url, ':admit_card_url' => $admit_card_url,
            ':official_website_url' => $official_website_url, ':exam_prediction' => $exam_prediction, ':custom_fields_json' => $custom_fields_json,
            ':submitted_by_user_id' => $submitted_by_user_id
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Error adding recruitment post: " . $e->getMessage());
        return false;
    }
}

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
                job_title = :job_title, total_vacancies = :total_vacancies, image_banner_url = :image_banner_url,
                eligibility_criteria = :eligibility_criteria, selection_process = :selection_process,
                start_date = :start_date, last_date = :last_date, exam_date = :exam_date,
                fee_payment_last_date = :fee_payment_last_date, application_fees = :application_fees,
                category_wise_vacancies = :category_wise_vacancies, notification_url = :notification_url,
                apply_url = :apply_url, admit_card_url = :admit_card_url, official_website_url = :official_website_url,
                exam_prediction = :exam_prediction, custom_fields_json = :custom_fields_json, updated_at = NOW(),
                approval_status = 'pending', is_new_for_admin = 1, approved_by_user_id = NULL, approved_at = NULL, admin_comments = NULL
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $postId, ':job_title' => $job_title, ':total_vacancies' => $total_vacancies, ':image_banner_url' => $image_banner_url,
            ':eligibility_criteria' => $eligibility_criteria, ':selection_process' => $selection_process, ':start_date' => $start_date, ':last_date' => $last_date,
            ':exam_date' => $exam_date, ':fee_payment_last_date' => $fee_payment_last_date, ':application_fees' => $application_fees,
            ':category_wise_vacancies' => $category_wise_vacancies, ':notification_url' => $notification_url, ':apply_url' => $apply_url,
            ':admit_card_url' => $admit_card_url, ':official_website_url' => $official_website_url, ':exam_prediction' => $exam_prediction,
            ':custom_fields_json' => $custom_fields_json
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Error updating recruitment post: " . $e->getMessage());
        return false;
    }
}
?>