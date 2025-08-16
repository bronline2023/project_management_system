<?php
/**
 * views/logout.php
 *
 * This file handles the user logout process.
 * It destroys the session and redirects the user to the login page.
 */

// Start the session (important to access session variables)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Include config.php to get BASE_URL for redirection
// Use __DIR__ to ensure the path is always relative to the current file.
require_once __DIR__ . '/../config.php'; 

// Redirect to the login page
// Ensure BASE_URL is correctly defined in your config.php
header('Location: ' . BASE_URL . '?page=login');
exit;
?>
