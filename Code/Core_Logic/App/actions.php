<?php
/**
 * app/actions.php
 * FINAL CORRECTED VERSION
 * - Fixes Redirect logic for Delete/Submit actions (Admin vs User).
 * - Fixes Database Updates logic.
 * - Supports Custom Links.
 */

// 1. ENABLE ERROR LOGGING
ini_set('display_errors', 1);
ini_set('log_errors', 1);
// Note: error_log will be set correctly once config.php is loaded below.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Invalid request method.');
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load all necessary files
require_once __DIR__ . '/../../../config.php';
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

// Default Redirect: Use the 'page' from the form if available, otherwise default to dashboard
$pageRedirect = $_POST['page'] ?? 'dashboard';
$redirectParams = '';

try {
    // Actions that don't require login
    $public_actions = ['login_submit', 'book_appointment', 'register_user', 'register_b2c'];
    
    if (!$currentUserId && !in_array($action, $public_actions)) {
        throw new Exception('You must be logged in.');
    }

    switch ($action) {

       // ==========================================
        // 1. AUTHENTICATION & LOGIN (IN app/actions.php)
        // ==========================================
        case 'login_submit':
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (loginUser($email, $password)) {
                $role = strtolower($_SESSION['user_role'] ?? 'guest');
                
                // 🚀 FIX: Set right dashboard directory (according to Role Permissions) 🚀
                $perms = $_SESSION['user_permissions'] ?? [];
                if (in_array('master_dashboard', $perms) || in_array($role, ['master_admin', 'admin'])) {
                    $dashboard_page = 'master_dashboard';
                } elseif (in_array('super_admin_dashboard', $perms)) {
                    $dashboard_page = 'super_admin_dashboard';
                } elseif (in_array('district_manager_dashboard', $perms)) {
                    $dashboard_page = 'district_manager_dashboard';
                } elseif (in_array('retailer_dashboard', $perms)) {
                    $dashboard_page = 'retailer_dashboard';
                } elseif (in_array('hr_dashboard', $perms)) {
                    $dashboard_page = 'hr_dashboard';
                } elseif (in_array('accountant_dashboard', $perms)) {
                    $dashboard_page = 'accountant_dashboard';
                } elseif (in_array('worker_dashboard', $perms)) {
                    $dashboard_page = 'worker_dashboard';
                } else {
                    $dashboard_page = 'user_dashboard'; // retailer, district_manager, clients
                }
                $pageRedirect = $dashboard_page;
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger fw-bold text-center mt-3"><i class="fas fa-exclamation-triangle"></i> The email or password is incorrect!</div>';
                $pageRedirect = 'login';
            }
            break;

        case 'register_user':
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)");
            $defaultRoleId = $_POST['role_id'] ?? 4; 
            $stmt->execute([$_POST['name'], $_POST['email'], $hashedPassword, $defaultRoleId]);
            
            // Give Rs. 10 Sign Up Bonus
            $newUserId = $pdo->lastInsertId();
            $signupBonus = 10.00;
            $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$signupBonus, $newUserId]);
            $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, 'credit_system', ?, 'Sign Up Bonus')")->execute([$newUserId, $signupBonus]);

            $_SESSION['status_message'] = '<div class="alert alert-success fw-bold text-center"><i class="fas fa-gift"></i> Registration successful! A ₹10 Sign-Up Bonus has been added to your Wallet. Please login.</div>';
            $pageRedirect = 'login';
            break;

        // ==========================================
        // 1.5 B2C REGISTRATION
        // ==========================================
        case 'register_b2c':
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($name) || empty($email) || empty($phone) || empty($password)) {
                $_SESSION['status_message'] = '<div class="alert alert-danger fw-bold text-center">Please fill all fields.</div>';
                $pageRedirect = 'b2c_register';
                break;
            }

            // Default to Retailer
            $roleId = fetchColumn($pdo, "SELECT id FROM roles WHERE role_name='Retailer'");
            if (!$roleId) $roleId = 4;

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, role_id, balance) VALUES (?, ?, ?, ?, 'retailer', ?, 10.00)");
            
            try {
                $stmt->execute([$name, $email, $phone, $hashedPassword, $roleId]);
                $newUserId = $pdo->lastInsertId();
                
                // Add wallet transaction record
                $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, 'credit_system', 10.00, 'B2C Sign Up Bonus (10 Points)')")->execute([$newUserId]);

                $_SESSION['status_message'] = '<div class="alert alert-success fw-bold text-center"><i class="fas fa-gift"></i> Registration successful! You have received 10 Credit Points. Please login.</div>';
                $pageRedirect = 'login';
            } catch (Exception $e) {
                $_SESSION['status_message'] = '<div class="alert alert-danger fw-bold text-center">Registration failed. Email might already exist.</div>';
                $pageRedirect = 'b2c_register';
            }
            break;

        // ==========================================
        // 2. CUSTOMER MANAGEMENT
        // ==========================================
        case 'add_customer':
            $name = trim($_POST['customer_name'] ?? '');
            $phone = trim($_POST['customer_phone'] ?? '');
            $email = trim($_POST['customer_email'] ?? '');
            $address = trim($_POST['customer_address'] ?? '');
            $clientId = !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null;

            if (empty($name) || empty($phone)) {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Name and phone number are required.</div>';
            } else {
                $stmt = $pdo->prepare("INSERT INTO customers (customer_name, customer_phone, customer_email, customer_address, client_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $phone, $email, $address, $clientId]);
                $_SESSION['status_message'] = '<div class="alert alert-success">Customer has been successfully added.</div>';
            }
            $pageRedirect = 'customers';
            break;
            
        case 'delete_customer':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid ID</div>';
            } else {
                try {
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM work_assignments WHERE customer_id = ?");
                    $checkStmt->execute([$id]);
                    $hasTasks = $checkStmt->fetchColumn();

                    if ($hasTasks > 0) {
                        $_SESSION['status_message'] = '<div class="alert alert-danger">These customers cannot be deleted, as their tasks are registered in the database.</div>';
                    } else {
                        $deleteStmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
                        $deleteStmt->execute([$id]);
                        if ($deleteStmt->rowCount() > 0) {
                            $_SESSION['status_message'] = '<div class="alert alert-success">Customer has been successfully deleted.</div>';
                        } else {
                            $_SESSION['status_message'] = '<div class="alert alert-warning">Customer not found or already deleted.</div>';
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Delete Error: " . $e->getMessage());
                    $_SESSION['status_message'] = '<div class="alert alert-danger">Database Error: Could not delete customer.</div>';
                }
            }
            $pageRedirect = 'customers';
            break;

        // ==========================================
        // 3. USER & PROFILE MANAGEMENT
        // ==========================================
        case 'recalculate_user_balance':
            $targetUserId = $_POST['user_id'];

            // 1. Recruitment Earnings
            // Get the post value from settings
            $ratePerPost = (float)fetchColumn($pdo, "SELECT earning_per_approved_post FROM settings WHERE id = 1");
            // Count approved posts
            $approvedPosts = (int)fetchColumn($pdo, "SELECT COUNT(*) FROM recruitment_posts WHERE submitted_by_user_id = ? AND approval_status = 'approved'", [$targetUserId]);
            $recruitmentIncome = $approvedPosts * $ratePerPost;

            // 2. Task Earnings (Task Earnings - Completed only)
            $taskIncome = (float)fetchColumn($pdo, "SELECT SUM(task_price) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed'", [$targetUserId]) ?: 0;

            // 3. Cash Collected (If the user has collected cash then it is borrowed)
            $cashCollected = (float)fetchColumn($pdo, "SELECT SUM(fee) FROM work_assignments WHERE assigned_to_user_id = ? AND status = 'verified_completed' AND payment_collected_by = 'self'", [$targetUserId]) ?: 0;

            // 4. Withdrawals (After both approved and pending)
            $withdrawals = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM withdrawals WHERE user_id = ? AND status != 'rejected'", [$targetUserId]) ?: 0;

            // 4.5 Wallet Transactions (Digital Services, Manual Recharges)
            $walletCredits = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM wallet_transactions WHERE user_id = ? AND type IN ('credit', 'credit_system')", [$targetUserId]) ?: 0;
            $walletDebits = (float)fetchColumn($pdo, "SELECT SUM(amount) FROM wallet_transactions WHERE user_id = ? AND type = 'debit'", [$targetUserId]) ?: 0;

            // 5. Final Calculation (correct formula)
            // (Total Earnings + Wallet Credit) - (Cash Taken) - (Withdrawal) - (Wallet Debit)
            $newBalance = ($recruitmentIncome + $taskIncome + $walletCredits) - $cashCollected - $withdrawals - $walletDebits;

            // 6. Update Database
            $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?")->execute([$newBalance, $targetUserId]);

            $_SESSION['status_message'] = '<div class="alert alert-success">Balance recalculated successfully! New Balance: ' . number_format($newBalance, 2) . '</div>';
            $pageRedirect = 'users';
            break;

        case 'edit_user_submit':
    if ($_SESSION['user_role'] !== 'admin') {
        $_SESSION['status_message'] = '<div class="alert alert-danger">Access denied.</div>';
        header('Location: ' . BASE_URL . '?page=dashboard');
        exit;
    }
    
    $user_id = (int)$_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role_id = (int)$_POST['role_id'];
    $salary = !empty($_POST['salary']) ? (float)$_POST['salary'] : 0.00;
    
    // 🚀 NEW: Get balance and digital service rate 🚀
    $balance = isset($_POST['balance']) ? (float)$_POST['balance'] : 0.00;
    // If rate is empty then let NULL in database (so global rate is found)
    $custom_poster_rate = ($_POST['custom_poster_rate'] !== '') ? (float)$_POST['custom_poster_rate'] : null;
    
    $password = $_POST['password'] ?? '';

    try {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role_id=?, salary=?, balance=?, custom_poster_rate=?, password=? WHERE id=?");
            $stmt->execute([$name, $email, $role_id, $salary, $balance, $custom_poster_rate, $hashed_password, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role_id=?, salary=?, balance=?, custom_poster_rate=? WHERE id=?");
            $stmt->execute([$name, $email, $role_id, $salary, $balance, $custom_poster_rate, $user_id]);
        }
        $_SESSION['status_message'] = '<div class="alert alert-success">User updated successfully!</div>';
    } catch (Exception $e) {
        $_SESSION['status_message'] = '<div class="alert alert-danger">Error updating user: ' . $e->getMessage() . '</div>';
    }
    
    header('Location: ' . BASE_URL . '?page=users');
    exit;

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

        case 'update_profile':
            $name = trim($_POST['name']);
            if (empty($name)) { throw new Exception('Name is required.'); }
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $currentUserId]);
            $_SESSION['user_name'] = $name;
            $_SESSION['status_message'] = '<div class="alert alert-success">Profile updated successfully!</div>';
            break;

        case 'change_password':
            $newPassword = $_POST['new_password'];
            if (strlen($newPassword) < 6) { throw new Exception('Password must be at least 6 characters long.'); }
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $currentUserId]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Password changed successfully!</div>';
            break;

        case 'update_profile_picture':
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $file = $_FILES['profile_picture'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (in_array($file['type'], $allowedTypes)) {
                    $uploadDir = UPLOADS_PATH . 'profile_pictures/';
                    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
                    $fileName = 'user_' . $currentUserId . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filePath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filePath)) {
                        $webPath = 'profile_pictures/' . $fileName;
                        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                        $stmt->execute([$webPath, $currentUserId]);
                        $_SESSION['user_profile_picture'] = $webPath;
                        $_SESSION['status_message'] = '<div class="alert alert-success">Profile picture updated!</div>';
                    } else {
                        $_SESSION['status_message'] = '<div class="alert alert-danger">Failed to upload file.</div>';
                    }
                } else {
                    $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid file type.</div>';
                }
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger">No file was uploaded.</div>';
            }
            $pageRedirect = 'user_settings';
            break;

        case 'save_bank_details':
            $bankName = trim($_POST['bank_name'] ?? '');
            $accountHolderName = trim($_POST['account_holder_name'] ?? '');
            $accountNumber = trim($_POST['account_number'] ?? '');
            $ifscCode = trim($_POST['ifsc_code'] ?? '');
            $upiId = trim($_POST['upi_id'] ?? '');

            if (empty($bankName) || empty($accountHolderName) || empty($accountNumber) || empty($ifscCode)) {
                 $_SESSION['status_message'] = '<div class="alert alert-danger" role="alert">Please fill in all required bank details.</div>';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET bank_name = ?, account_holder_name = ?, account_number = ?, ifsc_code = ?, upi_id = ? WHERE id = ?");
                $stmt->execute([$bankName, $accountHolderName, $accountNumber, $ifscCode, $upiId, $currentUserId]);
                $_SESSION['status_message'] = '<div class="alert alert-success" role="alert">Your bank details have been saved successfully!</div>';
            }
            $pageRedirect = 'bank_details';
            break;

        // ==========================================
        // 4. SETTINGS
        // ==========================================
        case 'update_website_settings':
            if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'master_admin') {
                $websiteLogoUrl = null;
                if (!empty($_FILES['website_logo']['name'])) {
                    $uploadDir = UPLOADS_PATH . 'logo/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    
                    $fileName = 'web_logo_' . time() . '_' . basename($_FILES['website_logo']['name']);
                    $targetFile = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['website_logo']['tmp_name'], $targetFile)) {
                        $websiteLogoUrl = UPLOADS_DIR_RELATIVE . 'logo/' . $fileName;
                    }
                }

                $headerStyle = $_POST['header_style'] ?? 'style1';
                $logoSize = (int)($_POST['website_logo_size'] ?? 50);
                $menuColor = $_POST['menu_color'] ?? '';
                $menuActiveColor = $_POST['menu_active_color'] ?? '';
                
                // Footer additions
                $footerAbout = $_POST['footer_about_text'] ?? '';
                $footerCopyright = $_POST['footer_copyright'] ?? '';
                $contactEmail = $_POST['contact_email_public'] ?? '';
                $officeAddress = $_POST['office_address'] ?? '';
                $helplineNumber = $_POST['helpline_number'] ?? '';
                $socialFb = $_POST['social_facebook'] ?? '';
                $socialTw = $_POST['social_twitter'] ?? '';
                $socialIg = $_POST['social_instagram'] ?? '';
                $socialLi = $_POST['social_linkedin'] ?? '';
                $socialYt = $_POST['social_youtube'] ?? '';
                
                if ($websiteLogoUrl) {
                    $pdo->prepare("UPDATE settings SET website_logo_url = ?, header_style = ?, website_logo_size = ?, menu_color = ?, menu_active_color = ?, footer_about_text = ?, footer_copyright = ?, contact_email_public = ?, office_address = ?, helpline_number = ?, social_facebook = ?, social_twitter = ?, social_instagram = ?, social_linkedin = ?, social_youtube = ? WHERE id = 1")
                        ->execute([$websiteLogoUrl, $headerStyle, $logoSize, $menuColor, $menuActiveColor, $footerAbout, $footerCopyright, $contactEmail, $officeAddress, $helplineNumber, $socialFb, $socialTw, $socialIg, $socialLi, $socialYt]);
                } else {
                    $pdo->prepare("UPDATE settings SET header_style = ?, website_logo_size = ?, menu_color = ?, menu_active_color = ?, footer_about_text = ?, footer_copyright = ?, contact_email_public = ?, office_address = ?, helpline_number = ?, social_facebook = ?, social_twitter = ?, social_instagram = ?, social_linkedin = ?, social_youtube = ? WHERE id = 1")
                        ->execute([$headerStyle, $logoSize, $menuColor, $menuActiveColor, $footerAbout, $footerCopyright, $contactEmail, $officeAddress, $helplineNumber, $socialFb, $socialTw, $socialIg, $socialLi, $socialYt]);
                }
                $_SESSION['status_message'] = '<div class="alert alert-success">Website settings updated successfully!</div>';
            }
            $pageRedirect = 'website_settings';
            break;

        // ==========================================
        case 'update_settings':
            $logoUrl = null;
            if (!empty($_FILES['app_logo']['name'])) {
                $uploadDir = UPLOADS_PATH . 'logo/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = 'logo_' . time() . '_' . basename($_FILES['app_logo']['name']);
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['app_logo']['tmp_name'], $targetFile)) {
                    $logoUrl = UPLOADS_DIR_RELATIVE . 'logo/' . $fileName;
                }
            }

            $websiteLogoUrl = null;
            if (!empty($_FILES['website_logo']['name'])) {
                $uploadDir = UPLOADS_PATH . 'logo/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = 'web_logo_' . time() . '_' . basename($_FILES['website_logo']['name']);
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['website_logo']['tmp_name'], $targetFile)) {
                    $websiteLogoUrl = UPLOADS_DIR_RELATIVE . 'logo/' . $fileName;
                }
            }

            $workingDays = isset($_POST['office_working_days']) ? implode(',', $_POST['office_working_days']) : '';

            $sql = "UPDATE settings SET 
                app_name = ?, currency_symbol = ?, office_address = ?, helpline_number = ?, 
                office_start_time = ?, office_end_time = ?, appointment_slot_duration = ?, 
                office_working_days = ?, earning_per_approved_post = ?, minimum_withdrawal_amount = ?, 
                whatsapp_business_number = ?, whatsapp_api_key = ?, 
                smtp_host = ?, smtp_port = ?, smtp_encryption = ?, 
                smtp_username = ?, smtp_from_email = ?, smtp_from_name = ?, header_style = ?";
            
            $params = [
                $_POST['app_name'], $_POST['currency_symbol'], $_POST['office_address'], $_POST['helpline_number'],
                $_POST['office_start_time'], $_POST['office_end_time'], $_POST['appointment_slot_duration'],
                $workingDays, $_POST['earning_per_approved_post'], $_POST['minimum_withdrawal_amount'],
                $_POST['whatsapp_phone_number_id'], $_POST['whatsapp_access_token'],
                $_POST['smtp_host'], $_POST['smtp_port'], $_POST['smtp_encryption'],
                $_POST['smtp_username'], $_POST['smtp_from_email'], $_POST['smtp_from_name'],
                $_POST['header_style'] ?? 'style1'
            ];

            if ($logoUrl) {
                $sql .= ", app_logo_url = ?";
                $params[] = $logoUrl;
            }
            if ($websiteLogoUrl) {
                $sql .= ", website_logo_url = ?";
                $params[] = $websiteLogoUrl;
            }
            if (!empty($_POST['smtp_password'])) {
                $sql .= ", smtp_password = ?";
                $params[] = $_POST['smtp_password'];
            }

            $sql .= " WHERE id = 1"; 
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $_SESSION['status_message'] = '<div class="alert alert-success">Application settings updated successfully!</div>';
            $pageRedirect = 'settings';
            break;

        // ==========================================
        // 5. TASK MANAGEMENT
        // ==========================================
        case 'assign_task':
            $attachmentPath = null;
            if (isset($_FILES['task_attachment']) && $_FILES['task_attachment']['error'] == UPLOAD_ERR_OK) {
                $uploadDir = UPLOADS_PATH . 'task_attachments/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES['task_attachment']['name']);
                move_uploaded_file($_FILES['task_attachment']['tmp_name'], $uploadDir . $fileName);
                $attachmentPath = UPLOADS_DIR_RELATIVE . 'task_attachments/' . $fileName;
            }
            
            $clientId = $_POST['client_id'] ?? null;
            if (empty($clientId) && !empty($_POST['customer_id'])) {
                $customer = fetchOne($pdo, "SELECT client_id FROM customers WHERE id = ?", [$_POST['customer_id']]);
                $clientId = $customer['client_id'] ?? null;
            }

            $stmt = $pdo->prepare("INSERT INTO work_assignments (customer_id, client_id, assigned_to_user_id, assigned_by_user_id, category_id, subcategory_id, work_description, deadline, fee, fee_mode, maintenance_fee, maintenance_fee_mode, discount, task_price, attachment_path, status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['customer_id'] ?? null, $clientId, $_POST['assigned_to_user_id'], $currentUserId, 
                $_POST['category_id'], $_POST['subcategory_id'] ?? null, $_POST['work_description'] ?? '', $_POST['deadline'], 
                $_POST['fee'] ?? 0, $_POST['fee_mode'] ?? 'pending', $_POST['maintenance_fee'] ?? 0, $_POST['maintenance_fee_mode'] ?? 'pending', 
                $_POST['discount'] ?? 0, $_POST['task_price'] ?? 0, $attachmentPath, 'in_process', $_POST['payment_status'] ?? 'pending'
            ]);
            
            addNotification($_POST['assigned_to_user_id'], "New task assigned.", "?page=my_freelancer_tasks");
            $_SESSION['status_message'] = '<div class="alert alert-success">Task assigned successfully!</div>';
            $pageRedirect = 'all_tasks';
            break;

        case 'update_task':
            $taskId = $_POST['task_id'];
            $newStatus = $_POST['status'];
            $assignedUserId = $_POST['assigned_to_user_id'];
            
            $oldTask = fetchOne($pdo, "SELECT status, is_verified, fee, task_price, payment_collected_by FROM work_assignments WHERE id = ?", [$taskId]);
            
            $isVerified = ($newStatus === 'verified_completed') ? 1 : 0;
            $completionDate = ($newStatus === 'verified_completed') ? date('Y-m-d') : NULL;

            $clientId = $_POST['client_id'] ?? null;
            if (empty($clientId) && !empty($_POST['customer_id'])) {
                $cust = fetchOne($pdo, "SELECT client_id FROM customers WHERE id = ?", [$_POST['customer_id']]);
                $clientId = $cust['client_id'] ?? null;
            }

            $sql = "UPDATE work_assignments SET 
                customer_id = ?, client_id = ?, assigned_to_user_id = ?, category_id = ?, subcategory_id = ?, 
                work_description = ?, deadline = ?, fee = ?, fee_mode = ?, maintenance_fee = ?, maintenance_fee_mode = ?, 
                discount = ?, task_price = ?, status = ?, payment_status = ?, admin_notes = ?, is_verified = ?, completion_date = ? 
                WHERE id = ?";

            $params = [
                $_POST['customer_id'] ?? null, $clientId, $_POST['assigned_to_user_id'], $_POST['category_id'], $_POST['subcategory_id'] ?? null,
                $_POST['work_description'] ?? '', $_POST['deadline'], $_POST['fee'] ?? 0, $_POST['fee_mode'] ?? 'pending',
                $_POST['maintenance_fee'] ?? 0, $_POST['maintenance_fee_mode'] ?? 'pending', $_POST['discount'] ?? 0, $_POST['task_price'] ?? 0,
                $newStatus, $_POST['payment_status'] ?? 'pending', $_POST['admin_notes'] ?? '', $isVerified, $completionDate, $taskId
            ];

            $pdo->prepare($sql)->execute($params);

            // Balance Calculation
            if ($newStatus === 'verified_completed' && $oldTask['status'] !== 'verified_completed') {
                $totalFee = (float)($_POST['fee'] ?? 0);
                $freelancerFee = (float)($_POST['task_price'] ?? 0);
                $collectedBy = $oldTask['payment_collected_by']; 
                
                $balanceChange = 0;
                if ($collectedBy === 'company') {
                    $balanceChange = $freelancerFee; 
                } elseif ($collectedBy === 'self') {
                    $balanceChange = -($totalFee - $freelancerFee); 
                }

                if ($balanceChange != 0) {
                    $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$balanceChange, $assignedUserId]);
                }
            }

            $_SESSION['status_message'] = '<div class="alert alert-success">Task updated successfully!</div>';
            $pageRedirect = 'all_tasks';
            break;

        case 'delete_task':
            $taskId = $_POST['task_id'];
            $task = fetchOne($pdo, "SELECT * FROM work_assignments WHERE id = ?", [$taskId]);
            if ($task) {
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
			
        case 'freelancer_transfer_task':
            $stmt = $pdo->prepare("UPDATE work_assignments SET assigned_to_user_id = ?, status = 'pending' WHERE id = ?");
            $stmt->execute([$_POST['transfer_to_user_id'], $_POST['task_id']]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Task transferred!</div>';
            $pageRedirect = 'my_freelancer_tasks';
            break;

        case 'submit_for_verification':
            $taskId = $_POST['task_id'];
            $uploadDir = UPLOADS_PATH . 'task_receipts/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

            $receiptPath = null;
            if (isset($_FILES['completion_receipt']) && $_FILES['completion_receipt']['error'] == 0) {
                $fName = 'receipt_' . $taskId . '_' . time() . '.' . pathinfo($_FILES['completion_receipt']['name'], PATHINFO_EXTENSION);
                move_uploaded_file($_FILES['completion_receipt']['tmp_name'], $uploadDir . $fName);
                $receiptPath = UPLOADS_DIR_RELATIVE . 'task_receipts/' . $fName;
            }
            $workFilePath = null;
            if (isset($_FILES['work_file']) && $_FILES['work_file']['error'] == 0) {
                $fName = 'work_' . $taskId . '_' . time() . '.' . pathinfo($_FILES['work_file']['name'], PATHINFO_EXTENSION);
                move_uploaded_file($_FILES['work_file']['tmp_name'], $uploadDir . $fName);
                $workFilePath = UPLOADS_DIR_RELATIVE . 'task_receipts/' . $fName;
            }

            $stmt = $pdo->prepare("UPDATE work_assignments SET status = 'pending_verification', completion_receipt_path = ?, work_file = ?, user_notes = ?, payment_collected_by = ? WHERE id = ? AND assigned_to_user_id = ?");
            $stmt->execute([$receiptPath, $workFilePath, $_POST['user_notes'] ?? '', $_POST['payment_collected_by'] ?? 'none', $taskId, $currentUserId]);
            
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
        // 6. WITHDRAWAL & ROLES
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
                    addWithdrawalRequest($currentUserId, $amount, json_encode($userBankDetails));
                    $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")->execute([$amount, $currentUserId]);
                    $_SESSION['status_message'] = '<div class="alert alert-success">Request submitted.</div>';
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
            $_SESSION['status_message'] = '<div class="alert alert-success">Role created successfully!</div>';
            $pageRedirect = 'manage_roles';
            break;

        case 'edit_role':
            updateRole($_POST['role_id'], $_POST['role_name'], $_POST['permissions'] ?? [], $_POST['dashboard_permissions'] ?? []);
            $_SESSION['status_message'] = '<div class="alert alert-success">Role updated successfully!</div>';
            $pageRedirect = 'manage_roles';
            break;

        case 'delete_role':
            $roleId = (int)($_POST['role_id'] ?? 0);
            if ($roleId > 1) { 
                if (deleteRole($roleId)) {
                    $_SESSION['status_message'] = '<div class="alert alert-success">Role deleted successfully.</div>';
                } else {
                    $_SESSION['status_message'] = '<div class="alert alert-danger">Failed to delete role.</div>';
                }
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Cannot delete Admin role.</div>';
            }
            $pageRedirect = 'manage_roles';
            break;

        // ==========================================
        // 6.5 CUSTOM PAGES (CMS)
        // ==========================================
        case 'add_custom_page':
            if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'master_admin') {
                $stmt = $pdo->prepare("INSERT INTO custom_pages (slug, title, content, status) VALUES (?, ?, ?, ?)");
                try {
                    $stmt->execute([$_POST['slug'], $_POST['title'], $_POST['content'] ?? '', $_POST['status']]);
                    $_SESSION['status_message'] = '<div class="alert alert-success">Page created successfully!</div>';
                } catch (Exception $e) {
                    $_SESSION['status_message'] = '<div class="alert alert-danger">Error: Slug must be unique.</div>';
                }
            }
            $pageRedirect = 'manage_custom_pages';
            break;

        case 'edit_custom_page':
            if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'master_admin') {
                $stmt = $pdo->prepare("UPDATE custom_pages SET slug=?, title=?, content=?, status=? WHERE id=?");
                try {
                    $stmt->execute([$_POST['slug'], $_POST['title'], $_POST['content'] ?? '', $_POST['status'], $_POST['page_id']]);
                    $_SESSION['status_message'] = '<div class="alert alert-success">Page updated successfully!</div>';
                } catch (Exception $e) {
                    $_SESSION['status_message'] = '<div class="alert alert-danger">Error updating page. Slug must be unique.</div>';
                }
            }
            $pageRedirect = 'manage_custom_pages';
            break;

        case 'delete_custom_page':
            if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'master_admin') {
                $pdo->prepare("DELETE FROM custom_pages WHERE id=?")->execute([$_POST['page_id']]);
                $_SESSION['status_message'] = '<div class="alert alert-success">Page deleted successfully!</div>';
            }
            $pageRedirect = 'manage_custom_pages';
            break;

        // ==========================================
        // 7. HR, CATEGORY & OTHER MODULES
        // ==========================================
        case 'mark_attendance':
            $userId = $_POST['user_id'];
            $entryDate = $_POST['entry_date'];
            $status = $_POST['status'];
            $checkIn = !empty($_POST['check_in']) ? $_POST['check_in'] : null;
            $checkOut = !empty($_POST['check_out']) ? $_POST['check_out'] : null;
            if (markAttendance($userId, $entryDate, $status, $checkIn, $checkOut)) {
                $_SESSION['status_message'] = '<div class="alert alert-success">Attendance marked successfully!</div>';
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Failed to mark attendance.</div>';
            }
            $pageRedirect = 'manage_attendance';
            break;

        case 'add_category': 
            $stmt = $pdo->prepare("INSERT INTO categories (name, description, required_documents) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['description'], $_POST['required_documents'] ?? '']); 
            $_SESSION['status_message'] = '<div class="alert alert-success">Category added successfully!</div>';
            $pageRedirect = 'categories'; 
            break;
        case 'edit_category': 
            $is_live = isset($_POST['is_live']) ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, required_documents = ?, is_live = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['description'], $_POST['required_documents'] ?? '', $is_live, $_POST['category_id']]); 
            $_SESSION['status_message'] = '<div class="alert alert-success">Category updated successfully!</div>';
            $pageRedirect = 'categories'; 
            break;
        case 'add_subcategory':
            $stmt = $pdo->prepare("INSERT INTO subcategories (category_id, name, fare, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['category_id_sub'], $_POST['sub_name'], $_POST['fare'], $_POST['sub_description']]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Subcategory added successfully!</div>';
            $pageRedirect = 'categories';
            break;
        case 'edit_subcategory':
             $stmt = $pdo->prepare("UPDATE subcategories SET category_id = ?, name = ?, fare = ?, description = ? WHERE id = ?");
            $stmt->execute([$_POST['edit_category_id_sub'], $_POST['edit_sub_name'], $_POST['edit_fare'], $_POST['edit_sub_description'], $_POST['subcategory_id']]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Subcategory updated successfully!</div>';
            $pageRedirect = 'categories';
            break;

        case 'give_bonus_action':
             $stmt = $pdo->prepare("INSERT INTO bonuses (user_id, amount, reason) VALUES (?, ?, ?)");
             $stmt->execute([$_POST['user_id'], $_POST['amount'], $_POST['reason']]);
             $_SESSION['status_message'] = '<div class="alert alert-success">Bonus given successfully!</div>';
             $pageRedirect = 'give_bonus';
             break;
        case 'edit_bonus_action':
            $stmt = $pdo->prepare("UPDATE bonuses SET amount = ?, reason = ? WHERE id = ?");
            $stmt->execute([$_POST['amount'], $_POST['reason'], $_POST['bonus_id']]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Bonus updated successfully!</div>';
            $pageRedirect = 'give_bonus';
            break;

        case 'add_expense':
            $stmt = $pdo->prepare("INSERT INTO expenses (expense_type, amount, description, expense_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['expense_type'], $_POST['amount'], $_POST['description'], $_POST['expense_date']]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Expense added successfully!</div>';
            $pageRedirect = 'expenses';
            break;
        case 'edit_expense':
            $stmt = $pdo->prepare("UPDATE expenses SET expense_type = ?, amount = ?, description = ?, expense_date = ? WHERE id = ?");
            $stmt->execute([$_POST['expense_type'], $_POST['amount'], $_POST['description'], $_POST['expense_date'], $_POST['expense_id']]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Expense updated successfully!</div>';
            $pageRedirect = 'expenses';
            break;

        // ==========================================
        // 8. APPOINTMENTS
        // ==========================================
        case 'book_appointment':
            $docPath = null;
            if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
                $uploadDir = UPLOADS_PATH . 'client_documents/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                $fName = uniqid() . '_' . basename($_FILES['document']['name']);
                move_uploaded_file($_FILES['document']['tmp_name'], $uploadDir . $fName);
                $docPath = UPLOADS_DIR_RELATIVE . 'client_documents/' . $fName;
            }
            $stmt = $pdo->prepare("INSERT INTO appointments (client_name, client_phone, client_email, category_id, user_id, appointment_date, appointment_time, notes, document_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['client_name'], $_POST['client_phone'], $_POST['client_email'], $_POST['category_id'], $_POST['user_id'], $_POST['appointment_date'], $_POST['appointment_time'], $_POST['notes'], $docPath]);
            $_SESSION['status_message'] = '<div class="alert alert-success appointment-toast-message">Appointment booked successfully! We will contact you soon.</div>';
            $pageRedirect = 'login';
            $redirectParams = '&appointment_success=1';
            
            $appointment_id = $pdo->lastInsertId();
            if (!empty($_POST['client_email'])) {
                $appointment = fetchOne($pdo, "SELECT a.*, c.name as category_name, u.name as user_name FROM appointments a JOIN categories c ON a.category_id = c.id JOIN users u ON a.user_id = u.id WHERE a.id = ?", [$appointment_id]);
                if ($appointment) sendAppointmentConfirmationEmail($appointment);
            }
            break;

        case 'update_appointment_status':
            $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?")->execute([$_POST['status'], $_POST['appointment_id']]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Status updated.</div>';
            $pageRedirect = 'appointments';
            
            $appointment = fetchOne($pdo, "SELECT a.*, c.name as category_name, u.name as user_name FROM appointments a JOIN categories c ON a.category_id = c.id JOIN users u ON a.user_id = u.id WHERE a.id = ?", [$_POST['appointment_id']]);
            if ($appointment && !empty($appointment['client_email'])) sendAppointmentStatusUpdateEmail($appointment);
            break;

        case 'accept_appointment_and_create_task':
            $appointmentId = $_POST['appointment_id'];
            $appointment = fetchOne($pdo, "SELECT * FROM appointments WHERE id = ? AND user_id = ?", [$appointmentId, $currentUserId]);
            if ($appointment && $appointment['status'] === 'pending') {
                 $pageRedirect = 'create_task_from_appointment';
                 $redirectParams = '&appointment_id=' . $appointmentId;
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid appointment.</div>';
                $pageRedirect = 'my_appointments';
            }
            break;
            
        case 'create_task_from_appointment_submit':
            $appointmentId = $_POST['appointment_id'];
            $appointment = fetchOne($pdo, "SELECT * FROM appointments WHERE id = ?", [$appointmentId]);
            if (!$appointment || $appointment['user_id'] != $currentUserId || $appointment['status'] !== 'pending') {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid request.</div>';
                $pageRedirect = 'my_appointments';
                break;
            }
            $pdo->beginTransaction();
            try {
                $clientEmail = $appointment['client_email'];
                $existingClient = fetchOne($pdo, "SELECT id FROM clients WHERE email = ?", [$clientEmail]);
                if ($existingClient) { $clientId = $existingClient['id']; } 
                else {
                    $stmt = $pdo->prepare("INSERT INTO clients (client_name, email, phone) VALUES (?, ?, ?)");
                    $stmt->execute([$appointment['client_name'], $clientEmail, $appointment['client_phone']]);
                    $clientId = $pdo->lastInsertId();
                }
                
                $existingCustomer = fetchOne($pdo, "SELECT id FROM customers WHERE customer_email = ?", [$clientEmail]);
                if ($existingCustomer) { $customerId = $existingCustomer['id']; }
                else {
                    $stmt = $pdo->prepare("INSERT INTO customers (customer_name, customer_email, customer_phone, client_id, source) VALUES (?, ?, ?, ?, 'appointment')");
                    $stmt->execute([$appointment['client_name'], $clientEmail, $appointment['client_phone'], $clientId]);
                    $customerId = $pdo->lastInsertId();
                }

                $stmt = $pdo->prepare("INSERT INTO work_assignments (customer_id, client_id, assigned_to_user_id, assigned_by_user_id, category_id, subcategory_id, work_description, deadline, fee, fee_mode, maintenance_fee, maintenance_fee_mode, discount, task_price, attachment_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$customerId, $clientId, $currentUserId, $currentUserId, $_POST['category_id'], $_POST['subcategory_id']??NULL, $_POST['work_description']??'', $_POST['deadline'], $_POST['fee']??0, $_POST['fee_mode']??'pending', $_POST['maintenance_fee']??0, $_POST['maintenance_fee_mode']??'pending', $_POST['discount']??0, $_POST['task_price']??0, $appointment['document_path'], 'in_process']);
                $newTaskId = $pdo->lastInsertId();

                $pdo->prepare("UPDATE appointments SET status = 'confirmed', task_id = ? WHERE id = ?")->execute([$newTaskId, $appointmentId]);
                $pdo->commit();
                
                $_SESSION['status_message'] = '<div class="alert alert-success">Task created successfully!</div>';
                $pageRedirect = 'my_tasks';
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['status_message'] = '<div class="alert alert-danger">Error creating task.</div>';
                $pageRedirect = 'my_appointments';
            }
            break;

        case 'delete_appointment':
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
            $stmt->execute([$_POST['appointment_id']]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Appointment deleted successfully!</div>';
            $pageRedirect = 'appointments';
            break;
        case 'transfer_appointment':
             $appointmentId = (int)($_POST['appointment_id'] ?? 0);
             $transferToUserId = (int)($_POST['transfer_to_user_id'] ?? 0);
             $transferComments = trim($_POST['transfer_comments'] ?? '');
             if ($appointmentId > 0 && $transferToUserId > 0 && $transferToUserId != $currentUserId) {
                $stmt = $pdo->prepare("UPDATE appointments SET transfer_status = 'pending', transferred_to_user_id = ?, transfer_from_user_id = ?, transfer_comments = ?, transfer_requested_at = NOW() WHERE id = ? AND user_id = ?");
                $stmt->execute([$transferToUserId, $currentUserId, $transferComments, $appointmentId, $currentUserId]);
                $_SESSION['status_message'] = '<div class="alert alert-success">Appointment transfer request sent!</div>';
             } else {
                 $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid request.</div>';
             }
             $pageRedirect = 'my_appointments';
             break;
        case 'accept_appointment_transfer':
             $appointmentId = (int)($_POST['appointment_id'] ?? 0);
             if ($appointmentId > 0) {
                 $stmt = $pdo->prepare("UPDATE appointments SET user_id = ?, transfer_status = 'none', transferred_to_user_id = NULL, transfer_from_user_id = NULL, transfer_comments = NULL WHERE id = ? AND transferred_to_user_id = ?");
                 $stmt->execute([$currentUserId, $appointmentId, $currentUserId]);
                 $_SESSION['status_message'] = '<div class="alert alert-success">Appointment transfer accepted!</div>';
             }
             $pageRedirect = 'my_appointments';
             break;
        case 'reject_appointment_transfer':
             $appointmentId = (int)($_POST['appointment_id'] ?? 0);
             if ($appointmentId > 0) {
                 $stmt = $pdo->prepare("UPDATE appointments SET transfer_status = 'rejected', transfer_rejection_reason = ?, transferred_to_user_id = NULL, transfer_from_user_id = NULL, transfer_comments = NULL WHERE id = ? AND transferred_to_user_id = ?");
                 $stmt->execute([$_POST['rejection_reason']??'', $appointmentId, $currentUserId]);
                 $_SESSION['status_message'] = '<div class="alert alert-danger">Appointment transfer rejected.</div>';
             }
             $pageRedirect = 'my_appointments';
             break;
		// ==========================================
        // 9. RECRUITMENT POSTS
        // ==========================================
       case 'submit_recruitment_post':
            // --- 1. FILE UPLOAD LOGIC ---
            $uploadDir = UPLOADS_PATH . 'recruitment_docs/';
            if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
            $baseUrlClean = rtrim(BASE_URL, '/');

            // Handle Notification File Upload
            $notification_url = $_POST['notification_url'] ?? '';
            if (isset($_FILES['notification_file']) && $_FILES['notification_file']['error'] == 0) {
                $fName = time() . '_notif_' . basename($_FILES['notification_file']['name']);
                if (move_uploaded_file($_FILES['notification_file']['tmp_name'], $uploadDir . $fName)) {
                    $notification_url = $baseUrlClean . '/' . UPLOADS_DIR_RELATIVE . 'recruitment_docs/' . $fName; // 100% working link 
                }
            }

            // 2. Process Custom Links & Their Files
            $custom_links_json = '[]';
            if (isset($_POST['custom_link_name'])) {
                $links = [];
                foreach ($_POST['custom_link_name'] as $index => $name) {
                    $url = $_POST['custom_link_url'][$index] ?? '';
                    
                    // Handle file upload for specific custom link
                    if (isset($_FILES['custom_link_file']['name'][$index]) && $_FILES['custom_link_file']['error'][$index] == 0) {
                        $fName = time() . '_link_' . $index . '_' . basename($_FILES['custom_link_file']['name'][$index]);
                        if (move_uploaded_file($_FILES['custom_link_file']['tmp_name'][$index], $uploadDir . $fName)) {
                            $url = $baseUrlClean . '/' . UPLOADS_DIR_RELATIVE . 'recruitment_docs/' . $fName;
                        }
                    }

                    if (!empty($name)) { $links[] = ['name' => $name, 'url' => $url]; }
                }
                $custom_links_json = json_encode($links);
            }

            // 3. Process Custom Fields
            $custom_fields_json = '[]';
            if (isset($_POST['custom_heading']) && isset($_POST['custom_content'])) {
                $fields = [];
                foreach ($_POST['custom_heading'] as $index => $heading) {
                    $content = $_POST['custom_content'][$index] ?? '';
                    if (!empty($heading)) { $fields[] = ['heading' => $heading, 'content' => $content]; }
                }
                $custom_fields_json = json_encode($fields);
            }

            // 4. Process Multiple Manual Dates
            $custom_dates_json = '[]';
            if (isset($_POST['custom_date_title']) && isset($_POST['custom_date_value'])) {
                $dates = [];
                foreach ($_POST['custom_date_title'] as $index => $dTitle) {
                    $dVal = $_POST['custom_date_value'][$index] ?? '';
                    if (!empty($dTitle) && !empty($dVal)) { $dates[] = ['title' => $dTitle, 'date' => $dVal]; }
                }
                $custom_dates_json = json_encode($dates);
            }

            // 5. Process Multiple Other Details
            $other_details_json = '[]';
            if (isset($_POST['other_details_title']) && isset($_POST['other_details_content'])) {
                $details = [];
                foreach ($_POST['other_details_title'] as $index => $oTitle) {
                    $oContent = $_POST['other_details_content'][$index] ?? '';
                    if (!empty($oTitle)) { $details[] = ['title' => $oTitle, 'content' => $oContent]; }
                }
                $other_details_json = json_encode($details);
            }

            $age_limit = $_POST['age_limit'] ?? '';

            if (empty($_POST['id'])) {
                addRecruitmentPostHtml(
                    $_POST['job_title'] ?? '', $_POST['total_vacancies'] ?? '', $_POST['image_banner_url'] ?? '',
                    $_POST['eligibility_criteria'] ?? '', $_POST['selection_process'] ?? '', $_POST['start_date'] ?? '', $_POST['last_date'] ?? '',
                    $_POST['exam_date'] ?? '', $_POST['fee_payment_last_date'] ?? '', $_POST['application_fees'] ?? '', $_POST['category_wise_vacancies'] ?? '',
                    $notification_url, $_POST['apply_url'] ?? '', $_POST['admit_card_url'] ?? '', $_POST['official_website_url'] ?? '',
                    $_POST['exam_prediction'] ?? '', $custom_fields_json, $currentUserId,
                    $custom_links_json, $age_limit, $other_details_json, $custom_dates_json
                );
                $_SESSION['status_message'] = '<div class="alert alert-success fw-bold"><i class="fas fa-check-circle"></i> Post submitted successfully!</div>';
            } else {
                updateRecruitmentPostHtml(
                    $_POST['id'], $_POST['job_title'] ?? '', $_POST['total_vacancies'] ?? '', $_POST['image_banner_url'] ?? '',
                    $_POST['eligibility_criteria'] ?? '', $_POST['selection_process'] ?? '', $_POST['start_date'] ?? '', $_POST['last_date'] ?? '',
                    $_POST['exam_date'] ?? '', $_POST['fee_payment_last_date'] ?? '', $_POST['application_fees'] ?? '', $_POST['category_wise_vacancies'] ?? '',
                    $notification_url, $_POST['apply_url'] ?? '', $_POST['admit_card_url'] ?? '', $_POST['official_website_url'] ?? '',
                    $_POST['exam_prediction'] ?? '', $custom_fields_json,
                    $custom_links_json, $age_limit, $other_details_json, $custom_dates_json
                );
                 $_SESSION['status_message'] = '<div class="alert alert-success fw-bold"><i class="fas fa-check-circle"></i> Post updated successfully!</div>';
            }
            if (!isset($_POST['page'])) { $pageRedirect = 'my_recruitment_posts'; }
            break;
            
            // Redirect fix
            if (!isset($_POST['page'])) {
                $pageRedirect = 'my_recruitment_posts';
            }
            break;

        case 'approve_post':
            $postId = $_POST['post_id'];
            $post = fetchOne($pdo, "SELECT submitted_by_user_id, approval_status FROM recruitment_posts WHERE id = ?", [$postId]);
            
            if ($post && $post['approval_status'] !== 'approved') {
                updateRecruitmentPostStatus($postId, 'approved', $currentUserId);
                $earningPerPost = (float)fetchColumn($pdo, "SELECT earning_per_approved_post FROM settings WHERE id = 1");
                
                if ($earningPerPost > 0) {
                    $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                    $stmt->execute([$earningPerPost, $post['submitted_by_user_id']]);
                    addNotification($post['submitted_by_user_id'], "Your recruitment post #{$postId} has been approved! ₹{$earningPerPost} added to wallet.", "?page=my_recruitment_posts");
                }
                $_SESSION['status_message'] = '<div class="alert alert-success">Post approved and wallet updated successfully.</div>';
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-warning">Post is already approved or not found.</div>';
            }
            $pageRedirect = 'manage_recruitment_posts';
            break;

        case 'reject_post':
            updateRecruitmentPostStatus($_POST['post_id'], 'rejected', $currentUserId, $_POST['admin_comments']);
            $_SESSION['status_message'] = '<div class="alert alert-warning">Post rejected.</div>';
            $pageRedirect = 'manage_recruitment_posts';
            break;

        case 'return_post_for_edit':
            updateRecruitmentPostStatus($_POST['post_id'], 'returned_for_edit', $currentUserId, $_POST['admin_comments']);
            $_SESSION['status_message'] = '<div class="alert alert-info">Post returned for edit.</div>';
            $pageRedirect = 'manage_recruitment_posts';
            break;
            
        case 'delete_recruitment_post':
            deleteRecruitmentPost($_POST['post_id']);
            $_SESSION['status_message'] = '<div class="alert alert-success">Post deleted.</div>';
            if (!isset($_POST['page'])) {
                $pageRedirect = 'manage_recruitment_posts';
            }
            break;

        case 'add_client':
            $stmt = $pdo->prepare("INSERT INTO clients (client_name, company_name, email, phone, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['client_name'], $_POST['company_name'], $_POST['email'], $_POST['phone'], $_POST['address']]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Client added successfully!</div>';
            $pageRedirect = 'clients';
            break;

        case 'delete_client':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id > 0) {
                $check = fetchColumn($pdo, "SELECT COUNT(*) FROM customers WHERE client_id = ?", [$id]);
                if ($check > 0) {
                    $_SESSION['status_message'] = '<div class="alert alert-danger">Cannot delete client. They have linked customers.</div>';
                } else {
                    $pdo->prepare("DELETE FROM clients WHERE id = ?")->execute([$id]);
                    $_SESSION['status_message'] = '<div class="alert alert-success">Client deleted successfully.</div>';
                }
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid Client ID.</div>';
            }
            $pageRedirect = 'clients';
            break;

        case 'delete_digital_draft':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                // Fetch to check file and ownership
                $stmt = $pdo->prepare("SELECT canvas_json FROM digital_service_history WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $currentUserId]);
                $draft = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($draft) {
                    if (str_starts_with($draft['canvas_json'], 'FILE:')) {
                        $filename = str_replace('FILE:', '', $draft['canvas_json']);
                        $filepath = UPLOADS_PATH . 'drafts/' . $filename;
                        if (file_exists($filepath)) @unlink($filepath);
                    }
                    $pdo->prepare("DELETE FROM digital_service_history WHERE id = ? AND user_id = ?")->execute([$id, $currentUserId]);
                    $_SESSION['status_message'] = '<div class="alert alert-success fw-bold"><i class="fas fa-check-circle"></i> Draft deleted successfully!</div>';
                }
            }
            $pageRedirect = 'digital_drafts';
            break;

        default:
            $_SESSION['status_message'] = '<div class="alert alert-warning">Unknown Action: ' . htmlspecialchars($action) . '</div>';
            break;
	// =======================================================
    // 10. DIGITAL SERVICES B2C MONETIZATION
    // =======================================================
        case 'update_service_rate':
            if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['master_admin', 'admin'])) {
                $rate_id = (int)$_POST['rate_id'];
                $price = (float)$_POST['price'];
                $points_price = (int)$_POST['points_price'];
                $pdo->prepare("UPDATE digital_service_rates SET price = ?, points_price = ? WHERE id = ?")->execute([$price, $points_price, $rate_id]);
                $_SESSION['status_message'] = '<div class="alert alert-success">Service rates updated successfully.</div>';
            }
            $pageRedirect = 'manage_service_rates';
            break;

        case 'save_b2c_plan':
            if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['master_admin', 'admin'])) {
                $plan_id = !empty($_POST['plan_id']) ? (int)$_POST['plan_id'] : 0;
                $plan_name = trim($_POST['plan_name']);
                $description = trim($_POST['description']);
                $price = (float)$_POST['price'];
                $validity_days = (int)$_POST['validity_days'];
                $services = isset($_POST['services']) ? json_encode($_POST['services']) : '[]';

                if ($plan_id > 0) {
                    $pdo->prepare("UPDATE b2c_subscription_plans SET plan_name = ?, description = ?, price = ?, validity_days = ?, allowed_services = ? WHERE id = ?")
                        ->execute([$plan_name, $description, $price, $validity_days, $services, $plan_id]);
                    $_SESSION['status_message'] = '<div class="alert alert-success">B2C Plan updated successfully.</div>';
                } else {
                    $pdo->prepare("INSERT INTO b2c_subscription_plans (plan_name, description, price, validity_days, allowed_services) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$plan_name, $description, $price, $validity_days, $services]);
                    $_SESSION['status_message'] = '<div class="alert alert-success">New B2C Plan created successfully.</div>';
                }
            }
            $pageRedirect = 'manage_b2c_subscriptions';
            break;

        case 'toggle_b2c_plan':
            if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['master_admin', 'admin'])) {
                $plan_id = (int)$_POST['plan_id'];
                $pdo->prepare("UPDATE b2c_subscription_plans SET is_active = NOT is_active WHERE id = ?")->execute([$plan_id]);
                $_SESSION['status_message'] = '<div class="alert alert-success">Plan status toggled.</div>';
            }
            $pageRedirect = 'manage_b2c_subscriptions';
            break;

        case 'buy_b2c_plan':
            $plan_id = (int)($_POST['plan_id'] ?? 0);
            $payment_id = $_POST['payment_id'] ?? '';
            $user_id = $_SESSION['user_id'];
            
            if ($plan_id > 0 && !empty($payment_id)) {
                $plan = fetchOne($pdo, "SELECT * FROM b2c_subscription_plans WHERE id = ?", [$plan_id]);
                if ($plan) {
                    $start_date = date('Y-m-d H:i:s');
                    $end_date = date('Y-m-d H:i:s', strtotime("+{$plan['validity_days']} days"));
                    
                    // Cancel any existing active subscription for this user to restart them on the new plan
                    $pdo->prepare("UPDATE user_subscriptions SET status = 'cancelled' WHERE user_id = ? AND status = 'active'")->execute([$user_id]);
                    
                    // Insert new subscription
                    $pdo->prepare("INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')")
                        ->execute([$user_id, $plan_id, $start_date, $end_date]);
                        
                    // Log the revenue transaction natively
                    $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, 'credit_system', ?, ?)")
                        ->execute([$user_id, $plan['price'], "Purchased B2C Plan: " . $plan['plan_name']]);

                    $_SESSION['status_message'] = '<div class="alert alert-success">🎉 Subscription activated! You now have unlimited access to all services in this plan.</div>';
                }
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Payment verification failed.</div>';
            }
            // User gets redirected to their dashboard or the same page
            $pageRedirect = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['retailer', 'freelancer', 'deo']) ? 'retailer_dashboard' : 'buy_subscription';
            break;

	// =======================================================
    // 🚀 WALLET RECHARGE SYSTEM LOGIC 🚀
    // =======================================================

    // 1. When the user sends a request 
    case 'submit_recharge_request':
        $amount = (float)$_POST['amount'];
        $user_id = $_SESSION['user_id'];
        
        // Folder to save the photo
        $upload_dir = UPLOADS_PATH . 'recharge_proofs/';
        if (!is_dir($upload_dir)) { 
            mkdir($upload_dir, 0777, true); 
        }
        
        $file_name = null;
        if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
            $file_ext = pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION);
            $file_name = 'proof_' . $user_id . '_' . time() . '.' . $file_ext;
            move_uploaded_file($_FILES['screenshot']['tmp_name'], $upload_dir . $file_name);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO wallet_recharge_requests (user_id, amount, screenshot_path, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $amount, $file_name]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Your recharge request has been successfully sent to admin.</div>';
        } catch (Exception $e) {
            $_SESSION['status_message'] = '<div class="alert alert-danger">Error: Request could not be saved.</div>';
        }
        
        header('Location: ' . BASE_URL . '?page=wallet_recharge');
        exit;

    // 2. When the admin approves or rejects the request
    case 'process_recharge_request':
        if ($_SESSION['user_role'] !== 'admin') {
            header('Location: ' . BASE_URL . '?page=dashboard');
            exit;
        }
        
        $request_id = (int)$_POST['request_id'];
        $status = $_POST['status']; // 'approved' or 'rejected'

        try {
            // Check whether the request is pending or not
            $req = fetchOne($pdo, "SELECT * FROM wallet_recharge_requests WHERE id = ? AND status = 'pending'", [$request_id]);
            
            if ($req) {
                // Update the status
                $pdo->prepare("UPDATE wallet_recharge_requests SET status = ? WHERE id = ?")->execute([$status, $request_id]);

                // If admin approved then deposit money in wallet
                if ($status === 'approved') {
                    // Add to the user's balance
                    $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$req['amount'], $req['user_id']]);
                    
                    // Make an entry for the report (Passbook).
                    $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, 'credit', ?, 'Online Wallet Recharge Approved')")->execute([$req['user_id'], $req['amount']]);
                }
                $_SESSION['status_message'] = '<div class="alert alert-success">Request successfully ' . ucfirst($status) . ' is done.</div>';
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-warning">This request has already been processed.</div>';
            }
        } catch (Exception $e) {
            $_SESSION['status_message'] = '<div class="alert alert-danger">System Error: ' . $e->getMessage() . '</div>';
        }
        
        header('Location: ' . BASE_URL . '?page=admin_wallet_requests');
        exit;

        // ==========================================
        // B2C SLIDER MANAGEMENT
        // ==========================================
        case 'add_b2c_slider':
            if (!in_array($_SESSION['user_role'] ?? '', ['admin', 'master_admin'])) throw new Exception('Access Denied.');
            $title = $_POST['title'] ?? '';
            $desc = $_POST['description'] ?? '';
            $link = $_POST['link'] ?? '';
            $display_order = (int)($_POST['display_order'] ?? 0);
            $mediaType = $_POST['media_type'] ?? 'image';
            $status = $_POST['status'] ?? 'active';
            $mediaPath = '';

            if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
                $uploadDir = UPLOADS_PATH . 'b2c_hero/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES['media']['name']);
                if (move_uploaded_file($_FILES['media']['tmp_name'], $uploadDir . $fileName)) {
                    $mediaPath = UPLOADS_DIR_RELATIVE . 'b2c_hero/' . $fileName;
                }
            }

            if ($mediaPath) {
                $stmt = $pdo->prepare("INSERT INTO b2c_sliders (title, description, link, media_type, media_path, status, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $desc, $link, $mediaType, $mediaPath, $status, $display_order]);
                $_SESSION['status_message'] = '<div class="alert alert-success">Slider added successfully!</div>';
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Please upload a media file.</div>';
            }
            $pageRedirect = 'manage_b2c_sliders';
            break;

        case 'edit_b2c_slider':
            if (!in_array($_SESSION['user_role'] ?? '', ['admin', 'master_admin'])) throw new Exception('Access Denied.');
            $id = (int)$_POST['slider_id'];
            $title = $_POST['title'] ?? '';
            $desc = $_POST['description'] ?? '';
            $link = $_POST['link'] ?? '';
            $display_order = (int)($_POST['display_order'] ?? 0);
            $mediaType = $_POST['media_type'] ?? 'image';
            $status = $_POST['status'] ?? 'active';

            $mediaQueryPart = "";
            $params = [$title, $desc, $link, $mediaType, $status, $display_order];

            if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
                $uploadDir = UPLOADS_PATH . 'b2c_hero/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES['media']['name']);
                if (move_uploaded_file($_FILES['media']['tmp_name'], $uploadDir . $fileName)) {
                    $mediaPath = UPLOADS_DIR_RELATIVE . 'b2c_hero/' . $fileName;
                    $mediaQueryPart = ", media_path = ?";
                    $params[] = $mediaPath;
                }
            }

            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE b2c_sliders SET title=?, description=?, link=?, media_type=?, status=?, display_order=? {$mediaQueryPart} WHERE id=?");
            $stmt->execute($params);
            $_SESSION['status_message'] = '<div class="alert alert-success">Slider updated successfully!</div>';
            $pageRedirect = 'manage_b2c_sliders';
            break;

        case 'delete_b2c_slider':
            if (!in_array($_SESSION['user_role'] ?? '', ['admin', 'master_admin'])) throw new Exception('Access Denied.');
            $sliderId = (int)$_POST['slider_id'];
            $pdo->prepare("DELETE FROM b2c_sliders WHERE id = ?")->execute([$sliderId]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Slider deleted!</div>';
            $pageRedirect = 'manage_b2c_sliders';
            break;

    } // end switch
} catch (Exception $e) {
    error_log("Error in actions.php: " . $e->getMessage());
    $_SESSION['status_message'] = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

header("Location: " . BASE_URL . "?page=" . $pageRedirect . $redirectParams);
exit;
?>