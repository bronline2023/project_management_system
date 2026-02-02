<?php
/**
 * views/includes/header.php
 * FIXED: Added Mobile Sidebar CSS & Overlay Styles
 */
?>
<!doctype html>
<html lang="en">
<head>
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Freelancer Portal' ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css"> 
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/poster_styles.css">

    <style>
        /* --- DIGITAL CLOCK --- */
        .digital-clock {
            font-family: 'Courier New', Courier, monospace;
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 5px;
            color: #333;
            font-weight: bold;
            font-size: 1rem;
            display: inline-block;
        }

        /* --- MOBILE SIDEBAR LOGIC --- */
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            min-height: 100vh;
            transition: all 0.3s;
        }

        /* Mobile specific styles */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px; /* Hide by default on mobile */
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                z-index: 9999;
                background: #fff; /* Ensure background is set */
            }
            #sidebar.active {
                margin-left: 0; /* Show when active */
                box-shadow: 2px 0 5px rgba(0,0,0,0.5);
            }
            
            /* Overlay Background */
            .overlay {
                display: none;
                position: fixed;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.7);
                z-index: 9998;
                opacity: 0;
                transition: all 0.5s ease-in-out;
                top: 0;
                left: 0;
            }
            .overlay.active {
                display: block;
                opacity: 1;
            }
            
            /* Show Hamburger on Mobile */
            #sidebarCollapse {
                display: inline-block !important;
            }
        }
    </style>
</head>
<body>
<div class="overlay"></div>