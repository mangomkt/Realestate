<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'reset') {
        db()->exec("DELETE FROM settings WHERE k LIKE 'theme\\_%' ESCAPE '\\'");
        flash('Theme reset to the defaults.');
    } else {
        foreach (THEME_DEFAULTS as $k => $_) {
            foreach (['' => 'theme_', 'dark_' => 'theme_dark_'] as $prefix => $store) {
                $v = strtolower(trim((string)($_POST[$prefix . $k] ?? '')));
                if (valid_hex($v)) save_setting($store . $k, $v);
            }
        }
        $font = $_POST['font'] ?? 'system';
        save_setting('theme_font', isset(THEME_FONTS[$font]) ? $font : 'system');
        $radius = (string)($_POST['radius'] ?? '12');
        save_setting('theme_radius', isset(THEME_RADII[$radius]) ? $radius : '12');
        $mode = $_POST['mode'] ?? 'light';
        save_setting('theme_mode', isset(THEME_MODES[$mode]) ? $mode : 'light');
        foreach (THEME_VARIANTS as $vk => [, $vo]) {
            $vv = (string)($_POST['style_' . $vk] ?? '');
            save_setting('style_' . $vk, isset($vo[$vv]) ? $vv : array_key_first($vo));
        }
        save_setting('theme_toggle', isset($_POST['toggle']) ? '1' : '0');
        save_setting('theme_dark_auto', isset($_POST['dark_auto']) ? '1' : '0');
        flash('Theme saved.');
    }
    redirect('admin/theme.php');
}

$ts = theme_settings();
$pal = ['' => theme_palette('light'), 'dark_' => theme_palette('dark')];
$groups = [
    'Brand colors' => [
        'primary'   => ['Primary', 'Logo, prices, badges, hero gradient'],
        'secondary' => ['Secondary', 'Dark end of the hero gradient'],
        'tertiary'  => ['Tertiary / accent', 'Highlights, card hover, active menu link'],
    ],
    'Buttons' => [
        'button'      => ['Button color', 'Main call-to-action buttons'],
        'button_text' => ['Button text', 'Text on buttons'],
    ],
    'Header' => ['header_bg' => ['Header background', ''], 'header_text' => ['Menu text', '']],
    'Footer' => ['footer_bg' => ['Footer background', ''], 'footer_text' => ['Footer text', '']],
    'Page content' => [
        'bg'      => ['Page background', ''],
        'surface' => ['Cards & panels', 'Property cards, forms, panels'],
        'text'    => ['Body text', 'Headings and paragraphs'],
        'link'    => ['Links', 'Text links in pages and forms'],
    ],
];
function color_groups(array $groups, string $prefix, array $values): void
{
    foreach ($groups as $title => $fields) {
        echo '<h3 class="grp">' . e($title) . '</h3><div class="color-grid">';
        foreach ($fields as $key => [$label, $hint]) {
            $n = $prefix . $key; ?>
            <div class="color-field">
              <label for="c-<?= $n ?>"><?= e($label) ?></label>
              <div class="cin">
                <input type="color" id="c-<?= $n ?>" name="<?= $n ?>" value="<?= e($values[$key]) ?>">
                <input type="text" class="hex" value="<?= e($values[$key]) ?>" maxlength="7" spellcheck="false" aria-label="<?= e($label) ?> hex value" data-for="<?= $n ?>">
              </div>
              <?php if ($hint): ?><small class="muted"><?= e($hint) ?></small><?php endif; ?>
            </div>
        <?php }
        echo '</div>';
    }
}
admin_head('Theme');
?>
<div class="theme-layout">
  <form method="post" class="panel" id="theme-form" data-presets="<?= e(json_encode(theme_presets())) ?>" data-defaults="<?= e(json_encode(THEME_DEFAULTS)) ?>">
    <?= csrf_field() ?>
    <h2>Appearance</h2>
    <div class="form-grid">
      <label>Default mode for visitors
        <select name="mode"><?php foreach (THEME_MODES as $k => $l) echo '<option value="' . e($k) . '"' . ($ts['mode'] === $k ? ' selected' : '') . '>' . e($l) . '</option>'; ?></select>
      </label>
      <label class="check" style="align-self:end"><input type="checkbox" name="toggle" value="1" <?= $ts['toggle'] ? 'checked' : '' ?>> Show a light/dark switch in the header</label>
    </div>

    <h2>Presets</h2>
    <p class="small muted" style="margin-top:-6px">Presets set the light colors. The dark colors follow automatically unless you turn that off.</p>
    <div class="actions presets" style="margin-bottom:18px">
      <?php foreach (array_keys(theme_presets()) as $name): ?>
        <button type="button" class="btn btn-sm btn-ghost preset" data-name="<?= e($name) ?>"><?= e($name) ?></button>
      <?php endforeach; ?>
    </div>

    <div class="tabs" role="tablist">
      <button type="button" role="tab" class="tab on" data-mode="light" aria-selected="true">&#9728; Light colors</button>
      <button type="button" role="tab" class="tab" data-mode="dark" aria-selected="false">&#9790; Dark colors</button>
    </div>

    <div class="tabpanel" data-mode="light"><?php color_groups($groups, '', $pal['']); ?></div>
    <div class="tabpanel" data-mode="dark" hidden>
      <label class="check"><input type="checkbox" name="dark_auto" id="dark-auto" value="1" <?= $ts['dark_auto'] ? 'checked' : '' ?>> Generate the dark colors automatically from the light colors</label>
      <?php color_groups($groups, 'dark_', $pal['dark_']); ?>
    </div>

    <h2>Style</h2>
    <div class="form-grid">
      <label>Font<select name="font" id="t-font"><?php foreach (THEME_FONTS as $k => [$l]) echo '<option value="' . e($k) . '"' . ($ts['font'] === $k ? ' selected' : '') . '>' . e($l) . '</option>'; ?></select></label>
      <label>Corners<select name="radius" id="t-radius"><?php foreach (THEME_RADII as $k => [$l]) echo '<option value="' . e((string)$k) . '"' . ($ts['radius'] === (string)$k ? ' selected' : '') . '>' . e($l) . '</option>'; ?></select></label>
    </div>

    <h2>Layout</h2>
    <div class="form-grid">
      <?php foreach (THEME_VARIANTS as $vk => [$vl, $vo]): ?>
        <label><?= e($vl) ?><select name="style_<?= e($vk) ?>"><?php foreach ($vo as $ok => $ol) echo '<option value="' . e($ok) . '"' . (theme_variant($vk) === $ok ? ' selected' : '') . '>' . e($ol) . '</option>'; ?></select></label>
      <?php endforeach; ?>
    </div>

    <div id="contrast-warnings" class="flash flash-info" hidden></div>

    <div class="actions">
      <button class="btn" type="submit">Save theme</button>
      <button class="btn btn-ghost" type="submit" name="action" value="reset" formnovalidate onclick="return confirm('Reset all theme colors to the defaults?')">Reset to defaults</button>
    </div>
  </form>

  <aside class="theme-preview-wrap">
    <h2>Live preview <small class="muted" id="pv-mode" style="font-weight:400">(light)</small></h2>
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
