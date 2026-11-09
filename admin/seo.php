<?php
require_once __DIR__ . '/_layout.php';

$textKeys = ['seo_title_format', 'seo_home_title', 'seo_home_desc', 'seo_property_title', 'seo_property_desc', 'seo_gsc', 'seo_bing',
             'biz_type', 'biz_name', 'biz_street', 'biz_city', 'biz_province', 'biz_postal', 'biz_country', 'biz_area', 'biz_price_range', 'currency'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($textKeys as $k) save_setting($k, mb_substr(trim((string)($_POST[$k] ?? '')), 0, 300));
    $img = trim((string)($_POST['seo_default_image'] ?? ''));
    save_setting('seo_default_image', preg_match('#^(https?://|/)[^\s"\'<>]*$#', $img) ? $img : '');
    save_setting('seo_noindex', isset($_POST['seo_noindex']) ? '1' : '0');
    // Custom code is printed as-is on every page, so it is saved exactly as typed (admin only).
    save_setting('code_head', (string)($_POST['code_head'] ?? ''));
    save_setting('code_body', (string)($_POST['code_body'] ?? ''));
    flash('SEO settings saved.');
    redirect('admin/seo.php');
}

$v = fn(string $k, string $d = '') => e(setting($k, $d));
$latest = db()->query('SELECT * FROM properties ORDER BY id DESC LIMIT 1')->fetch() ?: [
    'id' => 0, 'title' => 'Bright 3-bed family home near the lake', 'address' => '12 Elm Street', 'city' => 'Caledon', 'province' => 'ON', 'postal' => 'L7C 1A1',
    'mls_number' => 'W1234567', 'price' => 899000, 'status' => 'for_sale', 'type' => 'house', 'bedrooms' => 3, 'bathrooms' => 2.5, 'sqft' => 2400,
    'description' => 'Sun-filled open-concept home with a renovated kitchen, finished basement and a large fenced yard.',
];
$sampleTokens = seo_property_tokens($latest);
$host = parse_url(site_origin(), PHP_URL_HOST) . url('property.php');
admin_head('SEO');
?>
<form method="post" class="panel" id="seo-form">
  <?= csrf_field() ?>
  <p class="muted">Defaults are generated for you. Every property and page can override them in its own editor. Templates use <code>{tokens}</code>: they are filled in for each listing, and empty ones disappear cleanly.</p>

  <h2>Home page</h2>
  <div class="form-grid">
    <label class="full">Title<input name="seo_home_title" id="s-home-t" value="<?= $v('seo_home_title') ?>" placeholder="<?= e(SEO_DEFAULTS['seo_home_title']) ?>"></label>
    <label class="full">Description<textarea name="seo_home_desc" id="s-home-d" rows="2" placeholder="<?= e(SEO_DEFAULTS['seo_home_desc']) ?>"><?= $v('seo_home_desc') ?></textarea></label>
  </div>
  <div class="seo-box" data-kind="home" data-title-input="#s-home-t" data-desc-input="#s-home-d" data-title-default="<?= e(SEO_DEFAULTS['seo_home_title']) ?>" data-desc-default="<?= e(SEO_DEFAULTS['seo_home_desc']) ?>"
       data-static="<?= e(json_encode(['site' => setting('site_name', 'Your Name Realty')])) ?>" data-sample="<?= e(json_encode(seo_site_tokens())) ?>" data-url="<?= e(parse_url(site_origin(), PHP_URL_HOST) . url()) ?>">
    <?php include __DIR__ . '/_serp.php'; ?>
  </div>

  <h2>Pages and other screens</h2>
  <label>Title format <span class="muted" style="font-weight:400">({page} is the page's own title)</span>
    <input name="seo_title_format" value="<?= $v('seo_title_format') ?>" placeholder="<?= e(SEO_DEFAULTS['seo_title_format']) ?>"></label>

  <h2>Property defaults</h2>
  <p class="small muted">Used for every listing that doesn't have its own SEO title or description. The preview uses <?= !empty($latest['id']) ? 'your newest listing' : 'a sample listing' ?>.</p>
  <div class="form-grid">
    <label class="full">Title template<input name="seo_property_title" id="s-prop-t" value="<?= $v('seo_property_title') ?>" placeholder="<?= e(SEO_DEFAULTS['seo_property_title']) ?>"></label>
    <label class="full">Description template<textarea name="seo_property_desc" id="s-prop-d" rows="3" placeholder="<?= e(SEO_DEFAULTS['seo_property_desc']) ?>"><?= $v('seo_property_desc') ?></textarea></label>
  </div>
  <div class="seo-box" data-kind="property" data-title-input="#s-prop-t" data-desc-input="#s-prop-d" data-title-default="<?= e(SEO_DEFAULTS['seo_property_title']) ?>" data-desc-default="<?= e(SEO_DEFAULTS['seo_property_desc']) ?>"
       data-static="{}" data-sample="<?= e(json_encode($sampleTokens)) ?>" data-url="<?= e($host . '?id=' . (int)$latest['id']) ?>">
    <?php include __DIR__ . '/_serp.php'; ?>
  </div>
  <details class="tokens"><summary>Available tokens</summary>
    <ul><?php foreach (SEO_TOKEN_HELP as $tk => $help) echo '<li><code>' . e($tk) . '</code> <span class="muted">' . e($help) . '</span></li>'; ?></ul>
  </details>

  <h2>Social sharing</h2>
  <?php render_image_field('seo_default_image', setting('seo_default_image'), 'Default share image', 'Shown when a page is shared on Facebook, LinkedIn, iMessage, etc. and the page has no image of its own. 1200 x 630 works best. A listing uses its own cover photo.'); ?>

  <h2>Business details <span class="muted" style="font-weight:400;font-size:.9rem">(helps Google show your business info)</span></h2>
  <div class="form-grid">
    <label>Business type<select name="biz_type"><?php foreach (['RealEstateAgent' => 'Real estate agent', 'LocalBusiness' => 'Local business', 'Organization' => 'Organization'] as $k => $l) echo '<option value="' . e($k) . '"' . (setting('biz_type', 'RealEstateAgent') === $k ? ' selected' : '') . '>' . e($l) . '</option>'; ?></select></label>
    <label>Business name<input name="biz_name" value="<?= $v('biz_name') ?>" placeholder="<?= e(setting('site_name', 'Your Name Realty')) ?>"></label>
    <label>Street address<input name="biz_street" value="<?= $v('biz_street') ?>"></label>
    <label>City<input name="biz_city" value="<?= $v('biz_city') ?>"></label>
    <label>Province / state<input name="biz_province" value="<?= $v('biz_province', 'ON') ?>"></label>
    <label>Postal code<input name="biz_postal" value="<?= $v('biz_postal') ?>"></label>
    <label>Country code<input name="biz_country" value="<?= $v('biz_country', 'CA') ?>" maxlength="2"></label>
    <label>Price range<input name="biz_price_range" value="<?= $v('biz_price_range') ?>" placeholder="$$$"></label>
    <label class="full">Areas served (comma separated)<input name="biz_area" value="<?= $v('biz_area') ?>" placeholder="Toronto, Mississauga, Caledon"></label>
    <label>Currency for listings<select name="currency"><?php foreach (['CAD', 'USD'] as $c) echo '<option' . (setting('currency', 'CAD') === $c ? ' selected' : '') . '>' . $c . '</option>'; ?></select></label>
  </div>

  <h2>Search engine tools</h2>
  <div class="form-grid">
    <label>Google Search Console verification code<input name="seo_gsc" value="<?= $v('seo_gsc') ?>" placeholder="content value only"></label>
    <label>Bing Webmaster verification code<input name="seo_bing" value="<?= $v('seo_bing') ?>"></label>
  </div>
  <p class="small muted">Your sitemap: <a href="<?= e(url('sitemap.php')) ?>" target="_blank">sitemap.php</a> &middot; robots file: <a href="<?= e(url('robots.php')) ?>" target="_blank">robots.php</a> (served as /robots.txt on Apache). Submit the sitemap in Google Search Console.</p>
  <label class="check"><input type="checkbox" name="seo_noindex" value="1" <?= setting('seo_noindex') === '1' ? 'checked' : '' ?>> Ask search engines not to index this site (use while building or for a staging copy)</label>

  <h2>Tracking and custom code</h2>
  <p class="small muted">For Google Analytics, Meta Pixel, chat widgets, cookie banners and similar. Pasted exactly as written, so only use code from a source you trust.</p>
  <label>Inside &lt;head&gt;<textarea name="code_head" rows="5" style="font-family:monospace" spellcheck="false"><?= e(setting('code_head')) ?></textarea></label>
  <label>Before &lt;/body&gt;<textarea name="code_body" rows="4" style="font-family:monospace" spellcheck="false"><?= e(setting('code_body')) ?></textarea></label>

  <div class="actions"><button class="btn" type="submit">Save SEO settings</button></div>
</form>
<script src="<?= url('assets/js/admin-seo.js') ?>"></script>
<?php admin_foot(); ?>
