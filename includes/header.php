<?php
/**
 * views/includes/header.php
 *
 * This file contains the common HTML <head> section and the beginning of the <body>
 * for all pages in the Project Management System.
 * It includes:
 * - Meta tags for character set and viewport.
 * - Page title (dynamic if passed, otherwise default).
 * - Bootstrap 5 CSS.
 * - Font Awesome for icons.
 * - Google Fonts (Inter).
 * - Link to custom CSS file.
 *
 * It is included by all other view files to maintain consistency.
 */

// Ensure BASE_URL is defined (from config.php)
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config.php';
}

// Get the current page title from $_GET['page'] or default
$page_title_raw = isset($_GET['page']) ? str_replace('_', ' ', $_GET['page']) : 'Home';
$page_title = ucwords($page_title_raw);

// Fetch app name from settings if available
$app_name = 'Project Management System';
try {
    $pdo = connectDB(); // Assuming connectDB() is available via config or included files
    $stmt = $pdo->query("SELECT app_name FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings && isset($settings['app_name'])) {
        $app_name = htmlspecialchars($settings['app_name']);
    }
} catch (PDOException $e) {
    error_log("Error fetching app name in header: " . $e->getMessage());
    // Fallback to default app_name
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= $app_name ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6hA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= time() ?>">
    <style>
        /* General layout for sidebar and content */
        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
            min-height: 100vh;
        }

        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: #343a40; /* Dark background */
            color: #fff;
            transition: all 0.3s;
        }

        #sidebar.active {
            margin-left: -250px;
        }

        #content {
            width: 100%;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
        }

        /* Sidebar styling */
        #sidebar .sidebar-header {
            padding: 20px;
            background: #212529; /* Even darker for header */
            text-align: center;
        }

        #sidebar ul.components {
            padding: 20px 0;
            border-bottom: 1px solid #47748b;
        }

        #sidebar ul li a {
            padding: 10px;
            font-size: 1.1em;
            display: block;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 50px; /* Pill shape for menu items */
            margin: 5px 15px; /* Spacing */
        }
        #sidebar ul li a:hover {
            color: #fff;
            background: #0d6efd; /* Primary blue on hover */
            text-decoration: none;
            transform: translateX(5px); /* Slight slide effect */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        #sidebar ul li.active > a, a[aria-expanded="true"] {
            color: #fff;
            background: #0d6efd; /* Primary blue for active item */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* Dropdown menu within sidebar */
        #sidebar ul li a i {
            margin-right: 10px;
        }
        #sidebar ul li a[data-bs-toggle="collapse"]::after {
            display: block;
            position: absolute;
            right: 20px;
            content: '\f107'; /* Font Awesome chevron-down icon */
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            transition: all 0.3s;
        }
        #sidebar ul li a[aria-expanded="true"]::after {
            transform: rotate(-180deg);
        }
        ul ul a {
            font-size: 0.9em !important;
            padding-left: 30px !important;
            background: #495057; /* Slightly lighter dark for sub-items */
            border-radius: 0; /* No pill for sub-items */
            margin: 0;
        }
        ul ul a:hover {
             background: #0d6efd; /* Primary blue on hover for sub-items */
        }
        ul.collapse.show > li > a {
            border-radius: 0; /* Ensure no border-radius for open sub-items */
        }

        /* Toggle button */
        #sidebarCollapse {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            cursor: pointer;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: none; /* Hidden by default, show on smaller screens */
            align-items: center;
            justify-content: center;
        }
        #sidebarCollapse i {
            color: #343a40;
        }

        /* Media queries for responsiveness */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            #sidebar.active {
                margin-left: 0;
            }
            #content {
                width: 100%;
            }
            #sidebarCollapse {
                display: flex; /* Show toggle button on mobile */
            }
            /* Adjust content padding when sidebar is open on mobile */
            body.sidebar-open #content {
                padding-left: 280px; /* Adjust as needed for sidebar width + spacing */
            }
        }
        .logo-text {
            font-weight: 700;
            font-size: 1.5rem;
            color: #fff;
        }
        .logo-img {
            max-width: 100%;
            height: auto;
            max-height: 50px; /* Adjust as needed */
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div id="sidebarCollapse" class="btn btn-light rounded-circle shadow-sm">
        <i class="fas fa-bars"></i>
    </div>
