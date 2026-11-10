<?php
// Emergency admin password reset. Safe by design: it only works for someone who can touch the server's files.
//
//  Option A (command line):   php reset_admin.php "new-password-here" [admin@email.com]
//  Option B (no shell access): create an empty file named  data/RESET_ADMIN  with your host's file manager
//                              or FTP, then open  /reset_admin.php  in your browser. The file is deleted
//                              automatically once the password is changed.
require_once __DIR__ . '/includes/bootstrap.php';

function reset_apply(string $pw, string $email): string
{
    // Also lifts login lockouts and turns off two-step verification, since this is the "I am locked out" tool.
    db()->exec("DELETE FROM login_attempts");
    db()->exec("UPDATE admins SET totp_enabled = 0, totp_secret = '', backup_codes = ''");
    if (strlen($pw) < 10) return 'The password must be at least 10 characters.';
    $pdo = db();
    $email = strtolower(trim($email));
    if ($email !== '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Enter a valid email address.';
        $st = $pdo->prepare('UPDATE admins SET password_hash = ? WHERE email = ?');
        $st->execute([password_hash($pw, PASSWORD_DEFAULT), $email]);
        if ($st->rowCount() === 0) return 'No admin account uses that email.';
        return '';
    }
    $first = $pdo->query('SELECT id FROM admins ORDER BY id LIMIT 1')->fetchColumn();
    if ($first === false) return 'No admin account exists yet. Open /admin/login.php to create one.';
    $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($pw, PASSWORD_DEFAULT), $first]);
    return '';
}

if (PHP_SAPI === 'cli') {
    $pw = $argv[1] ?? '';
    if ($pw === '') {
        $admins = db()->query('SELECT email FROM admins ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
        fwrite(STDERR, "Usage: php reset_admin.php \"new-password\" [admin@email.com]\nAdmin accounts: " . ($admins ? implode(', ', $admins) : '(none yet)') . "\n");
        exit(1);
    }
    $err = reset_apply($pw, $argv[2] ?? '');
    echo $err === '' ? "Password changed. You can log in now.\n" : "Error: $err\n";
    exit($err === '' ? 0 : 1);
}

$flag = __DIR__ . '/data/RESET_ADMIN';
http_response_code(is_file($flag) ? 200 : 404);
header('X-Robots-Tag: noindex');
$msg = '';
$done = false;
if (is_file($flag) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['password'] ?? '') !== ($_POST['password2'] ?? '')) $msg = 'The two passwords do not match.';
    else {
        $msg = reset_apply((string)($_POST['password'] ?? ''), (string)($_POST['email'] ?? ''));
        if ($msg === '') { @unlink($flag); $done = true; }
    }
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Reset admin password</title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>"></head><body>
<div class="wrap narrow section">
<?php if ($done): ?>
  <h1>Password changed</h1>
  <p><a class="btn" href="<?= url('admin/login.php') ?>">Go to admin login</a></p>
<?php elseif (!is_file($flag)): ?>
  <h1>Not found</h1>
  <p class="muted">This page is switched off.</p>
<?php else: ?>
  <h1>Reset admin password</h1>
  <?php if ($msg): ?><div class="flash flash-err"><?= e($msg) ?></div><?php endif; ?>
  <form method="post" class="panel">
    <?= csrf_field() ?>
    <label>Admin email (leave blank for the first admin account)<input type="email" name="email"></label>
    <label>New password (10+ characters)<input type="password" name="password" minlength="10" required autocomplete="new-password"></label>
    <label>Repeat password<input type="password" name="password2" minlength="10" required autocomplete="new-password"></label>
    <button class="btn block" type="submit">Change password</button>
  </form>
<?php endif; ?>
</div></body></html>
