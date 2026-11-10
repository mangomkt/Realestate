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
    if (!empty($_POST['demo']) && (int)$pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn() === 0) { try { demo_load(); } catch (Throwable $ex) { flash('Sample content could not be added: ' . $ex->getMessage(), 'err'); } }
    $look = (string)($_POST['look'] ?? '');
    if ($look !== '') look_apply($look, true);
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
  <h2>3. Pick a look</h2>
  <p class="small muted">Sets colors, fonts, layout and a starting home page. You can change every detail later.</p>
  <div class="looks">
    <?php foreach (LOOKS as $k => $l): $p = look_palette($l); ?>
      <label class="look panel" style="cursor:pointer;margin:0">
        <input type="radio" name="look" value="<?= e($k) ?>" <?= $k === 'classic' ? 'checked' : '' ?>>
        <span style="display:block;height:10px;margin:6px 0;border-radius:4px;background:linear-gradient(90deg,<?= e($p['primary']) ?> 40%,<?= e($p['tertiary']) ?> 40% 70%,<?= e($p['bg']) ?> 70%);border:1px solid var(--line)"></span>
        <strong><?= e($l['name']) ?></strong><br><span class="small muted"><?= e($l['desc']) ?></span>
      </label>
    <?php endforeach; ?>
  </div>
  <label class="check"><input type="checkbox" name="demo" value="1" checked> Add sample listings and pages so the site doesn't look empty (you can remove them in one click under Tools)</label>
  <p><button class="btn" type="submit">Save and continue</button> <a class="btn btn-ghost" href="<?= url('admin/') ?>">Skip for now</a></p>
</form>
<?php admin_foot(); ?>
