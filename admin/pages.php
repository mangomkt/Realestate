<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete' && $id) {
        db()->prepare("DELETE FROM nav_items WHERE type = 'page' AND target = ?")->execute([(string)$id]);
        db()->prepare('DELETE FROM pages WHERE id = ?')->execute([$id]);
        flash('Page deleted (and removed from the menus).');
    } elseif (($_POST['action'] ?? '') === 'toggle' && $id) {
        db()->prepare("UPDATE pages SET status = CASE status WHEN 'published' THEN 'draft' ELSE 'published' END WHERE id = ?")->execute([$id]);
    }
    redirect('admin/pages.php');
}

$rows = db()->query('SELECT * FROM pages ORDER BY title COLLATE NOCASE')->fetchAll();
$inNav = [];
foreach (db()->query("SELECT target, location FROM nav_items WHERE type = 'page'") as $n) $inNav[(int)$n['target']][] = $n['location'];
admin_head('Pages');
?>
<div class="toolbar"><span class="muted"><?= count($rows) ?> page<?= count($rows) === 1 ? '' : 's' ?></span><a class="btn" href="page_edit.php">+ Add page</a></div>
<div class="table-wrap"><table>
  <tr><th>Title</th><th>URL</th><th>Status</th><th>In menus</th><th>Updated</th><th></th></tr>
  <?php foreach ($rows as $p): ?>
  <tr>
    <td><strong><a href="page_edit.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a></strong></td>
    <td class="small"><code><?= e(parse_url(page_url($p['slug']), PHP_URL_PATH) . (parse_url(page_url($p['slug']), PHP_URL_QUERY) ? '?' . parse_url(page_url($p['slug']), PHP_URL_QUERY) : '')) ?></code></td>
    <td><?= $p['status'] === 'published' ? 'Published' : '<span class="muted">Draft</span>' ?></td>
    <td class="small"><?= isset($inNav[(int)$p['id']]) ? e(implode(', ', array_unique($inNav[(int)$p['id']]))) : '<span class="muted">none</span>' ?></td>
    <td class="small"><?= e($p['updated_at']) ?></td>
    <td><div class="actions">
      <a class="btn btn-sm btn-ghost" href="page_edit.php?id=<?= (int)$p['id'] ?>">Edit</a>
      <a class="btn btn-sm btn-ghost" href="<?= e(page_url($p['slug'])) ?>" target="_blank"><?= $p['status'] === 'published' ? 'View' : 'Preview' ?></a>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="toggle">
        <button class="btn btn-sm btn-ghost" type="submit"><?= $p['status'] === 'published' ? 'Unpublish' : 'Publish' ?></button></form>
      <form method="post" data-confirm="Delete this page? It will also be removed from the menus."><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="delete">
        <button class="btn btn-sm btn-danger" type="submit">Delete</button></form>
    </div></td>
  </tr>
  <?php endforeach; if (!$rows) echo '<tr><td colspan="6" class="muted">No pages yet. Try an About page, a Buyer guide or a Seller guide.</td></tr>'; ?>
</table></div>
<p class="small muted">Add pages to the header or footer under <a href="navigation.php">Navigation</a>. Pretty URLs (/p/about) can be switched on in <a href="settings.php">Settings</a>.</p>
<?php admin_foot(); ?>
