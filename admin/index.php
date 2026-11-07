<?php
require_once __DIR__ . '/_layout.php';
$c = fn(string $sql) => (int)db()->query($sql)->fetchColumn();
$leads = db()->query('SELECT l.*, p.title AS ptitle FROM leads l LEFT JOIN properties p ON p.id = l.property_id ORDER BY l.id DESC LIMIT 8')->fetchAll();
admin_head('Dashboard');
?>
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
