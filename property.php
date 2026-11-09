<?php
require_once __DIR__ . '/includes/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM properties WHERE id = ?');
$st->execute([$id]);
$p = $st->fetch();
if (!$p) { http_response_code(404); $pageTitle = 'Not found'; $seo = ['robots' => 'noindex']; include __DIR__ . '/includes/header.php'; echo '<div class="wrap section"><h1>Property not found</h1><p><a href="' . url('listings.php') . '">Back to listings</a></p></div>'; include __DIR__ . '/includes/footer.php'; exit; }

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
    $showing = '';
    $sd = trim($_POST['showing_date'] ?? '');
    if ($sd !== '') {
        $dt = DateTime::createFromFormat('!Y-m-d', $sd);
        if (!$dt || $dt->format('Y-m-d') !== $sd || $dt < new DateTime('today')) $errors[] = 'Please choose a showing date that is today or later.';
        else $showing = $sd . ' (' . (['morning' => 'morning', 'afternoon' => 'afternoon', 'evening' => 'evening'][$_POST['showing_time'] ?? ''] ?? 'any time') . ')';
    }
    if (!$errors) {
        save_lead(['user_id' => $user['id'] ?? null, 'property_id' => $id, 'name' => $name, 'email' => $email,
            'phone' => $phone, 'message' => $msg, 'source' => $showing !== '' ? 'showing request' : 'property inquiry', 'showing_at' => $showing]);
        flash('Thanks! I will be in touch shortly.');
        redirect('property.php?id=' . $id);
    }
}

$images = property_images($id);
$embed = $p['video_url'] ? embed_url($p['video_url']) : null;
$seo = seo_for_property($p, $images);
$seo['jsonld'] = [
    seo_property_ld($p, $images, $seo),
    seo_breadcrumb_ld([['Home', url()], ['Listings', url('listings.php')], [$p['title'], property_url($p)]]),
];
$features = array_filter(array_map('trim', explode(',', (string)$p['features'])));
$extras = array_filter([
    'Year built' => $p['year_built'] ? (string)$p['year_built'] : '',
    'Lot size' => $p['lot_size'], 'Parking' => $p['parking'],
    'Property taxes' => $p['taxes'] ? money($p['taxes']) . ' / year' : '',
    'Maintenance fee' => $p['maintenance_fee'] ? money($p['maintenance_fee']) . ' / month' : '',
]);
$mapLink = trim($p['address'] . ' ' . $p['city'] . ' ' . $p['province']) !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(trim($p['address'] . ', ' . $p['city'] . ', ' . $p['province'] . ' ' . $p['postal'])) : '';
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

      <?php if ($p['open_house']): ?><p class="open-house"><strong>Open house</strong> <?= e($p['open_house']) ?></p><?php endif; ?>
      <?php if ($extras): ?>
        <dl class="extras"><?php foreach ($extras as $k => $val): ?><div><dt><?= e($k) ?></dt><dd><?= e($val) ?></dd></div><?php endforeach; ?></dl>
      <?php endif; ?>
      <?php if ($features): ?><ul class="chips"><?php foreach ($features as $ft): ?><li><?= e($ft) ?></li><?php endforeach; ?></ul><?php endif; ?>
      <div class="actions" style="margin:14px 0">
        <?php if ($p['virtual_tour_url']): ?><a class="btn btn-sm" href="<?= e($p['virtual_tour_url']) ?>" target="_blank" rel="noopener">Take the virtual tour</a><?php endif; ?>
        <?php if ($mapLink): ?><a class="btn btn-sm btn-ghost" href="<?= e($mapLink) ?>" target="_blank" rel="noopener">View on map</a><?php endif; ?>
      </div>

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

    <?php if (setting('mortgage_calc', '1') === '1' && (int)$p['price'] > 0 && $p['status'] !== 'for_rent'): ?>
      <section class="panel mortgage" id="mortgage" data-price="<?= (int)$p['price'] ?>" data-ca="<?= setting('currency', 'CAD') === 'CAD' ? 1 : 0 ?>">
        <h3>Mortgage estimate</h3>
        <div class="form-grid">
          <label>Purchase price<input id="m-price" inputmode="numeric" value="<?= (int)$p['price'] ?>"></label>
          <label>Down payment (%)<input id="m-down" type="number" min="0" max="100" step="1" value="20"></label>
          <label>Interest rate (%)<input id="m-rate" type="number" min="0" max="30" step="0.05" value="<?= e(setting('mortgage_rate', '5')) ?>"></label>
          <label>Amortization<select id="m-years"><?php foreach ([10, 15, 20, 25, 30] as $y) echo '<option' . ($y === 25 ? ' selected' : '') . ">$y</option>"; ?></select></label>
        </div>
        <div class="mortgage-result"><span>Estimated monthly payment</span><strong id="m-pay">&nbsp;</strong>
          <small id="m-note">Principal and interest only; excludes taxes, insurance and fees. An estimate, not a quote.</small></div>
      </section>
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
          <fieldset class="showing"><legend>Want to see it? (optional)</legend>
            <label>Preferred date<input type="date" name="showing_date" min="<?= date('Y-m-d') ?>" value="<?= e($_POST['showing_date'] ?? '') ?>"></label>
            <label>Time of day<select name="showing_time"><option value="any">Any time</option><option value="morning">Morning</option><option value="afternoon">Afternoon</option><option value="evening">Evening</option></select></label>
          </fieldset>
          <button class="btn block" type="submit">Request info / book a showing</button>
        </form>
      </div>
    </aside>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
