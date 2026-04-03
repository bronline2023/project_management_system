<?php
/**
 * models/reports_helper.php
 * Helper functions for Financial & Usage Reports
 */

/**
 * Get Wallet Recharges (Cash deposits)
 */
function getWalletRecharges($pdo, $userId = null, $startDate = null, $endDate = null) {
    $where = "1=1";
    $params = [];
    
    if ($userId) { $where .= " AND wr.user_id = ?"; $params[] = $userId; }
    if ($startDate) { $where .= " AND wr.created_at >= ?"; $params[] = $startDate . ' 00:00:00'; }
    if ($endDate) { $where .= " AND wr.created_at <= ?"; $params[] = $endDate . ' 23:59:59'; }
    
    $sql = "SELECT wr.*, u.name as user_name, u.email as user_email 
            FROM wallet_recharge_requests wr
            JOIN users u ON wr.user_id = u.id
            WHERE $where
            ORDER BY wr.created_at DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get Point Purchases (Wallet balance used to buy reward points)
 */
function getPointPurchases($pdo, $userId = null, $startDate = null, $endDate = null) {
    $where = "type = 'debit' AND description LIKE 'Purchased % Points%'";
    $params = [];
    
    if ($userId) { $where .= " AND wt.user_id = ?"; $params[] = $userId; }
    if ($startDate) { $where .= " AND wt.created_at >= ?"; $params[] = $startDate . ' 00:00:00'; }
    if ($endDate) { $where .= " AND wt.created_at <= ?"; $params[] = $endDate . ' 23:59:59'; }
    
    $sql = "SELECT wt.*, u.name as user_name 
            FROM wallet_transactions wt
            JOIN users u ON wt.user_id = u.id
            WHERE $where
            ORDER BY wt.created_at DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get Digital Service Usage Log
 */
function getDigitalUsage($pdo, $userId = null, $startDate = null, $endDate = null) {
    $where = "is_draft = 0";
    $params = [];
    
    if ($userId) { $where .= " AND dsh.user_id = ?"; $params[] = $userId; }
    if ($startDate) { $where .= " AND dsh.created_at >= ?"; $params[] = $startDate . ' 00:00:00'; }
    if ($endDate) { $where .= " AND dsh.created_at <= ?"; $params[] = $endDate . ' 23:59:59'; }
    
    $sql = "SELECT dsh.*, u.name as user_name 
            FROM digital_service_history dsh
            JOIN users u ON dsh.user_id = u.id
            WHERE $where
            ORDER BY dsh.created_at DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
