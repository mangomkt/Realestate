<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = '';
$featured = db()->query("SELECT * FROM properties WHERE featured = 1 AND status != 'sold' ORDER BY created_at DESC LIMIT 6")->fetchAll();
$latest = db()->query("SELECT * FROM properties WHERE status != 'sold' ORDER BY created_at DESC LIMIT 6")->fetchAll();
$cities = db()->query("SELECT DISTINCT city FROM properties WHERE city != '' ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="wrap">
    <h1><?= e(setting('hero_title', 'Find a home you love')) ?></h1>
    <p><?= e(setting('tagline', 'Local expertise. Honest advice.')) ?></p>
    <form class="search-bar" action="<?= url('listings.php') ?>" method="get">
      <input type="search" name="q" placeholder="City, address or MLS&reg; #" list="cities">
      <datalist id="cities"><?php foreach ($cities as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
      <select name="status">
        <option value="">Buy or rent</option>
        <?php foreach (STATUSES as $k => $v) echo '<option value="' . e($k) . '">' . e($v) . '</option>'; ?>
      </select>
      <select name="beds">
        <option value="">Beds</option>
        <?php for ($i = 1; $i <= 5; $i++) echo "<option value=\"$i\">$i+</option>"; ?>
      </select>
      <button class="btn" type="submit">Search</button>
    </form>
    <?php if (trim(setting('idx_embed')) !== ''): ?>
      <p class="hero-link"><a href="<?= url('idx.php') ?>">Search every MLS&reg; listing &rarr;</a></p>
    <?php endif; ?>
  </div>
</section>

<?php if ($featured): ?>
<section class="wrap section">
  <h2>Featured properties</h2>
  <div class="grid"><?php foreach ($featured as $p) render_card($p); ?></div>
</section>
<?php endif; ?>

<section class="wrap section">
  <div class="section-head"><h2>Latest listings</h2><a href="<?= url('listings.php') ?>">View all &rarr;</a></div>
  <?php if ($latest): ?>
    <div class="grid"><?php foreach ($latest as $p) render_card($p); ?></div>
  <?php else: ?>
    <p class="muted">No listings yet. Add your first property in the admin area.</p>
  <?php endif; ?>
</section>

<section class="cta">
  <div class="wrap">
    <h2>Thinking of buying or selling?</h2>
    <p>Get in touch for a no-pressure conversation.</p>
    <a class="btn btn-light" href="<?= url('contact.php') ?>">Contact me</a>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
