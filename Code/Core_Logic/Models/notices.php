<?php
/**
 * models/notices.php
 * Handles creation and fetching of targeted and global notices.
 */

if (!function_exists('connectDB')) { require_once __DIR__ . '/db.php'; }

function createNotice($title, $message, $target_type = 'all', $target_user_id = null, $created_by = null) {
    $pdo = connectDB();
    $sql = "INSERT INTO notices (title, message, target_type, target_user_id, created_by) VALUES (?, ?, ?, ?, ?)";
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$title, $message, $target_type, $target_user_id, $created_by]);
    } catch (PDOException $e) {
        error_log("Error creating notice: " . $e->getMessage());
        return false;
    }
}

function getActiveNotices($userId = null) {
    $pdo = connectDB();
    $sql = "SELECT n.*, u.name as author FROM notices n 
            LEFT JOIN users u ON n.created_by = u.id 
            WHERE n.is_active = 1 
            AND (n.target_type = 'all' OR n.target_user_id = ?) 
            ORDER BY n.created_at DESC";
    try {
        return fetchAll($pdo, $sql, [$userId]);
    } catch (PDOException $e) {
        return [];
    }
}

function getAllNotices() {
    $pdo = connectDB();
    return fetchAll($pdo, "SELECT n.*, u.name as author, t.name as target_name 
                           FROM notices n 
                           LEFT JOIN users u ON n.created_by = u.id 
                           LEFT JOIN users t ON n.target_user_id = t.id 
                           ORDER BY n.created_at DESC");
}

function deleteNotice($id) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("DELETE FROM notices WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

function toggleNoticeStatus($id, $status) {
    $pdo = connectDB();
    try {
        $stmt = $pdo->prepare("UPDATE notices SET is_active = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    } catch (PDOException $e) {
        return false;
    }
}
