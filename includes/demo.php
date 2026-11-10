<?php
// Sample content: illustrated placeholder listings, photos and pages, so a new site never looks empty.
// Everything it creates is flagged (is_demo) and can be removed in one click.
declare(strict_types=1);

function demo_present(): bool
{
    return (int)db()->query('SELECT COUNT(*) FROM properties WHERE is_demo = 1')->fetchColumn() > 0
        || (int)db()->query('SELECT COUNT(*) FROM pages WHERE is_demo = 1')->fetchColumn() > 0;
}

/** Draws a simple illustrated home. $kind: house|condo|townhouse|bungalow. $dusk lights the windows. Returns JPEG bytes. */
function demo_photo(string $kind, int $seed, bool $dusk): string
{
    mt_srand($seed);
    $W = 1200; $H = 800;
    $im = imagecreatetruecolor($W, $H);
    imageantialias($im, true);
    $c = fn(int $r, int $g, int $b) => imagecolorallocate($im, max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
    // sky
    [$top, $bot] = $dusk ? [[44, 52, 98], [243, 160, 110]] : [[96, 165, 224], [214, 236, 250]];
    for ($y = 0; $y < 560; $y++) {
        $t = $y / 560;
        imageline($im, 0, $y, $W, $y, $c((int)($top[0] + ($bot[0] - $top[0]) * $t), (int)($top[1] + ($bot[1] - $top[1]) * $t), (int)($top[2] + ($bot[2] - $top[2]) * $t)));
    }
    $sun = $dusk ? $c(255, 214, 150) : $c(255, 247, 214);
    imagefilledellipse($im, mt_rand(150, 1050), mt_rand(110, 190), 110, 110, $sun);
    $cloud = $dusk ? $c(255, 205, 175) : $c(255, 255, 255);
    for ($i = 0; $i < 3; $i++) { $x = mt_rand(80, 1100); $y = mt_rand(70, 250); foreach ([[0, 0, 150, 44], [-50, 12, 110, 36], [55, 14, 120, 34]] as [$dx, $dy, $w, $h]) imagefilledellipse($im, $x + $dx, $y + $dy, $w, $h, $cloud); }
    // ground
    $grass = $dusk ? $c(52, 92, 70) : $c(96, 160, 88);
    imagefilledrectangle($im, 0, 560, $W, $H, $grass);
    $win = $dusk ? $c(255, 218, 120) : $c(176, 214, 238);
    $frame = $c(250, 250, 250);
    $palettes = [[[236, 226, 208], [120, 70, 60]], [[210, 222, 232], [70, 82, 96]], [[226, 206, 186], [92, 64, 52]], [[238, 238, 232], [60, 80, 72]], [[214, 196, 170], [88, 58, 44]]];
    [$wall, $roof] = $palettes[$seed % count($palettes)];
    $wallC = $c(...$wall); $roofC = $c(...$roof); $door = $c(max(0, $roof[0] - 20), max(0, $roof[1] - 10), max(0, $roof[2] - 10));
    $windowAt = function (int $x, int $y, int $w, int $h) use ($im, $win, $frame) {
        imagefilledrectangle($im, $x - 4, $y - 4, $x + $w + 4, $y + $h + 4, $frame);
        imagefilledrectangle($im, $x, $y, $x + $w, $y + $h, $win);
        imageline($im, $x + intdiv($w, 2), $y, $x + intdiv($w, 2), $y + $h, $frame);
        imageline($im, $x, $y + intdiv($h, 2), $x + $w, $y + intdiv($h, 2), $frame);
    };
    $path = $c(214, 206, 190);
    if ($kind === 'condo') {
        $skyline = $dusk ? $c(70, 74, 112) : $c(168, 190, 214);
        for ($i = 0; $i < 7; $i++) imagefilledrectangle($im, 40 + $i * 170, mt_rand(170, 330), 40 + $i * 170 + 130, 560, $skyline);
        imagefilledrectangle($im, 380, 70, 820, 600, $wallC);
        imagefilledrectangle($im, 380, 70, 820, 92, $roofC);
        for ($r = 0; $r < 8; $r++) for ($k = 0; $k < 5; $k++) { $lit = $dusk ? (mt_rand(0, 3) > 0) : true; if ($lit) $windowAt(412 + $k * 82, 112 + $r * 60, 46, 34); else imagefilledrectangle($im, 412 + $k * 82, 112 + $r * 60, 458 + $k * 82, 146 + $r * 60, $c(92, 100, 120)); }
        imagefilledrectangle($im, 560, 548, 640, 600, $door);
        imagefilledrectangle($im, 360, 600, 840, 616, $path);
    } elseif ($kind === 'townhouse') {
        $cols = [$wallC, $c(190, 120, 100), $c(120, 140, 160)];
        for ($i = 0; $i < 3; $i++) {
            $x = 170 + $i * 290;
            imagefilledrectangle($im, $x, 270, $x + 280, 590, $cols[($i + $seed) % 3]);
            imagefilledpolygon($im, [$x - 10, 270, $x + 290, 270, $x + 280, 220, $x, 220], $roofC);
            $windowAt($x + 40, 320, 70, 80); $windowAt($x + 170, 320, 70, 80); $windowAt($x + 40, 450, 70, 70);
            imagefilledrectangle($im, $x + 170, 450, $x + 240, 590, $door);
        }
        imagefilledrectangle($im, 150, 590, 1050, 606, $path);
    } else {   // house, bungalow
        $bungalow = $kind === 'bungalow';
        $top = $bungalow ? 360 : 250;
        imagefilledrectangle($im, 260, $top, 940, 590, $wallC);
        imagefilledpolygon($im, [230, $top, 970, $top, 800, $top - ($bungalow ? 110 : 150), 400, $top - ($bungalow ? 110 : 150)], $roofC);
        if (!$bungalow) imagefilledrectangle($im, 760, 150, 810, 250, $c(...$wall) );
        $windowAt(310, $top + 50, 100, 90); $windowAt(740, $top + 50, 100, 90);
        if (!$bungalow) { $windowAt(310, 400, 100, 90); $windowAt(740, 400, 100, 90); $windowAt(525, 280, 110, 60); }
        imagefilledrectangle($im, 530, 590 - 130, 630, 590, $door);
        imagefilledellipse($im, 618, 530, 10, 10, $c(224, 190, 90));
        imagefilledrectangle($im, 500, 590, 660, 604, $path);
        imagefilledpolygon($im, [500, 604, 660, 604, 720, $H, 440, $H], $path);
        // trees
        $trunk = $c(96, 70, 48); $leaf = $dusk ? $c(40, 84, 58) : $c(66, 140, 76);
        foreach ([120, 1080] as $tx) { imagefilledrectangle($im, $tx - 10, 500, $tx + 10, 600, $trunk); imagefilledellipse($im, $tx, 470, 150, 170, $leaf); }
    }
    ob_start();
    imagejpeg($im, null, 82);
    $bytes = (string)ob_get_clean();
    imagedestroy($im);
    return $bytes;
}

const DEMO_LISTINGS = [
    ['Bright Corner Condo with Lake Views', 'condo', 'Toronto', '1204 - 20 Harbourfront Lane', 'M5J 2K4', 2, 2, 1050, 789000, 'for_sale', 1, 'Sat Nov 15, 2-4 pm', 2019, 'Floor-to-ceiling windows, Underground parking, Gym and pool, Walk to the waterfront', 'Wake up to open water and city lights from this sun-filled corner suite. Two real bedrooms, a split layout and a wraparound balcony make it as practical as it is beautiful.'],
    ['Renovated Family Home near Top Schools', 'house', 'Mississauga', '47 Willow Creek Dr', 'L5M 3H2', 4, 3, 2450, 1249000, 'for_sale', 1, '', 2004, 'Finished basement, Hardwood floors, Double garage, Fenced yard', 'Four bedrooms, a chef-style kitchen and a finished lower level, on a quiet street a short walk from highly rated schools and parks.'],
    ['Modern Townhouse by the GO Station', 'townhouse', 'Oakville', '12 Station Row', 'L6J 0A5', 3, 3, 1680, 949000, 'for_sale', 1, 'Sun Nov 16, 1-3 pm', 2017, 'Rooftop terrace, Attached garage, Walk to GO Transit, Smart thermostat', 'Low-maintenance living with a private rooftop terrace and an easy commute. Open concept main floor with 9-foot ceilings.'],
    ['Charming Detached on a Quiet Crescent', 'house', 'Markham', '8 Birchwood Cres', 'L3P 4B8', 3, 2, 1900, 1099000, 'for_sale', 0, '', 1998, 'Updated kitchen, Gas fireplace, Mature trees, Close to transit', 'A warm, well-kept home on a tree-lined crescent. New kitchen, bright family room and a deep south-facing backyard.'],
    ['Downtown Loft with Soaring Ceilings', 'condo', 'Toronto', '305 - 71 King Street East', 'M5C 1G1', 1, 1, 780, 2950, 'for_rent', 0, '', 1912, 'Exposed brick, 12-foot ceilings, Steps to the subway, Locker included', 'A true loft in a converted warehouse: brick, beams and big windows. Available for a one-year lease.'],
    ['Sun-filled Bungalow with Big Backyard', 'bungalow', 'Burlington', '130 Orchard Ave', 'L7R 2P6', 3, 2, 1500, 879000, 'for_sale', 0, '', 1975, 'Large lot, Main-floor laundry, New windows, Walk to the lake', 'One-floor living on an extra-wide lot. Perfect for downsizers or families who want room to play outside.'],
    ['Stately Colonial on a Mature Lot', 'house', 'Oakville', '25 Heritage Lane', 'L6H 1M3', 5, 4, 3600, 1675000, 'sold', 0, '', 2001, 'Pool, Home office, Three-car garage', 'Sold over asking in six days. A reminder of what a well-prepared home can achieve.'],
    ['Starter Condo, Walk to the Subway', 'condo', 'Toronto', '508 - 15 Queen Park Cres', 'M5S 2H1', 1, 1, 560, 549000, 'sold', 0, '', 2012, 'Locker, Balcony, Concierge', 'Snapped up by a first-time buyer in a weekend, with multiple offers.'],
];

function demo_load(): int
{
    if (demo_present()) return 0;
    if (!function_exists('imagecreatetruecolor')) throw new RuntimeException('The PHP GD extension is needed to draw the sample photos.');
    $pdo = db();
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $pdo->beginTransaction();
    $kindOf = ['condo' => 'condo', 'townhouse' => 'townhouse', 'bungalow' => 'bungalow', 'house' => 'house'];
    $pi = $pdo->prepare('INSERT INTO properties (title, description, price, status, type, bedrooms, bathrooms, sqft, address, city, province, postal, mls_number, featured, features, open_house, year_built, is_demo, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1, datetime("now", ?))');
    $img = $pdo->prepare('INSERT INTO property_images (property_id, filename, sort, alt) VALUES (?,?,?,?)');
    foreach (DEMO_LISTINGS as $n => [$title, $kind, $city, $addr, $postal, $bd, $ba, $sq, $price, $status, $feat, $open, $yr, $features, $desc]) {
        $type = $kind === 'bungalow' ? 'house' : $kind;
        $pi->execute([$title, '<p>' . e($desc) . '</p>', $price, $status, $type, $bd, $ba, $sq, $addr, $city, 'ON', $postal, 'DEMO' . str_pad((string)($n + 1), 4, '0', STR_PAD_LEFT),
            $feat, $features, $open, $yr, '-' . ($n * 2) . ' days']);
        $pid = (int)$pdo->lastInsertId();
        foreach ([false, true] as $i => $dusk) {
            $name = bin2hex(random_bytes(12)) . '.jpg';
            file_put_contents(UPLOAD_DIR . '/' . $name, demo_photo($kindOf[$kind], 100 + $n * 7, $dusk));
            $img->execute([$pid, $name, $i, $title . ($dusk ? ' at dusk' : ', front view') . ' (sample illustration)']);
        }
    }
    // Pages
    $pg = $pdo->prepare("INSERT INTO pages (title, slug, content, meta_title, meta_desc, status, is_demo) VALUES (?,?,?,?,?, 'published', 1)");
    $pages = [
        ['About', 'about', '<h2>Hello, I\'m your local agent</h2><p>This is a sample page. Replace this text with your story: how long you have worked in the area, the neighbourhoods you know best and what clients can expect when they work with you.</p><h3>What I do</h3><ul><li>Help buyers find the right home and negotiate with confidence</li><li>Price and market homes so sellers get the best result</li><li>Guide first-time buyers through every step</li></ul>'],
        ['Buyer guide', 'buyer-guide', '<h2>Buying a home, step by step</h2><ol><li><strong>Get pre-approved</strong> so you know your budget.</li><li><strong>Tell me what matters</strong>: location, space, commute, schools.</li><li><strong>View homes</strong> in person or by video.</li><li><strong>Make an offer</strong> with a plan for conditions and timing.</li><li><strong>Close and move in.</strong></li></ol><p>This is sample text. Edit it to match how you work.</p>'],
        ['Seller guide', 'seller-guide', '<h2>Selling your home</h2><p>Sample text: explain your marketing plan, how you price a home, and what you need from the seller. A short checklist works well:</p><ul><li>Free valuation and pricing review</li><li>Professional photos and video</li><li>Staging advice</li><li>Open houses and private showings</li></ul>'],
    ];
    $nav = $pdo->prepare("INSERT INTO nav_items (location, label, type, target, sort, is_demo) VALUES (?, ?, 'page', ?, ?, 1)");
    foreach ($pages as $i => [$t, $slug, $html]) {
        if ($pdo->query('SELECT 1 FROM pages WHERE slug = ' . $pdo->quote($slug))->fetchColumn()) continue;   // never overwrite a real page
        $pg->execute([$t, $slug, sanitize_html($html), $t . ' | ' . setting('site_name', 'Your Name Realty'), mb_substr(trim(strip_tags($html)), 0, 150)]);
        $pid = (string)$pdo->lastInsertId();
        $max = (int)$pdo->query("SELECT COALESCE(MAX(sort), -1) + 1 FROM nav_items WHERE location = 'header'")->fetchColumn();
        if ($i === 0) $nav->execute(['header', $t, $pid, $max]);
        $max = (int)$pdo->query("SELECT COALESCE(MAX(sort), -1) + 1 FROM nav_items WHERE location = 'footer'")->fetchColumn();
        $nav->execute(['footer', $t, $pid, $max]);
    }
    $pdo->commit();
    return count(DEMO_LISTINGS);
}

function demo_clear(): void
{
    $pdo = db();
    $pdo->beginTransaction();
    $ids = $pdo->query('SELECT id FROM properties WHERE is_demo = 1')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        foreach (property_images((int)$id) as $im) delete_upload($im['filename']);
        $pdo->prepare('DELETE FROM property_images WHERE property_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM leads WHERE property_id = ?')->execute([$id]);
    }
    $pdo->exec('DELETE FROM properties WHERE is_demo = 1');
    foreach ($pdo->query('SELECT id FROM pages WHERE is_demo = 1')->fetchAll(PDO::FETCH_COLUMN) as $pid) {
        $pdo->prepare('DELETE FROM home_sections WHERE page_id = ?')->execute([$pid]);
        $pdo->prepare("DELETE FROM nav_items WHERE type = 'page' AND target = ?")->execute([(string)$pid]);
    }
    $pdo->exec('DELETE FROM pages WHERE is_demo = 1');
    $pdo->exec('DELETE FROM nav_items WHERE is_demo = 1');
    $pdo->commit();
}
