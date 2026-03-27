<?php
// views/b2c_page.php
$slug = $_GET['slug'] ?? '';
$pdo = connectDB();
$stmt = $pdo->prepare("SELECT * FROM custom_pages WHERE slug = ? AND status = 'active'");
$stmt->execute([$slug]);
$pageInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pageInfo) {
    http_response_code(404);
    echo "<div class='container my-5 text-center'><h2>Page Not Found</h2><p>The page you are looking for does not exist or has been disabled.</p><a href='?page=b2c_home' class='btn btn-primary'>Go Home</a></div>";
    exit;
}

include __DIR__ . '/includes/b2c_header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4" style="color: var(--primary-color); border-bottom: 2px solid var(--primary-color); padding-bottom: 10px; display: inline-block;">
                <?= htmlspecialchars($pageInfo['title']) ?>
            </h1>
            
            <div class="page-content" style="line-height: 1.8; font-size: 1.1rem; color: #333;">
                <?= $pageInfo['content'] ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/b2c_footer.php'; ?>
