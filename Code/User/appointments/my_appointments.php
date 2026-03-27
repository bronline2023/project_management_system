<?php
/**
 * user/my_appointments.php
 * Page for staff members to view their assigned appointments.
 */
if (!defined('ROOT_PATH')) {
    die('Invalid access');
}

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];

// Check for and display a status message
$message = '';
if(isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

// Fetch owned appointments for the logged-in user (including accepted transfers)
$ownedAppointments = fetchAll($pdo, "
    SELECT a.*, c.name as category_name
    FROM appointments a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.user_id = ? AND a.transfer_status = 'none'
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
", [$currentUserId]);

// Fetch pending transfer requests for the logged-in user
$pendingTransfers = fetchAll($pdo, "
    SELECT a.*, c.name as category_name, u.name as from_user_name
    FROM appointments a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN users u ON a.transfer_from_user_id = u.id
    WHERE a.transferred_to_user_id = ? AND a.transfer_status = 'pending'
    ORDER BY a.transfer_requested_at DESC
", [$currentUserId]);


// Fetch users for the transfer dropdown, excluding the current user and admins
$usersForTransfer = fetchAll($pdo, "SELECT u.id, u.name, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id != ? AND u.status = 'active' AND r.role_name != 'admin'", [$currentUserId]);
?>

<h2 class="mb-4">My Appointments</h2>

<?php if (!empty($message)): ?>
    <div class="message-container mb-3">
        <?php include VIEWS_PATH . 'components/message_box.php'; ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning text-dark"><h5 class="mb-0">Pending Transfer Requests</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Service</th>
                        <th>Date & Time</th>
                        <th>Requested By</th>
                        <th>Comments</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pendingTransfers)): ?>
                        <tr><td colspan="6" class="text-center">No pending transfer requests.</td></tr>
                    <?php endif; ?>
                    <?php foreach($pendingTransfers as $apt): ?>
                    <tr>
                        <td><?= htmlspecialchars($apt['client_name']) ?></td>
                        <td><?= htmlspecialchars($apt['category_name']) ?></td>
                        <td><?= date('d M, Y', strtotime($apt['appointment_date'])) ?> at <?= date('h:i A', strtotime($apt['appointment_time'])) ?></td>
                        <td><?= htmlspecialchars($apt['from_user_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($apt['transfer_comments'] ?? 'N/A') ?></td>
                        <td>
                            <form action="index.php" method="POST" class="d-inline">
                                <input type="hidden" name="action" value="accept_appointment_transfer">
                                <input type="hidden" name="page" value="my_appointments">
                                <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($apt['id']) ?>">
                                <button type="submit" class="btn btn-sm btn-success">Accept</button>
                            </form>
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#transferActionModal" data-appointment-id="<?= $apt['id'] ?>" data-action="reject">Reject</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header"><h5 class="mb-0">My Scheduled Appointments</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Phone</th>
                        <th>Service</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Document</th>
                        <th>Actions</th>
                    </tr>
</thead>
<tbody>
    <?php if (empty($ownedAppointments)): ?>
        <tr><td colspan="8" class="text-center">You have no appointments.</td></tr>
    <?php endif; ?>
    <?php foreach($ownedAppointments as $apt): ?>
    <tr>
        <td><?= htmlspecialchars($apt['client_name']) ?></td>
        <td><?= htmlspecialchars($apt['client_phone']) ?></td>
        <td><?= htmlspecialchars($apt['category_name']) ?></td>
        <td><?= date('d M, Y', strtotime($apt['appointment_date'])) ?> at <?= date('h:i A', strtotime($apt['appointment_time'])) ?></td>
        <td>
            <span class="badge 
                <?php 
                    switch($apt['status']) {
                        case 'confirmed': echo 'bg-success'; break;
                        case 'cancelled': echo 'bg-danger'; break;
                        case 'completed': echo 'bg-secondary'; break;
                        default: echo 'bg-warning';
                    }
                ?>">
                <?= ucfirst($apt['status']) ?>
            </span>
        </td>
        <td><?= htmlspecialchars($apt['notes']) ?></td>
        <td>
            <?php if (!empty($apt['document_path'])): ?>
                <a href="<?= BASE_URL . htmlspecialchars($apt['document_path']) ?>" target="_blank" class="btn btn-info btn-sm">View Doc</a>
            <?php else: ?>
                N/A
            <?php endif; ?>
        </td>
        <td>
            <?php if ($apt['status'] === 'pending'): ?>
                <a href="?page=create_task_from_appointment&appointment_id=<?= htmlspecialchars($apt['id']) ?>" class="btn btn-sm btn-success rounded-pill">Accept & Create Task</a>
            <?php elseif ($apt['status'] === 'confirmed' && $apt['task_id']): ?>
                <a href="?page=update_task&id=<?php echo htmlspecialchars($apt['task_id']); ?>" class="btn btn-sm btn-primary rounded-pill">View & Update Task</a>
            <?php endif; ?>
            <?php if ($apt['status'] !== 'completed' && $apt['status'] !== 'cancelled'): ?>
                <button class="btn btn-sm btn-warning rounded-pill" data-bs-toggle="modal" data-bs-target="#transferRequestModal" data-appointment-id="<?= $apt['id'] ?>"><i class="fas fa-exchange-alt me-1"></i> Transfer</button>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="transferRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Appointment Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="index.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="transfer_appointment">
                    <input type="hidden" name="page" value="my_appointments">
                    <input type="hidden" name="appointment_id" id="transfer-appointment-id">
                    <div class="mb-3">
                        <label for="transfer_to_user_id" class="form-label">Transfer To</label>
                        <select class="form-select" id="transfer_to_user_id" name="transfer_to_user_id" required>
                            <option value="">Select User</option>
                            <?php foreach ($usersForTransfer as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="transfer_comments" class="form-label">Reason for Transfer</label>
                        <textarea class="form-control" name="transfer_comments" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="transferActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transfer-action-modal-title">Appointment Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                 <p id="transfer-action-message"></p>
                 <form action="index.php" method="POST" id="accept-form" style="display: none;">
                    <input type="hidden" name="action" value="accept_appointment_transfer">
                    <input type="hidden" name="page" value="my_appointments">
                    <input type="hidden" name="appointment_id" id="accept-appointment-id">
                    <button type="submit" class="btn btn-success">Accept</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                 </form>
                 <form action="index.php" method="POST" id="reject-form" style="display: none;">
                    <input type="hidden" name="action" value="reject_appointment_transfer">
                    <input type="hidden" name="page" value="my_appointments">
                    <input type="hidden" name="appointment_id" id="reject-appointment-id">
                     <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" name="rejection_reason" id="rejection_reason" rows="3" required></textarea>
                     </div>
                    <button type="submit" class="btn btn-danger">Reject</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                 </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const transferRequestModal = document.getElementById('transferRequestModal');
    transferRequestModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const appointmentId = button.getAttribute('data-appointment-id');
        const modalInput = transferRequestModal.querySelector('#transfer-appointment-id');
        modalInput.value = appointmentId;
    });

    const transferActionModal = document.getElementById('transferActionModal');
    transferActionModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const appointmentId = button.getAttribute('data-appointment-id');
        const action = button.getAttribute('data-action');
        
        const modalTitle = document.getElementById('transfer-action-modal-title');
        const messageContainer = document.getElementById('transfer-action-message');
        const acceptForm = document.getElementById('accept-form');
        const rejectForm = document.getElementById('reject-form');
        const acceptAppointmentId = document.getElementById('accept-appointment-id');
        const rejectAppointmentId = document.getElementById('reject-appointment-id');

        acceptForm.style.display = 'none';
        rejectForm.style.display = 'none';

        if (action === 'accept') {
            modalTitle.textContent = 'Accept Appointment Transfer';
            messageContainer.textContent = `Do you want to accept appointment #${appointmentId}?`;
            acceptAppointmentId.value = appointmentId;
            acceptForm.style.display = 'block';
        } else if (action === 'reject') {
            modalTitle.textContent = 'Reject Appointment Transfer';
            messageContainer.textContent = `Do you want to reject appointment #${appointmentId}?`;
            rejectAppointmentId.value = appointmentId;
            rejectForm.style.display = 'block';
        }
    });
});
</script>