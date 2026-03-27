<?php
/**
 * user/update_freelancer_task.php
 * FINAL UPDATED VERSION
 * Features:
 * 1. Added 'Payment Status' Update Option.
 * 2. Smart 'Payment Collected By' Logic (Disabled if Admin already collected).
 * 3. Shows correct collector name (Self/Company).
 */

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = $_SESSION['status_message'] ?? '';
unset($_SESSION['status_message']);

// Fetch Task Details
$task = fetchOne($pdo, "
    SELECT wa.*, cl.client_name, cl.phone as client_phone, cl.email as client_email,
           cat.name as category_name, cu.customer_name, cu.customer_phone
    FROM work_assignments wa
    LEFT JOIN clients cl ON wa.client_id = cl.id
    LEFT JOIN customers cu ON wa.customer_id = cu.id
    JOIN categories cat ON wa.category_id = cat.id
    WHERE wa.id = ? AND wa.assigned_to_user_id = ?
", [$taskId, $currentUserId]);

if (!$task) { echo '<div class="alert alert-danger m-3">Task not found or access denied.</div>'; exit; }

$settings = fetchOne($pdo, "SELECT currency_symbol FROM settings LIMIT 1");
$currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? '₹');

// Lock Logic (Task is locked if submitted/verified)
$isLocked = in_array($task['status'], ['pending_verification', 'verified_completed', 'cancelled']);

// Logic: Check if Admin has already collected payment (Paid + Company)
$isAdminCollected = (strtolower($task['payment_status']) === 'paid' && $task['payment_collected_by'] === 'company');

// Fetch Other Freelancers (For Transfer)
$otherUsers = fetchAll($pdo, "
    SELECT u.id, u.name, r.role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE u.id != ? AND r.role_name IN ('Freelancer', 'Data Entry Operator', 'DEO')
    ORDER BY u.name ASC
", [$currentUserId]);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between mb-4">
        <h3 class="h3 text-gray-800">Task #<?= $task['id'] ?> Management</h3>
        <a href="index.php?page=my_freelancer_tasks" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <?php if ($message) echo $message; ?>

    <?php if ($task['status'] === 'returned'): ?>
        <div class="alert alert-danger shadow border-left-danger mb-4">
            <h4 class="alert-heading"><i class="fas fa-exclamation-circle"></i> Task Returned by Admin</h4>
            <p><strong>Reason / Note:</strong> <?= !empty($task['admin_notes']) ? nl2br(htmlspecialchars($task['admin_notes'])) : 'Please check your work and resubmit.' ?></p>
            <hr>
            <p class="mb-0 small">Please fix the issues and submit the work again using the form below.</p>
        </div>
    <?php elseif (!empty($task['admin_notes']) && !$isLocked): ?>
         <div class="alert alert-info shadow mb-4">
            <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Admin Note:</h5>
            <p class="mb-0"><?= nl2br(htmlspecialchars($task['admin_notes'])) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($isLocked): ?>
        <div class="alert alert-warning border-left-warning shadow">
            <h5 class="alert-heading"><i class="fas fa-lock"></i> Task Locked</h5>
            <p class="mb-0">Task status: <strong><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></strong></p>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">Task Details</div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <p><strong>Service:</strong> <?= htmlspecialchars($task['category_name']) ?></p>
                            <p><strong>Client:</strong> <?= htmlspecialchars($task['client_name']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Customer:</strong> <?= htmlspecialchars($task['customer_name'] ?? 'N/A') ?></p>
                            <p><strong>Contact:</strong> 
                                <?php if (!empty($task['customer_phone'])): ?>
                                    <a href="tel:<?= htmlspecialchars($task['customer_phone']) ?>" class="text-decoration-none">
                                        <i class="fas fa-phone-alt"></i> <?= htmlspecialchars($task['customer_phone']) ?>
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <p>
                        <strong>Payment Status:</strong> 
                        <?php if ($task['payment_status'] == 'paid'): ?>
                            <span class="badge bg-success">PAID</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark"><?= ucfirst($task['payment_status']) ?></span>
                        <?php endif; ?>
                        
                        <span class="ms-2 small text-muted">
                            (Collected By: <strong><?= ucfirst($task['payment_collected_by'] ?: 'None') ?></strong>)
                        </span>
                    </p>

                    <p><strong>Deadline:</strong> <span class="text-danger fw-bold"><?= date('d M Y', strtotime($task['deadline'])) ?></span></p>
                    
                    <div class="p-3 bg-light rounded border mb-3">
                        <strong>Description:</strong><br>
                        <?= nl2br(htmlspecialchars($task['work_description'])) ?>
                    </div>

                    <?php if (!empty($task['attachment_path'])): ?>
                        <div class="alert alert-info d-flex align-items-center justify-content-between">
                            <span><i class="fas fa-paperclip me-2"></i> <strong>Admin Attachment:</strong></span>
                            <a href="<?= htmlspecialchars($task['attachment_path']) ?>" class="btn btn-sm btn-info text-white" target="_blank" download>Download</a>
                        </div>
                    <?php endif; ?>

                    <hr>
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <small>Total Fee (To Collect)</small>
                            <h4 class="text-danger"><?= $currencySymbol . number_format($task['fee'], 2) ?></h4>
                        </div>
                        <div class="col-6">
                            <small>Your Earning</small>
                            <h4 class="text-success"><?= $currencySymbol . number_format($task['task_price'], 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$isLocked): ?>
            <div class="card shadow mb-4 border-left-info">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-exchange-alt"></i> Transfer Task</h6>
                </div>
                <div class="card-body">
                    <form action="index.php" method="POST" onsubmit="return confirm('Transfer this task?');">
                        <input type="hidden" name="action" value="freelancer_transfer_task">
                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                        <div class="input-group">
                            <select name="transfer_to_user_id" class="form-select" required>
                                <option value="">-- Select Freelancer --</option>
                                <?php foreach ($otherUsers as $user): ?>
                                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-info text-white">Transfer</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-5">
            <?php if (!$isLocked): ?>
            <div class="card shadow">
                <div class="card-header bg-dark text-white">Update & Submit</div>
                <div class="card-body">
                    
                    <form action="index.php" method="POST" class="mb-3 bg-light p-2 rounded border">
                        <input type="hidden" name="action" value="update_user_task">
                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                        
                        <h6 class="text-dark font-weight-bold small mb-2">Update Progress:</h6>
                        
                        <div class="mb-2">
                            <label class="small text-muted">Work Status:</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="pending" <?= $task['status']=='pending'?'selected':'' ?>>Pending</option>
                                <option value="in_process" <?= $task['status']=='in_process'?'selected':'' ?>>In Process</option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="small text-muted">Payment Status:</label>
                            <select name="payment_status" class="form-select form-select-sm">
                                <option value="pending" <?= $task['payment_status']=='pending'?'selected':'' ?>>Pending</option>
                                <option value="paid" <?= $task['payment_status']=='paid'?'selected':'' ?>>Paid</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-sm btn-secondary w-100">Update Status</button>
                    </form>

                    <hr>

                    <h6 class="text-success fw-bold">Submit Completed Work</h6>
                    <form action="index.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="submit_for_verification">
                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                        
                        <div class="mb-3">
                            <label class="small fw-bold">Payment Collected By? <span class="text-danger">*</span></label>
                            
                            <?php if ($isAdminCollected): ?>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white"><i class="fas fa-check"></i></span>
                                    <input type="text" class="form-control form-control-sm bg-white" value="Company (Received by Admin)" disabled>
                                </div>
                                <input type="hidden" name="payment_collected_by" value="company">
                                <small class="text-muted d-block mt-1">
                                    Admin has marked this as Paid. Earning will be added to your wallet.
                                </small>

                            <?php else: ?>
                                <select name="payment_collected_by" class="form-select form-select-sm" required>
                                    <option value="">-- Select Who Took Payment --</option>
                                    <option value="company" <?= $task['payment_collected_by']=='company'?'selected':'' ?>>Company (Client Paid Online/Admin)</option>
                                    <option value="self" <?= $task['payment_collected_by']=='self'?'selected':'' ?>>Self (I Collected Cash)</option>
                                </select>
                                <small class="text-muted">
                                    Select 'Self' if you took cash. Select 'Company' if paid to office.
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-2">
                            <label class="small">Work File / Proof</label>
                            <input type="file" name="work_file" class="form-control form-control-sm">
                        </div>
                        <div class="mb-2">
                            <label class="small">Payment Receipt <span class="text-danger">*</span></label>
                            <input type="file" name="completion_receipt" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <textarea name="user_notes" class="form-control form-control-sm" placeholder="Any notes for Admin..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold">Submit & Lock</button>
                    </form>

                    <hr>
                    <button class="btn btn-outline-danger w-100 btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#returnBox">
                        <i class="fas fa-undo"></i> Return / Cancel Task
                    </button>
                    <div class="collapse mt-2" id="returnBox">
                        <div class="card card-body p-2 bg-light">
                            <form action="index.php" method="POST">
                                <input type="hidden" name="action" value="return_task_to_admin">
                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                <label class="small">Reason:</label>
                                <select name="return_reason" class="form-select form-select-sm mb-2" required>
                                    <option value="Customer Wants to Cancel">Customer Wants to Cancel</option>
                                    <option value="Cannot Do This Task">Cannot Do This Task</option>
                                    <option value="Other">Other</option>
                                </select>
                                <textarea name="return_notes" class="form-control form-control-sm mb-2" placeholder="Explain..." required></textarea>
                                <button type="submit" class="btn btn-danger btn-sm w-100">Confirm Return</button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
            <?php else: ?>
                <div class="card"><div class="card-body text-center text-muted">Task is Locked.</div></div>
            <?php endif; ?>
        </div>
    </div>
</div>