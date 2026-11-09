<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Listings';

$f = search_clean($_GET);
[$where, $args] = search_where($f);
$q = $f['q'] ?? '';
$status = $f['status'] ?? '';
$type = $f['type'] ?? '';

// Logged-in visitors can save the current search to get email alerts.
$user = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_search'])) {
    csrf_check();
    if ($user) {
        $mx = (int)db()->query('SELECT COALESCE(MAX(id),0) FROM properties')->fetchColumn();
        $dup = db()->prepare('SELECT 1 FROM saved_searches WHERE user_id = ? AND filters = ?');
        $json = json_encode(search_clean($_POST));
        $dup->execute([$user['id'], $json]);
        if (!$dup->fetchColumn()) db()->prepare('INSERT INTO saved_searches (user_id, filters, last_property_id) VALUES (?,?,?)')->execute([$user['id'], $json, $mx]);
        flash('Search saved. We will email you when new matches are listed.');
    }
    redirect('listings.php' . ($f ? '?' . http_build_query($f) : ''));
}

$orders = ['new' => 'created_at DESC', 'low' => 'price ASC', 'high' => 'price DESC'];
$sort = $_GET['sort'] ?? 'new';
$order = $orders[$sort] ?? $orders['new'];

$perPage = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$cnt = db()->prepare('SELECT COUNT(*) FROM properties WHERE ' . implode(' AND ', $where));
$cnt->execute($args);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);

$st = db()->prepare('SELECT * FROM properties WHERE ' . implode(' AND ', $where) . " ORDER BY $order LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
$st->execute($args);
$rows = $st->fetchAll();

function page_link(int $p): string { $g = $_GET; $g['page'] = $p; return '?' . http_build_query($g); }

$seo = ['canonical' => abs_url(url('listings.php'))];
if ($_GET) $seo['robots'] = 'noindex,follow';   // filtered / paged result pages: keep out of the index
include __DIR__ . '/includes/header.php';
?>
<div class="wrap section">
  <h1>Listings</h1>
  <form class="filters" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="City, address, MLS&reg; #">
    <select name="status"><option value="">Any status</option>
      <?php foreach (STATUSES as $k => $v) echo '<option value="' . e($k) . '"' . ($status === $k ? ' selected' : '') . '>' . e($v) . '</option>'; ?></select>
    <select name="type"><option value="">Any type</option>
      <?php foreach (TYPES as $k => $v) echo '<option value="' . e($k) . '"' . ($type === $k ? ' selected' : '') . '>' . e($v) . '</option>'; ?></select>
    <input type="number" name="min" min="0" step="10000" placeholder="Min price" value="<?= e($_GET['min'] ?? '') ?>">
    <input type="number" name="max" min="0" step="10000" placeholder="Max price" value="<?= e($_GET['max'] ?? '') ?>">
    <select name="beds"><option value="">Beds</option>
      <?php for ($i = 1; $i <= 6; $i++) echo "<option value=\"$i\"" . (($_GET['beds'] ?? '') == $i ? ' selected' : '') . ">$i+</option>"; ?></select>
    <select name="baths"><option value="">Baths</option>
      <?php for ($i = 1; $i <= 5; $i++) echo "<option value=\"$i\"" . (($_GET['baths'] ?? '') == $i ? ' selected' : '') . ">$i+</option>"; ?></select>
    <select name="sort">
      <option value="new"<?= $sort === 'new' ? ' selected' : '' ?>>Newest</option>
      <option value="low"<?= $sort === 'low' ? ' selected' : '' ?>>Price: low to high</option>
      <option value="high"<?= $sort === 'high' ? ' selected' : '' ?>>Price: high to low</option>
    </select>
    <button class="btn" type="submit">Filter</button>
  </form>
  <?php if ($f && setting('registration_enabled', '1') === '1'): ?>
    <?php if ($user): ?>
      <form method="post" class="save-search"><?= csrf_field() ?>
        <?php foreach ($f as $k => $v) echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">'; ?>
        <button class="btn btn-sm btn-ghost" name="save_search" value="1">&#9733; Email me new matches for this search</button>
        <a class="small" href="<?= url('searches.php') ?>">My saved searches</a>
      </form>
    <?php else: ?>
      <p class="small"><a href="<?= url('login.php') ?>">Log in</a> or <a href="<?= url('register.php') ?>">register</a> to get emailed when new listings match this search.</p>
    <?php endif; ?>
  <?php endif; ?>

  <p class="muted"><?= $total ?> propert<?= $total === 1 ? 'y' : 'ies' ?> found</p>
  <?php if ($rows): ?>
    <div class="grid"><?php foreach ($rows as $p) render_card($p); ?></div>
    <?php if ($pages > 1): ?>
      <nav class="pager">
        <?php if ($page > 1): ?><a href="<?= e(page_link($page - 1)) ?>">&larr; Prev</a><?php endif; ?>
        <span>Page <?= $page ?> of <?= $pages ?></span>
        <?php if ($page < $pages): ?><a href="<?= e(page_link($page + 1)) ?>">Next &rarr;</a><?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php else: ?>
    <p>No matches. Try widening your search.</p>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
