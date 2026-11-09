<?php
// Home page builder: section types and their fields, cleaning, and rendering.
declare(strict_types=1);

/**
 * Each section type: [label, description, fields].
 * Each field: key => [kind, label, default, extra, addLabel]
 *   kinds: text, textarea, select (extra = options), number (extra = min/max), checkbox, image, url, code,
 *          repeater (extra = sub-fields, same format)
 */
const HOME_TYPES = [
    'content' => ['Page text', 'The text you write in the editor above. Move it up or down among the sections, or switch it off.', []],
    'hero' => ['Hero banner', 'The big opening banner: headline, optional photo background, search box and button.', [
        'headline'     => ['text', 'Headline', 'Find a home you love'],
        'subheadline'  => ['text', 'Sub-headline (blank = your site tagline)', ''],
        'style'        => ['select', 'Background', 'gradient', ['gradient' => 'Brand gradient', 'image' => 'Photo', 'solid' => 'Solid brand color']],
        'image'        => ['image', 'Background photo (for the Photo style)', ''],
        'overlay'      => ['number', 'Photo darkness, 0 to 80 (%)', 45, ['min' => 0, 'max' => 80]],
        'align'        => ['select', 'Text alignment', 'left', ['left' => 'Left', 'center' => 'Centered']],
        'height'       => ['select', 'Height', 'normal', ['compact' => 'Compact', 'normal' => 'Normal', 'tall' => 'Tall']],
        'show_search'  => ['checkbox', 'Show the search box', 1],
        'button_label' => ['text', 'Button text (optional)', ''],
        'button_url'   => ['url', 'Button link', ''],
    ]],
    'featured' => ['Featured properties', 'Listings you have marked "Featured" in Properties.', [
        'title'    => ['text', 'Heading', 'Featured properties'],
        'subtitle' => ['text', 'Short line under the heading', ''],
        'count'    => ['number', 'How many to show (1 to 12)', 6, ['min' => 1, 'max' => 12]],
    ]],
    'latest' => ['Latest listings', 'Your newest active listings.', [
        'title'     => ['text', 'Heading', 'Latest listings'],
        'count'     => ['number', 'How many to show (1 to 12)', 6, ['min' => 1, 'max' => 12]],
        'show_link' => ['checkbox', 'Show a "View all" link', 1],
    ]],
    'cities' => ['Browse by city', 'Tiles for the cities where you have listings, linking to a filtered search.', [
        'title' => ['text', 'Heading', 'Browse by city'],
        'count' => ['number', 'How many cities (1 to 16)', 8, ['min' => 1, 'max' => 16]],
    ]],
    'split' => ['Photo + text', 'A photo next to some text, good for "Meet your agent" or "How I work".', [
        'title'        => ['text', 'Heading', 'Meet your agent'],
        'text'         => ['textarea', 'Text (a blank line starts a new paragraph)', "Tell visitors who you are, the areas you serve and what it is like to work with you."],
        'image'        => ['image', 'Photo', ''],
        'image_side'   => ['select', 'Photo side', 'left', ['left' => 'Left', 'right' => 'Right']],
        'button_label' => ['text', 'Button text (optional)', 'Get in touch'],
        'button_url'   => ['url', 'Button link', 'contact.php'],
    ]],
    'text' => ['Text block', 'A heading and a few paragraphs.', [
        'title' => ['text', 'Heading', 'Why work with me'],
        'text'  => ['textarea', 'Text (a blank line starts a new paragraph)', ''],
        'align' => ['select', 'Alignment', 'left', ['left' => 'Left', 'center' => 'Centered']],
    ]],
    'stats' => ['Numbers', 'A row of big numbers, such as homes sold or years in the business.', [
        'title' => ['text', 'Heading (optional)', ''],
        'items' => ['repeater', 'Numbers', [
            ['number' => '250+', 'label' => 'Homes sold'], ['number' => '15 years', 'label' => 'Local experience'], ['number' => '4.9/5', 'label' => 'Client rating'],
        ], ['number' => ['text', 'Number', ''], 'label' => ['text', 'Label', '']], '+ Add number'],
    ]],
    'testimonials' => ['Testimonials', 'Quotes from happy clients.', [
        'title' => ['text', 'Heading', 'What clients say'],
        'items' => ['repeater', 'Quotes', [
            ['quote' => 'Professional, patient and always available. We found our home in three weeks.', 'name' => 'A happy client', 'role' => 'Home buyer'],
        ], ['quote' => ['textarea', 'Quote', ''], 'name' => ['text', 'Name', ''], 'role' => ['text', 'Role or place', '']], '+ Add quote'],
    ]],
    'sold' => ['Recently sold', 'A gallery of your sold listings, good social proof. Mark a property as Sold to include it.', [
        'title' => ['text', 'Heading', 'Recently sold'],
        'count' => ['number', 'How many to show (1 to 12)', 3, ['min' => 1, 'max' => 12]],
    ]],
    'tiles' => ['Neighbourhoods / areas', 'Photo tiles for the areas you serve, each linking to a search or a page.', [
        'title' => ['text', 'Heading', 'Areas I serve'],
        'items' => ['repeater', 'Areas', [
            ['name' => 'Downtown', 'blurb' => 'Condos and lofts near everything', 'image' => '', 'link' => 'listings.php?q=Downtown'],
        ], ['name' => ['text', 'Name', ''], 'blurb' => ['text', 'One-line description', ''], 'image' => ['image', 'Photo', ''], 'link' => ['url', 'Link', '']], '+ Add area'],
    ]],
    'team' => ['Team', 'Photos and short bios for each agent on your team.', [
        'title' => ['text', 'Heading', 'Meet the team'],
        'items' => ['repeater', 'Team members', [
            ['name' => 'Your Name', 'role' => 'Sales Representative', 'bio' => '', 'image' => '', 'email' => '', 'phone' => ''],
        ], ['name' => ['text', 'Name', ''], 'role' => ['text', 'Role', ''], 'image' => ['image', 'Photo', ''], 'bio' => ['textarea', 'Short bio', ''], 'email' => ['text', 'Email', ''], 'phone' => ['text', 'Phone', '']], '+ Add team member'],
    ]],
    'cta' => ['Call to action', 'A band with a message and a button.', [
        'heading'      => ['text', 'Heading', 'Thinking of buying or selling?'],
        'text'         => ['text', 'Text', 'Get in touch for a no-pressure conversation.'],
        'button_label' => ['text', 'Button text', 'Contact me'],
        'button_url'   => ['url', 'Button link', 'contact.php'],
        'style'        => ['select', 'Style', 'soft', ['soft' => 'Soft background', 'brand' => 'Brand color']],
    ]],
    'video' => ['Video', 'An embedded YouTube or Vimeo video.', [
        'title' => ['text', 'Heading (optional)', ''],
        'url'   => ['url', 'YouTube or Vimeo link', ''],
    ]],
    'html' => ['Custom code', 'Paste an embed from another service (a calendar, a form, a widget). Printed exactly as written, so only use code you trust.', [
        'title' => ['text', 'Heading (optional)', ''],
        'code'  => ['code', 'HTML or embed code', ''],
    ]],
];

/** The starting layout (also used by "Reset to default layout"). */
function home_seed_defaults(PDO $pdo, ?string $headline = null): void
{
    $ins = $pdo->prepare('INSERT INTO home_sections (type, sort, config, page_id) VALUES (?,?,?,0)');
    $layout = [['hero', $headline !== null ? ['headline' => $headline] : []], ['featured', []], ['latest', []], ['cta', []]];
    foreach ($layout as $i => [$type, $cfg]) $ins->execute([$type, $i, json_encode($cfg)]);
}

function home_defaults(string $type): array
{
    $out = [];
    foreach (HOME_TYPES[$type][2] as $k => $d) $out[$k] = $d[2];
    return $out;
}
/** Stored settings of a section merged over the defaults. */
function home_config(array $row): array
{
    if (!isset(HOME_TYPES[$row['type']])) return [];
    $stored = json_decode((string)($row['config'] ?? ''), true);
    return array_replace(home_defaults($row['type']), is_array($stored) ? $stored : []);
}

function home_clean_config(string $type, array $in): array
{
    $out = [];
    foreach (HOME_TYPES[$type][2] as $k => $d) {
        [$kind, , $def] = $d;
        $extra = $d[3] ?? null;
        $v = $in[$k] ?? null;
        switch ($kind) {
            case 'text':     $out[$k] = mb_substr(trim(preg_replace('/\s+/', ' ', (string)$v)), 0, 200); break;
            case 'textarea': $out[$k] = mb_substr(trim(str_replace("\r", '', (string)$v)), 0, 4000); break;
            case 'select':   $out[$k] = isset($extra[(string)$v]) ? (string)$v : $def; break;
            case 'number':   $out[$k] = ($v === null || $v === '') ? $def : max($extra['min'] ?? 0, min($extra['max'] ?? 100, (int)$v)); break;
            case 'checkbox': $out[$k] = !empty($v) ? 1 : 0; break;
            case 'image':    $s = trim((string)$v); $out[$k] = preg_match('#^(https?://|/)[^\s"\'<>]*$#', $s) ? $s : ''; break;
            case 'url':      $s = trim((string)$v); $out[$k] = $s !== '' ? (nav_clean_url($s) ?? '') : ''; break;
            case 'code':     $out[$k] = mb_substr((string)$v, 0, 20000); break;
            case 'repeater':
                $rows = [];
                foreach ((array)$v as $r) {
                    if (!is_array($r)) continue;
                    $row = [];
                    $any = false;
                    foreach ($extra as $sk => $sd) {
                        $sv = trim((string)($r[$sk] ?? ''));
                        if ($sd[0] === 'image') $sv = preg_match('#^(https?://|/)[^\s"\'<>]*$#', $sv) ? $sv : '';
                        elseif ($sd[0] === 'url') $sv = $sv !== '' ? (nav_clean_url($sv) ?? '') : '';
                        else $sv = $sd[0] === 'textarea' ? mb_substr($sv, 0, 1000) : mb_substr(preg_replace('/\s+/', ' ', $sv), 0, 200);
                        $row[$sk] = $sv;
                        if ($sv !== '') $any = true;
                    }
                    if ($any) $rows[] = $row;
                    if (count($rows) >= 12) break;
                }
                $out[$k] = $rows;
                break;
        }
    }
    return $out;
}

// ---------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------
function home_href(string $u): string
{
    if ($u === '') return '';
    return preg_match('/^([a-z][a-z0-9+.\-]*:|#|\/\/)/i', $u) ? $u : url($u);
}
function home_paras(string $t): string
{
    $o = '';
    foreach (preg_split('/\n{2,}/', trim($t)) as $p) if (trim($p) !== '') $o .= '<p>' . nl2br(e(trim($p))) . '</p>';
    return $o;
}
function home_button(string $label, string $url, string $class = 'btn'): string
{
    return ($label !== '' && $url !== '') ? '<a class="' . $class . '" href="' . e(home_href($url)) . '">' . e($label) . '</a>' : '';
}

/** Print the enabled sections of the home page (page id 0) or of a CMS page. $page is the pages row, for the "Page text" block. */
function home_render_all(int $pageId = 0, ?array $page = null): void
{
    $st = db()->prepare('SELECT * FROM home_sections WHERE enabled = 1 AND page_id = ? ORDER BY sort, id');
    $st->execute([$pageId]);
    $rows = $st->fetchAll();
    $hasH1 = (bool)array_filter($rows, fn($r) => in_array($r['type'], ['hero', 'content'], true));
    if (!$hasH1) echo '<h1 class="sr-only">' . e($page ? $page['title'] : setting('site_name', 'Your Name Realty')) . '</h1>';
    $first = true;
    foreach ($rows as $row) {
        if (!isset(HOME_TYPES[$row['type']])) continue;
        if ($row['type'] === 'content') {
            if ($page) home_sec_content($page, $first);
            $first = false;
            continue;
        }
        $fn = 'home_sec_' . $row['type'];
        $fn(home_config($row), (int)$row['id'], $first);
        if ($row['type'] === 'hero') $first = false;
    }
}

function home_sec_content(array $page, bool $first): void
{
    $h = $first ? 'h1' : 'h2';
    echo '<article class="readable section"><' . $h . '>' . e($page['title']) . '</' . $h . '><div class="cms">' . $page['content'] . '</div></article>';
}

function home_sec_hero(array $c, int $id, bool $first): void
{
    $style = $c['style'];
    $img = (string)$c['image'];
    $photo = $style === 'image' && $img !== '';
    $a = number_format(max(0, min(80, (int)$c['overlay'])) / 100, 2, '.', '');
    $inline = $photo ? ' style="background-image:linear-gradient(rgba(8,12,18,' . $a . '),rgba(8,12,18,' . $a . ')),url(\'' . e($img) . '\')"' : '';
    $tag = $first ? 'h1' : 'h2';
    $sub = trim((string)$c['subheadline']) !== '' ? $c['subheadline'] : setting('tagline', 'Local expertise. Honest advice.');
    $cities = $c['show_search'] ? db()->query("SELECT DISTINCT city FROM properties WHERE city != '' ORDER BY city")->fetchAll(PDO::FETCH_COLUMN) : [];
    ?>
    <section class="hero hero-<?= e($style) ?> hero-<?= e($c['align']) ?> hero-<?= e($c['height']) ?><?= $photo ? ' has-photo' : '' ?>"<?= $inline ?>>
      <div class="wrap">
        <<?= $tag ?>><?= e($c['headline']) ?></<?= $tag ?>>
        <?php if ($sub !== ''): ?><p><?= e($sub) ?></p><?php endif; ?>
        <?php if ($c['show_search']): ?>
        <form class="search-bar" action="<?= url('listings.php') ?>" method="get">
          <input type="search" name="q" placeholder="City, address or MLS&reg; #" list="cities-<?= $id ?>" aria-label="Search listings">
          <datalist id="cities-<?= $id ?>"><?php foreach ($cities as $ct): ?><option value="<?= e($ct) ?>"><?php endforeach; ?></datalist>
          <select name="status" aria-label="Buy or rent">
            <option value="">Buy or rent</option>
            <?php foreach (STATUSES as $k => $v) echo '<option value="' . e($k) . '">' . e($v) . '</option>'; ?>
          </select>
          <select name="beds" aria-label="Bedrooms">
            <option value="">Beds</option>
            <?php for ($i = 1; $i <= 5; $i++) echo "<option value=\"$i\">$i+</option>"; ?>
          </select>
          <button class="btn" type="submit">Search</button>
        </form>
        <?php endif; ?>
        <?php if ($b = home_button((string)$c['button_label'], (string)$c['button_url'])): ?><p class="hero-actions"><?= $b ?></p><?php endif; ?>
        <?php if (trim(setting('idx_embed')) !== ''): ?>
          <p class="hero-link"><a href="<?= url('idx.php') ?>">Search every MLS&reg; listing &rarr;</a></p>
        <?php endif; ?>
      </div>
    </section>
    <?php
}

function home_sec_featured(array $c, int $id, bool $first): void
{
    $n = max(1, min(12, (int)$c['count']));
    $rows = db()->query("SELECT * FROM properties WHERE featured = 1 AND status != 'sold' ORDER BY created_at DESC, id DESC LIMIT $n")->fetchAll();
    if (!$rows) return;
    ?>
    <section class="wrap section">
      <h2><?= e($c['title']) ?></h2>
      <?php if (trim((string)$c['subtitle']) !== ''): ?><p class="muted section-sub"><?= e($c['subtitle']) ?></p><?php endif; ?>
      <div class="grid"><?php foreach ($rows as $p) render_card($p); ?></div>
    </section>
    <?php
}

function home_sec_latest(array $c, int $id, bool $first): void
{
    $n = max(1, min(12, (int)$c['count']));
    $rows = db()->query("SELECT * FROM properties WHERE status != 'sold' ORDER BY created_at DESC, id DESC LIMIT $n")->fetchAll();
    ?>
    <section class="wrap section">
      <div class="section-head"><h2><?= e($c['title']) ?></h2><?php if ($c['show_link']): ?><a href="<?= url('listings.php') ?>">View all &rarr;</a><?php endif; ?></div>
      <?php if ($rows): ?>
        <div class="grid"><?php foreach ($rows as $p) render_card($p); ?></div>
      <?php else: ?>
        <p class="muted">No listings yet. Add your first property in the admin area.</p>
      <?php endif; ?>
    </section>
    <?php
}

function home_sec_cities(array $c, int $id, bool $first): void
{
    $n = max(1, min(16, (int)$c['count']));
    $rows = db()->query("SELECT city, COUNT(*) AS n FROM properties WHERE city != '' AND status != 'sold' GROUP BY city ORDER BY n DESC, city LIMIT $n")->fetchAll();
    if (!$rows) return;
    ?>
    <section class="wrap section">
      <h2><?= e($c['title']) ?></h2>
      <div class="city-grid">
        <?php foreach ($rows as $r): ?>
          <a class="city-tile" href="<?= e(url('listings.php?q=' . rawurlencode($r['city']))) ?>"><strong><?= e($r['city']) ?></strong><span><?= (int)$r['n'] ?> home<?= (int)$r['n'] === 1 ? '' : 's' ?></span></a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php
}

function home_sec_split(array $c, int $id, bool $first): void
{
    ?>
    <section class="wrap section">
      <div class="split split-<?= e($c['image_side']) ?><?= $c['image'] === '' ? ' no-photo' : '' ?>">
        <?php if ($c['image'] !== ''): ?><figure><img src="<?= e($c['image']) ?>" alt="<?= e($c['title']) ?>" loading="lazy"></figure><?php endif; ?>
        <div class="split-text">
          <?php if ($c['title'] !== ''): ?><h2><?= e($c['title']) ?></h2><?php endif; ?>
          <?= home_paras((string)$c['text']) ?>
          <?= home_button((string)$c['button_label'], (string)$c['button_url']) ?>
        </div>
      </div>
    </section>
    <?php
}

function home_sec_text(array $c, int $id, bool $first): void
{
    ?>
    <section class="wrap section readable<?= $c['align'] === 'center' ? ' text-center' : '' ?>">
      <?php if ($c['title'] !== ''): ?><h2><?= e($c['title']) ?></h2><?php endif; ?>
      <?= home_paras((string)$c['text']) ?>
    </section>
    <?php
}

function home_sec_stats(array $c, int $id, bool $first): void
{
    $items = array_values(array_filter((array)$c['items'], fn($i) => trim((string)($i['number'] ?? '')) !== ''));
    if (!$items) return;
    ?>
    <section class="stats-band">
      <div class="wrap">
        <?php if ($c['title'] !== ''): ?><h2 class="text-center"><?= e($c['title']) ?></h2><?php endif; ?>
        <div class="stat-grid">
          <?php foreach ($items as $i): ?><div><span class="stat-num"><?= e($i['number']) ?></span><span class="stat-lbl"><?= e($i['label'] ?? '') ?></span></div><?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php
}

function home_sec_testimonials(array $c, int $id, bool $first): void
{
    $items = array_values(array_filter((array)$c['items'], fn($i) => trim((string)($i['quote'] ?? '')) !== ''));
    if (!$items) return;
    ?>
    <section class="wrap section">
      <?php if ($c['title'] !== ''): ?><h2><?= e($c['title']) ?></h2><?php endif; ?>
      <div class="quotes">
        <?php foreach ($items as $i): ?>
          <figure class="quote">
            <blockquote><?= home_paras((string)$i['quote']) ?></blockquote>
            <?php if (($i['name'] ?? '') !== ''): ?><figcaption><strong><?= e($i['name']) ?></strong><?php if (($i['role'] ?? '') !== ''): ?><span><?= e($i['role']) ?></span><?php endif; ?></figcaption><?php endif; ?>
          </figure>
        <?php endforeach; ?>
      </div>
    </section>
    <?php
}

function home_sec_cta(array $c, int $id, bool $first): void
{
    $brand = $c['style'] === 'brand';
    ?>
    <section class="cta<?= $brand ? ' cta-brand' : '' ?>">
      <div class="wrap">
        <h2><?= e($c['heading']) ?></h2>
        <?php if ($c['text'] !== ''): ?><p><?= e($c['text']) ?></p><?php endif; ?>
        <?= home_button((string)$c['button_label'], (string)$c['button_url'], $brand ? 'btn btn-light' : 'btn') ?>
      </div>
    </section>
    <?php
}

function home_sec_video(array $c, int $id, bool $first): void
{
    $embed = embed_url((string)$c['url']);
    if ($embed === null) return;
    ?>
    <section class="wrap section readable">
      <?php if ($c['title'] !== ''): ?><h2><?= e($c['title']) ?></h2><?php endif; ?>
      <div class="video"><iframe src="<?= e($embed) ?>" title="<?= e($c['title'] !== '' ? $c['title'] : 'Video') ?>" allowfullscreen loading="lazy"
        allow="accelerometer; encrypted-media; gyroscope; picture-in-picture"></iframe></div>
    </section>
    <?php
}

function home_sec_html(array $c, int $id, bool $first): void
{
    if (trim((string)$c['code']) === '') return;
    ?>
    <section class="wrap section">
      <?php if ($c['title'] !== ''): ?><h2><?= e($c['title']) ?></h2><?php endif; ?>
      <?= $c['code'] /* trusted admin code, printed as written */ ?>
    </section>
    <?php
}

function home_sec_sold(array $c, int $id, bool $first): void
{
    $n = max(1, min(12, (int)$c['count']));
    $rows = db()->query("SELECT * FROM properties WHERE status = 'sold' ORDER BY created_at DESC, id DESC LIMIT $n")->fetchAll();
    if (!$rows) return;
    ?>
    <section class="wrap section">
      <h2><?= e($c['title']) ?></h2>
      <div class="grid"><?php foreach ($rows as $p) render_card($p); ?></div>
    </section>
    <?php
}

function home_sec_tiles(array $c, int $id, bool $first): void
{
    $items = array_values(array_filter((array)$c['items'], fn($i) => trim((string)($i['name'] ?? '')) !== ''));
    if (!$items) return;
    ?>
    <section class="wrap section">
      <?php if ($c['title'] !== ''): ?><h2><?= e($c['title']) ?></h2><?php endif; ?>
      <div class="area-grid">
        <?php foreach ($items as $i): $href = home_href((string)($i['link'] ?? '')); ?>
          <<?= $href !== '' ? 'a href="' . e($href) . '"' : 'div' ?> class="area-tile"<?= ($i['image'] ?? '') !== '' ? ' style="background-image:url(\'' . e($i['image']) . '\')"' : '' ?>>
            <span class="area-text"><strong><?= e($i['name']) ?></strong><?php if (($i['blurb'] ?? '') !== ''): ?><span><?= e($i['blurb']) ?></span><?php endif; ?></span>
          </<?= $href !== '' ? 'a' : 'div' ?>>
        <?php endforeach; ?>
      </div>
    </section>
    <?php
}

function home_sec_team(array $c, int $id, bool $first): void
{
    $items = array_values(array_filter((array)$c['items'], fn($i) => trim((string)($i['name'] ?? '')) !== ''));
    if (!$items) return;
    ?>
    <section class="wrap section">
      <?php if ($c['title'] !== ''): ?><h2><?= e($c['title']) ?></h2><?php endif; ?>
      <div class="team-grid">
        <?php foreach ($items as $i): ?>
          <article class="member">
            <?php if (($i['image'] ?? '') !== ''): ?><img src="<?= e($i['image']) ?>" alt="<?= e($i['name']) ?>" loading="lazy"><?php endif; ?>
            <h3><?= e($i['name']) ?></h3>
            <?php if (($i['role'] ?? '') !== ''): ?><p class="muted"><?= e($i['role']) ?></p><?php endif; ?>
            <?= home_paras((string)($i['bio'] ?? '')) ?>
            <?php if (($i['email'] ?? '') !== '' && filter_var($i['email'], FILTER_VALIDATE_EMAIL)): ?><p><a href="mailto:<?= e($i['email']) ?>"><?= e($i['email']) ?></a></p><?php endif; ?>
            <?php if (($i['phone'] ?? '') !== ''): ?><p><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $i['phone'])) ?>"><?= e($i['phone']) ?></a></p><?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
    <?php
}
