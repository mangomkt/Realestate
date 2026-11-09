<?php
require_once __DIR__ . '/_layout.php';
$c = fn(string $sql) => (int)db()->query($sql)->fetchColumn();
$leads = db()->query('SELECT l.*, p.title AS ptitle FROM leads l LEFT JOIN properties p ON p.id = l.property_id ORDER BY l.id DESC LIMIT 8')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dismiss_setup'])) { csrf_check(); save_setting('setup_dismissed', '1'); redirect('admin/'); }
$steps = [
    ['Name your site and add contact details', setting('site_name', 'Your Name Realty') !== 'Your Name Realty' && setting('contact_email') !== '', 'setup.php'],
    ['Upload your logo and favicon', setting('logo') !== '' && setting('favicon') !== '', 'settings.php'],
    ['Pick your brand colors', db()->query("SELECT 1 FROM settings WHERE k LIKE 'theme\_%' ESCAPE '\\' LIMIT 1")->fetchColumn() !== false, 'theme.php'],
    ['Arrange your home page', $c("SELECT COUNT(*) FROM home_sections WHERE page_id = 0 AND config != '{}'") > 0, 'home.php'],
    ['Add your first property', $c('SELECT COUNT(*) FROM properties') > 0, 'property_edit.php'],
    ['Add an About page', $c('SELECT COUNT(*) FROM pages') > 0, 'page_edit.php'],
    ['Connect IDX / MLS search (optional)', setting('idx_embed') !== '', 'settings.php'],
    ['Check your SEO defaults', setting('seo_property_title') !== '' || setting('seo_home_title') !== '', 'seo.php'],
];
$done = count(array_filter($steps, fn($s) => $s[1]));
admin_head('Dashboard');
?>
<?php if (setting('setup_dismissed') !== '1' && $done < count($steps)): ?>
<div class="panel" style="margin-bottom:24px">
  <h2>Get set up (<?= $done ?> of <?= count($steps) ?> done)</h2>
  <ul class="checklist">
    <?php foreach ($steps as [$label, $ok, $href]): ?>
      <li><?= $ok ? '&#10003;' : '&#9675;' ?> <a href="<?= e($href) ?>"><?= e($label) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <form method="post"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" name="dismiss_setup" value="1">Hide this</button></form>
</div>
<?php endif; ?>
<div class="stats">
  <div class="stat"><b><?= $c("SELECT COUNT(*) FROM properties WHERE status != 'sold'") ?></b>Active listings</div>
  <div class="stat"><b><?= $c('SELECT COUNT(*) FROM users') ?></b>Registered users</div>
  <div class="stat"><b><?= $c('SELECT COUNT(*) FROM leads') ?></b>Total leads</div>
  <div class="stat"><b><?= $c("SELECT COUNT(*) FROM leads WHERE created_at >= datetime('now','-7 days')") ?></b>Leads this week</div>
</div>
<div class="actions" style="margin-bottom:24px">
  <a class="btn" href="property_edit.php">+ Add property</a>
  <a class="btn btn-ghost" href="settings.php">Site settings</a>
</div>
<h2>Recent leads</h2>
<div class="table-wrap"><table>
  <tr><th>When</th><th>Name</th><th>Contact</th><th>Source</th><th>Property</th></tr>
  <?php foreach ($leads as $l): ?>
    <tr><td><?= e($l['created_at']) ?></td><td><?= e($l['name']) ?></td>
      <td><a href="mailto:<?= e($l['email']) ?>"><?= e($l['email']) ?></a><br><?= e($l['phone']) ?></td>
      <td><?= e($l['source']) ?></td><td><?= e($l['ptitle'] ?? '') ?></td></tr>
  <?php endforeach; if (!$leads) echo '<tr><td colspan="5" class="muted">No leads yet.</td></tr>'; ?>
</table></div>
<?php admin_foot(); ?>
