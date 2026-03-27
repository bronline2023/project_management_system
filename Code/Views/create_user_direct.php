<?php
// app/create_user_direct.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../Core_Logic/Config/init.php';

$data = json_decode(file_get_contents("php://input"), true);
if(!$data) { echo json_encode(['success'=>false, 'message'=>'Invalid Request']); exit; }

$name = $data['name'];
$email = $data['email'];
$password = password_hash($data['pass'], PASSWORD_DEFAULT);
$role = $data['role'];
$domain = $data['domain'];
$creator_portal_id = $data['creator_portal_id']; 

$pdo = connectDB();

try {
    $pdo->beginTransaction();

    // 1. Create a user
    $stmt = $pdo->prepare("INSERT INTO users (role, name, email, password, portal_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$role, $name, $email, $password, $creator_portal_id]);
    $new_user_id = $pdo->lastInsertId();

    // 2. If the role is Admin, also create a new portal (Subscription).
    if ($role === 'admin' && !empty($domain)) {
        $folder_name = "portal_" . $new_user_id . "_" . time();
        $expiry = date('Y-m-d', strtotime('+1 year')); // 1 year by default

        $stmt2 = $pdo->prepare("INSERT INTO portals (domain_name, owner_id, plan_id, expiry_date, folder_path) VALUES (?, ?, 1, ?, ?)");
        $stmt2->execute([$domain, $new_user_id, $expiry, $folder_name]);
        $new_portal_id = $pdo->lastInsertId();

        // Update this new portal ID in the user's record
        $pdo->prepare("UPDATE users SET portal_id = ? WHERE id = ?")->execute([$new_portal_id, $new_user_id]);

        // Create its folder on the server
        $target_dir = "../uploads/" . $folder_name;
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
    }

    $pdo->commit();
    echo json_encode(['success'=>true, 'message'=>"User created successfully!"]);

} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'message'=>"Database Error: " . $e->getMessage()]);
}
?>