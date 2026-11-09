</main>
<footer class="site-footer">
  <div class="wrap foot-grid">
    <div>
      <strong><?= e(setting('site_name', 'Your Name Realty')) ?></strong>
      <p class="muted"><?= e(setting('tagline')) ?></p>
      <?php if (setting('phone')): ?><p><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('phone'))) ?>"><?= e(setting('phone')) ?></a></p><?php endif; ?>
      <?php if (setting('contact_email')): ?><p><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></p><?php endif; ?>
      <?php if (setting('address')): ?><p class="muted"><?= nl2br(e(setting('address'))) ?></p><?php endif; ?>
    </div>
    <?php if ($fl = nav_items_flat('footer')): ?>
    <div>
      <strong>Quick links</strong>
      <ul class="foot-links">
        <?php foreach ($fl as $it): ?>
          <li><a href="<?= e($it['href']) ?>"<?= $it['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($it['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
    <div>
      <strong>Follow</strong>
      <ul class="social">
        <?php foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn', 'x' => 'X', 'tiktok' => 'TikTok'] as $k => $label):
            $u = setting('social_' . $k);
            if ($u !== '' && preg_match('~^https?://~i', $u)): ?>
          <li><a href="<?= e($u) ?>" target="_blank" rel="noopener"><?= $label ?></a></li>
        <?php endif; endforeach; ?>
      </ul>
    </div>
    <div class="muted small">
      <?php if (setting('agent_line') !== ''): ?><p class="agent-line"><strong><?= e(setting('agent_line')) ?></strong></p><?php endif; ?>
      <?= e(setting('disclaimer', 'Listing information is deemed reliable but not guaranteed.')) ?>
    </div>
  </div>
  <div class="wrap small muted copy">&copy; <?= date('Y') ?> <?= e(setting('site_name', 'Your Name Realty')) ?></div>
</footer>
<?php
$ga = setting('analytics_ga4'); $pl = setting('analytics_plausible'); $banner = setting('cookie_banner') === '1';
if ($ga !== '' || $pl !== '' || $banner):
?>
<?php if ($banner): ?>
<div class="cookie-bar" id="cookie-bar" role="region" aria-label="Cookie notice" hidden>
  <p><?= e(setting('cookie_text') ?: 'We use cookies to understand how the site is used.') ?></p>
  <div><button type="button" class="btn btn-sm" id="cookie-yes">Accept</button> <button type="button" class="btn btn-sm btn-ghost" id="cookie-no">Decline</button></div>
</div>
<?php endif; ?>
<script>
(function(){
  var need=<?= $banner ? 'true' : 'false' ?>, ga=<?= json_encode($ga) ?>, pl=<?= json_encode($pl) ?>;
  function load(){
    if(pl){var s=document.createElement('script');s.defer=true;s.setAttribute('data-domain',pl);s.src='https://plausible.io/js/script.js';document.head.appendChild(s);}
    if(ga){var g=document.createElement('script');g.async=true;g.src='https://www.googletagmanager.com/gtag/js?id='+encodeURIComponent(ga);document.head.appendChild(g);
      window.dataLayer=window.dataLayer||[];window.gtag=function(){dataLayer.push(arguments)};gtag('js',new Date());gtag('config',ga,{anonymize_ip:true});}
  }
  var c=null;try{c=localStorage.getItem('re_consent')}catch(e){}
  if(!need||c==='yes'){load();return;}
  if(c==='no')return;
  var bar=document.getElementById('cookie-bar');if(!bar)return;bar.hidden=false;
  function set(v){try{localStorage.setItem('re_consent',v)}catch(e){}bar.hidden=true;if(v==='yes')load();}
  document.getElementById('cookie-yes').onclick=function(){set('yes')};document.getElementById('cookie-no').onclick=function(){set('no')};
})();
</script>
<?php endif; ?>
<script src="<?= url('assets/js/app.js') ?>"></script>
<?= setting('code_body') ?>
</body>
</html>
