<?php
require_once __DIR__ . '/_layout.php';

$sql = 'SELECT l.*, p.title AS ptitle FROM leads l LEFT JOIN properties p ON p.id = l.property_id ORDER BY l.id DESC';

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leads-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Name', 'Email', 'Phone', 'Source', 'Property', 'Message']);
    // Prefix risky leading characters so spreadsheets don't treat cells as formulas.
    $safe = fn($v) => preg_match('/^[=+\-@\t\r]/', (string)$v) ? "'" . $v : $v;
    foreach (db()->query($sql) as $l) {
        fputcsv($out, array_map($safe, [$l['created_at'], $l['name'], $l['email'], $l['phone'], $l['source'], $l['ptitle'], $l['message']]));
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    db()->prepare('DELETE FROM leads WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
    flash('Lead deleted.');
    redirect('admin/leads.php');
}

$rows = db()->query($sql)->fetchAll();
admin_head('Leads');
?>
<div class="toolbar"><span class="muted"><?= count($rows) ?> total</span><a class="btn" href="?export=csv">Export CSV</a></div>
<div class="table-wrap"><table>
  <tr><th>When</th><th>Name</th><th>Contact</th><th>Source</th><th>Property / message</th><th></th></tr>
  <?php foreach ($rows as $l): ?>
  <tr>
    <td><?= e($l['created_at']) ?></td>
    <td><?= e($l['name']) ?></td>
    <td><a href="mailto:<?= e($l['email']) ?>"><?= e($l['email']) ?></a><br><?= e($l['phone']) ?></td>
    <td><?= e($l['source']) ?></td>
    <td><?php if ($l['ptitle']): ?><strong><?= e($l['ptitle']) ?></strong><br><?php endif; ?><?= nl2br(e($l['message'])) ?></td>
    <td><form method="post" data-confirm="Delete this lead?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$l['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form></td>
  </tr>
  <?php endforeach; if (!$rows) echo '<tr><td colspan="6" class="muted">No leads yet.</td></tr>'; ?>
</table></div>
<?php admin_foot(); ?>
