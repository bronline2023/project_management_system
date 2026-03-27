<?php
/**
 * models/auth.php
 * FIXED: Smart Role Detection & Activity Tracking
 */

function loginUser($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_profile_picture'] = $user['profile_picture'] ?? '';
        $_SESSION['current_portal_id'] = $user['portal_id'] ?? null;
        $_SESSION['wallet_balance'] = $user['balance'] ?? 0;
        
        // 🚀 SMART ROLE DETECTION: નવો 'role' (String) કે જૂનો 'role_id' બંને ચાલશે
        $roleStr = 'guest';
        if (!empty($user['role_id'])) {
            $roleStr = strtolower(getRoleName($user['role_id']));
        } elseif (!empty($user['role']) && !is_numeric($user['role'])) {
            $roleStr = strtolower(trim($user['role']));
        }
        $_SESSION['user_role'] = $roleStr;
        
        require_once MODELS_PATH . 'roles.php';
        
        // 🚀 SMART PERMISSION FETCHING
        $perms = [];
        try {
            $stmtRole = $pdo->prepare("SELECT permissions FROM roles WHERE role_name = ? LIMIT 1");
            $stmtRole->execute([$roleStr]);
            $permJson = $stmtRole->fetchColumn();
            if ($permJson) {
                $decoded = json_decode($permJson, true);
                if (is_array($decoded)) $perms = $decoded;
            } elseif (!empty($user['role_id'])) {
                $perms = getRolePermissions($user['role_id']);
            }
        } catch (Exception $e) {
            if (!empty($user['role_id'])) $perms = getRolePermissions($user['role_id']);
        }
        $_SESSION['user_permissions'] = $perms;

        updateUserActivity($user['id']);
        return true;
    }
    return false;
}

function updateUserActivity($userId = null) {
    global $pdo;
    if (!$userId && isset($_SESSION['user_id'])) $userId = $_SESSION['user_id'];
    if ($userId) {
        try {
            $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?")->execute([$userId]);
        } catch (Exception $e) { /* Silent ignore missing column */ }
    }
}

function isLoggedIn() { return isset($_SESSION['user_id']); }

function logoutUser() {
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_unset(); session_destroy();
    }
}

function getRoleName($roleId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT role_name FROM roles WHERE id = ?");
        $stmt->execute([$roleId]);
        $role = $stmt->fetchColumn();
        return $role ? $role : 'guest';
    } catch(Exception $e) { return 'guest'; }
}
?>