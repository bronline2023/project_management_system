<?php
/**
 * views/appointment.php
 * REDESIGNED: Premium Split-Screen Public Appointment Booking Page.
 * Matches the aesthetic of the new Login & Register Portals.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config.php';
}
if (!function_exists('connectDB')) {
    require_once MODELS_PATH . 'db.php';
}
$pdo = connectDB();
$settings = fetchOne($pdo, "SELECT app_name, app_logo_url FROM settings LIMIT 1");
$appName = htmlspecialchars($settings['app_name'] ?? APP_NAME);
$appLogo = htmlspecialchars($settings['app_logo_url'] ?? '');

// Fetch only categories that are marked as 'live'
$live_categories = fetchAll($pdo, "SELECT id, name FROM categories WHERE is_live = 1");

// Fetch the Master Admin or first admin for public appointments
$admin_user = fetchOne($pdo, "SELECT id, name FROM users WHERE role_id = 1 OR role = 'master_admin' LIMIT 1");
$admin_id = $admin_user['id'] ?? 1;
$admin_name = $admin_user['name'] ?? 'Main Office';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Consultation - <?= $appName ?></title>
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>img/br_favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #4f46e5;
            --brand-secondary: #0ea5e9;
            --brand-accent: #e11d48;
            --brand-grad: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 50%, #e11d48 100%);
        }

        body {
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            color: #1e293b;
            overflow-x: hidden;
        }

        .auth-split-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* LEFT SIDE: Branding & Visuals */
        .auth-visual-panel {
            flex: 1;
            background: var(--brand-grad);
            background-size: 200% 200%;
            animation: gradientMove 15s ease infinite;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 4rem;
            position: relative;
            overflow: hidden;
        }

        @media (min-width: 992px) {
            .auth-visual-panel { display: flex; }
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .visual-content {
            position: relative;
            z-index: 2;
            text-align: center;
            animation: fadeIn 1s ease-out;
        }

        .auth-glass-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 3rem;
            border-radius: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .logo-circle {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            padding: 15px;
            margin: 0 auto 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            animation: float 4s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        /* RIGHT SIDE: Booking Form */
        .auth-form-panel {
            flex: 1.5;
            min-width: 60%;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem;
        }

        .booking-box {
            width: 100%;
            max-width: 800px;
            background: white;
            padding: 4rem;
            border-radius: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            border: 1px solid #f1f5f9;
            animation: slideUp 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        .form-label { font-weight: 700; color: #475569; font-size: 0.85rem; margin-bottom: 0.6rem; display: block; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .form-control, .form-select {
            border-radius: 16px;
            padding: 0.9rem 1.4rem;
            border: 2px solid #f1f5f9;
            background-color: #f8fafc;
            color: #1e293b;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            background-color: #ffffff;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            outline: none;
        }

        .btn-booking {
            background: var(--brand-grad);
            background-size: 200% auto;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 1.2rem;
            font-weight: 800;
            font-size: 1.1rem;
            transition: 0.5s;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        }

        .btn-booking:hover {
            background-position: right center;
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(79, 70, 229, 0.4);
            color: white;
        }

        .back-home {
            position: absolute;
            top: 2rem;
            right: 2rem;
            color: #64748b;
            text-decoration: none;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: 0.3s;
            z-index: 10;
        }

        .back-home:hover { color: var(--brand-primary); transform: translateX(-5px); }

        .service-badge {
            background: rgba(79, 70, 229, 0.1);
            color: var(--brand-primary);
            padding: 4px 12px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="auth-split-wrapper">
    <!-- Visual Side -->
    <div class="auth-visual-panel">
        <div class="visual-content">
            <div class="auth-glass-box">
                <div class="logo-square">
                    <?php if ($appLogo): ?>
                        <img src="<?= $appLogo ?>" alt="Logo" style="max-height: 50px;">
                    <?php else: ?>
                        <i class="fas fa-calendar-check fa-3x" style="color: var(--brand-primary);"></i>
                    <?php endif; ?>
                </div>
                <h2 class="display-5 fw-800 mb-3">Priority Access</h2>
                <p class="lead opacity-90 mb-0">Book your expert consultation with <?= $appName ?> and experience premium personalized services tailored just for you.</p>
            </div>
        </div>
    </div>

    <!-- Form Side -->
    <div class="auth-form-panel position-relative">
        <a href="<?= BASE_URL ?>?page=b2c_home" class="back-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <div class="booking-box">
            <div class="mb-5">
                <span class="service-badge">Authorized Booking</span>
                <h2 class="display-6 fw-800 mb-2">Schedule Your Visit</h2>
                <p class="text-muted fs-5 mb-0">Fill out the details below to secure your appointment.</p>
                <p class="mt-2"><i class="fas fa-user-tie text-primary"></i> <span class="fw-600 text-dark">Consulting with:</span> <span class="badge bg-primary px-3 rounded-pill"><?= htmlspecialchars($admin_name) ?></span></p>
            </div>

            <form action="<?= BASE_URL ?>" method="POST" id="appointmentForm">
                <input type="hidden" name="action" value="book_appointment">
                <input type="hidden" name="user_id" value="<?= $admin_id ?>">
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" class="form-control" name="client_name" placeholder="John Doe" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control" name="client_email" placeholder="john@example.com" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-phone text-muted"></i></span>
                            <input type="text" class="form-control" name="client_phone" placeholder="+91 00000 00000" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Service Category</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-list text-muted"></i></span>
                            <select class="form-select" name="category_id" required>
                                <option value="">Choose Service...</option>
                                <?php foreach ($live_categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Preferred Date</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-calendar-day text-muted"></i></span>
                            <input type="date" class="form-control" name="appointment_date" required min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Preferred Time</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-clock text-muted"></i></span>
                            <input type="time" class="form-control" name="appointment_time" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Additional Notes (Optional)</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Tell us more about your requirements..."></textarea>
                    </div>
                    <div class="col-12 mt-5">
                        <button type="submit" class="btn btn-booking w-100">
                            Confirm Appointment <i class="fas fa-paper-plane ms-2"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
