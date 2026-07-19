<?php
/**
 * Прогрев кэша горящих туров: promo-search live (GET /tours/hots) → data/promo_cache_{countryId}_{departureId}.json
 *
 * Cron (2 раза в сутки):
 *   0 0,12 * * * cd /path/to/website-main && bash backend/cron/warm_promotions_cache.sh >> data/promo_warm.log 2>&1
 *
 * Ручной прогрев по SSH (на хостинге нужен php7.4, не системный php 5.2):
 *   cd /path/to/website-main && bash backend/cron/warm_promotions_cache.sh
 *   # или: PHP_BIN=/usr/bin/php7.4 php backend/cron/update_promotions_cache.php
 *
 * Требует SITE_URL / TOURVISOR_PROXY_URL (см. tourvisor_proxy_http_base.php).
 */
declare(strict_types=1);

require_once __DIR__ . '/../components/tourvisor_proxy_http_base.php';
require_once __DIR__ . '/../components/promo_speed_cache.php';

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
    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($envLines)) {
        $envLines = array();
    }
    foreach ($envLines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        $parts = explode('=', $line, 2);
        $k = isset($parts[0]) ? trim($parts[0]) : '';
        $v = isset($parts[1]) ? trim($parts[1]) : '';
        if ($k !== '') {
            putenv($k . '=' . $v);
            $_ENV[$k] = $v;
        }
    }
}

$popularFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'popular_countries.php';
$popular = is_file($popularFile) ? require $popularFile : [];
if (!is_array($popular) || $popular === []) {
    $popular = [['id' => 4, 'name' => 'Турция']];
}

$proxyBase = get_tourvisor_proxy_http_base_url();
$departures = th_promo_speed_warm_departures();

$index = [];
$ok = 0;
$err = 0;
$seenCountry = [];

th_promo_speed_log('cron_start', [
    'countries' => count($popular),
    'departures' => array_column($departures, 'departureId'),
    'mode' => 'promo-search-tour-hots',
]);

foreach ($departures as $depRow) {
    $departureId = (int) (isset($depRow['departureId']) ? $depRow['departureId'] : 0);
    if ($departureId <= 0) {
        continue;
    }
    $depKey = (string) $departureId;
    if (!isset($index[$depKey])) {
        $index[$depKey] = [];
    }

    foreach ($popular as $row) {
        $countryId = (int) (isset($row['id']) ? $row['id'] : 0);
        if ($countryId <= 0) {
            continue;
        }
        $comboKey = $departureId . '_' . $countryId;
        if (isset($seenCountry[$comboKey])) {
            continue;
        }
        $seenCountry[$comboKey] = true;

        $dates = th_promo_speed_promo_dates($countryId);

        $cronDispatch = static function (array $winParams) use ($proxyBase): array {
            $params = http_build_query($winParams);
            $url = $proxyBase . (strpos($proxyBase, '?') !== false ? '&' : '?') . $params;
            $ctx = stream_context_create([
                'http' => ['timeout' => 300, 'ignore_errors' => true],
                'ssl' => ['verify_peer' => true],
            ]);
            $raw = @file_get_contents($url, false, $ctx);
            if ($raw === false) {
                return ['success' => false, 'data' => []];
            }
            $data = json_decode($raw, true);
            return is_array($data) ? $data : ['success' => false, 'data' => []];
        };

        $res = $cronDispatch([
            'type' => 'promo-search',
            'departureId' => (string) $departureId,
            'countryId' => (string) $countryId,
            'dateFrom' => $dates['dateFrom'],
            'dateTo' => $dates['dateTo'],
            'adults' => '2',
            'live' => '1',
        ]);
        $hotels = (!empty($res['success']) && is_array(isset($res['data']) ? $res['data'] : null)) ? $res['data'] : [];
        if (count($hotels) === 0) {
            th_promo_speed_log('cron_promo_search_empty', [
                'countryId' => $countryId,
                'departureId' => $departureId,
                'error' => isset($res['error']) ? $res['error'] : 'empty',
                'source' => isset($res['promoSearchSource']) ? $res['promoSearchSource'] : null,
            ]);
        } else {
            th_promo_speed_log('cron_promo_search_ok', [
                'countryId' => $countryId,
                'departureId' => $departureId,
                'hotels' => count($hotels),
                'source' => isset($res['promoSearchSource']) ? $res['promoSearchSource'] : null,
            ]);
        }

        $hotelsForTile = th_promo_filter_hotels_min_nights($hotels, $countryId);
        $min = 0;
        foreach ($hotelsForTile as $h) {
            if (!is_array($h)) {
                continue;
            }
            $p = th_promo_speed_hotel_min_price($h);
            if ($p > 0 && ($min === 0 || $p < $min)) {
                $min = $p;
            }
        }
        $entry = ['has' => count($hotelsForTile) > 0];
        if ($min > 0) {
            $entry['minPrice'] = $min;
        }
        $index[$depKey][(string) $countryId] = $entry;

        if (count($hotels) > 0) {
            $ok++;
            if ($isCli) {
                echo (isset($row['name']) ? $row['name'] : $countryId) . " (dep {$departureId}): " . count($hotels) . " отелей\n";
            }
        } else {
            $err++;
            if ($isCli) {
                echo (isset($row['name']) ? $row['name'] : $countryId) . " (dep {$departureId}): пусто\n";
            }
        }
        th_promo_speed_index_set($index);
        sleep(1);
    }
}

th_promo_speed_index_set($index);
th_promo_speed_log('cron_done', ['ok' => $ok, 'err' => $err]);

if (!$isCli) {
    echo json_encode(['success' => true, 'ok' => $ok, 'err' => $err], JSON_UNESCAPED_UNICODE);
}
