<?php
// views/contact_us.php
if (file_exists(__DIR__ . '/includes/b2c_header.php')) {
    require_once __DIR__ . '/includes/b2c_header.php';
}
?>

<div class="container-fluid py-5 bg-light" style="min-height: 80vh;">
    <div class="container">
        <div class="row mb-5 text-center">
            <div class="col-12">
                <h1 class="display-5 fw-bold text-dark mb-3">Get in <span class="text-primary">Touch</span></h1>
                <p class="lead text-muted mx-auto" style="max-width: 600px;">Have questions about our B2B SaaS portal or standard retail subscriptions? Our team is standing by to help you grow your business.</p>
            </div>
        </div>

        <div class="row g-5">
            <!-- Contact Info -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-5">
                        <h4 class="fw-bold mb-4">Contact Information</h4>
                        
                        <div class="d-flex align-items-start mb-4">
                            <div class="icon-box bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-4">
                                <i class="fas fa-map-marker-alt fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Head Office</h6>
                                <p class="text-muted mb-0">BR Online Services Pvt. Ltd.<br>IT Park, Sector 63, Noida, India - 201301</p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start mb-4">
                            <div class="icon-box bg-success bg-opacity-10 text-success rounded-circle p-3 me-4">
                                <i class="fas fa-phone-alt fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Call Us</h6>
                                <p class="text-muted mb-0">+91 98765 43210<br><small class="text-muted">Mon-Sat, 9am to 6pm (IST)</small></p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start mb-4">
                            <div class="icon-box bg-warning bg-opacity-10 text-warning rounded-circle p-3 me-4">
                                <i class="fas fa-envelope fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Email Support</h6>
                                <p class="text-muted mb-0">support@bronline.in<br>sales@bronline.in</p>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="fw-bold mb-3">Follow Us</h6>
                        <div class="d-flex gap-3">
                            <a href="#" class="btn btn-outline-primary rounded-circle"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="btn btn-outline-info rounded-circle"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="btn btn-outline-dark rounded-circle"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#" class="btn btn-outline-danger rounded-circle"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-5">
                        <h4 class="fw-bold mb-4">Send Us a Message</h4>
                        <form action="#" method="POST" onsubmit="event.preventDefault(); alert('Thank you for reaching out! Our team will contact you shortly.');">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg bg-light border-0" required placeholder="John">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Last Name</label>
                                    <input type="text" class="form-control form-control-lg bg-light border-0" placeholder="Doe">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control form-control-lg bg-light border-0" required placeholder="john@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control form-control-lg bg-light border-0" required placeholder="+91 00000 00000">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Subject / Reason for Inquiry</label>
                                    <select class="form-select form-select-lg bg-light border-0">
                                        <option>Setup a B2B Portal (SaaS)</option>
                                        <option>Retailer API Integration</option>
                                        <option>Technical Support</option>
                                        <option>Billing & Subscriptions</option>
                                        <option>Other / General</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Your Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control bg-light border-0" rows="5" required placeholder="Tell us how we can help you..."></textarea>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold rounded-pill shadow-sm py-3">
                                        <i class="fas fa-paper-plane me-2"></i> Submit Inquiry
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (file_exists(__DIR__ . '/includes/b2c_footer.php')) {
    require_once __DIR__ . '/includes/b2c_footer.php';
}
?>
