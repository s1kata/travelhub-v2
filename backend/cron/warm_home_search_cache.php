<?php
/**
 * Прогрев search-cached для главной: топ-направления из Самары (departureId=7).
 * Запись в файловый кэш Tourvisor — первый поиск на сайте отдаётся за секунды.
 *
 * Cron (2× в сутки, после promo warm):
 *   30 0,12 * * * cd /path/to/site && bash backend/cron/warm_home_search_cache.sh >> data/home_search_warm.log 2>&1
 */
declare(strict_types=1);

require_once __DIR__ . '/../components/tourvisor_proxy_http_base.php';
require_once __DIR__ . '/../config/departure_defaults.php';

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
}

$projectRoot = dirname(dirname(__DIR__));
$envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if (!is_file($envPath)) {
    $envPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';
}
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $k = trim($k);
        if ($k !== '') {
            putenv($k . '=' . trim($v));
            $_ENV[$k] = trim($v);
        }
    }
}

$popularFile = dirname(__DIR__) . '/config/popular_countries.php';
$popular = is_file($popularFile) ? require $popularFile : [];
if (!is_array($popular) || $popular === []) {
    $popular = [['id' => 4, 'name' => 'Турция']];
}

$departureId = th_departure_default_id();
$dateFrom = date('Y-m-d', strtotime('+7 days'));
$dateTo = date('Y-m-d', strtotime('+21 days'));
$proxyBase = rtrim(get_tourvisor_proxy_http_base_url(), '/');

$ok = 0;
$err = 0;
$results = [];

foreach (array_slice($popular, 0, 6) as $row) {
    $countryId = (int) ($row['id'] ?? 0);
    if ($countryId <= 0) {
        continue;
    }
    $qs = http_build_query([
        'type' => 'search-cached',
        'departureId' => $departureId,
        'countryId' => $countryId,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
        'nightsFrom' => 7,
        'nightsTo' => 14,
        'adults' => 2,
        'currency' => 'RUB',
        'live' => 1,
    ]);
    $url = $proxyBase . (str_contains($proxyBase, '?') ? '&' : '?') . $qs;

    $ch = curl_init($url);
    if ($ch === false) {
        $err++;
        $results[] = ['countryId' => $countryId, 'ok' => false, 'error' => 'curl_init'];
        continue;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $j = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
    $count = (is_array($j) && isset($j['data']) && is_array($j['data'])) ? count($j['data']) : 0;
    $success = is_array($j) && !empty($j['success']) && $count > 0;

    if ($success) {
        $ok++;
    } else {
        $err++;
    }
    $results[] = [
        'countryId' => $countryId,
        'name' => (string) ($row['name'] ?? ''),
        'ok' => $success,
        'hotels' => $count,
        'http' => $code,
        'error' => is_array($j) ? ($j['error'] ?? null) : 'bad_json',
    ];
    usleep(500000);
}

$out = [
    'success' => true,
    'departureId' => $departureId,
    'dateFrom' => $dateFrom,
    'dateTo' => $dateTo,
    'warmed' => $ok,
    'errors' => $err,
    'results' => $results,
];

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
exit($err > 0 && $ok === 0 ? 1 : 0);
