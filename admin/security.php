<?php
require_once __DIR__ . '/_layout.php';

$me = db()->prepare('SELECT * FROM admins WHERE id = ?');
$me->execute([$_SESSION['admin']]);
$me = $me->fetch();
$newCodes = null;
$setupSecret = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    $pwOk = fn() => password_verify((string)($_POST['password'] ?? ''), $me['password_hash']);
    if ($act === 'start' && !$me['totp_enabled']) {
        $_SESSION['totp_setup'] = totp_secret();
    } elseif ($act === 'cancel') {
        unset($_SESSION['totp_setup']);
    } elseif ($act === 'enable' && !empty($_SESSION['totp_setup'])) {
        $sec = $_SESSION['totp_setup'];
        $step = totp_verify($sec, (string)($_POST['code'] ?? ''));
        if ($step === null) { flash('That code was not right. Check your app and try again.', 'err'); }
        else {
            [$plain, $hashed] = backup_codes_make();
            db()->prepare('UPDATE admins SET totp_secret = ?, totp_enabled = 1, totp_last = ?, backup_codes = ? WHERE id = ?')->execute([$sec, $step, $hashed, $me['id']]);
            unset($_SESSION['totp_setup']);
            $newCodes = $plain;
            $me['totp_enabled'] = 1;
        }
    } elseif ($act === 'regen' && $me['totp_enabled']) {
        if (!$pwOk()) flash('Password incorrect.', 'err');
        else { [$plain, $hashed] = backup_codes_make(); db()->prepare('UPDATE admins SET backup_codes = ? WHERE id = ?')->execute([$hashed, $me['id']]); $newCodes = $plain; }
    } elseif ($act === 'disable' && $me['totp_enabled']) {
        if (!$pwOk()) flash('Password incorrect, so two-step verification was left on.', 'err');
        else {
            db()->prepare("UPDATE admins SET totp_enabled = 0, totp_secret = '', backup_codes = '' WHERE id = ?")->execute([$me['id']]);
            flash('Two-step verification is off.');
            redirect('admin/security.php');
        }
    } elseif ($act === 'password') {
        $new = (string)($_POST['new_password'] ?? '');
        if (!$pwOk()) flash('Current password incorrect.', 'err');
        elseif (strlen($new) < 10) flash('The new password must be at least 10 characters.', 'err');
        else {
            db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
            session_regenerate_id(true);
            flash('Password changed.');
            redirect('admin/security.php');
        }
    }
    if ($newCodes === null && !in_array($act, ['start', 'cancel', 'enable'], true)) redirect('admin/security.php');
}
$setupSecret = $_SESSION['totp_setup'] ?? null;
$left = $me['totp_enabled'] ? count(json_decode((string)$me['backup_codes'], true) ?: []) : 0;
admin_head('Security');
?>
<?php if ($newCodes): ?>
<div class="panel" style="border-color:var(--brand)">
  <h2>Your backup codes</h2>
  <p>Save these somewhere safe now (a password manager is ideal). Each code works once if you lose your phone. They will not be shown again.</p>
  <pre style="font-size:1.1rem;line-height:1.8"><?= e(implode("\n", $newCodes)) ?></pre>
</div>
<?php endif; ?>

<div class="panel">
  <h2>Two-step verification</h2>
  <?php if ($me['totp_enabled']): ?>
    <p><strong>On.</strong> Logging in needs a code from your authenticator app. <?= $left ?> backup code<?= $left === 1 ? '' : 's' ?> left.</p>
    <form method="post" class="actions"><?= csrf_field() ?>
      <label style="max-width:260px">Your password<input type="password" name="password" required autocomplete="current-password"></label>
      <button class="btn btn-ghost" name="action" value="regen">New backup codes</button>
      <button class="btn btn-ghost" name="action" value="disable" onclick="return confirm('Turn off two-step verification?')">Turn off</button>
    </form>
  <?php elseif ($setupSecret): ?>
    <ol>
      <li>Open an authenticator app (Google Authenticator, Microsoft Authenticator, 1Password, Authy).</li>
      <li>Add an account by choosing <em>Enter a setup key</em> and type this key (spaces don't matter):<br>
        <code style="font-size:1.15rem;user-select:all"><?= e(trim(chunk_split($setupSecret, 4, ' '))) ?></code></li>
      <li>Type the 6-digit code the app shows to confirm.</li>
    </ol>
    <p class="small muted">On a phone with the app installed you can also <a href="<?= e(totp_uri($setupSecret, $me['email'])) ?>">tap here to add it automatically</a>.</p>
    <form method="post" class="actions"><?= csrf_field() ?>
      <label style="max-width:200px">6-digit code<input name="code" inputmode="numeric" autocomplete="one-time-code" required></label>
      <button class="btn" name="action" value="enable">Turn on</button>
      <button class="btn btn-ghost" name="action" value="cancel" formnovalidate>Cancel</button>
    </form>
  <?php else: ?>
    <p>Adds a second step at login, so a stolen password alone cannot open your admin area. Recommended.</p>
    <form method="post"><?= csrf_field() ?><button class="btn" name="action" value="start">Set up two-step verification</button></form>
  <?php endif; ?>
</div>

<div class="panel" style="margin-top:18px">
  <h2>Change password</h2>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="password">
    <div class="form-grid">
      <label>Current password<input type="password" name="password" required autocomplete="current-password"></label>
      <label>New password (10+ characters)<input type="password" name="new_password" minlength="10" required autocomplete="new-password"></label>
    </div>
    <button class="btn" type="submit">Change password</button>
  </form>
</div>

<div class="panel" style="margin-top:18px">
  <h2>Sign-in activity</h2>
  <p class="muted">Last sign-in: <?= $me['last_login_at'] ? e($me['last_login_at'] . ' UTC from ' . $me['last_login_ip']) : 'not recorded yet' ?>. Admin sessions end after 2 hours of inactivity, and 6 wrong passwords lock an account for 15 minutes.</p>
</div>
<?php admin_foot(); ?>
