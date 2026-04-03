<?php
/**
 * sitemap.php
 * Dynamically generates a sitemap.xml for search engines.
 */
header("Content-Type: application/xml; charset=utf-8");

require_once 'config.php';
require_once MODELS_PATH . 'db.php';
$pdo = connectDB();

// Fetch settings for base URL (if needed) or use BASE_URL from config
$baseUrl = rtrim(BASE_URL, '/') . '/';

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// 1. Home Page
echo '<url><loc>' . $baseUrl . '</loc><priority>1.0</priority><changefreq>daily</changefreq></url>';

// 2. Main Public Pages
$publicPages = ['b2c_home', 'login', 'appointment'];
foreach ($publicPages as $page) {
    echo '<url><loc>' . $baseUrl . '?page=' . $page . '</loc><priority>0.8</priority><changefreq>weekly</changefreq></url>';
}

// 3. Digital Services
try {
    $services = fetchAll($pdo, "SELECT service_slug FROM digital_service_rates");
    foreach ($services as $svc) {
        echo '<url><loc>' . $baseUrl . '?page=digital_service&service=' . $svc['service_slug'] . '</loc><priority>0.7</priority><changefreq>monthly</changefreq></url>';
    }
} catch (Exception $e) { /* Table may not exist yet */ }

// 4. Dynamic Pages from B2C Menus
try {
    $dynamicPages = fetchAll($pdo, "SELECT slug FROM b2c_menus WHERE status='active' AND is_dynamic_page=1");
    foreach ($dynamicPages as $dp) {
        echo '<url><loc>' . $baseUrl . '?page=b2c_page&slug=' . $dp['slug'] . '</loc><priority>0.6</priority><changefreq>monthly</changefreq></url>';
    }
} catch (Exception $e) { /* Table may not exist yet */ }

echo '</urlset>';
