<?php
/**
 * user/settings.php
 * Allows users to update their profile information.
 */

$pdo = connectDB();
$currentUserId = $_SESSION['user_id'];
$message = '';

// The POST logic is now handled in app/actions.php, so we just display messages here.
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}

$user = fetchOne($pdo, "SELECT name, email FROM users WHERE id = ?", [$currentUserId]);
?>

<h2 class="mb-4">My Settings</h2>
<?php if ($message) { include VIEWS_PATH . 'components/message_box.php'; } ?>

<div class="row">
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Profile Picture</h5>
            </div>
            <div class="card-body text-center">
                <img src="<?= !empty($_SESSION['user_profile_picture']) ? BASE_URL . htmlspecialchars($_SESSION['user_profile_picture']) : ASSETS_URL . 'images/default-profile.png' ?>" alt="Profile Picture" class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                <form action="index.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="page" value="user_settings">
                    <input type="hidden" name="action" value="update_profile_picture">
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Change Picture</label>
                        <input type="file" name="profile_picture" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Profile Information</h5>
            </div>
            <div class="card-body">
                <form action="index.php" method="POST">
                    <input type="hidden" name="page" value="user_settings">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email (Cannot be changed)</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Change Password</h5>
            </div>
            <div class="card-body">
                <form action="index.php" method="POST">
                    <input type="hidden" name="page" value="user_settings">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6">
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>