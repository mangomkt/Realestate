<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if ($act === 'delete') {
        $st = db()->prepare('SELECT video_file FROM properties WHERE id = ?'); $st->execute([$id]);
        $vf = $st->fetchColumn();
        foreach (property_images($id) as $im) delete_upload($im['filename']);
        delete_upload($vf ?: null);
        db()->prepare('DELETE FROM properties WHERE id = ?')->execute([$id]);
        flash('Property deleted.');
    } elseif ($act === 'feature') {
        db()->prepare('UPDATE properties SET featured = 1 - featured WHERE id = ?')->execute([$id]);
    }
    redirect('admin/properties.php');
}

$rows = db()->query('SELECT * FROM properties ORDER BY created_at DESC, id DESC')->fetchAll();
admin_head('Properties');
?>
<div class="toolbar"><span class="muted"><?= count($rows) ?> total</span><a class="btn" href="property_edit.php">+ Add property</a></div>
<div class="table-wrap"><table>
  <tr><th></th><th>Title</th><th>Price</th><th>Status</th><th>Featured</th><th></th></tr>
  <?php foreach ($rows as $p): ?>
  <tr>
    <td style="width:80px"><img src="<?= e(cover_image((int)$p['id'])) ?>" alt="" style="width:72px;height:52px;object-fit:cover;border-radius:6px"></td>
    <td><strong><?= e($p['title']) ?></strong><br><span class="muted small"><?= e($p['address'] . ' ' . $p['city']) ?></span></td>
    <td><?= money($p['price']) ?></td>
    <td><?= e(STATUSES[$p['status']] ?? $p['status']) ?></td>
    <td>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="feature">
        <button class="btn btn-sm btn-ghost" type="submit"><?= $p['featured'] ? '★ Yes' : '☆ No' ?></button></form>
    </td>
    <td><div class="actions">
      <a class="btn btn-sm btn-ghost" href="property_edit.php?id=<?= (int)$p['id'] ?>">Edit</a>
      <a class="btn btn-sm btn-ghost" href="<?= url('property.php?id=' . (int)$p['id']) ?>" target="_blank">View</a>
      <form method="post" data-confirm="Delete this property and its photos?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="delete">
        <button class="btn btn-sm btn-danger" type="submit">Delete</button></form>
    </div></td>
  </tr>
  <?php endforeach; if (!$rows) echo '<tr><td colspan="6" class="muted">No properties yet.</td></tr>'; ?>
</table></div>
<?php admin_foot(); ?>
