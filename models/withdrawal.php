<?php
/**
 * models/withdrawal.php
 *
 * This file contains functions for managing withdrawal requests.
 * It handles database interactions for adding, retrieving, updating withdrawal requests,
 * and managing minimum withdrawal amount settings.
 */

// Ensure ROOT_PATH is defined for consistent path resolution
if (!defined('ROOT_PATH')) {
    // This file is in models/, so ROOT_PATH is one directory up
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

// Ensure db.php is included for database connection
if (!function_exists('connectDB')) {
    require_once ROOT_PATH . 'models/db.php';
}

/**
 * Adds a new withdrawal request for a DEO.
 *
 * @param int $deoId The ID of the DEO requesting withdrawal.
 * @param float $amount The amount requested for withdrawal.
 * @param array|null $paymentDetails Array containing bank_name, account_holder_name, account_number, ifsc_code, upi_id (optional, for initial submission).
 * @return bool True on success, false on failure.
 */
function addWithdrawalRequest($deoId, $amount, $paymentDetails = null) {
    $pdo = connectDB();
    try {
        $sql = "
            INSERT INTO withdrawal_requests (deo_id, amount, request_date, status, bank_name, account_holder_name, account_number, ifsc_code, upi_id)
            VALUES (:deo_id, :amount, NOW(), 'pending', :bank_name, :account_holder_name, :account_number, :ifsc_code, :upi_id)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':deo_id', $deoId, PDO::PARAM_INT);
        $stmt->bindParam(':amount', $amount, PDO::PARAM_STR); // Use STR for DECIMAL

        // Bind payment details if provided, otherwise NULL
        $stmt->bindParam(':bank_name', $paymentDetails['bank_name']);
        $stmt->bindParam(':account_holder_name', $paymentDetails['account_holder_name']);
        $stmt->bindParam(':account_number', $paymentDetails['account_number']);
        $stmt->bindParam(':ifsc_code', $paymentDetails['ifsc_code']);
        $stmt->bindParam(':upi_id', $paymentDetails['upi_id']);

        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error adding withdrawal request: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates the status of a withdrawal request.
 *
 * @param int $requestId The ID of the withdrawal request.
 * @param string $status The new status ('pending', 'processing', 'details_requested', 'paid', 'rejected').
 * @param int|null $adminId The ID of the admin processing the request (optional).
 * @param string|null $transactionNumber The transaction number if status is 'paid' (optional).
 * @param string|null $adminComments Comments from admin (optional).
 * @param array|null $paymentDetails Array containing bank_name, account_holder_name, account_number, ifsc_code, upi_id (optional, for updating details).
 * @return bool True on success, false on failure.
 */
function updateWithdrawalRequestStatus($requestId, $status, $adminId = null, $transactionNumber = null, $adminComments = null, $paymentDetails = null) {
    $pdo = connectDB();
    try {
        $sql = "
            UPDATE withdrawal_requests
            SET status = :status,
                processed_by_admin_id = :admin_id,
                processed_at = NOW(),
                transaction_number = :transaction_number,
                admin_comments = :admin_comments
        ";

        if ($paymentDetails) {
            $sql .= ", bank_name = :bank_name,
                     account_holder_name = :account_holder_name,
                     account_number = :account_number,
                     ifsc_code = :ifsc_code,
                     upi_id = :upi_id";
        }

        $sql .= " WHERE id = :request_id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
        $stmt->bindParam(':transaction_number', $transactionNumber);
        $stmt->bindParam(':admin_comments', $adminComments);

        if ($paymentDetails) {
            $stmt->bindParam(':bank_name', $paymentDetails['bank_name']);
            $stmt->bindParam(':account_holder_name', $paymentDetails['account_holder_name']);
            $stmt->bindParam(':account_number', $paymentDetails['account_number']);
            $stmt->bindParam(':ifsc_code', $paymentDetails['ifsc_code']);
            $stmt->bindParam(':upi_id', $paymentDetails['upi_id']);
        }

        $stmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error updating withdrawal request status: " . $e->getMessage());
        return false;
    }
}

/**
 * Retrieves a single withdrawal request by its ID.
 *
 * @param int $requestId The ID of the withdrawal request.
 * @return array|false The withdrawal request data as an associative array, or false if not found.
 */
function getWithdrawalRequestById($requestId) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("
            SELECT wr.*, u_deo.name AS deo_name, u_admin.name AS processed_by_admin_name
            FROM withdrawal_requests wr
            JOIN users u_deo ON wr.deo_id = u_deo.id
            LEFT JOIN users u_admin ON wr.processed_by_admin_id = u_admin.id
            WHERE wr.id = :request_id
        ");
        $stmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching withdrawal request by ID: " . $e->getMessage());
        return false;
    }
}

/**
 * Retrieves withdrawal requests for a specific DEO.
 *
 * @param int $deoId The ID of the DEO.
 * @param string $statusFilter Optional. Filter by status ('all', 'pending', 'processing', 'details_requested', 'paid', 'rejected').
 * @return array An array of withdrawal requests.
 */
function getWithdrawalRequestsByDeo($deoId, $statusFilter = 'all') {
    $pdo = connectDB();
    $sql = "
        SELECT wr.*, u_admin.name AS processed_by_admin_name
        FROM withdrawal_requests wr
        LEFT JOIN users u_admin ON wr.processed_by_admin_id = u_admin.id
        WHERE wr.deo_id = :deo_id
    ";
    $params = [':deo_id' => $deoId];

    if ($statusFilter !== 'all') {
        $sql .= " AND wr.status = :status_filter";
        $params[':status_filter'] = $statusFilter;
    }

    $sql .= " ORDER BY wr.request_date DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching withdrawal requests by DEO: " . $e->getMessage());
        return [];
    }
}

/**
 * Retrieves all withdrawal requests for admin review.
 *
 * @param string $statusFilter Optional. Filter by status ('all', 'pending', 'processing', 'details_requested', 'paid', 'rejected').
 * @return array An array of withdrawal requests.
 */
function getAllWithdrawalRequests($statusFilter = 'all') {
    $pdo = connectDB();
    $sql = "
        SELECT wr.*, u_deo.name AS deo_name, u_admin.name AS processed_by_admin_name
        FROM withdrawal_requests wr
        JOIN users u_deo ON wr.deo_id = u_deo.id
        LEFT JOIN users u_admin ON wr.processed_by_admin_id = u_admin.id
    ";
    $params = [];

    if ($statusFilter !== 'all') {
        $sql .= " WHERE wr.status = :status_filter";
        $params[':status_filter'] = $statusFilter;
    }

    $sql .= " ORDER BY wr.request_date DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching all withdrawal requests: " . $e->getMessage());
        return [];
    }
}

/**
 * Retrieves the minimum withdrawal amount from settings.
 *
 * @return float The minimum withdrawal amount.
 */
function getMinimumWithdrawalAmount() {
    $pdo = connectDB();
    try {
        $stmt = $pdo->query("SELECT minimum_withdrawal_amount FROM settings LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['minimum_withdrawal_amount'] ?? 0.00);
    } catch (PDOException $e) {
        error_log("Error fetching minimum_withdrawal_amount from settings: " . $e->getMessage());
        return 0.00; // Default to 0 if error
    }
}

/**
 * Updates the minimum withdrawal amount in settings.
 *
 * @param float $amount The new minimum withdrawal amount.
 * @return bool True on success, false on failure.
 */
function updateMinimumWithdrawalAmount($amount) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("UPDATE settings SET minimum_withdrawal_amount = :amount WHERE id = 1"); // Assuming settings has ID 1
        $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error updating minimum withdrawal amount: " . $e->getMessage());
        return false;
    }
}

/**
 * Get count of pending withdrawal requests for DEO.
 *
 * @param int $deoId The ID of the DEO.
 * @return int The count of pending requests.
 */
function getDeoPendingWithdrawalCount($deoId) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM withdrawal_requests WHERE deo_id = :deo_id AND status = 'pending'");
        $stmt->bindParam(':deo_id', $deoId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching DEO pending withdrawal count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get count of withdrawal requests requiring details from DEO.
 *
 * @param int $deoId The ID of the DEO.
 * @return int The count of requests.
 */
function getDeoDetailsRequestedWithdrawalCount($deoId) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM withdrawal_requests WHERE deo_id = :deo_id AND status = 'details_requested'");
        $stmt->bindParam(':deo_id', $deoId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching DEO details requested withdrawal count: " . $e->getMessage());
        return 0;
    }
}


// Helper function for withdrawal status badge color
if (!function_exists('getWithdrawalStatusBadgeColor')) {
    function getWithdrawalStatusBadgeColor($status) {
        switch ($status) {
            case 'pending': return 'warning';
            case 'processing': return 'info';
            case 'details_requested': return 'danger'; // Highlight for action needed from DEO
            case 'paid': return 'success';
            case 'rejected': return 'secondary';
            default: return 'secondary';
        }
    }
}

?>
