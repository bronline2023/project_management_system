<?php
/**
 * models/db.php
 * This file handles the database connection and provides helper functions for queries.
 * FINAL: Added a generic insert() function to fix the "undefined function" error.
 */

// This check is a safeguard. If config.php wasn't included somehow, it prevents a fatal error.
if (!defined('DB_HOST')) {
    // In a real production environment, you might log this error.
    die('FATAL ERROR: The application configuration file is missing or not properly configured.');
}

function connectDB() {
    static $pdo; // Use a static variable to prevent multiple connections

    if ($pdo) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // For development, this helps in debugging.
        // For production, you should log this error and show a generic error page.
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }
}

// Helper function to fetch a single record
function fetchOne($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

// Helper function to fetch all records
function fetchAll($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Helper function to fetch a single column from a single record
function fetchColumn($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

/**
 * --- [ નવું ફંક્શન ] ---
 * Helper function to insert a record into a table.
 *
 * @param string $tableName The name of the table.
 * @param array  $data      An associative array where keys are column names and values are the data to insert.
 * @return bool True on success, false on failure.
 */
function insert($tableName, $data) {
    $pdo = connectDB();
    // Creates string of column names: `name`, `email`, `password`
    $columns = implode(', ', array_keys($data));
    // Creates string of placeholders: :name, :email, :password
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO {$tableName} ({$columns}) VALUES ({$placeholders})";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($data);
}
?>