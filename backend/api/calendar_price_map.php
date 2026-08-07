<?php
/**
 * Лёгкая карта цен по датам для календаря поиска (без полного search).
 * Источник: promo_cache_* файлы (прогрев акций).
 *
 * GET: departureId, countryId
 * Ответ: { success, dates: { "Y-m-d": { minPrice, deal } } }
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/components/promo_speed_cache.php';
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
if ($departureId <= 0) {
    $departureId = 7;
}
if ($countryId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'countryId required'], JSON_UNESCAPED_UNICODE);
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

$today = new DateTimeImmutable('today');
$horizon = $today->modify('+60 days');
$byDate = [];

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
        $raw = '';
        foreach (['flydate', 'datefrom', 'dateFrom', 'checkIn', 'checkin', 'startDate'] as $k) {
            $v = trim((string) ($tour[$k] ?? ''));
            if ($v !== '') {
                $raw = $v;
                break;
            }
        }
        if ($raw === '') {
            continue;
        }
        $ymd = '';
        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $raw, $m)) {
            $ymd = $m[3] . '-' . $m[2] . '-' . $m[1];
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}/', $raw)) {
            $ymd = substr($raw, 0, 10);
        }
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
        if (!isset($byDate[$key]) || $price < (int) $byDate[$key]) {
            $byDate[$key] = $price;
        }
    }
}

if ($byDate === []) {
    // Fallback: равномерно размазать minPrice индекса на ближайшие выходные (мягкий сигнал)
    $index = th_promo_speed_index_for_frontend();
    $entry = is_array($index[(string) $departureId][(string) $countryId] ?? null)
        ? $index[(string) $departureId][(string) $countryId]
        : [];
    $min = (int) ($entry['minPrice'] ?? 0);
    if ($min > 0) {
        for ($i = 3; $i <= 45; $i += 3) {
            $d = $today->modify('+' . $i . ' days');
            $byDate[$d->format('Y-m-d')] = $min;
        }
    }
}

ksort($byDate);
$prices = array_values($byDate);
sort($prices);
$dealThreshold = 0;
if (count($prices) >= 4) {
    $dealThreshold = (int) $prices[(int) floor(count($prices) * 0.35)];
}

$dates = [];
foreach ($byDate as $ymd => $price) {
    $dates[$ymd] = [
        'minPrice' => (int) $price,
        'deal' => $dealThreshold > 0 && (int) $price <= $dealThreshold,
    ];
}

echo json_encode([
    'success' => true,
    'departureId' => $departureId,
    'countryId' => $countryId,
    'updatedAt' => time(),
    'dates' => $dates,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
