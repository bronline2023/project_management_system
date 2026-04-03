<?php
/**
 * views/b2c_home.php
 * REDESIGNED: Premium Digital Services Landing Page.
 * - Global High-End Hero Slider.
 * - 3D Glassmorphism Service Cards.
 * - Modern Pro Subscription Pricing.
 */
include __DIR__ . '/includes/b2c_header.php';

$pdo = connectDB();

// Fetch sliders
$stmt = $pdo->query("SELECT * FROM b2c_sliders WHERE status='active' ORDER BY display_order ASC");
$sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch subscription plans
$plans = [];
try {
    // Correcting column name to is_active and table mapping
    $plan_stmt = $pdo->query("SELECT * FROM b2c_subscription_plans WHERE is_active = 1 ORDER BY price ASC LIMIT 6");
    $plans = $plan_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback if main table fails
    try {
        $plan_stmt = $pdo->query("SELECT * FROM subscription_plans LIMIT 6");
        $plans = $plan_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        $plans = [];
    }
}

?>

<style>
    :root {
        --brand-blue: #0ea5e9;
        --brand-indigo: #4f46e5;
        --brand-crimson: #e11d48;
        --brand-dark: #1e293b;
        --glass-white: rgba(255, 255, 255, 0.7);
        --glass-border: rgba(255, 255, 255, 0.4);
    }

    body {
        font-family: 'Outfit', sans-serif;
        margin: 0;
        padding: 0;
        background: #f8fafc;
        color: #1e293b;
    }

    /* --- PREMIUM HERO SLIDER --- */
    #heroCarousel {
        margin-top: calc(-1 * var(--nav-height));
        height: 100vh;
        min-height: 700px;
        overflow: hidden;
    }

    .hero-slide-item {
        height: 100vh;
        min-height: 700px;
        position: relative;
    }

    .hero-slide-item img, .hero-slide-item video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scale(1.1);
        transition: transform 10s ease-out;
    }

    .carousel-item.active .hero-slide-item img {
        transform: scale(1);
    }

    .hero-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(0deg, rgba(15,23,42,0.95) 0%, rgba(15,23,42,0.6) 40%, rgba(15,23,42,0.1) 100%);
        z-index: 1;
    }

    .carousel-caption {
        z-index: 2;
        bottom: 25%;
        text-align: left;
        left: 10%;
        right: 10%;
        max-width: 900px;
    }

    .caption-label {
        display: inline-block;
        padding: 8px 20px;
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        border-radius: 50px;
        color: var(--brand-blue);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 13px;
        margin-bottom: 2rem;
        border: 1px solid rgba(255,255,255,0.2);
        animation: fadeInLeft 1s both;
    }

    .carousel-caption h1 {
        font-size: 6rem;
        font-weight: 800;
        letter-spacing: -3px;
        line-height: 1;
        margin-bottom: 2rem;
        animation: fadeInUp 1.1s 0.3s both cubic-bezier(0.165, 0.84, 0.44, 1);
        text-shadow: 0 10px 40px rgba(0,0,0,0.5);
        color: white;
    }

    .carousel-caption p {
        font-size: 1.5rem;
        font-weight: 400;
        color: rgba(255,255,255,0.85);
        max-width: 650px;
        line-height: 1.6;
        animation: fadeInUp 1.3s 0.6s both cubic-bezier(0.165, 0.84, 0.44, 1);
        margin-bottom: 3rem;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInLeft {
        from { opacity: 0; transform: translateX(-40px); }
        to { opacity: 1; transform: translateX(0); }
    }

    .pricing-caption {
        display: inline-block;
        padding: 10px 30px;
        background: rgba(79, 70, 229, 0.08);
        color: var(--brand-indigo);
        border: 1px solid rgba(79, 70, 229, 0.2);
        border-radius: 50px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 14px;
        margin-bottom: 2rem;
        position: relative;
    }

    .pricing-caption::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(79, 70, 229, 0.1);
        z-index: -1;
    }

    /* --- FALLBACK HERO --- */
    .hero-fallback {
        height: 100vh;
        display: flex;
        align-items: center;
        background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 50%, #e11d48 100%);
        background-size: 300% 300%;
        animation: gradientMove 15s ease infinite;
    }

    @keyframes gradientMove {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    /* --- SERVICE CARDS (3D GLASS) --- */
    .services-section {
        padding: 150px 0;
        background: #ffffff;
        position: relative;
    }

    .section-title {
        text-align: center;
        margin-bottom: 100px;
        position: relative;
    }

    .section-title h2 {
        font-size: 4rem;
        font-weight: 800;
        letter-spacing: -1.5px;
        color: var(--brand-indigo); /* Fallback for browsers that don't support text-clip */
        background: linear-gradient(135deg, var(--brand-indigo), var(--brand-blue), var(--brand-crimson));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 15px;
        line-height: 1.2;
    }

    .section-subtitle {
        font-size: 1.25rem;
        color: #64748b;
        font-weight: 500;
    }

    .service-card {
        background: #ffffff;
        border-radius: 40px;
        padding: 60px 40px;
        height: 100%;
        border: 1px solid #f1f5f9;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0,0,0,0.03);
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .service-card:hover {
        transform: translateY(-20px);
        box-shadow: 0 40px 80px rgba(79, 70, 229, 0.12);
        border-color: var(--brand-indigo);
    }

    .service-card .icon-box {
        width: 100px;
        height: 100px;
        border-radius: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        margin-bottom: 40px;
        transition: 0.3s;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    }

    .service-card:hover .icon-box {
        transform: scale(1.1) rotate(10deg);
        box-shadow: 0 15px 35px rgba(79, 70, 229, 0.2);
    }

    .service-card h3 {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--brand-dark);
        margin-bottom: 20px;
        letter-spacing: -0.5px;
    }

    .service-card p {
        color: #64748b;
        font-size: 1.1rem;
        line-height: 1.7;
        margin-bottom: 35px;
        flex-grow: 1;
    }

    .service-btn {
        background: #f8fafc;
        color: var(--brand-dark);
        border: none;
        padding: 14px 40px;
        border-radius: 20px;
        font-weight: 800;
        font-size: 0.95rem;
        transition: 0.3s;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        display: inline-block;
    }

    .service-card:hover .service-btn {
        background: var(--brand-indigo);
        color: white !important;
        border-color: var(--brand-indigo);
        box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
    }

    /* --- PRICING SECTION REDESIGNED --- */
    .pricing-section {
        padding: 150px 0;
        background: #f1f5f9;
        position: relative;
    }

    .pricing-card {
        background: white;
        border-radius: 40px;
        padding: 60px 45px;
        text-align: center;
        border: 1px solid #e2e8f0;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        height: 100%;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .pricing-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; height: 6px;
        background: linear-gradient(90deg, var(--brand-indigo), var(--brand-blue));
        opacity: 0;
        transition: 0.3s;
    }

    .pricing-card:hover::before { opacity: 1; }

    .pricing-card.premium {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: white;
        border: none;
        box-shadow: 0 40px 80px rgba(15, 23, 42, 0.2);
        z-index: 5;
    }

    .pricing-card.premium::before {
        background: linear-gradient(90deg, #fbbf24, #f59e0b);
        opacity: 1;
    }

    .pricing-card:hover {
        transform: translateY(-15px);
        box-shadow: 0 30px 60px rgba(0,0,0,0.1);
    }

    .pricing-card-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        border-radius: 20px;
        background: #f8fafc;
        color: var(--brand-indigo);
        transition: 0.3s;
    }

    .pricing-card.premium .pricing-card-icon {
        background: rgba(255, 255, 255, 0.05);
        color: #fbbf24;
    }

    .pricing-card:hover .pricing-card-icon {
        transform: scale(1.1) rotate(5deg);
    }

    .price-tag {
        font-size: 4rem;
        font-weight: 800;
        margin-bottom: 5px;
        letter-spacing: -2px;
    }

    .price-curr { font-size: 1.5rem; vertical-align: top; margin-right: 5px; opacity: 0.6; }

    .plan-badge {
        display: inline-block;
        padding: 6px 20px;
        border-radius: 50px;
        font-weight: 800;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 25px;
        background: rgba(79, 70, 229, 0.1);
        color: var(--brand-indigo);
    }

    .pricing-card.premium .plan-badge {
        background: rgba(251, 191, 36, 0.1);
        color: #fbbf24;
    }

    .btn-gradient-indigo {
        background: linear-gradient(135deg, var(--brand-indigo), var(--brand-blue));
        color: white; border: none; box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
    }
    .btn-gradient-indigo:hover { color: white; transform: translateY(-2px); box-shadow: 0 15px 25px rgba(79, 70, 229, 0.4); }

    .btn-gradient-gold {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #1e293b; border: none; box-shadow: 0 10px 20px rgba(245, 158, 11, 0.3);
    }
    .btn-gradient-gold:hover { color: #1e293b; transform: translateY(-2px); box-shadow: 0 15px 25px rgba(245, 158, 11, 0.4); }

    /* --- CTA SECTION --- */
    .cta-banner {
        background: linear-gradient(135deg, #4f46e5, #e11d48);
        border-radius: 50px;
        padding: 6rem 3rem;
        margin: 100px 0;
        color: white;
        text-align: center;
        box-shadow: 0 30px 60px rgba(79, 70, 229, 0.4);
        position: relative;
        overflow: hidden;
    }

    .cta-btn {
        background: white;
        color: var(--brand-primary);
        font-weight: 800;
        padding: 1.2rem 4rem;
        border-radius: 50px;
        text-transform: uppercase;
        font-size: 1.1rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    @media (max-width: 991px) {
        .carousel-caption h1 { font-size: 3rem; }
        .pricing-card.premium { transform: scale(1); margin: 2rem 0; }
        .section-title h2 { font-size: 2.5rem; }
    }
</style>

<!-- SERVICES REDESIGNED -->
<section class="services-section">
    <div class="container">
        <div class="section-title">
            <h6 class="text-primary fw-800 text-uppercase letter-spacing-2 mb-3">Our Toolkit</h6>
            <h2>Advanced Studio Suite</h2>
        </div>
        
        <div class="row g-5">
            <?php 
            $services = [
                ['title' => 'Poster Studio', 'icon' => 'fa-wand-magic-sparkles', 'color' => '#6366f1', 'slug' => 'poster_studio', 'desc' => 'High-end design studio for professional social media posters.'],
                ['title' => 'Resume PRO', 'icon' => 'fa-file-invoice', 'color' => '#0ea5e9', 'slug' => 'resume_builder', 'desc' => 'Modern ATS-friendly resume templates for high-tier career growth.'],
                ['title' => 'PVC Smart Card', 'icon' => 'fa-id-card-clip', 'color' => '#f59e0b', 'slug' => 'smart_card', 'desc' => 'One-click generation of HD PVC ID cards with precision cropping.'],
                ['title' => 'Photo Studio', 'icon' => 'fa-camera-retro', 'color' => '#ec4899', 'slug' => 'photo_studio', 'desc' => 'Advanced photo retouching, colorization, and background removal.'],
                ['title' => 'Doc Converter', 'icon' => 'fa-sync', 'color' => '#10b981', 'slug' => 'document_converter', 'desc' => 'Batch processing for PDF, DOC, and Image format conversions.'],
                ['title' => 'Size Converter', 'icon' => 'fa-maximize', 'color' => '#f97316', 'slug' => 'size_converter', 'desc' => 'Instant conversion of images and documents to accurate printing sizes.'],
                ['title' => 'Passport Photo', 'icon' => 'fa-user-gear', 'color' => '#8b5cf6', 'slug' => 'passport_photo', 'desc' => 'AI-driven passport photo generation with official standards.']
            ];
            foreach($services as $s): ?>
            <div class="col-lg-4 col-md-6">
                <div class="service-card">
                    <div class="icon-box" style="color: <?= $s['color'] ?>; background: <?= $s['color'] ?>15;">
                        <i class="fas <?= $s['icon'] ?>"></i>
                    </div>
                    <h3><?= $s['title'] ?></h3>
                    <p><?= $s['desc'] ?></p>
                    <a href="<?= BASE_URL ?>?page=<?= $s['slug'] ?>" class="btn service-btn">Access Studio</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FULL-HEIGHT HERO SLIDER (Moved Below Services) -->
<?php if(count($sliders) > 0): ?>
<div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <?php foreach($sliders as $idx => $slide): ?>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $idx ?>" class="<?= $idx === 0 ? 'active' : '' ?>"></button>
        <?php endforeach; ?>
    </div>
    <div class="carousel-inner">
        <?php foreach($sliders as $idx => $slide): ?>
        <div class="carousel-item hero-slide-item <?= $idx === 0 ? 'active' : '' ?>">
            <?php if($slide['media_type'] === 'video'): ?>
                <video src="<?= BASE_URL . $slide['media_path'] ?>" autoplay loop muted></video>
            <?php else: ?>
                <img src="<?= BASE_URL . $slide['media_path'] ?>" alt="<?= htmlspecialchars($slide['title']) ?>">
            <?php endif; ?>
            <div class="hero-overlay"></div>
            <div class="carousel-caption">
                <span class="caption-label">Authorized Service</span>
                <h1 class="display-1 fw-800"><?= htmlspecialchars($slide['title']) ?></h1>
                <p><?= htmlspecialchars($slide['description']) ?></p>
                <div class="d-flex gap-3 mt-4">
                    <a href="<?= htmlspecialchars($slide['link'] ?? '#') ?>" class="btn btn-nav-primary px-5 py-3 fs-5 shadow-lg">
                        Explore <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                    <a href="<?= BASE_URL ?>?page=appointment" class="btn btn-nav-outline px-5 py-3 fs-5">
                        Book Visit
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<!-- Fallback -->
<div class="hero-fallback text-white text-center">
    <div class="container py-5">
        <h1 class="display-1 fw-800 mb-4">A New Era of Digital Services</h1>
        <p class="lead mb-5 fs-4">Join 50k+ users who trust our advanced digital toolsuite for daily workflow.</p>
        <a href="<?= BASE_URL ?>?page=b2c_register" class="btn btn-light btn-lg rounded-pill px-5 py-3 fw-bold text-primary shadow-lg">Start Free Trial</a>
    </div>
</div>
<?php endif; ?>

<!-- PRICING REDESIGNED -->
<section class="pricing-section">
    <div class="container">
        <div class="section-title">
            <h6 class="pricing-caption">Membership Perks</h6>
            <h2>Unlimited Access</h2>
        </div>

        <div class="row g-4 justify-content-center">
            <?php foreach($plans as $plan): 
                $isPremium = ($plan['price'] > 500);
                $cardIcon = 'fa-shield-halved';
                if ($plan['price'] > 200) $cardIcon = 'fa-rocket';
                if ($isPremium) $cardIcon = 'fa-crown';
            ?>
            <div class="col-lg-4">
                <div class="pricing-card <?= $isPremium ? 'premium' : '' ?>">
                    <div class="pricing-card-icon">
                        <i class="fas <?= $cardIcon ?>"></i>
                    </div>
                    <span class="plan-badge"><?= htmlspecialchars($plan['plan_name']) ?></span>
                    <div class="price-tag"><span class="price-curr">₹</span><?= number_format($plan['price'], 0) ?></div>
                    <p class="opacity-75 mb-4">Validity: <?= $plan['validity_days'] ?? $plan['duration_days'] ?? 'N/A' ?> Days</p>
                    
                    <ul class="list-unstyled mb-5 <?= $isPremium ? 'opacity-90' : 'opacity-75' ?>" style="line-height: 2.2; text-align: left;">
                        <?php 
                        $benefits = explode("\n", $plan['description']);
                        foreach($benefits as $b): if(trim($b)):
                        ?>
                        <li><i class="fas fa-circle-check <?= $isPremium ? 'text-warning' : 'text-primary' ?> me-2"></i> <?= htmlspecialchars(trim($b)) ?></li>
                        <?php endif; endforeach; ?>
                    </ul>

                    <div class="mt-auto">
                        <a href="<?= BASE_URL ?>?page=buy_subscription&plan_id=<?= $plan['id'] ?>" class="btn <?= $isPremium ? 'btn-gradient-gold' : 'btn-gradient-indigo' ?> w-100 rounded-pill py-3 fw-bold">
                            Activate Account <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA BANNER REDESIGNED -->
<div class="container pb-5">
    <div class="cta-banner">
        <h2 class="display-4 fw-800 mb-3">Claim Your First 10 Points Free</h2>
        <p class="fs-4 opacity-75 mb-5 mx-auto" style="max-width: 700px;">Join 50k+ users who trust our advanced digital toolsuite for daily workflow and pro designs.</p>
        <a href="<?= BASE_URL ?>?page=b2c_register" class="btn cta-btn">Join Now <i class="fas fa-arrow-right ms-2"></i></a>
    </div>
</div>

<?php include __DIR__ . '/includes/b2c_footer.php'; ?>
