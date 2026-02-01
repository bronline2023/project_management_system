<?php
/**
 * views/includes/header.php
 * FINAL & COMPLETE: Correctly uses the ASSETS_URL constant to link stylesheets and scripts,
 * fixing all design and layout issues.
 */
?>
<!doctype html>
<html lang="en">
<head>
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : htmlspecialchars(APP_NAME) ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800,900" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css"> 
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/poster_styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
</head>
<body>