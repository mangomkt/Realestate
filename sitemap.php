<?php
// XML sitemap for search engines: submit /sitemap.php in Google Search Console.
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$origin = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$urls = [[$origin . url(), null], [$origin . url('listings.php'), null], [$origin . url('contact.php'), null]];
if (trim(setting('idx_embed')) !== '' && setting('registration_required') !== '1') $urls[] = [$origin . url('idx.php'), null];
foreach (db()->query("SELECT slug, updated_at FROM pages WHERE status = 'published'") as $r) $urls[] = [$origin . page_url($r['slug']), $r['updated_at']];
foreach (db()->query('SELECT id, created_at FROM properties') as $r) $urls[] = [$origin . url('property.php?id=' . $r['id']), $r['created_at']];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$loc, $mod]) {
    echo '  <url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>';
    if ($mod) echo '<lastmod>' . date('Y-m-d', strtotime($mod)) . '</lastmod>';
    echo "</url>\n";
}
echo '</urlset>';
