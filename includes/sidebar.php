<?php
/**
 * views/includes/sidebar.php
 *
 * This file contains the HTML structure and PHP logic for the navigation sidebar.
 * It dynamically displays menu items based on the logged-in user's role.
 * This file is included in all pages that require the sidebar.
 */

// Ensure configuration and authentication functions are available
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config.php';
}
require_once MODELS_PATH . 'auth.php'; // For isLoggedIn() and $_SESSION['user_role']
require_once MODELS_PATH . 'db.php';   // For connectDB() and getUnreadMessageCount()
// recruitment_post.php is now included in config.php

// Check if user is logged in
if (!isLoggedIn()) {
    // This file should ideally only be included on authenticated pages,
    // but as a fallback, if not logged in, redirect to login page.
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$userRole = $_SESSION['user_role'] ?? 'guest';
$userName = $_SESSION['user_name'] ?? 'Guest';
$currentUserId = $_SESSION['user_id'] ?? null; // Get current user ID from session

// Fetch app logo and name from settings for the sidebar header
$app_logo_url = '';
$app_name_setting = 'Project Management System'; // Default value
$unreadMessageCount = 0; // Initialize unread message count

try {
    $pdo = connectDB(); // Assuming connectDB() returns a PDO object
    $stmt = $pdo->query("SELECT app_name, app_logo_url FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings) {
        // Use null coalescing operator to ensure a string is always passed to htmlspecialchars
        $app_name_setting = htmlspecialchars($settings['app_name'] ?? 'Project Management System');
        $app_logo_url = htmlspecialchars($settings['app_logo_url'] ?? '');
    }

    // Fetch unread message count if user is logged in and user ID is available
    if ($currentUserId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = :user_id AND is_read = 0");
        $stmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
        $stmt->execute();
        $unreadMessageCount = $stmt->fetchColumn();
    }

} catch (PDOException $e) {
    error_log("Error fetching app settings or unread messages in sidebar: " . $e->getMessage());
    // Fallback to default names already initialized, unread count remains 0
}

// --- DEBUG: Log the user role to check what PHP sees in error.log ---
error_log("DEBUG: sidebar.php - Logged in user role: '" . $userRole . "' for user: '" . ($userName ?? 'N/A') . "'");
// --- DEBUG: Display user role directly on the page for visual confirmation ---
// આ લાઇનને અનકમેન્ટ કરો જો તમે DEO તરીકે લૉગિન કરો ત્યારે ભૂમિકાને સીધી સ્ક્રીન પર જોવા માંગતા હો
// echo '<h1 style="color: red; text-align: center; margin-top: 20px;">DEBUG ROLE: ' . $userRole . '</h1>';
?>

<!-- Sidebar -->
<nav id="sidebar" class="shadow-lg">
    <div class="sidebar-header text-center py-4">
        <?php if (!empty($app_logo_url)): ?>
            <img src="<?= $app_logo_url ?>" alt="App Logo" class="img-fluid mb-2" style="max-height: 60px;">
        <?php else: ?>
            <i class="fas fa-project-diagram fa-3x text-white mb-2"></i>
        <?php endif; ?>
        <h3 class="text-white"><?= $app_name_setting ?></h3>
        <p class="text-white-50 mb-0">Hello, <?= htmlspecialchars($userName) ?> (<?= ucwords(str_replace('_', ' ', $userRole)) ?>)</p>
    </div>

    <ul class="list-unstyled components">
        <?php if ($userRole === 'admin'): ?>
            <li>
                <a href="<?= BASE_URL ?>?page=dashboard">
                    <i class="fas fa-fw fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="#usersSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-fw fa-users"></i> Users
                </a>
                <ul class="collapse list-unstyled" id="usersSubmenu">
                    <li><a href="<?= BASE_URL ?>?page=users"><i class="fas fa-user-cog me-2"></i>Manage Users</a></li>
                    <li><a href="<?= BASE_URL ?>?page=add_user"><i class="fas fa-user-plus me-2"></i>Register New User</a></li>
                </ul>
            </li>
            <li>
                <a href="<?= BASE_URL ?>?page=clients">
                    <i class="fas fa-fw fa-user-tie"></i> Clients
                </a>
            </li>
            <li>
                <a href="#tasksSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-fw fa-tasks"></i> Tasks
                </a>
                <ul class="collapse list-unstyled" id="tasksSubmenu">
                    <li>
                        <a href="<?= BASE_URL ?>?page=assign_task">Assign New Task</a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>?page=all_tasks">View All Tasks</a>
                    </li>
                </ul>
            </li>
             <li>
                <a href="#expensesSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-fw fa-money-bill-wave"></i> Expenses
                </a>
                <ul class="collapse list-unstyled" id="expensesSubmenu">
                    <li>
                        <a href="<?= BASE_URL ?>?page=add_expense">Add Expense</a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>?page=manage_expenses">Manage Expenses</a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="#categoriesSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-fw fa-sitemap"></i> Categories
                </a>
                <ul class="collapse list-unstyled" id="categoriesSubmenu">
                    <li><a href="<?= BASE_URL ?>?page=categories"><i class="fas fa-folder-open me-2"></i>Manage Categories</a></li>
                    <li><a href="<?= BASE_URL ?>?page=subcategories"><i class="fas fa-sitemap me-2"></i>Manage Subcategories</a></li>
                </ul>
            </li>
            <li>
                <a href="<?= BASE_URL ?>?page=reports">
                    <i class="fas fa-fw fa-chart-pie"></i> Reports
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>?page=settings">
                    <i class="fas fa-fw fa-cogs"></i> Settings
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>?page=manage_recruitment_posts">
                    <i class="fas fa-fw fa-bullhorn"></i> Recruitment Posts
                </a>
            </li>
            <li>
                <!-- Added ID for JavaScript to update the count and added conditional display -->
                <a href="<?= BASE_URL ?>?page=messages" class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-fw fa-comments"></i> Messenger</span>
                    <span id="message-count-badge" class="badge bg-danger rounded-pill ms-auto px-2 py-1"
                          style="display: <?= ($unreadMessageCount > 0) ? 'inline-block' : 'none'; ?>;">
                        <?= $unreadMessageCount ?>
                    </span>
                </a>
            </li>
        <?php elseif ($userRole === 'data_entry_operator'): ?>
            <!-- DEO Dashboard -->
            <li>
                <a href="<?= BASE_URL ?>?page=deo_dashboard">
                    <i class="fas fa-fw fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>?page=add_client">
                    <i class="fas fa-fw fa-user-plus"></i> Add New Client
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>?page=add_recruitment_post">
                    <i class="fas fa-fw fa-bullhorn"></i> Add Recruitment Post
                </a>
            </li>
            <li>
                <!-- Added ID for JavaScript to update the count and added conditional display -->
                <a href="<?= BASE_URL ?>?page=messages" class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-fw fa-comments"></i> Messenger</span>
                    <span id="message-count-badge" class="badge bg-danger rounded-pill ms-auto px-2 py-1"
                          style="display: <?= ($unreadMessageCount > 0) ? 'inline-block' : 'none'; ?>;">
                        <?= $unreadMessageCount ?>
                    </span>
                </a>
            </li>
        <?php elseif (in_array($userRole, ['manager', 'assistant', 'coordinator', 'sales', 'accountant'])): ?>
            <!-- Other regular user roles -->
            <li>
                <a href="<?= BASE_URL ?>?page=user_dashboard">
                    <i class="fas fa-fw fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <?php // Show clients menu for Manager and Assistant
            if (in_array($userRole, ['manager', 'assistant'])) :
            ?>
                <li>
                    <a href="#clientSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-fw fa-user-tie"></i> Clients
                    </a>
                    <ul class="collapse list-unstyled" id="clientSubmenu">
                        <li>
                            <a href="<?= BASE_URL ?>?page=clients">View Clients</a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>?page=add_client">Add New Client</a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>
            <li>
                <a href="<?= BASE_URL ?>?page=my_tasks">
                    <i class="fas fa-fw fa-tasks"></i> My Tasks
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>?page=submit_work">
                    <i class="fas fa-fw fa-plus-square"></i> Submit New Work
                </a>
            </li>
            <li>
                <!-- Added ID for JavaScript to update the count and added conditional display -->
                <a href="<?= BASE_URL ?>?page=messages" class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-fw fa-comments"></i> Messenger</span>
                    <span id="message-count-badge" class="badge bg-danger rounded-pill ms-auto px-2 py-1"
                          style="display: <?= ($unreadMessageCount > 0) ? 'inline-block' : 'none'; ?>;">
                        <?= $unreadMessageCount ?>
                    </span>
                </a>
            </li>
        <?php else: // For any other unexpected roles or guests (though isLoggedIn() should prevent this) ?>
             <li>
                <a href="<?= BASE_URL ?>?page=home">
                    <i class="fas fa-fw fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
        <?php endif; ?>
        <li>
            <a href="<?= BASE_URL ?>?page=logout">
                <i class="fas fa-fw fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</nav>
<!-- End Sidebar -->
