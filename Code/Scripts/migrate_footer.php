<?php
require_once '../../config.php';
require_once 'models/db.php';
$pdo = connectDB();

$columnsToAdd = [
    'footer_about_text' => "TEXT NULL",
    'social_facebook' => "VARCHAR(255) NULL",
    'social_twitter' => "VARCHAR(255) NULL",
    'social_instagram' => "VARCHAR(255) NULL",
    'social_linkedin' => "VARCHAR(255) NULL",
    'social_youtube' => "VARCHAR(255) NULL",
    'contact_email_public' => "VARCHAR(255) NULL",
    'footer_copyright' => "VARCHAR(255) NULL"
];

foreach ($columnsToAdd as $col => $def) {
    try {
        $pdo->exec("ALTER TABLE settings ADD COLUMN $col $def");
        echo "Successfully added $col\n";
    } catch (PDOException $e) {
        // SQLSTATE 42S21 means Duplicate column name
        if ($e->getCode() == '42S21') {
            echo "Column $col already exists.\n";
        } else {
            echo "Error adding $col: " . $e->getMessage() . "\n";
        }
    }
}

// Seed the initial data based on what's hardcoded right now
$defaults = [
    'footer_about_text' => 'Your one-stop destination for all digital online services. Fast, secure, and reliable.',
    'footer_copyright' => '2026 BR Online Services - Portal. All Rights Reserved.',
    'contact_email_public' => 'support@localhost'
];

$updateSql = "UPDATE settings SET footer_about_text = ?, footer_copyright = ?, contact_email_public = ? WHERE id = 1";
$stmt = $pdo->prepare($updateSql);
$stmt->execute([
    $defaults['footer_about_text'],
    $defaults['footer_copyright'],
    $defaults['contact_email_public']
]);
echo "Successfully seeded default footer strings.\n";
?>
