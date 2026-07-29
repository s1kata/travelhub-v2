<?php
declare(strict_types=1);

/**
 * Build data/tophotels/enrichment.json from ratings feed + matches.
 * Run via cron after partner credentials arrive (or with TOPHOTELS_USE_FIXTURE=1).
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/client.php';
require_once __DIR__ . '/match.php';
require_once __DIR__ . '/enrich.php';
require_once __DIR__ . '/schema.php';

if (!function_exists('th_tophotels_sync')) {
    /**
     * @return array{
     *   ok: bool,
     *   ratings: int,
     *   matched: int,
     *   enrichment_path: string,
     *   error: ?string
     * }
     */
    function th_tophotels_sync(?PDO $pdo = null): array
    {
        $enrichmentPath = th_tophotels_enrichment_map_path(false);
        try {
            $ratings = th_tophotels_fetch_ratings_feed();
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'ratings' => 0,
                'matched' => 0,
                'enrichment_path' => $enrichmentPath,
                'error' => $e->getMessage(),
            ];
        }

        $byThId = [];
        foreach ($ratings as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = trim((string) ($row['tophotels_id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $byThId[$id] = $row;

            if ($pdo instanceof PDO) {
                try {
                    th_tophotels_ensure_schema($pdo);
                    $driver = strtolower((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
                    $syncedAt = $driver === 'mysql' ? date('Y-m-d H:i:s') : date('c');
                    $params = [
                        $id,
                        $row['rating'] ?? null,
                        (int) ($row['scale'] ?? th_tophotels_config()['scale']),
                        $row['reviews_count'] ?? null,
                        $row['rating_food'] ?? null,
                        $row['rating_service'] ?? null,
                        $row['rating_placement'] ?? null,
                        $row['last_review_at'] ?? null,
                        json_encode($row, JSON_UNESCAPED_UNICODE),
                        $syncedAt,
                    ];
                    if ($driver === 'mysql') {
                        $pdo->prepare(
                            'INSERT INTO tophotels_ratings
                                (tophotels_id, rating, scale, reviews_count, rating_food, rating_service,
                                 rating_placement, last_review_at, raw_json, synced_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE
                                rating = VALUES(rating),
                                scale = VALUES(scale),
                                reviews_count = VALUES(reviews_count),
                                rating_food = VALUES(rating_food),
                                rating_service = VALUES(rating_service),
                                rating_placement = VALUES(rating_placement),
                                last_review_at = VALUES(last_review_at),
                                raw_json = VALUES(raw_json),
                                synced_at = VALUES(synced_at)'
                        )->execute($params);
                    } else {
                        $pdo->prepare(
                            'INSERT OR REPLACE INTO tophotels_ratings
                                (tophotels_id, rating, scale, reviews_count, rating_food, rating_service,
                                 rating_placement, last_review_at, raw_json, synced_at, updated_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                        )->execute([...$params, $syncedAt]);
                    }
                } catch (Throwable $e) {
                    error_log('[tophotels] ratings DB upsert: ' . $e->getMessage());
                }
            }
        }

        $matches = th_tophotels_load_matches();
        $byTvId = [];
        foreach ($matches as $tvId => $thId) {
            if (!isset($byThId[$thId])) {
                $byTvId[(string) $tvId] = th_tophotels_build_hotel_payload((string) $thId);
                continue;
            }
            $byTvId[(string) $tvId] = th_tophotels_build_hotel_payload((string) $thId, $byThId[$thId]);
        }

        $saved = th_tophotels_save_enrichment_map($byTvId);

        return [
            'ok' => $saved,
            'ratings' => count($byThId),
            'matched' => count($byTvId),
            'enrichment_path' => $enrichmentPath,
            'error' => $saved ? null : 'failed_to_write_enrichment_json',
        ];
    }
}
