<?php
/**
 * models/auth.php
 * This file contains authentication-related functions.
 * FINAL & COMPLETE: Prevents inactive users from logging in and ensures session is started correctly.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/roles.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function updateUserActivity() {
    if (isLoggedIn()) {
        $pdo = connectDB();
        try {
            $stmt = $pdo->prepare("UPDATE users SET last_activity_at = NOW() WHERE id = :user_id");
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating user activity: " . $e->getMessage());
        }
    }
}
updateUserActivity();

function loginUser($email, $password) {
    $pdo = connectDB();
    try {
        // [FIX] Added status = 'active' to the query
        $stmt = $pdo->prepare("SELECT u.id, u.name, u.email, u.password, u.role_id, u.profile_picture, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.email = :email AND u.status = 'active'");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if (empty($user['role_id'])) {
                error_log("Login failed for email: " . $email . " - User has no assigned role.");
                return false;
            }

            $permissions = getRolePermissions($user['role_id']);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['user_role'] = strtolower($user['role_name'] ?? 'guest');
            $_SESSION['user_permissions'] = $permissions;
            $_SESSION['user_profile_picture'] = !empty($user['profile_picture']) ? $user['profile_picture'] : '';


            session_regenerate_id(true);
            updateUserActivity();
            return true;
        } else {
            error_log("Login failed for email: " . $email . " - Invalid credentials or inactive user.");
            return false;
        }
    } catch (PDOException $e) {
        error_log("Database error during login: " . $e->getMessage());
        return false;
    }
}

function logoutUser() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
?>