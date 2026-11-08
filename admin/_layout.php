<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

function admin_head(string $title): void
{
    $flash = flash();
    ?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?> | Admin</title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head><body>
<div class="admin-bar"><div class="wrap bar">
  <nav>
    <a href="<?= url('admin/') ?>"><strong>Admin</strong></a>
    <a href="<?= url('admin/properties.php') ?>">Properties</a>
    <a href="<?= url('admin/pages.php') ?>">Pages</a>
    <a href="<?= url('admin/navigation.php') ?>">Navigation</a>
    <a href="<?= url('admin/theme.php') ?>">Theme</a>
    <a href="<?= url('admin/leads.php') ?>">Leads</a>
    <a href="<?= url('admin/settings.php') ?>">Settings</a>
  </nav>
  <nav><a href="<?= url() ?>" target="_blank">View site</a><a href="<?= url('admin/logout.php') ?>">Log out</a></nav>
</div></div>
<div class="wrap section">
<?php if ($flash): ?><div class="flash flash-<?= e($flash[1]) ?>"><?= e($flash[0]) ?></div><?php endif; ?>
<h1><?= e($title) ?></h1>
<?php
}

function admin_foot(): void
{
    echo '</div><script src="' . url('assets/js/app.js') . '"></script></body></html>';
}
