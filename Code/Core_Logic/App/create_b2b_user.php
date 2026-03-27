<?php
// app/create_b2b_user.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../Config/init.php';

$data = json_decode(file_get_contents("php://input"), true);

// Only Master Admin can run this file
if(!$data || ($_SESSION['user_role'] ?? '') !== 'master_admin') { 
    echo json_encode(['success'=>false, 'message'=>'Unauthorized Request']); exit; 
}

$pdo = connectDB();

try {
    // 1. Check if the email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if($stmt->fetch()) { throw new Exception("This email is already registered."); }

    // 2. Create user (balance will default to 0.00)
    $hashed_pass = password_hash($data['pass'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (role, name, email, password, status) VALUES (?, ?, ?, ?, 'active')");
    $stmt->execute([$data['role'], $data['name'], $data['email'], $hashed_pass]);

    echo json_encode(['success'=>true]);

} catch(Exception $e) {
    echo json_encode(['success'=>false, 'message'=> $e->getMessage()]);
}
?>