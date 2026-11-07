<?php
require_once __DIR__ . '/bootstrap.php';
$user = current_user();
$siteName = setting('site_name', 'Your Name Realty');
$flash = flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($pageTitle ?? '') !== '' ? $pageTitle . ' | ' . $siteName : $siteName) ?></title>
  <meta name="description" content="<?= e($metaDesc ?? setting('tagline', 'Find your next home.')) ?>">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
  <div class="wrap bar">
    <a class="logo" href="<?= url() ?>"><?= e($siteName) ?></a>
    <button class="nav-toggle" aria-label="Menu" aria-expanded="false" aria-controls="nav">&#9776;</button>
    <nav id="nav" class="nav">
      <a href="<?= url() ?>">Home</a>
      <a href="<?= url('listings.php') ?>">Listings</a>
      <?php if (trim(setting('idx_embed')) !== ''): ?><a href="<?= url('idx.php') ?>">Search All MLS</a><?php endif; ?>
      <a href="<?= url('contact.php') ?>">Contact</a>
      <?php if ($user): ?>
        <a href="<?= url('logout.php') ?>">Log out (<?= e(explode(' ', $user['name'])[0]) ?>)</a>
      <?php elseif (setting('registration_enabled', '1') === '1'): ?>
        <a href="<?= url('login.php') ?>">Log in</a>
        <a class="btn btn-sm" href="<?= url('register.php') ?>">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<?php if ($flash): ?><div class="wrap"><div class="flash flash-<?= e($flash[1]) ?>"><?= e($flash[0]) ?></div></div><?php endif; ?>
<main>
