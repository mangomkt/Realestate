<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo = db();
    save_setting('site_name', mb_substr(trim($_POST['site_name'] ?? ''), 0, 100) ?: 'Your Name Realty');
    save_setting('tagline', mb_substr(trim($_POST['tagline'] ?? ''), 0, 160));
    save_setting('phone', mb_substr(trim($_POST['phone'] ?? ''), 0, 40));
    $em = trim($_POST['contact_email'] ?? '');
    if ($em === '' || filter_var($em, FILTER_VALIDATE_EMAIL)) save_setting('contact_email', $em);
    save_setting('agent_line', mb_substr(trim($_POST['agent_line'] ?? ''), 0, 200));
    foreach (['logo', 'favicon'] as $k) {
        $u = trim($_POST[$k] ?? '');
        if (preg_match('#^(https?://|/)[^\s"\'<>]*$#', $u)) save_setting($k, $u);
    }
    $presets = theme_presets();
    $pre = $_POST['preset'] ?? '';
    if (isset($presets[$pre])) foreach ($presets[$pre] as $k => $v) save_setting('theme_' . $k, $v);
    $mode = $_POST['mode'] ?? '';
    if (isset(THEME_MODES[$mode])) save_setting('theme_mode', $mode);
    foreach (THEME_VARIANTS as $vk => [, $vo]) {
        $vv = (string)($_POST['style_' . $vk] ?? '');
        if (isset($vo[$vv])) save_setting('style_' . $vk, $vv);
    }
    flash('You are set up. Next: add your first property and arrange the home page.');
    redirect('admin/');
}

admin_head('Quick setup');
?>
<p class="muted">Five minutes to a site that looks like yours. Everything here can be changed later under Settings, Theme and Home page.</p>
<form method="post" class="panel">
  <?= csrf_field() ?>
  <h2>1. About you</h2>
  <div class="form-grid">
    <label>Your name or brokerage name<input name="site_name" value="<?= e(setting('site_name', '') === 'Your Name Realty' ? '' : setting('site_name')) ?>" required></label>
    <label>Tagline<input name="tagline" value="<?= e(setting('tagline')) ?>" placeholder="Local expertise. Honest advice."></label>
    <label>Phone<input name="phone" value="<?= e(setting('phone')) ?>"></label>
    <label>Email for leads<input type="email" name="contact_email" value="<?= e(setting('contact_email')) ?>"></label>
    <label class="full">Registration line for the footer<input name="agent_line" value="<?= e(setting('agent_line')) ?>" placeholder="Jane Doe, Sales Representative, Example Realty Inc., Brokerage"></label>
  </div>
  <h2>2. Branding</h2>
  <div class="form-grid">
    <?php render_image_field('logo', setting('logo'), 'Logo'); render_image_field('favicon', setting('favicon'), 'Favicon (square)'); ?>
  </div>
  <label style="max-width:360px">Color scheme
    <select name="preset"><option value="">Keep current</option><?php foreach (array_keys(theme_presets()) as $n) echo '<option>' . e($n) . '</option>'; ?></select>
  </label>
  <div class="form-grid">
    <label>Light or dark<select name="mode"><?php foreach (THEME_MODES as $k => $l) echo '<option value="' . e($k) . '"' . (theme_settings()['mode'] === $k ? ' selected' : '') . '>' . e($l) . '</option>'; ?></select></label>
    <?php foreach (THEME_VARIANTS as $vk => [$vl, $vo]): ?>
      <label><?= e($vl) ?><select name="style_<?= e($vk) ?>"><?php foreach ($vo as $ok => $ol) echo '<option value="' . e($ok) . '"' . (theme_variant($vk) === $ok ? ' selected' : '') . '>' . e($ol) . '</option>'; ?></select></label>
    <?php endforeach; ?>
  </div>
  <p><button class="btn" type="submit">Save and continue</button> <a class="btn btn-ghost" href="<?= url('admin/') ?>">Skip for now</a></p>
</form>
<?php admin_foot(); ?>
