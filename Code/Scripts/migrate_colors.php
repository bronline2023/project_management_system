<?php
require_once '../../config.php';
require_once 'models/db.php';
$pdo = connectDB();

try {
    $pdo->exec("ALTER TABLE settings ADD COLUMN website_logo_size INT DEFAULT 50");
    echo "Added website_logo_size. \n";
} catch(Exception $e) {}

try {
    $pdo->exec("ALTER TABLE settings ADD COLUMN menu_color VARCHAR(50) DEFAULT ''");
    echo "Added menu_color. \n";
} catch(Exception $e) {}

try {
    $pdo->exec("ALTER TABLE settings ADD COLUMN menu_active_color VARCHAR(50) DEFAULT ''");
    echo "Added menu_active_color. \n";
} catch(Exception $e) {}
?>
