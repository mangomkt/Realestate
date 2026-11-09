<?php
// Listing filters shared by listings.php and saved-search alerts.
declare(strict_types=1);

const SEARCH_KEYS = ['q', 'status', 'type', 'min', 'max', 'beds', 'baths'];

/** Keep only known, valid filter values. */
function search_clean(array $g): array
{
    $o = [];
    $q = mb_substr(trim((string)($g['q'] ?? '')), 0, 100);
    if ($q !== '') $o['q'] = $q;
    if (isset(STATUSES[$g['status'] ?? ''])) $o['status'] = $g['status'];
    if (isset(TYPES[$g['type'] ?? ''])) $o['type'] = $g['type'];
    foreach (['min', 'max', 'beds', 'baths'] as $k) if (($g[$k] ?? '') !== '' && ctype_digit((string)$g[$k])) $o[$k] = (string)(int)$g[$k];
    return $o;
}

/** @return array{0:string[],1:array} WHERE fragments and bound arguments */
function search_where(array $f): array
{
    $where = ['1=1'];
    $args = [];
    if (isset($f['q'])) { $where[] = '(city LIKE :q OR address LIKE :q OR mls_number LIKE :q OR title LIKE :q OR postal LIKE :q)'; $args[':q'] = '%' . $f['q'] . '%'; }
    if (isset($f['status'])) { $where[] = 'status = :status'; $args[':status'] = $f['status']; }
    if (isset($f['type'])) { $where[] = 'type = :type'; $args[':type'] = $f['type']; }
    if (isset($f['min'])) { $where[] = 'price >= :min'; $args[':min'] = (int)$f['min']; }
    if (isset($f['max'])) { $where[] = 'price <= :max'; $args[':max'] = (int)$f['max']; }
    if (isset($f['beds'])) { $where[] = 'bedrooms >= :beds'; $args[':beds'] = (int)$f['beds']; }
    if (isset($f['baths'])) { $where[] = 'bathrooms >= :baths'; $args[':baths'] = (int)$f['baths']; }
    return [$where, $args];
}

function search_label(array $f): string
{
    $p = [];
    if (isset($f['q'])) $p[] = '"' . $f['q'] . '"';
    if (isset($f['status'])) $p[] = STATUSES[$f['status']];
    if (isset($f['type'])) $p[] = TYPES[$f['type']];
    if (isset($f['min']) || isset($f['max'])) $p[] = (isset($f['min']) ? money($f['min']) : '') . ' to ' . (isset($f['max']) ? money($f['max']) : 'any');
    if (isset($f['beds'])) $p[] = $f['beds'] . '+ beds';
    if (isset($f['baths'])) $p[] = $f['baths'] . '+ baths';
    return $p ? implode(', ', $p) : 'All listings';
}
