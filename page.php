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
    include __DIR__ . '/includes/header.php';
    echo '<div class="wrap readable section"><h1>Page not found</h1><p><a href="' . e(url()) . '">Back to the home page</a></p></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $p['meta_title'] !== '' ? $p['meta_title'] : $p['title'];
$metaDesc = $p['meta_desc'] !== '' ? $p['meta_desc'] : excerpt((string)$p['content']);
include __DIR__ . '/includes/header.php';
?>
<?php if ($isDraft): ?><div class="draft-banner">Draft preview: visitors cannot see this page until you publish it.</div><?php endif; ?>
<article class="readable section">
  <h1><?= e($p['title']) ?></h1>
  <div class="cms"><?= $p['content'] /* sanitized when saved */ ?></div>
</article>
<?php include __DIR__ . '/includes/footer.php'; ?>
