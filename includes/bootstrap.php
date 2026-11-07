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
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_start();
}

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
    ");
    return $pdo;
}

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
function require_admin(): void
{
    if (!is_admin()) redirect('admin/login.php');
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

function render_card(array $p): void
{
    ?>
    <article class="card">
      <a href="<?= url('property.php?id=' . (int)$p['id']) ?>" class="card-img">
        <img src="<?= e(cover_image((int)$p['id'])) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
        <span class="badge badge-<?= e($p['status']) ?>"><?= e(STATUSES[$p['status']] ?? $p['status']) ?></span>
      </a>
      <div class="card-body">
        <div class="price"><?= money($p['price']) ?><?= $p['status'] === 'for_rent' ? '<small>/mo</small>' : '' ?></div>
        <h3><a href="<?= url('property.php?id=' . (int)$p['id']) ?>"><?= e($p['title']) ?></a></h3>
        <p class="muted"><?= e(trim($p['address'] . ', ' . $p['city'], ', ')) ?></p>
        <ul class="facts">
          <li><?= (int)$p['bedrooms'] ?> bd</li><li><?= rtrim(rtrim(number_format((float)$p['bathrooms'], 1), '0'), '.') ?> ba</li>
          <?php if ($p['sqft']): ?><li><?= number_format((int)$p['sqft']) ?> sqft</li><?php endif; ?>
        </ul>
      </div>
    </article>
    <?php
}

function save_lead(array $d): void
{
    db()->prepare('INSERT INTO leads (user_id, property_id, name, email, phone, message, source) VALUES (?,?,?,?,?,?,?)')
        ->execute([$d['user_id'] ?? null, $d['property_id'] ?? null, $d['name'], $d['email'], $d['phone'] ?? '', $d['message'] ?? '', $d['source']]);
    $to = setting('contact_email');
    if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $body = "New lead from the website\n\nName: {$d['name']}\nEmail: {$d['email']}\nPhone: " . ($d['phone'] ?? '') . "\nSource: {$d['source']}\n\n" . ($d['message'] ?? '');
        @mail($to, 'New website lead: ' . str_replace(["\r", "\n"], ' ', $d['name']), $body, 'Reply-To: ' . str_replace(["\r", "\n"], '', $d['email']));
    }
}
