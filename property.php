<?php
require_once __DIR__ . '/includes/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM properties WHERE id = ?');
$st->execute([$id]);
$p = $st->fetch();
if (!$p) { http_response_code(404); $pageTitle = 'Not found'; include __DIR__ . '/includes/header.php'; echo '<div class="wrap section"><h1>Property not found</h1><p><a href="' . url('listings.php') . '">Back to listings</a></p></div>'; include __DIR__ . '/includes/footer.php'; exit; }

gate('p' . $id);

$user = current_user();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $msg = trim($_POST['message'] ?? '');
    if (($_POST['website'] ?? '') !== '') { redirect('property.php?id=' . $id); } // honeypot
    if ($name === '') $errors[] = 'Please enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
    if (!$errors) {
        save_lead(['user_id' => $user['id'] ?? null, 'property_id' => $id, 'name' => $name, 'email' => $email,
            'phone' => $phone, 'message' => $msg, 'source' => 'property inquiry']);
        flash('Thanks! I will be in touch shortly.');
        redirect('property.php?id=' . $id);
    }
}

$images = property_images($id);
$embed = $p['video_url'] ? embed_url($p['video_url']) : null;
$pageTitle = $p['title'];
$metaDesc = mb_substr(strip_tags((string)$p['description']), 0, 155);
include __DIR__ . '/includes/header.php';
?>
<div class="wrap section">
  <p><a href="<?= url('listings.php') ?>">&larr; All listings</a></p>
  <div class="detail-head">
    <div>
      <span class="badge badge-<?= e($p['status']) ?> inline"><?= e(STATUSES[$p['status']] ?? '') ?></span>
      <h1><?= e($p['title']) ?></h1>
      <p class="muted"><?= e(trim($p['address'] . ', ' . $p['city'] . ', ' . $p['province'] . ' ' . $p['postal'], ', ')) ?></p>
    </div>
    <div class="price big"><?= money($p['price']) ?><?= $p['status'] === 'for_rent' ? '<small>/mo</small>' : '' ?></div>
  </div>

  <div class="detail-grid">
    <div>
      <div class="gallery">
        <img id="main-photo" src="<?= e($images ? url('uploads/' . $images[0]['filename']) : url('assets/img/placeholder.svg')) ?>" alt="<?= e($images ? image_alt($p, $images[0], 1) : $p['title']) ?>">
        <?php if (count($images) > 1): ?>
        <div class="thumbs">
          <?php foreach ($images as $n => $im): $alt = image_alt($p, $im, $n + 1); ?>
            <button type="button" data-full="<?= e(url('uploads/' . $im['filename'])) ?>" data-alt="<?= e($alt) ?>" aria-label="<?= e($alt) ?>"><img src="<?= e(url('uploads/' . $im['filename'])) ?>" alt="<?= e($alt) ?>" loading="lazy"></button>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <ul class="facts big-facts">
        <li><strong><?= (int)$p['bedrooms'] ?></strong> Bedrooms</li>
        <li><strong><?= rtrim(rtrim(number_format((float)$p['bathrooms'], 1), '0'), '.') ?></strong> Bathrooms</li>
        <?php if ($p['sqft']): ?><li><strong><?= number_format((int)$p['sqft']) ?></strong> Sq ft</li><?php endif; ?>
        <li><strong><?= e(TYPES[$p['type']] ?? '') ?></strong> Type</li>
        <?php if ($p['mls_number']): ?><li><strong><?= e($p['mls_number']) ?></strong> MLS&reg; #</li><?php endif; ?>
      </ul>

      <h2>About this property</h2>
      <div class="prose"><?= nl2br(e($p['description'])) ?></div>

      <?php if ($p['video_file'] || $embed): ?>
        <h2>Video tour</h2>
        <div class="video">
          <?php if ($p['video_file']): ?>
            <video controls preload="metadata" playsinline src="<?= e(url('uploads/' . $p['video_file'])) ?>"></video>
          <?php else: ?>
            <iframe src="<?= e($embed) ?>" title="Video tour" allowfullscreen loading="lazy"
                    allow="accelerometer; encrypted-media; gyroscope; picture-in-picture"></iframe>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <aside class="side">
      <div class="panel">
        <h3>Interested in this property?</h3>
        <?php foreach ($errors as $er): ?><div class="flash flash-err"><?= e($er) ?></div><?php endforeach; ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
          <label>Name<input name="name" required value="<?= e($_POST['name'] ?? $user['name'] ?? '') ?>"></label>
          <label>Email<input type="email" name="email" required value="<?= e($_POST['email'] ?? $user['email'] ?? '') ?>"></label>
          <label>Phone<input type="tel" name="phone" value="<?= e($_POST['phone'] ?? $user['phone'] ?? '') ?>"></label>
          <label>Message<textarea name="message" rows="4"><?= e($_POST['message'] ?? 'I would like more information about ' . $p['title'] . '.') ?></textarea></label>
          <button class="btn block" type="submit">Request info / book a showing</button>
        </form>
      </div>
    </aside>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
