<?php
require_once __DIR__ . '/_layout.php';

$pages = db()->query('SELECT id, title, status FROM pages ORDER BY title COLLATE NOCASE')->fetchAll();
$pageIds = array_map('intval', array_column($pages, 'id'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo = db();
    $skipped = 0;
    $pdo->beginTransaction();
    foreach (NAV_LOCATIONS as $loc => $_) {
        if (empty($_POST['has'][$loc])) continue;            // that menu was not part of the form
        $pdo->prepare('DELETE FROM nav_items WHERE location = ?')->execute([$loc]);
        $ins = $pdo->prepare('INSERT INTO nav_items (location, label, type, target, sort, visible, new_tab, parent_id) VALUES (?,?,?,?,?,?,?,?)');
        $sort = 0;
        $lastTop = 0;
        foreach ((array)($_POST['items'][$loc] ?? []) as $it) {   // PHP keeps the order the rows had on screen
            if (!is_array($it)) continue;
            $depth = ($loc === 'header' && !empty($it['depth']) && $lastTop > 0) ? 1 : 0;   // one level of sub-items, header only
            $label = mb_substr(trim((string)($it['label'] ?? '')), 0, 40);
            $type = (string)($it['type'] ?? '');
            $target = null;
            if ($type === 'builtin') {
                $k = (string)($it['builtin'] ?? '');
                $target = isset(NAV_BUILTIN[$k]) ? $k : null;
                if ($label === '' && $target) $label = NAV_BUILTIN[$target][0];
            } elseif ($type === 'page') {
                $pid = (int)($it['page'] ?? 0);
                $target = in_array($pid, $pageIds, true) ? (string)$pid : null;
                if ($label === '' && $target) foreach ($pages as $pg) if ((int)$pg['id'] === $pid) $label = mb_substr($pg['title'], 0, 40);
            } elseif ($type === 'link') {
                $target = nav_clean_url((string)($it['url'] ?? ''));
            } elseif ($type === 'none' && $depth === 0) {
                $target = '';                                    // dropdown label with no link of its own
            }
            if ($target === null || $label === '') { $skipped++; if ($depth === 0) $lastTop = 0; continue; }
            $ins->execute([$loc, $label, $type, $target, $sort++, empty($it['hidden']) ? 1 : 0, empty($it['new_tab']) ? 0 : 1, $depth ? $lastTop : 0]);
            if ($depth === 0) $lastTop = (int)$pdo->lastInsertId();
        }
    }
    $pdo->commit();
    flash('Navigation saved.' . ($skipped ? " $skipped item(s) were skipped because the label or link was missing or not valid." : ''), $skipped ? 'info' : 'ok');
    redirect('admin/navigation.php');
}

$menus = [];
foreach (NAV_LOCATIONS as $loc => $_) {
    $st = db()->prepare('SELECT * FROM nav_items WHERE location = ? ORDER BY sort, id');
    $st->execute([$loc]);
    $menus[$loc] = $st->fetchAll();
}

/** One editable row. $i is a unique index (any string), $it the saved item or null for a blank template. */
function nav_row(string $loc, string $i, ?array $it): void
{
    global $pages;
    $type = $it['type'] ?? 'link';
    $n = "items[$loc][$i]";
    $sub = !empty($it['parent_id']);
    ?>
    <div class="nav-row<?= $sub ? ' is-sub' : '' ?>">
      <input type="hidden" class="nr-depth" name="<?= $n ?>[depth]" value="<?= $sub ? 1 : 0 ?>">
      <span class="grip" title="Drag to reorder" aria-hidden="true">&#8942;&#8942;</span>
      <input class="nr-label" name="<?= $n ?>[label]" value="<?= e($it['label'] ?? '') ?>" maxlength="40" placeholder="Label" aria-label="Menu label">
      <select class="nr-type" name="<?= $n ?>[type]" aria-label="Link type">
        <option value="builtin"<?= $type === 'builtin' ? ' selected' : '' ?>>Site section</option>
        <option value="page"<?= $type === 'page' ? ' selected' : '' ?>>Page</option>
        <option value="link"<?= $type === 'link' ? ' selected' : '' ?>>Custom link</option>
        <?php if ($loc === 'header'): ?><option value="none"<?= $type === 'none' ? ' selected' : '' ?>>Dropdown label (no link)</option><?php endif; ?>
      </select>
      <span class="nr-target">
        <select class="t-builtin" name="<?= $n ?>[builtin]" aria-label="Site section">
          <?php foreach (NAV_BUILTIN as $k => [$l]) echo '<option value="' . e($k) . '"' . (($it['target'] ?? '') === $k && $type === 'builtin' ? ' selected' : '') . '>' . e($l) . '</option>'; ?>
        </select>
        <select class="t-page" name="<?= $n ?>[page]" aria-label="Page">
          <?php foreach ($pages as $pg) echo '<option value="' . (int)$pg['id'] . '"' . ((string)($it['target'] ?? '') === (string)$pg['id'] && $type === 'page' ? ' selected' : '') . '>' . e($pg['title'] . ($pg['status'] === 'draft' ? ' (draft)' : '')) . '</option>'; ?>
          <?php if (!$pages) echo '<option value="">No pages yet</option>'; ?>
        </select>
        <input class="t-link" name="<?= $n ?>[url]" value="<?= e($type === 'link' ? ($it['target'] ?? '') : '') ?>" placeholder="https://… or /path" aria-label="Link address">
      </span>
      <label class="nr-chk"><input type="checkbox" name="<?= $n ?>[hidden]" value="1"<?= isset($it) && !$it['visible'] ? ' checked' : '' ?>> Hide</label>
      <label class="nr-chk"><input type="checkbox" name="<?= $n ?>[new_tab]" value="1"<?= !empty($it['new_tab']) ? ' checked' : '' ?>> New tab</label>
      <span class="nr-btns">
        <?php if ($loc === 'header'): ?><button type="button" class="mv nr-in" title="Indent: make this a sub-item of the item above" aria-label="Indent as sub-item">&#8677;</button><button type="button" class="mv nr-out" title="Outdent: make this a top-level item" aria-label="Outdent to top level">&#8676;</button><?php endif; ?>
        <button type="button" class="mv" data-dir="-1" aria-label="Move up">&uarr;</button>
        <button type="button" class="mv" data-dir="1" aria-label="Move down">&darr;</button>
        <button type="button" class="mv nr-del" aria-label="Remove item">&times;</button>
      </span>
    </div>
    <?php
}

admin_head('Navigation');
?>
<p class="muted">Choose which links appear in the header and footer, in what order. Drag the <b>&#8942;&#8942;</b> handle or use the arrows. In the header, use <b>&#8677;</b> to tuck an item under the one above it as a dropdown sub-item (pick "Dropdown label" if the parent shouldn't link anywhere). "Log in / Register" links are added automatically when visitor registration is on (see Settings).</p>
<form method="post" id="nav-form">
  <?= csrf_field() ?>
  <?php foreach (NAV_LOCATIONS as $loc => $label): ?>
    <section class="panel nav-section" data-loc="<?= e($loc) ?>" style="margin-bottom:20px">
      <h2><?= e($label) ?></h2>
      <input type="hidden" name="has[<?= e($loc) ?>]" value="1">
      <div class="nav-list" id="nav-<?= e($loc) ?>">
        <?php foreach ($menus[$loc] as $it) nav_row($loc, 'e' . (int)$it['id'], $it); ?>
      </div>
      <p class="muted small nav-empty" <?= $menus[$loc] ? 'hidden' : '' ?>>No links yet.</p>
      <button type="button" class="btn btn-sm btn-ghost nav-add" data-loc="<?= e($loc) ?>">+ Add link</button>
      <template id="tpl-<?= e($loc) ?>"><?php nav_row($loc, '__I__', null); ?></template>
    </section>
  <?php endforeach; ?>
  <div class="actions"><button class="btn" type="submit">Save navigation</button></div>
</form>
<script src="<?= url('assets/js/admin-nav.js') ?>"></script>
<?php admin_foot(); ?>
