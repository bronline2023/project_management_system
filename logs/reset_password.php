<?php
require_once __DIR__ . '/config.php';
require_once MODELS_PATH . 'db.php';
$pdo = connectDB();
try {
    $new_password = 'admin_123'; // તમારો નવો પાસવર્ડ અહીં સેટ કરો
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@bronline.net'");
    $stmt->execute([$hashed_password]);
    echo "Admin password has been reset to: admin_123";
} catch (PDOException $e) {
    echo "Error resetting password: " . $e->getMessage();
}
?>