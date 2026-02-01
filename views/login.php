<?php
/**
 * views/login.php
 * This file provides the login form and public appointment booking form.
 * FINAL & COMPLETE: All POST logic has been moved to the central index.php handler.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}
if (!function_exists('connectDB')) {
    require_once MODELS_PATH . 'db.php';
}

$message = '';
if(isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$pdo = connectDB();
$settings = fetchOne($pdo, "SELECT app_name, app_logo_url FROM settings LIMIT 1");
$app_name_setting = htmlspecialchars($settings['app_name'] ?? APP_NAME);
$app_logo_url = htmlspecialchars($settings['app_logo_url'] ?? '');

// Fetch only categories that are marked as 'live'
$live_categories = fetchAll($pdo, "SELECT id, name FROM categories WHERE is_live = 1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= $app_name_setting ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <style>
        body {
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            min-height: 100vh;
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        
        .animated-logo-container {
            width: 150px;
            height: 150px;
            background-color: white;
            border-radius: 12px;
            padding: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.5s ease-in-out;
            animation: bounce 2s infinite ease-in-out;
        }

        .animated-logo-container:hover {
            transform: scale(1.1);
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row min-vh-100 justify-content-center align-items-center">
            <div class="col-lg-5 col-md-6 mb-4 mb-md-0">
                <div class="card login-card p-4">
                    <div class="card-body text-white">
                        <div class="text-center mb-4">
                            <?php if (!empty($app_logo_url)): ?>
                                <div class="animated-logo-container">
                                     <img src="<?= $app_logo_url ?>" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                </div>
                            <?php endif; ?>
                            <h4 class="card-title"><?= $app_name_setting ?></h4>
                            <p>Please log in to continue.</p>
                        </div>

                        <?php 
                        if (!empty($message) && strpos($message, 'appointment-toast-message') === false) {
                           include __DIR__ . '/components/message_box.php';
                        }
                        ?>

                        <form action="index.php" method="POST">
                            <input type="hidden" name="action" value="login_submit">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 col-md-6">
                 <div class="card login-card p-4">
                    <div class="card-body text-white">
                        <div class="text-center mb-4">
                            <h4 class="card-title">Book an Appointment</h4>
                            <p>Schedule a visit to our office.</p>
                        </div>
                        <form action="index.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="book_appointment">
                             <input type="hidden" name="page" value="login">
                            <div class="mb-3"><input type="text" class="form-control" name="client_name" placeholder="Your Name" required></div>
                            <div class="mb-3"><input type="tel" class="form-control" name="client_phone" placeholder="Your Phone Number" required></div>
                             <div class="mb-3"><input type="email" class="form-control" name="client_email" placeholder="Your Email (Optional)"></div>
                            <div class="mb-3">
                                <select class="form-select" name="category_id" id="appointment_category_id" required>
                                    <option value="">Select Service</option>
                                    <?php foreach($live_categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3"><select class="form-select" name="user_id" required><option value="">Whom to Meet?</option><?php foreach(fetchAll($pdo, "SELECT id, name FROM users WHERE role_id != 1 AND status = 'active'") as $user): ?><option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-3"><label class="form-label">Appointment Date</label><input type="date" class="form-control" id="appointment_date" name="appointment_date" required></div>
                            <div class="mb-3"><label class="form-label">Appointment Time</label><select class="form-select" id="appointment_time" name="appointment_time" required><option value="">Select Date First</option></select></div>
                            <div class="mb-3"><label class="form-label">Attach Document (Optional)</label><input type="file" class="form-control" name="document"></div>
                            <div class="mb-3"><textarea class="form-control" name="notes" rows="2" placeholder="Notes about your work..."></textarea></div>
                            <div class="d-grid"><button type="submit" class="btn btn-success">Book Appointment</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('appointment_date');
            const categorySelect = document.getElementById('appointment_category_id');
            const timeSelect = document.getElementById('appointment_time');

            if (dateInput) {
                dateInput.setAttribute('min', new Date().toISOString().split('T')[0]);
                dateInput.addEventListener('change', function() {
                    fetchTimeSlots();
                });
            }

            if (categorySelect) {
                categorySelect.addEventListener('change', function() {
                    // When category changes, re-fetch time slots as availability might depend on the category
                    fetchTimeSlots();
                });
            }

            function fetchTimeSlots() {
                const selectedDate = dateInput.value;
                const selectedCategory = categorySelect.value;
                timeSelect.innerHTML = '<option value="">Loading...</option>';

                if (selectedDate && selectedCategory) {
                    fetch(`models/fetch_timeslots.php?date=${selectedDate}&category_id=${selectedCategory}`)
                        .then(response => response.json())
                        .then(data => {
                            timeSelect.innerHTML = '<option value="">Select Time Slot</option>';
                            if(data.slots && data.slots.length > 0) {
                                data.slots.forEach(slot => {
                                    const option = document.createElement('option');
                                    option.value = slot.value;
                                    option.textContent = slot.label;
                                    timeSelect.appendChild(option);
                                });
                            } else {
                                timeSelect.innerHTML = '<option value="">No slots available</option>';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching time slots:', error);
                            timeSelect.innerHTML = '<option value="">Error loading slots</option>';
                        });
                }
            }


            // Check for the specific appointment success message and show as toast
            const urlParams = new URLSearchParams(window.location.search);
            const successMessage = urlParams.get('appointment_success');
            if (successMessage) {
                const toastContainer = document.getElementById('toast-container');
                const toastElement = document.createElement('div');
                toastElement.className = 'toast align-items-center text-bg-success border-0';
                toastElement.setAttribute('role', 'alert');
                toastElement.setAttribute('aria-live', 'assertive');
                toastElement.setAttribute('aria-atomic', 'true');
                toastElement.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            Appointment booked successfully! We will contact you soon.
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                `;
                toastContainer.appendChild(toastElement);
                const toast = new bootstrap.Toast(toastElement);
                toast.show();
            }
        });
    </script>
</body>
</html>