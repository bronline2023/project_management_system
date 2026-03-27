<?php
// fix_chat_db.php (Safe Version)
require_once '../../config.php';
require_once 'models/db.php';

$pdo = connectDB();

function addColumnIfNotExists($pdo, $table, $column, $definition) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
        $exists = $stmt->fetch();
        
        if (!$exists) {
            $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
            echo "✅ Added column '$column' to table '$table'.<br>";
        } else {
            echo "ℹ️ Column '$column' already exists in '$table'. (Skipped)<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Error checking/adding '$column': " . $e->getMessage() . "<br>";
    }
}

echo "<h3>Database Update Status:</h3>";

// 1. Soft Delete Columns
addColumnIfNotExists($pdo, 'messages', 'deleted_by_sender', 'TINYINT(1) DEFAULT 0');
addColumnIfNotExists($pdo, 'messages', 'deleted_by_receiver', 'TINYINT(1) DEFAULT 0');

// 2. Reply Feature
addColumnIfNotExists($pdo, 'messages', 'reply_to_id', 'INT NULL DEFAULT NULL');

// 3. Task Linking
addColumnIfNotExists($pdo, 'messages', 'related_task_id', 'INT NULL DEFAULT NULL');

// 4. Last Activity for Users
addColumnIfNotExists($pdo, 'users', 'last_activity', 'DATETIME NULL DEFAULT NULL');

echo "<hr><strong>Done! You can now use the Chat features.</strong>";
?>