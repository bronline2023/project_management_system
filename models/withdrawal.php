<?php
/**
 * models/withdrawal.php
 * FINAL & COMPLETE VERSION:
 * - The updateWithdrawalStatus function now accepts and saves a transaction ID.
 * - All necessary functions for managing withdrawals are included.
 */

if (!function_exists('connectDB')) {
    require_once ROOT_PATH . 'models/db.php';
}

if (!function_exists('getWithdrawalStatusBadgeColor')) {
    function getWithdrawalStatusBadgeColor($status) {
        switch ($status) {
            case 'pending': return 'warning';
            case 'approved': return 'success';
            case 'rejected': return 'danger';
            default: return 'secondary';
        }
    }
}

// ... (addWithdrawalRequest, getApprovedWithdrawalAmountForUser, etc. functions remain the same) ...
function addWithdrawalRequest($userId, $amount, $bankDetailsJson) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO withdrawals (user_id, amount, bank_details_json, status, requested_at)
            VALUES (:user_id, :amount, :bank_details_json, 'pending', NOW())
        ");
        return $stmt->execute([
            ':user_id' => $userId,
            ':amount' => $amount,
            ':bank_details_json' => $bankDetailsJson
        ]);
    } catch (PDOException $e) {
        error_log("Error adding withdrawal request: " . $e->getMessage());
        return false;
    }
}
function getApprovedWithdrawalAmountForUser($userId) {
    $pdo = connectDB();
    return (float)fetchColumn($pdo, "SELECT SUM(amount) FROM withdrawals WHERE user_id = ? AND status = 'approved'", [$userId]) ?? 0.00;
}
function getPendingWithdrawalAmountForUser($userId) {
    $pdo = connectDB();
    return (float)fetchColumn($pdo, "SELECT SUM(amount) FROM withdrawals WHERE user_id = ? AND status = 'pending'", [$userId]) ?? 0.00;
}

function getUserWithdrawalRequests($userId) {
    $pdo = connectDB();
    $sql = "
        SELECT w.*, u_proc.name AS processed_by_name
        FROM withdrawals w
        LEFT JOIN users u_proc ON w.processed_by_user_id = u_proc.id
        WHERE w.user_id = :user_id
        ORDER BY w.requested_at DESC
    ";
    return fetchAll($pdo, $sql, [':user_id' => $userId]);
}

function getAllWithdrawalRequests($statusFilter = 'all') {
    $pdo = connectDB();
    $sql = "
        SELECT w.*, u.name AS user_name, u.email AS user_email
        FROM withdrawals w
        JOIN users u ON w.user_id = u.id
    ";
    $params = [];
    if ($statusFilter !== 'all') {
        $sql .= " WHERE w.status = :status_filter";
        $params[':status_filter'] = $statusFilter;
    }
    $sql .= " ORDER BY w.requested_at DESC";
    return fetchAll($pdo, $sql, $params);
}

/**
 * --- [ સુધારેલું ફંક્શન: ટ્રાન્ઝેક્શન ID ઉમેર્યું છે ] ---
 * Updates the status of a withdrawal request.
 */
function updateWithdrawalStatus($withdrawalId, $newStatus, $processedByUserId, $adminComments = null, $transactionId = null) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("
            UPDATE withdrawals
            SET status = :new_status,
                processed_by_user_id = :processed_by_user_id,
                processed_at = NOW(),
                admin_comments = :admin_comments,
                transaction_id = :transaction_id
            WHERE id = :id
        ");
        return $stmt->execute([
            ':new_status' => $newStatus,
            ':processed_by_user_id' => $processedByUserId,
            ':admin_comments' => $adminComments,
            ':transaction_id' => ($newStatus === 'approved') ? $transactionId : null, // Only save transaction ID on approval
            ':id' => $withdrawalId
        ]);
    } catch (PDOException $e) {
        error_log("Error updating withdrawal status: " . $e->getMessage());
        return false;
    }
}

?>