<?php
/**
 * app/actions.php
 * FINAL FIXED VERSION
 * Features:
 * 1. Fixed Freelancer & User Login Redirection (Case-insensitive check).
 * 2. Payment Status & Balance Logic included.
 * 3. All HR, Recruitment, and Appointment modules preserved.
 */

// 1. ENABLE ERROR LOGGING
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error_log.txt');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Invalid request method.');
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. LOAD DEPENDENCIES
require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';
require_once MODELS_PATH . 'auth.php';
require_once MODELS_PATH . 'roles.php';
require_once MODELS_PATH . 'notifications.php';
require_once MODELS_PATH . 'hr.php';
require_once MODELS_PATH . 'withdrawal.php';
require_once MODELS_PATH . 'messages.php';
require_once RECRUITMENT_MODELS_PATH . 'recruitment_post.php';
require_once MODELS_PATH . 'email_helper.php';
require_once MODELS_PATH . 'whatsapp_helper.php';

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'] ?? null;
$action = $_POST['action'] ?? '';
$pageRedirect = $_POST['page'] ?? 'dashboard';
$redirectParams = '';

try {
    // Actions that don't require login
    $public_actions = ['login_submit', 'book_appointment', 'register_user'];
    
    if (!$currentUserId && !in_array($action, $public_actions)) {
        throw new Exception('You must be logged in.');
    }

    switch ($action) {

        // ==========================================
        // 1. AUTHENTICATION (LOGIN FIX)
        // ==========================================
        case 'login_submit':
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (loginUser($email, $password)) {
                // Fetch Role and convert to lowercase to avoid mismatch (Fixes Freelancer Login)
                $role = strtolower($_SESSION['user_role'] ?? 'guest');
                
                // Redirect Logic based on Role
                if ($role === 'admin') {
                    $dashboard_page = 'master_dashboard';
                } elseif ($role === 'hr') {
                    $dashboard_page = 'hr_dashboard';
                } elseif ($role === 'accountant') {
                    $dashboard_page = 'accountant_dashboard';
                } elseif (in_array($role, ['deo', 'freelancer', 'data_entry_operator', 'worker', 'coordinator'])) {
                    // Fix: All workers go to worker_dashboard
                    $dashboard_page = 'worker_dashboard';
                } else {
                    // Default for customers/users
                    $dashboard_page = 'user_dashboard';
                }
                
                $pageRedirect = $dashboard_page;
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid email or password.</div>';
                $pageRedirect = 'login';
            }
            break;

        case 'register_user':
            // Basic registration logic
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)");
            // Default role ID for new users (adjust as needed, e.g., 4 for User)
            $defaultRoleId = $_POST['role_id'] ?? 4; 
            $stmt->execute([$_POST['name'], $_POST['email'], $hashedPassword, $defaultRoleId]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Registration successful! Please login.</div>';
            $pageRedirect = 'login';
            break;

        // ==========================================
        // 2. USER MANAGEMENT & BALANCE
        // ==========================================
        
        case 'recalculate_user_balance':
            $targetUserId = $_POST['user_id'];
            
            // Credit: Tasks Paid by Company
            $credit = fetchColumn($pdo, "SELECT SUM(task_price) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed' AND payment_collected_by = 'company'", [$targetUserId]) ?: 0;

            // Debit: Tasks Collected by Self (Company Fee Owed)
            $debitSelf = fetchColumn($pdo, "SELECT SUM(fee - task_price) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed' AND payment_collected_by = 'self'", [$targetUserId]) ?: 0;

            // Deduct: Withdrawals
            $withdrawals = fetchColumn($pdo, "SELECT SUM(amount) FROM withdrawals WHERE user_id = ? AND status != 'rejected'", [$targetUserId]) ?: 0;

            $newBalance = $credit - $debitSelf - $withdrawals;

            $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?")->execute([$newBalance, $targetUserId]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Balance recalculated! New Balance: ' . number_format($newBalance, 2) . '</div>';
            $pageRedirect = 'users';
            break;

        case 'edit_user_submit':
            $userIdToEdit = $_POST['user_id'];
            $sql = "UPDATE users SET name = ?, email = ?, role_id = ?, salary = ? WHERE id = ?";
            $params = [$_POST['name'], $_POST['email'], $_POST['role_id'], $_POST['salary'], $userIdToEdit];
            if (!empty($_POST['password'])) {
                $sql = "UPDATE users SET name = ?, email = ?, role_id = ?, salary = ?, password = ? WHERE id = ?";
                $params = [$_POST['name'], $_POST['email'], $_POST['role_id'], $_POST['salary'], password_hash($_POST['password'], PASSWORD_DEFAULT), $userIdToEdit];
            }
            $pdo->prepare($sql)->execute($params);
            $_SESSION['status_message'] = '<div class="alert alert-success">User updated successfully!</div>';
            $pageRedirect = 'users';
            break;

        case 'delete_user':
            $userIdToDelete = (int)$_POST['user_id'];
            if ($userIdToDelete > 1 && $userIdToDelete != $currentUserId) {
                $pdo->beginTransaction();
                try {
                    $pdo->prepare("UPDATE work_assignments SET assigned_to_user_id = 1 WHERE assigned_to_user_id = ?")->execute([$userIdToDelete]);
                    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userIdToDelete]);
                    $pdo->commit();
                    $_SESSION['status_message'] = '<div class="alert alert-success">User deleted. Tasks reassigned to Admin.</div>';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Cannot delete this user.</div>';
            }
            $pageRedirect = 'users';
            break;

        // ==========================================
        // 3. TASK MANAGEMENT
        // ==========================================

        case 'assign_task':
            $attachmentPath = null;
            if (isset($_FILES['task_attachment']) && $_FILES['task_attachment']['error'] == UPLOAD_ERR_OK) {
                $uploadDir = ROOT_PATH . 'uploads/task_attachments/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES['task_attachment']['name']);
                move_uploaded_file($_FILES['task_attachment']['tmp_name'], $uploadDir . $fileName);
                $attachmentPath = 'uploads/task_attachments/' . $fileName;
            }
            
            $clientId = $_POST['client_id'] ?? null;
            if (empty($clientId) && !empty($_POST['customer_id'])) {
                $customer = fetchOne($pdo, "SELECT client_id FROM customers WHERE id = ?", [$_POST['customer_id']]);
                $clientId = $customer['client_id'] ?? null;
            }

            // Fix: Added payment_status to Insert
            $stmt = $pdo->prepare("INSERT INTO work_assignments (
                customer_id, client_id, assigned_to_user_id, assigned_by_user_id, category_id, subcategory_id, 
                work_description, deadline, fee, fee_mode, maintenance_fee, maintenance_fee_mode, 
                discount, task_price, attachment_path, status, payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $_POST['customer_id'] ?? null, $clientId, $_POST['assigned_to_user_id'], $currentUserId, 
                $_POST['category_id'], $_POST['subcategory_id'] ?? null, $_POST['work_description'] ?? '', $_POST['deadline'], 
                $_POST['fee'] ?? 0, $_POST['fee_mode'] ?? 'pending', $_POST['maintenance_fee'] ?? 0, $_POST['maintenance_fee_mode'] ?? 'pending', 
                $_POST['discount'] ?? 0, $_POST['task_price'] ?? 0, $attachmentPath, 'in_process',
                $_POST['payment_status'] ?? 'pending'
            ]);
            
            addNotification($_POST['assigned_to_user_id'], "New task assigned.", "?page=my_freelancer_tasks");
            $_SESSION['status_message'] = '<div class="alert alert-success">Task assigned successfully!</div>';
            $pageRedirect = 'all_tasks';
            break;

        case 'update_task':
            $taskId = $_POST['task_id'];
            $newStatus = $_POST['status'];
            $assignedUserId = $_POST['assigned_to_user_id'];
            
            $oldTask = fetchOne($pdo, "SELECT status, is_verified FROM work_assignments WHERE id = ?", [$taskId]);
            $isVerified = ($newStatus === 'verified_completed') ? 1 : 0;
            $completionDate = ($newStatus === 'verified_completed') ? date('Y-m-d') : NULL;
            
            $stmt = $pdo->prepare("UPDATE work_assignments SET status=?, payment_status=?, admin_notes=?, is_verified=?, completion_date=? WHERE id=?");
            $stmt->execute([$newStatus, $_POST['payment_status'] ?? 'pending', $_POST['admin_notes'] ?? '', $isVerified, $completionDate, $taskId]);

            // Balance Update Logic
            if ($newStatus === 'verified_completed' && $oldTask['status'] !== 'verified_completed') {
                $taskData = fetchOne($pdo, "SELECT fee, task_price, payment_collected_by FROM work_assignments WHERE id = ?", [$taskId]);
                if ($taskData) {
                    $totalFee = (float)$taskData['fee'];
                    $freelancerFee = (float)$taskData['task_price'];
                    $collectedBy = $taskData['payment_collected_by'];
                    $balanceChange = 0;

                    if ($collectedBy === 'company') {
                        $balanceChange = $freelancerFee; // Credit
                    } elseif ($collectedBy === 'self') {
                        $balanceChange = -($totalFee - $freelancerFee); // Debit
                    }

                    if ($balanceChange != 0) {
                        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$balanceChange, $assignedUserId]);
                    }
                }
            }
            $_SESSION['status_message'] = '<div class="alert alert-success">Task updated!</div>';
            $pageRedirect = 'all_tasks';
            break;

        case 'delete_task':
            $taskId = $_POST['task_id'];
            $task = fetchOne($pdo, "SELECT * FROM work_assignments WHERE id = ?", [$taskId]);

            if ($task) {
                // Reverse Balance if completed
                if ($task['status'] === 'verified_completed') {
                    $uId = $task['assigned_to_user_id'];
                    $tPrice = (float)$task['task_price'];
                    $fee = (float)$task['fee'];
                    $mode = $task['payment_collected_by'];
                    
                    $correction = 0;
                    if ($mode === 'company') $correction = -$tPrice; 
                    if ($mode === 'self') $correction = ($fee - $tPrice); 

                    if ($correction != 0) {
                        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$correction, $uId]);
                    }
                }
                $pdo->prepare("DELETE FROM work_assignments WHERE id = ?")->execute([$taskId]);
                $_SESSION['status_message'] = '<div class="alert alert-success">Task deleted.</div>';
            }
            $pageRedirect = 'all_tasks';
            break;

        // ==========================================
        // 4. FREELANCER ACTIONS
        // ==========================================

        case 'freelancer_transfer_task':
            $taskId = $_POST['task_id'];
            $newUserId = $_POST['transfer_to_user_id'];
            $stmt = $pdo->prepare("UPDATE work_assignments SET assigned_to_user_id = ?, status = 'pending' WHERE id = ?");
            $stmt->execute([$newUserId, $taskId]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Task transferred!</div>';
            $pageRedirect = 'my_freelancer_tasks';
            break;

        case 'submit_for_verification':
            $taskId = $_POST['task_id'];
            $paymentCollectedBy = $_POST['payment_collected_by'] ?? 'none';
            $receiptPath = null;
            $workFilePath = null;
            $uploadDir = ROOT_PATH . 'uploads/task_receipts/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

            // Receipt Upload
            if (isset($_FILES['completion_receipt']) && $_FILES['completion_receipt']['error'] == 0) {
                $fName = 'receipt_' . $taskId . '_' . time() . '.' . pathinfo($_FILES['completion_receipt']['name'], PATHINFO_EXTENSION);
                move_uploaded_file($_FILES['completion_receipt']['tmp_name'], $uploadDir . $fName);
                $receiptPath = 'uploads/task_receipts/' . $fName;
            }
            // Work File Upload
            if (isset($_FILES['work_file']) && $_FILES['work_file']['error'] == 0) {
                $fName = 'work_' . $taskId . '_' . time() . '.' . pathinfo($_FILES['work_file']['name'], PATHINFO_EXTENSION);
                move_uploaded_file($_FILES['work_file']['tmp_name'], $uploadDir . $fName);
                $workFilePath = 'uploads/task_receipts/' . $fName;
            }

            $stmt = $pdo->prepare("UPDATE work_assignments SET status = 'pending_verification', completion_receipt_path = ?, work_file = ?, user_notes = ?, payment_collected_by = ? WHERE id = ? AND assigned_to_user_id = ?");
            $stmt->execute([$receiptPath, $workFilePath, $_POST['user_notes'] ?? '', $paymentCollectedBy, $taskId, $currentUserId]);
            
            // Notify Admins
            $admins = fetchAll($pdo, "SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'Admin'");
            foreach ($admins as $admin) {
                addNotification($admin['id'], "Task #{$taskId} submitted by freelancer.", "?page=edit_task&id={$taskId}");
            }

            $_SESSION['status_message'] = '<div class="alert alert-success">Work submitted!</div>';
            $pageRedirect = 'update_freelancer_task';
            $redirectParams = '&id=' . $taskId;
            break;

        case 'update_user_task':
            $stmt = $pdo->prepare("UPDATE work_assignments SET status = ?, user_notes = ? WHERE id = ? AND assigned_to_user_id = ?");
            $stmt->execute([$_POST['status'], $_POST['user_notes'] ?? '', $_POST['task_id'], $currentUserId]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Status updated.</div>';
            $pageRedirect = 'update_freelancer_task';
            $redirectParams = '&id=' . $_POST['task_id'];
            break;

        case 'return_task_to_admin':
            $stmt = $pdo->prepare("UPDATE work_assignments SET status = 'returned', user_notes = ? WHERE id = ? AND assigned_to_user_id = ?");
            $stmt->execute(["Returned: " . ($_POST['return_reason'] ?? '') . " - " . ($_POST['return_notes'] ?? ''), $_POST['task_id'], $currentUserId]);
            $_SESSION['status_message'] = '<div class="alert alert-warning">Task returned to admin.</div>';
            $pageRedirect = 'my_freelancer_tasks';
            break;

        // ==========================================
        // 5. WITHDRAWAL & ROLES
        // ==========================================

        case 'request_withdrawal':
            $amount = floatval($_POST['amount_to_withdraw']);
            $currentBalance = fetchColumn($pdo, "SELECT balance FROM users WHERE id = ?", [$currentUserId]) ?: 0.00;

            if ($amount <= 0 || $amount > $currentBalance) {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid amount.</div>';
            } else {
                $userBankDetails = fetchOne($pdo, "SELECT bank_name, account_holder_name, account_number, ifsc_code FROM users WHERE id = ?", [$currentUserId]);
                if (empty($userBankDetails['account_number'])) {
                    $_SESSION['status_message'] = '<div class="alert alert-danger">Add bank details first.</div>';
                } else {
                    $bankDetailsJson = json_encode($userBankDetails);
                    if (addWithdrawalRequest($currentUserId, $amount, $bankDetailsJson)) {
                        $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")->execute([$amount, $currentUserId]);
                        $_SESSION['status_message'] = '<div class="alert alert-success">Request submitted. Balance deducted.</div>';
                    } else {
                        $_SESSION['status_message'] = '<div class="alert alert-danger">Failed to submit.</div>';
                    }
                }
            }
            $pageRedirect = 'my_withdrawals';
            break;

        case 'update_withdrawal_status':
            $withdrawalId = $_POST['withdrawal_id'];
            $newStatus = $_POST['new_status'];
            $withdrawal = fetchOne($pdo, "SELECT user_id, amount, status FROM withdrawals WHERE id = ?", [$withdrawalId]);

            if ($withdrawal) {
                if ($newStatus === 'rejected' && $withdrawal['status'] !== 'rejected') {
                    $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$withdrawal['amount'], $withdrawal['user_id']]);
                }
                updateWithdrawalStatus($withdrawalId, $newStatus, $currentUserId, $_POST['admin_comments'] ?? '', $_POST['transaction_id'] ?? '');
                $_SESSION['status_message'] = '<div class="alert alert-success">Status updated.</div>';
            }
            $pageRedirect = 'manage_withdrawals';
            break;

        case 'add_role': 
            createRole($_POST['role_name'], $_POST['permissions'] ?? [], $_POST['dashboard_permissions'] ?? []); 
            $pageRedirect = 'manage_roles'; 
            break;
        case 'edit_role': 
            updateRole($_POST['role_id'], $_POST['role_name'], $_POST['permissions'] ?? [], $_POST['dashboard_permissions'] ?? []); 
            $pageRedirect = 'manage_roles'; 
            break;

        // ==========================================
        // 6. OTHER MODULES (Appointments, Categories, etc.)
        // ==========================================
        
        case 'book_appointment':
            $docPath = null;
            if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
                $uploadDir = ROOT_PATH . 'uploads/client_documents/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                $fName = uniqid() . '_' . basename($_FILES['document']['name']);
                move_uploaded_file($_FILES['document']['tmp_name'], $uploadDir . $fName);
                $docPath = 'uploads/client_documents/' . $fName;
            }
            $stmt = $pdo->prepare("INSERT INTO appointments (client_name, client_phone, client_email, category_id, user_id, appointment_date, appointment_time, notes, document_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['client_name'], $_POST['client_phone'], $_POST['client_email'], $_POST['category_id'], $_POST['user_id'], $_POST['appointment_date'], $_POST['appointment_time'], $_POST['notes'], $docPath]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Appointment booked!</div>';
            $pageRedirect = 'login';
            break;

        case 'update_appointment_status':
            $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?")->execute([$_POST['status'], $_POST['appointment_id']]);
            $pageRedirect = 'appointments';
            break;

        case 'add_category': 
            $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)")->execute([$_POST['name'], $_POST['description']]); 
            $pageRedirect = 'categories'; 
            break;
        case 'edit_category': 
            $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?")->execute([$_POST['name'], $_POST['description'], $_POST['category_id']]); 
            $pageRedirect = 'categories'; 
            break;
        case 'add_client':
            $stmt = $pdo->prepare("INSERT INTO clients (client_name, company_name, email, phone, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['client_name'], $_POST['company_name'], $_POST['email'], $_POST['phone'], $_POST['address']]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Client added successfully!</div>';
            $pageRedirect = 'clients';
            break;

        default:
            // Fallback for any unknown action
            break;
    }
} catch (Exception $e) {
    error_log("Error in actions.php: " . $e->getMessage());
    $_SESSION['status_message'] = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

header("Location: " . BASE_URL . "?page=" . $pageRedirect . $redirectParams);
exit;
?>