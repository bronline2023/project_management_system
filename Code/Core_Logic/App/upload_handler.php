<?php
require_once __DIR__ . '/../Config/init.php';

$portal_folder = $_SESSION['portal_folder'] ?? 'master_uploads';
$target_dir = UPLOADS_PATH . $portal_folder . "/documents/";

// Create a new folder if it doesn't exist (with security)
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
    // To prevent direct folder access by anyone other than master admin
    file_put_contents($target_dir . "index.php", "<?php echo 'Access Denied'; ?>"); 
}

$filename = uniqid() . "_" . basename($_FILES["file"]["name"]);
$target_file = $target_dir . $filename;

if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
    echo json_encode(["success" => true, "path" => $target_file]);
} else {
    echo json_encode(["success" => false, "message" => "Upload Failed"]);
}
?>