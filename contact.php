<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = current_user();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['website'] ?? '') !== '') redirect('');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $msg = trim($_POST['message'] ?? '');
    if ($name === '') $errors[] = 'Please enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
    if ($msg === '') $errors[] = 'Please enter a message.';
    if (!$errors) {
        save_lead(['user_id' => $user['id'] ?? null, 'name' => $name, 'email' => $email, 'phone' => $phone,
            'message' => $msg, 'source' => 'contact form']);
        flash('Thanks for reaching out. I will reply soon.');
        redirect('contact.php');
    }
}
$pageTitle = 'Contact';
include __DIR__ . '/includes/header.php';
?>
<div class="wrap narrow section">
  <h1>Contact</h1>
  <?php foreach ($errors as $er): ?><div class="flash flash-err"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" class="panel">
    <?= csrf_field() ?>
    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
    <label>Name<input name="name" required value="<?= e($_POST['name'] ?? $user['name'] ?? '') ?>"></label>
    <label>Email<input type="email" name="email" required value="<?= e($_POST['email'] ?? $user['email'] ?? '') ?>"></label>
    <label>Phone<input type="tel" name="phone" value="<?= e($_POST['phone'] ?? $user['phone'] ?? '') ?>"></label>
    <label>Message<textarea name="message" rows="5" required><?= e($_POST['message'] ?? '') ?></textarea></label>
    <button class="btn block" type="submit">Send message</button>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
