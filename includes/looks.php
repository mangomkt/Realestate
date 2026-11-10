<?php
// Starter looks: a one-click bundle of colors, fonts, corner style, layout styles and a home page layout.
declare(strict_types=1);

const LOOKS = [
    'classic' => [
        'name' => 'Trusted Classic', 'desc' => 'Navy and gold, a classic serif headline and a familiar layout. Safe for any market.',
        'palette' => 'Navy & Gold', 'font' => 'georgia-sans', 'radius' => '6', 'mode' => 'light',
        'styles' => ['cards' => 'classic', 'header' => 'left', 'footer' => 'columns', 'buttons' => 'solid'],
        'home' => [
            ['hero', ['headline' => 'Find your next home', 'style' => 'gradient', 'height' => 'normal']],
            ['featured', []], ['stats', []], ['latest', []], ['testimonials', []], ['cta', ['style' => 'brand']],
        ],
    ],
    'luxury' => [
        'name' => 'Luxury Estates', 'desc' => 'Black and champagne, elegant type, centered header and photo-led listing cards.',
        'palette' => ['primary' => '#1a1a1a', 'secondary' => '#0d0d0d', 'tertiary' => '#b8975a', 'button' => '#b8975a', 'button_text' => '#111111', 'header_bg' => '#0d0d0d', 'header_text' => '#f2eee6', 'footer_bg' => '#0d0d0d', 'footer_text' => '#cfc8ba', 'text' => '#1d1b18', 'link' => '#8a6d3b', 'bg' => '#faf8f4', 'surface' => '#ffffff'],
        'font' => 'luxe', 'radius' => '0', 'mode' => 'light',
        'styles' => ['cards' => 'overlay', 'header' => 'center', 'footer' => 'simple', 'buttons' => 'outline'],
        'home' => [
            ['hero', ['headline' => 'Exceptional homes, quietly presented', 'style' => 'solid', 'align' => 'center', 'height' => 'tall']],
            ['featured', ['title' => 'Signature properties', 'count' => 6]], ['split', ['title' => 'A private, personal service', 'image_side' => 'right']],
            ['sold', ['title' => 'Recently sold']], ['testimonials', []], ['cta', ['style' => 'brand', 'heading' => 'Let us talk about your next move']],
        ],
    ],
    'modern' => [
        'name' => 'Modern Urban', 'desc' => 'Charcoal and red, bold geometric headings, flat cards. Follows the visitor\'s light/dark setting.',
        'palette' => 'Charcoal & Red', 'font' => 'modern', 'radius' => '12', 'mode' => 'auto',
        'styles' => ['cards' => 'flat', 'header' => 'left', 'footer' => 'columns', 'buttons' => 'solid'],
        'home' => [
            ['hero', ['headline' => 'The city, your way', 'style' => 'gradient', 'height' => 'normal']],
            ['cities', ['title' => 'Explore neighbourhoods']], ['featured', []], ['stats', []], ['latest', []], ['cta', ['style' => 'soft']],
        ],
    ],
    'family' => [
        'name' => 'Warm & Welcoming', 'desc' => 'Sand and sage, friendly serif headings, soft buttons and a personal "meet your agent" section.',
        'palette' => 'Sand & Sage', 'font' => 'warm', 'radius' => '20', 'mode' => 'light',
        'styles' => ['cards' => 'minimal', 'header' => 'left', 'footer' => 'columns', 'buttons' => 'soft'],
        'home' => [
            ['hero', ['headline' => 'Let us help you find home', 'style' => 'gradient', 'align' => 'center', 'height' => 'normal']],
            ['latest', ['title' => 'Homes you might love']], ['split', ['title' => 'Meet your agent', 'image_side' => 'left']],
            ['tiles', []], ['testimonials', []], ['cta', ['style' => 'soft']],
        ],
    ],
    'coastal' => [
        'name' => 'Fresh Coastal', 'desc' => 'Ocean blue with a sunny accent, a clean single font and bright, airy cards.',
        'palette' => ['primary' => '#0b6e8e', 'secondary' => '#08455a', 'tertiary' => '#f2994a', 'button' => '#0b6e8e', 'button_text' => '#ffffff', 'header_bg' => '#ffffff', 'header_text' => '#08455a', 'footer_bg' => '#08455a', 'footer_text' => '#d3e8f0', 'text' => '#17323d', 'link' => '#0b6e8e', 'bg' => '#f6fbfd', 'surface' => '#ffffff'],
        'font' => 'clean', 'radius' => '12', 'mode' => 'light',
        'styles' => ['cards' => 'flat', 'header' => 'left', 'footer' => 'columns', 'buttons' => 'solid'],
        'home' => [
            ['hero', ['headline' => 'Live where life feels easy', 'style' => 'gradient', 'height' => 'tall']],
            ['featured', []], ['video', ['title' => 'Take a tour']], ['latest', []], ['cta', ['style' => 'brand']],
        ],
    ],
    'bold' => [
        'name' => 'Bold Agent', 'desc' => 'Plum and coral, big friendly type, brand-colored header and footer. Great for a personal brand.',
        'palette' => 'Plum & Coral', 'font' => 'bold', 'radius' => '20', 'mode' => 'light',
        'styles' => ['cards' => 'classic', 'header' => 'brand', 'footer' => 'brand', 'buttons' => 'solid'],
        'home' => [
            ['hero', ['headline' => 'Real estate, made personal', 'style' => 'gradient', 'height' => 'tall']],
            ['stats', []], ['featured', []], ['team', []], ['testimonials', []], ['cta', ['style' => 'brand']],
        ],
    ],
];

function look_palette(array $look): array
{
    $p = is_array($look['palette']) ? $look['palette'] : (theme_presets()[$look['palette']] ?? theme_presets()['Teal & Orange (default)']);
    return $p + ['bg' => '#ffffff', 'surface' => '#ffffff'];
}

/** Apply a look. $withHome also replaces the home page sections. Contact details, logo and content are never touched. */
function look_apply(string $key, bool $withHome): bool
{
    if (!isset(LOOKS[$key])) return false;
    $l = LOOKS[$key];
    $pdo = db();
    $pdo->beginTransaction();
    foreach (look_palette($l) as $k => $v) if (array_key_exists($k, THEME_DEFAULTS)) save_setting('theme_' . $k, $v);
    $pdo->exec("DELETE FROM settings WHERE k LIKE 'theme\\_dark\\_%' ESCAPE '\\'");
    save_setting('theme_dark_auto', '1');
    save_setting('theme_font', $l['font']);
    save_setting('theme_radius', $l['radius']);
    save_setting('theme_mode', $l['mode']);
    foreach ($l['styles'] as $k => $v) save_setting('style_' . $k, $v);
    save_setting('look', $key);
    if ($withHome) {
        $pdo->exec('DELETE FROM home_sections WHERE page_id = 0');
        $ins = $pdo->prepare('INSERT INTO home_sections (type, sort, config, page_id) VALUES (?,?,?,0)');
        foreach ($l['home'] as $i => [$type, $cfg]) if (isset(HOME_TYPES[$type])) $ins->execute([$type, $i, json_encode($cfg)]);
        save_setting('home_seeded', '1');
    }
    $pdo->commit();
    return true;
}
