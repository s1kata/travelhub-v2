<?php
/**
 * Ускоренный кэш акций: data/promo_cache_{countryId}_{departureId}.json + data/promo_cache_index.json
 */
declare(strict_types=1);

require_once __DIR__ . '/promo_sochi_filter.php';
require_once __DIR__ . '/operator_filter.php';

require_once __DIR__ . '/../config/departure_defaults.php';

function th_promo_speed_log(string $msg, array $ctx = []): void
{
    $root = function_exists('th_project_root') ? th_project_root() : dirname(__DIR__, 2);
    $dir = $root . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $line = date('Y-m-d H:i:s') . ' [promo-speed] ' . $msg;
    if ($ctx !== []) {
        $line .= ' ' . json_encode($ctx, JSON_UNESCAPED_UNICODE);
    }
    error_log($line);
    @file_put_contents($dir . DIRECTORY_SEPARATOR . 'promo_speed.log', $line . "\n", FILE_APPEND | LOCK_EX);
}

function th_promo_speed_data_dir(): string
{
    $root = function_exists('th_project_root') ? th_project_root() : dirname(__DIR__, 2);
    $dir = $root . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function th_promo_speed_cache_file(int $countryId, int $departureId): string
{
    return th_promo_speed_data_dir() . DIRECTORY_SEPARATOR . 'promo_cache_' . $countryId . '_' . $departureId . '.json';
}

function th_promo_speed_index_file(): string
{
    return th_promo_speed_data_dir() . DIRECTORY_SEPARATOR . 'promo_cache_index.json';
}

function th_promo_speed_ttl_seconds(): int
{
    $h = (float) (getenv('PROMO_SPEED_CACHE_TTL_HOURS') ?: ($_ENV['PROMO_SPEED_CACHE_TTL_HOURS'] ?? 12));
    $h = min(48, max(1, $h));
    return (int) ($h * 3600);
}

/** ОАЭ, Шри-Ланка, Вьетнам, Абхазия: /tours/hots → 403, promo_cache устаревает — только search-cached + onlyPromo. */
function th_promo_speed_live_search_country_ids(): array
{
    return [9, 12, 16, 46];
}

function th_promo_speed_uses_live_search(int $countryId): bool
{
    return in_array($countryId, th_promo_speed_live_search_country_ids(), true);
}

/** @return array<int, array{0: int, 1: int}> */
function th_promo_speed_night_windows(int $countryId = 0): array
{
    if (in_array($countryId, [1, 4, 13], true)) {
        return [[6, 13]];
    }
    if ($countryId === th_promo_sochi_country_id()) {
        return [[5, 14]];
    }
    if (th_promo_speed_uses_live_search($countryId)) {
        if ($countryId === 12) {
            return [[7, 14], [1, 11]];
        }
        return [[7, 14]];
    }
    return [[1, 11], [12, 22], [23, 28]];
}

function th_promo_speed_date_plus_to(int $countryId): int
{
    if (in_array($countryId, [1, 2, 4, 8, 9, 12, 13, 16, 46, 47], true)) {
        return 21;
    }
    return 7;
}

/** @return int[] countryId с fallback на обычные ближайшие туры */
function th_promo_speed_nearest_fallback_country_ids(): array
{
    return [2, 47, 8];
}

/** @return int[] Турция/Египет: добор 4★/5★ из search-cached при finalize */
function th_promo_speed_tr_eg_star_boost_country_ids(): array
{
    return [1, 4, 13];
}

/**
 * Обычный поиск (без onlyPromo) по окнам ночей.
 *
 * @param callable(array<string, string>): array $dispatch
 * @return array<int, array<int, array<string, mixed>>>
 */
function th_promo_speed_fetch_regular_window_arrays(
    int $countryId,
    int $departureId,
    array $promoDates,
    int $adults,
    ?string $childs,
    callable $dispatch
): array {
    $regularArrays = [];
    foreach (th_promo_speed_night_windows($countryId) as $promoWin) {
        $nFrom = (int) ($promoWin[0] ?? 1);
        $nTo = (int) ($promoWin[1] ?? 11);
        $winParams = [
            'type' => 'search-cached',
            'departureId' => (string) $departureId,
            'countryId' => (string) $countryId,
            'dateFrom' => $promoDates['dateFrom'],
            'dateTo' => $promoDates['dateTo'],
            'nightsFrom' => (string) $nFrom,
            'nightsTo' => (string) $nTo,
            'adults' => (string) max(1, $adults),
            'live' => '1',
        ];
        if ($childs !== null && $childs !== '') {
            $winParams['childs'] = $childs;
        }
        $winRes = $dispatch($winParams);
        if (!empty($winRes['success']) && is_array($winRes['data'] ?? null)) {
            $regularArrays[] = $winRes['data'];
        }
    }

    return $regularArrays;
}

/** Дата вылета тура YYYY-MM-DD из ответа Tourvisor. */
function th_promo_tour_start_ymd(array $tour): string
{
    foreach (['date', 'startDate', 'departureDate', 'flydate', 'flyDate'] as $key) {
        if (!isset($tour[$key]) || $tour[$key] === '') {
            continue;
        }
        $s = trim((string) $tour[$key]);
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $s, $m)) {
            return $m[1];
        }
    }

    return '';
}

/**
 * @param array<int, array<string, mixed>> $hotels
 * @return array<int, array<string, mixed>>
 */
function th_promo_filter_hotels_future_tours(array $hotels, ?string $minDateYmd = null): array
{
    $min = $minDateYmd ?? date('Y-m-d');
    $out = [];
    foreach ($hotels as $hotel) {
        if (!is_array($hotel)) {
            continue;
        }
        $tours = $hotel['tours'] ?? [];
        if (!is_array($tours)) {
            continue;
        }
        $kept = [];
        foreach ($tours as $t) {
            if (!is_array($t)) {
                continue;
            }
            $ymd = th_promo_tour_start_ymd($t);
            if ($ymd !== '' && $ymd < $min) {
                continue;
            }
            $kept[] = $t;
        }
        if ($kept === []) {
            continue;
        }
        $hotel['tours'] = array_values($kept);
        $minPrice = th_promo_speed_hotel_min_price($hotel);
        if ($minPrice > 0) {
            $hotel['price'] = $minPrice;
        }
        $out[] = $hotel;
    }

    return $out;
}

/**
 * Акционный поиск через search-cached + onlyPromo (без promo_cache_* и /tours/hots).
 *
 * @param callable(array<string, string>): array $dispatch
 * @return array<int, array<int, array<string, mixed>>>
 */
function th_promo_speed_fetch_promo_only_search_arrays(
    int $countryId,
    int $departureId,
    array $promoDates,
    int $adults,
    ?string $childs,
    callable $dispatch,
    bool $forceLive = false
): array {
    $arrays = [];
    foreach (th_promo_speed_night_windows($countryId) as $promoWin) {
        $nFrom = (int) ($promoWin[0] ?? 7);
        $nTo = (int) ($promoWin[1] ?? 14);
        $winParams = [
            'type' => 'search-cached',
            'departureId' => (string) $departureId,
            'countryId' => (string) $countryId,
            'dateFrom' => $promoDates['dateFrom'],
            'dateTo' => $promoDates['dateTo'],
            'nightsFrom' => (string) $nFrom,
            'nightsTo' => (string) $nTo,
            'adults' => (string) max(1, $adults),
            'onlyPromo' => '1',
        ];
        if ($forceLive) {
            $winParams['live'] = '1';
        }
        if ($childs !== null && $childs !== '') {
            $winParams['childs'] = $childs;
        }
        $winRes = $dispatch($winParams);
        if (!empty($winRes['success']) && is_array($winRes['data'] ?? null) && $winRes['data'] !== []) {
            $arrays[] = $winRes['data'];
        }
    }

    return $arrays;
}

/**
 * @param array{results?: array, dateFrom?: string, dateTo?: string} $cachePayload
 * @param array{dateFrom: string, dateTo: string} $promoDates
 */
function th_promo_speed_cache_is_fresh(array $cachePayload, array $promoDates): bool
{
    $today = date('Y-m-d');
    $fileFrom = trim((string) ($cachePayload['dateFrom'] ?? ''));
    $fileTo = trim((string) ($cachePayload['dateTo'] ?? ''));
    $reqFrom = trim((string) ($promoDates['dateFrom'] ?? ''));
    if ($fileFrom !== '' && $fileFrom < $today) {
        return false;
    }
    if ($fileTo !== '' && $fileTo < $today) {
        return false;
    }
    if ($reqFrom !== '' && $fileFrom !== '' && $fileFrom !== $reqFrom) {
        return false;
    }

    return true;
}

/**
 * @param array<int, array<string, mixed>> $hotels
 * @return array<int, array<string, mixed>>
 */
function th_promo_speed_prepare_live_search_hotels(
    array $hotels,
    int $countryId,
    int $departureId,
    array $promoDates
): array {
    $hotels = th_promo_filter_hotels_for_promo_country($hotels, $countryId);
    $hotels = th_departure_filter_hotels_for_departure($hotels, $departureId);
    $hotels = th_promo_filter_hotels_future_tours($hotels, $promoDates['dateFrom']);
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

    return array_slice($hotels, 0, 50);
}

/**
 * @param callable(array<string, string>): array $dispatch
 * @return array<int, array<string, mixed>>
 */
function th_promo_speed_promo_search_live(
    int $countryId,
    int $departureId,
    array $promoDates,
    int $adults,
    ?string $childs,
    callable $dispatch
): array {
    $arrays = th_promo_speed_fetch_promo_only_search_arrays(
        $countryId,
        $departureId,
        $promoDates,
        $adults,
        $childs,
        $dispatch,
        false
    );
    $merged = th_promo_speed_merge_hotels($arrays, $countryId);
    if ($merged === []) {
        $arrays = th_promo_speed_fetch_promo_only_search_arrays(
            $countryId,
            $departureId,
            $promoDates,
            $adults,
            $childs,
            $dispatch,
            true
        );
        $merged = th_promo_speed_merge_hotels($arrays, $countryId);
    }
    if ($merged === []) {
        $regularArrays = th_promo_speed_fetch_regular_window_arrays(
            $countryId,
            $departureId,
            $promoDates,
            $adults,
            $childs,
            $dispatch
        );
        $merged = th_promo_speed_merge_hotels($regularArrays, $countryId);
        if ($merged !== []) {
            th_promo_speed_log('promo_search_live_regular_fallback', [
                'countryId' => $countryId,
                'departureId' => $departureId,
                'hotels' => count($merged),
            ]);
        }
    }

    return th_promo_speed_prepare_live_search_hotels($merged, $countryId, $departureId, $promoDates);
}

/**
 * Гибрид: свежий promo_cache_* → иначе live search → откладка в файл.
 *
 * @param callable(array<string, string>): array $dispatch
 * @return array{hotels: array<int, array<string, mixed>>, source: string, fromCache: bool}
 */
function th_promo_speed_promo_search_hybrid(
    int $countryId,
    int $departureId,
    array $promoDates,
    int $adults,
    ?string $childs,
    callable $dispatch,
    bool $bypassFile = false,
    bool $cacheOnly = false
): array {
    if (!$bypassFile && !$cacheOnly) {
        $file = th_promo_speed_cache_get($countryId, $departureId, false, $departureId);
        if ($file !== null && th_promo_speed_cache_is_fresh($file, $promoDates)) {
            $fromFile = is_array($file['results'] ?? null) ? $file['results'] : [];
            $hotels = th_promo_speed_prepare_live_search_hotels($fromFile, $countryId, $departureId, $promoDates);
            if ($hotels !== []) {
                th_promo_speed_log('promo_search_hybrid_file_hit', [
                    'countryId' => $countryId,
                    'departureId' => $departureId,
                    'hotels' => count($hotels),
                    'fileDateFrom' => $file['dateFrom'] ?? '',
                ]);

                return [
                    'hotels' => $hotels,
                    'source' => 'promo_search_live_file',
                    'fromCache' => true,
                ];
            }
        }
    }
    if ($cacheOnly) {
        th_promo_speed_log('promo_search_hybrid_cache_only_miss', [
            'countryId' => $countryId,
            'departureId' => $departureId,
        ]);

        return [
            'hotels' => [],
            'source' => 'promo_search_live_cache_miss',
            'fromCache' => true,
        ];
    }
    $hotels = th_promo_speed_promo_search_live(
        $countryId,
        $departureId,
        $promoDates,
        $adults,
        $childs,
        $dispatch
    );
    if ($hotels !== []) {
        th_promo_speed_cache_set($countryId, $departureId, $hotels, $promoDates);
    }
    th_promo_speed_log('promo_search_hybrid_live', [
        'countryId' => $countryId,
        'departureId' => $departureId,
        'hotels' => count($hotels),
        'cached_written' => $hotels !== [],
    ]);

    return [
        'hotels' => $hotels,
        'source' => 'promo_search_live',
        'fromCache' => false,
    ];
}

/**
 * Доборка 5★ для Турции через обычный поиск (если /tours/hots дал мало премиума).
 *
 * @param callable(array<string, string>): array $dispatch
 * @return array<int, array<int, array<string, mixed>>>
 */
function th_promo_speed_fetch_turkey_five_star_arrays(
    int $countryId,
    int $departureId,
    array $promoDates,
    int $adults,
    ?string $childs,
    callable $dispatch
): array {
    if ($countryId !== 4) {
        return [];
    }
    $winParams = [
        'type' => 'search-cached',
        'departureId' => (string) $departureId,
        'countryId' => (string) $countryId,
        'dateFrom' => $promoDates['dateFrom'],
        'dateTo' => $promoDates['dateTo'],
        'nightsFrom' => '6',
        'nightsTo' => '13',
        'adults' => (string) max(1, $adults),
        'hotelCategory' => '5',
        'live' => '1',
    ];
    if ($childs !== null && $childs !== '') {
        $winParams['childs'] = $childs;
    }
    $winRes = $dispatch($winParams);
    if (!empty($winRes['success']) && is_array($winRes['data'] ?? null) && $winRes['data'] !== []) {
        return [$winRes['data']];
    }

    return [];
}

/**
 * Доборка 4★ для Турции (/tours/hots часто без четвёрок).
 *
 * @param callable(array<string, string>): array $dispatch
 * @return array<int, array<int, array<string, mixed>>>
 */
function th_promo_speed_fetch_turkey_four_star_arrays(
    int $countryId,
    int $departureId,
    array $promoDates,
    int $adults,
    ?string $childs,
    callable $dispatch
): array {
    if ($countryId !== 4) {
        return [];
    }
    $winParams = [
        'type' => 'search-cached',
        'departureId' => (string) $departureId,
        'countryId' => (string) $countryId,
        'dateFrom' => $promoDates['dateFrom'],
        'dateTo' => $promoDates['dateTo'],
        'nightsFrom' => '6',
        'nightsTo' => '13',
        'adults' => (string) max(1, $adults),
        'hotelCategory' => '4',
        'live' => '1',
    ];
    if ($childs !== null && $childs !== '') {
        $winParams['childs'] = $childs;
    }
    $winRes = $dispatch($winParams);
    if (!empty($winRes['success']) && is_array($winRes['data'] ?? null) && $winRes['data'] !== []) {
        return [$winRes['data']];
    }

    return [];
}

/**
 * Египет: добор 5★ (hots часто отдаёт 2–3 премиум-отеля).
 *
 * @param callable(array<string, string>): array $dispatch
 * @return array<int, array<int, array<string, mixed>>>
 */
function th_promo_speed_fetch_egypt_five_star_arrays(
    int $countryId,
    int $departureId,
    array $promoDates,
    int $adults,
    ?string $childs,
    callable $dispatch
): array {
    if (!in_array($countryId, [1, 13], true)) {
        return [];
    }
    $winParams = [
        'type' => 'search-cached',
        'departureId' => (string) $departureId,
        'countryId' => (string) $countryId,
        'dateFrom' => $promoDates['dateFrom'],
        'dateTo' => $promoDates['dateTo'],
        'nightsFrom' => '6',
        'nightsTo' => '13',
        'adults' => (string) max(1, $adults),
        'hotelCategory' => '5',
        'live' => '1',
    ];
    if ($childs !== null && $childs !== '') {
        $winParams['childs'] = $childs;
    }
    $winRes = $dispatch($winParams);
    if (!empty($winRes['success']) && is_array($winRes['data'] ?? null) && $winRes['data'] !== []) {
        return [$winRes['data']];
    }

    return [];
}

/**
 * Египет: один добор search-cached от 3★ (3–5★), hots часто без четвёрок.
 *
 * @param callable(array<string, string>): array $dispatch
 * @return array<int, array<int, array<string, mixed>>>
 */
function th_promo_speed_fetch_egypt_three_star_arrays(
    int $countryId,
    int $departureId,
    array $promoDates,
    int $adults,
    ?string $childs,
    callable $dispatch
): array {
    if (!in_array($countryId, [1, 13], true)) {
        return [];
    }
    $winParams = [
        'type' => 'search-cached',
        'departureId' => (string) $departureId,
        'countryId' => (string) $countryId,
        'dateFrom' => $promoDates['dateFrom'],
        'dateTo' => $promoDates['dateTo'],
        'nightsFrom' => '6',
        'nightsTo' => '13',
        'adults' => (string) max(1, $adults),
        'hotelCategory' => '3',
        'live' => '1',
    ];
    if ($childs !== null && $childs !== '') {
        $winParams['childs'] = $childs;
    }
    $winRes = $dispatch($winParams);
    if (!empty($winRes['success']) && is_array($winRes['data'] ?? null) && $winRes['data'] !== []) {
        return [$winRes['data']];
    }

    return [];
}

/**
 * @param array<int, array<string, mixed>> $out
 * @param array<int, array<int, array<string, mixed>>> $starArrays
 * @return array<int, array<string, mixed>>
 */
/** @return int Количество отелей с category в диапазоне [minStars, maxStars]. */
function th_promo_speed_count_hotels_in_star_range(array $hotels, int $minStars, int $maxStars): int
{
    $n = 0;
    foreach ($hotels as $h) {
        if (!is_array($h)) {
            continue;
        }
        $c = (int) ($h['category'] ?? 0);
        if ($c >= $minStars && $c <= $maxStars) {
            $n++;
        }
    }

    return $n;
}

/**
 * Устаревший promo_speed_file для TR/EG: только hots, мало 4★/5★ — добираем search-cached.
 *
 * @param array<int, array<string, mixed>> $hotels
 */
function th_promo_speed_tr_eg_needs_star_boost(array $hotels, int $countryId): bool
{
    if (!in_array($countryId, th_promo_speed_tr_eg_star_boost_country_ids(), true)) {
        return false;
    }
    if ($countryId === 4) {
        return th_promo_speed_count_hotels_in_star_range($hotels, 4, 5) < 8;
    }
    if (in_array($countryId, [1, 13], true)) {
        return th_promo_speed_count_hotels_in_star_range($hotels, 5, 5) < 6;
    }

    return false;
}

/**
 * @param array<int, array<string, mixed>> $out
 * @return array<int, array<string, mixed>>
 */
function th_promo_speed_apply_tr_eg_star_boosts(
    array $out,
    int $countryId,
    int $departureId,
    array $promoDates,
    int $adults,
    ?string $childs,
    callable $dispatch
): array {
    if (!in_array($countryId, th_promo_speed_tr_eg_star_boost_country_ids(), true)) {
        return $out;
    }
    $out = th_promo_speed_merge_star_boost(
        $out,
        th_promo_speed_fetch_turkey_four_star_arrays($countryId, $departureId, $promoDates, $adults, $childs, $dispatch),
        $countryId,
        $departureId,
        'promo_finalize_turkey_four_star_boost'
    );
    $out = th_promo_speed_merge_star_boost(
        $out,
        th_promo_speed_fetch_turkey_five_star_arrays($countryId, $departureId, $promoDates, $adults, $childs, $dispatch),
        $countryId,
        $departureId,
        'promo_finalize_turkey_five_star_boost'
    );
    $out = th_promo_speed_merge_star_boost(
        $out,
        th_promo_speed_fetch_egypt_three_star_arrays($countryId, $departureId, $promoDates, $adults, $childs, $dispatch),
        $countryId,
        $departureId,
        'promo_finalize_egypt_three_star_boost'
    );
    $out = th_promo_speed_merge_star_boost(
        $out,
        th_promo_speed_fetch_egypt_five_star_arrays($countryId, $departureId, $promoDates, $adults, $childs, $dispatch),
        $countryId,
        $departureId,
        'promo_finalize_egypt_five_star_boost'
    );

    return $out;
}

function th_promo_speed_merge_star_boost(
    array $out,
    array $starArrays,
    int $countryId,
    int $departureId,
    string $logEvent
): array {
    if ($starArrays === []) {
        return $out;
    }
    $boost = th_promo_speed_merge_hotels($starArrays, $countryId);
    $boost = th_promo_filter_hotels_for_promo_country($boost, $countryId);
    $boost = th_departure_filter_hotels_for_departure($boost, $departureId);
    if ($boost === []) {
        return $out;
    }
    $combined = th_promo_speed_merge_hotels([$out, $boost], $countryId);
    th_promo_speed_log($logEvent, [
        'countryId' => $countryId,
        'departureId' => $departureId,
        'boost_hotels' => count($boost),
        'combined' => count($combined),
    ]);

    return $combined;
}

/**
 * Promo + regular fallback (Сочи, Мальдивы): minPrice и выдача из объединённого списка.
 *
 * @param array<int, array<string, mixed>> $promoMerged
 * @param callable(array<string, string>): array $dispatch
 * @return array<int, array<string, mixed>>
 */
function th_promo_speed_finalize_merged(
    array $promoMerged,
    int $countryId,
    int $departureId,
    array $promoDates,
    int $adults,
    ?string $childs,
    callable $dispatch
): array {
    $out = th_promo_filter_hotels_for_promo_country($promoMerged, $countryId);
    $out = th_departure_filter_hotels_for_departure($out, $departureId);

    /* Турция: 4★+5★; Египет: 3★+5★ (hots часто без премиума). */
    $out = th_promo_speed_apply_tr_eg_star_boosts(
        $out,
        $countryId,
        $departureId,
        $promoDates,
        $adults,
        $childs,
        $dispatch
    );

    if (!in_array($countryId, th_promo_speed_nearest_fallback_country_ids(), true)) {
        return $out;
    }

    $regularArrays = th_promo_speed_fetch_regular_window_arrays(
        $countryId,
        $departureId,
        $promoDates,
        $adults,
        $childs,
        $dispatch
    );
    if ($regularArrays === []) {
        return $out;
    }

    $regularMerged = th_promo_speed_merge_hotels($regularArrays, $countryId);
    $regularMerged = th_promo_filter_hotels_for_promo_country($regularMerged, $countryId);
    $regularMerged = th_departure_filter_hotels_for_departure($regularMerged, $departureId);

    if ($out === []) {
        th_promo_speed_log('promo_finalize_regular_only', [
            'countryId' => $countryId,
            'departureId' => $departureId,
            'hotels' => count($regularMerged),
        ]);
        return th_departure_filter_hotels_for_departure($regularMerged, $departureId);
    }

    if ($regularMerged === []) {
        return $out;
    }

    $combined = th_promo_speed_merge_hotels([$out, $regularMerged], $countryId);
    th_promo_speed_log('promo_finalize_regular_merged', [
        'countryId' => $countryId,
        'departureId' => $departureId,
        'promo_hotels' => count($out),
        'regular_hotels' => count($regularMerged),
        'combined' => count($combined),
    ]);

    return th_promo_filter_hotels_for_promo_country(
        th_departure_filter_hotels_for_departure($combined, $departureId),
        $countryId
    );
}

/** @return array{dateFrom: string, dateTo: string} */
function th_promo_speed_promo_dates(int $countryId): array
{
    $plusTo = th_promo_speed_date_plus_to($countryId);
    return [
        'dateFrom' => date('Y-m-d'),
        'dateTo' => date('Y-m-d', strtotime('+' . $plusTo . ' days')),
    ];
}

/** Минимум ночей для цены на плитке и в index (как promoMinNightsForCountry в promotions-page.js). */
function th_promo_min_nights_for_country(int $countryId): int
{
    if (in_array($countryId, [1, 4, 13], true)) {
        return 6;
    }
    if ($countryId === th_promo_sochi_country_id()) {
        return 5;
    }

    return 0;
}

function th_promo_max_nights_for_country(int $countryId): int
{
    if (in_array($countryId, [1, 4, 13], true)) {
        return 13;
    }
    if ($countryId === th_promo_sochi_country_id()) {
        return 14;
    }

    return 0;
}

/**
 * @param array<int, array<string, mixed>> $hotels
 * @return array<int, array<string, mixed>>
 */
function th_promo_filter_hotels_min_nights(array $hotels, int $countryId): array
{
    $minN = th_promo_min_nights_for_country($countryId);
    $maxN = th_promo_max_nights_for_country($countryId);
    if ($minN <= 0) {
        return $hotels;
    }
    $out = [];
    foreach ($hotels as $hotel) {
        if (!is_array($hotel)) {
            continue;
        }
        $tours = $hotel['tours'] ?? [];
        if (!is_array($tours)) {
            continue;
        }
        $kept = [];
        foreach ($tours as $t) {
            if (!is_array($t)) {
                continue;
            }
            $n = (int) ($t['nights'] ?? 0);
            if ($n === 0 || ($n >= $minN && ($maxN <= 0 || $n <= $maxN))) {
                $kept[] = $t;
            }
        }
        if ($kept === []) {
            continue;
        }
        $hotel['tours'] = array_values($kept);
        $min = th_promo_speed_hotel_min_price($hotel);
        if ($min > 0) {
            $hotel['price'] = $min;
        }
        $out[] = $hotel;
    }

    return $out;
}

/** @return array{departureId: int, countryId: int}[] */
function th_promo_speed_warm_departures(): array
{
    return [
        ['departureId' => 7, 'name' => 'Самара'],
        ['departureId' => 1, 'name' => 'Москва'],
    ];
}

function th_promo_speed_hotel_country_id(array $h): string
{
    if (isset($h['country']['id']) && $h['country']['id'] !== '') {
        return (string) $h['country']['id'];
    }
    if (isset($h['countryId']) && $h['countryId'] !== '') {
        return (string) $h['countryId'];
    }
    if (isset($h['country_id']) && $h['country_id'] !== '') {
        return (string) $h['country_id'];
    }
    return '';
}

/**
 * Слияние отелей из нескольких окон ночей (как mergePromoHotelDataArrays на фронте, без loose-fallback).
 *
 * @param array<int, array<int, array<string, mixed>>> $dataArrays
 * @return array<int, array<string, mixed>>
 */
function th_promo_speed_merge_hotels(array $dataArrays, int $expectedCountryId): array
{
    $ec = $expectedCountryId > 0 ? (string) $expectedCountryId : '';
    $byKey = [];
    foreach ($dataArrays as $arr) {
        if (!is_array($arr)) {
            continue;
        }
        foreach ($arr as $h) {
            if (!is_array($h) || !isset($h['id'])) {
                continue;
            }
            if ($ec !== '') {
                $cid = th_promo_speed_hotel_country_id($h);
                if ($cid !== '' && $cid !== $ec) {
                    continue;
                }
            }
            $hid = (string) $h['id'];
            $key = $ec !== '' ? ($ec . '_' . $hid) : $hid;
            if (!isset($byKey[$key])) {
                $byKey[$key] = $h;
                continue;
            }
            $merged = &$byKey[$key];
            $tourIds = [];
            foreach (($merged['tours'] ?? []) as $t) {
                if (is_array($t) && isset($t['id'])) {
                    $tourIds[(string) $t['id']] = true;
                }
            }
            foreach (($h['tours'] ?? []) as $t) {
                if (!is_array($t) || !isset($t['id'])) {
                    continue;
                }
                $tid = (string) $t['id'];
                if (isset($tourIds[$tid])) {
                    continue;
                }
                $tourIds[$tid] = true;
                if (!isset($merged['tours']) || !is_array($merged['tours'])) {
                    $merged['tours'] = [];
                }
                $merged['tours'][] = $t;
            }
            unset($merged);
        }
    }
    $merged = array_values($byKey);
    if ($expectedCountryId === th_promo_sochi_country_id()) {
        $merged = th_promo_filter_hotels_sochi_resort_only($merged);
    }

    return $merged;
}

/** Имя туроператора из тура (Tourvisor: operatorName или operator). */
function th_promo_tour_operator_label(array $tour): string
{
    if (isset($tour['operatorName']) && is_string($tour['operatorName'])) {
        return trim($tour['operatorName']);
    }
    if (isset($tour['operator'])) {
        if (is_string($tour['operator'])) {
            return trim($tour['operator']);
        }
        if (is_array($tour['operator'])) {
            return trim((string) ($tour['operator']['name'] ?? $tour['operator']['russianName'] ?? $tour['operator']['title'] ?? ''));
        }
    }

    return '';
}

/**
 * Оставляет только туры операторов из белого списка (как operator_filter.php).
 * Для Турции/Египта — сокращённый список.
 *
 * @param array<int, array<string, mixed>> $hotels
 * @return array<int, array<string, mixed>>
 */
function th_promo_filter_hotels_by_allowed_operators(array $hotels, int $countryId = 0, string $countryName = ''): array
{
    $allowedTokens = th_operator_allowed_tokens($countryId, $countryName);
    if ($allowedTokens === []) {
        return $hotels;
    }
    $out = [];
    foreach ($hotels as $hotel) {
        if (!is_array($hotel)) {
            continue;
        }
        $tours = $hotel['tours'] ?? [];
        if (!is_array($tours) || $tours === []) {
            continue;
        }
        $kept = [];
        foreach ($tours as $t) {
            if (!is_array($t)) {
                continue;
            }
            if (th_operator_label_allowed(th_promo_tour_operator_label($t), $allowedTokens)) {
                $kept[] = $t;
            }
        }
        if ($kept === []) {
            continue;
        }
        $hotel['tours'] = array_values($kept);
        $min = th_promo_speed_hotel_min_price($hotel);
        if ($min > 0) {
            $hotel['price'] = $min;
        }
        $out[] = $hotel;
    }

    return $out;
}

function th_promo_speed_hotel_min_price(array $hotel): int
{
    $min = 0;
    foreach (($hotel['tours'] ?? []) as $t) {
        if (!is_array($t)) {
            continue;
        }
        $p = (int) ($t['totalPrice'] ?? $t['price'] ?? 0);
        if ($p > 0 && ($min === 0 || $p < $min)) {
            $min = $p;
        }
    }
    return $min;
}

/**
 * @return array{results: array, cachedAt: int, departureId: int, countryId: int, dateFrom: string, dateTo: string}|null
 */
/**
 * @param int|null $filterDepartureId город вылета для фильтра туров (если файл с другого departureId — fallback)
 */
function th_promo_speed_cache_get(int $countryId, int $departureId, bool $ignoreTtl = false, ?int $filterDepartureId = null): ?array
{
    if ($countryId <= 0 || $departureId <= 0) {
        return null;
    }
    $file = th_promo_speed_cache_file($countryId, $departureId);
    if (!is_file($file)) {
        return null;
    }
    if (!$ignoreTtl) {
        $age = time() - (int) filemtime($file);
        if ($age >= th_promo_speed_ttl_seconds()) {
            return null;
        }
    }
    $raw = @file_get_contents($file);
    if ($raw === false || $raw === '') {
        return null;
    }
    $d = json_decode($raw, true);
    if (!is_array($d) || !isset($d['results']) || !is_array($d['results'])) {
        return null;
    }
    if ($d['results'] === []) {
        return null;
    }
    $filterDep = ($filterDepartureId !== null && $filterDepartureId > 0) ? $filterDepartureId : $departureId;
    $d['results'] = th_departure_filter_hotels_for_departure($d['results'], $filterDep);
    if ($d['results'] === []) {
        return null;
    }

    return $d;
}

/** @param array<int, array<string, mixed>> $hotels */
/**
 * Файл с турами: свой departureId + warm-города; при промахе live — stale (ignore TTL).
 *
 * @return array{results: array, cachedAt: int, departureId: int, countryId: int, dateFrom: string, dateTo: string}|null
 */
function th_promo_speed_cache_get_best(
    int $countryId,
    int $requestedDepartureId,
    bool $ignoreTtl = false
): ?array {
    if ($countryId <= 0 || $requestedDepartureId <= 0) {
        return null;
    }
    $tryDeps = [$requestedDepartureId];
    foreach (th_promo_speed_warm_departures() as $depRow) {
        $id = (int) ($depRow['departureId'] ?? 0);
        if ($id > 0 && !in_array($id, $tryDeps, true)) {
            $tryDeps[] = $id;
        }
    }
    foreach ($tryDeps as $fileDepId) {
        $hit = th_promo_speed_cache_get($countryId, $fileDepId, $ignoreTtl, $requestedDepartureId);
        if ($hit !== null) {
            return $hit;
        }
    }

    return null;
}

/**
 * Те же отели, что promo-search при чтении promo_cache_*: фильтр страны, ночи, star-boost TR/EG, fallback Сочи/Мальдивы.
 *
 * @param array{results?: array<int, array<string, mixed>>, dateFrom?: string, dateTo?: string} $cachePayload
 * @param callable(array<string, string>): array $dispatch
 * @return array<int, array<string, mixed>>
 */
function th_promo_speed_hotels_from_cache_payload(
    array $cachePayload,
    int $countryId,
    int $departureId,
    array $promoDates,
    callable $dispatch,
    int $adults = 2,
    ?string $childs = null
): array {
    $hotelsFromFile = th_promo_filter_hotels_min_nights(
        th_promo_filter_hotels_for_promo_country(
            is_array($cachePayload['results'] ?? null) ? $cachePayload['results'] : [],
            $countryId
        ),
        $countryId
    );
    if (in_array($countryId, th_promo_speed_nearest_fallback_country_ids(), true)) {
        $hotelsFromFile = th_promo_speed_finalize_merged(
            $hotelsFromFile,
            $countryId,
            $departureId,
            $promoDates,
            $adults,
            $childs,
            $dispatch
        );
        $hotelsFromFile = th_promo_filter_hotels_min_nights($hotelsFromFile, $countryId);
    } elseif (th_promo_speed_tr_eg_needs_star_boost($hotelsFromFile, $countryId)) {
        $boostBase = th_promo_filter_hotels_for_promo_country($hotelsFromFile, $countryId);
        $boostBase = th_departure_filter_hotels_for_departure($boostBase, $departureId);
        $boosted = th_promo_speed_apply_tr_eg_star_boosts(
            $boostBase,
            $countryId,
            $departureId,
            $promoDates,
            $adults,
            $childs,
            $dispatch
        );
        $hotelsFromFile = th_promo_filter_hotels_min_nights($boosted, $countryId);
    }

    return $hotelsFromFile;
}

/** @param array<int, array<string, mixed>> $hotels */
function th_promo_speed_cache_set(int $countryId, int $departureId, array $hotels, array $meta = []): void
{
    if ($countryId <= 0 || $departureId <= 0 || $hotels === []) {
        return;
    }
    $dates = th_promo_speed_promo_dates($countryId);
    $payload = [
        'results' => $hotels,
        'cachedAt' => time(),
        'countryId' => $countryId,
        'departureId' => $departureId,
        'dateFrom' => (string) ($meta['dateFrom'] ?? $dates['dateFrom']),
        'dateTo' => (string) ($meta['dateTo'] ?? $dates['dateTo']),
    ];
    @file_put_contents(
        th_promo_speed_cache_file($countryId, $departureId),
        json_encode($payload, JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
    th_promo_speed_log('cache_set', [
        'countryId' => $countryId,
        'departureId' => $departureId,
        'hotels' => count($hotels),
    ]);
}

/**
 * Манифест для страницы акций: minPrice из promo_cache_* с фильтром ночей (как список туров).
 *
 * @return array<string, array<string, array{has: bool, minPrice?: int}>>
 */
function th_promo_speed_index_for_frontend(): array
{
    $index = th_promo_speed_index_get(true);
    foreach ($index as $depKey => $countries) {
        if (!is_array($countries)) {
            continue;
        }
        $departureId = (int) $depKey;
        if ($departureId <= 0) {
            continue;
        }
        foreach ($countries as $cid => $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $countryId = (int) $cid;
            if ($countryId <= 0) {
                continue;
            }
            $file = th_promo_speed_cache_get($countryId, $departureId, true);
            if ($file === null || !is_array($file['results'] ?? null)) {
                continue;
            }
            $hotels = th_promo_filter_hotels_min_nights($file['results'], $countryId);
            $min = 0;
            foreach ($hotels as $h) {
                if (!is_array($h)) {
                    continue;
                }
                $p = th_promo_speed_hotel_min_price($h);
                if ($p > 0 && ($min === 0 || $p < $min)) {
                    $min = $p;
                }
            }
            $index[$depKey][$cid] = [
                'has' => count($hotels) > 0,
            ];
            if ($min > 0) {
                $index[$depKey][$cid]['minPrice'] = $min;
            }
        }
    }

    return $index;
}

/** @return array<string, array<string, array{has: bool, minPrice?: int}>> */
function th_promo_speed_index_get(bool $ignoreTtl = false): array
{
    $file = th_promo_speed_index_file();
    if (!is_file($file)) {
        return [];
    }
    if (!$ignoreTtl) {
        $age = time() - (int) filemtime($file);
        if ($age >= th_promo_speed_ttl_seconds()) {
            return [];
        }
    }
    $raw = @file_get_contents($file);
    if ($raw === false || $raw === '') {
        return [];
    }
    $d = json_decode($raw, true);
    if (!is_array($d)) {
        return [];
    }
    if (isset($d['departures']) && is_array($d['departures'])) {
        return $d['departures'];
    }
    return $d;
}

/** @param array<string, array<string, array{has: bool, minPrice?: int}>> $index */
function th_promo_speed_index_set(array $index): void
{
    @file_put_contents(th_promo_speed_index_file(), json_encode($index, JSON_UNESCAPED_UNICODE), LOCK_EX);
    th_promo_speed_log('index_set', ['departures' => count($index)]);
}

function th_promo_speed_index_update_country(int $departureId, int $countryId, array $hotels): void
{
    $index = th_promo_speed_index_get(true);
    $depKey = (string) $departureId;
    if (!isset($index[$depKey]) || !is_array($index[$depKey])) {
        $index[$depKey] = [];
    }
    $hotelsForTile = th_promo_filter_hotels_min_nights($hotels, $countryId);
    $min = 0;
    if ($hotelsForTile !== []) {
        foreach ($hotelsForTile as $h) {
            if (!is_array($h)) {
                continue;
            }
            $p = th_promo_speed_hotel_min_price($h);
            if ($p > 0 && ($min === 0 || $p < $min)) {
                $min = $p;
            }
        }
    }
    $hotels = $hotelsForTile;
    $entry = ['has' => count($hotels) > 0];
    if ($min > 0) {
        $entry['minPrice'] = $min;
    }
    $index[$depKey][(string) $countryId] = $entry;
    th_promo_speed_index_set($index);
}
