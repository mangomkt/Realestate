<?php
// CMS helpers: theme colours, HTML sanitizer, page URLs, navigation menus.
declare(strict_types=1);

// =====================================================================
// Theme: light + dark palettes, derived surface tokens
// =====================================================================
const THEME_DEFAULTS = [
    'primary'     => '#0f4c5c',
    'secondary'   => '#0a3641',
    'tertiary'    => '#e36414',
    'button'      => '#bf4e0c',
    'button_text' => '#ffffff',
    'header_bg'   => '#ffffff',
    'header_text' => '#1b2430',
    'footer_bg'   => '#101828',
    'footer_text' => '#d0d5dd',
    'text'        => '#1b2430',
    'link'        => '#0f4c5c',
    'bg'          => '#ffffff',
    'surface'     => '#ffffff',
];
const THEME_FONTS = [
    'system'   => ['System sans-serif', 'system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif'],
    'serif'    => ['Classic serif (Georgia)', 'Georgia,"Times New Roman",Times,serif'],
    'humanist' => ['Humanist sans', '"Gill Sans","Gill Sans MT",Calibri,"Trebuchet MS",sans-serif'],
];
// radius => [label, card radius px, button radius px, input radius px]
const THEME_RADII = [
    '0'  => ['Square', 0, 0, 0],
    '6'  => ['Slightly rounded', 6, 4, 4],
    '12' => ['Rounded', 12, 8, 8],
    '20' => ['Very rounded', 20, 999, 10],
];
const THEME_MODES = ['light' => 'Light', 'dark' => 'Dark', 'auto' => "Match the visitor's device"];
const THEME_PRESETS_RAW = [
    'Teal & Orange (default)' => ['primary' => '#0f4c5c', 'secondary' => '#0a3641', 'tertiary' => '#e36414', 'button' => '#bf4e0c', 'button_text' => '#ffffff', 'header_bg' => '#ffffff', 'header_text' => '#1b2430', 'footer_bg' => '#101828', 'footer_text' => '#d0d5dd', 'text' => '#1b2430', 'link' => '#0f4c5c'],
    'Navy & Gold'             => ['primary' => '#1d3557', 'secondary' => '#0b1d33', 'tertiary' => '#c9a227', 'button' => '#c9a227', 'button_text' => '#10202f', 'header_bg' => '#ffffff', 'header_text' => '#1d3557', 'footer_bg' => '#0b1d33', 'footer_text' => '#cfd8e3', 'text' => '#1c2733', 'link' => '#1d3557'],
    'Forest & Terracotta'     => ['primary' => '#2d6a4f', 'secondary' => '#1b4332', 'tertiary' => '#bc6c25', 'button' => '#a35a1c', 'button_text' => '#ffffff', 'header_bg' => '#ffffff', 'header_text' => '#1b4332', 'footer_bg' => '#1b4332', 'footer_text' => '#d8e5dd', 'text' => '#22302a', 'link' => '#2d6a4f'],
    'Charcoal & Red'          => ['primary' => '#2b2d42', 'secondary' => '#14151f', 'tertiary' => '#d90429', 'button' => '#d90429', 'button_text' => '#ffffff', 'header_bg' => '#2b2d42', 'header_text' => '#ffffff', 'footer_bg' => '#14151f', 'footer_text' => '#c9cbd6', 'text' => '#1f2030', 'link' => '#b0031f'],
    'Plum & Coral'            => ['primary' => '#6d3a6b', 'secondary' => '#3f1f3e', 'tertiary' => '#ef6f5e', 'button' => '#ef6f5e', 'button_text' => '#2a0f1f', 'header_bg' => '#ffffff', 'header_text' => '#3f1f3e', 'footer_bg' => '#3f1f3e', 'footer_text' => '#e6d5e4', 'text' => '#2a1f29', 'link' => '#6d3a6b'],
    'Sand & Sage'             => ['primary' => '#4f6f52', 'secondary' => '#2f4232', 'tertiary' => '#b5651d', 'button' => '#4f6f52', 'button_text' => '#ffffff', 'header_bg' => '#faf7f2', 'header_text' => '#2f4232', 'footer_bg' => '#2f4232', 'footer_text' => '#e6eadf', 'text' => '#2b2a26', 'link' => '#3f5b42', 'bg' => '#faf7f2', 'surface' => '#ffffff'],
];
/** Presets with every key filled in (bg/surface default to white). */
function theme_presets(): array
{
    return array_map(fn($p) => $p + ['bg' => '#ffffff', 'surface' => '#ffffff'], THEME_PRESETS_RAW);
}

function valid_hex($v): bool { return is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) === 1; }

function hex_rgb(string $h): array { return [hexdec(substr($h, 1, 2)), hexdec(substr($h, 3, 2)), hexdec(substr($h, 5, 2))]; }
// floor(x + 0.5) rounds exactly like JavaScript's Math.round (PHP's round() can differ on .5 edge cases)
function rgb_hex(array $c): string { return sprintf('#%02x%02x%02x', ...array_map(fn($x) => max(0, min(255, (int)floor($x + 0.5))), $c)); }
function rel_lum(string $h): float
{
    [$r, $g, $b] = array_map(function ($v) { $v /= 255; return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4; }, hex_rgb($h));
    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}
function contrast(string $a, string $b): float
{
    $l1 = rel_lum($a); $l2 = rel_lum($b);
    if ($l1 < $l2) [$l1, $l2] = [$l2, $l1];
    return ($l1 + 0.05) / ($l2 + 0.05);
}
/** White or near-black, whichever reads better on $bg. */
function on_color(string $bg): string { return contrast($bg, '#ffffff') >= contrast($bg, '#101828') ? '#ffffff' : '#101828'; }
/** $a * (1 - $w) + $b * $w */
function mix_hex(string $a, string $b, float $w = 0.5): string
{
    $x = hex_rgb($a); $y = hex_rgb($b);
    return rgb_hex([$x[0] * (1 - $w) + $y[0] * $w, $x[1] * (1 - $w) + $y[1] * $w, $x[2] * (1 - $w) + $y[2] * $w]);
}
function hex_hsl(string $h): array
{
    [$r, $g, $b] = array_map(fn($v) => $v / 255, hex_rgb($h));
    $max = max($r, $g, $b); $min = min($r, $g, $b); $l = ($max + $min) / 2; $d = $max - $min;
    if ($d == 0) return [0.0, 0.0, $l];
    $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
    if ($max == $r) $hh = (($g - $b) / $d) + ($g < $b ? 6 : 0);
    elseif ($max == $g) $hh = (($b - $r) / $d) + 2;
    else $hh = (($r - $g) / $d) + 4;
    return [$hh * 60, $s, $l];
}
function hsl_hex(float $h, float $s, float $l): string
{
    $h = fmod(fmod($h, 360) + 360, 360) / 360; $s = max(0, min(1, $s)); $l = max(0, min(1, $l));
    if ($s == 0) return rgb_hex([$l * 255, $l * 255, $l * 255]);
    $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s; $p = 2 * $l - $q;
    $f = function ($t) use ($p, $q) {
        if ($t < 0) $t += 1; if ($t > 1) $t -= 1;
        if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1 / 2) return $q;
        if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;
        return $p;
    };
    return rgb_hex([$f($h + 1 / 3) * 255, $f($h) * 255, $f($h - 1 / 3) * 255]);
}
/** Make colour $c readable on $bg by lightening it (used for dark mode). */
function lift_to_contrast(string $c, string $bg, float $min): string
{
    if (contrast($c, $bg) >= $min) return $c;
    [$h, $s, $l] = hex_hsl($c);
    for ($n = 0; $n < 40 && $l < 0.92 && contrast(hsl_hex($h, $s, $l), $bg) < $min; $n++) $l += 0.03;
    return hsl_hex($h, $s, min($l, 0.92));
}
/** Dark palette derived from a light one. Mirrored in assets/js/admin-theme.js: keep both in sync. */
function theme_auto_dark(array $L): array
{
    [$h, $s] = hex_hsl($L['primary']);
    $bs = min(0.28, max(0.10, $s * 0.45));
    $bg = hsl_hex($h, $bs, 0.085);
    $text = hsl_hex($h, 0.12, 0.92);
    $primary = lift_to_contrast($L['primary'], $bg, 4.5);
    $button = lift_to_contrast($L['button'], $bg, 3.0);
    $btext = contrast($L['button_text'], $button) >= 4.5 ? $L['button_text'] : on_color($button);
    return [
        'primary' => $primary,
        'secondary' => hsl_hex($h, max(0.2, min($s, 0.6)), 0.16),
        'tertiary' => lift_to_contrast($L['tertiary'], $bg, 3.0),
        'button' => $button, 'button_text' => $btext,
        'header_bg' => hsl_hex($h, $bs, 0.105), 'header_text' => $text,
        'footer_bg' => hsl_hex($h, $bs, 0.055), 'footer_text' => hsl_hex($h, 0.12, 0.76),
        'text' => $text, 'link' => $primary,
        'bg' => $bg, 'surface' => hsl_hex($h, $bs, 0.125),
    ];
}

/** Saved palette for 'light' or 'dark' (dark = generated from light unless set by hand). */
function theme_palette(string $mode = 'light'): array
{
    $light = [];
    foreach (THEME_DEFAULTS as $k => $d) { $v = setting('theme_' . $k); $light[$k] = valid_hex($v) ? strtolower($v) : $d; }
    if ($mode !== 'dark') return $light;
    $auto = theme_auto_dark($light);
    if (setting('theme_dark_auto', '1') === '1') return $auto;
    $dark = [];
    foreach (THEME_DEFAULTS as $k => $_) { $v = setting('theme_dark_' . $k); $dark[$k] = valid_hex($v) ? strtolower($v) : $auto[$k]; }
    return $dark;
}

/** Structural style choices: key => [label, [option => label]]. First option is the default. */
const THEME_VARIANTS = [
    'cards'   => ['Listing cards', ['classic' => 'Classic (border + lift)', 'flat' => 'Flat (no border, soft shadow)', 'overlay' => 'Photo overlay (text on the photo)', 'minimal' => 'Minimal (no box)']],
    'header'  => ['Header', ['left' => 'Logo left, menu right', 'center' => 'Logo centered, menu below', 'brand' => 'Brand-colored bar']],
    'footer'  => ['Footer', ['columns' => 'Columns', 'simple' => 'Simple, centered', 'brand' => 'Brand-colored band']],
    'buttons' => ['Buttons', ['solid' => 'Solid', 'outline' => 'Outline', 'soft' => 'Soft tint']],
];
function theme_variant(string $k): string
{
    $v = setting('style_' . $k);
    return isset(THEME_VARIANTS[$k][1][$v]) ? $v : array_key_first(THEME_VARIANTS[$k][1]);
}
/** Classes for <body>, e.g. "cards-flat hdr-center". */
function theme_body_class(): string
{
    return 'cards-' . theme_variant('cards') . ' hdr-' . theme_variant('header') . ' ftr-' . theme_variant('footer') . ' btn-' . theme_variant('buttons');
}

function theme_settings(): array
{
    $mode = setting('theme_mode', 'light');
    $font = setting('theme_font', 'system');
    $radius = setting('theme_radius', '12');
    return [
        'mode' => isset(THEME_MODES[$mode]) ? $mode : 'light',
        'toggle' => setting('theme_toggle', '1') === '1',
        'dark_auto' => setting('theme_dark_auto', '1') === '1',
        'font' => isset(THEME_FONTS[$font]) ? $font : 'system',
        'radius' => isset(THEME_RADII[$radius]) ? (string)$radius : '12',
    ];
}

/** CSS variables for one palette. Mirrored in assets/js/admin-theme.js. */
function theme_palette_vars(array $p, bool $dark): array
{
    $logo = contrast($p['primary'], $p['header_bg']) >= 3 ? $p['primary'] : $p['header_text'];
    $muted = $p['text'];
    foreach ([0.4, 0.3, 0.2, 0.1, 0.0] as $w) {
        $muted = mix_hex($p['text'], $p['bg'], $w);
        if (contrast($muted, $p['bg']) >= 4.5) break;
    }
    $flash = $dark
        ? ['--ok-bg' => '#12352a', '--ok-ink' => '#b7ebcd', '--err-bg' => '#3b1a17', '--err-ink' => '#ffc9c2', '--info-bg' => '#14304d', '--info-ink' => '#c3dcf7']
        : ['--ok-bg' => '#e8f5ee', '--ok-ink' => '#14532d', '--err-bg' => '#fdecea', '--err-ink' => '#7a1c14', '--info-bg' => '#e8f1fb', '--info-ink' => '#123e6b'];
    return [
        '--brand' => $p['primary'], '--brand-d' => $p['secondary'], '--accent' => $p['tertiary'],
        '--on-brand' => on_color($p['primary']), '--on-accent' => on_color($p['tertiary']),
        '--on-hero' => on_color(mix_hex($p['primary'], $p['secondary'])),
        '--btn' => $p['button'], '--btn-text' => $p['button_text'],
        '--header-bg' => $p['header_bg'], '--header-text' => $p['header_text'], '--logo' => $logo,
        '--footer-bg' => $p['footer_bg'], '--footer-text' => $p['footer_text'],
        '--ink' => $p['text'], '--link' => $p['link'], '--bg' => $p['bg'], '--surface' => $p['surface'],
        '--soft' => mix_hex($p['bg'], $p['text'], 0.04), '--line' => mix_hex($p['bg'], $p['text'], 0.14),
        '--field-border' => mix_hex($p['bg'], $p['text'], 0.3), '--muted' => $muted,
        '--logo-l' => $dark ? 'none' : 'inline-block', '--logo-d' => $dark ? 'inline-block' : 'none',
    ] + $flash;
}

/** Theme CSS: light variables, dark variables, and the "match the device" media query when chosen. */
function theme_css(): string
{
    $s = theme_settings();
    [, $rc, $rb, $ri] = THEME_RADII[$s['radius']];
    $pack = function (array $v): string { $o = ''; foreach ($v as $k => $x) $o .= $k . ':' . $x . ';'; return $o; };
    $light = $pack(theme_palette_vars(theme_palette('light'), false));
    $dark = $pack(theme_palette_vars(theme_palette('dark'), true));
    $layout = $pack(['--radius' => $rc . 'px', '--radius-btn' => $rb . 'px', '--radius-in' => $ri . 'px', '--font' => THEME_FONTS[$s['font']][1]]);
    $css = ':root{' . $layout . $light . 'color-scheme:light}';
    $css .= ':root[data-theme="dark"]{' . $dark . 'color-scheme:dark}';
    if ($s['mode'] === 'auto') $css .= '@media (prefers-color-scheme:dark){:root:not([data-theme="light"]){' . $dark . 'color-scheme:dark}}';
    return $css;
}

// =====================================================================
// Slugs and page URLs
// =====================================================================
function slugify(string $s): string
{
    if (function_exists('transliterator_transliterate')) {
        $s = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $s) ?: $s;
    } elseif (function_exists('iconv')) {
        $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    }
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim(substr($s, 0, 80), '-');
}

/** Pretty URLs (/p/slug) need the bundled .htaccess rewrite; otherwise page.php?slug= is used. */
function page_url(string $slug): string
{
    return setting('pretty_urls') === '1' ? url('p/' . $slug) : url('page.php?slug=' . rawurlencode($slug));
}

// =====================================================================
// HTML sanitizer (allow-list) for CMS page content
// =====================================================================
function sanitize_html(string $html): string
{
    $html = trim($html);
    if ($html === '') return '';

    $allowed = [
        'p' => ['class'], 'br' => [], 'hr' => [], 'h2' => ['class'], 'h3' => ['class'], 'h4' => ['class'],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'small' => [], 'blockquote' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'a' => ['href', 'target', 'rel', 'title', 'class'],
        'img' => ['src', 'alt', 'width', 'height', 'class'],
        'figure' => ['class'], 'figcaption' => [], 'div' => ['class'], 'span' => ['class'],
        'table' => ['class'], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'video' => ['src', 'controls', 'poster', 'playsinline', 'preload'],
        'source' => ['src', 'type'],
        'iframe' => ['src', 'title', 'allowfullscreen', 'width', 'height'],
    ];
    // Removed together with their contents.
    $drop = ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'option', 'link', 'meta',
             'svg', 'math', 'base', 'noscript', 'template', 'title', 'head', 'frame', 'frameset', 'applet', 'audio'];
    $embedHosts = ['www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com'];

    /** Returns the cleaned URL or null. $schemes = absolute schemes allowed; relative URLs are always fine. */
    $cleanUrl = function (string $v, array $schemes): ?string {
        $v = trim(preg_replace('/[\x00-\x20\x7f]+/', '', $v));
        if ($v === '') return null;
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $v, $m)) return in_array(strtolower($m[1]), $schemes, true) ? $v : null;
        return $v;
    };

    $prev = libxml_use_internal_errors(true);
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->loadHTML('<?xml encoding="utf-8" ?><div id="cms-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $root = $doc->getElementsByTagName('div')->item(0);
    if (!$root) { libxml_clear_errors(); libxml_use_internal_errors($prev); return ''; }

    $clean = function (DOMNode $node) use (&$clean, $allowed, $drop, $cleanUrl, $embedHosts): void {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_TEXT_NODE) continue;
            if ($child->nodeType !== XML_ELEMENT_NODE) { $node->removeChild($child); continue; }  // comments, PIs, CDATA
            $tag = strtolower($child->nodeName);
            if (in_array($tag, $drop, true)) { $node->removeChild($child); continue; }
            if (!isset($allowed[$tag])) {                       // unknown tag: keep its (cleaned) children
                $clean($child);
                while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
                $node->removeChild($child);
                continue;
            }
            /** @var DOMElement $child */
            foreach (iterator_to_array($child->attributes) as $attr) {
                $n = strtolower($attr->nodeName);
                $val = (string)$attr->nodeValue;
                if (!in_array($n, $allowed[$tag], true)) { $child->removeAttribute($attr->nodeName); continue; }
                $ok = true;
                switch ($n) {
                    case 'href':
                        $u = $cleanUrl($val, ['http', 'https', 'mailto', 'tel']);
                        $ok = $u !== null;
                        if ($ok) $child->setAttribute('href', $u);
                        break;
                    case 'src':
                    case 'poster':
                        $u = $cleanUrl($val, ['http', 'https']);
                        $ok = $u !== null;
                        if ($ok && $tag === 'iframe') {
                            $host = strtolower((string)parse_url(str_starts_with($u, '//') ? 'https:' . $u : $u, PHP_URL_HOST));
                            $ok = in_array($host, $embedHosts, true);
                        }
                        if ($ok) $child->setAttribute($n, $u);
                        break;
                    case 'class':   $ok = preg_match('/^[a-z0-9_\- ]{1,60}$/i', $val) === 1; break;
                    case 'target':  $ok = $val === '_blank'; break;
                    case 'width': case 'height': case 'colspan': case 'rowspan': $ok = preg_match('/^\d{1,4}%?$/', $val) === 1; break;
                    case 'preload': $ok = in_array($val, ['auto', 'metadata', 'none'], true); break;
                    case 'type':    $ok = in_array($val, ['video/mp4', 'video/webm'], true); break;
                    case 'rel':     $ok = preg_match('/^[a-z ]{1,60}$/i', $val) === 1; break;
                }
                if (!$ok) $child->removeAttribute($attr->nodeName);
            }
            // Elements that are useless or unsafe without a valid source go away.
            if (in_array($tag, ['img', 'iframe', 'source'], true) && !$child->hasAttribute('src')) { $node->removeChild($child); continue; }
            if ($tag === 'a' && $child->getAttribute('target') === '_blank') $child->setAttribute('rel', 'noopener');
            if ($tag === 'img' && !$child->hasAttribute('alt')) $child->setAttribute('alt', '');
            if ($tag === 'img') $child->setAttribute('loading', 'lazy');
            $clean($child);
        }
    };
    $clean($root);

    $out = '';
    foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    return trim($out);
}

/** Plain-text excerpt, for meta descriptions. */
function excerpt(string $html, int $len = 155): string
{
    $t = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')));
    return mb_strlen($t) > $len ? rtrim(mb_substr($t, 0, $len - 1)) . '…' : $t;
}

// =====================================================================
// Navigation
// =====================================================================
const NAV_BUILTIN = [
    'home'     => ['Home', ''],
    'listings' => ['Listings', 'listings.php'],
    'idx'      => ['Search All MLS', 'idx.php'],
    'contact'  => ['Contact', 'contact.php'],
];
const NAV_LOCATIONS = ['header' => 'Header menu', 'footer' => 'Footer links'];

/** Validates a custom link: http(s), mailto, tel, #anchor or a relative path. Returns null if unsafe. */
function nav_clean_url(string $u): ?string
{
    $u = trim($u);
    if ($u === '' || preg_match('/[\x00-\x1f\x7f\s]/', $u)) return null;
    if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $u, $m)) return in_array(strtolower($m[1]), ['http', 'https', 'mailto', 'tel'], true) ? $u : null;
    return $u;
}

/** Menu items ready to print for a location, as a tree (one level of children).
 *  Each item: label, href (null for a dropdown-only label), new_tab, current, children[]. */
function nav_items(string $loc): array
{
    $st = db()->prepare('SELECT * FROM nav_items WHERE location = ? AND visible = 1 ORDER BY sort, id');
    $st->execute([$loc]);
    $pages = db()->prepare("SELECT slug FROM pages WHERE id = ? AND status = 'published'");
    $reqPath = rtrim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $reqPath = preg_replace('#/index\.php$#', '', $reqPath);
    $reqQuery = (string)($_SERVER['QUERY_STRING'] ?? '');

    $resolve = function (array $it) use ($pages, $reqPath, $reqQuery): ?array {
        $href = null;
        if ($it['type'] === 'none') {
            return ['label' => $it['label'], 'href' => null, 'new_tab' => false, 'current' => false, 'children' => []];
        } elseif ($it['type'] === 'builtin' && isset(NAV_BUILTIN[$it['target']])) {
            if ($it['target'] === 'idx' && trim(setting('idx_embed')) === '') return null;
            $href = url(NAV_BUILTIN[$it['target']][1]);
        } elseif ($it['type'] === 'page') {
            $pages->execute([(int)$it['target']]);
            if ($slug = $pages->fetchColumn()) $href = page_url((string)$slug);
        } elseif ($it['type'] === 'link') {
            $u = nav_clean_url((string)$it['target']);
            if ($u !== null) $href = preg_match('/^([a-z][a-z0-9+.\-]*:|#|\/\/)/i', $u) ? $u : url($u);
        }
        if ($href === null) return null;
        $p = rtrim((string)parse_url($href, PHP_URL_PATH), '/');
        $q = parse_url($href, PHP_URL_QUERY);
        $isLocal = !preg_match('/^([a-z][a-z0-9+.\-]*:|\/\/)/i', $href) && $href[0] !== '#';
        $current = $isLocal && $p === $reqPath && ($q === null || $q === $reqQuery);
        return ['label' => $it['label'], 'href' => $href, 'new_tab' => (bool)$it['new_tab'], 'current' => $current, 'children' => []];
    };

    $tops = [];
    $kids = [];
    foreach ($st->fetchAll() as $it) {
        $r = $resolve($it);
        if ($r === null) continue;
        if ((int)($it['parent_id'] ?? 0) > 0) {
            if ($r['href'] !== null) $kids[(int)$it['parent_id']][] = $r;
        } else {
            $tops[(int)$it['id']] = $r;
        }
    }
    $out = [];
    foreach ($tops as $id => $r) {
        $r['children'] = $kids[$id] ?? [];
        if ($r['href'] === null && !$r['children']) continue;     // an empty dropdown label is pointless
        $out[] = $r;
    }
    return $out;
}

/** Flat list (parents then their children) for places without dropdowns, such as the footer. */
function nav_items_flat(string $loc): array
{
    $flat = [];
    foreach (nav_items($loc) as $it) {
        if ($it['href'] !== null) $flat[] = $it;
        foreach ($it['children'] as $c) $flat[] = $c;
    }
    return $flat;
}
