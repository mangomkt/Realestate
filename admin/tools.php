<?php
require_once __DIR__ . '/_layout.php';

/** Settings that identify one particular agent; left out of a template unless asked for. */
function tools_identity_key(string $k): bool
{
    return in_array($k, ['site_name', 'tagline', 'agent_line', 'phone', 'contact_email', 'address', 'disclaimer', 'logo', 'logo_dark', 'favicon',
        'idx_title', 'idx_embed', 'home_seeded', 'seo_default_image', 'seo_gsc', 'seo_bing', 'site_url', 'analytics_ga4', 'analytics_plausible'], true) || str_starts_with($k, 'social_') || str_contains($k, 'verification') || str_contains($k, 'analytics');
}

if (($_GET['do'] ?? '') === 'backup') {
    if (!class_exists('ZipArchive')) { flash('Backups need the PHP zip extension, which is not enabled on this host.', 'err'); redirect('admin/tools.php'); }
    $tmp = tempnam(sys_get_temp_dir(), 'bk');
    $z = new ZipArchive();
    $z->open($tmp, ZipArchive::OVERWRITE);
    $db = DB_FILE;
    db()->exec('PRAGMA wal_checkpoint(TRUNCATE)');
    if (is_file($db)) $z->addFile($db, 'data/site.sqlite');
    $up = realpath(__DIR__ . '/../uploads');
    if ($up) foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($up, FilesystemIterator::SKIP_DOTS)) as $f)
        if ($f->isFile() && $f->getFilename() !== '.htaccess') $z->addFile($f->getPathname(), 'uploads/' . substr($f->getPathname(), strlen($up) + 1));
    $z->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="site-backup-' . date('Y-m-d') . '.zip"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp); unlink($tmp); exit;
}

if (($_GET['do'] ?? '') === 'export') {
    $withId = !empty($_GET['identity']);
    $withPages = !empty($_GET['pages']);
    $set = [];
    foreach (db()->query('SELECT k, v FROM settings') as $r) {
        if (str_starts_with($r['k'], 'admin_') || in_array($r['k'], ['setup_dismissed', 'alerts_key'], true)) continue;
        if (!$withId && tools_identity_key($r['k'])) continue;
        $set[$r['k']] = $r['v'];
    }
    $out = ['format' => 're-template', 'version' => 1, 'exported' => date('c'), 'settings' => $set,
        'home' => array_map(fn($r) => ['type' => $r['type'], 'enabled' => (int)$r['enabled'], 'config' => json_decode($r['config'], true) ?: new stdClass()],
            db()->query('SELECT * FROM home_sections WHERE page_id = 0 ORDER BY sort, id')->fetchAll()),
        'nav' => db()->query('SELECT id, location, label, type, target, sort, visible, new_tab, parent_id FROM nav_items ORDER BY location, sort, id')->fetchAll()];
    if ($withPages) {
        $out['pages'] = db()->query('SELECT id, title, slug, content, meta_title, meta_desc, status FROM pages ORDER BY id')->fetchAll();
        foreach ($out['pages'] as &$pg) {
            $q = db()->prepare('SELECT type, enabled, config FROM home_sections WHERE page_id = ? ORDER BY sort, id');
            $q->execute([$pg['id']]);
            $pg['sections'] = array_map(fn($r) => ['type' => $r['type'], 'enabled' => (int)$r['enabled'], 'config' => json_decode($r['config'], true) ?: new stdClass()], $q->fetchAll());
            unset($pg['id']);
        }
        unset($pg);
    }
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="site-template-' . date('Y-m-d') . '.json"');
    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['demo_load']) || isset($_POST['demo_clear']))) {
    csrf_check();
    if (isset($_POST['demo_clear'])) { demo_clear(); flash('Sample content removed.'); }
    else {
        try { flash(($n = demo_load()) ? "Added $n sample listings and 3 pages. Remember to clear them before you go live." : 'Sample content is already loaded.'); }
        catch (Throwable $ex) { flash('Could not add sample content: ' . $ex->getMessage(), 'err'); }
    }
    redirect('admin/tools.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_key'])) {
    csrf_check();
    save_setting('alerts_key', bin2hex(random_bytes(16)));
    flash('New alert key created.');
    redirect('admin/tools.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f = $_FILES['template'] ?? null;
    $raw = ($f && $f['error'] === UPLOAD_ERR_OK && $f['size'] < 3_000_000) ? file_get_contents($f['tmp_name']) : '';
    $d = json_decode($raw, true);
    if (!is_array($d) || ($d['format'] ?? '') !== 're-template') { flash('That file is not a site template.', 'err'); redirect('admin/tools.php'); }
    $pdo = db();
    $pdo->beginTransaction();
    foreach ((array)($d['settings'] ?? []) as $k => $v) {
        if (!is_string($k) || !is_scalar($v) || !preg_match('/^[a-z0-9_]{1,60}$/', $k) || str_starts_with($k, 'admin_') || $k === 'alerts_key') continue;
        save_setting($k, (string)$v);
    }
    if (isset($d['home']) && is_array($d['home'])) {
        $pdo->exec('DELETE FROM home_sections WHERE page_id = 0');
        $ins = $pdo->prepare('INSERT INTO home_sections (type, sort, enabled, config, page_id) VALUES (?,?,?,?,0)');
        foreach (array_values($d['home']) as $i => $s) {
            if (!is_array($s) || !isset(HOME_TYPES[$s['type'] ?? ''])) continue;
            $ins->execute([$s['type'], $i, !empty($s['enabled']) ? 1 : 0, json_encode(home_clean_config($s['type'], (array)($s['config'] ?? [])))]);
        }
        save_setting('home_seeded', '1');
    }
    if (isset($d['nav']) && is_array($d['nav'])) {
        $pdo->exec('DELETE FROM nav_items');
        $ins = $pdo->prepare('INSERT INTO nav_items (id, location, label, type, target, sort, visible, new_tab, parent_id) VALUES (?,?,?,?,?,?,?,?,?)');
        foreach ($d['nav'] as $n) {
            if (!is_array($n) || !isset(NAV_LOCATIONS[$n['location'] ?? ''])) continue;
            $ins->execute([(int)($n['id'] ?? 0) ?: null, $n['location'], mb_substr((string)($n['label'] ?? ''), 0, 80), mb_substr((string)($n['type'] ?? 'link'), 0, 20),
                mb_substr((string)($n['target'] ?? ''), 0, 300), (int)($n['sort'] ?? 0), (int)($n['visible'] ?? 1), (int)($n['new_tab'] ?? 0), (int)($n['parent_id'] ?? 0)]);
        }
    }
    if (isset($d['pages']) && is_array($d['pages'])) {
        $ins = $pdo->prepare('INSERT INTO pages (title, slug, content, meta_title, meta_desc, status) VALUES (?,?,?,?,?,?)
            ON CONFLICT(slug) DO UPDATE SET title = excluded.title, content = excluded.content, meta_title = excluded.meta_title, meta_desc = excluded.meta_desc, status = excluded.status');
        foreach ($d['pages'] as $p) {
            if (!is_array($p) || ($slug = slugify((string)($p['slug'] ?? ''))) === '') continue;
            $ins->execute([mb_substr((string)($p['title'] ?? $slug), 0, 200), $slug, sanitize_html((string)($p['content'] ?? '')),
                mb_substr((string)($p['meta_title'] ?? ''), 0, 200), mb_substr((string)($p['meta_desc'] ?? ''), 0, 400), ($p['status'] ?? '') === 'draft' ? 'draft' : 'published']);
            if (isset($p['sections']) && is_array($p['sections'])) {
                $pid = (int)$pdo->query('SELECT id FROM pages WHERE slug = ' . $pdo->quote($slug))->fetchColumn();
                $pdo->prepare('DELETE FROM home_sections WHERE page_id = ?')->execute([$pid]);
                $si = $pdo->prepare('INSERT INTO home_sections (type, sort, enabled, config, page_id) VALUES (?,?,?,?,?)');
                foreach (array_values($p['sections']) as $n => $sec) {
                    if (!is_array($sec) || !isset(HOME_TYPES[$sec['type'] ?? ''])) continue;
                    $si->execute([$sec['type'], $n, !empty($sec['enabled']) ? 1 : 0, json_encode(home_clean_config($sec['type'], (array)($sec['config'] ?? []))), $pid]);
                }
            }
        }
    }
    $pdo->commit();
    flash('Template imported.');
    redirect('admin/tools.php');
}

admin_head('Tools');
$ak = setting('alerts_key');
?>
<div class="panel" style="margin-bottom:18px">
  <h2>Sample content</h2>
  <p class="muted">Fills the site with 8 illustrated sample listings (including two "sold") and 3 sample pages, so you can see how everything looks. Sample photos are simple drawings; replace them with real photos. Your own listings and pages are never touched by this, and everything sample can be removed in one click.</p>
  <form method="post"><?= csrf_field() ?>
    <?php if (demo_present()): ?><button class="btn btn-ghost" name="demo_clear" value="1" onclick="return confirm('Remove all sample listings, photos and pages?')">Remove sample content</button>
    <?php else: ?><button class="btn" name="demo_load" value="1">Add sample content</button><?php endif; ?>
  </form>
</div>
<div class="panel">
  <h2>New-listing alerts</h2>
  <p class="muted">Visitors can save searches and get emailed when matching listings appear. Set it up once: add a scheduled job (cron) on your host, hourly or daily, running:</p>
  <?php if (setting('site_url') === ''): ?><div class="flash flash-info">Set your Website address in <a href="settings.php">Settings</a> first, so links in alert emails are complete.</div><?php endif; ?>
  <p><code>php <?= e(realpath(__DIR__ . '/../send_alerts.php')) ?></code></p>
  <p class="muted">If your host only offers web-address cron jobs, use this address instead:</p>
  <p><code><?= $ak !== '' ? e(abs_url(url('send_alerts.php')) . '?key=' . $ak) : 'Create a key first' ?></code></p>
  <form method="post"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" name="new_key" value="1"><?= $ak === '' ? 'Create web key' : 'Replace web key' ?></button></form>
</div>
<div class="panel" style="margin-top:18px">
  <h2>Back up</h2>
  <p class="muted">Download everything (database and uploaded photos/videos) as one zip. Do this before big changes and keep a copy off the server.</p>
  <a class="btn" href="?do=backup">Download backup</a>
</div>
<div class="panel" style="margin-top:18px">
  <h2>Export as a template</h2>
  <p class="muted">Saves your look and layout (theme, home page, menus, SEO defaults) as a file you can import into another copy of this site. Properties, leads and users are never included.</p>
  <form method="get" class="actions">
    <input type="hidden" name="do" value="export">
    <label class="check"><input type="checkbox" name="pages" value="1"> Include pages</label>
    <label class="check"><input type="checkbox" name="identity" value="1"> Include name, contact details, logo and social links</label>
    <button class="btn" type="submit">Download template</button>
  </form>
</div>
<div class="panel" style="margin-top:18px">
  <h2>Import a template</h2>
  <p class="muted">Replaces the theme, home page and menus with those in the file (pages with the same URL name are overwritten). Download a backup first.</p>
  <form method="post" enctype="multipart/form-data" onsubmit="return confirm('Import this template and replace your current look and layout?')">
    <?= csrf_field() ?>
    <input type="file" name="template" accept="application/json,.json" required>
    <button class="btn btn-ghost" type="submit">Import template</button>
  </form>
</div>
<?php admin_foot(); ?>
