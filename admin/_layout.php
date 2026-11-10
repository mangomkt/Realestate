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
    <a href="<?= url('admin/home.php') ?>">Home page</a>
    <a href="<?= url('admin/pages.php') ?>">Pages</a>
    <a href="<?= url('admin/navigation.php') ?>">Navigation</a>
    <a href="<?= url('admin/looks.php') ?>">Starter looks</a>
    <a href="<?= url('admin/theme.php') ?>">Theme</a>
    <a href="<?= url('admin/seo.php') ?>">SEO</a>
    <a href="<?= url('admin/leads.php') ?>">Leads</a>
    <a href="<?= url('admin/settings.php') ?>">Settings</a>
    <a href="<?= url('admin/tools.php') ?>">Tools</a>
    <a href="<?= url('admin/security.php') ?>">Security</a>
  </nav>
  <nav><a href="<?= url() ?>" target="_blank">View site</a><a href="<?= url('admin/logout.php') ?>">Log out</a></nav>
</div></div>
<div class="wrap section">
<?php if ($flash): ?><div class="flash flash-<?= e($flash[1]) ?>"><?= e($flash[0]) ?></div><?php endif; ?>
<h1><?= e($title) ?></h1>
<?php
}

/** Image picker that uploads straight away and stores the file URL in a hidden input. Needs assets/js/admin-fields.js. */
function render_image_field(string $name, string $value, string $label = '', string $hint = ''): void
{
    ?>
    <div class="img-field" data-upload="<?= e(url('admin/upload.php')) ?>" data-csrf="<?= e(csrf_token()) ?>">
      <?php if ($label !== ''): ?><span class="imf-label"><?= e($label) ?></span><?php endif; ?>
      <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
      <div class="imf-row">
        <span class="imf-preview"><img src="<?= e($value) ?>" alt="" <?= $value === '' ? 'hidden' : '' ?>></span>
        <button type="button" class="btn btn-sm btn-ghost imf-choose">Choose image</button>
        <button type="button" class="btn btn-sm btn-ghost imf-clear" <?= $value === '' ? 'hidden' : '' ?>>Remove</button>
      </div>
      <input type="file" accept="image/jpeg,image/png,image/webp" hidden>
      <?php if ($hint !== ''): ?><small class="muted"><?= e($hint) ?></small><?php endif; ?>
      <small class="imf-msg" role="status"></small>
    </div>
    <?php
}

function admin_foot(): void
{
    echo '</div><script src="' . url('assets/js/app.js') . '"></script><script src="' . url('assets/js/admin-fields.js') . '"></script></body></html>';
}
