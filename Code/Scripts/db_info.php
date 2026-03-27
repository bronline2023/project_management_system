<?php
require_once '../../config.php';
require_once 'models/db.php';
$pdo = connectDB();
$tables = ['users', 'roles', 'custom_pages', 'settings'];
foreach($tables as $t) {
    echo "TABLE: $t\n";
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$t`");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['Field']} ({$row['Type']})\n";
        }
    } catch(Exception $e) {
        echo "Table not found.\n";
    }
    echo "\n";
}
