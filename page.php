<?php
require_once __DIR__ . '/includes/bootstrap.php';

$slug = strtolower(trim((string)($_GET['slug'] ?? '')));
$st = db()->prepare('SELECT * FROM pages WHERE slug = ?');
$st->execute([$slug]);
$p = $st->fetch();
$isDraft = $p && $p['status'] !== 'published';

if (!$p || ($isDraft && !is_admin())) {
    http_response_code(404);
    $pageTitle = 'Page not found';
    $seo = ['robots' => 'noindex'];
    include __DIR__ . '/includes/header.php';
    echo '<div class="wrap readable section"><h1>Page not found</h1><p><a href="' . e(url()) . '">Back to the home page</a></p></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$canonical = abs_url(page_url($p['slug']));
$seo = [
    'title' => seo_page_title($p['meta_title'] !== '' ? $p['meta_title'] : $p['title']),
    'desc' => $p['meta_desc'] !== '' ? $p['meta_desc'] : excerpt((string)$p['content']),
    'image' => (string)($p['meta_image'] ?? ''),
    'canonical' => $canonical,
    'robots' => ($isDraft || !empty($p['meta_noindex'])) ? 'noindex,follow' : '',
    'jsonld' => [
        ['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => $p['title'], 'url' => $canonical, 'dateModified' => date('c', strtotime((string)$p['updated_at']) ?: time())],
        seo_breadcrumb_ld([['Home', url()], [$p['title'], page_url($p['slug'])]]),
    ],
];
include __DIR__ . '/includes/header.php';
?>
<?php if ($isDraft): ?><div class="draft-banner">Draft preview: visitors cannot see this page until you publish it.</div><?php endif; ?>
<?php
$hasSections = db()->prepare('SELECT COUNT(*) FROM home_sections WHERE page_id = ?');
$hasSections->execute([(int)$p['id']]);
if ((int)$hasSections->fetchColumn() > 0) {
    home_render_all((int)$p['id'], $p);   // page built from sections (the text is one of them)
} else {
    home_sec_content($p, true);           // plain text page
}
?>
<?php include __DIR__ . '/includes/footer.php'; ?>
