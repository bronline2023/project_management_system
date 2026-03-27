<?php
// app/manual_recharge.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../Config/init.php';

$data = json_decode(file_get_contents("php://input"), true);
if(!$data || !in_array($_SESSION['user_role'] ?? '', ['master_admin', 'admin'])) { 
    echo json_encode(['success'=>false, 'message'=>'Unauthorized']); exit; 
}

$amount = floatval($data['amount']);
$target_user_id = intval($data['user_id']);

$pdo = connectDB();

try {
    $pdo->beginTransaction();

    // 1. Increase user's balance (using 'balance' column)
    $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$amount, $target_user_id]);

    // 2. Record the transaction (in your wallet_transactions table)
    $desc = "Manual Recharge by Admin";
    $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, 'credit', ?, ?)");
    $stmt->execute([$target_user_id, $amount, $desc]);

    $pdo->commit();
    echo json_encode(['success'=>true]);

} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
?>