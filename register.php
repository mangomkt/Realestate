<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (setting('registration_enabled', '1') !== '1') { flash('Registration is currently closed.', 'info'); redirect(''); }
if (current_user()) redirect('listings.php');

$errors = [];
$v = ['name' => '', 'email' => '', 'phone' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    $pw = $_POST['password'] ?? '';
    if (($_POST['website'] ?? '') !== '') redirect('');
    if (rl_locked('register', '')) { flash(RL_MESSAGE, 'err'); redirect('register.php'); }
    rl_fail('register', '');
    if ($v['name'] === '') $errors[] = 'Please enter your name.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
    if (strlen($pw) < 8) $errors[] = 'Password must be at least 8 characters.';
    if (!$errors) {
        $ex = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $ex->execute([strtolower($v['email'])]);
        if ($ex->fetch()) {
            $errors[] = 'An account with that email already exists. Try logging in.';
        } else {
            db()->prepare('INSERT INTO users (name, email, phone, password_hash) VALUES (?,?,?,?)')
                ->execute([$v['name'], strtolower($v['email']), $v['phone'], password_hash($pw, PASSWORD_DEFAULT)]);
            $uid = (int)db()->lastInsertId();
            save_lead(['user_id' => $uid, 'name' => $v['name'], 'email' => strtolower($v['email']), 'phone' => $v['phone'],
                'message' => 'New account registration', 'source' => 'registration']);
            session_regenerate_id(true);
            $_SESSION['uid'] = $uid;
            flash('Welcome! Your account is ready.');
            redirect(safe_next());
        }
    }
}
$pageTitle = 'Register';
$seo = ['robots' => 'noindex,follow'];
include __DIR__ . '/includes/header.php';
?>
<div class="wrap narrow section">
  <h1>Create your free account</h1>
  <p class="muted">Save searches, view all listings and get new-listing updates.</p>
  <?php foreach ($errors as $er): ?><div class="flash flash-err"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" class="panel">
    <?= csrf_field() ?>
    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
    <label>Full name<input name="name" required value="<?= e($v['name']) ?>"></label>
    <label>Email<input type="email" name="email" required value="<?= e($v['email']) ?>"></label>
    <label>Phone (optional)<input type="tel" name="phone" value="<?= e($v['phone']) ?>"></label>
    <label>Password (8+ characters)<input type="password" name="password" minlength="8" required></label>
    <button class="btn block" type="submit">Register</button>
    <p class="small muted">Already registered? <a href="<?= url('login.php') ?>">Log in</a></p>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
