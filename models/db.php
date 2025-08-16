<?php
/**
 * models/db.php
 *
 * This file contains database connection functions and helper functions
 * for interacting with the database.
 */

// Ensure configuration is loaded
if (!defined('DB_HOST')) {
    // This path assumes db.php is in models/ and config.php is in the root.
    require_once __DIR__ . '/../config.php';
}

/**
 * Establishes a PDO database connection.
 * @return PDO - A PDO database connection object.
 * @throws PDOException if the connection fails.
 */
function connectDB() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        error_log("DEBUG: Attempting to connect to database...");
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        error_log("DEBUG: Database connection successful.");
        return $pdo;
    } catch (PDOException $e) {
        // Log the error message (e.g., to a file or system logger)
        error_log("Database Connection Error in connectDB(): " . $e->getMessage() . " (DSN: $dsn, User: " . DB_USER . ")");
        // In a production environment, you might want to show a generic error page
        // or message instead of the raw PDOException message.
        die("Database connection failed. Please try again later. (Error: " . $e->getMessage() . ")");
    }
}

/**
 * Executes a prepared statement and fetches a single row.
 * Useful for fetching user details, settings, etc.
 * @param PDO $pdo - The PDO database connection object.
 * @param string $sql - The SQL query to execute.
 * @param array $params - Optional array of parameters for the prepared statement.
 * @return array|false - The fetched row as an associative array, or false on failure/no rows.
 */
function fetchOne(PDO $pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("DB Helper Error (fetchOne): " . $e->getMessage() . " for SQL: " . $sql);
        return false;
    }
}

/**
 * Executes a prepared statement and fetches all rows.
 * Useful for fetching lists of users, tasks, etc.
 * @param PDO $pdo - The PDO database connection object.
 * @param string $sql - The SQL query to execute.
 * @param array $params - Optional array of parameters for the prepared statement.
 * @return array - An array of fetched rows, or an empty array on failure/no rows.
 */
function fetchAll(PDO $pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("DB Helper Error (fetchAll): " . $e->getMessage() . " for SQL: " . $sql);
        return [];
    }
}

/**
 * Executes a prepared statement and returns the value of a single column in the next row.
 * Useful for COUNT(*), SUM(), or fetching a single specific value.
 * @param PDO $pdo - The PDO database connection object.
 * @param string $sql - The SQL query to execute.
 * @param array $params - Optional array of parameters for the prepared statement.
 * @param int $columnNumber - The 0-indexed number of the column to retrieve. Default is 0 (first column).
 * @return mixed|false - The column value, or false on failure/no rows.
 */
function fetchColumn(PDO $pdo, $sql, $params = [], $columnNumber = 0) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn($columnNumber);
    } catch (PDOException $e) {
        error_log("DB Helper Error (fetchColumn): " . $e->getMessage() . " for SQL: " . $sql);
        return false;
    }
}


/**
 * Fetches unread message count for a given user.
 * Uses `read_status` column.
 * @param PDO $pdo - The PDO database connection object.
 * @param int $userId - The ID of the user whose unread messages are to be counted.
 * @return int - The number of unread messages.
 */
function getUnreadMessageCount(PDO $pdo, $userId) {
    try {
        // Assuming read_status = 0 means unread
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = :userId AND read_status = 0");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching unread message count for user $userId (using read_status): " . $e->getMessage());
        return 0; // Return 0 on error
    }
}

/**
 * Marks all unread messages for a given user as read.
 * Updates `read_status` to 1 and `read_at` timestamp.
 * @param PDO $pdo - The PDO database connection object.
 * @param int $userId - The ID of the user whose messages are to be marked as read.
 * @return bool - True on success, false on failure.
 */
function markMessagesAsRead(PDO $pdo, $userId) {
    try {
        // Update read_status to 1 and set read_at timestamp for unread messages
        $stmt = $pdo->prepare("UPDATE messages SET read_status = 1, read_at = NOW() WHERE receiver_id = :userId AND read_status = 0");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $rowCount = $stmt->rowCount();
        error_log("DEBUG: Messages for user $userId marked as read (read_status=1, read_at=NOW()). Rows affected: " . $rowCount);
        return true;
    } catch (PDOException $e) {
        error_log("Error marking messages as read for user $userId (using read_status, read_at): " . $e->getMessage());
        return false;
    }
}

?>
