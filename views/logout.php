<?php
/**
 * views/logout.php
 * Handles the user logout process.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}

require_once MODELS_PATH . 'auth.php'; // Include auth model to use logoutUser() function

logoutUser(); // Call the robust logout function

header('Location: ' . BASE_URL . '?page=login');
exit;
?>