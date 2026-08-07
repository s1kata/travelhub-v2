<?php
/**
 * Карта цен по датам для календаря выгодных дат (без live Tourvisor).
 * Без countryId / countryId=0 — свод по всем популярным направлениям.
 *
 * GET: departureId, countryId? (0 = все), nightsFrom?, nightsTo?
 * Ответ: { success, dates: { "Y-m-d": { minPrice, deal, reduced, countryId?, countryName? } }, ladder }
 *
 * Метки: deal = выгодная цена, reduced = пониженная цена.
 * Горизонт — «лестница» месяцев (см. th_deals_calendar_ladder).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/components/promo_speed_cache.php';
require_once dirname(__DIR__) . '/components/promo_sochi_filter.php';
require_once dirname(__DIR__) . '/components/deals_calendar.php';
require_once dirname(__DIR__) . '/components/security_helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('Access-Control-Allow-Origin: *');
security_apply_default_headers();

$guard = security_guard_public_api('calendar_price_map', '', 120, 4096);
if (empty($guard['ok'])) {
    http_response_code((int) ($guard['code'] ?? 400));
    echo json_encode(['success' => false, 'error' => (string) ($guard['error'] ?? 'Bad request')], JSON_UNESCAPED_UNICODE);
    exit;
}

$departureId = isset($_GET['departureId']) ? (int) $_GET['departureId'] : 0;
$countryId = isset($_GET['countryId']) ? (int) $_GET['countryId'] : 0;
$nightsFrom = isset($_GET['nightsFrom']) ? (int) $_GET['nightsFrom'] : 0;
$nightsTo = isset($_GET['nightsTo']) ? (int) $_GET['nightsTo'] : 0;
if ($departureId <= 0) {
    $departureId = 7;
}

$countries = [];
$popularFile = dirname(__DIR__) . '/config/popular_countries.php';
if (is_file($popularFile)) {
    $loaded = require $popularFile;
    if (is_array($loaded)) {
        $countries = $loaded;
    }
}
if ($countries === []) {
    $countries = [['id' => 4, 'name' => 'Турция']];
}

/** @var list<array{id:int,name:string}> $scan */
$scan = [];
if ($countryId > 0) {
    $name = '';
    foreach ($countries as $c) {
        if ((int) ($c['id'] ?? 0) === $countryId) {
            $name = (string) ($c['name'] ?? '');
            break;
        }
    }
    $scan[] = ['id' => $countryId, 'name' => $name !== '' ? $name : ('Страна ' . $countryId)];
} else {
    foreach ($countries as $c) {
        $cid = (int) ($c['id'] ?? 0);
        $cname = trim((string) ($c['name'] ?? ''));
        if ($cid <= 0) {
            continue;
        }
        $scan[] = ['id' => $cid, 'name' => $cname !== '' ? $cname : ('Страна ' . $cid)];
    }
}

$ladder = th_deals_calendar_ladder();
$today = $ladder['today'];
$horizon = $ladder['horizon'];
/** @var array<string, array{minPrice:int, countryId:int, countryName:string}> $byDate */
$byDate = [];
$fromCache = false;

foreach ($scan as $row) {
    $cid = (int) $row['id'];
    $cname = (string) $row['name'];
    $payload = th_promo_speed_cache_get($cid, $departureId, true, $departureId);
    if ($payload === null) {
        $payload = th_promo_speed_cache_get_best($cid, $departureId, true);
    }
    $hotels = is_array($payload['results'] ?? null) ? $payload['results'] : [];
    if ($hotels === []) {
        continue;
    }
    $fromCache = true;
    $hotels = th_promo_filter_hotels_for_promo_country($hotels, $cid);
    $hotels = th_promo_filter_hotels_min_nights($hotels, $cid);

    foreach ($hotels as $hotel) {
        if (!is_array($hotel)) {
            continue;
        }
        $tours = is_array($hotel['tours'] ?? null) ? $hotel['tours'] : [];
        foreach ($tours as $tour) {
            if (!is_array($tour)) {
                continue;
            }
            $price = (int) ($tour['totalPrice'] ?? $tour['price'] ?? $tour['priceRub'] ?? 0);
            if ($price <= 0) {
                continue;
            }
            $n = (int) ($tour['nights'] ?? 0);
            if ($nightsFrom > 0 && $nightsTo > 0 && $n > 0 && ($n < $nightsFrom || $n > $nightsTo)) {
                continue;
            }
            $ymd = th_promo_tour_start_ymd($tour);
            if ($ymd === '') {
                continue;
            }
            try {
                $d = new DateTimeImmutable($ymd);
            } catch (Throwable $e) {
                continue;
            }
            if ($d < $today || $d > $horizon) {
                continue;
            }
            $key = $d->format('Y-m-d');
            if (!isset($byDate[$key]) || $price < (int) $byDate[$key]['minPrice']) {
                $byDate[$key] = [
                    'minPrice' => $price,
                    'countryId' => $cid,
                    'countryName' => $cname,
                ];
            }
        }
    }
}

ksort($byDate);
$dates = th_deals_calendar_mark_tiers($byDate);

$emptyReason = '';
if ($dates === []) {
    $emptyReason = $fromCache ? 'no_dates_in_range' : 'no_promo_cache';
}

echo json_encode([
    'success' => true,
    'departureId' => $departureId,
    'countryId' => $countryId,
    'mode' => $countryId > 0 ? 'country' : 'all',
    'nightsFrom' => $nightsFrom,
    'nightsTo' => $nightsTo,
    'updatedAt' => time(),
    'fromCache' => $fromCache,
    'emptyReason' => $emptyReason,
    'ladder' => [
        'monthsAhead' => $ladder['monthsAhead'],
        'horizon' => $ladder['horizonYmd'],
        'viewMaxYm' => $ladder['viewMaxYm'],
    ],
    'dates' => $dates,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
