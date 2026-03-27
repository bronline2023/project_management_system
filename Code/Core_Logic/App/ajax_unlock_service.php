<?php
/**
 * app/ajax_unlock_service.php
 * Micro-endpoint to unlock a digital service without reloading the page.
 */
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config.php';
require_once MODELS_PATH . 'db.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request']);
    exit;
}

$action = $_POST['action'] ?? '';
$service_slug = $_POST['service_slug'] ?? '';

if (empty($action) || empty($service_slug)) {
    echo json_encode(['success' => false, 'error' => 'Missing Parameters']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['user_role'] ?? 'guest';

// GUEST LOGIC
if ($action === 'unlock_guest_digital_service' && $user_id == 0) {
    if (!isset($_COOKIE['guest_service_used'])) {
        setcookie('guest_service_used', '1', time() + 86400, "/"); // 24 hours
        $_SESSION['unlocked_services'][$service_slug] = true;
        echo json_encode(['success' => true]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'You have already used your free guest access today. Please sign up or come back tomorrow!']);
        exit;
    }
}

// LOGGED IN USER WALLET LOGIC
if ($action === 'unlock_digital_service' && $user_id > 0) {
    try {
        $pdo = connectDB();
        
        // Check rate
        $stmt = $pdo->prepare("SELECT price, points_price, service_name FROM digital_service_rates WHERE service_slug = ? AND is_active = 1");
        $stmt->execute([$service_slug]);
        $service_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$service_info) {
            echo json_encode(['success' => false, 'error' => 'Service not found or disabled.']);
            exit;
        }
        
        $rate = (float)$service_info['price'];
        $pts_rate = (int)$service_info['points_price'];
        
        // Check custom rate if exists
        $stmt_user = $pdo->prepare("SELECT balance, poster_points, custom_poster_rate FROM users WHERE id = ?");
        $stmt_user->execute([$user_id]);
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        if ($service_slug === 'poster_studio' && !empty($user_data['custom_poster_rate'])) {
            $rate = (float)$user_data['custom_poster_rate'];
        }
        
        $balance = (float)($user_data['balance'] ?? 0);
        $points = (int)($user_data['poster_points'] ?? 0);
        
        // Safety check: if standard rate & points rate is 0, it's globally free.
        if ($rate <= 0 && $pts_rate <= 0) {
            $_SESSION['unlocked_services'][$service_slug] = true;
            echo json_encode(['success' => true]);
            exit;
        }

        $pdo->beginTransaction();

        // 1. Try to deduct Points FIRST (if configured)
        if ($pts_rate > 0 && $points >= $pts_rate) {
            $pdo->prepare("UPDATE users SET poster_points = poster_points - ? WHERE id = ?")->execute([$pts_rate, $user_id]);
            $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, 'debit', ?, ?)")->execute([$user_id, 0, "Earned/Spent Points: Deducted $pts_rate pts for " . $service_info['service_name']]);
            // Update session cache if needed
            $_SESSION['unlocked_services'][$service_slug] = true;
            $pdo->commit();
            echo json_encode(['success' => true]);
            exit;
        } 
        // 2. Fallback to Wallet Deduction (if configured)
        elseif ($rate > 0 && $balance >= $rate) {
            $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")->execute([$rate, $user_id]);
            $_SESSION['wallet_balance'] = $balance - $rate; // Sync session
            
            $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, 'debit', ?, ?)")->execute([$user_id, $rate, "Unlocked Service: " . $service_info['service_name']]);
            $pdo->commit();
            $_SESSION['unlocked_services'][$service_slug] = true;
            echo json_encode(['success' => true]);
            exit;
        } 
        // 3. User is broke
        else {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => 'Insufficient wallet or points balance. Please recharge.']);
            exit;
        }
    } catch(Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Unauthorized action.']);
exit;

