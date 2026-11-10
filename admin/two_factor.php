<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if (is_admin()) redirect('admin/');
$pend = $_SESSION['admin_pending'] ?? null;
if (!$pend || time() - (int)$pend['at'] > 600) { unset($_SESSION['admin_pending']); redirect('admin/login.php'); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)$pend['id'];
    $acct = 'id' . $id;
    $a = db()->prepare('SELECT * FROM admins WHERE id = ?');
    $a->execute([$id]);
    $a = $a->fetch();
    $code = trim((string)($_POST['code'] ?? ''));
    if (!$a) redirect('admin/login.php');
    if (rl_locked('admin2fa', $acct)) {
        $error = RL_MESSAGE;
    } else {
        $step = totp_verify((string)$a['totp_secret'], $code, (int)$a['totp_last']);
        $left = $step === null && $a['backup_codes'] !== '' ? backup_code_use((string)$a['backup_codes'], $code) : null;
        if ($step !== null) {
            db()->prepare('UPDATE admins SET totp_last = ? WHERE id = ?')->execute([$step, $id]);
        } elseif ($left !== null) {
            db()->prepare('UPDATE admins SET backup_codes = ? WHERE id = ?')->execute([$left, $id]);
        }
        if ($step !== null || $left !== null) {
            rl_clear('admin2fa', $acct);
            admin_signed_in($id);
            if ($left !== null) flash('You signed in with a backup code. Create new codes under Security when convenient.', 'info');
            redirect('admin/');
        }
        rl_fail('admin2fa', $acct);
        $error = 'That code was not right. Check the code in your app (it changes every 30 seconds).';
    }
}
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Two-step verification</title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head><body>
<div class="wrap narrow section">
  <h1>Two-step verification</h1>
  <p class="muted">Enter the 6-digit code from your authenticator app, or one of your backup codes.</p>
  <?php if ($error): ?><div class="flash flash-err"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="panel">
    <?= csrf_field() ?>
    <label>Code<input name="code" inputmode="numeric" autocomplete="one-time-code" autofocus required></label>
    <button class="btn block" type="submit">Verify</button>
  </form>
  <p class="small muted"><a href="<?= url('admin/login.php') ?>">Start over</a></p>
</div>
</body></html>
