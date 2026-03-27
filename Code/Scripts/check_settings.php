<?php
require_once '../../config.php';
require_once 'models/db.php';
$pdo = connectDB();
$stmt = $pdo->query('SHOW COLUMNS FROM settings');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($columns as $col) {
    echo "- " . $col['Field'] . "\n";
}
