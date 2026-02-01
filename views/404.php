<?php
/**
 * views/404.php
 *
 * This file serves as the "Page Not Found" error page.
 * It is displayed when a user tries to access a URL that does not map to
 * a valid route or file within the application.
 */

// This script is typically called directly by index.php or a web server rule,
// which should have already set the 404 header.
// http_response_code(404); // This is now handled in index.php

if (!defined('BASE_URL')) {
    // If accessed directly, load config
    require_once __DIR__ . '/../config.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found - Project Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .container-404 {
            max-width: 600px;
            padding: 40px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .container-404 h1 {
            font-size: 6em;
            color: #dc3545;
            font-weight: 700;
        }
        .container-404 h2 {
            font-size: 2.5em;
            color: #343a40;
            margin-bottom: 15px;
        }
        .container-404 p {
            font-size: 1.1em;
            color: #6c757d;
            margin-bottom: 30px;
        }
        .btn-primary {
            border-radius: 50px;
            padding: 12px 25px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container-404">
        <h1><i class="fas fa-exclamation-triangle"></i> 404</h1>
        <h2>Page Not Found</h2>
        <p>Sorry, the page you are looking for does not exist or you do not have permission to access it.</p>
        <a href="<?= BASE_URL ?>?page=dashboard" class="btn btn-primary">
            <i class="fas fa-home me-2"></i>Go to Dashboard
        </a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>