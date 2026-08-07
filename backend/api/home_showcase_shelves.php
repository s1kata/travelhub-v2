<?php
/**
<<<<<<< HEAD
 * Главная: готовые горящие туры (отели из promo_cache / live), не страны.
=======
 * Главная: готовые горящие туры (отели из promo_cache), не страны.
 * Mood-фильтры режут уже готовые карточки туров.
>>>>>>> origin/master
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
<<<<<<< HEAD
require_once dirname(__DIR__) . '/components/home_showcase_build.php';
require_once dirname(__DIR__) . '/components/security_helper.php';
=======
require_once dirname(__DIR__) . '/components/promo_speed_cache.php';
>>>>>>> origin/master

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
<<<<<<< HEAD
security_apply_default_headers();

$guard = security_guard_public_api('home_showcase_shelves', '', 60, 4096);
if (empty($guard['ok'])) {
    http_response_code((int) ($guard['code'] ?? 400));
    echo json_encode(['success' => false, 'error' => (string) ($guard['error'] ?? 'Bad request')], JSON_UNESCAPED_UNICODE);
    exit;
}

$departureId = isset($_GET['departureId']) ? (int) $_GET['departureId'] : 0;
$allowLive = !isset($_GET['cacheOnly']) || $_GET['cacheOnly'] !== '1';

$payload = th_home_showcase_build($departureId, $allowLive);

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
=======

$departureId = isset($_GET['departureId']) ? (int) $_GET['departureId'] : 0;
if ($departureId <= 0) {
    $departureId = 7;
}

$cards = require dirname(__DIR__) . '/config/site_popular_destinations_cards.php';
$departureName = ($departureId === 1) ? 'Москва' : (($departureId === 7) ? 'Самара' : 'Ваш город');

/**
 * @param array<string, mixed> $hotel
 */
function th_home_showcase_hotel_image(array $hotel): string
{
    $pic = trim((string) ($hotel['picturelink'] ?? $hotel['pictureLink'] ?? ''));
    if ($pic !== '') {
        return $pic;
    }
    $hid = (int) ($hotel['id'] ?? 0);
    if ($hid > 0) {
        return 'https://static.tourvisor.ru/hotel_pics/main400/' . $hid . '.jpg';
    }

    return '';
}

/**
 * @param array<string, mixed> $tour
 */
function th_home_showcase_tour_start(array $tour): string
{
    foreach (['flydate', 'datefrom', 'dateFrom', 'checkIn', 'checkin', 'startDate'] as $k) {
        $v = trim((string) ($tour[$k] ?? ''));
        if ($v !== '') {
            if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $v, $m)) {
                return $m[3] . '-' . $m[2] . '-' . $m[1];
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $v)) {
                return substr($v, 0, 10);
            }
        }
    }

    return '';
}

/**
 * @param array<string, mixed> $hotel
 * @param array<string, mixed> $meta country card meta
 * @return array<string, mixed>|null
 */
function th_home_showcase_pick_tour(array $hotel, array $meta, int $departureId, string $departureName): ?array
{
    $tours = is_array($hotel['tours'] ?? null) ? $hotel['tours'] : [];
    $best = null;
    $bestPrice = 0;
    foreach ($tours as $t) {
        if (!is_array($t)) {
            continue;
        }
        $p = (int) ($t['totalPrice'] ?? $t['price'] ?? $t['priceRub'] ?? 0);
        if ($p <= 0) {
            continue;
        }
        if ($best === null || $p < $bestPrice) {
            $best = $t;
            $bestPrice = $p;
        }
    }
    if ($best === null) {
        $p = (int) ($hotel['price'] ?? 0);
        if ($p <= 0) {
            return null;
        }
        $best = ['price' => $p, 'nights' => 7];
        $bestPrice = $p;
    }

    $hotelName = trim((string) ($hotel['name'] ?? ''));
    if ($hotelName === '') {
        return null;
    }

    $countryName = (string) ($meta['name'] ?? '');
    $countryFromHotel = '';
    if (is_array($hotel['country'] ?? null)) {
        $countryFromHotel = trim((string) ($hotel['country']['name'] ?? ''));
    }
    if ($countryFromHotel !== '' && $countryName === '') {
        $countryName = $countryFromHotel;
    }
    if ((int) ($meta['countryId'] ?? 0) === 47) {
        $countryName = 'Сочи';
    }

    $region = '';
    if (is_array($hotel['region'] ?? null)) {
        $region = trim((string) ($hotel['region']['name'] ?? ''));
    }

    $nights = (int) ($best['nights'] ?? 0);
    $meal = '';
    if (is_array($best['meal'] ?? null)) {
        $meal = trim((string) ($best['meal']['russianName'] ?? $best['meal']['name'] ?? ''));
    }
    $dateFrom = th_home_showcase_tour_start($best);
    $dateTo = '';
    if ($dateFrom !== '' && $nights > 0) {
        try {
            $dt = new DateTimeImmutable($dateFrom);
            $dateTo = $dt->modify('+' . $nights . ' days')->format('Y-m-d');
        } catch (Throwable $e) {
            $dateTo = '';
        }
    }

    $tourId = trim((string) ($best['id'] ?? $best['tourId'] ?? ''));
    $link = trim((string) ($hotel['hotelDescriptionLink'] ?? $hotel['hoteldescriptionlink'] ?? $hotel['link'] ?? ''));
    $image = th_home_showcase_hotel_image($hotel);
    if ($image === '') {
        $image = (string) ($meta['image'] ?? '');
    }

    $params = [
        'country' => $countryName,
        'hotel_name' => $hotelName,
        'price' => (string) $bestPrice,
        'nights' => (string) $nights,
        'meal' => $meal,
        'region' => $region,
        'departure_city' => $departureName,
        'departure_id' => (string) $departureId,
        'image' => $image,
        'adults' => '2',
        'rating' => (string) ($hotel['rating'] ?? ''),
        'category' => (string) ($hotel['category'] ?? ''),
    ];
    if ($tourId !== '') {
        $params['tour_id'] = $tourId;
    }
    if ($link !== '') {
        $params['tour_link'] = $link;
    }
    if ($dateFrom !== '') {
        $params['date_from'] = $dateFrom;
    }
    if ($dateTo !== '') {
        $params['date_to'] = $dateTo;
    }
    if (!empty($hotel['id'])) {
        $params['hotel_id'] = (string) $hotel['id'];
    }

    $href = '/frontend/window/tour-detail.php?' . http_build_query($params);

    return [
        'hotelId' => (int) ($hotel['id'] ?? 0),
        'hotelName' => $hotelName,
        'countryId' => (int) ($meta['countryId'] ?? 0),
        'countryName' => $countryName,
        'slug' => (string) ($meta['slug'] ?? ''),
        'region' => $region,
        'price' => $bestPrice,
        'nights' => $nights,
        'meal' => $meal,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
        'image' => $image,
        'stars' => (int) ($hotel['category'] ?? 0),
        'rating' => $hotel['rating'] ?? null,
        'tourId' => $tourId,
        'href' => $href,
        'promoHref' => '/frontend/window/promotions.php?departureId=' . $departureId
            . '&departureName=' . rawurlencode($departureName)
            . '&countryId=' . (int) ($meta['countryId'] ?? 0)
            . '&countryName=' . rawurlencode($countryName),
    ];
}

$tours = [];
$seenHotels = [];
foreach ($cards as $row) {
    if (!is_array($row)) {
        continue;
    }
    $countryId = (int) ($row['countryId'] ?? 0);
    if ($countryId <= 0) {
        continue;
    }
    $payload = th_promo_speed_cache_get($countryId, $departureId, true, $departureId);
    if ($payload === null) {
        // fallback: другой город вылета в кэше
        $payload = th_promo_speed_cache_get_best($countryId, $departureId, true);
    }
    $hotels = is_array($payload['results'] ?? null) ? $payload['results'] : [];
    if ($hotels === []) {
        continue;
    }
    $hotels = th_promo_filter_hotels_min_nights($hotels, $countryId);
    usort($hotels, static function (array $a, array $b): int {
        $pa = th_promo_speed_hotel_min_price($a);
        $pb = th_promo_speed_hotel_min_price($b);
        if ($pa > 0 && $pb > 0 && $pa !== $pb) {
            return $pa <=> $pb;
        }
        if ($pa > 0 && $pb === 0) {
            return -1;
        }
        if ($pb > 0 && $pa === 0) {
            return 1;
        }

        return 0;
    });

    $taken = 0;
    foreach ($hotels as $hotel) {
        if (!is_array($hotel) || $taken >= 3) {
            break;
        }
        $hid = (int) ($hotel['id'] ?? 0);
        $key = $hid > 0 ? ('h' . $hid) : ('n' . md5((string) ($hotel['name'] ?? '')));
        if (isset($seenHotels[$key])) {
            continue;
        }
        $card = th_home_showcase_pick_tour($hotel, $row, $departureId, $departureName);
        if ($card === null) {
            continue;
        }
        $seenHotels[$key] = true;
        $tours[] = $card;
        $taken++;
    }
}

usort($tours, static function (array $a, array $b): int {
    return ((int) ($a['price'] ?? 0)) <=> ((int) ($b['price'] ?? 0));
});

$moodRules = [
    'beach' => ['title' => 'На пляж', 'slugs' => ['turkey', 'egypt', 'abkhazia', 'vietnam', 'russia']],
    'mountains' => ['title' => 'В горы', 'slugs' => ['russia', 'abkhazia', 'turkey']],
    'family' => ['title' => 'С детьми', 'slugs' => ['turkey', 'egypt', 'abkhazia', 'russia']],
    'budget' => ['title' => 'Недорого', 'slugs' => ['abkhazia', 'russia', 'egypt', 'turkey']],
];

$moods = [];
foreach ($moodRules as $moodKey => $rule) {
    $slugs = is_array($rule['slugs'] ?? null) ? $rule['slugs'] : [];
    $picked = [];
    foreach ($slugs as $slug) {
        foreach ($tours as $tour) {
            if (($tour['slug'] ?? '') !== $slug) {
                continue;
            }
            $dup = false;
            foreach ($picked as $p) {
                if (($p['hotelId'] ?? 0) === ($tour['hotelId'] ?? 0) && ($p['hotelName'] ?? '') === ($tour['hotelName'] ?? '')) {
                    $dup = true;
                    break;
                }
            }
            if ($dup) {
                continue;
            }
                    $picked[] = $tour;
            if (count($picked) >= 12) {
                break 2;
            }
        }
    }
    if ($picked === []) {
        $picked = array_slice($tours, 0, 8);
    }
    if ($moodKey === 'budget') {
        usort($picked, static function (array $a, array $b): int {
            return ((int) ($a['price'] ?? 0)) <=> ((int) ($b['price'] ?? 0));
        });
    }
    $moods[$moodKey] = [
        'title' => (string) ($rule['title'] ?? $moodKey),
        'items' => array_values(array_slice($picked, 0, 12)),
    ];
}

$hotRail = array_slice($tours, 0, 16);

echo json_encode([
    'success' => true,
    'departureId' => $departureId,
    'departureName' => $departureName,
    'updatedAt' => time(),
    'mode' => 'tours',
    'moods' => $moods,
    'hot' => array_values($hotRail),
    'tours' => array_values($tours),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
>>>>>>> origin/master
