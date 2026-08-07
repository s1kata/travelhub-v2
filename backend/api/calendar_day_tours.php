<?php
/**
 * Туры на выбранную дату для календаря выгодных дат.
 * Источник: promo_cache → filter по дате (без live, без 429).
 *
 * GET: departureId, countryId, date=Y-m-d, nightsFrom?, nightsTo?, limit?
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/components/promo_speed_cache.php';
require_once dirname(__DIR__) . '/components/security_helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120');
header('Access-Control-Allow-Origin: *');
security_apply_default_headers();

$guard = security_guard_public_api('calendar_day_tours', '', 90, 4096);
if (empty($guard['ok'])) {
    http_response_code((int) ($guard['code'] ?? 400));
    echo json_encode(['success' => false, 'error' => (string) ($guard['error'] ?? 'Bad request')], JSON_UNESCAPED_UNICODE);
    exit;
}

$departureId = isset($_GET['departureId']) ? (int) $_GET['departureId'] : 7;
$countryId = isset($_GET['countryId']) ? (int) $_GET['countryId'] : 0;
$date = trim((string) ($_GET['date'] ?? ''));
$nightsFrom = isset($_GET['nightsFrom']) ? (int) $_GET['nightsFrom'] : 0;
$nightsTo = isset($_GET['nightsTo']) ? (int) $_GET['nightsTo'] : 0;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 12;
$limit = max(1, min(24, $limit));

if ($departureId <= 0) {
    $departureId = 7;
}
if ($countryId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'countryId and date=Y-m-d required'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $day = new DateTimeImmutable($date);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid date'], JSON_UNESCAPED_UNICODE);
    exit;
}
$today = new DateTimeImmutable('today');
if ($day < $today) {
    echo json_encode(['success' => true, 'data' => [], 'fromCache' => true, 'source' => 'none'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = th_promo_speed_cache_get($countryId, $departureId, true, $departureId);
if ($payload === null) {
    $payload = th_promo_speed_cache_get_best($countryId, $departureId, true);
}
$hotels = is_array($payload['results'] ?? null) ? $payload['results'] : [];
if ($hotels !== []) {
    $hotels = th_promo_filter_hotels_min_nights($hotels, $countryId);
}

$parseTourDate = static function (array $tour): string {
    foreach (['flydate', 'datefrom', 'dateFrom', 'checkIn', 'checkin', 'startDate', 'date'] as $k) {
        $v = trim((string) ($tour[$k] ?? ''));
        if ($v === '') {
            continue;
        }
        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $v, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $v)) {
            return substr($v, 0, 10);
        }
    }
    return '';
};

$out = [];
foreach ($hotels as $hotel) {
    if (!is_array($hotel)) {
        continue;
    }
    $tours = is_array($hotel['tours'] ?? null) ? $hotel['tours'] : [];
    $matching = [];
    $bestPrice = 0;
    foreach ($tours as $tour) {
        if (!is_array($tour)) {
            continue;
        }
        $ymd = $parseTourDate($tour);
        if ($ymd !== $date) {
            continue;
        }
        $n = (int) ($tour['nights'] ?? 0);
        if ($nightsFrom > 0 && $nightsTo > 0 && $n > 0 && ($n < $nightsFrom || $n > $nightsTo)) {
            continue;
        }
        $price = (int) ($tour['totalPrice'] ?? $tour['price'] ?? $tour['priceRub'] ?? 0);
        if ($price <= 0) {
            continue;
        }
        $matching[] = $tour;
        if ($bestPrice === 0 || $price < $bestPrice) {
            $bestPrice = $price;
        }
    }
    if ($matching === []) {
        continue;
    }
    usort($matching, static function ($a, $b) {
        $pa = (int) ($a['totalPrice'] ?? $a['price'] ?? 0);
        $pb = (int) ($b['totalPrice'] ?? $b['price'] ?? 0);
        return $pa <=> $pb;
    });
    $hotelOut = $hotel;
    $hotelOut['tours'] = array_slice($matching, 0, 3);
    $hotelOut['_dayMinPrice'] = $bestPrice;
    $out[] = $hotelOut;
}

usort($out, static function ($a, $b) {
    return ((int) ($a['_dayMinPrice'] ?? 0)) <=> ((int) ($b['_dayMinPrice'] ?? 0));
});
$out = array_slice($out, 0, $limit);

echo json_encode([
    'success' => true,
    'departureId' => $departureId,
    'countryId' => $countryId,
    'date' => $date,
    'fromCache' => true,
    'source' => $out !== [] ? 'promo_cache' : 'empty',
    'data' => $out,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
