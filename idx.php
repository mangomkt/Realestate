<?php
require_once __DIR__ . '/includes/bootstrap.php';
$code = trim(setting('idx_embed'));
if ($code === '') { http_response_code(404); redirect('listings.php'); }
gate('idx');
$pageTitle = setting('idx_title', 'Search All Listings');
include __DIR__ . '/includes/header.php';
?>
<div class="wrap section">
  <h1><?= e($pageTitle) ?></h1>
  <!-- IDX / MLS feed embed supplied by your board-approved IDX vendor (set in Admin > Settings). -->
  <div class="idx-embed"><?= $code ?></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
