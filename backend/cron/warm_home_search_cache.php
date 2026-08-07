<?php
/**
 * Прогрев search-cached для главной:
 * — широкий nights 5–10;
 * — горизонт today+3…+42;
 * — skip если cover свежий и закрывает горизонт;
 * — иначе live только по дырам (куски ≤14 дней);
 * — туристические комбо: 2+0 всегда, 2+1 (возраст 7) для топ-5 стран.
 *
 * Cron (ежедневно + доп. прогоны днём — витрина главной питается из promo_cache):
 *   30 0,8,14,20 * * * cd /path/to/site && bash backend/cron/warm_home_search_cache.sh >> data/home_search_warm.log 2>&1
 * Рекомендуется минимум 1 полный прогон ночью (00:30), чтобы утром на главной были свежие туры.
 */
declare(strict_types=1);

require_once __DIR__ . '/../components/tourvisor_proxy_http_base.php';
require_once __DIR__ . '/../config/departure_defaults.php';
require_once __DIR__ . '/../components/tourvisor_search_cover.php';

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

$departureIds = [7, 1];
$wideNights = th_search_cover_wide_nights();
$targetFrom = date('Y-m-d', strtotime('+3 days'));
$targetTo = date('Y-m-d', strtotime('+42 days'));
$warmCoverEnabled = filter_var(
    getenv('TH_WARM_COVER_ENABLED') ?: ($_ENV['TH_WARM_COVER_ENABLED'] ?? '1'),
    FILTER_VALIDATE_BOOLEAN
);

/** TTL как у country_page (24ч по умолчанию) — для skip. */
$ttlHours = (float) (getenv('TOURVISOR_COUNTRY_PAGE_CACHE_TTL_HOURS')
    ?: ($_ENV['TOURVISOR_COUNTRY_PAGE_CACHE_TTL_HOURS'] ?? 24));
$ttlSeconds = (int) (min(168, max(1, $ttlHours)) * 3600);

/** 2+0 всегда; 2+1 для первых N стран. */
$childComboTopCountries = 5;
$touristCombosBase = [
    ['adults' => 2, 'childs' => ''],
];
$touristCombosTop = [
    ['adults' => 2, 'childs' => ''],
    ['adults' => 2, 'childs' => '7'],
];

$proxyBase = rtrim(get_tourvisor_proxy_http_base_url(), '/');

/** Пауза между live-сегментами (мс) — не упираться в ~30 req/min Tourvisor. */
$warmPauseUs = (int) (max(1.5, min(8.0, (float) (getenv('TH_WARM_LIVE_PAUSE_SEC') ?: ($_ENV['TH_WARM_LIVE_PAUSE_SEC'] ?? 2.5)))) * 1000000);
/** Жёсткий потолок live-сегментов за один прогон (защита суточной квоты). */
$maxLiveChunks = (int) (getenv('TH_WARM_MAX_LIVE_CHUNKS') ?: ($_ENV['TH_WARM_MAX_LIVE_CHUNKS'] ?? 40));
$maxLiveChunks = max(5, min(120, $maxLiveChunks));
$liveChunksDone = 0;

$ok = 0;
$err = 0;
$skipped = 0;
$extended = 0;
$results = [];
$started = microtime(true);

/**
 * @param array<string,mixed> $job
 * @return array<string,mixed>
 */
$runLiveChunk = static function (array $job) use ($proxyBase): array {
    $q = [
        'type' => 'search-cached',
        'departureId' => $job['departureId'],
        'countryId' => $job['countryId'],
        'dateFrom' => $job['dateFrom'],
        'dateTo' => $job['dateTo'],
        'nightsFrom' => $job['nightsFrom'],
        'nightsTo' => $job['nightsTo'],
        'adults' => $job['adults'],
        'currency' => 'RUB',
        'cacheScope' => 'country_page',
        'live' => 1,
        'slim' => 1,
    ];
    if ($job['childs'] !== '') {
        $q['childs'] = $job['childs'];
    }
    $qs = http_build_query($q);

    $url = $proxyBase . (str_contains($proxyBase, '?') ? '&' : '?') . $qs;
    $t0 = microtime(true);
    $ch = curl_init($url);
    if ($ch === false) {
        return array_merge($job, [
            'ok' => false,
            'action' => 'live',
            'error' => 'curl_init',
            'ms' => 0,
            'hotels' => 0,
            'http' => 0,
        ]);
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

    return array_merge($job, [
        'ok' => $success,
        'action' => 'live',
        'hotels' => $count,
        'http' => $code,
        'ms' => $ms,
        'error' => $success ? null : (is_array($j) ? ($j['error'] ?? 'bad_json') : 'bad_json'),
    ]);
};

if (!$warmCoverEnabled) {
    $ok = 0;
    $err = 0;
    $results = [];
    $legacyDateWindows = [
        ['from' => date('Y-m-d', strtotime('+3 days')), 'to' => date('Y-m-d', strtotime('+17 days'))],
        ['from' => date('Y-m-d', strtotime('+7 days')), 'to' => date('Y-m-d', strtotime('+21 days'))],
        ['from' => date('Y-m-d', strtotime('+14 days')), 'to' => date('Y-m-d', strtotime('+28 days'))],
        ['from' => date('Y-m-d', strtotime('+21 days')), 'to' => date('Y-m-d', strtotime('+35 days'))],
        ['from' => date('Y-m-d', strtotime('+28 days')), 'to' => date('Y-m-d', strtotime('+42 days'))],
    ];
    foreach ($departureIds as $departureId) {
        $ci = 0;
        foreach ($popular as $row) {
            $countryId = (int) ($row['id'] ?? 0);
            if ($countryId <= 0) {
                continue;
            }
            $ranges = [['from' => 6, 'to' => 9]];
            if ($ci < 5) {
                $ranges[] = ['from' => 5, 'to' => 10];
            }
            $ci++;
            foreach ($legacyDateWindows as $win) {
                foreach ($ranges as $nr) {
                    $rowOut = $runLiveChunk([
                        'departureId' => $departureId,
                        'countryId' => $countryId,
                        'name' => (string) ($row['name'] ?? ''),
                        'dateFrom' => $win['from'],
                        'dateTo' => $win['to'],
                        'nightsFrom' => $nr['from'],
                        'nightsTo' => $nr['to'],
                        'adults' => 2,
                        'childs' => '',
                        'mode' => 'legacy',
                    ]);
                    if (!empty($rowOut['ok'])) {
                        $ok++;
                    } else {
                        $err++;
                    }
                    $results[] = $rowOut;
                    usleep($warmPauseUs);
                }
            }
        }
    }
    $out = [
        'success' => true,
        'mode' => 'legacy-full-live',
        'coverEnabled' => false,
        'warmed' => $ok,
        'errors' => $err,
        'elapsedSec' => round(microtime(true) - $started, 1),
        'results' => $results,
    ];
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    exit($err > 0 && $ok === 0 ? 1 : 0);
}

$identities = [];
$ci = 0;
foreach ($popular as $row) {
    $countryId = (int) ($row['id'] ?? 0);
    if ($countryId <= 0) {
        continue;
    }
    $name = (string) ($row['name'] ?? '');
    $combos = $ci < $childComboTopCountries ? $touristCombosTop : $touristCombosBase;
    $ci++;

    foreach ($departureIds as $departureId) {
        foreach ($combos as $combo) {
            $params = [
                'departureId' => $departureId,
                'countryId' => $countryId,
                'adults' => $combo['adults'],
                'childs' => $combo['childs'],
                'nightsFrom' => $wideNights['from'],
                'nightsTo' => $wideNights['to'],
                'currency' => 'RUB',
            ];
            $identity = th_search_cover_identity($params);
            $identities[] = [
                'identity' => $identity,
                'params' => $params,
                'name' => $name,
            ];
        }
    }
}

foreach ($identities as $item) {
    $identity = $item['identity'];
    $params = $item['params'];
    $entry = th_search_cover_get_entry($identity);
    $fresh = th_search_cover_is_fresh($entry, $ttlSeconds);
    $coverFrom = is_array($entry) ? (string) ($entry['from'] ?? '') : '';
    $coverTo = is_array($entry) ? (string) ($entry['to'] ?? '') : '';

    $coversTarget = $fresh
        && $coverFrom !== ''
        && $coverTo !== ''
        && th_search_cover_dates_supset($coverFrom, $coverTo, $targetFrom, $targetTo);

    if ($coversTarget) {
        $skipped++;
        $results[] = [
            'action' => 'skipped',
            'ok' => true,
            'identity' => $identity,
            'name' => $item['name'],
            'departureId' => $params['departureId'],
            'countryId' => $params['countryId'],
            'adults' => $params['adults'],
            'childs' => $params['childs'],
            'coverFrom' => $coverFrom,
            'coverTo' => $coverTo,
            'ms' => 0,
        ];
        continue;
    }

    // Свежий, но дыры — только extend; протухший — полный горизонт
    $gapCoverFrom = ($fresh && $coverFrom !== '') ? $coverFrom : null;
    $gapCoverTo = ($fresh && $coverTo !== '') ? $coverTo : null;
    $gaps = th_search_cover_date_gaps($targetFrom, $targetTo, $gapCoverFrom, $gapCoverTo);
    if ($gaps === []) {
        $skipped++;
        $results[] = [
            'action' => 'skipped',
            'ok' => true,
            'identity' => $identity,
            'name' => $item['name'],
            'reason' => 'no_gaps',
            'ms' => 0,
        ];
        continue;
    }

    $didExtend = $gapCoverFrom !== null;
    $chunks = [];
    foreach ($gaps as $gap) {
        foreach (th_search_cover_split_chunks($gap['from'], $gap['to'], 14) as $chunk) {
            $chunks[] = $chunk;
        }
    }

    foreach ($chunks as $chunk) {
        if ($liveChunksDone >= $maxLiveChunks) {
            $results[] = [
                'action' => 'budget_stop',
                'ok' => true,
                'identity' => $identity,
                'name' => $item['name'],
                'reason' => 'max_live_chunks',
                'maxLiveChunks' => $maxLiveChunks,
                'ms' => 0,
            ];
            break 2;
        }
        $job = [
            'departureId' => $params['departureId'],
            'countryId' => $params['countryId'],
            'name' => $item['name'],
            'dateFrom' => $chunk['from'],
            'dateTo' => $chunk['to'],
            'nightsFrom' => $params['nightsFrom'],
            'nightsTo' => $params['nightsTo'],
            'adults' => $params['adults'],
            'childs' => $params['childs'],
            'identity' => $identity,
            'mode' => $didExtend ? 'extended' : 'full',
        ];
        $rowOut = $runLiveChunk($job);
        $liveChunksDone++;
        if (!empty($rowOut['ok'])) {
            $ok++;
            if ($didExtend) {
                $extended++;
            }
        } else {
            $err++;
            // При 429 / ошибках — удлиняем паузу, не долбим TV
            usleep((int) ($warmPauseUs * 1.5));
        }
        $results[] = $rowOut;
        usleep($warmPauseUs);
    }
}

$out = [
    'success' => true,
    'mode' => 'cover-skip-extend',
    'departureIds' => $departureIds,
    'targetFrom' => $targetFrom,
    'targetTo' => $targetTo,
    'nights' => $wideNights,
    'ttlSeconds' => $ttlSeconds,
    'identities' => count($identities),
    'skipped' => $skipped,
    'extendedChunks' => $extended,
    'liveChunksDone' => $liveChunksDone,
    'maxLiveChunks' => $maxLiveChunks,
    'warmed' => $ok,
    'errors' => $err,
    'elapsedSec' => round(microtime(true) - $started, 1),
    'results' => $results,
];

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
exit($err > 0 && $ok === 0 && $skipped === 0 ? 1 : 0);
