<?php
require_once __DIR__ . '/bootstrap.php';
$user = current_user();
$siteName = setting('site_name', 'Your Name Realty');
$flash = flash();
?>
<?php $ts = theme_settings(); ?>
<!doctype html>
<html lang="en" data-default="<?= e($ts['mode']) ?>"<?= $ts['mode'] === 'dark' ? ' data-theme="dark"' : '' ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
<?php
  $seoData = $seo ?? [];
  if (!empty($isHome)) $seoData += seo_home() + ['jsonld' => [seo_home_ld()]];
  if (!isset($seoData['title'])) $seoData['title'] = ($pageTitle ?? '') !== '' ? seo_page_title((string)$pageTitle) : seo_home()['title'];
  if (!isset($seoData['desc'])) $seoData['desc'] = $metaDesc ?? seo_home()['desc'];
  seo_head($seoData);
?>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <style><?= theme_css() ?></style>
  <script>try{var t=localStorage.getItem('re_theme');if(t==='light'||t==='dark')document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>
<?= setting('code_head') ?>
</head>
<body class="<?= e(theme_body_class()) ?>">
<header class="site-header">
  <div class="wrap bar">
    <?php $logoL = setting('logo'); $logoD = setting('logo_dark') ?: $logoL; ?>
    <a class="logo<?= $logoL !== '' ? ' has-img' : '' ?>" href="<?= url() ?>">
      <?php if ($logoL !== ''): ?>
        <img class="logo-l" src="<?= e($logoL) ?>" alt="<?= e($siteName) ?>"><img class="logo-d" src="<?= e($logoD) ?>" alt="" aria-hidden="true">
      <?php else: ?><?= e($siteName) ?><?php endif; ?>
    </a>
    <button class="nav-toggle" aria-label="Menu" aria-expanded="false" aria-controls="nav">&#9776;</button>
    <nav id="nav" class="nav">
      <?php foreach (nav_items('header') as $it):
        if ($it['children']):
          $childCurrent = (bool)array_filter($it['children'], fn($c) => $c['current']); ?>
        <div class="nav-dd<?= $childCurrent || $it['current'] ? ' has-current' : '' ?>">
          <?php if ($it['href'] !== null): ?>
            <a class="dd-link" href="<?= e($it['href']) ?>"<?= $it['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?><?= $it['current'] ? ' aria-current="page"' : '' ?>><?= e($it['label']) ?></a>
            <button type="button" class="dd-toggle" aria-expanded="false" aria-label="Show <?= e($it['label']) ?> submenu"><span aria-hidden="true">&#9662;</span></button>
          <?php else: ?>
            <button type="button" class="dd-link dd-btn dd-toggle" aria-expanded="false"><?= e($it['label']) ?> <span aria-hidden="true">&#9662;</span></button>
          <?php endif; ?>
          <div class="dd-menu">
            <?php foreach ($it['children'] as $c): ?>
              <a href="<?= e($c['href']) ?>"<?= $c['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?><?= $c['current'] ? ' aria-current="page"' : '' ?>><?= e($c['label']) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= e($it['href']) ?>"<?= $it['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?><?= $it['current'] ? ' aria-current="page"' : '' ?>><?= e($it['label']) ?></a>
      <?php endif; endforeach; ?>
      <?php if ($ts['toggle']): ?><button type="button" class="theme-toggle" id="theme-toggle" hidden aria-pressed="false" aria-label="Switch to dark mode"><span aria-hidden="true"></span></button><?php endif; ?>
      <?php if ($user): ?>
        <a href="<?= url('searches.php') ?>">Saved searches</a>
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
