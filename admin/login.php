<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// First run: no admin yet -> create one.
$hasAdmin = (int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
if (is_admin()) redirect('admin/');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pw = $_POST['password'] ?? '';
    if ($hasAdmin && rl_locked('admin', $email)) {
        $error = RL_MESSAGE;
    } elseif (!$hasAdmin) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter a valid email.';
        elseif (strlen($pw) < 10) $error = 'Use a password of at least 10 characters.';
        else {
            db()->prepare('INSERT INTO admins (email, password_hash) VALUES (?, ?)')->execute([$email, password_hash($pw, PASSWORD_DEFAULT)]);
            $newId = (int)db()->lastInsertId();   // read before any other insert (save_setting would change it)
            if (setting('contact_email') === '') save_setting('contact_email', $email);
            admin_signed_in($newId);
            redirect('admin/setup.php');
        }
    } else {
        $st = db()->prepare('SELECT id, password_hash, totp_enabled FROM admins WHERE email = ?');
        $st->execute([$email]);
        $a = $st->fetch();
        // Always run a hash check so response time does not reveal whether the email exists.
        $ok = password_verify($pw, $a['password_hash'] ?? '$2y$10$T.X/eH12UmRHmy3OQDuFAuGubNJ0Va/BwIjEUB83C4TYI63.X5A8G') && $a;
        if ($ok) {
            rl_clear('admin', $email);
            session_regenerate_id(true);
            if ($a['totp_enabled']) {
                $_SESSION['admin_pending'] = ['id' => (int)$a['id'], 'at' => time()];
                redirect('admin/two_factor.php');
            }
            admin_signed_in((int)$a['id']);
            redirect('admin/');
        }
        rl_fail('admin', $email);
        $error = 'Incorrect email or password.';
    }
}
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= $hasAdmin ? 'Admin login' : 'Create admin account' ?></title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head><body>
<div class="wrap narrow section">
  <h1><?= $hasAdmin ? 'Admin login' : 'Create your admin account' ?></h1>
  <?php if (!$hasAdmin): ?><p class="muted">First-time setup. This account manages properties, leads and settings.</p><?php endif; ?>
  <?php if ($error): ?><div class="flash flash-err"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="panel">
    <?= csrf_field() ?>
    <label>Email<input type="email" name="email" required autofocus></label>
    <label>Password<input type="password" name="password" required <?= $hasAdmin ? '' : 'minlength="10"' ?>></label>
    <button class="btn block" type="submit"><?= $hasAdmin ? 'Log in' : 'Create account' ?></button>
  </form>
  <?php if ($hasAdmin): ?><p class="small muted"><a href="<?= url('reset_admin.php') ?>">Forgot your password?</a></p><?php endif; ?>
</div>
</body></html>
