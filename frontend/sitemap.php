<?php
/**
 * sitemap.php - خريطة الموقع لمحركات البحث
 */
require_once __DIR__ . '/../config.php';
header('Content-Type: application/xml; charset=utf-8');
$db = getDBConnection();

$pages = [
    ['url' => 'frontend/index.php', 'priority' => '1.0', 'changefreq' => 'daily'],
    ['url' => 'frontend/auth.php', 'priority' => '0.7', 'changefreq' => 'monthly'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($pages as $p) {
    echo "  <url>\n";
    echo "    <loc>" . APP_URL . '/' . $p['url'] . "</loc>\n";
    echo "    <changefreq>" . $p['changefreq'] . "</changefreq>\n";
    echo "    <priority>" . $p['priority'] . "</priority>\n";
    echo "  </url>\n";
}

// إعلانات نشطة
try {
    $stmt = $db->query("SELECT id, slug, updatedAt FROM ads WHERE status = 'active' ORDER BY id DESC LIMIT 5000");
    foreach ($stmt->fetchAll() as $ad) {
        $loc = APP_URL . '/frontend/ad.php?id=' . $ad['id'] . ($ad['slug'] ? '&slug=' . urlencode($ad['slug']) : '');
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        echo "    <lastmod>" . date('Y-m-d', strtotime($ad['updatedAt'])) . "</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.8</priority>\n";
        echo "  </url>\n";
    }
} catch (Exception $e) {}

echo '</urlset>';
