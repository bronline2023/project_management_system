<?php
/**
 * app/create_tenant.php
 * Handles the creation of a new B2B Tenant
 * - Injects record into Master Database (tenants table)
 * - Creates a blank database using tenant_template.sql
 * - Provisions isolated file folders.
 */

if (session_status() == PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../../config.php';
require_once MODELS_PATH . 'db.php';

// Only Central Admin should do this
if (($_SESSION['user_role'] ?? '') !== 'admin' || IS_TENANT) {
    die("Access denied. Only Central Admin can create tenants.");
}

$tenant_name = $_POST['company_name'] ?? '';
$owner_name = $_POST['owner_name'] ?? '';
$owner_email = $_POST['owner_email'] ?? '';
$owner_phone = $_POST['owner_phone'] ?? '';
$domain_name = $_POST['domain_name'] ?? ''; // e.g. client1.localhost
$plan_id = (int)($_POST['plan_id'] ?? 1);

if (empty($domain_name) || empty($owner_email)) {
    $_SESSION['status_message'] = "<div class='alert alert-danger'>Company Domain and Email are required.</div>";
    header("Location: " . BASE_URL . "?page=dashboard");
    exit;
}

// 1. Generate unique names
$timestamp = time();
$db_name = 'pms_tenant_' . $timestamp;
$folder_path = 'uploads/tenants/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $domain_name);

$master_pdo = connectDB(); 

try {
    $master_pdo->beginTransaction();

    // 2. Insert into master DB
    $stmt = $master_pdo->prepare("INSERT INTO tenants (company_name, owner_name, owner_email, owner_phone, domain_name, db_name, folder_path, plan_id, subscription_start, subscription_end, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active')");
    $stmt->execute([$tenant_name, $owner_name, $owner_email, $owner_phone, $domain_name, $db_name, $folder_path, $plan_id]);

    // 3. Provision the MySQL Database Let's dynamically provision!
    $master_pdo->exec("CREATE DATABASE `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

    // 4. Import Template Database
    $template_path = realpath(__DIR__ . '/../database/tenant_template.sql');
    $mysql_bin = 'c:\\xampp\\mysql\\bin\\mysql.exe'; // Hardcoded for XAMPP Windows
    
    // Construct the dump import command
    $command = "\"$mysql_bin\" -u root "; // DB_USER is root
    if (defined('DB_PASS') && !empty(DB_PASS)) {
        $command .= "-p\"" . DB_PASS . "\" ";
    }
    $command .= "\"$db_name\" < \"$template_path\"";
    
    exec('cmd.exe /c "' . $command . '"', $output, $return_var);
    if ($return_var !== 0) {
        throw new Exception("SQL import failed. Command exited with code $return_var");
    }

    // 5. Setup the tenant folder structure for complete isolation
    $target_dir = __DIR__ . '/../' . $folder_path;
    $sub_dirs = ['/profile_pictures', '/task_attachments', '/task_receipts', '/client_documents', '/logo', '/recruitment_docs', '/generated_posters', '/recharge_proofs', '/chat_attachments'];
    
    foreach ($sub_dirs as $sub) {
        $dirToMake = $target_dir . $sub;
        if (!is_dir($dirToMake)) {
            mkdir($dirToMake, 0777, true);
            file_put_contents($dirToMake . '/index.php', '<?php echo "Access Denied"; ?>');
        }
    }

    $master_pdo->commit();
    $_SESSION['status_message'] = "<div class='alert alert-success fw-bold'><i class='fas fa-check-circle'></i> B2B Portal Successfully Created! Domain: $domain_name</div>";
    header("Location: " . BASE_URL . "?page=manage_b2b_users");
    exit;

} catch (Exception $e) {
    if ($master_pdo->inTransaction()) {
        $master_pdo->rollBack();
    }
    $master_pdo->exec("DROP DATABASE IF EXISTS `$db_name`");
    
    $_SESSION['status_message'] = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Internal Provisioning Error: " . $e->getMessage() . "</div>";
    header("Location: " . BASE_URL . "?page=dashboard");
    exit;
}
?>
