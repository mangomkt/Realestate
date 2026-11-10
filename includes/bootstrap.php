<?php
// Core bootstrap: session, database, helpers.
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('DB_FILE', ROOT . '/data/site.sqlite');
define('UPLOAD_DIR', ROOT . '/uploads');

$docroot = rtrim(str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$rootNorm = str_replace('\\', '/', ROOT);
define('BASE_URL', $docroot !== '' && str_starts_with($rootNorm, $docroot) ? substr($rootNorm, strlen($docroot)) : '');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_start();
}
require_once __DIR__ . '/security.php';
security_headers();

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    if (!is_dir(dirname(DB_FILE))) mkdir(dirname(DB_FILE), 0755, true);
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (k TEXT PRIMARY KEY, v TEXT);
    CREATE TABLE IF NOT EXISTS admins (id INTEGER PRIMARY KEY, email TEXT UNIQUE, password_hash TEXT);
    CREATE TABLE IF NOT EXISTS login_attempts (id INTEGER PRIMARY KEY, scope TEXT, account TEXT, ip TEXT, ts INTEGER);
    CREATE INDEX IF NOT EXISTS idx_login_attempts ON login_attempts (scope, ts);
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY, name TEXT, email TEXT UNIQUE, phone TEXT, password_hash TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS properties (
        id INTEGER PRIMARY KEY, title TEXT NOT NULL, description TEXT, price INTEGER DEFAULT 0,
        status TEXT DEFAULT 'for_sale', type TEXT DEFAULT 'house',
        bedrooms INTEGER DEFAULT 0, bathrooms REAL DEFAULT 0, sqft INTEGER DEFAULT 0,
        address TEXT, city TEXT, province TEXT DEFAULT 'ON', postal TEXT, mls_number TEXT,
        featured INTEGER DEFAULT 0, video_file TEXT, video_url TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS property_images (
        id INTEGER PRIMARY KEY, property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
        filename TEXT NOT NULL, sort INTEGER DEFAULT 0);
    CREATE TABLE IF NOT EXISTS leads (
        id INTEGER PRIMARY KEY, user_id INTEGER, property_id INTEGER, name TEXT, email TEXT, phone TEXT,
        message TEXT, source TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS pages (
        id INTEGER PRIMARY KEY, title TEXT NOT NULL, slug TEXT NOT NULL UNIQUE, content TEXT,
        meta_title TEXT, meta_desc TEXT, status TEXT DEFAULT 'published',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS nav_items (
        id INTEGER PRIMARY KEY, location TEXT NOT NULL, label TEXT NOT NULL, type TEXT NOT NULL,
        target TEXT DEFAULT '', sort INTEGER DEFAULT 0, visible INTEGER DEFAULT 1, new_tab INTEGER DEFAULT 0);
    CREATE TABLE IF NOT EXISTS saved_searches (
        id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL, filters TEXT NOT NULL, last_property_id INTEGER DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS home_sections (
        id INTEGER PRIMARY KEY, type TEXT NOT NULL, sort INTEGER DEFAULT 0, enabled INTEGER DEFAULT 1, config TEXT DEFAULT '{}', page_id INTEGER DEFAULT 0);
    ");
    // First run: the home page starts with the original layout (hero, featured, latest, call to action).
    if (!$pdo->query("SELECT 1 FROM settings WHERE k = 'home_seeded'")->fetchColumn()) {
        $old = $pdo->query("SELECT v FROM settings WHERE k = 'hero_title'")->fetchColumn();
        home_seed_defaults($pdo, $old !== false && trim((string)$old) !== '' ? trim((string)$old) : null);
        $pdo->exec("INSERT INTO settings (k, v) VALUES ('home_seeded', '1')");
    }
    // First run: seed the menus with the original links (never re-seeded, so deleting items sticks).
    if (!$pdo->query("SELECT 1 FROM settings WHERE k = 'nav_seeded'")->fetchColumn()) {
        $ins = $pdo->prepare('INSERT INTO nav_items (location, label, type, target, sort) VALUES (?,?,?,?,?)');
        foreach ([['header', 'Home', 'home'], ['header', 'Listings', 'listings'], ['header', 'Search All MLS', 'idx'],
                  ['header', 'Contact', 'contact'], ['footer', 'Listings', 'listings'], ['footer', 'Contact', 'contact']] as $i => $r) {
            $ins->execute([$r[0], $r[1], 'builtin', $r[2], $i]);
        }
        $pdo->exec("INSERT INTO settings (k, v) VALUES ('nav_seeded', '1')");
    }
    // Migration: alt text for SEO on property photos.
    $cols = array_column($pdo->query('PRAGMA table_info(property_images)')->fetchAll(), 'name');
    if (!in_array('alt', $cols, true)) $pdo->exec("ALTER TABLE property_images ADD COLUMN alt TEXT DEFAULT ''");
    // Columns added after the first release (safe to run on every request: only missing ones are added).
    $newCols = [
        'nav_items'  => ['parent_id' => 'INTEGER DEFAULT 0', 'is_demo' => 'INTEGER DEFAULT 0'],
        'admins'     => ['totp_secret' => "TEXT DEFAULT ''", 'totp_enabled' => 'INTEGER DEFAULT 0', 'totp_last' => 'INTEGER DEFAULT 0',
                         'backup_codes' => "TEXT DEFAULT ''", 'last_login_at' => "TEXT DEFAULT ''", 'last_login_ip' => "TEXT DEFAULT ''"],
        'home_sections' => ['page_id' => 'INTEGER DEFAULT 0'],
        'leads'      => ['status' => "TEXT DEFAULT 'new'", 'notes' => "TEXT DEFAULT ''", 'showing_at' => "TEXT DEFAULT ''"],
        'pages'      => ['is_demo' => 'INTEGER DEFAULT 0', 'meta_noindex' => 'INTEGER DEFAULT 0', 'meta_image' => "TEXT DEFAULT ''"],
        'properties' => ['is_demo' => 'INTEGER DEFAULT 0', 'seo_title' => "TEXT DEFAULT ''", 'seo_desc' => "TEXT DEFAULT ''", 'seo_image' => "TEXT DEFAULT ''", 'seo_noindex' => 'INTEGER DEFAULT 0',
                         'year_built' => 'INTEGER DEFAULT 0', 'lot_size' => "TEXT DEFAULT ''", 'parking' => "TEXT DEFAULT ''",
                         'taxes' => 'INTEGER DEFAULT 0', 'maintenance_fee' => 'INTEGER DEFAULT 0', 'features' => "TEXT DEFAULT ''",
                         'virtual_tour_url' => "TEXT DEFAULT ''", 'open_house' => "TEXT DEFAULT ''"],
    ];
    foreach ($newCols as $table => $cols) {
        $have = array_column($pdo->query("PRAGMA table_info($table)")->fetchAll(), 'name');
        foreach ($cols as $col => $ddl) if (!in_array($col, $have, true)) $pdo->exec("ALTER TABLE $table ADD COLUMN $col $ddl");
    }
    return $pdo;
}

require_once __DIR__ . '/cms.php';
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/home.php';
require_once __DIR__ . '/home_admin.php';
require_once __DIR__ . '/search.php';
require_once __DIR__ . '/looks.php';
require_once __DIR__ . '/demo.php';

// ---------- helpers ----------
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path))); exit; }
function money($n): string { return '$' . number_format((float)$n); }

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT k, v FROM settings') as $r) $cache[$r['k']] = (string)$r['v'];
    }
    return $cache[$key] ?? $default;
}
function save_setting(string $key, string $value): void
{
    db()->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON CONFLICT(k) DO UPDATE SET v = excluded.v')->execute([$key, $value]);
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Invalid or expired form. Go back and try again.');
    }
}

function flash(?string $msg = null, string $type = 'ok'): ?array
{
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function current_user(): ?array
{
    if (empty($_SESSION['uid'])) return null;
    $s = db()->prepare('SELECT id, name, email, phone FROM users WHERE id = ?');
    $s->execute([$_SESSION['uid']]);
    return $s->fetch() ?: null;
}

function is_admin(): bool { return !empty($_SESSION['admin']); }
/** Marks the admin as signed in and records when and from where. */
function admin_signed_in(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['admin'] = $id;
    $_SESSION['admin_seen'] = time();
    unset($_SESSION['admin_pending']);
    db()->prepare("UPDATE admins SET last_login_at = datetime('now'), last_login_ip = ? WHERE id = ?")->execute([client_ip(), $id]);
}
const ADMIN_IDLE_SECONDS = 7200;
function require_admin(): void
{
    if (is_admin() && time() - (int)($_SESSION['admin_seen'] ?? 0) > ADMIN_IDLE_SECONDS) {
        unset($_SESSION['admin'], $_SESSION['admin_seen']);   // signed out after 2 hours of inactivity
        flash('You were signed out after a period of inactivity. Please log in again.', 'err');
    }
    if (!is_admin()) redirect('admin/login.php');
    $_SESSION['admin_seen'] = time();
}

/** Registration gate: after N free views, visitors must register (if enabled in settings). */
function gate(string $key): void
{
    if (setting('registration_required') !== '1' || current_user() || is_admin()) return;
    $free = max(0, (int)setting('free_views', '2'));
    $seen = $_SESSION['seen'] ?? [];
    if (!in_array($key, $seen, true)) {
        if (count($seen) >= $free) {
            $_SESSION['next'] = $_SERVER['REQUEST_URI'] ?? '/';
            flash('Please create a free account to keep viewing listings.', 'info');
            redirect('register.php');
        }
        $seen[] = $key;
        $_SESSION['seen'] = $seen;
    }
}

function safe_next(): string
{
    $n = $_SESSION['next'] ?? '';
    unset($_SESSION['next']);
    return ($n !== '' && $n[0] === '/' && !str_starts_with($n, '//')) ? $n : url('listings.php');
}

// ---------- uploads ----------
function save_upload(array $file, string $kind): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    $allowed = $kind === 'video'
        ? ['video/mp4' => 'mp4', 'video/webm' => 'webm']
        : ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $max = ($kind === 'video' ? 200 : 8) * 1024 * 1024;
    if ($file['size'] > $max) return null;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) return null;
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    return move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name) ? $name : null;
}
function delete_upload(?string $name): void
{
    if ($name && preg_match('/^[a-f0-9]{24}\.(jpg|png|webp|mp4|webm)$/', $name)) @unlink(UPLOAD_DIR . '/' . $name);
}

function embed_url(string $u): ?string
{
    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([\w-]{11})~', $u, $m))
        return 'https://www.youtube-nocookie.com/embed/' . $m[1];
    if (preg_match('~vimeo\.com/(\d+)~', $u, $m)) return 'https://player.vimeo.com/video/' . $m[1];
    return null;
}

// ---------- property helpers ----------
const STATUSES = ['for_sale' => 'For Sale', 'for_rent' => 'For Rent', 'sold' => 'Sold'];
const TYPES = ['house' => 'House', 'condo' => 'Condo', 'townhouse' => 'Townhouse', 'land' => 'Land', 'commercial' => 'Commercial'];

function property_images(int $id): array
{
    $s = db()->prepare('SELECT * FROM property_images WHERE property_id = ? ORDER BY sort, id');
    $s->execute([$id]);
    return $s->fetchAll();
}
function cover_image(int $id): string
{
    $s = db()->prepare('SELECT filename FROM property_images WHERE property_id = ? ORDER BY sort, id LIMIT 1');
    $s->execute([$id]);
    $f = $s->fetchColumn();
    return $f ? url('uploads/' . $f) : url('assets/img/placeholder.svg');
}

/** Alt text for a photo: the saved text, or a sensible fallback built from the listing. */
function image_alt(array $p, ?array $im, int $n = 1): string
{
    $alt = trim((string)($im['alt'] ?? ''));
    if ($alt !== '') return $alt;
    $where = trim(($p['address'] ?? '') . ' ' . ($p['city'] ?? ''));
    return trim($p['title'] . ($where !== '' ? ' at ' . $where : '') . ' - photo ' . $n);
}
function cover_alt(array $p): string
{
    $s = db()->prepare('SELECT * FROM property_images WHERE property_id = ? ORDER BY sort, id LIMIT 1');
    $s->execute([(int)$p['id']]);
    $im = $s->fetch() ?: null;
    return $im ? image_alt($p, $im, 1) : $p['title'];
}

function render_card(array $p): void
{
    $addr = e(trim($p['address'] . ', ' . $p['city'], ', '));
    ?>
    <article class="card">
      <a href="<?= e(property_url($p)) ?>" class="card-img">
        <img src="<?= e(cover_image((int)$p['id'])) ?>" alt="<?= e(cover_alt($p)) ?>" loading="lazy">
        <span class="badge badge-<?= e($p['status']) ?>"><?= e(STATUSES[$p['status']] ?? $p['status']) ?></span>
      </a>
      <div class="card-body">
        <div class="price"><?= money($p['price']) ?><?= $p['status'] === 'for_rent' ? '<small>/mo</small>' : '' ?></div>
        <h3><a href="<?= e(property_url($p)) ?>"><?= e($p['title']) ?></a></h3>
        <p class="muted"><?= $addr ?></p>
        <ul class="facts">
          <li><?= (int)$p['bedrooms'] ?> bd</li><li><?= rtrim(rtrim(number_format((float)$p['bathrooms'], 1), '0'), '.') ?> ba</li>
          <?php if ($p['sqft']): ?><li><?= number_format((int)$p['sqft']) ?> sqft</li><?php endif; ?>
        </ul>
      </div>
    </article>
    <?php
}

const LEAD_STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'showing' => 'Showing booked', 'won' => 'Closed (won)', 'lost' => 'Closed (lost)'];

function mail_clean(string $v): string { return trim(str_replace(["\r", "\n"], ' ', $v)); }

function save_lead(array $d): void
{
    rl_fail('lead', '');
    if (rl_failures('lead', '')[0] > 15) return;   // more than 15 form submissions in 15 minutes from one address: drop quietly
    db()->prepare('INSERT INTO leads (user_id, property_id, name, email, phone, message, source, showing_at) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$d['user_id'] ?? null, $d['property_id'] ?? null, $d['name'], $d['email'], $d['phone'] ?? '', $d['message'] ?? '', $d['source'], $d['showing_at'] ?? '']);
    $to = setting('contact_email');
    $site = setting('site_name', 'Your Name Realty');
    if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $body = "New lead from the website\n\nName: {$d['name']}\nEmail: {$d['email']}\nPhone: " . ($d['phone'] ?? '') . "\nSource: {$d['source']}\n"
            . (!empty($d['showing_at']) ? "Requested showing: {$d['showing_at']}\n" : '') . "\n" . ($d['message'] ?? '');
        @mail($to, 'New website lead: ' . mail_clean($d['name']), $body, 'Reply-To: ' . mail_clean($d['email']));
    }
    // Optional automatic reply to the visitor.
    if (setting('autoreply_on') === '1' && filter_var($d['email'], FILTER_VALIDATE_EMAIL) && (string)$d['source'] !== 'registration') {
        $text = setting('autoreply_text') ?: "Hi {name},\n\nThanks for getting in touch. I have your message and will reply personally as soon as I can.\n\n{site}";
        $text = strtr($text, ['{name}' => mail_clean($d['name']), '{site}' => $site, '{phone}' => setting('phone')]);
        $from = ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) ? $to : 'noreply@' . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
        @mail($d['email'], mail_clean(setting('autoreply_subject') ?: 'Thanks for contacting ' . $site), $text,
            'From: ' . mail_clean($site) . ' <' . $from . ">\r\nReply-To: " . $from);
    }
}
