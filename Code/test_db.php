<?php
require 'c:/xampp/htdocs/project_management_system/Code/config.php';
require 'c:/xampp/htdocs/project_management_system/Code/Models/db.php';
$pdo = connectDB();
try {
    $stmt = $pdo->query('SELECT * FROM digital_service_rates');
    echo "digital_service_rates EXISTS\n";
} catch(Exception $e) {
    echo "ERROR digital_service_rates: " . $e->getMessage() . "\n";
}
try {
    $stmt = $pdo->query('SELECT poster_points FROM users LIMIT 1');
    echo "poster_points in users EXISTS\n";
} catch(Exception $e) {
    echo "ERROR users.poster_points: " . $e->getMessage() . "\n";
}
