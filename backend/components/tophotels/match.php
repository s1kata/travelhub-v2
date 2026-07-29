<?php
declare(strict_types=1);

/**
 * Tourvisor hotel id ↔ TopHotels id mapping (file + optional DB).
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/schema.php';

if (!function_exists('th_tophotels_matches_path')) {
    function th_tophotels_matches_path(bool $sample = false): string
    {
        $name = $sample ? 'matches.sample.json' : 'matches.json';

        return th_tophotels_data_dir() . DIRECTORY_SEPARATOR . $name;
    }
}

if (!function_exists('th_tophotels_load_matches')) {
    /**
     * @return array<string, string> tourvisor_hotel_id => tophotels_id
     */
    function th_tophotels_load_matches(bool $reload = false): array
    {
        static $cache = null;
        if ($reload) {
            $cache = null;
        }
        if (is_array($cache)) {
            return $cache;
        }

        $path = th_tophotels_matches_path(false);
        if (!is_file($path) && th_tophotels_use_fixture()) {
            $path = th_tophotels_matches_path(true);
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

        $map = [];
        $src = isset($decoded['by_tv_id']) && is_array($decoded['by_tv_id'])
            ? $decoded['by_tv_id']
            : $decoded;
        foreach ($src as $tvId => $thId) {
            if (is_array($thId)) {
                $thId = $thId['tophotels_id'] ?? $thId['id'] ?? '';
            }
            $tv = trim((string) $tvId);
            $th = trim((string) $thId);
            if ($tv !== '' && $th !== '' && $tv !== 'by_tv_id' && $tv !== 'updated_at') {
                $map[$tv] = $th;
            }
        }
        $cache = $map;

        return $cache;
    }
}

if (!function_exists('th_tophotels_save_matches')) {
    /**
     * @param array<string, string> $map
     */
    function th_tophotels_save_matches(array $map): bool
    {
        $dir = th_tophotels_data_dir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }
        $payload = [
            'updated_at' => gmdate('c'),
            'by_tv_id' => $map,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            return false;
        }
        $ok = file_put_contents(th_tophotels_matches_path(false), $json . "\n", LOCK_EX) !== false;
        if ($ok) {
            th_tophotels_load_matches(true);
        }

        return $ok;
    }
}

if (!function_exists('th_tophotels_import_matches_csv')) {
    /**
     * CSV: tourvisor_hotel_id,tophotels_id[,hotel_name[,country_name]]
     *
     * @return array{imported: int, skipped: int, path: string}
     */
    function th_tophotels_import_matches_csv(string $csvPath, ?PDO $pdo = null): array
    {
        if (!is_file($csvPath)) {
            throw new InvalidArgumentException('CSV not found: ' . $csvPath);
        }
        $fh = fopen($csvPath, 'rb');
        if ($fh === false) {
            throw new RuntimeException('Cannot open CSV');
        }

        $map = th_tophotels_load_matches();
        $imported = 0;
        $skipped = 0;
        $lineNo = 0;
        while (($row = fgetcsv($fh)) !== false) {
            $lineNo++;
            if ($row === [null] || $row === false) {
                continue;
            }
            if ($lineNo === 1 && isset($row[0]) && stripos((string) $row[0], 'tourvisor') !== false) {
                continue; // header
            }
            $tvId = trim((string) ($row[0] ?? ''));
            $thId = trim((string) ($row[1] ?? ''));
            if ($tvId === '' || $thId === '' || !ctype_digit($tvId)) {
                $skipped++;
                continue;
            }
            $map[$tvId] = $thId;
            $imported++;

            if ($pdo instanceof PDO) {
                th_tophotels_ensure_schema($pdo);
                $name = trim((string) ($row[2] ?? ''));
                $country = trim((string) ($row[3] ?? ''));
                $stmt = $pdo->prepare(
                    'INSERT INTO tophotels_hotel_match
                        (tourvisor_hotel_id, tophotels_id, hotel_name, country_name, match_source, is_active, updated_at)
                     VALUES (?, ?, ?, ?, ?, 1, ?)
                     ON DUPLICATE KEY UPDATE
                        tophotels_id = VALUES(tophotels_id),
                        hotel_name = VALUES(hotel_name),
                        country_name = VALUES(country_name),
                        match_source = VALUES(match_source),
                        is_active = 1,
                        updated_at = VALUES(updated_at)'
                );
                // SQLite has no ON DUPLICATE — best-effort for MySQL; ignore failures on sqlite
                try {
                    $driver = strtolower((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
                    if ($driver === 'mysql') {
                        $stmt->execute([(int) $tvId, $thId, $name !== '' ? $name : null, $country !== '' ? $country : null, 'csv', date('Y-m-d H:i:s')]);
                    } else {
                        $pdo->prepare(
                            'INSERT OR REPLACE INTO tophotels_hotel_match
                                (tourvisor_hotel_id, tophotels_id, hotel_name, country_name, match_source, is_active, updated_at)
                             VALUES (?, ?, ?, ?, ?, 1, ?)'
                        )->execute([(int) $tvId, $thId, $name !== '' ? $name : null, $country !== '' ? $country : null, 'csv', date('c')]);
                    }
                } catch (Throwable $e) {
                    error_log('[tophotels] match DB upsert: ' . $e->getMessage());
                }
            }
        }
        fclose($fh);
        th_tophotels_save_matches($map);

        return ['imported' => $imported, 'skipped' => $skipped, 'path' => th_tophotels_matches_path(false)];
    }
}
