<?php
// app/process_subscription.php
header('Content-Type: application/json');
require_once __DIR__ . '/../Config/init.php'; // Your database connection

// Grab the JSON data
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

$domain_name = $data['domain_name'];
$name = $data['name'];
$email = $data['email'];
$password = password_hash($data['password'], PASSWORD_DEFAULT); // Secure the password
$payment_id = $data['payment_id'];
$amount = 5000.00;
$expiry_date = date('Y-m-d', strtotime('+1 year')); // 1 year validity

$pdo = connectDB();

try {
    // 0. Start the TRANSACTION (if an error occurs in between, the whole process is rolled back)
    $pdo->beginTransaction();

    // 1. First create Admin account in USERS table (still keep portal_id NULL)
    $stmt = $pdo->prepare("INSERT INTO users (role, role_id, name, email, password) VALUES ('master_admin', 1, ?, ?, ?)");
    $stmt->execute([$name, $email, $password]);
    $new_user_id = $pdo->lastInsertId();

    // 2. Register the domain in the PORTALS table and set the folder name
    $folder_name = "portal_" . $new_user_id . "_" . time();
    $stmt = $pdo->prepare("INSERT INTO portals (domain_name, owner_id, plan_id, expiry_date, folder_path) VALUES (?, ?, 1, ?, ?)");
    $stmt->execute([$domain_name, $new_user_id, $expiry_date, $folder_name]);
    $new_portal_id = $pdo->lastInsertId();

    // 3. Go back and update the Portal ID in that Admin's account
    $stmt = $pdo->prepare("UPDATE users SET portal_id = ? WHERE id = ?");
    $stmt->execute([$new_portal_id, $new_user_id]);

    // 4. Save the payment receipt (Invoice) in the TRANSACTIONS table
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, portal_id, amount, txn_type, payment_gateway_id, status) VALUES (?, ?, ?, 'subscription_fee', ?, 'success')");
    $stmt->execute([$new_user_id, $new_portal_id, $amount, $payment_id]);

    // 5. 🪄 Magic: Auto create personal folder on server
    $target_dir = UPLOADS_PATH . $folder_name;
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
        // Put index.php for security so no one can see the direct files
        file_put_contents($target_dir . "/index.php", "<?php echo 'Access Denied - Security Wall'; ?>");
    }

    // If everything goes well, make a permanent save (Commit) in the database
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Portal successfully created!']);

} catch (Exception $e) {
    // If an error occurs (eg email is already registered), roll back everything
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>