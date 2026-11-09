<?php
// XML sitemap for search engines: submit /sitemap.php in Google Search Console.
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');

$x = fn($s) => htmlspecialchars($s, ENT_XML1);
$urls = [[abs_url(url()), null, []], [abs_url(url('listings.php')), null, []], [abs_url(url('contact.php')), null, []]];
if (trim(setting('idx_embed')) !== '' && setting('registration_required') !== '1') $urls[] = [abs_url(url('idx.php')), null, []];
foreach (db()->query("SELECT slug, updated_at FROM pages WHERE status = 'published' AND meta_noindex = 0") as $r) $urls[] = [abs_url(page_url($r['slug'])), $r['updated_at'], []];
foreach (db()->query('SELECT * FROM properties WHERE seo_noindex = 0 ORDER BY id DESC') as $r) {
    $imgs = array_map(fn($im) => abs_url(url('uploads/' . $im['filename'])), array_slice(property_images((int)$r['id']), 0, 5));
    $urls[] = [abs_url(property_url($r)), $r['created_at'], $imgs];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
foreach ($urls as [$loc, $mod, $imgs]) {
    echo '  <url><loc>' . $x($loc) . '</loc>';
    if ($mod) echo '<lastmod>' . date('Y-m-d', strtotime($mod)) . '</lastmod>';
    foreach ($imgs as $i) echo '<image:image><image:loc>' . $x($i) . '</image:loc></image:image>';
    echo "</url>\n";
}
echo '</urlset>';
