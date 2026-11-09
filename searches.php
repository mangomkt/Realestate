<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = current_user();
if (!$user) redirect('login.php?next=' . urlencode('searches.php'));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    db()->prepare('DELETE FROM saved_searches WHERE id = ? AND user_id = ?')->execute([(int)($_POST['id'] ?? 0), $user['id']]);
    flash('Saved search removed.');
    redirect('searches.php');
}
$st = db()->prepare('SELECT * FROM saved_searches WHERE user_id = ? ORDER BY id DESC');
$st->execute([$user['id']]);
$rows = $st->fetchAll();
$pageTitle = 'My saved searches';
$seo = ['robots' => 'noindex'];
include __DIR__ . '/includes/header.php';
?>
<div class="wrap section readable">
  <h1>My saved searches</h1>
  <?php foreach ($rows as $r): $f = search_clean(json_decode($r['filters'], true) ?: []); ?>
    <div class="panel" style="margin-bottom:12px;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap">
      <a href="<?= e(url('listings.php' . ($f ? '?' . http_build_query($f) : ''))) ?>"><strong><?= e(search_label($f)) ?></strong></a>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-ghost">Remove</button></form>
    </div>
  <?php endforeach; if (!$rows) echo '<p class="muted">Nothing saved yet. Run a search on the <a href="' . e(url('listings.php')) . '">listings page</a> and choose "Email me new matches".</p>'; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
