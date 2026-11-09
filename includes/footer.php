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
<script src="<?= url('assets/js/app.js') ?>"></script>
<?= setting('code_body') ?>
</body>
</html>
