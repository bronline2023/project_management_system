<?php
require 'c:/xampp/htdocs/project_management_system/config.php';
// config.php usually defines $pdo. Let's check if it exists:
if (!isset($pdo)) {
    // If not, try to define it
    $pdo = new PDO("mysql:host=localhost;dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

echo "Table Schema b2c_subscription_plans:\n";
try {
    $stmt = $pdo->query('DESCRIBE b2c_subscription_plans');
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error describing b2c_subscription_plans: " . $e->getMessage() . "\n";
}

// Let's test an insert
try {
    $pdo->prepare("INSERT INTO b2c_subscription_plans (plan_name, description, price, validity_days, allowed_services) VALUES (?, ?, ?, ?, ?)")
        ->execute(['Test Plan', 'Test', 99.99, 30, '[]']);
    echo "\nInsert success. Last ID: " . $pdo->lastInsertId() . "\n";
} catch (Exception $e) {
    echo "\nError inserting: " . $e->getMessage() . "\n";
}
?>
