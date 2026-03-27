<?php
/**
 * models/recruitment/recruitment_post.php
 * FULL FIX: Added missing 'getApprovalStatusBadgeColor' function.
 */

if (!function_exists('connectDB')) { require_once MODELS_PATH . 'db.php'; }

// આ ફંક્શન ગાયબ થઈ ગયું હતું, જે હવે ઉમેરી દેવામાં આવ્યું છે
if (!function_exists('getApprovalStatusBadgeColor')) {
    function getApprovalStatusBadgeColor($status) {
        switch ($status) {
            case 'pending': return 'warning text-dark';
            case 'approved': return 'success';
            case 'rejected': return 'danger';
            case 'returned_for_edit': return 'info text-dark';
            default: return 'secondary';
        }
    }
}

function getRecruitmentPostById($postId) {
    $pdo = connectDB();
    return fetchOne($pdo, "SELECT * FROM recruitment_posts WHERE id = ?", [$postId]);
}

function getNewPendingPostCount() {
    $pdo = connectDB();
    try { return (int)fetchColumn($pdo, "SELECT COUNT(id) FROM recruitment_posts WHERE approval_status = 'pending' AND is_new_for_admin = 1"); } catch (Exception $e) { return 0; }
}

function markPendingRecruitmentPostsAsViewedByAdmin() {
    $pdo = connectDB();
    try { return $pdo->prepare("UPDATE recruitment_posts SET is_new_for_admin = 0 WHERE approval_status = 'pending' AND is_new_for_admin = 1")->execute(); } catch (Exception $e) { return false; }
}

function getTotalRecruitmentPostsCount($statusFilter = 'all', $searchQuery = '') {
    $pdo = connectDB();
    $sql = "SELECT COUNT(p.id) FROM recruitment_posts p JOIN users u ON p.submitted_by_user_id = u.id";
    $params = []; $whereClauses = [];
    if ($statusFilter !== 'all') { $whereClauses[] = "p.approval_status = :status"; $params[':status'] = $statusFilter; }
    if (!empty($searchQuery)) { $whereClauses[] = "(p.job_title LIKE :search_title OR u.name LIKE :search_name)"; $params[':search_title'] = '%' . $searchQuery . '%'; $params[':search_name'] = '%' . $searchQuery . '%'; }
    if (!empty($whereClauses)) { $sql .= " WHERE " . implode(' AND ', $whereClauses); }
    return (int)fetchColumn($pdo, $sql, $params);
}

function getAllRecruitmentPosts($statusFilter = 'all', $searchQuery = '', $limit = 10, $offset = 0) {
    $pdo = connectDB();
    $sql = "SELECT p.*, u.name as submitted_by_name FROM recruitment_posts p JOIN users u ON p.submitted_by_user_id = u.id";
    $params = []; $whereClauses = [];
    if ($statusFilter !== 'all') { $whereClauses[] = "p.approval_status = :status"; $params[':status'] = $statusFilter; }
    if (!empty($searchQuery)) { $whereClauses[] = "(p.job_title LIKE :search_title OR u.name LIKE :search_name)"; $params[':search_title'] = '%' . $searchQuery . '%'; $params[':search_name'] = '%' . $searchQuery . '%'; }
    if (!empty($whereClauses)) { $sql .= " WHERE " . implode(' AND ', $whereClauses); }
    $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit; $params[':offset'] = $offset;
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => &$val) { $stmt->bindParam($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR); }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateRecruitmentPostStatus($postId, $newStatus, $adminId, $adminComments = null) {
    $pdo = connectDB();
    return $pdo->prepare("UPDATE recruitment_posts SET approval_status = ?, approved_by_user_id = ?, admin_comments = ?, approved_at = NOW() WHERE id = ?")->execute([$newStatus, $adminId, $adminComments, $postId]);
}

function deleteRecruitmentPost($postId) {
    $pdo = connectDB();
    return $pdo->prepare("DELETE FROM recruitment_posts WHERE id = ?")->execute([$postId]);
}

function getDeoRecruitmentPostsCount($userId, $statusFilter = 'all', $searchQuery = '') {
    $pdo = connectDB();
    $sql = "SELECT COUNT(id) FROM recruitment_posts WHERE submitted_by_user_id = :userId";
    $params = [':userId' => $userId];
    if ($statusFilter !== 'all') { $sql .= " AND approval_status = :status"; $params[':status'] = $statusFilter; }
    if (!empty($searchQuery)) { $sql .= " AND job_title LIKE :search"; $params[':search'] = '%' . $searchQuery . '%'; }
    return (int)fetchColumn($pdo, $sql, $params);
}

function getDeoRecruitmentPosts($userId, $statusFilter = 'all', $searchQuery = '', $limit = 10, $offset = 0) {
    $pdo = connectDB();
    $sql = "SELECT * FROM recruitment_posts WHERE submitted_by_user_id = :userId";
    $params = [':userId' => $userId];
    if ($statusFilter !== 'all') { $sql .= " AND approval_status = :status"; $params[':status'] = $statusFilter; }
    if (!empty($searchQuery)) { $sql .= " AND job_title LIKE :search"; $params[':search'] = '%' . $searchQuery . '%'; }
    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit; $params[':offset'] = $offset;
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => &$val) { $stmt->bindParam($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR); }
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

function addRecruitmentPostHtml(
    $job_title, $total_vacancies, $image_banner_url,
    $eligibility_criteria, $selection_process, $start_date, $last_date,
    $exam_date, $fee_payment_last_date, $application_fees, $category_wise_vacancies,
    $notification_url, $apply_url, $admit_card_url, $official_website_url,
    $exam_prediction, $custom_fields_json, $submitted_by_user_id,
    $custom_links_json, $age_limit, $other_details_json, $custom_dates_json
) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO recruitment_posts (
                job_title, total_vacancies, image_banner_url, eligibility_criteria, selection_process,
                age_limit, other_details_json, custom_dates_json,
                start_date, last_date, exam_date, fee_payment_last_date, application_fees, category_wise_vacancies, 
                notification_url, apply_url, admit_card_url, official_website_url, exam_prediction, 
                custom_fields_json, custom_links_json, submitted_by_user_id, approval_status, is_new_for_admin, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 1, NOW(), NOW()
            )
        ");
        return $stmt->execute([
            $job_title, $total_vacancies, $image_banner_url, $eligibility_criteria, $selection_process,
            $age_limit, $other_details_json, $custom_dates_json,
            $start_date, $last_date, $exam_date, $fee_payment_last_date, $application_fees, $category_wise_vacancies,
            $notification_url, $apply_url, $admit_card_url, $official_website_url, $exam_prediction, 
            $custom_fields_json, $custom_links_json, $submitted_by_user_id
        ]);
    } catch (PDOException $e) { error_log("Error adding post: " . $e->getMessage()); return false; }
}

function updateRecruitmentPostHtml(
    $postId, $job_title, $total_vacancies, $image_banner_url,
    $eligibility_criteria, $selection_process, $start_date, $last_date,
    $exam_date, $fee_payment_last_date, $application_fees, $category_wise_vacancies,
    $notification_url, $apply_url, $admit_card_url, $official_website_url,
    $exam_prediction, $custom_fields_json, $custom_links_json,
    $age_limit, $other_details_json, $custom_dates_json
) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("
            UPDATE recruitment_posts SET
                job_title=?, total_vacancies=?, image_banner_url=?, eligibility_criteria=?, selection_process=?,
                age_limit=?, other_details_json=?, custom_dates_json=?,
                start_date=?, last_date=?, exam_date=?, fee_payment_last_date=?, application_fees=?, 
                category_wise_vacancies=?, notification_url=?, apply_url=?, admit_card_url=?, official_website_url=?,
                exam_prediction=?, custom_fields_json=?, custom_links_json=?,
                updated_at=NOW(), approval_status='pending', is_new_for_admin=1, approved_by_user_id=NULL, approved_at=NULL, admin_comments=NULL
            WHERE id=?
        ");
        return $stmt->execute([
            $job_title, $total_vacancies, $image_banner_url, $eligibility_criteria, $selection_process,
            $age_limit, $other_details_json, $custom_dates_json,
            $start_date, $last_date, $exam_date, $fee_payment_last_date, $application_fees,
            $category_wise_vacancies, $notification_url, $apply_url, $admit_card_url, $official_website_url,
            $exam_prediction, $custom_fields_json, $custom_links_json, $postId
        ]);
    } catch (PDOException $e) { error_log("Error updating post: " . $e->getMessage()); return false; }
}
?>