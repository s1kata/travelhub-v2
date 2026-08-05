<?php
/**
 * Прогрев search-cached для главной (PHP-only / SpaceWeb):
 * топ-направления × Самара/Москва × несколько окон дат.
 *
 * Cron (3–4× в сутки):
 *   30 0,8,14,20 * * * cd /path/to/site && bash backend/cron/warm_home_search_cache.sh >> data/home_search_warm.log 2>&1
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

/** Города вылета для прогрева (Самара + Москва). */
$departureIds = [7, 1];

/** Несколько окон дат — покрывает типичный выбор на главной. */
$dateWindows = [
    [
        'from' => date('Y-m-d', strtotime('+7 days')),
        'to' => date('Y-m-d', strtotime('+21 days')),
    ],
    [
        'from' => date('Y-m-d', strtotime('+14 days')),
        'to' => date('Y-m-d', strtotime('+28 days')),
    ],
    [
        'from' => date('Y-m-d', strtotime('+21 days')),
        'to' => date('Y-m-d', strtotime('+35 days')),
    ],
];

/** Базовый диапазон ночей + alt для топ-5 (как на фронте). */
$nightRanges = [
    ['from' => 6, 'to' => 9],
];
$altNightCountries = 5; // первые N стран ещё и 5–10 ночей

$proxyBase = rtrim(get_tourvisor_proxy_http_base_url(), '/');

$ok = 0;
$err = 0;
$results = [];
$started = microtime(true);

$jobs = [];
foreach ($departureIds as $departureId) {
    $ci = 0;
    foreach ($popular as $row) {
        $countryId = (int) ($row['id'] ?? 0);
        if ($countryId <= 0) {
            continue;
        }
        $ranges = $nightRanges;
        if ($ci < $altNightCountries) {
            $ranges[] = ['from' => 5, 'to' => 10];
        }
        $ci++;
        foreach ($dateWindows as $win) {
            foreach ($ranges as $nr) {
                $jobs[] = [
                    'departureId' => $departureId,
                    'countryId' => $countryId,
                    'name' => (string) ($row['name'] ?? ''),
                    'dateFrom' => $win['from'],
                    'dateTo' => $win['to'],
                    'nightsFrom' => $nr['from'],
                    'nightsTo' => $nr['to'],
                ];
            }
        }
    }
}

foreach ($jobs as $job) {
    $qs = http_build_query([
        'type' => 'search-cached',
        'departureId' => $job['departureId'],
        'countryId' => $job['countryId'],
        'dateFrom' => $job['dateFrom'],
        'dateTo' => $job['dateTo'],
        'nightsFrom' => $job['nightsFrom'],
        'nightsTo' => $job['nightsTo'],
        'adults' => 2,
        'currency' => 'RUB',
        'cacheScope' => 'country_page',
        'live' => 1,
        'slim' => 1,
    ]);
    $url = $proxyBase . (str_contains($proxyBase, '?') ? '&' : '?') . $qs;

    $t0 = microtime(true);
    $ch = curl_init($url);
    if ($ch === false) {
        $err++;
        $results[] = array_merge($job, ['ok' => false, 'error' => 'curl_init', 'ms' => 0]);
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
    $ms = (int) round((microtime(true) - $t0) * 1000);

    $j = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
    $count = (is_array($j) && isset($j['data']) && is_array($j['data'])) ? count($j['data']) : 0;
    $success = is_array($j) && !empty($j['success']) && $count > 0;

    if ($success) {
        $ok++;
    } else {
        $err++;
    }
    $results[] = array_merge($job, [
        'ok' => $success,
        'hotels' => $count,
        'http' => $code,
        'ms' => $ms,
        'error' => is_array($j) ? ($j['error'] ?? null) : 'bad_json',
    ]);
    usleep(400000);
}

$out = [
    'success' => true,
    'departureIds' => $departureIds,
    'dateWindows' => $dateWindows,
    'jobs' => count($jobs),
    'warmed' => $ok,
    'errors' => $err,
    'elapsedSec' => round(microtime(true) - $started, 1),
    'results' => $results,
];

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
exit($err > 0 && $ok === 0 ? 1 : 0);
