<?php
// views/about_us.php
if (file_exists(__DIR__ . '/includes/b2c_header.php')) {
    require_once __DIR__ . '/includes/b2c_header.php';
}
?>

<div class="container-fluid py-5 bg-light">
    <div class="container">
        <div class="row align-items-center mb-5">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h1 class="display-4 fw-bold text-dark mb-4">Empowering Your <span class="text-primary">Digital Journey</span></h1>
                <p class="lead text-muted mb-4">
                    Welcome to BR Online Services, India's leading B2B SaaS and B2C Digital Services platform. We provide an integrated ecosystem designed to help retailers, freelancers, and businesses establish their own digital presence and offer top-tier online services to their customers efficiently.
                </p>
                <div class="d-flex align-items-center">
                    <div class="me-4 text-center">
                        <h2 class="fw-bold text-success mb-0">50K+</h2>
                        <span class="text-muted small text-uppercase fw-bold">Active Retailers</span>
                    </div>
                    <div class="text-center">
                        <h2 class="fw-bold text-warning mb-0">1M+</h2>
                        <span class="text-muted small text-uppercase fw-bold">Services Delivered</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <img src="assets/images/about_hero.jpg" onerror="this.src='https://images.unsplash.com/photo-1522071820081-009f0129c71c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'" alt="About BR Online Services" class="img-fluid w-100">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row text-center mb-5">
        <div class="col-12">
            <h2 class="fw-bold text-dark mb-3">Our Mission & Vision</h2>
            <p class="text-muted mx-auto" style="max-width: 700px;">To bridge the digital divide by equipping local entrepreneurs with enterprise-grade cloud tools and helping individuals create professional documents seamlessly.</p>
        </div>
    </div>
    <div class="row g-4 justify-content-center">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 p-4 text-center rounded-4 hover-lift">
                <div class="icon-circle mx-auto mb-4 bg-primary text-white d-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px;">
                    <i class="fas fa-rocket fa-2x"></i>
                </div>
                <h5 class="fw-bold mb-3">Innovation Built-In</h5>
                <p class="text-muted mb-0">We consistently push the boundaries, providing robust state-of-the-art tools ranging from Resume Builders to full-fledged B2B White Label Portals.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 p-4 text-center rounded-4 hover-lift">
                <div class="icon-circle mx-auto mb-4 bg-success text-white d-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px;">
                    <i class="fas fa-handshake fa-2x"></i>
                </div>
                <h5 class="fw-bold mb-3">Partner Success</h5>
                <p class="text-muted mb-0">Your growth is our priority. By delivering our white-label SaaS structure, you can launch your own tech business under your own domain instantly.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 p-4 text-center rounded-4 hover-lift">
                <div class="icon-circle mx-auto mb-4 bg-warning text-white d-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px;">
                    <i class="fas fa-shield-alt fa-2x"></i>
                </div>
                <h5 class="fw-bold mb-3">Unmatched Security</h5>
                <p class="text-muted mb-0">Platform stability, secure payment gateways, and encrypted credentials ensure that our network of retailers operates without a single worry.</p>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid py-5 bg-dark text-white text-center">
    <div class="container">
        <h2 class="fw-bold mb-4">Start Your Digital Business Today</h2>
        <p class="lead mb-5 mx-auto opacity-75" style="max-width: 600px;">Join our growing family of digital retailers and freelancers. Whether you need a simple ID card creation or a master distributor portal, we've got you covered.</p>
        <a href="?page=register" class="btn btn-primary btn-lg px-5 rounded-pill fw-bold shadow-sm me-3">Become a Partner</a>
        <a href="?page=contact_us" class="btn btn-outline-light btn-lg px-5 rounded-pill fw-bold">Contact Sales</a>
    </div>
</div>

<style>
.hover-lift { transition: transform 0.3s ease, box-shadow 0.3s ease; }
.hover-lift:hover { transform: translateY(-10px); box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important; }
</style>

<?php
if (file_exists(__DIR__ . '/includes/b2c_footer.php')) {
    require_once __DIR__ . '/includes/b2c_footer.php';
}
?>
