<?php
// SEO: title/description templates (with per-item overrides), canonical + social tags, JSON-LD, URLs.
declare(strict_types=1);

const SEO_DEFAULTS = [
    'seo_title_format'    => '{page} | {site}',
    'seo_home_title'      => '{site} - {tagline}',
    'seo_home_desc'       => '{tagline}',
    'seo_property_title'  => '{title} - {city} | {site}',
    'seo_property_desc'   => '{type} {status_lc} {in_city}: {beds_text}, {baths_text}, {sqft_text}, {price_text}. {excerpt}',
];
const SEO_TOKEN_HELP = [
    '{title}' => 'Listing title', '{address}' => 'Street address', '{city}' => 'City', '{in_city}' => 'in Caledon (blank if no city)', '{province}' => 'Province', '{postal}' => 'Postal code',
    '{price_text}' => '$899,000', '{beds_text}' => '4 bed', '{baths_text}' => '3 bath', '{sqft_text}' => '2,400 sq ft',
    '{type}' => 'House / Condo...', '{status}' => 'For Sale / For Rent / Sold', '{status_lc}' => 'for sale', '{mls}' => 'MLS number',
    '{excerpt}' => 'Start of the description', '{site}' => 'Your site name', '{tagline}' => 'Your tagline',
];

function seo_setting(string $key): string
{
    $v = trim(setting($key));
    return $v !== '' ? $v : (SEO_DEFAULTS[$key] ?? '');
}

function site_origin(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
function abs_url(string $u): string
{
    if ($u === '') return '';
    if (preg_match('#^https?://#i', $u)) return $u;
    if (str_starts_with($u, '//')) return 'https:' . $u;
    return site_origin() . ($u[0] === '/' ? $u : '/' . $u);
}

function property_url(array $p): string
{
    if (setting('pretty_urls') === '1') {
        $slug = slugify((string)($p['title'] ?? ''));
        return url('property/' . (int)$p['id'] . ($slug !== '' ? '-' . $slug : ''));
    }
    return url('property.php?id=' . (int)$p['id']);
}

// ---------- templates ----------
/** Replace {tokens}, then tidy up the gaps left by empty values ("Title -  | Site" becomes "Title | Site"). Mirrored in assets/js/admin-seo.js. */
function seo_fill(string $tpl, array $t): string
{
    $s = preg_replace_callback('/\{([a-z_]+)\}/', fn($m) => (string)($t[$m[1]] ?? ''), $tpl);
    $s = preg_replace('/\s+/', ' ', $s);
    $s = preg_replace('/\s*[-–|]\s*(?=[-–|])/u', ' ', $s);
    $s = preg_replace('/\s*,(\s*,)+/', ',', $s);
    $s = preg_replace('/:\s*,/', ':', $s);
    $s = preg_replace('/,\s*\./', '.', $s);
    $s = preg_replace('/\.(\s*\.)+/', '.', $s);
    $s = preg_replace('/\s+([.,:;])/', '$1', $s);
    return trim($s, " ,:-–|");
}
function seo_cut(string $s, int $max): string
{
    $s = trim($s);
    if (mb_strlen($s) <= $max) return $s;
    $cut = mb_substr($s, 0, $max - 1);
    $sp = mb_strrpos($cut, ' ');
    if ($sp !== false && $sp > $max * 0.6) $cut = mb_substr($cut, 0, $sp);
    return rtrim($cut, " ,.;:-") . '…';
}

function seo_site_tokens(): array
{
    return ['site' => setting('site_name', 'Your Name Realty'), 'tagline' => setting('tagline')];
}
function seo_property_tokens(array $p): array
{
    $num = fn($n, $unit) => ((float)$n > 0) ? rtrim(rtrim(number_format((float)$n, 1), '0'), '.') . ' ' . $unit : '';
    $status = STATUSES[$p['status'] ?? 'for_sale'] ?? '';
    $type = TYPES[$p['type'] ?? 'house'] ?? '';
    return seo_site_tokens() + [
        'title' => (string)($p['title'] ?? ''), 'address' => (string)($p['address'] ?? ''), 'city' => (string)($p['city'] ?? ''),
        'in_city' => trim((string)($p['city'] ?? '')) !== '' ? 'in ' . trim((string)$p['city']) : '',
        'province' => (string)($p['province'] ?? ''), 'postal' => (string)($p['postal'] ?? ''), 'mls' => (string)($p['mls_number'] ?? ''),
        'price_text' => ((int)($p['price'] ?? 0) > 0) ? money($p['price']) . (($p['status'] ?? '') === 'for_rent' ? '/mo' : '') : '',
        'beds_text' => $num($p['bedrooms'] ?? 0, 'bed'), 'baths_text' => $num($p['bathrooms'] ?? 0, 'bath'),
        'sqft_text' => ((int)($p['sqft'] ?? 0) > 0) ? number_format((int)$p['sqft']) . ' sq ft' : '',
        'type' => $type, 'status' => $status, 'status_lc' => strtolower($status),
        'excerpt' => excerpt((string)($p['description'] ?? ''), 110),
    ];
}

/** Title, description, image and robots for a property: its own settings win, templates fill the gaps. */
function seo_for_property(array $p, array $images): array
{
    $t = seo_property_tokens($p);
    $title = trim((string)($p['seo_title'] ?? '')) !== '' ? $p['seo_title'] : seo_setting('seo_property_title');
    $desc = trim((string)($p['seo_desc'] ?? '')) !== '' ? $p['seo_desc'] : seo_setting('seo_property_desc');
    $img = trim((string)($p['seo_image'] ?? ''));
    if ($img === '' && $images) $img = url('uploads/' . $images[0]['filename']);
    return [
        'title' => seo_cut(seo_fill($title, $t), 120),
        'desc' => seo_cut(seo_fill($desc, $t), 160),
        'image' => $img,
        'type' => 'website',
        'canonical' => abs_url(property_url($p)),
        'robots' => !empty($p['seo_noindex']) ? 'noindex,follow' : '',
    ];
}
function seo_page_title(string $page): string
{
    return seo_cut(seo_fill(seo_setting('seo_title_format'), seo_site_tokens() + ['page' => $page]), 120);
}
function seo_home(): array
{
    $t = seo_site_tokens();
    return ['title' => seo_cut(seo_fill(seo_setting('seo_home_title'), $t), 120), 'desc' => seo_cut(seo_fill(seo_setting('seo_home_desc'), $t), 160)];
}

// ---------- structured data ----------
function seo_social_urls(): array
{
    $o = [];
    foreach (['facebook', 'instagram', 'youtube', 'linkedin', 'x', 'tiktok'] as $k) {
        $u = setting('social_' . $k);
        if (preg_match('~^https?://~i', $u)) $o[] = $u;
    }
    return $o;
}
function seo_business_ld(): array
{
    $site = setting('site_name', 'Your Name Realty');
    $b = ['@type' => setting('biz_type', 'RealEstateAgent') ?: 'RealEstateAgent', '@id' => abs_url(url()) . '#business', 'name' => setting('biz_name') ?: $site, 'url' => abs_url(url())];
    if ($v = setting('logo')) $b['logo'] = abs_url($v);
    if ($v = setting('seo_default_image')) $b['image'] = abs_url($v);
    if ($v = setting('phone')) $b['telephone'] = $v;
    if ($v = setting('contact_email')) $b['email'] = $v;
    if ($v = setting('biz_price_range')) $b['priceRange'] = $v;
    if ($v = setting('biz_area')) $b['areaServed'] = array_values(array_filter(array_map('trim', explode(',', $v))));
    if (setting('biz_street') || setting('biz_city')) {
        $b['address'] = array_filter(['@type' => 'PostalAddress', 'streetAddress' => setting('biz_street'), 'addressLocality' => setting('biz_city'),
            'addressRegion' => setting('biz_province'), 'postalCode' => setting('biz_postal'), 'addressCountry' => setting('biz_country', 'CA')]);
    }
    if ($s = seo_social_urls()) $b['sameAs'] = $s;
    return $b;
}
function seo_home_ld(): array
{
    return [
        '@context' => 'https://schema.org',
        '@graph' => [
            ['@type' => 'WebSite', 'name' => setting('site_name', 'Your Name Realty'), 'url' => abs_url(url()),
             'potentialAction' => ['@type' => 'SearchAction', 'target' => abs_url(url('listings.php')) . '?q={search_term_string}', 'query-input' => 'required name=search_term_string']],
            seo_business_ld(),
        ],
    ];
}
function seo_breadcrumb_ld(array $trail): array
{
    $items = [];
    foreach (array_values($trail) as $i => [$name, $u]) $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => abs_url($u)];
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
}
function seo_property_ld(array $p, array $images, array $seo): array
{
    $cur = setting('currency', 'CAD') ?: 'CAD';
    $kind = ['house' => 'SingleFamilyResidence', 'condo' => 'Apartment', 'townhouse' => 'House', 'land' => 'Place', 'commercial' => 'Accommodation'][$p['type'] ?? 'house'] ?? 'Accommodation';
    $about = array_filter([
        '@type' => $kind, 'name' => $p['title'],
        'address' => array_filter(['@type' => 'PostalAddress', 'streetAddress' => $p['address'], 'addressLocality' => $p['city'], 'addressRegion' => $p['province'],
                                   'postalCode' => $p['postal'], 'addressCountry' => setting('biz_country', 'CA') ?: 'CA']),
        'numberOfBedrooms' => (int)$p['bedrooms'] ?: null, 'numberOfBathroomsTotal' => (float)$p['bathrooms'] ?: null,
        'floorSize' => (int)$p['sqft'] ? ['@type' => 'QuantitativeValue', 'value' => (int)$p['sqft'], 'unitCode' => 'FTK'] : null,
        'yearBuilt' => (int)($p['year_built'] ?? 0) ?: null,
    ]);
    $ld = [
        '@context' => 'https://schema.org', '@type' => 'RealEstateListing', 'name' => $seo['title'], 'url' => $seo['canonical'],
        'description' => $seo['desc'], 'datePosted' => date('Y-m-d', strtotime((string)$p['created_at']) ?: time()),
        'image' => array_map(fn($im) => abs_url(url('uploads/' . $im['filename'])), array_slice($images, 0, 8)),
        'about' => $about,
    ];
    if ((int)$p['price'] > 0) {
        $ld['offers'] = ['@type' => 'Offer', 'price' => (int)$p['price'], 'priceCurrency' => $cur, 'url' => $seo['canonical'],
                         'availability' => ($p['status'] === 'sold') ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock'];
    }
    if (!$ld['image']) unset($ld['image']);
    return $ld;
}

// ---------- <head> output ----------
function seo_favicon_tag(): string
{
    if ($f = setting('favicon')) {
        $type = preg_match('/\.png($|\?)/i', $f) ? 'image/png' : (preg_match('/\.webp($|\?)/i', $f) ? 'image/webp' : 'image/jpeg');
        return '<link rel="icon" type="' . $type . '" href="' . e($f) . '">';
    }
    $c = theme_palette('light')['primary'];
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="' . $c . '"/><path d="M14 33 32 17l18 16" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 31v17h24V31" fill="none" stroke="#fff" stroke-width="5" stroke-linejoin="round"/></svg>';
    return '<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,' . rawurlencode($svg) . '">';
}

/** Prints title, description, canonical, robots, Open Graph, Twitter, verification, favicon and JSON-LD. */
function seo_head(array $d): void
{
    $site = setting('site_name', 'Your Name Realty');
    $title = $d['title'] ?? $site;
    $desc = $d['desc'] ?? '';
    $path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = preg_replace('#/index\.php$#', '/', $path);
    $canonical = $d['canonical'] ?? (site_origin() . $path);
    $img = $d['image'] ?? '';
    if ($img === '') $img = setting('seo_default_image') ?: setting('logo');
    $robots = setting('seo_noindex') === '1' ? 'noindex,nofollow' : ($d['robots'] ?? '');
    $img = $img !== '' ? abs_url($img) : '';
    $m = fn($attr, $name, $val) => '  <meta ' . $attr . '="' . e($name) . '" content="' . e($val) . '">' . "\n";

    echo '  <title>' . e($title) . "</title>\n";
    if ($desc !== '') echo $m('name', 'description', $desc);
    echo '  <link rel="canonical" href="' . e($canonical) . '">' . "\n";
    if ($robots !== '') echo $m('name', 'robots', $robots);
    echo $m('property', 'og:type', $d['type'] ?? 'website') . $m('property', 'og:site_name', $site) . $m('property', 'og:title', $title);
    if ($desc !== '') echo $m('property', 'og:description', $desc);
    echo $m('property', 'og:url', $canonical);
    if ($img !== '') echo $m('property', 'og:image', $img);
    echo $m('name', 'twitter:card', $img !== '' ? 'summary_large_image' : 'summary') . $m('name', 'twitter:title', $title);
    if ($desc !== '') echo $m('name', 'twitter:description', $desc);
    if ($img !== '') echo $m('name', 'twitter:image', $img);
    echo $m('name', 'theme-color', theme_palette('light')['header_bg']);
    if ($v = setting('seo_gsc')) echo $m('name', 'google-site-verification', $v);
    if ($v = setting('seo_bing')) echo $m('name', 'msvalidate.01', $v);
    echo '  ' . seo_favicon_tag() . "\n";
    foreach ($d['jsonld'] ?? [] as $ld) {
        echo '  <script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . "</script>\n";
    }
}
