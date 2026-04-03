<?php
/**
 * views/digital_services.php
 * Card-based dashboard for all digital services.
 */

if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-danger'>Please login to access digital services.</div>";
    return;
}

$user_role = $_SESSION['user_role'] ?? 'guest';
$user_permissions = $_SESSION['user_permissions'] ?? [];
$isAdmin = in_array($user_role, ['master_admin', 'admin']);

$services = [
    [
        'title' => 'Service History',
        'icon' => 'fas fa-history text-muted',
        'desc' => 'View usage and transaction logs for all digital services.',
        'link' => '?page=digital_service_history',
        'perm' => 'digital_service_history',
        'bg' => '#f8f9fa',
        'border' => '#e2e8f0'
    ],
    [
        'title' => 'Saved Drafts',
        'icon' => 'fas fa-save text-muted',
        'desc' => 'Manage your saved projects and continue where you left off.',
        'link' => '?page=digital_drafts',
        'perm' => 'digital_drafts',
        'bg' => '#f8f9fa',
        'border' => '#e2e8f0'
    ],
    [
        'title' => 'Poster Design',
        'icon' => 'fas fa-paint-brush text-danger',
        'desc' => 'Create stunning digital posters and marketing materials.',
        'link' => '?page=poster_studio',
        'perm' => 'poster_studio',
        'bg' => '#fff5f5',
        'border' => '#fecaca'
    ],
    [
        'title' => 'Resume Builder',
        'icon' => 'fas fa-file-alt text-info',
        'desc' => 'Build professional resumes and download them instantly.',
        'link' => '?page=resume_builder',
        'perm' => 'resume_builder',
        'bg' => '#f0f9ff',
        'border' => '#bae6fd'
    ],
    [
        'title' => 'Smart Card',
        'icon' => 'fas fa-id-card text-warning',
        'desc' => 'Design and print PVC smart cards with high precision.',
        'link' => '?page=smart_card',
        'perm' => 'smart_card',
        'bg' => '#fffbeb',
        'border' => '#fde68a'
    ],
    [
        'title' => 'Passport Photo',
        'icon' => 'fas fa-id-badge text-primary',
        'desc' => 'Create perfect passport size photos from standard images.',
        'link' => '?page=passport_photo',
        'perm' => 'passport_photo',
        'bg' => '#f0f4ff',
        'border' => '#bfdbfe'
    ],
    [
        'title' => 'Document Converter',
        'icon' => 'fas fa-file-export text-success',
        'desc' => 'Convert documents between multiple formats easily.',
        'link' => '?page=document_converter',
        'perm' => 'document_converter',
        'bg' => '#f0fdf4',
        'border' => '#bbf7d0'
    ],
    [
        'title' => 'Size Converter',
        'icon' => 'fas fa-expand-arrows-alt text-secondary',
        'desc' => 'Resize your images to specific dimensions and limits.',
        'link' => '?page=size_converter',
        'perm' => 'size_converter',
        'bg' => '#f8fafc',
        'border' => '#cbd5e1'
    ],
    [
        'title' => 'Photo Studio Pro',
        'icon' => 'fas fa-camera-retro',
        'icon_style' => 'color: #6f42c1;',
        'desc' => 'Advanced image editing, coloring, enhancement and more.',
        'link' => '?page=photo_studio',
        'perm' => 'photo_studio',
        'bg' => '#f5f3ff',
        'border' => '#ddd6fe'
    ]
];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="fw-bold mb-1" style="color: #1e293b;"><i class="fas fa-laptop-code text-primary me-2"></i> Digital Services Dashboard</h2>
            <p class="text-muted mb-0">Select a tool to start working on your next digital project.</p>
        </div>
        <a href="<?= BASE_URL ?>?page=dashboard" class="btn btn-outline-secondary rounded-pill shadow-sm px-4 fw-bold">
            <i class="fas fa-arrow-left me-2"></i> Home
        </a>
    </div>

    <div class="row g-4">
        <?php foreach ($services as $srv): 
            // Permission check: history and drafts are always available to digital users
            $can_access = $isAdmin || in_array($srv['perm'], $user_permissions) || in_array($srv['perm'], ['digital_service_history', 'digital_drafts']);
            if (!$can_access) continue;
        ?>
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
            <a href="<?= $srv['link'] ?>" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm digital-card" style="background: <?= $srv['bg'] ?>; border: 1px solid <?= $srv['border'] ?> !important; border-radius: 16px; transition: transform 0.3s, box-shadow 0.3s; overflow: hidden;">
                    <div class="card-body p-4 text-center d-flex flex-column align-items-center justify-content-center">
                        <div class="icon-circle shadow-sm mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; border-radius: 50%; background: white;">
                            <i class="<?= $srv['icon'] ?> fa-2x" <?= isset($srv['icon_style']) ? 'style="'.$srv['icon_style'].'"' : '' ?>></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2"><?= $srv['title'] ?></h5>
                        <p class="text-muted small mb-0" style="line-height: 1.5;"><?= $srv['desc'] ?></p>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.digital-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
}
.icon-circle {
    transition: transform 0.3s;
}
.digital-card:hover .icon-circle {
    transform: scale(1.1);
}
</style>
