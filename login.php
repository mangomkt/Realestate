<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('listings.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (rl_locked('user', $email)) {
        $error = RL_MESSAGE;
    } else {
        $st = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();
        $ok = password_verify($_POST['password'] ?? '', $u['password_hash'] ?? '$2y$10$T.X/eH12UmRHmy3OQDuFAuGubNJ0Va/BwIjEUB83C4TYI63.X5A8G') && $u;
        if ($ok) {
            rl_clear('user', $email);
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$u['id'];
            redirect(safe_next());
        }
        rl_fail('user', $email);
        $error = 'Incorrect email or password.';
    }
}
$pageTitle = 'Log in';
$seo = ['robots' => 'noindex,follow'];
include __DIR__ . '/includes/header.php';
?>
<div class="wrap narrow section">
  <h1>Log in</h1>
  <?php if ($error): ?><div class="flash flash-err"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="panel">
    <?= csrf_field() ?>
    <label>Email<input type="email" name="email" required autofocus></label>
    <label>Password<input type="password" name="password" required></label>
    <button class="btn block" type="submit">Log in</button>
    <?php if (setting('registration_enabled', '1') === '1'): ?>
      <p class="small muted">New here? <a href="<?= url('register.php') ?>">Create an account</a></p>
    <?php endif; ?>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
