<?php
/**
 * views/login.php
 * REDESIGNED: Premium Split-Screen B2B Login Portal.
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

$message = '';
if(isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$pdo = connectDB();
$settings = fetchOne($pdo, "SELECT app_name, app_logo_url, website_logo_url FROM settings LIMIT 1");
$appName = htmlspecialchars($settings['app_name'] ?? APP_NAME);
$appLogo = htmlspecialchars($settings['app_logo_url'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authorized Access - <?= $appName ?></title>
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

        .split-container {
            display: flex;
            min-height: 100vh;
            flex-wrap: wrap;
        }

        /* LEFT SIDE: Branding & Visuals */
        .branding-panel {
            flex: 1;
            min-width: 50%;
            background: var(--brand-grad);
            background-size: 200% 200%;
            animation: gradientMove 15s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 4rem;
            position: relative;
            overflow: hidden;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .branding-content {
            position: relative;
            z-index: 2;
            text-align: center;
            animation: fadeIn 1s ease-out;
        }

        .glass-brand {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 3rem;
            border-radius: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .logo-square {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 20px;
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

        /* RIGHT SIDE: Login Form */
        .login-panel {
            flex: 1;
            min-width: 50%;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem;
        }

        .login-box {
            width: 100%;
            max-width: 440px;
            animation: slideUp 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        .form-label { font-weight: 600; color: #64748b; font-size: 0.9rem; margin-bottom: 0.5rem; display: block; }
        
        .form-control {
            border-radius: 16px;
            padding: 0.9rem 1.2rem;
            border: 2px solid #f1f5f9;
            background-color: #f8fafc;
            color: #1e293b;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            background-color: #ffffff;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            outline: none;
        }

        .btn-auth {
            background: var(--brand-grad);
            border: none;
            color: white;
            font-weight: 800;
            padding: 1rem;
            border-radius: 16px;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-size: 0.9rem;
            transition: all 0.3s;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
            margin-top: 1rem;
        }

        .btn-auth:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.5);
            filter: brightness(1.1);
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-icon {
            position: absolute;
            right: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .decoration-blob {
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            filter: blur(40px);
        }

        @media (max-width: 991px) {
            .branding-panel { min-width: 100%; padding: 3rem 1.5rem; height: 350px; min-height: 350px; }
            .login-panel { min-width: 100%; padding: 3rem 1.5rem; }
            .glass-brand { padding: 1.5rem; }
            .logo-square { width: 80px; height: 80px; margin-bottom: 1rem; }
        }
    </style>
</head>
<body>

    <div class="split-container">
        <!-- Branding Panel -->
        <div class="branding-panel">
            <div class="decoration-blob" style="top: -50px; right: -50px;"></div>
            <div class="decoration-blob" style="bottom: -50px; left: -50px; background: rgba(0,0,0,0.05);"></div>
            
            <div class="branding-content">
                <div class="glass-brand">
                    <div class="logo-square">
                        <?php if (!empty($appLogo)): ?>
                            <img src="<?= $appLogo ?>" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        <?php else: ?>
                            <i class="fas fa-fingerprint fa-3x text-primary"></i>
                        <?php endif; ?>
                    </div>
                    <h1 class="fw-800 mb-2 display-6"><?= $appName ?></h1>
                    <p class="opacity-75 lead fw-bold mb-0">Authorized B2B & Partner Portal</p>
                    <div class="mt-4 d-none d-lg-block">
                        <small class="text-white-50">Enterprise Resource Planning & Business Management System</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Login Panel -->
        <div class="login-panel">
            <div class="login-box">
                <div class="mb-5">
                    <h2 class="fw-800 text-dark mb-2">Welcome Back</h2>
                    <p class="text-muted">Please enter your professional credentials to access your dashboard.</p>
                </div>

                <?php if (!empty($message)) { echo $message; } ?>

                <form action="index.php" method="POST">
                    <input type="hidden" name="action" value="login_submit">
                    
                    <div class="input-group-custom">
                        <label class="form-label">Professional Email</label>
                        <input type="email" class="form-control" name="email" placeholder="name@company.com" required>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>

                    <div class="input-group-custom">
                        <label class="form-label">Security Password</label>
                        <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember">
                            <label class="form-check-label text-muted small" for="remember">Keep me logged in</label>
                        </div>
                        <a href="#" class="text-primary small fw-bold text-decoration-none">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-auth w-100 mb-3">
                        <i class="fas fa-shield-alt me-2"></i> Authorized Login
                    </button>
                </form>

                <div class="text-center mt-5">
                    <a href="<?= BASE_URL ?>" class="text-muted text-decoration-none small transition-all hover-primary">
                        <i class="fas fa-arrow-left me-1"></i> Return to Website
                    </a>
                </div>
                
                <div class="mt-5 pt-4 text-center border-top">
                    <p class="text-muted small">Powered by <?= $appName ?> Security Protocol v<?= APP_VERSION ?></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Micro-interaction for label focus
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.previousElementSibling.style.color = '#4f46e5';
            });
            input.addEventListener('blur', () => {
                input.previousElementSibling.style.color = '#64748b';
            });
        });
    </script>
</body>
</html>