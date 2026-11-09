<?php
require_once __DIR__ . '/_layout.php';

$textKeys = ['site_name', 'tagline', 'agent_line', 'phone', 'contact_email', 'address', 'disclaimer', 'idx_title',
             'social_facebook', 'social_instagram', 'social_youtube', 'social_linkedin', 'social_x', 'social_tiktok'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($textKeys as $k) save_setting($k, trim($_POST[$k] ?? ''));
    save_setting('registration_enabled', isset($_POST['registration_enabled']) ? '1' : '0');
    save_setting('registration_required', isset($_POST['registration_required']) ? '1' : '0');
    save_setting('free_views', (string)max(0, (int)($_POST['free_views'] ?? 2)));
    save_setting('idx_embed', trim($_POST['idx_embed'] ?? ''));
    foreach (['logo', 'logo_dark', 'favicon'] as $k) {
        $u = trim($_POST[$k] ?? '');
        save_setting($k, preg_match('#^(https?://|/)[^\s"\'<>]*$#', $u) ? $u : '');
    }
    $su = rtrim(trim($_POST['site_url'] ?? ''), '/');
    save_setting('site_url', preg_match('#^https?://[^\s"\'<>]+$#i', $su) ? $su : '');
    save_setting('autoreply_on', isset($_POST['autoreply_on']) ? '1' : '0');
    save_setting('autoreply_subject', mb_substr(trim($_POST['autoreply_subject'] ?? ''), 0, 150));
    save_setting('autoreply_text', mb_substr(trim($_POST['autoreply_text'] ?? ''), 0, 3000));
    save_setting('cookie_banner', isset($_POST['cookie_banner']) ? '1' : '0');
    save_setting('cookie_text', mb_substr(trim($_POST['cookie_text'] ?? ''), 0, 400));
    $ga = strtoupper(trim($_POST['analytics_ga4'] ?? ''));
    save_setting('analytics_ga4', preg_match('/^G-[A-Z0-9]{4,15}$/', $ga) ? $ga : '');
    $pl = trim($_POST['analytics_plausible'] ?? '');
    save_setting('analytics_plausible', preg_match('/^[a-z0-9.\-]+\.[a-z]{2,}$/i', $pl) ? strtolower($pl) : '');
    save_setting('mortgage_calc', isset($_POST['mortgage_calc']) ? '1' : '0');
    $rate = (float)($_POST['mortgage_rate'] ?? 5);
    save_setting('mortgage_rate', (string)max(0.1, min(25, $rate ?: 5)));
    save_setting('pretty_urls', isset($_POST['pretty_urls']) ? '1' : '0');

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
    <label>Agent / brokerage line (footer)<input name="agent_line" value="<?= $v('agent_line') ?>" placeholder="Jane Doe, Sales Representative, Example Realty Inc., Brokerage"></label>
    <label>Phone<input name="phone" value="<?= $v('phone') ?>"></label>
    <label>Email (leads are sent here)<input type="email" name="contact_email" value="<?= $v('contact_email') ?>"></label>
    <label>Office address<textarea name="address" rows="2"><?= $v('address') ?></textarea></label>
    <label class="full">Footer disclaimer<textarea name="disclaimer" rows="2"><?= $v('disclaimer', 'Listing information is deemed reliable but not guaranteed.') ?></textarea></label>
  </div>

  <label>Website address (used in alert emails, e.g. https://www.janedoe.ca)<input name="site_url" value="<?= $v('site_url') ?>" placeholder="https://"></label>

  <h2>Automatic reply to leads</h2>
  <label class="check"><input type="checkbox" name="autoreply_on" value="1" <?= setting('autoreply_on') === '1' ? 'checked' : '' ?>> Email visitors a confirmation when they send an inquiry or contact message</label>
  <div class="form-grid">
    <label>Subject<input name="autoreply_subject" value="<?= $v('autoreply_subject') ?>" placeholder="Thanks for contacting <?= $v('site_name', 'Your Name Realty') ?>"></label>
    <label class="full">Message ({name}, {site} and {phone} are filled in)<textarea name="autoreply_text" rows="5" placeholder="Hi {name}, thanks for getting in touch..."><?= $v('autoreply_text') ?></textarea></label>
  </div>

  <h2>Privacy and analytics</h2>
  <div class="form-grid">
    <label>Google Analytics 4 measurement ID<input name="analytics_ga4" value="<?= $v('analytics_ga4') ?>" placeholder="G-XXXXXXXXXX"></label>
    <label>Plausible domain (optional)<input name="analytics_plausible" value="<?= $v('analytics_plausible') ?>" placeholder="example.com"></label>
  </div>
  <label class="check"><input type="checkbox" name="cookie_banner" value="1" <?= setting('cookie_banner') === '1' ? 'checked' : '' ?>> Show a cookie notice and only load analytics after a visitor accepts</label>
  <label>Cookie notice text<input name="cookie_text" value="<?= $v('cookie_text') ?>" placeholder="We use cookies to understand how the site is used."></label>
  <p class="small muted">Many regions (including Canada and the EU) expect consent before analytics cookies. If you only use Plausible, no banner is needed. Tell your client to check local rules.</p>

  <h2>Logo and icon</h2>
  <div class="form-grid">
    <?php render_image_field('logo', setting('logo'), 'Logo (for light mode)', 'PNG or WebP with a transparent background works best.');
          render_image_field('logo_dark', setting('logo_dark'), 'Logo (for dark mode, optional)', 'Leave empty to reuse the main logo.');
          render_image_field('favicon', setting('favicon'), 'Favicon (square, 512px+)', 'The small icon in browser tabs.'); ?>
  </div>

  <h2>Mortgage calculator</h2>
  <label class="check"><input type="checkbox" name="mortgage_calc" value="1" <?= setting('mortgage_calc', '1') === '1' ? 'checked' : '' ?>> Show a payment calculator on property pages</label>
  <label style="max-width:260px">Default interest rate (%)<input type="number" step="0.01" min="0.1" max="25" name="mortgage_rate" value="<?= $v('mortgage_rate', '5') ?>"></label>

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

  <h2>Page URLs</h2>
  <label class="check"><input type="checkbox" name="pretty_urls" value="1" <?= setting('pretty_urls') === '1' ? 'checked' : '' ?>> Use pretty page URLs like /p/about (requires Apache with mod_rewrite; the included .htaccess does the rest)</label>
  <p class="small muted">If pages show "Not Found" after switching this on, your host does not allow .htaccess rewrites: switch it off again.</p>

  <h2>Admin password</h2>
  <label style="max-width:320px">New password (leave blank to keep)<input type="password" name="new_password" minlength="10" autocomplete="new-password"></label>

  <button class="btn" type="submit">Save settings</button>
</form>
<?php admin_foot(); ?>
