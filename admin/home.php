<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo = db();
    if (isset($_POST['reset'])) {
        $pdo->exec('DELETE FROM home_sections');
        home_seed_defaults($pdo);
        flash('Home page reset to the default layout.');
        redirect('admin/home.php');
    }
    $pdo->beginTransaction();
    $pdo->exec('DELETE FROM home_sections');
    $ins = $pdo->prepare('INSERT INTO home_sections (type, sort, enabled, config) VALUES (?,?,?,?)');
    $i = 0;
    foreach ((array)($_POST['sections'] ?? []) as $s) {
        $type = (string)($s['type'] ?? '');
        if (!isset(HOME_TYPES[$type])) continue;
        $ins->execute([$type, $i++, !empty($s['enabled']) ? 1 : 0, json_encode(home_clean_config($type, (array)($s['cfg'] ?? [])))]);
    }
    $pdo->commit();
    flash('Home page saved.');
    redirect('admin/home.php');
}

/** Print the input for one field. $base is the input-name prefix, e.g. sections[3][cfg]. */
function home_field(string $base, string $k, array $d, $value): void
{
    [$kind, $label, , $extra] = array_pad($d, 4, null);
    $n = $base . '[' . $k . ']';
    if ($kind === 'checkbox') {
        echo '<label class="check"><input type="checkbox" name="' . e($n) . '" value="1"' . ($value ? ' checked' : '') . '> ' . e($label) . '</label>';
        return;
    }
    if ($kind === 'image') {
        echo '<div class="full">'; render_image_field($n, (string)$value, $label); echo '</div>';
        return;
    }
    if ($kind === 'repeater') {
        echo '<div class="full rep" data-name="' . e($n) . '"><span class="imf-label">' . e($label) . '</span><div class="rep-rows">';
        foreach ((array)$value as $r => $row) home_rep_row($n . '[' . $r . ']', $extra, (array)$row);
        echo '</div><template class="rep-tpl">'; home_rep_row($n . '[__R__]', $extra, []);
        echo '</template><button type="button" class="btn btn-sm btn-ghost rep-add">' . e($d[4] ?? '+ Add') . '</button></div>';
        return;
    }
    $full = in_array($kind, ['textarea', 'code'], true) ? ' class="full"' : '';
    echo '<label' . $full . '>' . e($label);
    if ($kind === 'textarea') echo '<textarea name="' . e($n) . '" rows="4">' . e((string)$value) . '</textarea>';
    elseif ($kind === 'code') echo '<textarea name="' . e($n) . '" rows="6" style="font-family:monospace">' . e((string)$value) . '</textarea>';
    elseif ($kind === 'select') {
        echo '<select name="' . e($n) . '">';
        foreach ($extra as $ov => $ol) echo '<option value="' . e($ov) . '"' . ((string)$value === (string)$ov ? ' selected' : '') . '>' . e($ol) . '</option>';
        echo '</select>';
    } elseif ($kind === 'number') echo '<input type="number" name="' . e($n) . '" value="' . e((string)$value) . '" min="' . (int)$extra['min'] . '" max="' . (int)$extra['max'] . '">';
    else echo '<input type="' . ($kind === 'url' ? 'text' : 'text') . '" name="' . e($n) . '" value="' . e((string)$value) . '"' . ($kind === 'url' ? ' placeholder="contact.php or https://..."' : '') . '>';
    echo '</label>';
}
function home_rep_row(string $base, array $sub, array $vals): void
{
    echo '<div class="rep-row">';
    foreach ($sub as $sk => $sd) home_field($base, $sk, $sd, $vals[$sk] ?? '');
    echo '<button type="button" class="btn btn-sm btn-ghost rep-del" aria-label="Remove">Remove</button></div>';
}
function home_card(string $idx, string $type, bool $enabled, array $cfg): void
{
    [$label, $desc, $fields] = HOME_TYPES[$type];
    $base = 'sections[' . $idx . ']';
    ?>
    <div class="hs-card" data-type="<?= e($type) ?>">
      <div class="hs-head">
        <span class="hs-grip" title="Drag to reorder" aria-hidden="true">&#8942;&#8942;</span>
        <strong class="hs-title"><?= e($label) ?></strong>
        <label class="check hs-on"><input type="checkbox" name="<?= $base ?>[enabled]" value="1" <?= $enabled ? 'checked' : '' ?>> Show</label>
        <button type="button" class="btn btn-sm btn-ghost hs-up" aria-label="Move up">&uarr;</button>
        <button type="button" class="btn btn-sm btn-ghost hs-down" aria-label="Move down">&darr;</button>
        <button type="button" class="btn btn-sm btn-ghost hs-toggle" aria-expanded="false">Edit</button>
        <button type="button" class="btn btn-sm btn-ghost hs-del">Delete</button>
      </div>
      <input type="hidden" name="<?= $base ?>[type]" value="<?= e($type) ?>">
      <div class="hs-body" hidden>
        <p class="small muted"><?= e($desc) ?></p>
        <div class="form-grid">
          <?php foreach ($fields as $k => $d) home_field($base . '[cfg]', $k, $d, $cfg[$k] ?? $d[2]); ?>
        </div>
      </div>
    </div>
    <?php
}

$rows = db()->query('SELECT * FROM home_sections ORDER BY sort, id')->fetchAll();
admin_head('Home page');
?>
<p class="muted">Build your home page from sections. Drag, or use the arrows, to reorder. Use <strong>Show</strong> to hide a section without losing it.</p>
<form method="post" id="home-form" class="panel">
  <?= csrf_field() ?>
  <div id="hs-list">
    <?php foreach ($rows as $i => $r) if (isset(HOME_TYPES[$r['type']])) home_card((string)$i, $r['type'], (bool)$r['enabled'], home_config($r)); ?>
  </div>
  <p class="muted" id="hs-empty" <?= $rows ? 'hidden' : '' ?>>No sections yet. Add one below.</p>

  <div class="hs-add">
    <label>Add a section
      <select id="hs-type"><?php foreach (HOME_TYPES as $k => $t) echo '<option value="' . e($k) . '">' . e($t[0]) . '</option>'; ?></select>
    </label>
    <button type="button" class="btn btn-ghost" id="hs-add">Add section</button>
  </div>

  <?php foreach (HOME_TYPES as $k => $t): ?>
    <template id="hs-tpl-<?= e($k) ?>"><?php home_card('__I__', $k, true, home_defaults($k)); ?></template>
  <?php endforeach; ?>

  <p><button class="btn" type="submit">Save home page</button>
  <a class="btn btn-ghost" href="<?= url() ?>" target="_blank">View site</a></p>
</form>
<form method="post" onsubmit="return confirm('Replace your home page with the default layout?')">
  <?= csrf_field() ?><button class="btn btn-ghost btn-sm" name="reset" value="1">Reset to default layout</button>
</form>
<script src="<?= url('assets/js/admin-home.js') ?>"></script>
<?php admin_foot(); ?>
