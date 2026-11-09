<?php
// robots.txt, generated so it always points at the right sitemap. Served as /robots.txt via .htaccess.
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\n";
if (setting('seo_noindex') === '1') {
    echo "Disallow: /\n";
} else {
    echo 'Disallow: ' . url('admin/') . "\n";
    echo 'Disallow: ' . url('login.php') . "\n";
    echo 'Disallow: ' . url('register.php') . "\n";
    echo "Allow: " . url('uploads/') . "\n";
    echo "\nSitemap: " . abs_url(url('sitemap.php')) . "\n";
}
