<?php
/**
 * models/auth.php
 * FIXED: 
 * 1. Converts Role Name to lowercase (Fixes login redirection issues).
 * 2. Handles 'last_activity' update safely without crashing.
 */

// Function to login user
function loginUser($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Set Session Variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_profile_picture'] = $user['profile_picture'];
        
        // --- CRITICAL FIX: Convert Role to lowercase ---
        // This ensures 'Freelancer' matches 'freelancer' in actions.php
        $_SESSION['user_role'] = strtolower(getRoleName($user['role_id'])); 
        
        // Load Permissions
        require_once MODELS_PATH . 'roles.php';
        $_SESSION['user_permissions'] = getRolePermissions($user['role_id']);

        // Update Activity (Safe Mode)
        updateUserActivity($user['id']);

        return true;
    }
    return false;
}

// Function to update last activity timestamp
function updateUserActivity($userId = null) {
    global $pdo;
    if (!$userId && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }

    if ($userId) {
        try {
            // Only update 'last_activity' which we created correctly
            $sql = "UPDATE users SET last_activity = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            // If error occurs (like column missing), simply log it and DO NOT stop login
            error_log("Activity Update Error: " . $e->getMessage());
        }
    }
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to logout
function logoutUser() {
    session_unset();
    session_destroy();
}

// Helper to get role name
function getRoleName($roleId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT role_name FROM roles WHERE id = ?");
    $stmt->execute([$roleId]);
    $role = $stmt->fetchColumn();
    return $role ? $role : 'guest';
}
?>