<?php
/**
 * models/auth.php
 * Fixed: Uses 'last_activity' column correctly.
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
        $_SESSION['user_role'] = getRoleName($user['role_id']); // Helper function from roles.php
        $_SESSION['user_profile_picture'] = $user['profile_picture'];
        
        // Load Permissions
        require_once MODELS_PATH . 'roles.php';
        $_SESSION['user_permissions'] = getRolePermissions($user['role_id']);

        // Update Activity Time
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
            // FIXED: Using 'last_activity' (Standardized Name)
            // Also updating 'last_activity_at' just in case legacy code needs it
            $sql = "UPDATE users SET last_activity = NOW(), last_activity_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            // Silently fail or log error to avoid breaking the page
            error_log("Error updating user activity: " . $e->getMessage());
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

// Helper to get role name (if not loaded)
function getRoleName($roleId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT role_name FROM roles WHERE id = ?");
    $stmt->execute([$roleId]);
    return $stmt->fetchColumn() ?: 'guest';
}
?>