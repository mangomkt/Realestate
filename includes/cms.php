<?php
// CMS helpers: theme colours, HTML sanitizer, page URLs, navigation menus.
declare(strict_types=1);

// =====================================================================
// Theme
// =====================================================================
const THEME_DEFAULTS = [
    'primary'     => '#0f4c5c',
    'secondary'   => '#0a3641',
    'tertiary'    => '#e36414',
    'button'      => '#e36414',
    'button_text' => '#ffffff',
    'header_bg'   => '#ffffff',
    'header_text' => '#1b2430',
    'footer_bg'   => '#101828',
    'footer_text' => '#d0d5dd',
    'text'        => '#1b2430',
    'link'        => '#0f4c5c',
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
const THEME_PRESETS = [
    'Teal & Orange (default)' => ['primary' => '#0f4c5c', 'secondary' => '#0a3641', 'tertiary' => '#e36414', 'button' => '#e36414', 'button_text' => '#ffffff', 'header_bg' => '#ffffff', 'header_text' => '#1b2430', 'footer_bg' => '#101828', 'footer_text' => '#d0d5dd', 'text' => '#1b2430', 'link' => '#0f4c5c'],
    'Navy & Gold'             => ['primary' => '#1d3557', 'secondary' => '#0b1d33', 'tertiary' => '#c9a227', 'button' => '#c9a227', 'button_text' => '#10202f', 'header_bg' => '#ffffff', 'header_text' => '#1d3557', 'footer_bg' => '#0b1d33', 'footer_text' => '#cfd8e3', 'text' => '#1c2733', 'link' => '#1d3557'],
    'Forest & Terracotta'     => ['primary' => '#2d6a4f', 'secondary' => '#1b4332', 'tertiary' => '#bc6c25', 'button' => '#bc6c25', 'button_text' => '#ffffff', 'header_bg' => '#ffffff', 'header_text' => '#1b4332', 'footer_bg' => '#1b4332', 'footer_text' => '#d8e5dd', 'text' => '#22302a', 'link' => '#2d6a4f'],
    'Charcoal & Red'          => ['primary' => '#2b2d42', 'secondary' => '#14151f', 'tertiary' => '#d90429', 'button' => '#d90429', 'button_text' => '#ffffff', 'header_bg' => '#2b2d42', 'header_text' => '#ffffff', 'footer_bg' => '#14151f', 'footer_text' => '#c9cbd6', 'text' => '#1f2030', 'link' => '#b0031f'],
    'Plum & Coral'            => ['primary' => '#6d3a6b', 'secondary' => '#3f1f3e', 'tertiary' => '#ef6f5e', 'button' => '#ef6f5e', 'button_text' => '#2a0f1f', 'header_bg' => '#ffffff', 'header_text' => '#3f1f3e', 'footer_bg' => '#3f1f3e', 'footer_text' => '#e6d5e4', 'text' => '#2a1f29', 'link' => '#6d3a6b'],
];

function valid_hex($v): bool { return is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) === 1; }

function theme_get(): array
{
    $t = [];
    foreach (THEME_DEFAULTS as $k => $d) {
        $v = setting('theme_' . $k);
        $t[$k] = valid_hex($v) ? strtolower($v) : $d;
    }
    $f = setting('theme_font', 'system');
    $t['font'] = isset(THEME_FONTS[$f]) ? $f : 'system';
    $r = setting('theme_radius', '12');
    $t['radius'] = isset(THEME_RADII[$r]) ? $r : '12';
    return $t;
}

function hex_rgb(string $h): array { return [hexdec(substr($h, 1, 2)), hexdec(substr($h, 3, 2)), hexdec(substr($h, 5, 2))]; }
function rgb_hex(array $c): string { return sprintf('#%02x%02x%02x', ...array_map(fn($x) => max(0, min(255, (int)round($x))), $c)); }
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
function mix_hex(string $a, string $b): string
{
    $x = hex_rgb($a); $y = hex_rgb($b);
    return rgb_hex([($x[0] + $y[0]) / 2, ($x[1] + $y[1]) / 2, ($x[2] + $y[2]) / 2]);
}

/** CSS custom properties for the saved theme. Every value is validated, so this is safe to print. */
function theme_css(): string
{
    $t = theme_get();
    $logo = contrast($t['primary'], $t['header_bg']) >= 3 ? $t['primary'] : $t['header_text'];
    [, $rc, $rb, $ri] = THEME_RADII[$t['radius']];
    $v = [
        '--brand' => $t['primary'], '--brand-d' => $t['secondary'], '--accent' => $t['tertiary'],
        '--on-brand' => on_color($t['primary']), '--on-accent' => on_color($t['tertiary']),
        '--on-hero' => on_color(mix_hex($t['primary'], $t['secondary'])),
        '--btn' => $t['button'], '--btn-text' => $t['button_text'],
        '--header-bg' => $t['header_bg'], '--header-text' => $t['header_text'], '--logo' => $logo,
        '--footer-bg' => $t['footer_bg'], '--footer-text' => $t['footer_text'],
        '--ink' => $t['text'], '--link' => $t['link'],
        '--radius' => $rc . 'px', '--radius-btn' => $rb . 'px', '--radius-in' => $ri . 'px',
        '--font' => THEME_FONTS[$t['font']][1],
    ];
    $css = ':root{';
    foreach ($v as $k => $val) $css .= $k . ':' . $val . ';';
    return $css . '}';
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

/** Menu items ready to print for a location: [label, href, new_tab, current]. Hidden/draft/missing targets are skipped. */
function nav_items(string $loc): array
{
    $st = db()->prepare('SELECT * FROM nav_items WHERE location = ? AND visible = 1 ORDER BY sort, id');
    $st->execute([$loc]);
    $pages = db()->prepare("SELECT slug FROM pages WHERE id = ? AND status = 'published'");
    $reqPath = rtrim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $reqPath = preg_replace('#/index\.php$#', '', $reqPath);
    $reqQuery = (string)($_SERVER['QUERY_STRING'] ?? '');
    $out = [];
    foreach ($st->fetchAll() as $it) {
        $href = null;
        if ($it['type'] === 'builtin' && isset(NAV_BUILTIN[$it['target']])) {
            if ($it['target'] === 'idx' && trim(setting('idx_embed')) === '') continue;
            $href = url(NAV_BUILTIN[$it['target']][1]);
        } elseif ($it['type'] === 'page') {
            $pages->execute([(int)$it['target']]);
            if ($slug = $pages->fetchColumn()) $href = page_url((string)$slug);
        } elseif ($it['type'] === 'link') {
            $u = nav_clean_url((string)$it['target']);
            if ($u !== null) $href = preg_match('/^([a-z][a-z0-9+.\-]*:|#|\/\/)/i', $u) ? $u : url($u);
        }
        if ($href === null) continue;
        $p = rtrim((string)parse_url($href, PHP_URL_PATH), '/');
        $q = parse_url($href, PHP_URL_QUERY);
        $isLocal = !preg_match('/^([a-z][a-z0-9+.\-]*:|\/\/)/i', $href) && $href[0] !== '#';
        $current = $isLocal && $p === $reqPath && ($q === null || $q === $reqQuery);
        $out[] = ['label' => $it['label'], 'href' => $href, 'new_tab' => (bool)$it['new_tab'], 'current' => $current];
    }
    return $out;
}
