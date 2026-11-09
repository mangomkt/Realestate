<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Listings';

$where = ['1=1'];
$args = [];
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $where[] = '(city LIKE :q OR address LIKE :q OR mls_number LIKE :q OR title LIKE :q OR postal LIKE :q)';
    $args[':q'] = '%' . $q . '%';
}
$status = $_GET['status'] ?? '';
if (isset(STATUSES[$status])) { $where[] = 'status = :status'; $args[':status'] = $status; }
$type = $_GET['type'] ?? '';
if (isset(TYPES[$type])) { $where[] = 'type = :type'; $args[':type'] = $type; }
foreach (['min' => ['price >= :min', 'min'], 'max' => ['price <= :max', 'max']] as $k => [$sql]) {
    if (($_GET[$k] ?? '') !== '' && ctype_digit((string)$_GET[$k])) { $where[] = $sql; $args[':' . $k] = (int)$_GET[$k]; }
}
if (($_GET['beds'] ?? '') !== '' && ctype_digit((string)$_GET['beds'])) { $where[] = 'bedrooms >= :beds'; $args[':beds'] = (int)$_GET['beds']; }
if (($_GET['baths'] ?? '') !== '' && ctype_digit((string)$_GET['baths'])) { $where[] = 'bathrooms >= :baths'; $args[':baths'] = (int)$_GET['baths']; }

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
