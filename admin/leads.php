<?php
require_once __DIR__ . '/_layout.php';

$sql = 'SELECT l.*, p.title AS ptitle FROM leads l LEFT JOIN properties p ON p.id = l.property_id ORDER BY l.id DESC';

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leads-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Name', 'Email', 'Phone', 'Source', 'Property', 'Message', 'Status', 'Notes', 'Showing']);
    // Prefix risky leading characters so spreadsheets don't treat cells as formulas.
    $safe = fn($v) => preg_match('/^[=+\-@\t\r]/', (string)$v) ? "'" . $v : $v;
    foreach (db()->query($sql) as $l) {
        fputcsv($out, array_map($safe, [$l['created_at'], $l['name'], $l['email'], $l['phone'], $l['source'], $l['ptitle'], $l['message'], LEAD_STATUSES[$l['status']] ?? $l['status'], $l['notes'], $l['showing_at']]));
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    if (isset($_POST['save'])) {
        $st = isset(LEAD_STATUSES[$_POST['status'] ?? '']) ? $_POST['status'] : 'new';
        db()->prepare('UPDATE leads SET status = ?, notes = ? WHERE id = ?')->execute([$st, mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 4000), $id]);
        flash('Lead updated.');
    } else {
        db()->prepare('DELETE FROM leads WHERE id = ?')->execute([$id]);
        flash('Lead deleted.');
    }
    redirect('admin/leads.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

$rows = db()->query($sql)->fetchAll();
$counts = array_count_values(array_column($rows, 'status'));
$f = $_GET['status'] ?? '';
if (isset(LEAD_STATUSES[$f])) $rows = array_values(array_filter($rows, fn($r) => $r['status'] === $f));
admin_head('Leads');
?>
<div class="actions" style="margin-bottom:12px">
  <a class="btn btn-sm <?= $f === '' ? '' : 'btn-ghost' ?>" href="leads.php">All</a>
  <?php foreach (LEAD_STATUSES as $k => $l): ?><a class="btn btn-sm <?= $f === $k ? '' : 'btn-ghost' ?>" href="?status=<?= e($k) ?>"><?= e($l) ?> (<?= (int)($counts[$k] ?? 0) ?>)</a><?php endforeach; ?>
</div>
<div class="toolbar"><span class="muted"><?= count($rows) ?> shown</span><a class="btn" href="?export=csv">Export CSV</a></div>
<div class="table-wrap"><table>
  <tr><th>When</th><th>Name</th><th>Contact</th><th>Source</th><th>Property / message</th><th>Follow-up</th></tr>
  <?php foreach ($rows as $l): ?>
  <tr>
    <td><?= e($l['created_at']) ?></td>
    <td><?= e($l['name']) ?></td>
    <td><a href="mailto:<?= e($l['email']) ?>"><?= e($l['email']) ?></a><br><?= e($l['phone']) ?></td>
    <td><?= e($l['source']) ?></td>
    <td><?php if ($l['ptitle']): ?><strong><?= e($l['ptitle']) ?></strong><br><?php endif; ?><?= nl2br(e($l['message'])) ?>
      <?php if ($l['showing_at']): ?><br><strong>Showing requested: <?= e($l['showing_at']) ?></strong><?php endif; ?></td>
    <td><form method="post" class="lead-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
      <select name="status" aria-label="Status"><?php foreach (LEAD_STATUSES as $k => $lb) echo '<option value="' . e($k) . '"' . ($l['status'] === $k ? ' selected' : '') . '>' . e($lb) . '</option>'; ?></select>
      <textarea name="notes" rows="2" placeholder="Private notes" aria-label="Notes"><?= e($l['notes']) ?></textarea>
      <button class="btn btn-sm" name="save" value="1">Save</button></form>
      <form method="post" data-confirm="Delete this lead?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$l['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form></td>
  </tr>
  <?php endforeach; if (!$rows) echo '<tr><td colspan="6" class="muted">No leads yet.</td></tr>'; ?>
</table></div>
<?php admin_foot(); ?>
