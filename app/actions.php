<?php
/**
 * app/actions.php
 * This is the "brain" of the application, a master file to handle all POST requests.
 * It keeps index.php clean and centralizes all data processing logic.
 * This is the final and most comprehensive version with all required actions fully implemented.
 * UPDATED: Includes Freelancer Payment & Balance Calculation Logic.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    die('Invalid request method.');
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load all necessary files
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

// Default redirect page is the one the form was submitted from, or a sensible default.
$pageRedirect = $_POST['page'] ?? 'dashboard';
$redirectParams = '';

try {
    // Actions that can be performed without being logged in
    $public_actions = ['login_submit', 'book_appointment'];
    if (!$currentUserId && !in_array($action, $public_actions)) {
        throw new Exception('You must be logged in to perform this action.');
    }

    switch ($action) {

        // --- AUTHENTICATION ---
        case 'login_submit':
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            if (loginUser($email, $password)) {
                $role = $_SESSION['user_role'] ?? 'guest';
                $dashboard_page = 'user_dashboard';
                if ($role === 'admin') $dashboard_page = 'master_dashboard';
                elseif ($role === 'hr') $dashboard_page = 'hr_dashboard';
                elseif ($role === 'accountant') $dashboard_page = 'accountant_dashboard';
                elseif (in_array($role, ['deo', 'freelancer', 'data_entry_operator'])) $dashboard_page = 'worker_dashboard';
                else $dashboard_page = 'user_dashboard';
                $pageRedirect = $dashboard_page;
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid credentials or inactive account.</div>';
                $pageRedirect = 'login';
            }
            break;

        // --- USER & PROFILE MANAGEMENT ---
        case 'register_user':
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['email'], $hashedPassword, $_POST['role_id']]);
            $_SESSION['status_message'] = '<div class="alert alert-success">User registered successfully!</div>';
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
            $userIdToDelete = (int)($_POST['user_id'] ?? 0);
            if ($userIdToDelete > 1 && $userIdToDelete != $currentUserId) {
                $pdo->beginTransaction();
                try {
                    // Re-assign tasks to the main admin (user_id = 1)
                    $stmt_unassign = $pdo->prepare("UPDATE work_assignments SET assigned_to_user_id = 1 WHERE assigned_to_user_id = ?");
                    $stmt_unassign->execute([$userIdToDelete]);

                    // Delete the user
                    $stmt_delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt_delete->execute([$userIdToDelete]);

                    $pdo->commit();
                    $_SESSION['status_message'] = '<div class="alert alert-success">User deleted successfully. Their tasks have been reassigned to the admin.</div>';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger">This user cannot be deleted.</div>';
            }
            $pageRedirect = 'users';
            break;
            
        case 'update_profile':
            $name = trim($_POST['name']);
            if (empty($name)) {
                throw new Exception('Name is required.');
            }
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $currentUserId]);
            $_SESSION['user_name'] = $name; // Update session
            $_SESSION['status_message'] = '<div class="alert alert-success">Profile updated successfully!</div>';
            break;

        case 'change_password':
            $newPassword = $_POST['new_password'];
            if (strlen($newPassword) < 6) {
                throw new Exception('Password must be at least 6 characters long.');
            }
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
                    $uploadDir = ROOT_PATH . 'uploads/profile_pictures/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $fileName = 'user_' . $currentUserId . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filePath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filePath)) {
                        $webPath = 'uploads/profile_pictures/' . $fileName;
                        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                        $stmt->execute([$webPath, $currentUserId]);
                        $_SESSION['user_profile_picture'] = $webPath; // Update session with relative path
                        $_SESSION['status_message'] = '<div class="alert alert-success">Profile picture updated!</div>';
                    } else {
                        $_SESSION['status_message'] = '<div class="alert alert-danger">Failed to upload file.</div>';
                    }
                } else {
                    $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid file type. Please upload JPG, PNG, or GIF.</div>';
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
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET bank_name = :bank_name,
                        account_holder_name = :account_holder_name,
                        account_number = :account_number,
                        ifsc_code = :ifsc_code,
                        upi_id = :upi_id
                    WHERE id = :user_id
                ");
                $stmt->execute([
                    ':bank_name' => $bankName,
                    ':account_holder_name' => $accountHolderName,
                    ':account_number' => $accountNumber,
                    ':ifsc_code' => $ifscCode,
                    ':upi_id' => $upiId,
                    ':user_id' => $currentUserId
                ]);
                $_SESSION['status_message'] = '<div class="alert alert-success" role="alert">Your bank details have been saved successfully!</div>';
            }
            $pageRedirect = 'bank_details';
            break;

        // --- ROLE MANAGEMENT ---
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
            
        // --- EXPENSE MANAGEMENT ---
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
            
        // --- APPOINTMENT MANAGEMENT ---
        case 'book_appointment':
            $documentPath = null;
            if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = ROOT_PATH . 'uploads/client_documents/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                $fileName = uniqid() . '-' . basename($_FILES['document']['name']);
                if (move_uploaded_file($_FILES['document']['tmp_name'], $uploadDir . $fileName)) {
                    $documentPath = 'uploads/client_documents/' . $fileName;
                }
            }
            $stmt = $pdo->prepare("INSERT INTO appointments (client_name, client_phone, client_email, category_id, user_id, appointment_date, appointment_time, notes, document_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['client_name'], $_POST['client_phone'], $_POST['client_email'], $_POST['category_id'], $_POST['user_id'], $_POST['appointment_date'], $_POST['appointment_time'], $_POST['notes'], $documentPath]);
            
            $_SESSION['status_message'] = '<div class="alert alert-success appointment-toast-message">Appointment booked successfully! We will contact you soon.</div>';
            $pageRedirect = 'login';
            $redirectParams = '&appointment_success=1';

            // Send email confirmation to the customer
            $appointment_id = $pdo->lastInsertId();
            if (!empty($_POST['client_email'])) {
                $appointment = fetchOne($pdo, "
                    SELECT a.*, c.name as category_name, u.name as user_name
                    FROM appointments a
                    JOIN categories c ON a.category_id = c.id
                    JOIN users u ON a.user_id = u.id
                    WHERE a.id = ?
                ", [$appointment_id]);
                if ($appointment) {
                    sendAppointmentConfirmationEmail($appointment);
                }
            }
            break;
            
        case 'update_appointment_status':
            $appointmentId = $_POST['appointment_id'];
            $newStatus = $_POST['status'];
            $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $appointmentId]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Appointment status updated to '.ucfirst($newStatus).' successfully!</div>';
            $pageRedirect = 'appointments';
            
            // Send email notification to the customer
            $appointment = fetchOne($pdo, "
                SELECT a.*, c.name as category_name, u.name as user_name
                FROM appointments a
                JOIN categories c ON a.category_id = c.id
                JOIN users u ON a.user_id = u.id
                WHERE a.id = ?
            ", [$appointmentId]);
            if ($appointment && !empty($appointment['client_email'])) {
                sendAppointmentStatusUpdateEmail($appointment);
            }
            break;
            
        case 'accept_appointment_and_create_task':
            $appointmentId = $_POST['appointment_id'];
            $appointment = fetchOne($pdo, "
                SELECT a.*, c.name as category_name, c.description as category_description, c.required_documents, u.name as user_name
                FROM appointments a
                JOIN categories c ON a.category_id = c.id
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.id = ? AND a.user_id = ?
            ", [$appointmentId, $currentUserId]);

            if ($appointment && $appointment['status'] === 'pending') {
                 $pageRedirect = 'create_task_from_appointment';
                 $redirectParams = '&appointment_id=' . $appointmentId;
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Appointment not found or already confirmed.</div>';
                $pageRedirect = 'my_appointments';
            }
            break;

        case 'create_task_from_appointment_submit':
            $appointmentId = $_POST['appointment_id'];
            $appointment = fetchOne($pdo, "SELECT * FROM appointments WHERE id = ?", [$appointmentId]);

            if (!$appointment || $appointment['user_id'] != $currentUserId || $appointment['status'] !== 'pending') {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid request. Task could not be created.</div>';
                $pageRedirect = 'my_appointments';
                break;
            }

            $pdo->beginTransaction();
            try {
                $clientEmail = $appointment['client_email'];
                $clientName = $appointment['client_name'];
                $clientPhone = $appointment['client_phone'];

                $clientId = null;
                $customerId = null;

                // First, check for an existing customer
                $existingCustomer = fetchOne($pdo, "SELECT id, client_id FROM customers WHERE customer_email = ?", [$clientEmail]);

                if ($existingCustomer) {
                    $customerId = $existingCustomer['id'];
                    $clientId = $existingCustomer['client_id'];

                    // If a customer exists but their client_id is missing, try to find a matching client
                    if (!$clientId) {
                        $existingClient = fetchOne($pdo, "SELECT id FROM clients WHERE email = ?", [$clientEmail]);
                        if ($existingClient) {
                            $clientId = $existingClient['id'];
                            // Update the customer record with the found client_id
                            $stmt_update_customer = $pdo->prepare("UPDATE customers SET client_id = ? WHERE id = ?");
                            $stmt_update_customer->execute([$clientId, $customerId]);
                        }
                    }
                }

                // If no customer was found, check for an existing client
                if (!$clientId) {
                    $existingClient = fetchOne($pdo, "SELECT id FROM clients WHERE email = ?", [$clientEmail]);
                    if ($existingClient) {
                        $clientId = $existingClient['id'];
                    } else {
                        // If no client exists, create a new one
                        $stmt = $pdo->prepare("INSERT INTO clients (client_name, email, phone) VALUES (?, ?, ?)");
                        $stmt->execute([$clientName, $clientEmail, $clientPhone]);
                        $clientId = $pdo->lastInsertId();
                    }
                }

                // If no customer was found, create a new one, linking it to the (new or existing) client
                if (!$customerId) {
                    $stmt = $pdo->prepare("INSERT INTO customers (customer_name, customer_email, customer_phone, client_id, source) VALUES (?, ?, ?, ?, 'appointment')");
                    $stmt->execute([$clientName, $clientEmail, $clientPhone, $clientId]);
                    $customerId = $pdo->lastInsertId();
                }

                $attachmentPath = null;
                if (isset($_FILES['task_attachment']) && $_FILES['task_attachment']['error'] == UPLOAD_ERR_OK) {
                    $uploadDir = ROOT_PATH . 'uploads/task_attachments/';
                    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                    $fileName = time() . '_' . basename($_FILES['task_attachment']['name']);
                    if (move_uploaded_file($_FILES['task_attachment']['tmp_name'], $uploadDir . $fileName)) {
                        $attachmentPath = 'uploads/task_attachments/' . $fileName;
                    }
                } else {
                    if (!empty($appointment['document_path'])) {
                        $attachmentPath = $appointment['document_path'];
                    }
                }
                
                $feeMode = $_POST['fee_mode'] ?? 'pending';
                $maintenanceFee = $_POST['maintenance_fee'] ?? 0;
                $maintenanceFeeMode = $_POST['maintenance_fee_mode'] ?? 'pending';

                $stmt = $pdo->prepare("INSERT INTO work_assignments (customer_id, client_id, assigned_to_user_id, assigned_by_user_id, category_id, subcategory_id, work_description, deadline, fee, fee_mode, maintenance_fee, maintenance_fee_mode, discount, task_price, attachment_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $customerId,
                    $clientId,
                    $currentUserId,
                    $currentUserId,
                    $_POST['category_id'],
                    $_POST['subcategory_id'] ?? NULL,
                    $_POST['work_description'] ?? NULL,
                    $_POST['deadline'],
                    $_POST['fee'] ?? 0,
                    $feeMode,
                    $maintenanceFee,
                    $maintenanceFeeMode,
                    $_POST['discount'] ?? 0,
                    $_POST['task_price'] ?? 0,
                    $attachmentPath,
                    'in_process'
                ]);
                $newTaskId = $pdo->lastInsertId();

                $stmt = $pdo->prepare("UPDATE appointments SET status = 'confirmed', task_id = ? WHERE id = ?");
                $stmt->execute([$newTaskId, $appointmentId]);

                $pdo->commit();

                addNotification($currentUserId, "New task #{$newTaskId} has been created from your appointment.", "?page=update_task&id={$newTaskId}");
                
                $newTask = fetchOne($pdo, "
                    SELECT wa.*, cu.customer_email, cu.customer_name, cat.name as category_name, sub.name as subcategory_name
                    FROM work_assignments wa
                    LEFT JOIN customers cu ON wa.customer_id = cu.id
                    LEFT JOIN categories cat ON wa.category_id = cat.id
                    LEFT JOIN subcategories sub ON wa.subcategory_id = sub.id
                    WHERE wa.id = ?
                ", [$newTaskId]);
                
                if ($newTask && !empty($newTask['customer_email'])) {
                    sendTaskStatusUpdateEmail($newTask);
                }

                $_SESSION['status_message'] = '<div class="alert alert-success">Appointment accepted and new task created successfully!</div>';
                $pageRedirect = 'my_tasks';
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Error creating task from appointment: " . $e->getMessage());
                $_SESSION['status_message'] = '<div class="alert alert-danger">An error occurred. Task could not be created.</div>';
                $pageRedirect = 'my_appointments';
            }
            break;

        case 'delete_appointment':
            $appointmentId = $_POST['appointment_id'];
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
            $stmt->execute([$appointmentId]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Appointment deleted successfully!</div>';
            $pageRedirect = 'appointments';
            break;
            
        case 'transfer_appointment':
             $appointmentId = (int)($_POST['appointment_id'] ?? 0);
             $transferToUserId = (int)($_POST['transfer_to_user_id'] ?? 0);
             $transferComments = trim($_POST['transfer_comments'] ?? '');

             if ($appointmentId > 0 && $transferToUserId > 0 && $transferToUserId != $currentUserId) {
                $targetUser = fetchOne($pdo, "SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ? AND r.role_name != 'admin'", [$transferToUserId]);

                if ($targetUser) {
                    $stmt = $pdo->prepare("UPDATE appointments SET transfer_status = 'pending', transferred_to_user_id = ?, transfer_from_user_id = ?, transfer_comments = ?, transfer_requested_at = NOW() WHERE id = ? AND user_id = ?");
                    $stmt->execute([$transferToUserId, $currentUserId, $transferComments, $appointmentId, $currentUserId]);
                    $_SESSION['status_message'] = '<div class="alert alert-success">Appointment transfer request sent successfully!</div>';
                } else {
                    $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid user selected for transfer.</div>';
                }
             } else {
                 $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid appointment or user selected for transfer.</div>';
             }
             $pageRedirect = 'my_appointments';
             break;
        case 'accept_appointment_transfer':
             $appointmentId = (int)($_POST['appointment_id'] ?? 0);
             if ($appointmentId > 0) {
                 $stmt = $pdo->prepare("UPDATE appointments SET user_id = ?, transfer_status = 'none', transferred_to_user_id = NULL, transfer_from_user_id = NULL, transfer_comments = NULL WHERE id = ? AND transferred_to_user_id = ?");
                 $stmt->execute([$currentUserId, $appointmentId, $currentUserId]);
                 $_SESSION['status_message'] = '<div class="alert alert-success">Appointment transfer accepted successfully!</div>';
             } else {
                 $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid appointment selected for transfer.</div>';
             }
             $pageRedirect = 'my_appointments';
             break;
        case 'reject_appointment_transfer':
             $appointmentId = (int)($_POST['appointment_id'] ?? 0);
             $rejectionReason = trim($_POST['rejection_reason'] ?? '');
             if ($appointmentId > 0) {
                 $stmt = $pdo->prepare("UPDATE appointments SET transfer_status = 'rejected', transfer_rejection_reason = ?, transferred_to_user_id = NULL, transfer_from_user_id = NULL, transfer_comments = NULL WHERE id = ? AND transferred_to_user_id = ?");
                 $stmt->execute([$rejectionReason, $appointmentId, $currentUserId]);
                 $_SESSION['status_message'] = '<div class="alert alert-danger">Appointment transfer rejected.</div>';
             } else {
                 $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid appointment selected for transfer.</div>';
             }
             $pageRedirect = 'my_appointments';
             break;
            
        // --- CLIENT MANAGEMENT ---
        case 'add_client':
            $stmt = $pdo->prepare("INSERT INTO clients (client_name, company_name, email, phone, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['client_name'], $_POST['company_name'], $_POST['email'], $_POST['phone'], $_POST['address']]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Client added successfully!</div>';
            $pageRedirect = 'clients';
            break;
        case 'edit_client':
            $stmt = $pdo->prepare("UPDATE clients SET client_name = ?, company_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$_POST['client_name'], $_POST['company_name'], $_POST['email'], $_POST['phone'], $_POST['address'], $_POST['client_id']]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Client updated successfully!</div>';
            $pageRedirect = 'clients';
            break;
        case 'delete_client':
            $id = $_POST['client_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['status_message'] = '<div class="alert alert-success">Client deleted successfully!</div>';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                     $_SESSION['status_message'] = '<div class="alert alert-danger">Error: Client cannot be deleted because there are tasks associated with them.</div>';
                } else {
                    $_SESSION['status_message'] = '<div class="alert alert-danger">Error deleting client. Please check server logs for details.</div>';
                }
            }
            $pageRedirect = 'clients';
            break;
            
        // --- CUSTOMER MANAGEMENT ---
        case 'add_customer':
            $clientId = !empty($_POST['client_id']) ? $_POST['client_id'] : null;
            $stmt = $pdo->prepare("INSERT INTO customers (customer_name, customer_phone, customer_email, customer_address, client_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['customer_name'], $_POST['customer_phone'], $_POST['customer_email'], $_POST['customer_address'], $clientId]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Customer added successfully!</div>';
            $pageRedirect = 'customers';
            break;
        case 'edit_customer':
            $clientId = !empty($_POST['client_id']) ? $_POST['client_id'] : null;
            $stmt = $pdo->prepare("UPDATE customers SET customer_name = ?, customer_phone = ?, customer_email = ?, customer_address = ?, client_id = ? WHERE id = ?");
            $stmt->execute([$_POST['customer_name'], $_POST['customer_phone'], $_POST['customer_email'], $_POST['customer_address'], $clientId, $_POST['customer_id']]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Customer updated successfully!</div>';
            $pageRedirect = 'customers';
            break;
        case 'delete_customer':
            $id = $_POST['customer_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
                $stmt->execute([$id]);
                 $_SESSION['status_message'] = '<div class="alert alert-success">Customer deleted successfully!</div>';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                     $_SESSION['status_message'] = '<div class="alert alert-danger">Error: Customer cannot be deleted because there are tasks associated with them.</div>';
                } else {
                    $_SESSION['status_message'] = '<div class="alert alert-danger">Error deleting customer. Please check server logs for details.</div>';
                }
            }
            $pageRedirect = 'customers';
            break;

        // --- TASK MANAGEMENT (ADMIN) ---
        case 'assign_task':
            $attachmentPath = null;
            if (isset($_FILES['task_attachment']) && $_FILES['task_attachment']['error'] == UPLOAD_ERR_OK) {
                $uploadDir = ROOT_PATH . 'uploads/task_attachments/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES['task_attachment']['name']);
                if (move_uploaded_file($_FILES['task_attachment']['tmp_name'], $uploadDir . $fileName)) {
                    $attachmentPath = 'uploads/task_attachments/' . $fileName;
                }
            }
            
            $clientId = $_POST['client_id'] ?? null;
            if (empty($clientId) && !empty($_POST['customer_id'])) {
                $customer = fetchOne($pdo, "SELECT client_id FROM customers WHERE id = ?", [$_POST['customer_id']]);
                $clientId = $customer['client_id'] ?? null;
            }
            if (empty($clientId)) {
                 $_SESSION['status_message'] = '<div class="alert alert-danger">Error: Client ID is required to assign a task.</div>';
                 $pageRedirect = 'assign_task';
                 break;
            }
            
            $stmt = $pdo->prepare("INSERT INTO work_assignments (customer_id, client_id, assigned_to_user_id, assigned_by_user_id, category_id, subcategory_id, work_description, deadline, fee, fee_mode, maintenance_fee, maintenance_fee_mode, discount, task_price, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['customer_id'] ?? null, 
                $clientId, 
                $_POST['assigned_to_user_id'], 
                $currentUserId, 
                $_POST['category_id'], 
                $_POST['subcategory_id'] ?? null, 
                $_POST['work_description'] ?? '', 
                $_POST['deadline'], 
                $_POST['fee'] ?? 0, 
                $_POST['fee_mode'] ?? 'pending', 
                $_POST['maintenance_fee'] ?? 0, 
                $_POST['maintenance_fee_mode'] ?? 'pending', 
                $_POST['discount'] ?? 0, 
                $_POST['task_price'] ?? 0, 
                $attachmentPath
            ]);
            
            $newTaskId = $pdo->lastInsertId();
            addNotification($_POST['assigned_to_user_id'], "You have been assigned a new task #{$newTaskId}.", "?page=update_task&id={$newTaskId}");

            $_SESSION['status_message'] = '<div class="alert alert-success">Task assigned successfully!</div>';
            $pageRedirect = 'all_tasks';
            break;

        case 'update_task':
            $taskId = $_POST['task_id'];
            $newStatus = $_POST['status'];
            $assignedUserId = $_POST['assigned_to_user_id'];
            
            // [NEW] Fetch OLD Status to check for transition
            $oldTask = fetchOne($pdo, "SELECT status, is_verified FROM work_assignments WHERE id = ?", [$taskId]);

            $isVerified = 0;
            $completionDate = NULL;
            // FIXED: Change 'completed' to 'verified_completed' for consistency
            if ($newStatus === 'verified_completed') {
                $isVerified = 1;
                $completionDate = date('Y-m-d');
            } else if ($newStatus === 'returned') {
                 $isVerified = 0;
                 $completionDate = NULL;
            }
            
            $subcategory_id = empty($_POST['subcategory_id']) ? null : $_POST['subcategory_id'];
            $work_description = empty($_POST['work_description']) ? null : $_POST['work_description'];
            $admin_notes = empty($_POST['admin_notes']) ? null : $_POST['admin_notes'];
            
            // [FIXED] Use null coalescing operator to provide a default value if the key is not set.
            $paymentStatus = $_POST['payment_status'] ?? 'pending';

            // FIXED: Added missing parameters to the update query and updated values.
            $stmt = $pdo->prepare("
                UPDATE work_assignments SET
                client_id=?, assigned_to_user_id=?, category_id=?, subcategory_id=?, work_description=?, deadline=?,
                fee=?, fee_mode=?, maintenance_fee=?, maintenance_fee_mode=?, discount=?, task_price=?,
                status=?, payment_status=?, admin_notes=?, is_verified=?, completion_date=?
                WHERE id=?
            ");
            $stmt->execute([
                $_POST['client_id'], $assignedUserId, $_POST['category_id'], $subcategory_id, $work_description, $_POST['deadline'],
                $_POST['fee'], $_POST['fee_mode'], $_POST['maintenance_fee'], $_POST['maintenance_fee_mode'], $_POST['discount'], $_POST['task_price'],
                $newStatus, $paymentStatus, $admin_notes, $isVerified, $completionDate, $taskId
            ]);

            // --- [NEW LOGIC START] FREELANCER BALANCE UPDATE ---
            // Only update balance if task is JUST marked as 'verified_completed' and wasn't before
            if ($newStatus === 'verified_completed' && $oldTask['status'] !== 'verified_completed') {
                
                // Fetch financial details again to be sure
                $taskData = fetchOne($pdo, "SELECT fee, task_price, payment_collected_by FROM work_assignments WHERE id = ?", [$taskId]);
                
                if ($taskData) {
                    $totalFee = (float)$taskData['fee'];       // Total Amount from Customer
                    $freelancerFee = (float)$taskData['task_price']; // Freelancer's Share
                    $collectedBy = $taskData['payment_collected_by'];
                    $balanceChange = 0;

                    if ($collectedBy === 'company') {
                        // Case 1: Company collected money.
                        // Company owes Freelancer their share.
                        // Action: CREDIT Freelancer Wallet
                        $balanceChange = $freelancerFee;
                    } elseif ($collectedBy === 'self') {
                        // Case 2: Freelancer collected money (Total Fee).
                        // Freelancer keeps their share, but owes Company the rest.
                        // Action: DEBIT Company Share from Freelancer Wallet
                        // Company Share = Total - FreelancerShare
                        $companyShare = $totalFee - $freelancerFee;
                        $balanceChange = -($companyShare);
                    }

                    // Execute Balance Update
                    if ($balanceChange != 0) {
                        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$balanceChange, $assignedUserId]);
                    }
                }
            }
            // --- [NEW LOGIC END] ---

            $notificationMessage = "The status of your task #{$taskId} has been updated to {$newStatus}.";
            if ($newStatus === 'verified_completed') {
                $notificationMessage = "Your task #{$taskId} has been verified and marked as completed!";
            } else if ($newStatus === 'returned') {
                $notificationMessage = "Your task #{$taskId} has been returned by the admin for further work. Please check the admin notes.";
            }
            addNotification($assignedUserId, $notificationMessage, "?page=update_task&id={$taskId}");

            if ($newStatus === 'verified_completed') {
                $task = fetchOne($pdo, "
                    SELECT wa.*, cu.customer_email, cu.customer_name, cat.name as category_name, sub.name as subcategory_name
                    FROM work_assignments wa
                    LEFT JOIN customers cu ON wa.customer_id = cu.id
                    LEFT JOIN categories cat ON wa.category_id = cat.id
                    LEFT JOIN subcategories sub ON wa.subcategory_id = sub.id
                    WHERE wa.id = ?
                ", [$taskId]);

                if ($task && !empty($task['customer_email'])) {
                    sendTaskStatusUpdateEmail($task);
                }
            }

            $_SESSION['status_message'] = '<div class="alert alert-success">Task updated successfully!</div>';
            $pageRedirect = 'all_tasks';
            break;

            
        // --- TASK MANAGEMENT (USER) ---
        case 'request_transfer':
            $taskId = (int)($_POST['task_id'] ?? 0);
            $transferToUserId = (int)($_POST['transfer_to_user_id'] ?? 0);
            $transferComments = trim($_POST['transfer_comments'] ?? '');

            if ($taskId > 0 && $transferToUserId > 0) {
                $stmt = $pdo->prepare("UPDATE work_assignments SET transfer_status = 'pending', transferred_to_user_id = ?, transfer_from_user_id = ?, transfer_comments = ?, transfer_requested_at = NOW() WHERE id = ? AND assigned_to_user_id = ?");
                $stmt->execute([$transferToUserId, $currentUserId, $transferComments, $taskId, $currentUserId]);
                $_SESSION['status_message'] = '<div class="alert alert-success">Task transfer request sent successfully!</div>';
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid task or user selected for transfer.</div>';
            }
            $pageRedirect = 'my_tasks';
            break;
            
        case 'accept_transfer':
            $taskId = (int)($_POST['task_id'] ?? 0);
            if ($taskId > 0) {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("UPDATE work_assignments SET assigned_to_user_id = ?, transfer_status = 'accepted', transferred_to_user_id = NULL, transfer_from_user_id = NULL, transfer_comments = NULL WHERE id = ? AND transferred_to_user_id = ?");
                    $stmt->execute([$currentUserId, $taskId, $currentUserId]);

                    $stmt = $pdo->prepare("UPDATE work_assignments SET transfer_status = 'none' WHERE id = ?");
                    $stmt->execute([$taskId]);

                    $pdo->commit();
                    $_SESSION['status_message'] = '<div class="alert alert-success">Task transfer accepted successfully!</div>';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['status_message'] = '<div class="alert alert-danger">Failed to accept task transfer.</div>';
                }
            }
            $pageRedirect = 'my_tasks';
            break;

        case 'reject_transfer':
            $taskId = (int)($_POST['task_id'] ?? 0);
            $rejectionReason = trim($_POST['rejection_reason'] ?? '');
            if ($taskId > 0) {
                $stmt = $pdo->prepare("UPDATE work_assignments SET transfer_status = 'rejected', transfer_rejection_reason = ?, transferred_to_user_id = NULL, transfer_from_user_id = NULL, transfer_comments = NULL WHERE id = ? AND transferred_to_user_id = ?");
                $stmt->execute([$rejectionReason, $taskId, $currentUserId]);
                $_SESSION['status_message'] = '<div class="alert alert-danger">Task transfer rejected.</div>';
            }
            $pageRedirect = 'my_tasks';
            break;

        case 'update_user_task':
            $taskId = $_POST['task_id'];
            $newStatus = $_POST['status'];

            $attachmentPathSQL = "";
            if (isset($_FILES['task_attachment_user']) && $_FILES['task_attachment_user']['error'] == UPLOAD_ERR_OK) {
                $uploadDir = ROOT_PATH . 'uploads/task_attachments/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                $fileName = 'user_task_' . $taskId . '_' . time() . '.' . pathinfo($_FILES['task_attachment_user']['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['task_attachment_user']['tmp_name'], $uploadDir . $fileName)) {
                    $attachmentPathSQL = ", task_attachment_path = '" . 'uploads/task_attachments/' . $fileName . "'";
                }
            }
            
            // FIXED: Correctly get the values from the form.
            $feeMode = $_POST['fee_mode'] ?? 'pending';
            $maintenanceFeeMode = $_POST['maintenance_fee_mode'] ?? 'pending';
            $paymentStatus = $_POST['payment_status'] ?? 'pending';

            $stmt = $pdo->prepare("UPDATE work_assignments SET status = ?, payment_status = ?, fee_mode = ?, maintenance_fee_mode = ?, user_notes = ? {$attachmentPathSQL} WHERE id = ? AND assigned_to_user_id = ?");
            $stmt->execute([$newStatus, $paymentStatus, $feeMode, $maintenanceFeeMode, $_POST['user_notes'], $taskId, $currentUserId]);
            
            $_SESSION['status_message'] = '<div class="alert alert-success">Task updated successfully!</div>';
            
            // Check user role to redirect to the correct page
            $userRole = $_SESSION['user_role'] ?? 'user';
            if ($userRole === 'freelancer' || $userRole === 'data_entry_operator') {
                $pageRedirect = 'update_freelancer_task';
            } else {
                 $pageRedirect = 'update_task';
            }
            $redirectParams = '&id=' . $taskId;
            break;

        case 'submit_for_verification':
            $taskId = $_POST['task_id'];
            $paymentCollectedBy = $_POST['payment_collected_by'] ?? 'none'; // [NEW] Catch Payment Mode

            // [NEW] Handle Work File Upload
            $workFilePath = null;
            $uploadDir = ROOT_PATH . 'uploads/task_receipts/'; // Using same dir for now, or create 'task_works'
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

            if (isset($_FILES['work_file']) && $_FILES['work_file']['error'] == UPLOAD_ERR_OK) {
                $fileName = 'work_' . $taskId . '_' . time() . '.' . pathinfo($_FILES['work_file']['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['work_file']['tmp_name'], $uploadDir . $fileName)) {
                    $workFilePath = 'uploads/task_receipts/' . $fileName;
                }
            }

            // Handle Receipt Upload
            $receiptPath = null;
            if (isset($_FILES['completion_receipt']) && $_FILES['completion_receipt']['error'] == UPLOAD_ERR_OK) {
                $fileName = 'receipt_' . $taskId . '_' . time() . '.' . pathinfo($_FILES['completion_receipt']['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['completion_receipt']['tmp_name'], $uploadDir . $fileName)) {
                    $receiptPath = 'uploads/task_receipts/' . $fileName;
                }
            }

            // [UPDATED] Update Query to include payment mode and work file
            $stmt = $pdo->prepare("UPDATE work_assignments SET status = 'pending_verification', completion_receipt_path = ?, work_file = ?, user_notes = ?, payment_collected_by = ? WHERE id = ? AND assigned_to_user_id = ?");
            $stmt->execute([$receiptPath, $workFilePath, $_POST['user_notes'], $paymentCollectedBy, $taskId, $currentUserId]);
            
            $admins = fetchAll($pdo, "SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'Admin'");
            foreach ($admins as $admin) {
                addNotification($admin['id'], "Task #{$taskId} has been submitted for verification.", "?page=edit_task&id={$taskId}");
            }

            $_SESSION['status_message'] = '<div class="alert alert-success">Task submitted for verification!</div>';
            
            // Check user role to redirect to the correct page
            $userRole = $_SESSION['user_role'] ?? 'user';
            if ($userRole === 'freelancer' || $userRole === 'data_entry_operator') {
                $pageRedirect = 'update_freelancer_task';
            } else {
                 $pageRedirect = 'update_task';
            }
            $redirectParams = '&id=' . $taskId;
            break;

        case 'return_task_to_admin':
            $stmt = $pdo->prepare("UPDATE work_assignments SET status = 'returned', user_notes = ? WHERE id = ? AND assigned_to_user_id = ?");
            $stmt->execute([$_POST['return_notes'], $_POST['task_id'], $currentUserId]);
            $_SESSION['status_message'] = '<div class="alert alert-warning">Task returned to admin for review.</div>';
            $pageRedirect = 'my_freelancer_tasks';
            break;

        // --- RECRUITMENT POSTS (ADMIN ACTIONS) ---
        case 'approve_post':
            updateRecruitmentPostStatus($_POST['post_id'], 'approved', $currentUserId);
            $_SESSION['status_message'] = '<div class="alert alert-success">Post approved.</div>';
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
            
         case 'submit_recruitment_post':
            if (empty($_POST['id'])) {
                addRecruitmentPostHtml(
                    $_POST['job_title'], $_POST['total_vacancies'], $_POST['image_banner_url'],
                    $_POST['eligibility_criteria'], $_POST['selection_process'], $_POST['start_date'], $_POST['last_date'],
                    $_POST['exam_date'], $_POST['fee_payment_last_date'], $_POST['application_fees'], $_POST['category_wise_vacancies'],
                    $_POST['notification_url'], $_POST['apply_url'], $_POST['admit_card_url'], $_POST['official_website_url'],
                    $_POST['exam_prediction'], json_encode($_POST['custom_fields'] ?? []), $currentUserId
                );
                $_SESSION['status_message'] = '<div class="alert alert-success" role="alert">Post submitted for approval!</div>';
            } else {
                updateRecruitmentPostHtml(
                    $_POST['id'], $_POST['job_title'], $_POST['total_vacancies'], $_POST['image_banner_url'],
                    $_POST['eligibility_criteria'], $_POST['selection_process'], $_POST['start_date'], $_POST['last_date'],
                    $_POST['exam_date'], $_POST['fee_payment_last_date'], $_POST['application_fees'], $_POST['category_wise_vacancies'],
                    $_POST['notification_url'], $_POST['apply_url'], $_POST['admit_card_url'], $_POST['official_website_url'],
                    $_POST['exam_prediction'], json_encode($_POST['custom_fields'] ?? [])
                );
                 $_SESSION['status_message'] = '<div class="alert alert-success" role="alert">Post updated and sent for re-approval!</div>';
            }
            $pageRedirect = 'my_recruitment_posts';
            break;

        case 'delete_recruitment_post':
            $postId = (int)($_POST['post_id'] ?? 0);
            if ($postId > 0) {
                deleteRecruitmentPost($postId, $currentUserId);
                $_SESSION['status_message'] = '<div class="alert alert-success">Post deleted successfully.</div>';
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid post ID.</div>';
            }
            $pageRedirect = 'my_recruitment_posts';
            break;
            
        // --- MESSENGER ACTIONS ---
        case 'send_request':
            $receiverId = $_POST['user_id'];
            if ($receiverId != $currentUserId) {
                sendChatRequest($currentUserId, $receiverId);
                $_SESSION['status_message'] = '<div class="alert alert-success">Chat request sent!</div>';
            }
            $pageRedirect = 'messages';
            $redirectParams = '&chat_with=' . $receiverId;
            break;
            
        case 'accept_request':
            $senderId = $_POST['user_id'];
            acceptChatRequest($currentUserId, $senderId);
            $_SESSION['status_message'] = '<div class="alert alert-success">Chat request accepted!</div>';
            $pageRedirect = 'messages';
            $redirectParams = '&chat_with=' . $senderId;
            break;
            
        case 'reject_request':
        case 'cancel_request':
            $otherUserId = $_POST['user_id'];
            rejectOrCancelRequest($currentUserId, $otherUserId);
            $message = ($action === 'reject_request') ? 'Chat request rejected.' : 'Chat request cancelled.';
            $_SESSION['status_message'] = '<div class="alert alert-info">' . $message . '</div>';
            $pageRedirect = 'messages';
            $redirectParams = '&chat_with=' . $otherUserId;
            break;
            
        case 'send_message':
            $receiverId = $_POST['receiver_id'];
            $messageText = trim($_POST['message_text']);
            if (!empty($messageText)) {
                sendMessage($currentUserId, $receiverId, $messageText);
            }
            $pageRedirect = 'messages';
            $redirectParams = '&chat_with=' . $receiverId;
            break;
            
        case 'clear_chat':
            $otherUserId = $_POST['receiver_id'];
            $stmt = $pdo->prepare("DELETE FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
            $stmt->execute([$currentUserId, $otherUserId, $otherUserId, $currentUserId]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Chat history cleared.</div>';
            $pageRedirect = 'messages';
            $redirectParams = '&chat_with=' . $otherUserId;
            break;

        // --- SETTINGS ---
        case 'update_settings':
        case 'update_hr_settings':
            if ($action === 'update_hr_settings') {
                $stmt = $pdo->prepare("UPDATE settings SET required_daily_hours = ? WHERE id = 1");
                $stmt->execute([$_POST['required_daily_hours']]);
                $_SESSION['status_message'] = '<div class="alert alert-success">HR settings updated successfully!</div>';
                $pageRedirect = 'hr_settings';
            } else {
                $working_days = isset($_POST['office_working_days']) ? implode(',', $_POST['office_working_days']) : '';
                
                $sql = "UPDATE settings SET app_name=?, currency_symbol=?, office_address=?, helpline_number=?, office_start_time=?, office_end_time=?, appointment_slot_duration=?, office_working_days=?, earning_per_approved_post=?, minimum_withdrawal_amount=?, whatsapp_business_number=?, whatsapp_api_key=?, smtp_host=?, smtp_port=?, smtp_encryption=?, smtp_username=?, smtp_from_email=?, smtp_from_name=?";
                $params = [
                    $_POST['app_name'], $_POST['currency_symbol'], $_POST['office_address'], $_POST['helpline_number'], $_POST['office_start_time'],
                    $_POST['office_end_time'], $_POST['appointment_slot_duration'], $working_days, $_POST['earning_per_approved_post'],
                    $_POST['minimum_withdrawal_amount'], $_POST['whatsapp_phone_number_id'], $_POST['whatsapp_access_token'], $_POST['smtp_host'],
                    $_POST['smtp_port'], $_POST['smtp_encryption'], $_POST['smtp_username'], $_POST['smtp_from_email'], $_POST['smtp_from_name']
                ];
                
                if (!empty($_POST['smtp_password'])) {
                    $sql .= ", smtp_password=?";
                    $params[] = $_POST['smtp_password'];
                }
                
                $sql .= " WHERE id=1";
                
                $pdo->prepare($sql)->execute($params);
                
                if (isset($_FILES['app_logo']) && $_FILES['app_logo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = ROOT_PATH . 'uploads/logo/';
                    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                    $fileName = 'logo.png';
                    if (move_uploaded_file($_FILES['app_logo']['tmp_name'], $uploadDir . $fileName)) {
                        $pdo->prepare("UPDATE settings SET app_logo_url = ? WHERE id = 1")->execute(['uploads/logo/' . $fileName]);
                    }
                }
                $_SESSION['status_message'] = '<div class="alert alert-success">Settings updated successfully!</div>';
                $pageRedirect = 'settings';
            }
            break;

        // --- CATEGORY & SUBCATEGORY MANAGEMENT ---
        case 'add_category':
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$_POST['name'], $_POST['description']]);
            $_SESSION['status_message'] = '<div class="alert alert-success">Category added successfully!</div>';
            $pageRedirect = 'categories';
            break;
        case 'edit_category':
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['description'], $_POST['category_id']]);
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

        // --- WITHDRAWAL MANAGEMENT ---
        case 'request_withdrawal':
            $amount = floatval($_POST['amount_to_withdraw']);
            if ($amount <= 0) {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Invalid withdrawal amount.</div>';
            } else {
                $userBankDetails = fetchOne($pdo, "SELECT bank_name, account_holder_name, account_number, ifsc_code FROM users WHERE id = ?", [$currentUserId]);
                if (empty($userBankDetails['account_number'])) {
                    $_SESSION['status_message'] = '<div class="alert alert-danger">Please add your bank details first.</div>';
                } else {
                    $bankDetailsJson = json_encode($userBankDetails);
                    if (addWithdrawalRequest($currentUserId, $amount, $bankDetailsJson)) {
                        $_SESSION['status_message'] = '<div class="alert alert-success">Withdrawal request submitted successfully!</div>';
                    } else {
                        $_SESSION['status_message'] = '<div class="alert alert-danger">Failed to submit withdrawal request.</div>';
                    }
                }
            }
            $pageRedirect = 'my_withdrawals';
            break;
        case 'update_withdrawal_status':
            $withdrawalId = $_POST['withdrawal_id'];
            $newStatus = $_POST['new_status'];
            $adminComments = $_POST['admin_comments'] ?? null;
            $transactionId = $_POST['transaction_id'] ?? null;
            if (updateWithdrawalStatus($withdrawalId, $newStatus, $currentUserId, $adminComments, $transactionId)) {
                $_SESSION['status_message'] = '<div class="alert alert-success">Withdrawal request status updated.</div>';
            } else {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Failed to update withdrawal status.</div>';
            }
            $pageRedirect = 'manage_withdrawals';
            break;


        default:
            $_SESSION['status_message'] = '<div class="alert alert-warning">Invalid or unhandled action requested: <strong>' . htmlspecialchars($action) . '</strong></div>';
            break;
    }
} catch (PDOException $e) {
    error_log("Database Error in actions.php: " . $e->getMessage());
    $_SESSION['status_message'] = '<div class="alert alert-danger">A database error occurred. Please check logs for details.</div>';
} catch (Exception $e) {
    error_log("General Error in actions.php: " . $e->getMessage());
    $_SESSION['status_message'] = '<div class="alert alert-danger">An unexpected error occurred: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Final, reliable redirect
header("Location: " . BASE_URL . "?page=" . $pageRedirect . $redirectParams);
exit;
?>