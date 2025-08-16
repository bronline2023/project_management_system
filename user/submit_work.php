<?php
/**
 * user/submit_work.php
 *
 * This file allows a regular user to submit new work entries for clients.
 * It features dynamic loading of subcategories based on category selection,
 * auto-population of fee (fare) from the selected subcategory, and fields
 * for maintenance fees and their payment modes.
 *
 * It ensures that only authenticated non-admin users can access this page.
 */

// Include the configuration file for database connection and session management.
require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';   // Database interaction functions
require_once MODELS_PATH . 'auth.php'; // Authentication functions

// Restrict access to non-admin users only.
// If the user is not logged in or is an admin, redirect them.
if (!isLoggedIn() || $_SESSION['user_role'] === 'admin') {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$pdo = connectDB(); // Establish database connection
$current_user_id = $_SESSION['user_id']; // The user submitting the work
$message = ''; // To store success or error messages

// --- Handle Form Submission for New Work Entry ---
if (isset($_POST['submit_work'])) {
    $client_id = $_POST['client_id'];
    $category_id = $_POST['category_id'];
    $subcategory_id = $_POST['subcategory_id'];
    $work_description = trim($_POST['work_description']);
    $deadline = $_POST['deadline'];
    $fee = floatval($_POST['fee']); // This should be auto-populated from subcategory
    $fee_mode = $_POST['fee_mode'];
    $maintenance_fee = floatval($_POST['maintenance_fee'] ?? 0); // Optional
    $maintenance_fee_mode = $_POST['maintenance_fee_mode'] ?? 'pending'; // Optional
    $user_notes = trim($_POST['user_notes'] ?? '');

    // Basic validation
    if (empty($client_id) || empty($category_id) || empty($subcategory_id) || empty($work_description) || empty($deadline) || !is_numeric($fee) || $fee < 0 || empty($fee_mode)) {
        $message = '<div class="alert alert-danger" role="alert">Please fill in all required fields (Client, Category, Subcategory, Description, Deadline, Fee, Fee Mode).</div>';
    } else {
        // Fetch the actual fare from the database for the selected subcategory to prevent tampering
        $actual_fare = 0;
        try {
            $stmt = $pdo->prepare("SELECT fare FROM subcategories WHERE id = ?");
            $stmt->execute([$subcategory_id]);
            $subcat_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($subcat_data) {
                $actual_fare = floatval($subcat_data['fare']);
            }
        } catch (PDOException $e) {
            error_log("Error verifying subcategory fare: " . $e->getMessage());
            $message = '<div class="alert alert-danger" role="alert">Error verifying subcategory fare. Please try again.</div>';
        }

        if (empty($message)) { // Proceed only if no prior errors
            // Ensure the fee matches the actual fare from DB.
            if ($fee != $actual_fare) {
                 // For user submission, we should strictly enforce the DB fare
                 $message = '<div class="alert alert-danger" role="alert">The entered fee does not match the subcategory\'s default fare. Please refresh the page and try again.</div>';
                 // Or, if you want to allow override: $fee = $actual_fare;
            }
        }

        if (empty($message)) { // Proceed if no errors after fare check
            try {
                // Task starts as 'pending' and payment status as 'pending'
                $stmt = $pdo->prepare("INSERT INTO work_assignments (client_id, assigned_to_user_id, category_id, subcategory_id, work_description, deadline, fee, fee_mode, maintenance_fee, maintenance_fee_mode, user_notes, status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')");
                $stmt->execute([
                    $client_id,
                    $current_user_id, // Assigned to the current logged-in user
                    $category_id,
                    $subcategory_id,
                    $work_description,
                    $deadline,
                    $fee,
                    $fee_mode,
                    $maintenance_fee,
                    $maintenance_fee_mode,
                    $user_notes
                ]);
                $message = '<div class="alert alert-success" role="alert">Work entry submitted successfully!</div>';

                // Clear form fields after successful submission (optional, for UX)
                $_POST = array(); // Clears all post variables
            } catch (PDOException $e) {
                error_log("Error submitting work entry: " . $e->getMessage());
                $message = '<div class="alert alert-danger" role="alert">Error submitting work entry: ' . $e->getMessage() . '</div>';
            }
        }
    }
}


// --- Fetch Data for Dropdowns ---

// Fetch Clients
$clients = [];
try {
    $stmt = $pdo->query("SELECT id, client_name FROM clients ORDER BY client_name ASC"); // Ensure 'client_name' column exists
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching clients: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading clients.</div>';
}

// Fetch Categories
$categories = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $message .= '<div class="alert alert-danger" role="alert">Error loading categories.</div>';
}

// Get currency symbol from settings for display
$currencySymbol = '$'; // Default
try {
    $stmt = $pdo->query("SELECT currency_symbol FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings && isset($settings['currency_symbol'])) {
        $currencySymbol = htmlspecialchars($settings['currency_symbol']);
    }
} catch (PDOException $e) {
    error_log("Error fetching currency symbol: " . $e->getMessage());
}


// Include the header (contains HTML <head> and initial Bootstrap/CSS)
include INCLUDES_PATH . 'header.php';
?>

<div class="wrapper d-flex">
    <?php include INCLUDES_PATH . 'sidebar.php'; // Include the sidebar for navigation ?>

    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <h2 class="mb-4">Submit New Work Entry</h2>

        <?php if (!empty($message)): ?>
            <?php include VIEWS_PATH . 'components/message_box.php'; // Custom message box ?>
            <script>
                // Auto-hide the message after 5 seconds
                setTimeout(function() {
                    const alert = document.querySelector('.alert');
                    if (alert) {
                        alert.classList.add('fade-out');
                        setTimeout(() => alert.remove(), 500); // Remove after fade-out
                    }
                }, 5000);
            </script>
        <?php endif; ?>

        <!-- Submit Work Form -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-plus-square me-2"></i>New Work Details</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST" id="submitWorkForm">
                    <div class="mb-3">
                        <label for="client_id" class="form-label">Client <span class="text-danger">*</span></label>
                        <!-- Search input for clients -->
                        <input type="text" id="clientSearchInput" class="form-control rounded-pill mb-2" placeholder="Search client...">
                        <select class="form-select rounded-pill" id="client_id" name="client_id" required>
                            <option value="">Select Client</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= htmlspecialchars($client['id']) ?>"><?= htmlspecialchars($client['client_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Type to search for a client.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select rounded-pill" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['id']) ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="subcategory_id" class="form-label">Subcategory <span class="text-danger">*</span></label>
                            <select class="form-select rounded-pill" id="subcategory_id" name="subcategory_id" required disabled>
                                <option value="">Select Subcategory</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="work_description" class="form-label">Work Description <span class="text-danger">*</span></label>
                        <textarea class="form-control rounded-3" id="work_description" name="work_description" rows="3" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="deadline" class="form-label">Deadline (Due Date) <span class="text-danger">*</span></label>
                            <input type="date" class="form-control rounded-pill" id="deadline" name="deadline" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fee" class="form-label">Fee (<?= $currencySymbol ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control rounded-pill" id="fee" name="fee" min="0" required readonly>
                            <small class="text-muted">Auto-populated based on selected subcategory.</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fee_mode" class="form-label">Fee Mode <span class="text-danger">*</span></label>
                            <select class="form-select rounded-pill" id="fee_mode" name="fee_mode" required>
                                <option value="">Select Fee Mode</option>
                                <option value="online">Online</option>
                                <option value="cash">Cash</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="maintenance_fee" class="form-label">Maintenance Fee (<?= $currencySymbol ?>)</label>
                            <input type="number" step="0.01" class="form-control rounded-pill" id="maintenance_fee" name="maintenance_fee" min="0" value="0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="maintenance_fee_mode" class="form-label">Maintenance Fee Mode</label>
                        <select class="form-select rounded-pill" id="maintenance_fee_mode" name="maintenance_fee_mode">
                            <option value="pending">Pending</option>
                            <option value="online">Online</option>
                            <option value="cash">Cash</option>
                            <option value="credit_card">Credit Card</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="user_notes" class="form-label">Additional Notes (Optional)</label>
                        <textarea class="form-control rounded-3" id="user_notes" name="user_notes" rows="2"></textarea>
                    </div>

                    <button type="submit" name="submit_work" class="btn btn-primary rounded-pill"><i class="fas fa-plus-circle me-2"></i>Submit Work</button>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- Custom Confirmation Modal (re-used for consistency across system) -->
<div class="modal fade" id="customConfirmModal" tabindex="-1" aria-labelledby="customConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="customConfirmModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirmation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p id="confirm-message" class="lead text-center"></p>
            </div>
            <div class="modal-footer border-0 rounded-bottom-4 justify-content-center">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirm-link" class="btn btn-danger rounded-pill">Confirm</a>
            </div>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; // Include the footer ?>

<script>
    // JavaScript for dynamic subcategory loading and fare auto-population
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('category_id');
        const subcategorySelect = document.getElementById('subcategory_id');
        const feeInput = document.getElementById('fee');
        const clientSearchInput = document.getElementById('clientSearchInput');
        const clientSelect = document.getElementById('client_id');
        const clientOptions = Array.from(clientSelect.options); // Get all original options

        // --- Client Search Functionality ---
        if (clientSearchInput && clientSelect) {
            clientSearchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();

                // Clear current options, but keep the "Select Client" option
                clientSelect.innerHTML = '';
                const defaultOption = document.createElement('option');
                defaultOption.value = "";
                defaultOption.textContent = "Select Client";
                clientSelect.appendChild(defaultOption);

                clientOptions.forEach(option => {
                    // Skip the default "Select Client" option during search
                    if (option.value === "") return;

                    const clientName = option.textContent.toLowerCase();
                    if (clientName.includes(searchTerm)) {
                        clientSelect.appendChild(option); // Re-add matching options
                    }
                });
            });
        }


        // --- Category/Subcategory/Fee Logic ---
        categorySelect.addEventListener('change', function() {
            const categoryId = this.value;
            subcategorySelect.innerHTML = '<option value="">Loading Subcategories...</option>';
            subcategorySelect.disabled = true;
            feeInput.value = '0.00'; // Reset fee when category changes

            if (categoryId) {
                // AJAX call to fetch subcategories
                fetch('<?= BASE_URL ?>models/fetch_subcategories.php?category_id=' + categoryId)
                    .then(response => response.json())
                    .then(data => {
                        subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
                        if (data.length > 0) {
                            data.forEach(sub => {
                                const option = document.createElement('option');
                                option.value = sub.id;
                                option.textContent = sub.name;
                                option.setAttribute('data-fare', sub.fare); // Store fare in data attribute
                                subcategorySelect.appendChild(option);
                            });
                            subcategorySelect.disabled = false;
                        } else {
                            subcategorySelect.innerHTML = '<option value="">No Subcategories Found</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching subcategories:', error);
                        subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
                    });
            } else {
                subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
            }
        });

        subcategorySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.getAttribute('data-fare')) {
                feeInput.value = parseFloat(selectedOption.getAttribute('data-fare')).toFixed(2);
            } else {
                feeInput.value = '0.00';
            }
        });

        // Custom confirm dialog function (re-used across files for consistency)
        function showCustomConfirm(title, message, link) {
            const confirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
            document.getElementById('customConfirmModalLabel').textContent = title;
            document.getElementById('confirm-message').textContent = message;
            document.getElementById('confirm-link').href = link;
            confirmModal.show();
        }

        // Auto-hide message functionality (if message is present from PHP)
        <?php if (!empty($message)): ?>
            setTimeout(function() {
                const alertElement = document.querySelector('.alert');
                if (alertElement) {
                    alertElement.classList.add('fade-out');
                    setTimeout(() => alertElement.remove(), 500);
                }
            }, 5000);
        <?php endif; ?>
    });
</script>

<style>
    /* Custom CSS for fade-out alert */
    .alert.fade-out {
        opacity: 0;
        transition: opacity 0.5s ease-out;
    }
</style>
