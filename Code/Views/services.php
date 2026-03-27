<?php
// views/services.php
$pdo = connectDB();
$services = $pdo->query("SELECT * FROM digital_service_rates WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$serviceIcons = [
    'poster_studio' => ['icon' => 'fa-paint-brush', 'color' => 'bg-danger'],
    'resume_builder' => ['icon' => 'fa-file-alt', 'color' => 'bg-info'],
    'smart_card' => ['icon' => 'fa-id-card', 'color' => 'bg-success'],
    'passport_photo' => ['icon' => 'fa-camera-retro', 'color' => 'bg-primary'],
    'document_converter' => ['icon' => 'fa-file-pdf', 'color' => 'bg-warning'],
    'size_converter' => ['icon' => 'fa-compress-arrows-alt', 'color' => 'bg-secondary'],
    'photo_studio' => ['icon' => 'fa-images', 'color' => 'bg-purple']
];

if (file_exists(__DIR__ . '/includes/b2c_header.php')) {
    require_once __DIR__ . '/includes/b2c_header.php';
}
?>
<style>.bg-purple { background-color: #6f42c1; }</style>

<!-- Hero Section -->
<div class="container-fluid py-5 bg-dark text-white text-center">
    <div class="container pb-4">
        <h1 class="display-4 fw-bold mb-3">Our <span class="text-warning">Digital Services</span></h1>
        <p class="lead opacity-75 mx-auto" style="max-width: 700px;">Access a powerful suite of cloud-based digital tools. Save time, reduce complex software overhead, and generate professional documents directly from your browser.</p>
    </div>
</div>

<!-- SaaS Portals Overview -->
<div class="container-fluid py-5 bg-light border-bottom">
    <div class="container text-center">
        <div class="row align-items-center justify-content-center">
            <div class="col-lg-8">
                <span class="badge bg-warning text-dark px-3 py-2 text-uppercase fw-bold rounded-pill mb-3">B2B Exclusive</span>
                <h2 class="fw-bold text-dark mb-4">Start Your Own Brand Portal (SaaS)</h2>
                <p class="text-muted lead mb-4">With our exclusive B2B Master Subscription, we set up an identical software architecture under your custom domain. You can create your own network of managers, district managers, and retailers—and dictate your own commission margins.</p>
                <a href="?page=buy_subscription" class="btn btn-dark btn-lg px-4 rounded-pill fw-bold"><i class="fas fa-crown text-warning me-2"></i> View B2B Plans</a>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic B2C Digital Services -->
<div class="container py-5 my-3">
    <div class="text-center mb-5">
        <h3 class="fw-bold">Individual Retail & User Services</h3>
        <p class="text-muted">Pay-per-use or grab an unlimited plan to use our core utilities.</p>
    </div>

    <div class="row g-4 flex-wrap justify-content-center">
        <?php foreach($services as $svc): 
            $slug = $svc['service_slug'];
            $iconInfo = $serviceIcons[$slug] ?? ['icon' => 'fa-star', 'color' => 'bg-primary'];
        ?>
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm h-100 text-center rounded-4 service-card">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="icon-wrap <?= $iconInfo['color'] ?> text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 shadow-sm" style="width: 70px; height: 70px;">
                        <i class="fas <?= $iconInfo['icon'] ?> fa-2x"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-3"><?= htmlspecialchars($svc['service_name']) ?></h5>
                    <p class="text-muted small mb-4 flex-grow-1">Advanced and responsive tool loaded with features directly on your dashboard.</p>
                    <a href="?page=<?= htmlspecialchars($slug) ?>" class="btn btn-outline-primary w-100 rounded-pill fw-bold hover-scale">Access Tool <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Unlimited B2C Subscription Promo -->
<div class="container mb-5">
    <div class="card bg-primary text-white border-0 shadow-lg rounded-4 overflow-hidden position-relative pt-4">
        <div class="card-body p-5">
            <div class="row align-items-center z-1 position-relative">
                <div class="col-md-8 text-md-start text-center mb-4 mb-md-0">
                    <h2 class="fw-bold mb-3">Tired of Paying Per Document?</h2>
                    <p class="lead opacity-75 mb-0">Switch to our unlimited B2C Digital Master Plan. Perform unlimited tool generation including posters, resumes, smart cards, and more without any deductibles.</p>
                </div>
                <div class="col-md-4 text-md-end text-center">
                    <a href="?page=buy_subscription" class="btn btn-light text-primary btn-lg px-5 fw-bold rounded-pill mx-auto">Get B2C Plan</a>
                </div>
            </div>
        </div>
        <!-- Decorative SVG / Circle in background -->
        <div class="position-absolute end-0 bottom-0 opacity-25" style="transform: scale(2) translate(10%, 10%);">
            <i class="fas fa-box-open" style="font-size: 15rem;"></i>
        </div>
    </div>
</div>

<style>
.service-card { transition: all 0.3s ease; }
.service-card:hover { transform: translateY(-8px); box-shadow: 0 1rem 3rem rgba(0,0,0,.15)!important; }
.hover-scale { transition: transform 0.2s; }
.hover-scale:hover { transform: scale(1.05); }
</style>

<?php
if (file_exists(__DIR__ . '/includes/b2c_footer.php')) {
    require_once __DIR__ . '/includes/b2c_footer.php';
}
?>
