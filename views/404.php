<?php
/**
 * views/404.php
 *
 * This file serves as the "Page Not Found" error page.
 * It is displayed when a user tries to access a URL that does not map to
 * a valid route or file within the application.
 */

// Include the configuration file for BASE_URL constant
require_once __DIR__ . '/../config.php';

// Set the HTTP response status code to 404 (Not Found)
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found - Project Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6hA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e0e5ec 100%); /* Light gradient */
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #333;
            text-align: center;
        }
        .container-404 {
            max-width: 600px;
            padding: 40px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: fadeInScale 0.8s ease-out;
        }
        .container-404 h1 {
            font-size: 6em;
            color: #dc3545; /* Danger red */
            margin-bottom: 20px;
            font-weight: 700;
            text-shadow: 2px 2px 5px rgba(0,0,0,0.1);
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
            background-color: #0d6efd;
            border-color: #0d6efd;
            border-radius: 50px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0a58ca;
            border-color: #0a58ca;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="container-404">
        <h1><i class="fas fa-exclamation-circle"></i> 404</h1>
        <h2>Page Not Found</h2>
        <p>Oops! The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
        <a href="<?= BASE_URL ?>?page=home" class="btn btn-primary">
            <i class="fas fa-home me-2"></i>Go to Homepage
        </a>
    </div>

    <!-- Bootstrap 5 JS (popper.js included) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
