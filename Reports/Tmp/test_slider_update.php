<?php
require 'config.php';
require 'models/db.php';
$pdo = connectDB();

echo "Testing Slider Update...\n";
$id = 1; // Try to update the first slider
$title = "Test Update " . time();
$desc = "Test Desc";
$link = "#";
$mediaType = "image";
$status = "active";
$display_order = 1;

$params = [$title, $desc, $link, $mediaType, $status, $display_order, $id];
$stmt = $pdo->prepare("UPDATE b2c_sliders SET title=?, description=?, link=?, media_type=?, status=?, display_order=? WHERE id=?");
$result = $stmt->execute($params);

if($result) {
    echo "SUCCESS: Slider updated!\n";
} else {
    echo "FAILURE: ". print_r($stmt->errorInfo(), true) . "\n";
}
?>
