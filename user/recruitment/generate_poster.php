<?php
/**
 * user/recruitment/generate_poster.php
 * FINAL & COMPLETE VERSION with ALL FEATURES:
 * - FIXED: All theme switching and preview update bugs, including watermark theme and layout issues.
 * - PERFORMANCE: Optimized html2canvas scale for significantly faster generation.
 * - NEW THEMES: Includes professional 'Sarkari Yojana', 'Sarkari Bharti', and 'AI Futuristic' themes.
 * - DYNAMIC FORM: Form fields intelligently switch between Recruitment and Scheme layouts.
 * - OPTIONAL DATES: Scheme theme now has an option to show or hide the date section.
 * - BACKGROUND SELECTOR: Users can now select custom backgrounds from a list or upload their own.
 */
?>

<h2 class="mb-4">Recruitment & Scheme Poster Generator</h2>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Poster Settings</h5>
            </div>
            <div class="card-body" style="max-height: calc(100vh - 220px); overflow-y: auto;">
                <div class="mb-3">
                    <label for="designSelector" class="form-label fw-bold">1. Select Poster Theme:</label>
                    <select id="designSelector" class="form-select">
                        <option value="design-1">Theme 1: Professional Blue</option>
                        <option value="design-2">Theme 2: Vibrant Orange</option>
                        <option value="design-3">Theme 3: Modern Dark</option>
                        <option value="design-4">Theme 4: Playful Green</option>
                        <option value="design-5">Theme 5: Elegant Purple</option>
                        <option value="design-6">Theme 6: BR Online Watermark</option>
                        <option value="design-sarkari-bharti">Theme 7: Sarkari Bharti</option>
                        <option value="design-sarkari-yojana">Theme 8: Sarkari Yojana</option>
                        <option value="design-ai-futuristic">Theme 9: AI Futuristic</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="backgroundSelector" class="form-label fw-bold">2. Select Background (Optional):</label>
                    <select id="backgroundSelector" class="form-select">
                        <option value="bg-default">Default (from Theme)</option>
                        <option value="bg-gradient-1">Abstract Blue Gradient</option>
                        <option value="bg-gradient-2">Soft Peach Gradient</option>
                        <option value="bg-gradient-3">Royal Gold Gradient</option>
                        <option value="bg-texture-1">Light Paper Texture</option>
                        <option value="bg-texture-2">Subtle Grid Pattern</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="backgroundUpload" class="form-label fw-bold">3. OR Upload Custom Background:</label>
                    <input type="file" id="backgroundUpload" class="form-control" accept="image/*">
                </div>

                <div id="recruitment-form">
                    <h5 class="mt-4 border-bottom pb-2">Header Content</h5>
                    <div class="mb-3"><label for="orgName" class="form-label fw-bold">Organization Name</label><input type="text" id="orgName" class="form-control" placeholder="e.g., Railway Recruitment Board"></div>
                    <div class="mb-3"><label for="recruitmentTitle" class="form-label fw-bold">Recruitment Title</label><input type="text" id="recruitmentTitle" class="form-control" placeholder="e.g., Technician Grade-I Recruitment"></div>
                    <h5 class="mt-4 border-bottom pb-2">Information Boxes</h5>
                    <div class="mb-3"><label class="form-label fw-bold">Vacancies Box</label><input type="text" id="vacanciesTitle" class="form-control mb-2" value="Total Vacancies"><input type="text" id="totalVacancies" class="form-control" placeholder="e.g., 9144 Posts"></div>
                    <div class="mb-3"><label class="form-label fw-bold">Eligibility Box</label><input type="text" id="eligibilityTitle" class="form-control mb-2" value="Eligibility"><textarea id="eligibility" class="form-control" rows="3" placeholder="e.g., ITI / Diploma / Degree"></textarea></div>
                    <div class="mb-3"><label class="form-label fw-bold">Age Limit Box</label><input type="text" id="ageLimitTitle" class="form-control mb-2" value="Age Limit"><input type="text" id="ageLimit" class="form-control" placeholder="e.g., 18 to 36 Years"></div>
                    <div class="mb-3"><label class="form-label fw-bold">Fees Details Box</label><input type="text" id="feesDetailsTitle" class="form-control mb-2" value="Fees Details"><input type="text" id="feesDetails" class="form-control" placeholder="e.g., General: ₹500/-"></div>
                </div>

                <div id="yojana-form" style="display: none;">
                    <h5 class="mt-4 border-bottom pb-2">Scheme Details</h5>
                    <div class="mb-3"><label for="yojanaName" class="form-label fw-bold">Scheme Name (યોજનાનું નામ)</label><input type="text" id="yojanaName" class="form-control" placeholder="e.g., PM-Kisan Samman Nidhi"></div>
                    <div class="mb-3"><label class="form-label fw-bold">Benefits (યોજનાના લાભ)</label><textarea id="yojanaBenefits" class="form-control" rows="3" placeholder="Enter scheme benefits, one per line..."></textarea></div>
                    <div class="mb-3"><label class="form-label fw-bold">Eligibility (પાત્રતા)</label><textarea id="yojanaEligibility" class="form-control" rows="3" placeholder="Enter eligibility criteria, one per line..."></textarea></div>
                    <div class="mb-3"><label class="form-label fw-bold">Required Documents (જરૂરી દસ્તાવેજો)</label><textarea id="yojanaDocuments" class="form-control" rows="3" placeholder="Enter required documents, one per line..."></textarea></div>
                </div>

                <h5 class="mt-4 border-bottom pb-2">Dates & Links</h5>
                <div class="form-check mb-3" id="date-enabler-container" style="display: none;">
                    <input class="form-check-input" type="checkbox" id="enableDates">
                    <label class="form-check-label" for="enableDates">Enable Start/End Dates for this Scheme</label>
                </div>
                <div class="row" id="date-fields-container">
                    <div class="col-md-6 mb-3"><label for="startDate" class="form-label fw-bold">Start Date</label><input type="date" id="startDate" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label for="endDate" class="form-label fw-bold">End Date</label><input type="date" id="endDate" class="form-control"></div>
                </div>
                <div class="mb-3"><label for="website" class="form-label fw-bold">Official Website</label><input type="text" id="website" class="form-control" placeholder="e.g., www.indianrailways.gov.in"></div>
                <div class="mb-3"><label for="companyWebsite" class="form-label fw-bold">Your Company Website</label><input type="text" id="companyWebsite" class="form-control" value="www.bronline.net"></div>
                <h5 class="mt-4 border-bottom pb-2">Logos</h5>
                <div class="mb-3"><label for="logoUpload" class="form-label fw-bold">Upload Official Logo</label><input type="file" id="logoUpload" class="form-control" accept="image/*"></div>
                <div class="d-grid mt-4"><button id="saveAndRedirectBtn" class="btn btn-success btn-lg"><i class="fas fa-save me-2"></i>Save Poster & Redirect</button></div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-eye me-2"></i>Live Preview</h5></div>
            <div class="card-body bg-light d-flex align-items-center justify-content-center p-3">
                <div id="poster-canvas" class="poster-container">
                    <div class="poster-header"><div class="poster-logo-container"><img id="logo-preview" src="https://bronline.net/images/logo.png" alt="Logo"></div><div class="poster-org-details"><h1 id="org-name-preview">Organization Name</h1><p id="website-preview">www.example.com</p></div></div>
                    <div class="poster-title"><h2 id="title-preview">Recruitment Title</h2></div>
                    <div class="poster-body recruitment-body">
                        <div class="poster-info-grid">
                            <div class="poster-info-box"><div class="poster-info-icon"><i class="fas fa-briefcase"></i></div><div class="poster-info-content"><h3 id="vacancies-title-preview">Total Vacancies</h3><p id="vacancies-preview">0000 Posts</p></div></div>
                            <div class="poster-info-box"><div class="poster-info-icon"><i class="fas fa-user-graduate"></i></div><div class="poster-info-content"><h3 id="eligibility-title-preview">Eligibility</h3><p id="eligibility-preview">Details here.</p></div></div>
                            <div class="poster-info-box"><div class="poster-info-icon"><i class="fas fa-birthday-cake"></i></div><div class="poster-info-content"><h3 id="age-limit-title-preview">Age Limit</h3><p id="age-limit-preview">Details here.</p></div></div>
                            <div class="poster-info-box"><div class="poster-info-icon"><i class="fas fa-rupee-sign"></i></div><div class="poster-info-content"><h3 id="fees-title-preview">Fees</h3><p id="fees-preview">Details here.</p></div></div>
                        </div>
                    </div>
                    <div class="poster-body yojana-body" style="display: none;">
                        <div class="poster-info-grid">
                             <div class="poster-info-box"><div class="poster-info-icon"><i class="fas fa-hands-helping"></i></div><div class="poster-info-content"><h3>Benefits (યોજનાના લાભ)</h3><p id="yojana-benefits-preview">Benefits details here.</p></div></div>
                             <div class="poster-info-box"><div class="poster-info-icon"><i class="fas fa-check-circle"></i></div><div class="poster-info-content"><h3>Eligibility (પાત્રતા)</h3><p id="yojana-eligibility-preview">Eligibility details here.</p></div></div>
                             <div class="poster-info-box"><div class="poster-info-icon"><i class="fas fa-file-alt"></i></div><div class="poster-info-content"><h3>Documents (જરૂરી દસ્તાવેજો)</h3><p id="yojana-documents-preview">Document details here.</p></div></div>
                        </div>
                    </div>
                    <div class="poster-footer">
                        <div class="poster-date-box"><h4>Start Date</h4><p id="start-date-preview">DD-MM-YYYY</p></div>
                        <div class="poster-date-box poster-last-date"><h4>Last Date</h4><p id="end-date-preview">DD-MM-YYYY</p></div>
                    </div>
                    <div class="poster-company-footer">
                        <div class="poster-company-logo"><img id="company-logo-preview" src="<?= BASE_URL ?>uploads/logo/logo.png" alt="Company Logo"></div>
                        <p id="company-website-preview">www.bronline.net</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Base Poster Styles */
    .poster-container { width: 100%; max-width: 500px; aspect-ratio: 1 / 1.414; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); display: flex; flex-direction: column; font-family: 'Poppins', sans-serif; color: #333; overflow: hidden; border: 1px solid #e0e0e0; position: relative; }
    .poster-header { padding: 1.2rem; display: flex; align-items: center; z-index: 1; } .poster-logo-container { width: 70px; height: 70px; flex-shrink: 0; margin-right: 1rem; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; padding: 5px; } .poster-logo-container img { max-width: 100%; max-height: 100%; object-fit: contain; } .poster-org-details h1 { font-size: 1.2rem; font-weight: 700; margin: 0; line-height: 1.2; } .poster-org-details p { font-size: 0.8rem; margin: 0; } .poster-title { text-align: center; padding: 0.5rem 1.2rem; z-index: 1;} .poster-title h2 { font-size: 1.5rem; font-weight: 700; line-height: 1.3; margin: 0; } .poster-body { flex-grow: 1; padding: 1rem 1.2rem; display: flex; flex-direction: column; justify-content: center; z-index: 1;} .poster-info-grid { display: grid; grid-template-columns: 1fr; gap: 0.6rem; } .poster-info-box { display: flex; align-items: center; padding: 0.7rem; border-radius: 10px; } .poster-info-icon { font-size: 1.4rem; margin-right: 0.8rem; width: 35px; text-align: center; } .poster-info-content h3 { font-size: 0.8rem; font-weight: 600; margin: 0 0 0.2rem 0; text-transform: uppercase; letter-spacing: 0.5px; } .poster-info-content p { font-size: 0.95rem; font-weight: 600; margin: 0; line-height: 1.3; } .poster-footer { display: flex; justify-content: space-around; padding: 0.9rem; margin-top: auto; z-index: 1;} .poster-date-box { text-align: center; } .poster-date-box h4 { font-size: 0.75rem; font-weight: 400; margin: 0 0 0.25rem 0; opacity: 0.8; text-transform: uppercase; } .poster-date-box p { font-size: 1.05rem; font-weight: 700; margin: 0; } .poster-company-footer { text-align: center; padding: 0.6rem; font-size: 0.8rem; z-index: 1;} .poster-company-logo { margin: 0 auto 0.3rem auto; width: 60px; height: 60px; background-color: #ffffff; border-radius: 8px; padding: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); } .poster-company-logo img { width: 100%; height: 100%; object-fit: contain; }
    
    /* Existing Designs */
    .design-1 { background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%); } .design-1 .poster-header { background: rgba(255, 255, 255, 0.6); } .design-1 .poster-title h2 { color: #b91c1c; } .design-1 .poster-info-box { background: rgba(255, 255, 255, 0.8); border-left: 4px solid #1e3a8a; } .design-1 .poster-info-icon, .design-1 .poster-org-details h1 { color: #1e3a8a; } .design-1 .poster-org-details p { color: #4b5563; } .design-1 .poster-info-content h3 { color: #6b7280; } .design-1 .poster-footer { background: #1e3a8a; color: #fff; } .design-1 .poster-date-box.poster-last-date p { color: #facc15; } .design-1 .poster-company-footer { background: #e0eafc; }
    .design-2 { background: linear-gradient(to top, #ff9a9e 0%, #fecfef 99%, #fecfef 100%); } .design-2 .poster-header { background: rgba(255, 255, 255, 0.7); } .design-2 .poster-title h2, .design-2 .poster-org-details h1 { color: #c81d25; } .design-2 .poster-org-details p { color: #58181c; } .design-2 .poster-info-box { background: rgba(255, 255, 255, 0.8); border-left: 4px solid #ff9a9e; } .design-2 .poster-info-icon { color: #ff9a9e; } .design-2 .poster-info-content h3 { color: #a13339; } .design-2 .poster-footer { background: #c81d25; color: #fff; } .design-2 .poster-company-footer { background: #fecfef; }
    .design-3 { background: linear-gradient(to right, #414345, #232526); color: #fff; } .design-3 .poster-header, .design-3 .poster-info-box { background: rgba(255, 255, 255, 0.1); border-left: 4px solid #00c6ff; } .design-3 .poster-org-details h1, .design-3 .poster-title h2, .design-3 .poster-info-icon, .design-3 .poster-date-box.poster-last-date p { color: #00c6ff; } .design-3 .poster-org-details p, .design-3 .poster-info-content h3 { color: #ccc; } .design-3 .poster-footer { background: #000; color: #fff; } .design-3 .poster-company-footer { background: #232526; color: #fff; }
    .design-4 { background: linear-gradient(120deg, #d4fc79 0%, #96e6a1 100%); } .design-4 .poster-header { background: rgba(255, 255, 255, 0.6); } .design-4 .poster-title h2, .design-4 .poster-org-details h1 { color: #228b22; } .design-4 .poster-org-details p { color: #2e8b57; } .design-4 .poster-info-box { background: rgba(255, 255, 255, 0.8); border-left: 4px solid #2e8b57; } .design-4 .poster-info-icon { color: #2e8b57; } .design-4 .poster-info-content h3 { color: #1e6a40; } .design-4 .poster-footer { background: #228b22; color: #fff; } .design-4 .poster-company-footer { background: #d4fc79; }
    .design-5 { background: linear-gradient(to right, #8e2de2, #4a00e0); color: #fff; } .design-5 .poster-header, .design-5 .poster-info-box { background: rgba(255, 255, 255, 0.15); border-left: 4px solid #fff; } .design-5 .poster-org-details h1, .design-5 .poster-title h2, .design-5 .poster-info-icon { color: #fff; text-shadow: 1px 1px 3px rgba(0,0,0,0.3); } .design-5 .poster-org-details p, .design-5 .poster-info-content h3 { color: #eee; } .design-5 .poster-footer { background: rgba(0,0,0,0.2); } .design-5 .poster-company-footer { background: linear-gradient(to right, #8e2de2, #4a00e0); }
    .design-6 { background-color: #f5f5f5; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='250' height='100'%3E%3Ctext x='50%25' y='50%25' font-size='16' fill='rgba(0,0,0,0.06)' transform='rotate(-30 125,50)' text-anchor='middle' font-family='Poppins, sans-serif' font-weight='600'%3Ewww.bronline.net%3C/text%3E%3C/svg%3E"); } .design-6 .poster-header { background: rgba(255, 255, 255, 0.7); } .design-6 .poster-title h2 { color: #d62828; } .design-6 .poster-info-box { background: rgba(255, 255, 255, 0.9); border-left: 4px solid #f77f00; } .design-6 .poster-info-icon, .design-6 .poster-org-details h1 { color: #003049; } .design-6 .poster-org-details p { color: #4b5563; } .design-6 .poster-info-content h3 { color: #6b7280; } .design-6 .poster-footer { background: #003049; color: #fff; } .design-6 .poster-date-box.poster-last-date p { color: #fcbf49; } .design-6 .poster-company-footer { background: #e9ecef; }
    .design-sarkari-bharti { background: #fff; border-top: 15px solid #FF9933; border-bottom: 15px solid #138808; position: relative; font-family: 'Arial', sans-serif;} .design-sarkari-bharti::before { content: ''; position: absolute; top: 15px; left: 0; width: 100%; height: 5px; background: #000080;} .design-sarkari-bharti .poster-logo-container { border-radius: 50%; border: 2px solid #000080; } .design-sarkari-bharti .poster-org-details h1 { color: #000080; } .design-sarkari-bharti .poster-org-details p { color: #555; } .design-sarkari-bharti .poster-title h2 { color: #D22B2B; font-weight: bold; border-bottom: 2px solid #FF9933; border-top: 2px solid #138808; padding: 10px 0; margin-top: 10px;} .design-sarkari-bharti .poster-info-box { background: #f5f5f5; border: 1px solid #ddd; border-left: 5px solid #000080;} .design-sarkari-bharti .poster-info-icon { color: #FF9933; } .design-sarkari-bharti .poster-info-content h3 { color: #555; } .design-sarkari-bharti .poster-info-content p { color: #111; font-weight: bold; } .design-sarkari-bharti .poster-footer { background: linear-gradient(to right, #FF9933, #138808); color: #fff; } .design-sarkari-bharti .poster-company-footer { background: #f0f0f0; }
    .design-sarkari-yojana { background: linear-gradient(135deg, #e3f2fd, #bbdefb); color: #0d47a1; border: 2px solid #0d47a1;} .design-sarkari-yojana::after { content: ''; background-image: url('https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/1200px-Emblem_of_India.svg.png'); background-size: contain; background-position: center; background-repeat: no-repeat; opacity: 0.05; top: 0; left: 0; bottom: 0; right: 0; position: absolute; z-index: 0; } .design-sarkari-yojana > * { z-index: 1; } .design-sarkari-yojana .poster-title h2 { color: #d32f2f; font-weight: bold; text-transform: uppercase; background: rgba(255,255,255,0.7); padding: 10px; border-radius: 5px;} .design-sarkari-yojana .poster-body { padding: 1.5rem; } .design-sarkari-yojana .poster-info-box { background: rgba(255,255,255,0.8); border-left: 5px solid #0d47a1; box-shadow: 0 2px 5px rgba(0,0,0,0.1); } .design-sarkari-yojana .poster-info-icon { color: #0d47a1; } .design-sarkari-yojana .poster-info-content h3 { color: #555; } .design-sarkari-yojana .poster-info-content p { color: #111; font-weight: bold; } .design-sarkari-yojana .poster-footer { background: #0d47a1; color: #fff; } .design-sarkari-yojana .poster-company-footer { background: #bbdefb; }
    .design-ai-futuristic { background: #121212; color: #e0e0e0; font-family: 'Roboto', sans-serif; } .design-ai-futuristic .poster-header { border-bottom: 1px solid rgba(139, 92, 246, 0.5); } .design-ai-futuristic .poster-org-details h1 { color: #fff; } .design-ai-futuristic .poster-org-details p { color: #aaa; } .design-ai-futuristic .poster-title h2 { background: -webkit-linear-gradient(45deg, #8B5CF6, #EC4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; } .design-ai-futuristic .poster-info-box { background: #1E1E1E; border: 1px solid #333; border-left: 4px solid #8B5CF6; } .design-ai-futuristic .poster-info-icon { color: #8B5CF6; } .design-ai-futuristic .poster-footer { background: linear-gradient(90deg, #8B5CF6, #EC4899); color: #fff; } .design-ai-futuristic .poster-company-footer { background: #1E1E1E; }
    
    /* Custom Backgrounds */
    .bg-default {}
    .bg-gradient-1 { background: linear-gradient(135deg, #6dd5ed, #2193b0) !important; }
    .bg-gradient-2 { background: linear-gradient(to right, #ffecd2 0%, #fcb69f 100%) !important; }
    .bg-gradient-3 { background: linear-gradient(to top, #c79081 0%, #dfa579 100%) !important; }
    .bg-texture-1 { background-image: url('data:image/svg+xml,%3Csvg width="6" height="6" viewBox="0 0 6 6" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="%23d4d4d4" fill-opacity="0.4" fill-rule="evenodd"%3E%3Cpath d="M5 0h1L0 6V5zM6 5v1H5z"/%3E%3C/g%3E%3C/svg%3E') !important; background-color: #ffffff !important; }
    .bg-texture-2 { background-color: #ffffff !important; background-image: linear-gradient(#e0e0e0 1px, transparent 1px), linear-gradient(to right, #e0e0e0 1px, #ffffff 1px) !important; background-size: 20px 20px !important;}
    .has-custom-bg { background-size: cover !important; background-position: center !important; background-repeat: no-repeat !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputs = {
        designSelector: document.getElementById('designSelector'), backgroundSelector: document.getElementById('backgroundSelector'), backgroundUpload: document.getElementById('backgroundUpload'),
        orgName: document.getElementById('orgName'), recruitmentTitle: document.getElementById('recruitmentTitle'),
        totalVacancies: document.getElementById('totalVacancies'), vacanciesTitle: document.getElementById('vacanciesTitle'),
        eligibility: document.getElementById('eligibility'), eligibilityTitle: document.getElementById('eligibilityTitle'),
        ageLimit: document.getElementById('ageLimit'), ageLimitTitle: document.getElementById('ageLimitTitle'),
        feesDetails: document.getElementById('feesDetails'), feesDetailsTitle: document.getElementById('feesDetailsTitle'),
        yojanaName: document.getElementById('yojanaName'), yojanaBenefits: document.getElementById('yojanaBenefits'),
        yojanaEligibility: document.getElementById('yojanaEligibility'), yojanaDocuments: document.getElementById('yojanaDocuments'),
        enableDates: document.getElementById('enableDates'),
        startDate: document.getElementById('startDate'), endDate: document.getElementById('endDate'),
        website: document.getElementById('website'), companyWebsite: document.getElementById('companyWebsite'),
        logoUpload: document.getElementById('logoUpload'),
    };
    const previews = {
        orgName: document.getElementById('org-name-preview'), title: document.getElementById('title-preview'),
        totalVacancies: document.getElementById('vacancies-preview'), vacanciesTitle: document.getElementById('vacancies-title-preview'),
        eligibility: document.getElementById('eligibility-preview'), eligibilityTitle: document.getElementById('eligibility-title-preview'),
        ageLimit: document.getElementById('age-limit-preview'), ageLimitTitle: document.getElementById('age-limit-title-preview'),
        feesDetails: document.getElementById('fees-preview'), feesDetailsTitle: document.getElementById('fees-title-preview'),
        yojanaBenefits: document.getElementById('yojana-benefits-preview'), yojanaEligibility: document.getElementById('yojana-eligibility-preview'),
        yojanaDocuments: document.getElementById('yojana-documents-preview'),
        startDate: document.getElementById('start-date-preview'), endDate: document.getElementById('end-date-preview'),
        website: document.getElementById('website-preview'), companyWebsite: document.getElementById('company-website-preview'),
        logo: document.getElementById('logo-preview'),
        footer: document.querySelector('.poster-footer'),
    };
    const forms = { recruitment: document.getElementById('recruitment-form'), yojana: document.getElementById('yojana-form') };
    const posterBodies = { recruitment: document.querySelector('.recruitment-body'), yojana: document.querySelector('.yojana-body') };
    const dateContainers = { enabler: document.getElementById('date-enabler-container'), fields: document.getElementById('date-fields-container') };
    const posterCanvas = document.getElementById('poster-canvas');
    let currentBackgroundClass = 'bg-default';

    function updatePosterClasses() {
        const selectedTheme = inputs.designSelector.value;
        const selectedBackground = inputs.backgroundSelector.value;
        posterCanvas.className = 'poster-container';
        posterCanvas.classList.add(selectedTheme);
        if (selectedBackground !== 'bg-default') {
            posterCanvas.classList.add(selectedBackground);
        }
    }

    inputs.backgroundUpload.addEventListener('change', function(event) {
        if (event.target.files && event.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                posterCanvas.style.backgroundImage = `url('${e.target.result}')`;
                posterCanvas.classList.add('has-custom-bg');
                inputs.backgroundSelector.value = 'bg-default';
                updatePreview();
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    });

    inputs.backgroundSelector.addEventListener('change', function() {
        posterCanvas.style.backgroundImage = '';
        posterCanvas.classList.remove('has-custom-bg');
        updatePreview();
    });

    function toggleFormAndPreview() {
        const selectedTheme = inputs.designSelector.value;
        const isYojana = selectedTheme === 'design-sarkari-yojana';
        forms.recruitment.style.display = isYojana ? 'none' : 'block';
        forms.yojana.style.display = isYojana ? 'block' : 'none';
        posterBodies.recruitment.style.display = isYojana ? 'none' : 'flex';
        posterBodies.yojana.style.display = isYojana ? 'flex' : 'none';
        dateContainers.enabler.style.display = isYojana ? 'block' : 'none';
        dateContainers.fields.style.display = !isYojana || inputs.enableDates.checked ? 'flex' : 'none';
        updatePreview();
    }

    function updatePreview() {
        updatePosterClasses();
        const selectedTheme = inputs.designSelector.value;
        const isYojana = selectedTheme === 'design-sarkari-yojana';

        if (isYojana) {
            previews.orgName.textContent = inputs.orgName.value || "Government of India / Scheme";
            previews.title.textContent = inputs.yojanaName.value || 'Scheme Name';
            previews.yojanaBenefits.innerHTML = (inputs.yojanaBenefits.value || 'Benefits details.').replace(/\n/g, '<br>');
            previews.yojanaEligibility.innerHTML = (inputs.yojanaEligibility.value || 'Eligibility details.').replace(/\n/g, '<br>');
            previews.yojanaDocuments.innerHTML = (inputs.yojanaDocuments.value || 'Document details.').replace(/\n/g, '<br>');
        } else {
            previews.orgName.textContent = inputs.orgName.value || 'Organization Name';
            previews.title.textContent = inputs.recruitmentTitle.value || 'Recruitment Title';
            previews.totalVacancies.textContent = inputs.totalVacancies.value || '0000 Posts';
            previews.vacanciesTitle.textContent = inputs.vacanciesTitle.value || 'Total Vacancies';
            previews.eligibility.innerHTML = (inputs.eligibility.value || 'Details here.').replace(/\n/g, '<br>');
            previews.eligibilityTitle.textContent = inputs.eligibilityTitle.value || 'Eligibility';
            previews.ageLimit.textContent = inputs.ageLimit.value || 'Details here.';
            previews.ageLimitTitle.textContent = inputs.ageLimitTitle.value || 'Age Limit';
            previews.feesDetails.textContent = inputs.feesDetails.value || 'Details here.';
            previews.feesDetailsTitle.textContent = inputs.feesDetailsTitle.value || 'Fees Details';
        }

        previews.website.textContent = inputs.website.value || 'www.example.com';
        previews.companyWebsite.textContent = inputs.companyWebsite.value || 'www.bronline.net';
        
        const showDates = !isYojana || inputs.enableDates.checked;
        previews.footer.style.display = showDates ? 'flex' : 'none';
        if (showDates) {
            previews.startDate.textContent = inputs.startDate.value ? new Date(inputs.startDate.value).toLocaleDateString('en-GB').replace(/\//g, '-') : 'DD-MM-YYYY';
            previews.endDate.textContent = inputs.endDate.value ? new Date(inputs.endDate.value).toLocaleDateString('en-GB').replace(/\//g, '-') : 'DD-MM-YYYY';
        }
    }

    Object.values(inputs).forEach(input => input.addEventListener('input', updatePreview));
    inputs.designSelector.addEventListener('change', toggleFormAndPreview);
    inputs.enableDates.addEventListener('change', toggleFormAndPreview);
    
    inputs.logoUpload.addEventListener('change', function(event) {
        if (event.target.files && event.target.files[0]) {
            const reader = new FileReader();
            reader.onload = e => { previews.logo.src = e.target.result; };
            reader.readAsDataURL(event.target.files[0]);
        }
    });

    document.getElementById('saveAndRedirectBtn').addEventListener('click', function() {
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
        this.disabled = true;
        
        // --- [ PERFORMANCE FIX: Reduced scale for faster generation ] ---
        html2canvas(posterCanvas, { scale: 1.5, useCORS: true, backgroundColor: null })
        .then(canvas => {
            // Changed to image/jpeg for smaller file size
            const imageData = canvas.toDataURL('image/jpeg', 0.9);
            fetch('<?= BASE_URL ?>user/recruitment/save_poster.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ imageData: imageData })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '<?= BASE_URL ?>?page=add_recruitment_post&image_url=' + encodeURIComponent(data.imageUrl);
                } else {
                    alert('Error saving poster: ' + data.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-save me-2"></i>Save Poster & Redirect';
                }
            })
            .catch(err => {
                 alert('An error occurred while saving the poster.');
                 console.error(err);
                 this.disabled = false;
                 this.innerHTML = '<i class="fas fa-save me-2"></i>Save Poster & Redirect';
            });
        });
    });

    // Initial call to set up the form based on the default theme
    toggleFormAndPreview(); 
});
</script>