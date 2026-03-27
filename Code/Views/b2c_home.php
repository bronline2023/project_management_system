<?php
// b2c_home.php
include __DIR__ . '/includes/b2c_header.php';

$pdo = connectDB();

// Fetch sliders
$stmt = $pdo->query("SELECT * FROM b2c_sliders WHERE status='active' ORDER BY display_order ASC");
$sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$plans = [];
try {
    $plan_stmt = $pdo->query("SELECT * FROM subscription_plans WHERE status='active' ORDER BY price ASC LIMIT 6");
    $plans = $plan_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

?>

<style>
    :root {
        --brand-blue: #0ea5e9;
        --brand-indigo: #4f46e5;
        --brand-dark: #0f172a;
        --glass-bg: rgba(255, 255, 255, 0.7);
        --glass-border: rgba(255, 255, 255, 0.4);
    }
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f8fafc;
        color: #334155;
    }
    /* Hero Carousel Adjustments */
    .hero-carousel {
        position: relative;
        overflow: hidden;
        border-radius: 0 0 40px 40px;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    }
    .hero-slide-item {
        height: 80vh;
        min-height: 600px;
    }
    .hero-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(180deg, rgba(15,23,42,0.1) 0%, rgba(15,23,42,0.85) 100%);
        z-index: 1;
    }
    .carousel-caption {
        z-index: 2;
        bottom: 15%;
        text-align: left;
        left: 8%;
        right: 8%;
    }
    .carousel-caption h1 {
        font-size: 4rem;
        font-weight: 900;
        letter-spacing: -1px;
        line-height: 1.1;
        margin-bottom: 1rem;
        text-shadow: 0 4px 20px rgba(0,0,0,0.5);
        animation: fadeInUp 1s ease-out;
    }
    .carousel-caption p {
        font-size: 1.25rem;
        font-weight: 400;
        opacity: 0.9;
        max-width: 600px;
        text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        animation: fadeInUp 1.2s ease-out;
    }
    /* Dynamic Animated Fallback Gradient */
    .gradient-hero {
        background: linear-gradient(-45deg, #4f46e5, #0ea5e9, #2563eb, #3b82f6);
        background-size: 400% 400%;
        animation: gradientBG 15s ease infinite;
        height: 70vh;
        display: flex;
        align-items: center;
        border-radius: 0 0 40px 40px;
    }
    @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    .gradient-hero h1 {
        font-size: 4rem; font-weight: 900; color: white;
    }
    
    /* Glassmorphism Services Cards */
    .services-grid {
        margin-top: -60px;
        position: relative;
        z-index: 10;
    }
    .service-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border);
        border-radius: 20px;
        padding: 2.5rem 1.5rem;
        text-align: center;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        position: relative;
        overflow: hidden;
    }
    .service-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, var(--brand-blue), var(--brand-indigo));
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .service-card:hover {
        transform: translateY(-12px);
        box-shadow: 0 20px 40px rgba(14, 165, 233, 0.15);
        background: white;
    }
    .service-card:hover::before {
        opacity: 1;
    }
    .icon-wrapper {
        width: 80px; height: 80px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1.5rem auto;
        font-size: 2rem;
        background: rgba(14, 165, 233, 0.1);
        color: var(--brand-blue);
        transition: all 0.3s ease;
    }
    .service-card:hover .icon-wrapper {
        background: linear-gradient(135deg, var(--brand-blue), var(--brand-indigo));
        color: white;
        transform: scale(1.1) rotate(5deg);
    }
    .service-title {
        font-weight: 800; font-size: 1.25rem; color: var(--brand-dark); margin-bottom: 0.75rem;
    }
    .service-btn {
        margin-top: 1.5rem; border-radius: 50px; font-weight: 600; padding: 0.5rem 1.5rem;
        transition: all 0.3s;
    }
    
    /* Pricing Section */
    .pricing-heading { text-align: center; margin: 5rem 0 3rem 0; }
    .pricing-heading h2 { font-weight: 900; font-size: 2.5rem; color: var(--brand-dark); }
    .pricing-card {
        border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: all 0.4s; position: relative; overflow: hidden;
    }
    .pricing-card:hover { transform: translateY(-10px); box-shadow: 0 20px 50px rgba(0,0,0,0.15); }
    .pricing-header { padding: 2.5rem 2rem 1rem 2rem; background: var(--brand-dark); color: white; border-radius: 24px 24px 0 0; }
    .pricing-amount { font-size: 3.5rem; font-weight: 900; color: var(--brand-blue); line-height: 1; margin: 1.5rem 0; }
    
    /* CTA Section */
    .cta-section {
        background: linear-gradient(135deg, var(--brand-indigo), var(--brand-blue));
        border-radius: 30px;
        padding: 5rem 2rem;
        text-align: center;
        color: white;
        margin: 5rem 0;
        box-shadow: 0 20px 40px rgba(79, 70, 229, 0.3);
        position: relative;
        overflow: hidden;
    }
    .cta-section::after {
        content: ''; position: absolute; top: -50%; right: -10%; width: 400px; height: 400px; background: rgba(255,255,255,0.1); border-radius: 50%; blur: 50px;
    }
    .cta-btn {
        background: white; color: var(--brand-indigo); font-weight: 800; font-size: 1.25rem; padding: 1rem 3rem; border-radius: 50px; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s;
    }
    .cta-btn:hover { background: var(--brand-dark); color: white; transform: scale(1.05); }

    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @media (max-width: 768px) {
        .carousel-caption h1 { font-size: 2.5rem; }
        .hero-slide-item { min-height: 400px; height: 60vh; }
        .services-grid { margin-top: 2rem; }
    }
</style>

<!-- HERO SECTION: Carousel with Image/Video -->
<?php if(count($sliders) > 0): ?>
<div id="heroCarousel" class="carousel slide hero-carousel carousel-fade" data-bs-ride="carousel">
  <div class="carousel-indicators">
    <?php foreach($sliders as $idx => $slide): ?>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $idx ?>" class="<?= $idx === 0 ? 'active' : '' ?>"></button>
    <?php endforeach; ?>
  </div>
  <div class="carousel-inner">
    <?php foreach($sliders as $idx => $slide): ?>
    <div class="carousel-item hero-slide-item <?= $idx === 0 ? 'active' : '' ?>">
        <?php if($slide['media_type'] === 'video'): ?>
            <video src="<?= BASE_URL . $slide['media_path'] ?>" autoplay loop muted class="d-block w-100 h-100" style="object-fit: cover;"></video>
        <?php else: ?>
            <img src="<?= BASE_URL . $slide['media_path'] ?>" class="d-block w-100 h-100" style="object-fit: cover;" alt="<?= htmlspecialchars($slide['title']) ?>">
        <?php endif; ?>
      
      <div class="hero-overlay"></div>
      <div class="carousel-caption">
        <h1><?= htmlspecialchars($slide['title']) ?></h1>
        <p class="lead"><?= htmlspecialchars($slide['description']) ?></p>
        <?php if(!empty($slide['link'])): ?>
            <a href="<?= htmlspecialchars($slide['link']) ?>" class="btn btn-warning btn-lg rounded-pill px-5 mt-3 fw-bold shadow">Learn More <i class="fas fa-arrow-right ms-2"></i></a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Previous</span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Next</span>
  </button>
</div>
<?php else: ?>
<!-- Fallback Animated Hero -->
<div class="gradient-hero px-4">
    <div class="container text-center">
        <h1 class="mb-3 text-shadow">Welcome to <?= APP_NAME ?></h1>
        <p class="lead text-white opacity-75 mb-5 fs-4">Redefining digital services with simplicity, speed, and premium quality.</p>
        <a href="<?= BASE_URL ?>?page=b2c_register" class="btn btn-light btn-lg px-5 py-3 rounded-pill fw-bold shadow-lg text-primary">Get Your 10 Free Points! <i class="fas fa-magic ms-2"></i></a>
    </div>
</div>
<?php endif; ?>

<!-- SERVICES GRID -->
<div class="container services-grid">
    <div class="row g-4 justify-content-center">
        <?php 
        $services = [
            ['title' => 'Poster Studio', 'icon' => 'fa-paint-brush', 'desc' => 'Design stunning social media posters natively.', 'link' => 'poster_studio', 'color' => '#ef4444'],
            ['title' => 'Resume Builder', 'icon' => 'fa-file-alt', 'desc' => 'Craft modern resumes that land jobs instantly.', 'link' => 'resume_builder', 'color' => '#0ea5e9'],
            ['title' => 'Smart Card', 'icon' => 'fa-id-card', 'desc' => 'Generate flawless HD PVC smart cards in a click.', 'link' => 'smart_card', 'color' => '#f59e0b'],
            ['title' => 'Passport Photo', 'icon' => 'fa-user-tie', 'desc' => 'Auto-crop precision passport photos natively.', 'link' => 'passport_photo', 'color' => '#8b5cf6'],
            ['title' => 'Document Converter', 'icon' => 'fa-sync-alt', 'desc' => 'Blazing fast, secure format conversions.', 'link' => 'document_converter', 'color' => '#10b981'],
            ['title' => 'Photo Studio Pro', 'icon' => 'fa-camera-retro', 'desc' => 'High-end photo enhancement studio.', 'link' => 'photo_studio', 'color' => '#ec4899']
        ];
        foreach($services as $srv): 
        ?>
        <div class="col-xl-4 col-md-6">
            <div class="service-card">
                <div class="icon-wrapper" style="color: <?= $srv['color'] ?>; background: <?= $srv['color'] ?>15;">
                    <i class="fas <?= $srv['icon'] ?>"></i>
                </div>
                <h3 class="service-title"><?= $srv['title'] ?></h3>
                <p class="text-muted mb-4"><?= $srv['desc'] ?></p>
                <a href="<?= BASE_URL ?>?page=<?= $srv['link'] ?>" class="btn btn-outline-primary w-100 service-btn rounded-pill">Launch Editor</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- SUBSCRIPTION PRICING -->
<?php if(count($plans) > 0): ?>
<div class="container">
    <div class="pricing-heading">
        <h2>Unleash Unlimited Access</h2>
        <p class="text-muted fs-5">Choose a Pro plan to bypass paywalls on all digital tools.</p>
    </div>
    <div class="row g-5 justify-content-center">
        <?php foreach($plans as $plan): ?>
        <div class="col-lg-4 col-md-6">
            <div class="card pricing-card h-100">
                <div class="pricing-header text-center">
                    <h3 class="fw-bold mb-0"><?= htmlspecialchars($plan['plan_name']) ?></h3>
                </div>
                <div class="card-body text-center p-5 d-flex flex-column">
                    <div class="pricing-amount">₹<?= number_format($plan['price'], 0) ?></div>
                    <p class="fw-bold text-muted text-uppercase letter-spacing-1 mb-4">Per <?= htmlspecialchars($plan['duration_days']) ?> Days</p>
                    
                    <ul class="list-unstyled text-start mb-5" style="line-height: 2.5;">
                        <?php 
                        $benefits = explode("\n", $plan['description']);
                        foreach($benefits as $b):
                            if(trim($b)):
                        ?>
                        <li><i class="fas fa-check-circle text-success me-2"></i> <?= htmlspecialchars(trim($b)) ?></li>
                        <?php endif; endforeach; ?>
                    </ul>
                    
                    <div class="mt-auto mt-4">
                        <a href="<?= BASE_URL ?>?page=buy_subscription&plan_id=<?= $plan['id'] ?>" class="btn btn-primary btn-lg w-100 rounded-pill shadow-sm fw-bold">Select Plan</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- CTA BANNER -->
<div class="container">
    <div class="cta-section">
        <h2 class="display-5 fw-bold mb-3">Your Journey Starts Here</h2>
        <p class="fs-4 mb-5 opacity-75">Sign up in seconds and get your first <strong>10 Points</strong> absolutely free!</p>
        <a href="<?= BASE_URL ?>?page=b2c_register" class="btn cta-btn shadow-lg">Claim Your Free Points <i class="fas fa-chevron-right ms-2 position-relative" style="top:2px;"></i></a>
    </div>
</div>

<?php include __DIR__ . '/includes/b2c_footer.php'; ?>
