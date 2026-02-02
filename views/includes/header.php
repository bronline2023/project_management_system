<?php
/**
 * views/includes/header.php
 * FIXED: Standard Flex Layout to prevent overlapping.
 */
?>
<!doctype html>
<html lang="en">
<head>
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Portal' ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css"> 
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/poster_styles.css">

    <style>
        /* --- Layout Structure --- */
        body {
            overflow-x: hidden;
            background: #f4f7f6;
        }

        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        /* --- Sidebar Desktop --- */
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: #343a40;
            color: #fff;
            transition: all 0.3s;
            height: 100vh;
            position: sticky;
            top: 0;
            overflow-y: auto;
        }

        /* --- Content Area --- */
        #content {
            width: 100%;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
        }

        /* --- Mobile Adjustments --- */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
                position: fixed;
                z-index: 1000;
            }
            #sidebar.active {
                margin-left: 0;
            }
            #content {
                padding: 15px;
            }
            .overlay {
                display: none;
                position: fixed;
                width: 100vw;
                height: 100vh;
                background: rgba(0,0,0,0.7);
                z-index: 999;
                top: 0;
                left: 0;
            }
            .overlay.active {
                display: block;
            }
        }

        .digital-clock {
            font-weight: bold;
            color: #0d6efd;
            font-family: monospace;
        }
    </style>
</head>
<body>
<div class="overlay"></div>
<div class="wrapper"> ```

---

### **2. `views/includes/sidebar.php` (Structure Fix)**
સાઇડબારમાં `dismiss` બટન ઉમેર્યું છે જે માત્ર મોબાઇલમાં દેખાશે.

```php
<nav id="sidebar">
    <div class="sidebar-header p-3 border-bottom border-secondary position-relative">
        <h3 class="m-0 fs-5"><?= htmlspecialchars($settings['app_name'] ?? 'Freelancer') ?></h3>
        <button id="dismiss" class="btn btn-sm btn-dark position-absolute top-50 end-0 translate-middle-y d-md-none me-2">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <ul class="list-unstyled components p-2">
        </ul>
</nav>