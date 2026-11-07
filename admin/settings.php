<?php
require_once __DIR__ . '/_layout.php';

$textKeys = ['site_name', 'tagline', 'hero_title', 'phone', 'contact_email', 'address', 'disclaimer', 'idx_title',
             'social_facebook', 'social_instagram', 'social_youtube', 'social_linkedin', 'social_x', 'social_tiktok'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($textKeys as $k) save_setting($k, trim($_POST[$k] ?? ''));
    save_setting('registration_enabled', isset($_POST['registration_enabled']) ? '1' : '0');
    save_setting('registration_required', isset($_POST['registration_required']) ? '1' : '0');
    save_setting('free_views', (string)max(0, (int)($_POST['free_views'] ?? 2)));
    save_setting('idx_embed', trim($_POST['idx_embed'] ?? ''));

    if (($_POST['new_password'] ?? '') !== '') {
        if (strlen($_POST['new_password']) < 10) { flash('Settings saved, but the new password must be 10+ characters, so it was not changed.', 'err'); redirect('admin/settings.php'); }
        db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($_POST['new_password'], PASSWORD_DEFAULT), $_SESSION['admin']]);
    }
    flash('Settings saved.');
    redirect('admin/settings.php');
}

$v = fn(string $k, string $d = '') => e(setting($k, $d));
admin_head('Settings');
?>
<form method="post" class="panel">
  <?= csrf_field() ?>
  <h2>Lead capture</h2>
  <label class="check"><input type="checkbox" name="registration_enabled" value="1" <?= setting('registration_enabled', '1') === '1' ? 'checked' : '' ?>> Allow visitors to register (shows Register / Log in links)</label>
  <label class="check"><input type="checkbox" name="registration_required" value="1" <?= setting('registration_required') === '1' ? 'checked' : '' ?>> Require registration after a few free listing views</label>
  <label style="max-width:260px">Free views before registration is required<input type="number" min="0" name="free_views" value="<?= $v('free_views', '2') ?>"></label>
  <p class="small muted">Every registration and inquiry is saved under Leads (and emailed to your contact address). "Require registration" only works if registration is allowed.</p>

  <h2>Site details</h2>
  <div class="form-grid">
    <label>Site / agent name<input name="site_name" value="<?= $v('site_name', 'Your Name Realty') ?>"></label>
    <label>Tagline<input name="tagline" value="<?= $v('tagline', 'Local expertise. Honest advice.') ?>"></label>
    <label>Home page headline<input name="hero_title" value="<?= $v('hero_title', 'Find a home you love') ?>"></label>
    <label>Phone<input name="phone" value="<?= $v('phone') ?>"></label>
    <label>Email (leads are sent here)<input type="email" name="contact_email" value="<?= $v('contact_email') ?>"></label>
    <label>Office address<textarea name="address" rows="2"><?= $v('address') ?></textarea></label>
    <label class="full">Footer disclaimer<textarea name="disclaimer" rows="2"><?= $v('disclaimer', 'Listing information is deemed reliable but not guaranteed.') ?></textarea></label>
  </div>

  <h2>Social media</h2>
  <div class="form-grid">
    <?php foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn', 'x' => 'X (Twitter)', 'tiktok' => 'TikTok'] as $k => $l): ?>
      <label><?= $l ?> URL<input type="url" name="social_<?= $k ?>" value="<?= $v('social_' . $k) ?>" placeholder="https://"></label>
    <?php endforeach; ?>
  </div>

  <h2>MLS&reg; / IDX search</h2>
  <label>Page title<input name="idx_title" value="<?= $v('idx_title', 'Search All Listings') ?>"></label>
  <label>IDX embed code (iframe or script from your IDX provider)<textarea name="idx_embed" rows="6" style="font-family:monospace" placeholder="<iframe src=&quot;...&quot;></iframe>"><?= $v('idx_embed') ?></textarea></label>
  <p class="small muted">Paste the snippet your board-approved IDX vendor gives you. A "Search All MLS" page and link appear once this is filled in. Only paste code from a vendor you trust: it runs on your site.</p>

  <h2>Admin password</h2>
  <label style="max-width:320px">New password (leave blank to keep)<input type="password" name="new_password" minlength="10" autocomplete="new-password"></label>

  <button class="btn" type="submit">Save settings</button>
</form>
<?php admin_foot(); ?>
