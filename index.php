<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = '';
$isHome = true;
include __DIR__ . '/includes/header.php';
home_render_all();
include __DIR__ . '/includes/footer.php';
