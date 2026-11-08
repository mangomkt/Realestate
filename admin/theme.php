<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'reset') {
        db()->exec("DELETE FROM settings WHERE k LIKE 'theme\\_%' ESCAPE '\\'");
        flash('Theme reset to the defaults.');
    } else {
        foreach (THEME_DEFAULTS as $k => $_) {
            $v = strtolower(trim((string)($_POST[$k] ?? '')));
            if (valid_hex($v)) save_setting('theme_' . $k, $v);
        }
        $font = $_POST['font'] ?? 'system';
        save_setting('theme_font', isset(THEME_FONTS[$font]) ? $font : 'system');
        $radius = (string)($_POST['radius'] ?? '12');
        save_setting('theme_radius', isset(THEME_RADII[$radius]) ? $radius : '12');
        flash('Theme saved. Reload the public site to see it.');
    }
    redirect('admin/theme.php');
}

$t = theme_get();
$groups = [
    'Brand colors' => [
        'primary'   => ['Primary', 'Logo, links, badges, hero gradient'],
        'secondary' => ['Secondary', 'Dark end of the hero gradient'],
        'tertiary'  => ['Tertiary / accent', 'Highlights, hover and active menu link, "For rent" tag'],
    ],
    'Buttons' => [
        'button'      => ['Button color', 'Main call-to-action buttons'],
        'button_text' => ['Button text', 'Text on buttons'],
    ],
    'Header' => [
        'header_bg'   => ['Header background', ''],
        'header_text' => ['Menu text', ''],
    ],
    'Footer' => [
        'footer_bg'   => ['Footer background', ''],
        'footer_text' => ['Footer text', ''],
    ],
    'Page content' => [
        'text' => ['Body text', 'Headings and paragraphs'],
        'link' => ['Links', 'Text links in pages and forms'],
    ],
];
admin_head('Theme');
?>
<div class="theme-layout">
  <form method="post" class="panel" id="theme-form" data-presets="<?= e(json_encode(THEME_PRESETS)) ?>" data-defaults="<?= e(json_encode(THEME_DEFAULTS)) ?>">
    <?= csrf_field() ?>
    <h2>Presets</h2>
    <div class="actions presets" style="margin-bottom:18px">
      <?php foreach (array_keys(THEME_PRESETS) as $name): ?>
        <button type="button" class="btn btn-sm btn-ghost preset" data-name="<?= e($name) ?>"><?= e($name) ?></button>
      <?php endforeach; ?>
    </div>

    <?php foreach ($groups as $title => $fields): ?>
      <h2><?= e($title) ?></h2>
      <div class="color-grid">
        <?php foreach ($fields as $key => [$label, $hint]): ?>
          <div class="color-field">
            <label for="c-<?= $key ?>"><?= e($label) ?></label>
            <div class="cin">
              <input type="color" id="c-<?= $key ?>" name="<?= $key ?>" value="<?= e($t[$key]) ?>">
              <input type="text" class="hex" value="<?= e($t[$key]) ?>" maxlength="7" spellcheck="false" aria-label="<?= e($label) ?> hex value" data-for="<?= $key ?>">
            </div>
            <?php if ($hint): ?><small class="muted"><?= e($hint) ?></small><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

    <h2>Style</h2>
    <div class="form-grid">
      <label>Font<select name="font" id="t-font"><?php foreach (THEME_FONTS as $k => [$l]) echo '<option value="' . e($k) . '"' . ($t['font'] === $k ? ' selected' : '') . '>' . e($l) . '</option>'; ?></select></label>
      <label>Corners<select name="radius" id="t-radius"><?php foreach (THEME_RADII as $k => [$l]) echo '<option value="' . e($k) . '"' . ($t['radius'] === (string)$k ? ' selected' : '') . '>' . e($l) . '</option>'; ?></select></label>
    </div>

    <div id="contrast-warnings" class="flash flash-info" hidden></div>

    <div class="actions">
      <button class="btn" type="submit">Save theme</button>
      <button class="btn btn-ghost" type="submit" name="action" value="reset" formnovalidate onclick="return confirm('Reset all theme colors to the defaults?')">Reset to defaults</button>
    </div>
  </form>

  <aside class="theme-preview-wrap">
    <h2>Live preview</h2>
    <div id="preview" class="pv" data-radii="<?= e(json_encode(array_map(fn($r) => array_slice($r, 1), THEME_RADII))) ?>" data-fonts="<?= e(json_encode(array_map(fn($f) => $f[1], THEME_FONTS))) ?>">
      <div class="pv-header"><span class="pv-logo">Your Name Realty</span><span class="pv-nav"><span class="on">Home</span><span>Listings</span><span>Contact</span><span class="pv-btn pv-btn-sm">Register</span></span></div>
      <div class="pv-hero"><h3>Find a home you love</h3><p>Local expertise. Honest advice.</p><span class="pv-search">City, address or MLS&reg; #</span></div>
      <div class="pv-body">
        <div class="pv-card">
          <div class="pv-img"><span class="pv-badge">For sale</span></div>
          <div class="pv-card-body"><b>$899,000</b><div>Bright family home near the lake</div><small>4 bd &middot; 3 ba &middot; 2,400 sqft</small>
            <p><a href="#" onclick="return false">Read more</a></p><span class="pv-btn">Request info</span></div>
        </div>
        <div class="pv-card pv-rent-card"><div class="pv-img"><span class="pv-badge pv-badge-rent">For rent</span></div><div class="pv-card-body"><b>$2,450/mo</b><div>Modern 2-bed condo downtown</div></div></div>
      </div>
      <div class="pv-footer"><b>Your Name Realty</b><br><span>Quick links &middot; Follow &middot; Disclaimer</span></div>
    </div>
  </aside>
</div>
<script src="<?= url('assets/js/admin-theme.js') ?>"></script>
<?php admin_foot(); ?>
