<?php
$pdo = connectDB();
$currency = '₹';
$user_role = $_SESSION['user_role'] ?? 'guest';

$service_rate = 10.00;
$points_rate = 0;
$user_balance = 0.00;
$user_points = 0;

try {
    $stmt_rate = $pdo->prepare("SELECT price, points_price FROM digital_service_rates WHERE service_slug = 'resume_builder' AND is_active = 1");
    $stmt_rate->execute();
    $rate_data = $stmt_rate->fetch();
    if ($rate_data) {
        $service_rate = (float)$rate_data['price'];
        $points_rate = (int)$rate_data['points_price'];
    }

    $stmt = $pdo->query("SELECT currency_symbol FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings) {
        $currency = isset($settings['currency_symbol']) ? $settings['currency_symbol'] : '₹';
    }

    if (isset($_SESSION['user_id'])) {
        $stmt_user = $pdo->prepare("SELECT balance, poster_points, custom_poster_rate FROM users WHERE id = ?");
        $stmt_user->execute([$_SESSION['user_id']]);
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if ($user_data) {
            $user_balance = (float)$user_data['balance'];
            $user_points = (int)$user_data['poster_points'];
            if ($user_data['custom_poster_rate'] !== null && $user_data['custom_poster_rate'] !== '') {
                $service_rate = (float)$user_data['custom_poster_rate'];
            }
        }
    }
} catch (Exception $e) {}

// --- LOAD DRAFT LOGIC ---
$loaded_draft_json = null;
$loaded_draft_name = '';
if (isset($_GET['draft_id']) && isset($_SESSION['user_id'])) {
    try {
        $stmt_draft = $pdo->prepare("SELECT canvas_json, draft_name FROM digital_service_history WHERE id = ? AND user_id = ? AND is_draft = 1");
        $stmt_draft->execute([$_GET['draft_id'], $_SESSION['user_id']]);
        $draft_row = $stmt_draft->fetch(PDO::FETCH_ASSOC);
        if ($draft_row) {
            $loaded_draft_name = $draft_row['draft_name'] ?? '';
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
    <title>Resume Maker Pro | Digital Services</title>
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>img/br_favicon.png">
    
    <!-- External Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Poppins:wght@300;400;600;700&family=Roboto:wght@400;700&family=Lora:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --sidebar-width: 450px;
            --header-height: 60px;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
        }

        /* Global Reset for Studio View */
        #sidebar, .navbar { display: none !important; }
        #content { padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
        .wrapper { margin: 0 !important; padding: 0 !important; }

        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background: #cbd5e1; height: 100vh; overflow: hidden; }
        
        /* 1. STUDIO HEADER */
        .studio-header {
            height: 65px;
            background: #1e293b;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 25px;
            border-bottom: 2px solid var(--primary);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            position: relative;
            z-index: 200;
        }

        .header-title {
            color: #38bdf8;
            font-size: 20px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-back {
            background: #ef4444;
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none !important;
            transition: 0.3s;
            font-size: 14px;
        }
        .btn-back:hover { background: #dc2626; transform: scale(1.05); }

        /* Layout Structure - Adjusted for Header */
        .builder-wrapper {
            display: flex;
            height: calc(100vh - 65px);
            width: 100%;
            overflow: hidden;
            position: relative;
        }

        /* Sidebar - Editor */
        .editor-sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            background: var(--sidebar-bg, #f8fafc);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 15px rgba(0,0,0,0.05);
            z-index: 100;
        }

        .sidebar-header {
            padding: 20px;
            background: #1e293b;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .form-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            padding-bottom: 120px; /* Space for action buttons */
            background: #f1f5f9;
        }

        /* Main Area - Preview */
        .builder-main {
            flex: 1;
            background: #94a3b8;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 50px 20px;
            position: relative;
        }

        .a4-page {
            width: 210mm;
            min-height: 297mm;
            background: white;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            transform-origin: top center;
            margin-bottom: 50px;
            transition: transform 0.2s ease;
        }

        /* Form Components */
        .form-section {
            background: white;
            padding: 15px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            border-left: 5px solid var(--primary);
        }

        .section-title {
            display: block;
            font-weight: 800;
            font-size: 14px;
            color: #1e293b;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 6px;
        }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 5px; }
        
        input[type="text"], input[type="email"], select, textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            box-sizing: border-box;
            transition: 0.2s;
        }
        
        input:focus, textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }

        .btn-add {
            background: #f1f5f9;
            color: var(--primary);
            border: 1px dashed var(--primary);
            padding: 10px;
            border-radius: 8px;
            width: 100%;
            font-weight: 800;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 5px;
        }
        .btn-add:hover { background: #eef2ff; color: var(--primary-dark); }

        .btn-del {
            background: #fee2e2;
            color: #ef4444;
            border: 1px solid #fecaca;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: bold;
            cursor: pointer;
            float: right;
            margin-top: -35px;
        }

        /* Action Buttons Fixed at Bottom */
        .action-btns {
            position: absolute;
            bottom: 0;
            left: 0;
            width: var(--sidebar-width);
            background: white;
            padding: 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 10px;
            box-sizing: border-box;
            box-shadow: 0 -5px 15px rgba(0,0,0,0.05);
            z-index: 110;
        }

        .btn-export {
            flex: 1;
            background: #10b981;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-weight: 800;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: 0.2s;
        }
        .btn-export:hover { background: #059669; transform: translateY(-1px); }

        .btn-save { background: #6366f1; }
        .btn-save:hover { background: #4f46e5; }

        /* Zoom UI */
        .sys-zoom-controls {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: white;
            padding: 10px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            gap: 8px;
            z-index: 9999;
            border: 1px solid var(--border-color);
        }
        .zoom-btn {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: 0.2s;
        }
        .zoom-btn:hover { background: #e2e8f0; }

        .profile-img-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            background: #e2e8f0;
            margin-bottom: 10px;
        }

        /* Mobile Adjustments */
        @media (max-width: 900px) {
            .builder-wrapper { flex-direction: column; overflow: visible; height: auto; }
            .editor-sidebar { width: 100%; height: auto; }
            .builder-main { width: 100%; min-height: 100vh; padding: 20px; }
            .a4-page { width: 100%; transform: scale(1) !important; min-height: auto; }
            .action-btns { position: fixed; width: 100%; bottom: 0; left: 0; }
        }
    </style>
</head>
<body>

<!-- 0. STUDIO HEADER (Global for Digital Services) -->
<?php $page_title = 'Resume Maker Pro'; require_once INCLUDES_PATH.'digital_header.php'; ?>

<div class="builder-wrapper">
    <!-- 1. LEFT: EDITOR SIDEBAR -->
    <div class="editor-sidebar">
        <!-- Sidebar content now scrolls below the main header -->
        <div class="form-area" id="resumeForm">
            <!-- SECTION VISIBILITY & LABELS -->
            <div class="form-section" style="background: #eef2ff;">
                <span class="section-title" style="color: #4338ca;">⚙️ Customize Sections</span>
                <p style="font-size:10px; color:#6366f1; margin: -5px 0 10px 0;">Hide elements or rename titles below.</p>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <div style="display:flex; align-items:center; gap:10px; background:white; padding:8px 12px; border-radius:8px; border:1px solid #cbd5e1;">
                        <input type="checkbox" id="showSummary" checked onchange="renderResume()">
                        <input type="text" id="labelSummary" value="Professional Summary" oninput="renderResume()" style="border:none; outline:none; font-size:13px; font-weight:700; width:100%;">
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; background:white; padding:8px 12px; border-radius:8px; border:1px solid #cbd5e1;">
                        <input type="checkbox" id="showExperience" checked onchange="renderResume()">
                        <input type="text" id="labelExperience" value="Work Experience" oninput="renderResume()" style="border:none; outline:none; font-size:13px; font-weight:700; width:100%;">
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; background:white; padding:8px 12px; border-radius:8px; border:1px solid #cbd5e1;">
                        <input type="checkbox" id="showEducation" checked onchange="renderResume()">
                        <input type="text" id="labelEducation" value="Education" oninput="renderResume()" style="border:none; outline:none; font-size:13px; font-weight:700; width:100%;">
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; background:white; padding:8px 12px; border-radius:8px; border:1px solid #cbd5e1;">
                        <input type="checkbox" id="showSkills" checked onchange="renderResume()">
                        <input type="text" id="labelSkills" value="Key Skills" oninput="renderResume()" style="border:none; outline:none; font-size:13px; font-weight:700; width:100%;">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <span class="section-title">🎨 Format & Style</span>
                <div class="form-group">
                    <label>Choose Resume Template</label>
                    <select id="templateSelector" onchange="renderResume()">
                        <optgroup label="Premium Series">
                            <option value="minimalist">Clean Minimalist</option>
                            <option value="executive">Executive Board (Elegant)</option>
                            <option value="stellar">Stellar Pro (Blue Accent)</option>
                            <option value="tech_startup">🚀 Tech Start-Up (Neon)</option>
                            <option value="minimal_grid">🔳 Modern Grid (Square)</option>
                            <option value="elegant_right">✨ Elegant Sidebar (Right)</option>
                            <option value="harvard">Harvard Standard (Official)</option>
                        </optgroup>
                        <optgroup label="Standard Series">
                            <option value="modern">Modern Professional</option>
                            <option value="classic">Classic Corporate</option>
                            <option value="creative">Creative CV</option>
                            <option value="elegant">Elegant Grid</option>
                            <option value="timeline">Chronological Timeline</option>
                        </optgroup>
                    </select>
                </div>
                <div style="display:flex; gap:15px;">
                    <div style="flex:1;">
                        <label>Accent Color</label>
                        <input type="color" id="themeColor" value="#6366f1" oninput="renderResume()" style="height:38px; padding:2px;">
                    </div>
                    <div style="flex:1;">
                        <label>Typography</label>
                        <select id="fontSelector" onchange="renderResume()">
                            <option value="'Inter', sans-serif">Inter (Modern)</option>
                            <option value="'Poppins', sans-serif">Poppins (Clean)</option>
                            <option value="'Roboto', sans-serif">Roboto (Formal)</option>
                            <option value="'Lora', serif">Lora (Classic)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <span class="section-title">👤 Personal Presence</span>
                <div style="display:flex; align-items:center; gap:20px; margin-bottom:15px;">
                    <img src="https://via.placeholder.com/150" id="imgPreview" class="profile-img-preview">
                    <div style="flex:1;">
                        <label style="font-size:11px; font-weight:bold; color:#64748b;">Upload Photo</label>
                        <input type="file" id="profileImage" accept="image/*" onchange="handleImageUpload(event)">
                    </div>
                </div>
                <div class="form-group"><label>Full Name</label><input type="text" id="fullName" value="Rahul Patel" oninput="renderResume()"></div>
                <div class="form-group"><label>Current Role / Title</label><input type="text" id="jobTitle" value="Senior Software Architect" oninput="renderResume()"></div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div class="form-group"><label>Email</label><input type="email" id="email" value="rahul@example.com" oninput="renderResume()"></div>
                    <div class="form-group"><label>Phone</label><input type="text" id="phone" value="+91 9876543210" oninput="renderResume()"></div>
                </div>
                <div class="form-group"><label>Location (City, State)</label><input type="text" id="address" value="Ahmedabad, Gujarat" oninput="renderResume()"></div>
                <div class="form-group"><label>LinkedIn / Portfolio URL</label><input type="text" id="linkedin" value="linkedin.com/in/rahul" oninput="renderResume()"></div>
            </div>

            <div class="form-section">
                <span class="section-title">📝 Profile Summary</span>
                <textarea id="summary" rows="4" oninput="renderResume()" placeholder="Write a professional summary...">Dynamic and results-driven professional with over 8 years of experience in system architecture and team leadership. Proven track record of delivering scalable solutions and optimizing organizational workflows.</textarea>
            </div>

            <div class="form-section">
                <span class="section-title">💼 Professional Experience</span>
                <div id="experienceContainer">
                    <div class="exp-item mb-4 pb-3 border-bottom">
                        <button class="btn-del" onclick="this.parentElement.remove(); renderResume();">Remove</button>
                        <div class="form-group"><label>Job Title</label><input type="text" class="exp-title" value="Lead Project Manager" oninput="renderResume()"></div>
                        <div class="form-group"><label>Company Name</label><input type="text" class="exp-company" value="Global Tech Solutions" oninput="renderResume()"></div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                            <div class="form-group"><label>Start Date</label><input type="text" class="exp-start" value="Jan 2020" oninput="renderResume()"></div>
                            <div class="form-group"><label>End Date</label><input type="text" class="exp-end" value="Present" oninput="renderResume()"></div>
                        </div>
                        <div class="form-group"><label>Core Responsibilities</label><textarea class="exp-desc" rows="3" oninput="renderResume()">Leading a cross-functional team of 20+ members. Successfully delivered 15 multi-million dollar projects. Implemented agile methodologies reducing delivery time by 25%.</textarea></div>
                    </div>
                </div>
                <button class="btn-add" onclick="addExperience()">+ ADD ANOTHER EXPERIENCE</button>
            </div>

            <div class="form-section">
                <span class="section-title">🎓 Academic Background</span>
                <div id="educationContainer">
                    <div class="edu-item mb-3 pb-2 border-bottom">
                        <button class="btn-del" onclick="this.parentElement.remove(); renderResume();">Remove</button>
                        <div class="form-group"><label>Degree / Qualification</label><input type="text" class="edu-degree" value="Master of Computer Science" oninput="renderResume()"></div>
                        <div class="form-group"><label>University / Institution</label><input type="text" class="edu-school" value="Gujarat Technological University" oninput="renderResume()"></div>
                        <div class="form-group"><label>Graduation Year</label><input type="text" class="edu-year" value="2016" oninput="renderResume()"></div>
                    </div>
                </div>
                <button class="btn-add" onclick="addEducation()">+ ADD ANOTHER DEGREE</button>
            </div>

            <div class="form-section">
                <span class="section-title">⭐ Core Competencies (Skills)</span>
                <div class="form-group">
                    <label>Separate skills with commas</label>
                    <input type="text" id="skills" value="Leadership, System Design, React, Node.js, Cloud Computing, Project Management" oninput="renderResume()">
                </div>
            </div>

            <div class="form-section" style="background:#fdf4ff;">
                <span class="section-title" style="color:#c026d3;">✨ Custom Data Sections</span>
                <div id="customSectionsContainer"></div>
                <button class="btn-add" style="border-color:#d946ef; color:#d946ef;" onclick="addCustomSection()">+ ADD NEW CUSTOM SECTION</button>
            </div>

            <div class="form-section">
                <span class="section-title">💾 Draft Management</span>
                <div class="form-group">
                    <label>Name this version</label>
                    <input type="text" id="draftNameInput" placeholder="My Resume Feb 2026" value="<?= htmlspecialchars($loaded_draft_name) ?>">
                </div>
            </div>
        </div>

        <!-- FIXED ACTION BUTTONS -->
        <div class="action-btns" style="flex-wrap: wrap;">
            <div style="width: 100%; text-align: center; margin-bottom: 5px;">
                <span id="autoSaveStatus" style="font-size:12px; color:#6366f1; font-weight:bold;">Auto-save: Ready</span>
            </div>
            <?php if(isset($_SESSION['user_id'])): ?>
            <button class="btn-export btn-save" onclick="saveDraft(false)"><i class="fas fa-save"></i> SAVE</button>
            <?php endif; ?>
            <select id="exportFormat" style="padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; flex: 1;">
                <option value="pdf">PDF</option>
                <option value="jpg">JPG</option>
            </select>
            <button class="btn-export" style="flex: 2;" onclick="handleExport()"><i class="fas fa-download"></i> DOWNLOAD</button>
        </div>
    </div>

    <!-- 2. RIGHT: MAIN WORKSPACE -->
    <div class="builder-main">
        <div class="sys-zoom-controls">
            <button type="button" onclick="sysChangeZoom(0.1)" class="zoom-btn">➕</button>
            <button type="button" onclick="sysResetZoom()" class="zoom-btn">100%</button>
            <button type="button" onclick="sysChangeZoom(-0.1)" class="zoom-btn">➖</button>
        </div>
        
        <div id="resumePreview" class="a4-page">
            <!-- Rendered Output -->
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.9); z-index:9999; justify-content:center; align-items:center; flex-direction:column; color:white;">
    <div class="spinner-border text-primary" style="width:3rem; height:3rem; border: 4px solid #6366f1; border-top: 4px solid transparent; border-radius: 50%; animation: spin 1s linear infinite;" role="status"></div>
    <div id="loadingText" class="mt-3 fw-bold" style="font-size:18px;">Processing...</div>
</div>

<style>
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<!-- Scripts -->
<script>
    // 0. DEFINITIONS FROM PHP (GLOBAL SCOPE)
    const APP_URL = "<?= APP_URL ?>";
    const userRole = "<?= $user_role ?>";
    const currency = "<?= $currency ?>";
    const serviceRate = <?= $service_rate ?>;
    const pointsRate = <?= $points_rate ?>;
    const userBalance = <?= $user_balance ?>;
    const userPoints = <?= $user_points ?>;
    const isGuest = (userRole === 'guest' || !userRole);

    // --- UI UTILITIES ---
    function showLoading(show, text = "Processing... Please wait.") {
        const el = document.getElementById('loadingOverlay');
        const txt = document.getElementById('loadingText');
        if (el) el.style.display = show ? 'flex' : 'none';
        if (txt && text) txt.innerText = text;
    }
    const hideLoader = () => showLoading(false);
    const showLoader = (text) => showLoading(true, text);

    function forceSyncAll() {
        if(typeof renderResume === 'function') renderResume();
    }

    let profileImageData = "https://via.placeholder.com/150";
    let isStackLoading = false;

    // 1. DATA COLLECTION
    function getResumeData() {
        return {
            template: document.getElementById('templateSelector').value,
            themeColor: document.getElementById('themeColor').value,
            fontStyle: document.getElementById('fontSelector').value,
            name: document.getElementById('fullName').value,
            title: document.getElementById('jobTitle').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            address: document.getElementById('address').value,
            linkedin: document.getElementById('linkedin').value,
            summary: document.getElementById('summary').value,
            skills: document.getElementById('skills').value.split(',').map(s => s.trim()).filter(s => s),
            image: profileImageData,
            exp: Array.from(document.querySelectorAll('.exp-item')).map(el => ({
                title: el.querySelector('.exp-title').value,
                company: el.querySelector('.exp-company').value,
                start: el.querySelector('.exp-start').value,
                end: el.querySelector('.exp-end').value,
                desc: el.querySelector('.exp-desc').value
            })),
            edu: Array.from(document.querySelectorAll('.edu-item')).map(el => ({
                degree: el.querySelector('.edu-degree').value,
                school: el.querySelector('.edu-school').value,
                year: el.querySelector('.edu-year').value
            })),
            custom: Array.from(document.querySelectorAll('.custom-section-item')).map(el => ({
                title: el.querySelector('.custom-sec-title').value,
                content: el.querySelector('.custom-sec-content').value
            })),
            settings: {
                showSummary: document.getElementById('showSummary').checked,
                showExperience: document.getElementById('showExperience').checked,
                showEducation: document.getElementById('showEducation').checked,
                showSkills: document.getElementById('showSkills').checked,
                labelSummary: document.getElementById('labelSummary').value,
                labelExperience: document.getElementById('labelExperience').value,
                labelEducation: document.getElementById('labelEducation').value,
                labelSkills: document.getElementById('labelSkills').value
            }
        };
    }

    // 2. LIVE RENDERER
    function renderResume() {
        if(isStackLoading) return;
        const data = getResumeData();
        document.getElementById('resumePreview').innerHTML = generateResumeHTML(data);
    }

    // 3. HTML GENERATOR
    function generateResumeHTML(data) {
        const color = data.themeColor;
        const font = data.fontStyle;
        const type = data.template;

        const expHTML = data.exp.map(e => `
            <div style="margin-bottom:15px;">
                <div style="display:flex; justify-content:space-between; font-weight:700; color:#1e293b; font-size:14px;">
                    <span>${e.title}</span>
                    <span style="color:${color}; font-size:12px;">${e.start} - ${e.end}</span>
                </div>
                <div style="font-size:13px; color:#475569; font-weight:600; margin-bottom:4px;">${e.company}</div>
                <p style="margin:0; font-size:12px; color:#64748b; line-height:1.5;">${e.desc}</p>
            </div>
        `).join('');

        const eduHTML = data.edu.map(e => `
            <div style="margin-bottom:12px;">
                <div style="font-weight:700; font-size:13px; color:#1e293b;">${e.degree}</div>
                <div style="font-size:12px; color:#64748b;">${e.school} | ${e.year}</div>
            </div>
        `).join('');

        const customHTML = data.custom.map(c => `
            <div style="margin-bottom:20px;">
                <h3 style="font-size:16px; color:${color}; border-bottom:2px solid #f1f5f9; padding-bottom:5px; margin-bottom:12px; text-transform:uppercase;">${c.title}</h3>
                <div style="font-size:12px; color:#475569; line-height:1.6; white-space:pre-wrap;">${c.content}</div>
            </div>
        `).join('');

        const section = (show, title, content) => {
            if(!show || !content) return "";
            return `
            <div style="margin-bottom:30px;">
                <h3 style="font-size:18px; color:${color}; border-bottom:2px solid #f1f5f9; padding-bottom:5px; margin-bottom:15px; text-transform:uppercase; letter-spacing:1px;">${title}</h3>
                ${content}
            </div>`;
        };

        // RENDER LOGIC BY TEMPLATE
        if (type === 'modern' || type === 'minimalist' || type === 'professional') {
             // Basic Side Logic for Modern
             return `
             <div style="display:flex; width:100%; min-height:297mm; font-family:${font}; background:white;">
                <div style="width:35%; background:${color}; color:white; padding:40px 30px;">
                    <div style="text-align:center; margin-bottom:40px;">
                        <img src="${data.image}" style="width:130px; height:130px; border-radius:50%; border:5px solid rgba(255,255,255,0.2); object-fit:cover;">
                    </div>
                    <div style="margin-bottom:40px;">
                        <h4 style="font-size:14px; border-bottom:1px solid rgba(255,255,255,0.3); padding-bottom:5px; margin-bottom:15px; letter-spacing:1px;">CONTACT</h4>
                        <div style="font-size:11px; line-height:2;">
                            📍 ${data.address}<br>📱 ${data.phone}<br>✉️ ${data.email}<br>🔗 ${data.linkedin}
                        </div>
                    </div>
                    ${data.settings.showSkills ? `<div><h4 style="font-size:14px; border-bottom:1px solid rgba(255,255,255,0.3); padding-bottom:5px; margin-bottom:15px; letter-spacing:1px;">${data.settings.labelSkills.toUpperCase()}</h4><div style="font-size:11px; line-height:2;">${data.skills.join('<br>')}</div></div>` : ""}
                </div>
                <div style="width:65%; padding:50px 40px; color:#1e293b;">
                    <h1 style="font-size:42px; font-weight:800; margin:0; line-height:1;">${data.name}</h1>
                    <h2 style="font-size:18px; color:${color}; margin:10px 0 30px 0; font-weight:400; text-transform:uppercase; letter-spacing:2px;">${data.title}</h2>
                    ${data.settings.showSummary ? section(true, data.settings.labelSummary, `<p style="font-size:12px; line-height:1.7; color:#475569;">${data.summary}</p>`) : ""}
                    ${section(data.settings.showExperience, data.settings.labelExperience, expHTML)}
                    ${section(data.settings.showEducation, data.settings.labelEducation, eduHTML)}
                    ${customHTML}
                </div>
             </div>`;
        }

        // TECH STARTUP
        else if (type === 'tech_startup') {
            return `
            <div style="width:100%; min-height:297mm; font-family:${font}; background:#0f172a; color:#f8fafc; padding:60px; position:relative; overflow:hidden;">
                <div style="position:absolute; top:-100px; right:-100px; width:400px; height:400px; background:${color}; filter:blur(150px); opacity:0.15; border-radius:50%;"></div>
                <div style="position:relative; z-index:2;">
                    <div style="border-left:5px solid ${color}; padding-left:25px; margin-bottom:60px;">
                        <h1 style="font-size:55px; font-weight:900; margin:0; letter-spacing:-2px; color:white;">${data.name}</h1>
                        <h2 style="font-size:24px; color:${color}; margin:5px 0 0 0; text-transform:uppercase; letter-spacing:4px; font-weight:300;">${data.title}</h2>
                    </div>
                    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:60px;">
                        <div>
                            ${data.settings.showSummary ? `<div style="margin-bottom:45px; background:rgba(255,255,255,0.03); padding:25px; border-radius:15px; border:1px solid rgba(255,255,255,0.05);"><p style="margin:0; font-size:13px; line-height:1.8; color:#cbd5e1;">${data.summary}</p></div>` : ""}
                            ${section(data.settings.showExperience, data.settings.labelExperience, expHTML.replace(/#1e293b/g, 'white').replace(/#475569/g, '#94a3b8'))}
                            ${customHTML.replace(/#475569/g, '#cbd5e1')}
                        </div>
                        <div style="font-size:12px;">
                            <div style="margin-bottom:40px; color:#94a3b8; line-height:2;">
                                ✉️ ${data.email}<br>📱 ${data.phone}<br>📍 ${data.address}
                            </div>
                            ${data.settings.showSkills ? `<h3 style="font-size:16px; color:${color}; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:8px; margin-bottom:15px;">${data.settings.labelSkills}</h3><div style="display:flex; flex-wrap:wrap; gap:8px;">${data.skills.map(s => `<span style="background:${color}; color:white; padding:5px 12px; border-radius:6px; font-size:10px; font-weight:bold;">${s}</span>`).join('')}</div>` : ""}
                            <div style="margin-top:40px;">
                                ${section(data.settings.showEducation, data.settings.labelEducation, eduHTML.replace(/#1e293b/g, 'white'))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        // DEFAULT FALLBACK (Other templates)
        return `
        <div style="padding:50px; width:100%; min-height:297mm; font-family:${font}; background:white;">
            <div style="text-align:center; border-bottom:4px solid ${color}; padding-bottom:20px; margin-bottom:40px;">
                <h1 style="font-size:48px; font-weight:800; margin:0; color:#1e293b;">${data.name}</h1>
                <h3 style="font-size:20px; color:${color}; margin:10px 0;">${data.title}</h3>
                <div style="font-size:12px; color:#64748b;">${data.email} | ${data.phone} | ${data.address}</div>
            </div>
            <div style="display:flex; gap:50px;">
                <div style="width:65%;">
                    ${section(data.settings.showSummary, data.settings.labelSummary, `<p style="font-size:13px; line-height:1.7;">${data.summary}</p>`)}
                    ${section(data.settings.showExperience, data.settings.labelExperience, expHTML)}
                    ${customHTML}
                </div>
                <div style="width:35%;">
                    ${section(data.settings.showSkills, data.settings.labelSkills, `<div style="line-height:2; font-size:13px;">${data.skills.join(', ')}</div>`)}
                    ${section(data.settings.showEducation, data.settings.labelEducation, eduHTML)}
                </div>
            </div>
        </div>`;
    }

    // 4. HELPERS (Add/Remove/Image)
    function addExperience() {
        const html = `
        <div class="exp-item mb-4 pb-3 border-bottom">
            <button class="btn-del" onclick="this.parentElement.remove(); renderResume();">Remove</button>
            <div class="form-group"><label>Job Title</label><input type="text" class="exp-title" placeholder="Position" oninput="renderResume()"></div>
            <div class="form-group"><label>Company</label><input type="text" class="exp-company" placeholder="Company Name" oninput="renderResume()"></div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="form-group"><label>Start</label><input type="text" class="exp-start" value="Jan 20xx" oninput="renderResume()"></div>
                <div class="form-group"><label>End</label><input type="text" class="exp-end" value="Present" oninput="renderResume()"></div>
            </div>
            <div class="form-group"><label>Responsibilities</label><textarea class="exp-desc" rows="2" oninput="renderResume()"></textarea></div>
        </div>`;
        document.getElementById('experienceContainer').insertAdjacentHTML('beforeend', html);
        renderResume();
    }

    function addEducation() {
        const html = `
        <div class="edu-item mb-3 pb-2 border-bottom">
            <button class="btn-del" onclick="this.parentElement.remove(); renderResume();">Remove</button>
            <div class="form-group"><label>Degree</label><input type="text" class="edu-degree" placeholder="B.Sc / M.Sc" oninput="renderResume()"></div>
            <div class="form-group"><label>University</label><input type="text" class="edu-school" oninput="renderResume()"></div>
            <div class="form-group"><label>Year</label><input type="text" class="edu-year" oninput="renderResume()"></div>
        </div>`;
        document.getElementById('educationContainer').insertAdjacentHTML('beforeend', html);
        renderResume();
    }

    function addCustomSection() {
        const html = `
        <div class="custom-section-item form-section" style="background:white; border-left-color:#d946ef;">
            <button class="btn-del" style="background:#fdf4ff; color:#d946ef; border-color:#f5d0fe;" onclick="this.parentElement.remove(); renderResume();">Remove Block</button>
            <div class="form-group"><label>Section Title (e.g. Certifications)</label><input type="text" class="custom-sec-title" value="New Section" oninput="renderResume()"></div>
            <div class="form-group"><label>Section Content</label><textarea class="custom-sec-content" rows="3" oninput="renderResume()"></textarea></div>
        </div>`;
        document.getElementById('customSectionsContainer').insertAdjacentHTML('beforeend', html);
        renderResume();
    }

    function handleImageUpload(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                profileImageData = event.target.result;
                document.getElementById('imgPreview').src = profileImageData;
                renderResume();
            };
            reader.readAsDataURL(file);
        }
    }

    // 5. ZOOM & EXPORT
    let sysCurrentZoom = 1.0;
    function sysChangeZoom(amount) {
        sysCurrentZoom += amount;
        sysCurrentZoom = Math.max(0.2, Math.min(3.0, sysCurrentZoom));
        sysApplyZoom();
    }
    function sysResetZoom() { sysCurrentZoom = 1.0; sysApplyZoom(); }
    function sysApplyZoom() {
        const el = document.getElementById('resumePreview');
        el.style.transform = `scale(${sysCurrentZoom})`;
        el.style.transformOrigin = 'top center';
    }

    // This is the main entry point for the download button
    function handleExport() {
        // Clear UI artifacts
        sysResetZoom();
        
        // Start the dedicated checkout flow
        triggerDownload();
    }

    async function triggerDownload() {
        if (!isGuest && userRole !== 'admin' && userRole !== 'master_admin') {
            let actualCost = serviceRate;
            let willUsePoints = false;
            
            if (actualCost <= 0) { 
                willUsePoints = false; 
            } else if (userBalance >= actualCost) {
                const result = await Swal.fire({
                    title: 'Confirm Purchase',
                    text: `${currency}${actualCost} will be deducted from your wallet to download this resume.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Deduct & Download',
                    cancelButtonText: 'No, Cancel'
                });
                if (!result.isConfirmed) return;
            } else if (pointsRate > 0 && userPoints >= pointsRate) {
                const result = await Swal.fire({
                    title: 'Use Points?',
                    text: `Insufficient Balance. Use ${pointsRate} Points to download?`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Use Points',
                    cancelButtonText: 'Cancel'
                });
                if (!result.isConfirmed) return;
                willUsePoints = true;
            } else {
                Swal.fire({ icon: 'error', title: 'Insufficient Funds', text: `You need ${currency}${actualCost} or ${pointsRate} Points.` });
                return;
            }
            await processPayment(willUsePoints);
        } else if (isGuest) {
            const result = await Swal.fire({
                title: 'Confirm Guest Download',
                text: "Confirm using your single free daily guest pass to download this Resume?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Use Pass',
                cancelButtonText: 'Cancel'
            });
            if (!result.isConfirmed) return;
            await processPayment(false);
        } else {
            // Admin override
            await processPayment(false);
        }
    }

    async function processPayment(willUsePoints) {
        const btn = document.querySelector('.btn-export:not(.btn-save)');
        const oldHtml = btn.innerHTML;
        btn.innerHTML = "<i class='fas fa-spinner fa-spin'></i> Processing...";
        btn.disabled = true;

        try {
            let formData = new FormData();
            formData.append('service_slug', 'resume_builder');
            formData.append('service_type', 'Resume Builder Pro');
            if (willUsePoints) formData.append('use_points', '1');

            let response = await fetch(APP_URL + 'deduct_poster_balance.php', { method: 'POST', body: formData });
            let text = await response.text(); 
            
            try {
                let result = JSON.parse(text);
                if (result.success) {
                    if(isGuest || result.cost <= 0) {
                        Swal.fire({ icon: 'success', title: 'Success', text: result.message || "✅ Guest pass used!" });
                    } else {
                        Swal.fire({ 
                            icon: 'success', 
                            title: 'Success!', 
                            html: `Paid from: <b>${result.deducted_type === 'points' ? result.cost + ' Pts' : currency + result.cost}</b>` 
                        });
                    }
                    await executeFinalDownload();
                } else { 
                    Swal.fire({ icon: 'error', title: 'Error', text: result.message }); 
                }
            } catch (e) { 
                console.error("JSON Error:", text);
                Swal.fire({ icon: 'error', title: 'Parse Error', text: 'API Server error. Check internet connection.' }); 
            }
        } catch (e) { 
            Swal.fire({ icon: 'error', title: 'Connection Error', text: 'Connection failed.' }); 
        }
        
        btn.innerHTML = oldHtml;
        btn.disabled = false;
        sysApplyZoom(); // Restore zoom if needed
    }

    async function executeFinalDownload() {
        const format = document.getElementById('exportFormat').value;
        const element = document.getElementById('resumePreview');
        const fName = document.getElementById('draftNameInput').value || 'My_Resume';

        if (format === 'pdf') {
            const opt = { 
                margin: 0, 
                filename: fName + '.pdf', 
                image: { type: 'jpeg', quality: 0.98 }, 
                html2canvas: { scale: 3, useCORS: true, letterRendering: true }, 
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            await html2pdf().set(opt).from(element).save();
        } else {
            // JPG Export
            const canvas = await html2canvas(element, { scale: 3, useCORS: true });
            const imgData = canvas.toDataURL('image/jpeg', 0.98);
            const link = document.createElement('a');
            link.download = fName + '.jpg';
            link.href = imgData;
            link.click();
        }
    }

    // 6. SAVE DRAFT
    function saveDraft(isAsNew = false) {
        const statusEl = document.getElementById('autoSaveStatus');
        statusEl.innerText = "Saving...";
        
        const data = getResumeData();
        const draftName = document.getElementById('draftNameInput').value || "Unnamed Resume";
        
        const formData = new FormData();
        formData.append('action', 'save_resume_draft');
        formData.append('draft_name', draftName);
        formData.append('canvas_json', JSON.stringify(data));
        if(isAsNew) formData.append('as_new', '1');

        fetch('Code/Core_Logic/App/ajax_digital_services.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                statusEl.innerText = "Saved Successfully!";
                setTimeout(() => { statusEl.innerText = "Auto-save: Ready"; }, 3000);
            } else {
                statusEl.innerText = "Save Failed: " + (res.error || "Unknown");
            }
        }).catch(err => {
            statusEl.innerText = "Connection Error";
            console.error(err);
        });
    }

    // Initialize
    window.onload = function() {
        renderResume();
        // Restore Draft if exists
        <?php if($loaded_draft_json): ?>
        try {
            const draft = <?= $loaded_draft_json ?>;
            isStackLoading = true;
            document.getElementById('templateSelector').value = draft.template || 'modern';
            document.getElementById('themeColor').value = draft.themeColor || '#6366f1';
            document.getElementById('fullName').value = draft.name || '';
            document.getElementById('jobTitle').value = draft.title || '';
            document.getElementById('email').value = draft.email || '';
            document.getElementById('phone').value = draft.phone || '';
            document.getElementById('address').value = draft.address || '';
            document.getElementById('linkedin').value = draft.linkedin || '';
            document.getElementById('summary').value = draft.summary || '';
            document.getElementById('skills').value = draft.skills.join(', ');
            profileImageData = draft.image || profileImageData;
            document.getElementById('imgPreview').src = profileImageData;
            
            // Settings
            if(draft.settings) {
                document.getElementById('showSummary').checked = draft.settings.showSummary !== false;
                document.getElementById('labelSummary').value = draft.settings.labelSummary || 'Professional Summary';
                // ... other settings ...
            }

            // Restore Experience
            const expCont = document.getElementById('experienceContainer');
            expCont.innerHTML = '';
            (draft.exp || []).forEach(e => {
                addExperience();
                const last = expCont.lastElementChild;
                last.querySelector('.exp-title').value = e.title;
                last.querySelector('.exp-company').value = e.company;
                last.querySelector('.exp-start').value = e.start;
                last.querySelector('.exp-end').value = e.end;
                last.querySelector('.exp-desc').value = e.desc;
            });

            isStackLoading = false;
            renderResume();
        } catch(e) { console.error("Draft restore error", e); }
        <?php endif; ?>
    };
</script>
</body>
</html>