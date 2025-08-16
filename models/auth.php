<?php
/**
 * models/auth.php
 *
 * This file contains authentication-related functions.
 * It handles user login, logout, and session management.
 */

// Ensure session is started before using $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if a user is currently logged in.
 *
 * @return bool True if a user is logged in, false otherwise.
 */
function isLoggedIn() {
    $loggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && isset($_SESSION['user_role']);
    error_log("DEBUG: auth.php - isLoggedIn() called. Session ID: " . session_id() . ", User ID: " . ($_SESSION['user_id'] ?? 'N/A') . ", User Role: " . ($_SESSION['user_role'] ?? 'N/A') . ", Result: " . ($loggedIn ? 'true' : 'false'));
    return $loggedIn;
}

/**
 * Attempts to log in a user with the provided email and password.
 *
 * @param string $email The user's email address.
 * @param string $password The user's plain-text password.
 * @return bool True on successful login, false otherwise.
 */
function loginUser($email, $password) {
    $pdo = connectDB(); // Assuming connectDB() is available and returns a PDO object
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            session_regenerate_id(true); // Regenerate session ID to prevent session fixation attacks
            error_log("User logged in successfully: ID=" . $user['id'] . ", Email=" . $user['email'] . ", Role=" . $user['role']);
            return true;
        } else {
            error_log("Login failed for email: " . $email . " - Invalid credentials.");
            return false;
        }
    } catch (PDOException $e) {
        error_log("Database error during login: " . $e->getMessage());
        return false;
    }
}

/**
 * Logs out the current user by destroying the session.
 */
function logoutUser() {
    // Unset all of the session variables
    $_SESSION = array();

    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finally, destroy the session.
    session_destroy();
    error_log("User logged out. Session destroyed.");
}
?>
