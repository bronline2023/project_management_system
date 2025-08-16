<?php
/**
 * views/includes/header.php
 *
 * This file contains the HTML <head> section and the start of the <body>.
 * It includes meta tags, title, CSS stylesheets (Bootstrap, Font Awesome, custom),
 * and sets up the basic structure for all pages.
 * It also ensures session is started and config is loaded.
 */

// Ensure config.php is loaded for constants like BASE_URL, APP_NAME, etc.
// This file is in views/includes, so ROOT_PATH is two directories up.
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR);
}
require_once ROOT_PATH . 'config.php';

// Start session if not already started (redundant if index.php handles it, but safe)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get app name and logo URL from settings (constants defined in config.php)
$app_name = APP_NAME;
$app_logo_url = ''; // Default empty, fetched from settings if available
try {
    $pdo_header = connectDB(); // Use a separate PDO instance for config to avoid conflicts
    $stmt_header = $pdo_header->query("SELECT app_logo_url FROM settings LIMIT 1");
    $settings_header = $stmt_header->fetch(PDO::FETCH_ASSOC);
    if ($settings_header && !empty($settings_header['app_logo_url'])) {
        $app_logo_url = htmlspecialchars($settings_header['app_logo_url']);
    }
} catch (PDOException $e) {
    error_log("Error fetching app logo in header.php: " . $e->getMessage());
    // Continue with default empty logo URL
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $app_name ?></title>

    <!-- Favicon (optional, using app logo if available) -->
    <?php if (!empty($app_logo_url)): ?>
        <link rel="icon" href="<?= $app_logo_url ?>" type="image/x-icon">
    <?php else: ?>
        <!-- Default favicon if no logo is set -->
        <link rel="icon" href="<?= ASSETS_URL ?>images/favicon.ico" type="image/x-icon">
    <?php endif; ?>


    <!-- Bootstrap CSS (v5.3.3) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Font Awesome CSS (v6.4.0) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" xintegrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdgNsGaKxVbQtdn-K/fQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Google Fonts - Poppins (for general text) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS for the sidebar and overall layout -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css">

    <!-- jQuery (required for some Bootstrap components and custom JS) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

</head>
<body>
    <!-- The main content and sidebar will be included here by other PHP files -->
