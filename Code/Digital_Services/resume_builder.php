<?php
require_once CORE_INCLUDES_PATH . 'service_paywall.php';
enforce_service_paywall('resume_builder');

// File location: views/resume_builder.php

$pdo = connectDB();
$resume_cost = 10.00; 
$currency = '₹';
$user_role = $_SESSION['user_role'] ?? 'guest';

try {
    $stmt = $pdo->query("SELECT poster_generation_cost, currency_symbol FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings) {
        $resume_cost = isset($settings['poster_generation_cost']) ? (float)$settings['poster_generation_cost'] : 10.00;
        $currency = isset($settings['currency_symbol']) ? $settings['currency_symbol'] : '₹';
    }

    if (isset($_SESSION['user_id'])) {
        $stmt_user = $pdo->prepare("SELECT custom_poster_rate FROM users WHERE id = ?");
        $stmt_user->execute([$_SESSION['user_id']]);
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data && isset($user_data['custom_poster_rate']) && $user_data['custom_poster_rate'] !== null && $user_data['custom_poster_rate'] !== '') {
            $resume_cost = (float)$user_data['custom_poster_rate'];
        }
    }
} catch (Exception $e) {}

// --- LOAD DRAFT LOGIC ---
$loaded_draft_json = null;
if (isset($_GET['draft_id']) && isset($_SESSION['user_id'])) {
    try {
        $stmt_draft = $pdo->prepare("SELECT canvas_json FROM digital_service_history WHERE id = ? AND user_id = ? AND is_draft = 1");
        $stmt_draft->execute([$_GET['draft_id'], $_SESSION['user_id']]);
        $draft_row = $stmt_draft->fetch(PDO::FETCH_ASSOC);
        if ($draft_row) {
            if (str_starts_with($draft_row['canvas_json'], 'FILE:')) {
                $filepath = UPLOADS_PATH . 'drafts/' . str_replace('FILE:', '', $draft_row['canvas_json']);
                if (file_exists($filepath)) $loaded_draft_json = file_get_contents($filepath);
            } else {
                $loaded_draft_json = $draft_row['canvas_json'];
            }
        }
    } catch(Exception $e) {}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Studio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Poppins:wght@300;400;600;700&family=Roboto:wght@400;700&family=Lora:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

<style>
    /* ========================================================== */
    /* 🚀 FULL SCREEN OVERRIDE - HIDES MAIN SIDEBAR & HEADER 🚀 */
    /* ========================================================== */
    #sidebar { display: none !important; }
    .navbar { display: none !important; }
    #content { padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    .wrapper { margin: 0 !important; padding: 0 !important; }
    body { background-color: #e2e8f0; overflow: auto; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
    
    .builder-wrapper { display: flex; min-height: 100vh; height: 100%; width: 100vw; }
    
    /* Left Panel - Editor */
    .editor-panel { width: 600px; min-width: 600px; background: #ffffff; display: flex; flex-direction: column; border-right: 2px solid #cbd5e1; z-index: 10; height: 100%; box-shadow: 5px 0 15px rgba(0,0,0,0.1); }
    .editor-header { padding: 15px; background: #1e293b; color: #38bdf8; text-align: center; font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; display: flex; justify-content: space-between; align-items: center; }
    .btn-back { background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; text-decoration: none;}
    .btn-back:hover { background: #dc2626; color: white; text-decoration: none;}
    
    .form-area { flex-grow: 1; overflow-y: auto; padding: 20px; background: #f8fafc; }
    .form-section { background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #cbd5e1; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .section-title { font-weight: bold; font-size: 16px; color: #0f172a; border-bottom: 2px solid #38bdf8; padding-bottom: 5px; margin-bottom: 15px; display: block; }
    
    .form-group { margin-bottom: 12px; }
    .form-group label { display: block; font-size: 12px; font-weight: bold; color: #475569; margin-bottom: 4px; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #94a3b8; font-size: 14px; outline: none; }
    .form-group input:focus, .form-group textarea:focus { border-color: #38bdf8; }
    
    /* Right Panel - Live Preview */
    .preview-panel { flex-grow: 1; display: flex; justify-content: center; align-items: flex-start; overflow-y: auto; padding: 30px 30px 30px 30px; padding-top: 40px; background-color: #64748b; }
    .a4-page { width: 210mm; min-height: 297mm; background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.3); position: relative; overflow: auto; margin-bottom: 30px; }
    
    .action-btns { padding: 15px; background: #ffffff; border-top: 1px solid #cbd5e1; display: flex; gap: 10px; align-items: center; justify-content: space-between; }
    .btn-export { background: #16a34a; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer; flex-grow: 1; text-align: center; }
    .btn-export:hover { background: #15803d; }
    
    /* Dynamic Buttons */
    .btn-add { background: #3b82f6; color: white; border: none; padding: 5px 10px; font-size: 12px; border-radius: 4px; cursor: pointer; margin-top: 5px; }
    .btn-del { background: #ef4444; color: white; border: none; padding: 4px 8px; font-size: 11px; border-radius: 4px; cursor: pointer; float: right; margin-top: -30px; }
    
    /* Profile Image Preview */
    .profile-img-preview { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #ccc; display: block; margin-bottom: 10px; }

    /* ========================================================== */
    /* 📱 MOBILE RESPONSIVENESS FIXES (Injected by System) 📱      */
    /* ========================================================== */
    @media (max-width: 992px) {
        .studio-wrapper, .builder-wrapper { flex-direction: column !important; height: auto !important; width: 100vw !important; overflow-x: hidden; }
        .editor-panel { width: 100% !important; min-width: 100% !important; height: auto !important; max-height: 55vh; overflow-y: auto; border-right: none !important; border-bottom: 2px solid #cbd5e1; }
        .preview-panel { width: 100% !important; height: 45vh !important; min-height: 45vh !important; padding: 10px !important; overflow-y: auto; }
        .canvas-container { max-width: 100% !important; height: auto !important; margin: 0 auto; }
        canvas { max-width: 100% !important; height: auto !important; }
        /* Scale down Previews */
        .a4-page, .card-preview { max-width: 100%; transform: scale(0.65) !important; transform-origin: top center !important; margin-bottom: 0 !important; }
        .mobile-gap { margin-bottom: 60px; }
        .action-btns { flex-wrap: wrap; justify-content: center; width: 100%; }
        .btn-export { width: 100%; margin-top: 10px; }
    }

</style>

<div class="builder-wrapper">
    <div class="editor-panel">
        <div class="editor-header">
            <a href="<?= BASE_URL ?><?= isset($_SESSION['user_id']) ? '?page=dashboard' : '?page=b2c_home' ?>" class="btn-back">⬅ <?= isset($_SESSION['user_id']) ? 'DASHBOARD' : 'WEBSITE' ?></a>
            <span>📄 Ultimate Resume Maker</span>
            <span id="autoSaveStatus" style="font-size:12px; color:#facc15;">Auto-save: Ready</span>
        </div>
        
        <div class="form-area" id="resumeForm">
            
            <div class="form-section">
                <span class="section-title">🎨 1. Choose Template Format</span>
                <div class="form-group">
                    <select id="templateSelector" onchange="renderResume()">
                        <optgroup label="Premium Series">
                            <option value="minimalist">Clean Minimalist (Typography)</option>
                            <option value="executive">Executive Board (Dark Header)</option>
                            <option value="stellar">Stellar Professional (Blue Accent)</option>
                            <option value="modern_grid">Modern Grid (Geometric)</option>
                            <option value="professional">Professional Pro (Two Column)</option>
                            <option value="harvard">Harvard Standard (Official & Clean)</option>
                        </optgroup>
                        <optgroup label="Classic Series">
                            <option value="modern">Modern Professional</option>
                            <option value="classic">Classic Corporate (Top Header)</option>
                            <option value="creative">Creative CV (Center Profile)</option>
                            <option value="elegant">Elegant Accent (Grid Layout)</option>
                            <option value="timeline">Timeline View (Chronological)</option>
                        </optgroup>
                    </select>
                </div>
                <div class="form-group" style="display: flex; gap: 10px; align-items: center;">
                    <div style="flex:1;">
                        <label>Theme Color</label>
                        <input type="color" id="themeColor" value="#0f172a" oninput="renderResume()" style="height:35px; padding:0; cursor:pointer;">
                    </div>
                    <div style="flex:1;">
                        <label>Font Style</label>
                        <select id="fontSelector" onchange="renderResume()">
                            <option value="'Poppins', sans-serif">Poppins (Modern)</option>
                            <option value="'Open Sans', sans-serif">Open Sans (Clean)</option>
                            <option value="'Roboto', sans-serif">Roboto (Formal)</option>
                            <option value="'Lora', serif">Lora (Classic/Royal)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <span class="section-title">👤 2. Personal Information</span>
                <div style="display:flex; gap:15px; align-items:center;">
                    <img src="https://via.placeholder.com/80" id="imgPreview" class="profile-img-preview">
                    <div class="form-group" style="flex-grow:1;">
                        <label>Profile Image (Upload)</label>
                        <input type="file" id="profileImage" accept="image/*" onchange="handleImageUpload(event)">
                    </div>
                </div>
                <div class="form-group"><label>Full Name</label><input type="text" id="fullName" placeholder="Rahul Patel" oninput="renderResume()" value="Rahul Patel"></div>
                <div class="form-group"><label>Professional Title</label><input type="text" id="jobTitle" placeholder="Senior Software Engineer" oninput="renderResume()" value="Senior Software Engineer"></div>
                
                <div style="display:flex; gap:10px;">
                    <div class="form-group" style="flex:1;"><label>Email</label><input type="email" id="email" placeholder="rahul@example.com" oninput="renderResume()" value="rahul@example.com"></div>
                    <div class="form-group" style="flex:1;"><label>Phone</label><input type="text" id="phone" placeholder="+91 9876543210" oninput="renderResume()" value="+91 9876543210"></div>
                </div>
                <div style="display:flex; gap:10px;">
                    <div class="form-group" style="flex:1;"><label>Address</label><input type="text" id="address" placeholder="Ahmedabad, Gujarat" oninput="renderResume()" value="Ahmedabad, Gujarat"></div>
                    <div class="form-group" style="flex:1;"><label>LinkedIn / Website</label><input type="text" id="linkedin" placeholder="linkedin.com/in/rahul" oninput="renderResume()" value="linkedin.com/in/rahul"></div>
                </div>
            </div>

            <div class="form-section">
                <span class="section-title">📝 3. Professional Summary</span>
                <div class="form-group">
                    <textarea id="summary" rows="3" oninput="renderResume()" placeholder="Write a short summary about your career...">Highly motivated and experienced professional with a proven track record of delivering high-quality results. Passionate about technology, teamwork, and continuous learning.</textarea>
                </div>
            </div>

            <div class="form-section">
                <span class="section-title">💼 4. Work Experience</span>
                <div id="experienceContainer">
                    <div class="exp-item p-2 mb-2 border rounded bg-light">
                        <div style="display:flex; gap:10px;">
                            <div class="form-group" style="flex:1;"><label>Job Title</label><input type="text" class="exp-title" value="Project Manager" oninput="renderResume()"></div>
                            <div class="form-group" style="flex:1;"><label>Company</label><input type="text" class="exp-company" value="Tech Solutions Pvt Ltd" oninput="renderResume()"></div>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <div class="form-group" style="flex:1;"><label>Start Date</label><input type="text" class="exp-start" value="Jan 2020" oninput="renderResume()"></div>
                            <div class="form-group" style="flex:1;"><label>End Date</label><input type="text" class="exp-end" value="Present" oninput="renderResume()"></div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="exp-desc" rows="2" oninput="renderResume()">Led a team of 15 developers. Delivered 20+ enterprise projects successfully. Improved overall system efficiency by 40%.</textarea>
                        </div>
                    </div>
                </div>
                <button class="btn-add" onclick="addExperience()">+ Add Experience</button>
            </div>

            <div class="form-section">
                <span class="section-title">🎓 5. Education</span>
                <div id="educationContainer">
                    <div class="edu-item p-2 mb-2 border rounded bg-light">
                        <div style="display:flex; gap:10px;">
                            <div class="form-group" style="flex:1;"><label>Degree / Course</label><input type="text" class="edu-degree" value="Master of Computer Applications" oninput="renderResume()"></div>
                            <div class="form-group" style="flex:1;"><label>University / School</label><input type="text" class="edu-school" value="Gujarat University" oninput="renderResume()"></div>
                        </div>
                        <div class="form-group"><label>Year</label><input type="text" class="edu-year" value="2015 - 2017" oninput="renderResume()"></div>
                    </div>
                </div>
                <button class="btn-add" onclick="addEducation()">+ Add Education</button>
            </div>

            <div class="form-section">
                <span class="section-title">⭐ 6. Key Skills</span>
                <div class="form-group">
                    <label>Enter skills separated by commas (,)</label>
                    <input type="text" id="skills" value="Leadership, Project Management, PHP, JavaScript, Client Relations" oninput="renderResume()">
                </div>
            </div>

        </div>

        <div class="action-btns">
            <?php if(isset($_SESSION['user_id'])): ?>
            <button class="btn-export" style="background: #f59e0b; margin-right: 5px;" onclick="saveDraft()"><i class="fas fa-save"></i> Save Draft</button>
            <?php endif; ?>
            <button class="btn-export" onclick="handleExport()">⬇ Download High Quality PDF <?= (!isset($_SESSION['user_id']) && isset($_COOKIE['guest_service_used'])) ? '' : '('.$currency . number_format($resume_cost, 2).')' ?></button>
        </div>
    </div>
    
    <!-- SECTION CUSTOMIZATION CONTROLS -->
    <div style="padding:15px; background:#f8fafc; border-top:1px solid #e2e8f0;">
        <h3 style="margin:0 0 10px 0; font-size:16px;">Section Customization</h3>
        <div style="display:flex; flex-wrap:wrap; gap:15px; align-items:center;">
            <div style="display:flex; align-items:center; gap:5px; background:white; padding:5px 10px; border-radius:4px; border:1px solid #cbd5e1;">
                <input type="checkbox" id="showSummary" checked onchange="renderResume()">
                <input type="text" id="labelSummary" value="Professional Summary" oninput="renderResume()" style="border:none; outline:none; font-size:13px; font-weight:bold; color:#475569; width:150px; background:transparent;">
            </div>
            <div style="display:flex; align-items:center; gap:5px; background:white; padding:5px 10px; border-radius:4px; border:1px solid #cbd5e1;">
                <input type="checkbox" id="showExperience" checked onchange="renderResume()">
                <input type="text" id="labelExperience" value="Work Experience" oninput="renderResume()" style="border:none; outline:none; font-size:13px; font-weight:bold; color:#475569; width:120px; background:transparent;">
            </div>
            <div style="display:flex; align-items:center; gap:5px; background:white; padding:5px 10px; border-radius:4px; border:1px solid #cbd5e1;">
                <input type="checkbox" id="showEducation" checked onchange="renderResume()">
                <input type="text" id="labelEducation" value="Education" oninput="renderResume()" style="border:none; outline:none; font-size:13px; font-weight:bold; color:#475569; width:100px; background:transparent;">
            </div>
            <div style="display:flex; align-items:center; gap:5px; background:white; padding:5px 10px; border-radius:4px; border:1px solid #cbd5e1;">
                <input type="checkbox" id="showSkills" checked onchange="renderResume()">
                <input type="text" id="labelSkills" value="Skills" oninput="renderResume()" style="border:none; outline:none; font-size:13px; font-weight:bold; color:#475569; width:80px; background:transparent;">
            </div>
        </div>
    </div>
    
    <div class="preview-panel">
    <div class="sys-zoom-controls" style="position: absolute; bottom: 20px; right: 20px; background: rgba(255,255,255,0.95); padding: 8px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); display: flex; flex-direction: column; gap: 5px; z-index: 9999; border: 1px solid #cbd5e1;">
        <div style="font-size: 10px; font-weight: bold; color: #475569; text-align: center; margin-bottom: 2px;">ZOOM</div>
        <button type="button" onclick="sysChangeZoom(0.1)" style="background: #f1f5f9; border: 1px solid #94a3b8; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-weight: bold; transition: 0.2s;">➕</button>
        <button type="button" onclick="sysResetZoom()" style="background: #f1f5f9; border: 1px solid #94a3b8; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-size: 10px; font-weight: bold; transition: 0.2s;">100%</button>
        <button type="button" onclick="sysChangeZoom(-0.1)" style="background: #f1f5f9; border: 1px solid #94a3b8; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-weight: bold; transition: 0.2s;">➖</button>
    </div>
        <div class="a4-page" id="resumePreview">
            </div>
    </div>
</div>

<script>
    // 🚀 PHP DATA TO JS 🚀
    const userRole = "<?= $_SESSION['user_role'] ?? 'guest' ?>";
    const resumeCost = <?= number_format($resume_cost, 2, '.', '') ?>; // 100% correct rate
    const currency = "<?= $currency ?>";
    const baseUrl = "<?= BASE_URL ?>"; 
    const APP_URL = "<?= APP_URL ?>";

    // Global Image Data
    let profileImageData = "https://via.placeholder.com/150";

    // Auto render on load
    window.onload = function() {
        renderResume();
        setupAutoSave();
    };

    function handleImageUpload(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                profileImageData = e.target.result;
                document.getElementById('imgPreview').src = profileImageData;
                renderResume();
            }
            reader.readAsDataURL(file);
        }
    }

    // --- DYNAMIC FORM ADD/REMOVE ---
    function addExperience() {
        const div = document.createElement('div');
        div.className = "exp-item p-2 mb-2 border rounded bg-light";
        div.innerHTML = `
            <button class="btn-del" onclick="this.parentElement.remove(); renderResume();">X</button>
            <div style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;"><label>Job Title</label><input type="text" class="exp-title" placeholder="Job Title" oninput="renderResume()"></div>
                <div class="form-group" style="flex:1;"><label>Company</label><input type="text" class="exp-company" placeholder="Company Name" oninput="renderResume()"></div>
            </div>
            <div style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;"><label>Start</label><input type="text" class="exp-start" placeholder="YYYY" oninput="renderResume()"></div>
                <div class="form-group" style="flex:1;"><label>End</label><input type="text" class="exp-end" placeholder="YYYY" oninput="renderResume()"></div>
            </div>
            <div class="form-group"><label>Description</label><textarea class="exp-desc" rows="2" oninput="renderResume()"></textarea></div>
        `;
        document.getElementById('experienceContainer').appendChild(div);
    }

    function addEducation() {
        const div = document.createElement('div');
        div.className = "edu-item p-2 mb-2 border rounded bg-light";
        div.innerHTML = `
            <button class="btn-del" onclick="this.parentElement.remove(); renderResume();">X</button>
            <div style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;"><label>Degree</label><input type="text" class="edu-degree" placeholder="Degree" oninput="renderResume()"></div>
                <div class="form-group" style="flex:1;"><label>School</label><input type="text" class="edu-school" placeholder="School Name" oninput="renderResume()"></div>
            </div>
            <div class="form-group"><label>Year</label><input type="text" class="edu-year" placeholder="YYYY - YYYY" oninput="renderResume()"></div>
        `;
        document.getElementById('educationContainer').appendChild(div);
    }

    // --- 🚀 MASTER RESUME RENDER ENGINE 🚀 ---
    // Auto-save variables
    let saveTimer;
    let lastSaveTime = Date.now();
    const AUTO_SAVE_INTERVAL = 30000; // 30 seconds
    const INACTIVITY_TIMEOUT = 15000; // 15 seconds
    
    // 🚀 AUTO-SAVE TRIGGERS 🚀
    function setupAutoSave() {
        // Clear existing timers
        if (saveTimer) clearInterval(saveTimer);
        
        // Set up inactivity monitor
        let inactivityTimer;
        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(triggerAutoSave, INACTIVITY_TIMEOUT);
        }
        
        // Event listeners for user activity
        document.querySelectorAll('input, textarea, select').forEach(el => {
            el.addEventListener('input', resetInactivityTimer);
            el.addEventListener('change', resetInactivityTimer);
        });
        
        // Periodic save
        saveTimer = setInterval(triggerAutoSave, AUTO_SAVE_INTERVAL);
        resetInactivityTimer();
    }
    
    // 🚀 AUTO-SAVE EXECUTION 🚀
    async function triggerAutoSave() {
        updateStatusIndicator('Saving...', '#fbbf24');
        
        const data = getResumeData();
        
        try {
            const response = await fetch(`${APP_URL}auto_save_resume.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            
            if (!response.ok) throw new Error('Network error');
            
            const result = await response.json();
            if (result.success) {
                updateStatusIndicator(`Saved ${formatTime()}`, '#10b981');
                lastSaveTime = Date.now();
            } else {
                throw new Error(result.message || 'Server error');
            }
        } catch (error) {
            // Retry logic
            setTimeout(() => {
                updateStatusIndicator('Retrying...', '#ef4444');
                triggerAutoSave();
            }, 5000);
        }
    }
    
    // 🚀 STATUS INDICATOR 🚀
    function updateStatusIndicator(message, color) {
        const indicator = document.getElementById('autoSaveStatus');
        if (indicator) {
            indicator.textContent = message;
            indicator.style.color = color;
        }
    }
    
    // 🚀 FORMAT TIME 🚀
    function formatTime() {
        const now = new Date();
        return now.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
    }
    
    // 🚀 GET RESUME DATA 🚀
    function getResumeData() {
        return {
            template: document.getElementById('templateSelector').value,
            themeColor: document.getElementById('themeColor').value,
            fontStyle: document.getElementById('fontSelector').value,
            name: document.getElementById('fullName').value || "Your Name",
            title: document.getElementById('jobTitle').value || "Professional Title",
            email: document.getElementById('email').value || "email@example.com",
            phone: document.getElementById('phone').value || "Phone Number",
            address: document.getElementById('address').value || "Address",
            linkedin: document.getElementById('linkedin').value || "Website/LinkedIn",
            summary: document.getElementById('summary').value || "Professional summary...",
            skills: document.getElementById('skills').value.split(',').map(s => s.trim()).filter(s => s),
            image: profileImageData,
            settings: {
                showSummary: document.getElementById('showSummary').checked,
                showExperience: document.getElementById('showExperience').checked,
                showEducation: document.getElementById('showEducation').checked,
                showSkills: document.getElementById('showSkills').checked,
                labelSummary: document.getElementById('labelSummary').value || 'Professional Summary',
                labelExperience: document.getElementById('labelExperience').value || 'Work Experience',
                labelEducation: document.getElementById('labelEducation').value || 'Education',
                labelSkills: document.getElementById('labelSkills').value || 'Skills'
            },
            exp: Array.from(document.querySelectorAll('.exp-item')).map(item => ({
                title: item.querySelector('.exp-title').value,
                company: item.querySelector('.exp-company').value,
                start: item.querySelector('.exp-start').value,
                end: item.querySelector('.exp-end').value,
                desc: item.querySelector('.exp-desc').value
            })),
            edu: Array.from(document.querySelectorAll('.edu-item')).map(item => ({
                degree: item.querySelector('.edu-degree').value,
                school: item.querySelector('.edu-school').value,
                year: item.querySelector('.edu-year').value
            }))
        };
    }
    
    // 🚀 RESUME RENDER ENGINE 🚀
    function renderResume() {
        const data = getResumeData();
        // Generate HTML based on template selection
        document.getElementById('resumePreview').innerHTML = generateTemplateHTML(data.template, data, data.themeColor, data.fontStyle);
    }

    // --- 🎨 8 PREMIUM TEMPLATES LIBRARY 🎨 ---
    function generateTemplateHTML(type, data, color, font) {
        
        // Common Sub-components
        let skillsHTML = data.skills.map(s => `<span style="display:inline-block; padding:4px 8px; margin:2px; background:#f1f5f9; color:#334155; border-radius:4px; font-size:12px;">${s}</span>`).join('');
        
        let expHTML = data.exp.map(e => `
            <div style="margin-bottom:15px;">
                <h4 style="margin:0; font-size:16px; color:#1e293b;">${e.title}</h4>
                <div style="font-size:13px; color:${color}; font-weight:bold; margin-bottom:5px;">${e.company} | ${e.start} - ${e.end}</div>
                <p style="margin:0; font-size:13px; color:#475569; line-height:1.4;">${e.desc}</p>
            </div>
        `).join('');

        let eduHTML = data.edu.map(e => `
            <div style="margin-bottom:10px;">
                <h4 style="margin:0; font-size:15px; color:#1e293b;">${e.degree}</h4>
                <div style="font-size:13px; color:#475569;">${e.school} | <span style="font-weight:bold; color:${color};">${e.year}</span></div>
            </div>
        `).join('');

        // Layout Helpers
        const section = (show, title, content, style="") => {
            if(!show) return "";
            return `<div style="${style}">
                <h3 style="font-size:18px; color:${color}; border-bottom:2px solid #e2e8f0; padding-bottom:5px; margin-bottom:15px;">${title}</h3>
                ${content}
            </div>`;
        };

        // 1. MODERN (Left Sidebar)
        if (type === 'modern') {
            let leftSkills = data.skills.map(s => `<div style="margin-bottom:6px; font-size:13px; border-bottom:1px solid rgba(255,255,255,0.2); padding-bottom:3px;">${s}</div>`).join('');
            return `
            <div style="display:flex; width:100%; min-height:297mm; font-family:${font};">
                <div style="width:35%; background-color:${color}; color:white; padding:30px;">
                    <div style="text-align:center; margin-bottom:30px;">
                        <img src="${data.image}" style="width:140px; height:140px; border-radius:50%; border:4px solid rgba(255,255,255,0.3); object-fit:cover;">
                    </div>
                    <h3 style="font-size:18px; border-bottom:2px solid white; padding-bottom:5px; margin-bottom:15px;">CONTACT</h3>
                    <div style="font-size:13px; line-height:1.8; margin-bottom:30px;">
                        📞 ${data.phone}<br>✉️ ${data.email}<br>📍 ${data.address}<br>🔗 ${data.linkedin}
                    </div>
                    ${data.settings.showSkills ? `<h3 style="font-size:18px; border-bottom:2px solid white; padding-bottom:5px; margin-bottom:15px;">${data.settings.labelSkills}</h3>${leftSkills}` : ""}
                </div>
                <div style="width:65%; padding:40px; background:white;">
                    <h1 style="font-size:38px; color:#1e293b; margin:0; text-transform:uppercase; line-height:1.1;">${data.name}</h1>
                    <h2 style="font-size:20px; color:${color}; margin:5px 0 25px 0; font-weight:400;">${data.title}</h2>
                    
                    ${data.settings.showSummary ? section(true, data.settings.labelSummary, `<p style="font-size:13px; color:#475569; line-height:1.6; margin-bottom:30px;">${data.summary}</p>`, "margin-top:0;") : ""}
                    ${section(data.settings.showExperience, data.settings.labelExperience, expHTML)}
                    ${section(data.settings.showEducation, data.settings.labelEducation, eduHTML, "margin-top:20px;")}
                </div>
            </div>`;
        }

        // 2. CLASSIC (Top Header)
        else if (type === 'classic') {
            return `
            <div style="padding:40px; width:100%; min-height:297mm; font-family:${font}; background:white;">
                <div style="text-align:center; border-bottom:3px solid ${color}; padding-bottom:20px; margin-bottom:20px;">
                    <h1 style="margin:0; font-size:36px; color:#1e293b; letter-spacing:2px;">${data.name}</h1>
                    <h3 style="margin:5px 0; font-size:18px; color:${color};">${data.title}</h3>
                    <div style="font-size:12px; color:#475569; margin-top:10px;">
                        ${data.email} | ${data.phone} | ${data.address} | ${data.linkedin}
                    </div>
                </div>
                ${data.settings.showSummary ? `<div style="margin-bottom:20px;">
                    <h2 style="font-size:16px; color:${color}; border-bottom:1px solid #ccc; padding-bottom:4px; text-transform:uppercase;">${data.settings.labelSummary}</h2>
                    <p style="font-size:13px; line-height:1.6; color:#333; margin-top:8px;">${data.summary}</p>
                </div>` : ""}
                <div style="display:flex; gap:30px;">
                    <div style="width:65%;">
                        ${data.settings.showExperience ? `<h2 style="font-size:16px; color:${color}; border-bottom:1px solid #ccc; padding-bottom:4px; text-transform:uppercase; margin-bottom:15px;">${data.settings.labelExperience}</h2>${expHTML}` : ""}
                    </div>
                    <div style="width:35%;">
                        ${data.settings.showEducation ? `<h2 style="font-size:16px; color:${color}; border-bottom:1px solid #ccc; padding-bottom:4px; text-transform:uppercase; margin-bottom:15px;">${data.settings.labelEducation}</h2>${eduHTML}` : ""}
                        ${data.settings.showSkills ? `<h2 style="font-size:16px; color:${color}; border-bottom:1px solid #ccc; padding-bottom:4px; text-transform:uppercase; margin-top:25px; margin-bottom:15px;">${data.settings.labelSkills}</h2><div style="line-height:1.8;">${skillsHTML}</div>` : ""}
                    </div>
                </div>
            </div>`;
        }

        // 3. CREATIVE (Center Profile)
        else if (type === 'creative') {
            return `
            <div style="padding:50px; width:100%; min-height:297mm; font-family:${font}; background:#fafafa;">
                <div style="display:flex; align-items:center; gap:30px; margin-bottom:40px;">
                    <img src="${data.image}" style="width:120px; height:120px; border-radius:10px; object-fit:cover; box-shadow:5px 5px 0px ${color};">
                    <div>
                        <h1 style="margin:0; font-size:42px; color:#111;">${data.name}</h1>
                        <h2 style="margin:0; font-size:22px; color:${color}; font-weight:300;">${data.title}</h2>
                        <div style="margin-top:10px; font-size:13px; color:#555;">
                            ${data.email} &nbsp;•&nbsp; ${data.phone} &nbsp;•&nbsp; ${data.address}
                        </div>
                    </div>
                </div>
                ${data.settings.showSummary ? `<div style="margin-bottom:30px; background:white; padding:20px; border-left:4px solid ${color}; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
                    <p style="margin:0; font-size:14px; line-height:1.6; color:#444;">${data.summary}</p>
                </div>` : ""}
                ${data.settings.showExperience ? `<h3 style="font-size:20px; color:#111; margin-bottom:15px; border-bottom:2px solid #eee; padding-bottom:5px;">💼 ${data.settings.labelExperience}</h3><div style="margin-left:15px; border-left:2px solid ${color}; padding-left:15px;">${expHTML}</div>` : ""}
                <div style="display:flex; gap:30px; margin-top:30px;">
                    <div style="flex:1;">
                        ${data.settings.showEducation ? `<h3 style="font-size:20px; color:#111; margin-bottom:15px; border-bottom:2px solid #eee; padding-bottom:5px;">🎓 ${data.settings.labelEducation}</h3>${eduHTML}` : ""}
                    </div>
                    <div style="flex:1;">
                        ${data.settings.showSkills ? `<h3 style="font-size:20px; color:#111; margin-bottom:15px; border-bottom:2px solid #eee; padding-bottom:5px;">⭐ ${data.settings.labelSkills}</h3>${skillsHTML}` : ""}
                    </div>
                </div>
            </div>`;
        }

        // 4. ELEGANT (Grid Background)
        else if (type === 'elegant') {
            return `
            <div style="width:100%; min-height:297mm; font-family:${font}; background:white; position:relative;">
                <div style="height:150px; background:${color}; width:100%; position:absolute; top:0; left:0; z-index:1;"></div>
                <div style="position:relative; z-index:2; padding:40px;">
                    <div style="background:white; padding:30px; box-shadow:0 5px 20px rgba(0,0,0,0.1); border-radius:8px; display:flex; align-items:center; gap:25px; margin-bottom:30px;">
                        <img src="${data.image}" style="width:110px; height:110px; border-radius:50%; object-fit:cover;">
                        <div>
                            <h1 style="margin:0; font-size:32px; color:#111;">${data.name}</h1>
                            <h3 style="margin:5px 0 10px 0; font-size:18px; color:${color};">${data.title}</h3>
                            <div style="font-size:12px; color:#666;">📞 ${data.phone} &nbsp;|&nbsp; ✉️ ${data.email} &nbsp;|&nbsp; 📍 ${data.address}</div>
                        </div>
                    </div>
                    <div style="display:flex; gap:30px;">
                        <div style="flex:2;">
                            ${data.settings.showSummary ? `<h3 style="font-size:18px; color:${color}; text-transform:uppercase; margin-bottom:10px;">${data.settings.labelSummary}</h3><p style="font-size:13px; color:#444; line-height:1.6; margin-bottom:25px;">${data.summary}</p>` : ""}
                            ${data.settings.showExperience ? `<h3 style="font-size:18px; color:${color}; text-transform:uppercase; margin-bottom:15px;">${data.settings.labelExperience}</h3>${expHTML}` : ""}
                        </div>
                        <div style="flex:1;">
                            ${data.settings.showEducation ? `<h3 style="font-size:18px; color:${color}; text-transform:uppercase; margin-bottom:15px;">${data.settings.labelEducation}</h3>${eduHTML}` : ""}
                            ${data.settings.showSkills ? `<h3 style="font-size:18px; color:${color}; text-transform:uppercase; margin-top:25px; margin-bottom:15px;">${data.settings.labelSkills}</h3>${skillsHTML}` : ""}
                        </div>
                    </div>
                </div>
            </div>`;
        }

        // 5. PROFESSIONAL PRO (Two Column)
        else if (type === 'professional') {
            return `
            <div style="width:100%; min-height:297mm; font-family:${font}; background:white; display:flex; flex-direction:column;">
                <div style="background:#f1f5f9; padding:40px 40px 30px 40px; display:flex; align-items:center; border-bottom:4px solid ${color};">
                    <img src="${data.image}" style="width:100px; height:100px; border-radius:50%; object-fit:cover; margin-right:30px; border:2px solid ${color};">
                    <div>
                        <h1 style="margin:0; font-size:38px; color:#0f172a;">${data.name}</h1>
                        <h2 style="margin:5px 0 0 0; font-size:20px; color:${color}; font-weight:600;">${data.title}</h2>
                    </div>
                </div>
                <div style="padding:30px 40px; display:flex; gap:30px; flex:1;">
                    <div style="width:30%; border-right:1px solid #e2e8f0; padding-right:20px;">
                        <h3 style="font-size:16px; color:#0f172a; border-bottom:2px solid ${color}; padding-bottom:5px;">CONTACT</h3>
                        <div style="font-size:12px; color:#475569; line-height:2; margin-bottom:30px;">
                            <strong>Phone:</strong><br>${data.phone}<br><br>
                            <strong>Email:</strong><br>${data.email}<br><br>
                            <strong>Address:</strong><br>${data.address}<br><br>
                            <strong>LinkedIn:</strong><br>${data.linkedin}
                        </div>
                        ${data.settings.showSkills ? `<h3 style="font-size:16px; color:#0f172a; border-bottom:2px solid ${color}; padding-bottom:5px;">${data.settings.labelSkills.toUpperCase()}</h3><div style="font-size:12px; line-height:2;">${data.skills.map(s => `<div style="display:flex; align-items:center; gap:5px;"><div style="width:6px;height:6px;background:${color};border-radius:50%;"></div>${s}</div>`).join('')}</div>` : ""}
                    </div>
                    <div style="width:70%;">
                        ${data.settings.showSummary ? `<h3 style="font-size:18px; color:${color}; border-bottom:1px solid #e2e8f0; padding-bottom:5px; margin-top:0;">${data.settings.labelSummary.toUpperCase()}</h3><p style="font-size:13px; color:#334155; line-height:1.6; margin-bottom:25px;">${data.summary}</p>` : ""}
                        ${data.settings.showExperience ? `<h3 style="font-size:18px; color:${color}; border-bottom:1px solid #e2e8f0; padding-bottom:5px;">${data.settings.labelExperience.toUpperCase()}</h3>${expHTML}` : ""}
                        ${data.settings.showEducation ? `<h3 style="font-size:18px; color:${color}; border-bottom:1px solid #e2e8f0; padding-bottom:5px; margin-top:25px;">${data.settings.labelEducation.toUpperCase()}</h3>${eduHTML}` : ""}
                    </div>
                </div>
            </div>`;
        }

        // 6. MINIMALIST
        else if (type === 'minimalist') {
            return `
            <div style="padding:50px; width:100%; min-height:297mm; font-family:${font}; background:white;">
                <div style="text-align:left; margin-bottom:40px;">
                    <h1 style="margin:0; font-size:46px; color:#000; font-weight:300; letter-spacing:1px;">${data.name}</h1>
                    <h2 style="margin:10px 0; font-size:18px; color:${color}; text-transform:uppercase; letter-spacing:2px;">${data.title}</h2>
                    <div style="font-size:12px; color:#666; margin-top:15px; display:flex; gap:15px; flex-wrap:wrap;">
                        <span>📞 ${data.phone}</span><span>✉️ ${data.email}</span><span>📍 ${data.address}</span>
                    </div>
                </div>
                ${data.settings.showSummary ? `<div style="margin-bottom:30px;"><p style="font-size:14px; line-height:1.8; color:#333; font-weight:300;">${data.summary}</p></div>` : ""}
                ${data.settings.showExperience ? `<div style="margin-bottom:30px;"><h3 style="font-size:14px; color:#000; text-transform:uppercase; letter-spacing:2px; border-bottom:1px solid #000; padding-bottom:5px; margin-bottom:20px;">${data.settings.labelExperience}</h3>${expHTML}</div>` : ""}
                <div style="display:flex; gap:40px;">
                    <div style="flex:1;">
                        ${data.settings.showEducation ? `<h3 style="font-size:14px; color:#000; text-transform:uppercase; letter-spacing:2px; border-bottom:1px solid #000; padding-bottom:5px; margin-bottom:20px;">${data.settings.labelEducation}</h3>${eduHTML}` : ""}
                    </div>
                    <div style="flex:1;">
                        ${data.settings.showSkills ? `<h3 style="font-size:14px; color:#000; text-transform:uppercase; letter-spacing:2px; border-bottom:1px solid #000; padding-bottom:5px; margin-bottom:20px;">${data.settings.labelSkills}</h3><div style="display:flex; flex-wrap:wrap; gap:8px;">${data.skills.map(s => `<span style="border:1px solid ${color}; color:${color}; padding:4px 10px; font-size:12px; border-radius:20px;">${s}</span>`).join('')}</div>` : ""}
                    </div>
                </div>
            </div>`;
        }

        // 7. EXECUTIVE
        else if (type === 'executive') {
            return `
            <div style="width:100%; min-height:297mm; font-family:${font}; background:white;">
                <div style="background:#111827; color:white; padding:40px 50px; text-align:center;">
                    <img src="${data.image}" style="width:110px; height:110px; border-radius:50%; border:3px solid ${color}; margin-bottom:15px; object-fit:cover;">
                    <h1 style="margin:0; font-size:36px; letter-spacing:2px;">${data.name}</h1>
                    <h2 style="margin:10px 0 0 0; font-size:18px; color:${color}; font-weight:400;">${data.title}</h2>
                </div>
                <div style="background:${color}; color:white; padding:10px 50px; font-size:12px; display:flex; justify-content:space-between; align-items:center;">
                    <span>${data.phone}</span> | <span>${data.email}</span> | <span>${data.address}</span>
                </div>
                <div style="padding:40px 50px;">
                    ${data.settings.showSummary ? `<div style="margin-bottom:30px;"><h3 style="font-size:18px; color:#111827; text-transform:uppercase; margin-bottom:15px; display:flex; align-items:center; gap:10px;"><span style="width:30px; height:2px; background:${color};"></span> ${data.settings.labelSummary.toUpperCase()}</h3><p style="font-size:13px; color:#475569; line-height:1.7;">${data.summary}</p></div>` : ""}
                    <div style="display:flex; gap:40px;">
                        <div style="width:65%;">
                            ${data.settings.showExperience ? `<h3 style="font-size:18px; color:#111827; text-transform:uppercase; margin-bottom:20px; display:flex; align-items:center; gap:10px;"><span style="width:30px; height:2px; background:${color};"></span> ${data.settings.labelExperience.toUpperCase()}</h3>${expHTML}` : ""}
                        </div>
                        <div style="width:35%;">
                            ${data.settings.showSkills ? `<h3 style="font-size:18px; color:#111827; text-transform:uppercase; margin-bottom:20px; display:flex; align-items:center; gap:10px;"><span style="width:30px; height:2px; background:${color};"></span> ${data.settings.labelSkills.toUpperCase()}</h3><div style="margin-bottom:30px;">${skillsHTML}</div>` : ""}
                            ${data.settings.showEducation ? `<h3 style="font-size:18px; color:#111827; text-transform:uppercase; margin-bottom:20px; display:flex; align-items:center; gap:10px;"><span style="width:30px; height:2px; background:${color};"></span> ${data.settings.labelEducation.toUpperCase()}</h3>${eduHTML}` : ""}
                        </div>
                    </div>
                </div>
            </div>`;
        }

        // 8. TIMELINE
        else if (type === 'timeline') {
            let timelineExp = data.exp.map(e => `
                <div style="position:relative; padding-left:25px; margin-bottom:25px;">
                    <div style="position:absolute; left:0; top:5px; width:12px; height:12px; border-radius:50%; background:${color}; border:3px solid white; box-shadow:0 0 0 1px ${color};"></div>
                    <div style="font-size:12px; color:${color}; font-weight:bold; margin-bottom:3px;">${e.start} - ${e.end}</div>
                    <h4 style="margin:0 0 3px 0; font-size:16px; color:#1e293b;">${e.title}</h4>
                    <div style="font-size:13px; font-weight:600; color:#475569; margin-bottom:8px;">${e.company}</div>
                    <p style="margin:0; font-size:13px; color:#64748b; line-height:1.5;">${e.desc}</p>
                </div>
            `).join('');

            return `
            <div style="width:100%; min-height:297mm; font-family:${font}; background:white; display:flex;">
                <div style="width:35%; background:#f8fafc; padding:40px; border-right:1px solid #e2e8f0;">
                    <img src="${data.image}" style="width:130px; height:130px; border-radius:15px; object-fit:cover; margin-bottom:20px; box-shadow:0 10px 15px rgba(0,0,0,0.1);">
                    <h2 style="font-size:16px; color:${color}; border-bottom:2px solid #cbd5e1; padding-bottom:5px; margin-bottom:15px; text-transform:uppercase;">Contact</h2>
                    <div style="font-size:12px; color:#475569; line-height:2; margin-bottom:30px;">
                        ${data.phone}<br>${data.email}<br>${data.address}<br>${data.linkedin}
                    </div>
                    ${data.settings.showSkills ? `<h2 style="font-size:16px; color:${color}; border-bottom:2px solid #cbd5e1; padding-bottom:5px; margin-bottom:15px; text-transform:uppercase;">${data.settings.labelSkills}</h2><div style="display:flex; flex-direction:column; gap:8px;">${data.skills.map(s => `<div style="font-size:13px; color:#1e293b; font-weight:500;">▪ ${s}</div>`).join('')}</div>` : ""}
                    ${data.settings.showEducation ? `<h2 style="font-size:16px; color:${color}; border-bottom:2px solid #cbd5e1; padding-bottom:5px; margin-top:30px; margin-bottom:15px; text-transform:uppercase;">${data.settings.labelEducation}</h2>${eduHTML}` : ""}
                </div>
                <div style="width:65%; padding:50px 40px;">
                    <h1 style="margin:0; font-size:42px; color:#0f172a; line-height:1.1;">${data.name}</h1>
                    <h2 style="margin:10px 0 30px 0; font-size:20px; color:${color}; font-weight:400;">${data.title}</h2>
                    ${data.settings.showSummary ? `<p style="font-size:14px; color:#475569; line-height:1.7; margin-bottom:40px;">${data.summary}</p>` : ""}
                    ${data.settings.showExperience ? `<h2 style="font-size:22px; color:#0f172a; margin-bottom:25px; display:flex; align-items:center; gap:10px;"><span style="color:${color};">⚡</span> ${data.settings.labelExperience}</h2><div style="border-left:2px solid #e2e8f0; margin-left:5px;">${timelineExp}</div>` : ""}
                </div>
            </div>`;
        }

        // 9. STELLAR (Blue Accent) - NEW
        else if (type === 'stellar') {
            return `
            <div style="width:100%; min-height:297mm; font-family:${font}; background:white; display:flex; flex-direction:column;">
                <div style="height:15px; background:${color};"></div>
                <div style="padding:40px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee;">
                    <div>
                        <h1 style="margin:0; font-size:40px; color:#111; font-weight:800; letter-spacing:-1px;">${data.name}</h1>
                        <h2 style="margin:5px 0 0 0; font-size:20px; color:${color}; font-weight:500;">${data.title}</h2>
                    </div>
                    <img src="${data.image}" style="width:100px; height:100px; border-radius:12px; object-fit:cover; border:3px solid #f1f5f9;">
                </div>
                <div style="background:#f8fafc; padding:15px 40px; display:flex; gap:20px; font-size:12px; color:#64748b; font-weight:500;">
                    <span>📍 ${data.address}</span> | <span>📞 ${data.phone}</span> | <span>✉️ ${data.email}</span>
                </div>
                <div style="padding:40px; display:flex; gap:40px; flex:1;">
                    <div style="flex:2;">
                        ${data.settings.showSummary ? `<h3 style="font-size:16px; color:#111; text-transform:uppercase; letter-spacing:1px; margin-bottom:15px; border-left:4px solid ${color}; padding-left:10px;">${data.settings.labelSummary}</h3><p style="font-size:13px; color:#475569; line-height:1.6; margin-bottom:30px;">${data.summary}</p>` : ""}
                        ${data.settings.showExperience ? `<h3 style="font-size:16px; color:#111; text-transform:uppercase; letter-spacing:1px; margin-bottom:15px; border-left:4px solid ${color}; padding-left:10px;">${data.settings.labelExperience}</h3>${expHTML}` : ""}
                    </div>
                    <div style="flex:1;">
                        ${data.settings.showSkills ? `<h3 style="font-size:16px; color:#111; text-transform:uppercase; letter-spacing:1px; margin-bottom:15px; border-left:4px solid ${color}; padding-left:10px;">${data.settings.labelSkills}</h3><div style="display:flex; flex-wrap:wrap; gap:5px; margin-bottom:30px;">${data.skills.map(s => `<span style="background:white; border:1px solid #e2e8f0; padding:4px 10px; border-radius:4px; font-size:11px; color:#334155;">${s}</span>`).join('')}</div>` : ""}
                        ${data.settings.showEducation ? `<h3 style="font-size:16px; color:#111; text-transform:uppercase; letter-spacing:1px; margin-bottom:15px; border-left:4px solid ${color}; padding-left:10px;">${data.settings.labelEducation}</h3>${eduHTML}` : ""}
                    </div>
                </div>
            </div>`;
        }

        // 10. MODERN GRID (Geometric) - NEW
        else if (type === 'modern_grid') {
            return `
            <div style="width:100%; min-height:297mm; font-family:${font}; background:white;">
                <div style="display:grid; grid-template-columns: 1fr 2fr; height:100%;">
                    <div style="background:#2d3748; color:white; padding:40px; height:297mm;">
                        <img src="${data.image}" style="width:100%; aspect-ratio:1/1; object-fit:cover; border-radius:50%; border:5px solid ${color}; margin-bottom:30px;">
                        <h2 style="font-size:16px; color:${color}; margin-bottom:15px; text-transform:uppercase; letter-spacing:2px;">Contact</h2>
                        <div style="font-size:12px; line-height:2; margin-bottom:40px;">
                            ${data.phone}<br>${data.email}<br>${data.address}<br>${data.linkedin}
                        </div>
                        ${data.settings.showSkills ? `<h2 style="font-size:16px; color:${color}; margin-bottom:15px; text-transform:uppercase; letter-spacing:2px;">${data.settings.labelSkills}</h2><div style="display:flex; flex-direction:column; gap:8px;">${data.skills.map(s => `<div style="padding:6px 10px; background:rgba(255,255,255,0.05); border-radius:4px; font-size:12px;">${s}</div>`).join('')}</div>` : ""}
                    </div>
                    <div style="padding:50px;">
                        <div style="border-bottom:5px solid ${color}; padding-bottom:20px; margin-bottom:40px;">
                            <h1 style="margin:0; font-size:48px; font-weight:900; color:#111;">${data.name}</h1>
                            <h2 style="margin:5px 0 0 0; font-size:22px; color:${color}; font-weight:400; text-transform:uppercase; letter-spacing:3px;">${data.title}</h2>
                        </div>
                        ${data.settings.showSummary ? `<div style="margin-bottom:40px;"><p style="font-size:14px; line-height:1.8; color:#4a5568;">${data.summary}</p></div>` : ""}
                        ${data.settings.showExperience ? `<h3 style="font-size:20px; color:#111; margin-bottom:25px; display:flex; align-items:center; gap:15px;"><span style="width:40px; height:3px; background:${color};"></span> ${data.settings.labelExperience}</h3><div style="padding-left:55px;">${expHTML}</div>` : ""}
                        ${data.settings.showEducation ? `<h3 style="font-size:20px; color:#111; margin-top:40px; margin-bottom:25px; display:flex; align-items:center; gap:15px;"><span style="width:40px; height:3px; background:${color};"></span> ${data.settings.labelEducation}</h3><div style="padding-left:55px;">${eduHTML}</div>` : ""}
                    </div>
                </div>
            </div>`;
        }

        // 11. HARVARD
        else if (type === 'harvard') {
            return `
            <div style="padding:50px; width:100%; min-height:297mm; font-family:${font}; background:white;">
                <div style="text-align:center; border-bottom:2px solid #000; padding-bottom:15px; margin-bottom:20px;">
                    <h1 style="margin:0; font-size:32px; color:#000; font-family:serif;">${data.name}</h1>
                    <div style="font-size:13px; color:#000; margin-top:5px;">
                        ${data.address} • ${data.phone} • ${data.email} • ${data.linkedin}
                    </div>
                </div>
                ${data.settings.showSummary ? `<div style="margin-bottom:20px;">
                    <p style="margin:0; font-size:13px; color:#000; line-height:1.6;">${data.summary}</p>
                </div>` : ""}
                ${data.settings.showExperience ? `<div style="margin-bottom:20px;"><div style="font-size:16px; color:#000; text-transform:uppercase; border-bottom:1px solid #000; padding-bottom:3px; margin-bottom:15px; font-weight:bold; font-family:serif;">${data.settings.labelExperience}</div>${expHTML}</div>` : ""}
                ${data.settings.showEducation ? `<div style="margin-bottom:20px;"><div style="font-size:16px; color:#000; text-transform:uppercase; border-bottom:1px solid #000; padding-bottom:3px; margin-bottom:15px; font-weight:bold; font-family:serif;">${data.settings.labelEducation}</div>${eduHTML}</div>` : ""}
                ${data.settings.showSkills ? `<div style="margin-bottom:20px;"><div style="font-size:16px; color:#000; text-transform:uppercase; border-bottom:1px solid #000; padding-bottom:3px; margin-bottom:15px; font-weight:bold; font-family:serif;">${data.settings.labelSkills}</div><div style="font-size:13px; color:#000;">${data.skills.join(', ')}</div></div>` : ""}
            </div>`;
        }
        
        // Default Fallback
        return `<div style="padding:50px; text-align:center;"><h2>Format Not Available</h2></div>`;
    }

    // --- 🚀 AUTO-SAVE TRIGGERS 🚀 ---
    let saveTimer;
    let lastSaveTime = Date.now();
    const AUTO_SAVE_INTERVAL = 30000; // 30 seconds
    const INACTIVITY_TIMEOUT = 15000; // 15 seconds

    function setupAutoSave() {
        // Clear existing timers
        if (saveTimer) clearInterval(saveTimer);

        // Set up inactivity monitor
        let inactivityTimer;
        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(triggerAutoSave, INACTIVITY_TIMEOUT);
        }

        // Event listeners for user activity
        document.querySelectorAll('input, textarea, select').forEach(el => {
            el.addEventListener('input', resetInactivityTimer);
            el.addEventListener('change', resetInactivityTimer);
        });

        // Periodic save
        saveTimer = setInterval(triggerAutoSave, AUTO_SAVE_INTERVAL);
        resetInactivityTimer();
    }

    async function triggerAutoSave() {
        updateStatusIndicator('Saving...', '#fbbf24');

        const data = getResumeData();

        try {
            const response = await fetch(`${APP_URL}auto_save_resume.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });

            if (!response.ok) throw new Error('Network error');

            const result = await response.json();
            if (result.success) {
                updateStatusIndicator(`Saved ${formatTime()}`, '#10b981');
                lastSaveTime = Date.now();
            } else {
                throw new Error(result.message || 'Server error');
            }
        } catch (error) {
            // Retry logic
            setTimeout(() => {
                updateStatusIndicator('Retrying...', '#ef4444');
                triggerAutoSave();
            }, 5000);
        }
    }

    function updateStatusIndicator(message, color) {
        const indicator = document.getElementById('autoSaveStatus');
        if (indicator) {
            indicator.textContent = message;
            indicator.style.color = color;
        }
    }

    function formatTime() {
        const now = new Date();
        return now.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
    }



    function saveDraft() {
        const btn = event.currentTarget || document.querySelector('.btn-export[onclick="saveDraft()"]');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        btn.disabled = true;

        const data = getResumeData();
        const json = JSON.stringify(data);
        const formData = new FormData();
        formData.append('service_slug', 'resume_builder');
        formData.append('service_name', 'Ultimate Resume Maker');
        formData.append('json', json);

        fetch(APP_URL + 'save_digital_draft.php', {
            method: 'POST', body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) alert('✅ Draft saved successfully! Find it in "Saved Drafts" in the sidebar.');
            else alert('❌ Error: ' + (data.error || 'Unknown error'));
        })
        .catch(err => alert('❌ Network Error: Could not save draft.'))
        .finally(() => { btn.innerHTML = originalHtml; btn.disabled = false; });
    }

    async function handleExport() {
        if (userRole !== 'admin') {
            // will show the correct (custom) rate here
            let confirmMsg = `${currency}${resumeCost} will be deducted from your wallet to download the resume PDF.\n\nDo you want to proceed?`;
            if (!confirm(confirmMsg)) return; 
            
            try {
                // Send service name to API (for logging)
                let formData = new FormData();
                formData.append('service_type', 'Resume Builder');

                let response = await fetch(APP_URL + 'deduct_poster_balance.php', { 
                    method: 'POST',
                    body: formData
                });
                let text = await response.text(); 
                
                try {
                    let result = JSON.parse(text);
                    if (result.success) {
                        generatePDF();
                        alert(` ✅ Resume has been downloaded!\nYour new wallet balance: ${currency}${result.remaining_balance}`);
                    } else {
                        alert("❌ Error: " + result.message);
                    }
                } catch (jsonError) {
                    alert("❌ Server Error: There is an error in the API."); 
                }
            } catch (error) { 
                alert("❌ Server Error (Network). Please contact admin."); 
            }
        } else {
            // Free entry for admin
            let formData = new FormData();
            formData.append('service_type', 'Resume Builder (Admin)');
            fetch(APP_URL + 'deduct_poster_balance.php', { method: 'POST', body: formData });
            
            generatePDF(); 
        }
    }

    // Convert HTML to PDF using html2pdf
    function generatePDF() {
        const element = document.getElementById('resumePreview');
        const opt = {
            margin:       0,
            filename:     document.getElementById('fullName').value.replace(' ', '_') + '_Resume.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }
</script>
<script>
    let sysCurrentZoom = 1.0;
    function sysChangeZoom(amount) {
        sysCurrentZoom += amount;
        if(sysCurrentZoom < 0.2) sysCurrentZoom = 0.2;
        if(sysCurrentZoom > 3.0) sysCurrentZoom = 3.0;
        sysApplyZoom();
    }
    function sysResetZoom() {
        sysCurrentZoom = 1.0;
        sysApplyZoom();
    }
    function sysApplyZoom() {
        const targets = document.querySelectorAll('.canvas-container, .a4-page, .card-preview, canvas#mainCanvas');
        targets.forEach(el => {
            el.style.transform = `scale(${sysCurrentZoom})`;
            el.style.transformOrigin = 'top center';
            el.style.transition = 'transform 0.2s ease';
            el.style.marginBottom = '50px'; // Prevent cutoffs when scaled up
        });
    }
</script>
</body>
</html>