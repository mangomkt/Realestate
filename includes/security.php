<?php
// Security helpers: login throttling, two-factor codes (TOTP), response headers.
declare(strict_types=1);

function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45); }

/** Failed attempts for $scope (e.g. "admin") by this IP or against this account within $window seconds. */
function rl_failures(string $scope, string $account, int $window = 900): array
{
    $since = time() - $window;
    $q = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE scope = ? AND ip = ? AND ts > ?');
    $q->execute([$scope, client_ip(), $since]);
    $byIp = (int)$q->fetchColumn();
    $q = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE scope = ? AND account = ? AND ts > ?');
    $q->execute([$scope, $account, $since]);
    return [$byIp, (int)$q->fetchColumn()];
}
/** True when this IP (10+) or this account (6+) has failed too often recently. */
function rl_locked(string $scope, string $account): bool
{
    [$ip, $acct] = rl_failures($scope, strtolower($account));
    return $ip >= 10 || ($account !== '' && $acct >= 6);
}
function rl_fail(string $scope, string $account): void
{
    db()->prepare('INSERT INTO login_attempts (scope, account, ip, ts) VALUES (?,?,?,?)')->execute([$scope, strtolower($account), client_ip(), time()]);
    if (random_int(1, 40) === 1) db()->prepare('DELETE FROM login_attempts WHERE ts < ?')->execute([time() - 86400]);
}
function rl_clear(string $scope, string $account): void
{
    db()->prepare('DELETE FROM login_attempts WHERE scope = ? AND account = ?')->execute([$scope, strtolower($account)]);
}
const RL_MESSAGE = 'Too many failed attempts. Please wait 15 minutes and try again.';

// ---- Two-factor codes (TOTP, RFC 6238: works with Google Authenticator, Microsoft Authenticator, 1Password, Authy...) ----
const B32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
function b32_encode(string $bin): string
{
    $bits = '';
    foreach (str_split($bin) as $c) $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
    $out = '';
    foreach (str_split($bits, 5) as $chunk) $out .= B32[bindec(str_pad($chunk, 5, '0'))];
    return $out;
}
function b32_decode(string $s): string
{
    $bits = '';
    foreach (str_split(strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $s))) as $c) $bits .= str_pad(decbin((int)strpos(B32, $c)), 5, '0', STR_PAD_LEFT);
    $out = '';
    foreach (str_split($bits, 8) as $byte) if (strlen($byte) === 8) $out .= chr(bindec($byte));
    return $out;
}
function totp_secret(): string { return b32_encode(random_bytes(20)); }
function totp_at(string $secret, int $step): string
{
    $h = hash_hmac('sha1', pack('N2', 0, $step), b32_decode($secret), true);
    $o = ord($h[19]) & 15;
    $n = ((ord($h[$o]) & 0x7f) << 24) | (ord($h[$o + 1]) << 16) | (ord($h[$o + 2]) << 8) | ord($h[$o + 3]);
    return str_pad((string)($n % 1000000), 6, '0', STR_PAD_LEFT);
}
/** Returns the matched time step (so it can be remembered and not accepted twice), or null. Allows one step of clock drift. */
function totp_verify(string $secret, string $code, int $lastStep = 0): ?int
{
    $code = preg_replace('/\D/', '', $code);
    if (strlen($code) !== 6) return null;
    $now = intdiv(time(), 30);
    foreach ([0, -1, 1] as $d) {
        $st = $now + $d;
        if ($st > $lastStep && hash_equals(totp_at($secret, $st), $code)) return $st;
    }
    return null;
}
function totp_uri(string $secret, string $account): string
{
    $issuer = setting('site_name', 'Real Estate Site');
    return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account) . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer) . '&digits=6&period=30';
}
function backup_codes_make(): array
{
    $plain = [];
    for ($i = 0; $i < 8; $i++) $plain[] = strtolower(bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)));
    return [$plain, json_encode(array_map(fn($c) => password_hash($c, PASSWORD_DEFAULT), $plain))];
}
/** Checks a backup code; if valid, returns the remaining hashed list (JSON), else null. */
function backup_code_use(string $stored, string $given): ?string
{
    $given = strtolower(trim($given));
    $list = json_decode($stored, true) ?: [];
    foreach ($list as $i => $h) {
        if (password_verify($given, $h)) { unset($list[$i]); return json_encode(array_values($list)); }
    }
    return null;
}

// ---- Headers ----
function security_headers(): void
{
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    if (!empty($_SERVER['HTTPS'])) header('Strict-Transport-Security: max-age=15552000');
    if (str_contains((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/admin/')) header('Cache-Control: no-store');
}
