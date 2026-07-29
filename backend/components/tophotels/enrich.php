<?php
declare(strict_types=1);

/**
 * Attach TopHotels payload onto Tourvisor hotel rows (post-cache, pre-JSON).
 * Does not change Tourvisor cache keys.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/match.php';

if (!function_exists('th_tophotels_enrichment_map_path')) {
    function th_tophotels_enrichment_map_path(bool $sample = false): string
    {
        $name = $sample ? 'enrichment.sample.json' : 'enrichment.json';

        return th_tophotels_data_dir() . DIRECTORY_SEPARATOR . $name;
    }
}

if (!function_exists('th_tophotels_load_enrichment_map')) {
    /**
     * Hot-path map: tourvisor_hotel_id => tophotels enrichment object.
     *
     * @return array<string, array<string, mixed>>
     */
    function th_tophotels_load_enrichment_map(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $path = th_tophotels_enrichment_map_path(false);
        if (!is_file($path) && th_tophotels_use_fixture()) {
            $path = th_tophotels_enrichment_map_path(true);
        }
        if (!is_file($path)) {
            $cache = [];

            return $cache;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            $cache = [];

            return $cache;
        }

        $src = isset($decoded['by_tv_id']) && is_array($decoded['by_tv_id'])
            ? $decoded['by_tv_id']
            : $decoded;
        $out = [];
        foreach ($src as $tvId => $payload) {
            if (!is_array($payload)) {
                continue;
            }
            $key = trim((string) $tvId);
            if ($key === '' || $key === 'updated_at' || $key === 'by_tv_id') {
                continue;
            }
            $out[$key] = $payload;
        }
        $cache = $out;

        return $cache;
    }
}

if (!function_exists('th_tophotels_save_enrichment_map')) {
    /**
     * @param array<string, array<string, mixed>> $byTvId
     */
    function th_tophotels_save_enrichment_map(array $byTvId): bool
    {
        $dir = th_tophotels_data_dir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }
        $payload = [
            'updated_at' => gmdate('c'),
            'by_tv_id' => $byTvId,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            return false;
        }

        return file_put_contents(th_tophotels_enrichment_map_path(false), $json . "\n", LOCK_EX) !== false;
    }
}

if (!function_exists('th_tophotels_build_hotel_payload')) {
    /**
     * Canonical field attached to each hotel as `tophotels`.
     *
     * @param array<string, mixed> $ratingRow
     * @return array<string, mixed>
     */
    function th_tophotels_build_hotel_payload(string $tophotelsId, array $ratingRow = []): array
    {
        $scale = isset($ratingRow['scale']) ? (int) $ratingRow['scale'] : th_tophotels_config()['scale'];

        return [
            'id' => $tophotelsId,
            'rating' => isset($ratingRow['rating']) ? (float) $ratingRow['rating'] : null,
            'scale' => $scale,
            'reviewsCount' => isset($ratingRow['reviews_count']) ? (int) $ratingRow['reviews_count']
                : (isset($ratingRow['reviewsCount']) ? (int) $ratingRow['reviewsCount'] : null),
            'food' => isset($ratingRow['rating_food']) ? (float) $ratingRow['rating_food']
                : (isset($ratingRow['food']) ? (float) $ratingRow['food'] : null),
            'service' => isset($ratingRow['rating_service']) ? (float) $ratingRow['rating_service']
                : (isset($ratingRow['service']) ? (float) $ratingRow['service'] : null),
            'placement' => isset($ratingRow['rating_placement']) ? (float) $ratingRow['rating_placement']
                : (isset($ratingRow['placement']) ? (float) $ratingRow['placement'] : null),
            'lastReviewAt' => $ratingRow['last_review_at'] ?? $ratingRow['lastReviewAt'] ?? null,
            'matched' => true,
            'source' => 'tophotels',
        ];
    }
}

if (!function_exists('th_tophotels_enrich_hotels')) {
    /**
     * @param array<int, mixed> $hotels
     * @return array<int, mixed>
     */
    function th_tophotels_enrich_hotels(array $hotels): array
    {
        if (!th_tophotels_enabled() && !th_tophotels_use_fixture()) {
            return $hotels;
        }
        if (!th_tophotels_config()['enrich_on_proxy'] && !th_tophotels_use_fixture()) {
            return $hotels;
        }

        $map = th_tophotels_load_enrichment_map();
        if ($map === []) {
            // Fallback: matches only → empty rating shells (widgets can still bind later)
            $matches = th_tophotels_load_matches();
            if ($matches === []) {
                return $hotels;
            }
            foreach ($hotels as $idx => $hotel) {
                if (!is_array($hotel)) {
                    continue;
                }
                $tvId = trim((string) ($hotel['id'] ?? $hotel['hotelId'] ?? ''));
                if ($tvId === '' || !isset($matches[$tvId])) {
                    continue;
                }
                $hotels[$idx]['tophotels'] = th_tophotels_build_hotel_payload($matches[$tvId]);
            }

            return $hotels;
        }

        foreach ($hotels as $idx => $hotel) {
            if (!is_array($hotel)) {
                continue;
            }
            $tvId = trim((string) ($hotel['id'] ?? $hotel['hotelId'] ?? ''));
            if ($tvId === '' || !isset($map[$tvId])) {
                continue;
            }
            $payload = $map[$tvId];
            if (!isset($payload['id']) && isset($payload['tophotels_id'])) {
                $payload = th_tophotels_build_hotel_payload((string) $payload['tophotels_id'], $payload);
            } elseif (!isset($payload['matched'])) {
                $payload['matched'] = true;
                $payload['source'] = $payload['source'] ?? 'tophotels';
            }
            $hotels[$idx]['tophotels'] = $payload;
        }

        return $hotels;
    }
}
