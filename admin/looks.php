<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (look_apply((string)($_POST['look'] ?? ''), !empty($_POST['with_home']))) flash('Look applied. Your contact details, logo, listings and pages were not changed.');
    redirect('admin/looks.php');
}

admin_head('Starter looks');
?>
<p class="muted">Pick a look to restyle the whole site in one click: colors, fonts, corners, card, header and footer styles, and (optionally) a matching home page layout. You can fine-tune anything afterwards in Theme and Home page. Your logo, contact details, listings and pages stay as they are.</p>
<div class="looks">
<?php foreach (LOOKS as $k => $l): $p = look_palette($l); $f = THEME_FONTS[$l['font']]; ?>
  <form method="post" class="look panel<?= setting('look') === $k ? ' look-on' : '' ?>">
    <?= csrf_field() ?><input type="hidden" name="look" value="<?= e($k) ?>">
    <div class="look-prev" style="background:<?= e($p['bg']) ?>;border-color:<?= e($p['primary']) ?>40" aria-hidden="true">
      <div style="background:<?= e($p['header_bg']) ?>;color:<?= e($p['header_text']) ?>;padding:6px 10px;display:flex;justify-content:space-between;font:700 12px <?= e($f[2]) ?>"><span>Your Name</span><span style="font-weight:400">Home &nbsp; Listings</span></div>
      <div style="background:linear-gradient(135deg,<?= e($p['secondary']) ?>,<?= e($p['primary']) ?>);color:#fff;padding:14px 10px"><div style="font:700 15px <?= e($f[2]) ?>">Find a home you love</div>
        <span style="display:inline-block;margin-top:6px;background:<?= e($p['button']) ?>;color:<?= e($p['button_text']) ?>;padding:3px 10px;font:600 11px <?= e($f[1]) ?>;border-radius:<?= (int)THEME_RADII[$l['radius']][2] ?>px">Search</span></div>
      <div style="display:flex;gap:6px;padding:8px"><?php for ($i = 0; $i < 3; $i++): ?><span style="flex:1;height:34px;background:<?= e($p['surface']) ?>;border:1px solid <?= e($p['primary']) ?>30;border-radius:<?= (int)THEME_RADII[$l['radius']][1] / 2 ?>px;border-top:10px solid <?= e($p['tertiary']) ?>"></span><?php endfor; ?></div>
    </div>
    <h2 style="margin-top:12px"><?= e($l['name']) ?><?= setting('look') === $k ? ' <small class="muted">(current)</small>' : '' ?></h2>
    <p class="small muted"><?= e($l['desc']) ?></p>
    <p class="small muted">Fonts: <?= e($f[0]) ?></p>
    <label class="check"><input type="checkbox" name="with_home" value="1" checked> Also set up the matching home page layout</label>
    <button class="btn btn-sm" type="submit" onclick="return confirm('Apply this look? Your colors, fonts and layout styles will change<?= '' ?>.')">Use this look</button>
  </form>
<?php endforeach; ?>
</div>
<?php admin_foot(); ?>
