<?php
// Emails visitors about new listings that match their saved searches.
// Run it on a schedule, e.g. every hour via cPanel "Cron Jobs":
//     php /path/to/realestate/send_alerts.php
// or, if your host only offers URL cron, call  /send_alerts.php?key=YOUR_KEY  (key is shown in Admin > Tools); set the Website address in Settings first.
require_once __DIR__ . '/includes/bootstrap.php';

$cli = PHP_SAPI === 'cli';
if (!$cli) {
    $key = setting('alerts_key');
    if ($key === '' || !hash_equals($key, (string)($_GET['key'] ?? ''))) { http_response_code(403); exit('Forbidden'); }
    header('Content-Type: text/plain');
}
$site = setting('site_name', 'Your Name Realty');
$from = filter_var(setting('contact_email'), FILTER_VALIDATE_EMAIL) ? setting('contact_email') : 'noreply@' . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
$base = rtrim(setting('site_url'), '/');
if ($base === '') $base = $cli ? '' : rtrim(site_origin() . BASE_URL, '/');
$sent = 0;
$maxId = (int)db()->query('SELECT COALESCE(MAX(id),0) FROM properties')->fetchColumn();
foreach (db()->query('SELECT s.*, u.email, u.name FROM saved_searches s JOIN users u ON u.id = s.user_id')->fetchAll() as $s) {
    $f = search_clean(json_decode($s['filters'], true) ?: []);
    [$where, $args] = search_where($f);
    $where[] = 'id > :last'; $args[':last'] = (int)$s['last_property_id'];
    $where[] = "status != 'sold'";
    $q = db()->prepare('SELECT * FROM properties WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 10');
    $q->execute($args);
    $new = $q->fetchAll();
    if ($new && filter_var($s['email'], FILTER_VALIDATE_EMAIL)) {
        $lines = array_map(fn($p) => '- ' . $p['title'] . ', ' . $p['city'] . ', ' . money($p['price']) . "\n  " . $base . '/property.php?id=' . (int)$p['id'], $new);
        $body = 'Hi ' . mail_clean((string)$s['name']) . ",\n\nNew listings match your saved search (" . search_label($f) . "):\n\n" . implode("\n", $lines)
            . "\n\nTo stop these emails, log in and remove the search under My saved searches.\n\n" . $site;
        if (@mail($s['email'], mail_clean('New listings for you at ' . $site), $body, 'From: ' . mail_clean($site) . ' <' . $from . '>')) $sent++;
    }
    db()->prepare('UPDATE saved_searches SET last_property_id = ? WHERE id = ?')->execute([$maxId, $s['id']]);
}
echo "Alerts sent: $sent\n";
