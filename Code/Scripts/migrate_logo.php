<?php
require_once '../../config.php';
require_once 'models/db.php';
$pdo = connectDB();

try {
    $pdo->exec("ALTER TABLE settings ADD COLUMN website_logo_url VARCHAR(255) DEFAULT NULL");
    echo "Added website_logo_url. \n";
} catch(Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $pdo->exec("ALTER TABLE settings ADD COLUMN header_style VARCHAR(50) DEFAULT 'style1'");
    echo "Added header_style. \n";
} catch(Exception $e) { echo $e->getMessage() . "\n"; }
?>
