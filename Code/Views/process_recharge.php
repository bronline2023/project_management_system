<?php
// app/process_recharge.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../Core_Logic/Config/init.php';

$data = json_decode(file_get_contents("php://input"), true);
if(!$data || empty($_SESSION['user_id'])) { echo json_encode(['success'=>false, 'message'=>'Invalid Request']); exit; }

$amount = floatval($data['amount']);
$payment_id = $data['payment_id'] ?? 'MANUAL_RECHARGE';
$user_id = $_SESSION['user_id'];
$portal_id = $_SESSION['current_portal_id'] ?? null;

$pdo = connectDB();

try {
    $pdo->beginTransaction();

    // 1. Make an entry in the Transactions table
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, portal_id, amount, txn_type, payment_gateway_id, status) VALUES (?, ?, ?, 'recharge', ?, 'success')");
    $stmt->execute([$user_id, $portal_id, $amount, $payment_id]);

    // 2. Add (+) the balance to the user's account
    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
    $stmt->execute([$amount, $user_id]);

    // update session balance (to be displayed immediately)
    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $_SESSION['wallet_balance'] = $stmt->fetchColumn();

    $pdo->commit();
    echo json_encode(['success'=>true]);

} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'message'=> $e->getMessage()]);
}
?>