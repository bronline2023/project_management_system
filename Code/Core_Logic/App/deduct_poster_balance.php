<?php
// File location: app/deduct_poster_balance.php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
ini_set('display_errors', 0);

// FIXED: config.php is 3 levels up, not 2. This resolves the 500 error!
require_once dirname(dirname(dirname(__DIR__))) . '/config.php';
require_once MODELS_PATH . 'db.php';

ob_clean();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['user_role'] ?? 'guest';
$service_type = isset($_POST['service_type']) ? trim($_POST['service_type']) : 'Digital Studio Service';
$service_slug = isset($_POST['service_slug']) ? trim($_POST['service_slug']) : '';

if (empty($service_slug) && ($user_id != 0 && !in_array($role, ['master_admin', 'admin']))) {
    echo json_encode(['success' => false, 'message' => 'System Error: Service slug is missing. Please refresh the page and try again.']);
    exit;
}

// GUEST LOGIC
if ($user_id == 0 || $role === 'guest') {
    if (!isset($_COOKIE['guest_service_used'])) {
        setcookie('guest_service_used', '1', time() + 86400, "/"); // 24 hours
        echo json_encode(['success' => true, 'message' => 'Guest pass used successfully!']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Free Limit Reached: You have already used your free guest download today. Please sign up for unlimited downloads!']);
        exit;
    }
}

// LOGGED IN USER LOGIC
try {
    $pdo = connectDB();
    if (!$pdo) { throw new Exception("Database connection failed."); }

    $currency = '₹';
    try {
        $stmt_settings = $pdo->query("SELECT currency_symbol FROM settings LIMIT 1");
        $settings = $stmt_settings->fetch(PDO::FETCH_ASSOC);
        if ($settings && isset($settings['currency_symbol'])) { $currency = $settings['currency_symbol']; }
    } catch(Exception $e) { /* ignore */ }

    // Cost logic setup
    $cost = 10.00;
    $points_cost = 0;
    
    // Check digital_service_rates if slug is provided
    $service_slug = isset($_POST['service_slug']) ? trim($_POST['service_slug']) : '';
    if (!empty($service_slug)) {
        $stmt_rate = $pdo->prepare("SELECT price, points_price FROM digital_service_rates WHERE service_slug = ? AND is_active = 1");
        $stmt_rate->execute([$service_slug]);
        $rate_info = $stmt_rate->fetch(PDO::FETCH_ASSOC);
        if ($rate_info) {
            $cost = (float)$rate_info['price'];
            $points_cost = (int)$rate_info['points_price'];
        }
    } else {
        // Fallback to old global setting if no slug provided
        try {
            $stmt_settings = $pdo->query("SELECT poster_generation_cost FROM settings LIMIT 1");
            $s = $stmt_settings->fetch(PDO::FETCH_ASSOC);
            if ($s && isset($s['poster_generation_cost'])) { $cost = (float)$s['poster_generation_cost']; }
        } catch(Exception $e) { }
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User account not found.']);
        exit;
    }

    if (!array_key_exists('balance', $user)) {
        echo json_encode(['success' => false, 'message' => 'System Error: Database does not have "balance" column!']);
        exit;
    }

    // Custom poster rate override for backwards compatibility or specific Poster Studio overrides
    if (isset($user['custom_poster_rate']) && $user['custom_poster_rate'] !== null && $user['custom_poster_rate'] !== '' && (empty($service_slug) || $service_slug === 'poster_studio')) {
        $cost = (float)$user['custom_poster_rate'];
    }

    // Free for admin (but entry will be from 0 rupees)
    if (in_array($role, ['master_admin', 'admin'])) {
        try {
            $insert_log = $pdo->prepare("INSERT INTO digital_studio_logs (user_id, service_type, cost) VALUES (?, ?, 0.00)");
            $insert_log->execute([$user_id, $service_type]);
        } catch(Exception $e) {}
        echo json_encode(['success' => true, 'remaining_balance' => 'Unlimited', 'cost' => 0, 'currency' => $currency, 'deducted_type' => 'admin_free']);
        exit;
    }

    $current_balance = (float)$user['balance'];
    $current_points = (int)($user['poster_points'] ?? 0);

    // DEDUCTION LOGIC
    if ($cost <= 0) {
        // Completely Free
        try { $pdo->prepare("INSERT INTO digital_studio_logs (user_id, service_type, cost) VALUES (?, ?, ?)")->execute([$user_id, $service_type, 0]); } catch(Exception $e) {}
        echo json_encode(['success' => true, 'remaining_balance' => number_format($current_balance, 2), 'cost' => 0, 'currency' => $currency, 'deducted_type' => 'free']);
        exit;
    }

    if ($points_cost > 0 && $current_points >= $points_cost && isset($_POST['use_points']) && $_POST['use_points'] == '1') {
        // Explicitly using points fallback
        $new_points = $current_points - $points_cost;
        $pdo->prepare("UPDATE users SET poster_points = ? WHERE id = ?")->execute([$new_points, $user_id]);
        
        try {
            $pdo->prepare("INSERT INTO digital_studio_logs (user_id, service_type, cost) VALUES (?, ?, ?)")->execute([$user_id, $service_type . " (Paid via Points)", 0]);
            $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, 'debit', ?, ?)")->execute([$user_id, 0, "Points Used: Deducted $points_cost pts for $service_type"]);
        } catch(Exception $e) {}

        $_SESSION['poster_points'] = $new_points;
        echo json_encode(['success' => true, 'remaining_balance' => number_format($current_balance, 2), 'points_remaining' => $new_points, 'cost' => $points_cost, 'currency' => 'Pts', 'deducted_type' => 'points']);
        exit;
    }

    if ($current_balance >= $cost) {
        // Regular Wallet Deduction
        $new_balance = $current_balance - $cost;
        $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?")->execute([$new_balance, $user_id]);

        try {
            $pdo->prepare("INSERT INTO digital_studio_logs (user_id, service_type, cost) VALUES (?, ?, ?)")->execute([$user_id, $service_type, $cost]);
            $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, 'debit', ?, ?)")->execute([$user_id, $cost, "Studio Usage: $service_type"]);
        } catch(Exception $e) {}

        $_SESSION['balance'] = $new_balance;
        $_SESSION['user_balance'] = $new_balance; 
        
        echo json_encode(['success' => true, 'remaining_balance' => number_format($new_balance, 2), 'cost' => $cost, 'currency' => $currency, 'deducted_type' => 'wallet']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Insufficient wallet balance or points limits reached.']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
}
?>