<?php
/**
 * Builds a rolling calendar cache from already warmed search-cover files.
 *
 * This job never starts a live Tourvisor search. Run it after
 * warm_home_search_cache.sh. Promo files fill today/+1/+2 and regular cover
 * fills the following 39+ days.
 */
declare(strict_types=1);

require_once __DIR__ . '/../components/tourvisor_proxy_http_base.php';
require_once __DIR__ . '/../components/calendar_tour_cache.php';
require_once __DIR__ . '/../components/promo_sochi_filter.php';

$projectRoot = dirname(__DIR__, 2);
foreach ([$projectRoot . '/backend/.env', $projectRoot . '/.env'] as $envPath) {
    if (!is_file($envPath)) {
        continue;
    }
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        if ($key !== '') {
            putenv($key . '=' . trim($value));
            $_ENV[$key] = trim($value);
        }
    }
}

$countries = require __DIR__ . '/../config/popular_countries.php';
if (!is_array($countries) || $countries === []) {
    $countries = [['id' => 4, 'name' => 'Турция']];
}
$departures = th_promo_speed_warm_departures();
$proxyBase = get_tourvisor_proxy_http_base_url();
$daysAhead = (int) (getenv('TH_CALENDAR_WARM_DAYS') ?: ($_ENV['TH_CALENDAR_WARM_DAYS'] ?? 42));
$daysAhead = max(31, min(62, $daysAhead));
$today = new DateTimeImmutable('today');
$fromYmd = $today->format('Y-m-d');
$toYmd = $today->modify('+' . $daysAhead . ' days')->format('Y-m-d');
$coverFrom = $today->modify('+3 days');
$coverTo = new DateTimeImmutable($toYmd);

/** @return array<string,mixed> */
function th_calendar_warm_fetch_json(string $url): array
{
    $ch = curl_init($url);
    if ($ch === false) {
        return ['success' => false, 'data' => [], '_transportError' => 'curl_init'];
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $raw = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!is_string($raw) || $raw === '' || $status >= 500) {
        return [
            'success' => false,
            'data' => [],
            '_transportError' => $error !== '' ? $error : ('HTTP ' . $status),
        ];
    }
    $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

    return is_array($decoded) ? $decoded : ['success' => false, 'data' => []];
}

/** @return list<array{from:string,to:string}> */
function th_calendar_warm_windows(DateTimeImmutable $from, DateTimeImmutable $to): array
{
    $windows = [];
    $cursor = $from;
    while ($cursor <= $to) {
        $end = $cursor->modify('+13 days');
        if ($end > $to) {
            $end = $to;
        }
        $windows[] = ['from' => $cursor->format('Y-m-d'), 'to' => $end->format('Y-m-d')];
        $cursor = $end->modify('+1 day');
    }

    return $windows;
}

$summary = [];
foreach ($departures as $departure) {
    $departureId = (int) ($departure['departureId'] ?? 0);
    if ($departureId <= 0) {
        continue;
    }

    /** @var array<string,list<array<string,mixed>>> $dateBuckets */
    $dateBuckets = [];
    /** @var array<string,list<array<string,mixed>>> $previousDates */
    $previousDates = [];
    $previous = th_calendar_cache_get($departureId, true);
    $previousAge = isset($previous['generatedAt']) ? time() - (int) $previous['generatedAt'] : PHP_INT_MAX;
    if ($previousAge <= 48 * 3600) {
        foreach ((array) ($previous['dates'] ?? []) as $date => $hotels) {
            if (is_string($date) && is_array($hotels) && $date >= $fromYmd && $date <= $toYmd) {
                $previousDates[$date] = $hotels;
            }
        }
    }

    $coverHits = 0;
    $coverMisses = 0;
    $promoCountries = 0;
    $transportErrors = 0;
    foreach ($countries as $country) {
        $countryId = (int) ($country['id'] ?? 0);
        $countryName = trim((string) ($country['name'] ?? ''));
        if ($countryId <= 0) {
            continue;
        }

        $promo = th_promo_speed_cache_get($countryId, $departureId, true, $departureId);
        if ($promo === null) {
            $promo = th_promo_speed_cache_get_best($countryId, $departureId, true);
        }
        $promoHotels = is_array($promo['results'] ?? null) ? $promo['results'] : [];
        if ($promoHotels !== []) {
            $promoCountries++;
            $promoHotels = th_promo_filter_hotels_for_promo_country($promoHotels, $countryId);
            $promoHotels = th_promo_filter_hotels_min_nights($promoHotels, $countryId);
            th_calendar_cache_add_hotels(
                $dateBuckets,
                $promoHotels,
                $countryId,
                $countryName,
                $fromYmd,
                $toYmd
            );
        }

        foreach (th_calendar_warm_windows($coverFrom, $coverTo) as $window) {
            $params = [
                'type' => 'search-cached',
                'departureId' => (string) $departureId,
                'countryId' => (string) $countryId,
                'dateFrom' => $window['from'],
                'dateTo' => $window['to'],
                'nightsFrom' => '5',
                'nightsTo' => '10',
                'adults' => '2',
                'currency' => 'RUB',
                'cacheScope' => 'country_page',
                'cacheOnly' => '1',
                'slim' => '1',
            ];
            $url = $proxyBase . (strpos($proxyBase, '?') !== false ? '&' : '?') . http_build_query($params);
            $response = th_calendar_warm_fetch_json($url);
            if (!empty($response['_transportError'])) {
                $transportErrors++;
                $coverMisses++;
                if ($transportErrors >= 3) {
                    break 2;
                }
                continue;
            }
            $hotels = !empty($response['success']) && is_array($response['data'] ?? null)
                ? $response['data']
                : [];
            if ($hotels === []) {
                $coverMisses++;
                continue;
            }
            $coverHits++;
            th_calendar_cache_add_hotels(
                $dateBuckets,
                $hotels,
                $countryId,
                $countryName,
                $window['from'],
                $window['to']
            );
        }
    }

    $dates = th_calendar_cache_finalize_dates($dateBuckets, $fromYmd, $toYmd, 18);
    // Stale-while-revalidate only for dates that could not be rebuilt this run.
    foreach ($previousDates as $date => $hotels) {
        if (!isset($dates[$date])) {
            $dates[$date] = $hotels;
        }
    }
    ksort($dates);
    $totalDays = $daysAhead + 1;
    $payload = [
        'version' => 1,
        'generatedAt' => time(),
        'departureId' => $departureId,
        'horizonFrom' => $fromYmd,
        'horizonTo' => $toYmd,
        'daysAhead' => $daysAhead,
        'filledDays' => count($dates),
        'totalDays' => $totalDays,
        'dates' => $dates,
    ];
    // Never replace/refresh a usable cache after a transport outage.
    $buildHealthy = $transportErrors < 3 || $coverHits > 0;
    $saved = $dates !== [] && $buildHealthy && th_calendar_cache_set($departureId, $payload);
    $summary[] = [
        'departureId' => $departureId,
        'saved' => $saved,
        'filledDays' => count($dates),
        'totalDays' => $totalDays,
        'promoCountries' => $promoCountries,
        'coverHits' => $coverHits,
        'coverMisses' => $coverMisses,
        'transportErrors' => $transportErrors,
    ];
}

$result = [
    'success' => !in_array(false, array_column($summary, 'saved'), true),
    'horizonFrom' => $fromYmd,
    'horizonTo' => $toYmd,
    'departures' => $summary,
];
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
