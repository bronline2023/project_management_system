<?php
// File location: app/deduct_poster_balance.php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
ini_set('display_errors', 0);

require_once dirname(dirname(__DIR__)) . '/config.php';
require_once MODELS_PATH . 'db.php';

ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Your session is over. Please login again.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? 'guest';
$service_type = isset($_POST['service_type']) ? trim($_POST['service_type']) : 'Digital Studio Service';

try {
    $pdo = connectDB();
    if (!$pdo) { throw new Exception("Database connection failed."); }

    $cost = 10.00;
    $currency = '₹';

    try {
        $stmt_settings = $pdo->query("SELECT poster_generation_cost, currency_symbol FROM settings LIMIT 1");
        $settings = $stmt_settings->fetch(PDO::FETCH_ASSOC);
        if ($settings) {
            if (isset($settings['poster_generation_cost'])) $cost = (float)$settings['poster_generation_cost'];
            if (isset($settings['currency_symbol'])) $currency = $settings['currency_symbol'];
        }
    } catch(Exception $e) { /* ignore */ }

    // Free for admin (but entry will be from 0 rupees)
    if ($role === 'admin') {
        try {
            $insert_log = $pdo->prepare("INSERT INTO digital_studio_logs (user_id, service_type, cost) VALUES (?, ?, 0.00)");
            $insert_log->execute([$user_id, $service_type]);
        } catch(Exception $e) {}
        echo json_encode(['success' => true, 'remaining_balance' => 'Unlimited', 'cost' => 0, 'currency' => $currency]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (!array_key_exists('balance', $user)) {
            echo json_encode(['success' => false, 'message' => 'System Error: Database does not have "balance" column!']);
            exit;
        }

        // User's custom rate
        if (isset($user['custom_poster_rate']) && $user['custom_poster_rate'] !== null && $user['custom_poster_rate'] !== '') {
            $cost = (float)$user['custom_poster_rate'];
        }

        if ((float)$user['balance'] >= $cost) {
            $new_balance = (float)$user['balance'] - $cost;
            $update = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $update->execute([$new_balance, $user_id]);

            // Make a report entry in the database
            try {
                $insert_log = $pdo->prepare("INSERT INTO digital_studio_logs (user_id, service_type, cost) VALUES (?, ?, ?)");
                $insert_log->execute([$user_id, $service_type, $cost]);
            } catch(Exception $e) {}

            // update session (for dashboard)
            $_SESSION['balance'] = $new_balance;
            $_SESSION['user_balance'] = $new_balance; 
            
            echo json_encode(['success' => true, 'remaining_balance' => number_format($new_balance, 2), 'cost' => $cost, 'currency' => $currency]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Your wallet does not have enough balance to download.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User account not found.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
}
?>