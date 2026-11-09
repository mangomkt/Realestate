<?php
require_once __DIR__ . '/_layout.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$p = ['title' => '', 'slug' => '', 'content' => '', 'meta_title' => '', 'meta_desc' => '', 'status' => 'published', 'meta_noindex' => 0, 'meta_image' => ''];
if ($id) {
    $st = db()->prepare('SELECT * FROM pages WHERE id = ?');
    $st->execute([$id]);
    $p = $st->fetch() ?: redirect('admin/pages.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f = [
        'title'      => trim($_POST['title'] ?? ''),
        'slug'       => slugify(trim($_POST['slug'] ?? '') !== '' ? (string)$_POST['slug'] : (string)($_POST['title'] ?? '')),
        'content'    => sanitize_html((string)($_POST['content'] ?? '')),
        'meta_title' => mb_substr(trim($_POST['meta_title'] ?? ''), 0, 120),
        'meta_desc'  => mb_substr(trim(preg_replace('/\s+/', ' ', $_POST['meta_desc'] ?? '')), 0, 300),
        'status'     => ($_POST['status'] ?? '') === 'draft' ? 'draft' : 'published',
        'meta_noindex' => isset($_POST['meta_noindex']) ? 1 : 0,
        'meta_image' => preg_match('#^(https?://|/)[^\s"\'<>]*$#', trim($_POST['meta_image'] ?? '')) ? trim($_POST['meta_image']) : '',
    ];
    if ($f['title'] === '') $errors[] = 'Title is required.';
    if ($f['slug'] === '') $errors[] = 'Please give the page a URL name (letters and numbers).';
    if (!$errors) {
        $dup = db()->prepare('SELECT 1 FROM pages WHERE slug = ? AND id != ?');
        $dup->execute([$f['slug'], $id]);
        if ($dup->fetch()) $errors[] = 'Another page already uses the URL name "' . $f['slug'] . '". Choose a different one.';
    }
    if (!$errors) {
        if ($id) {
            $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($f)));
            db()->prepare("UPDATE pages SET $set, updated_at = datetime('now') WHERE id = :id")->execute($f + ['id' => $id]);
        } else {
            $cols = implode(', ', array_keys($f));
            $ph = implode(', ', array_map(fn($k) => ":$k", array_keys($f)));
            db()->prepare("INSERT INTO pages ($cols) VALUES ($ph)")->execute($f);
            $id = (int)db()->lastInsertId();
            foreach (NAV_LOCATIONS as $loc => $_) {
                if (!empty($_POST['add_' . $loc])) {
                    $max = db()->prepare('SELECT COALESCE(MAX(sort), -1) + 1 FROM nav_items WHERE location = ?');
                    $max->execute([$loc]);
                    db()->prepare("INSERT INTO nav_items (location, label, type, target, sort) VALUES (?, ?, 'page', ?, ?)")
                        ->execute([$loc, mb_substr($f['title'], 0, 40), (string)$id, (int)$max->fetchColumn()]);
                }
            }
        }
        home_save_post($id);
        flash('Page saved.');
        redirect('admin/page_edit.php?id=' . $id);
    }
    $p = $f + ['id' => $id];   // $f['content'] is already sanitized, so it is safe to show again
}

$navLocs = [];
if ($id) {
    $q = db()->prepare("SELECT DISTINCT location FROM nav_items WHERE type = 'page' AND target = ?");
    $q->execute([(string)$id]);
    $navLocs = $q->fetchAll(PDO::FETCH_COLUMN);
}
admin_head($id ? 'Edit page' : 'Add page');
foreach ($errors as $er) echo '<div class="flash flash-err">' . e($er) . '</div>';
?>
<form method="post" class="panel" id="page-form" data-upload="<?= e(url('admin/upload.php')) ?>" data-csrf="<?= e(csrf_token()) ?>">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <div class="form-grid">
    <label class="full">Title<input name="title" id="p-title" required value="<?= e($p['title']) ?>" placeholder="About us"></label>
    <label>URL name<input name="slug" id="p-slug" value="<?= e($p['slug']) ?>" placeholder="about-us" pattern="[a-z0-9\-]*"></label>
    <label>Status<select name="status">
      <option value="published"<?= $p['status'] === 'published' ? ' selected' : '' ?>>Published</option>
      <option value="draft"<?= $p['status'] === 'draft' ? ' selected' : '' ?>>Draft (only you can see it)</option>
    </select></label>
  </div>

  <label for="ed-area">Content</label>
  <div class="editor" id="editor">
    <div class="ed-toolbar" role="toolbar" aria-label="Formatting">
      <button type="button" data-cmd="bold" title="Bold (Ctrl+B)"><b>B</b></button>
      <button type="button" data-cmd="italic" title="Italic (Ctrl+I)"><i>I</i></button>
      <span class="sep"></span>
      <button type="button" data-block="h2" title="Heading">H2</button>
      <button type="button" data-block="h3" title="Sub-heading">H3</button>
      <button type="button" data-block="p" title="Normal paragraph">&para;</button>
      <button type="button" data-block="blockquote" title="Quote">&ldquo;</button>
      <span class="sep"></span>
      <button type="button" data-cmd="insertUnorderedList" title="Bullet list">&bull; List</button>
      <button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
      <span class="sep"></span>
      <button type="button" data-act="link" title="Link">Link</button>
      <button type="button" data-cmd="unlink" title="Remove link">Unlink</button>
      <button type="button" data-act="button" title="Make the selected text a button link">Button</button>
      <span class="sep"></span>
      <button type="button" data-act="image" title="Upload and insert an image">Image</button>
      <button type="button" data-act="video" title="Embed a YouTube or Vimeo video">Video</button>
      <button type="button" data-cmd="insertHorizontalRule" title="Divider line">&mdash;</button>
      <span class="sep"></span>
      <button type="button" data-act="source" title="Edit the HTML directly" aria-pressed="false">&lt;/&gt;</button>
    </div>
    <div class="ed-area cms" id="ed-area" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Page content"></div>
    <textarea name="content" id="ed-source" class="ed-source" rows="16" hidden spellcheck="false"><?= e($p['content']) ?></textarea>
    <input type="file" id="ed-file" accept="image/jpeg,image/png,image/webp" hidden>
    <div class="small muted" id="ed-status" role="status">Tip: to put a button on the page, type its text, select it and click Button, then add the link.</div>
  </div>

  <h2>Sections</h2>
  <p class="small muted">Add the same building blocks as the home page (hero, listings, call to action, photo + text, team and more) above or below your text. Drag, or use the arrows, to reorder.</p>
  <?php home_builder_ui($id ?: -1); ?>

  <h2>Search engines (SEO)</h2>
  <div class="form-grid">
    <label class="full">Page title for Google <span class="muted" style="font-weight:400">(optional, ~60 characters)</span>
      <input name="meta_title" id="p-mt" maxlength="120" value="<?= e($p['meta_title']) ?>" placeholder="Defaults to the page title"></label>
    <label class="full">Meta description <span class="muted" style="font-weight:400">(optional, ~155 characters)</span>
      <textarea name="meta_desc" id="p-md" rows="2" maxlength="300" placeholder="Defaults to the start of the page text"><?= e($p['meta_desc']) ?></textarea>
      <span class="small muted"><span id="md-count">0</span> characters</span></label>
  </div>

  <?php render_image_field('meta_image', (string)$p['meta_image'], 'Social share image (optional)', 'Used when this page is shared. Defaults to the site-wide share image.'); ?>
  <label class="check"><input type="checkbox" name="meta_noindex" value="1" <?= !empty($p['meta_noindex']) ? 'checked' : '' ?>> Hide this page from search engines</label>

  <?php if (!$id): ?>
    <h2>Menus</h2>
    <label class="check"><input type="checkbox" name="add_header" value="1"> Add to the header menu</label>
    <label class="check"><input type="checkbox" name="add_footer" value="1"> Add to the footer links</label>
  <?php else: ?>
    <p class="small muted">Menus: <?= $navLocs ? e(implode(' and ', array_map(fn($l) => NAV_LOCATIONS[$l] ?? $l, $navLocs))) : 'not in any menu' ?>. <a href="navigation.php">Edit navigation</a></p>
  <?php endif; ?>

  <div class="actions">
    <button class="btn" type="submit">Save page</button>
    <?php if ($id): ?><a class="btn btn-ghost" href="<?= e(page_url($p['slug'])) ?>" target="_blank">View page</a><?php endif; ?>
    <a class="btn btn-ghost" href="pages.php">Back to pages</a>
  </div>
</form>
<script src="<?= url('assets/js/admin-editor.js') ?>"></script>
<?php admin_foot(); ?>
